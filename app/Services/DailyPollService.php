<?php

namespace App\Services;

use App\Components\RocketChat\Contracts\RocketChatClientContract;
use App\Components\RocketChat\RocketChatException;
use App\Models\DailyPollSession;
use App\Models\Fact;
use App\Models\FactUsage;
use App\Models\PollChannel;
use App\Models\ReminderAbsencePeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DailyPollService
{
    public function __construct(
        private readonly RocketChatClientContract $rocket,
    ) {}

    public function today(string $timezone): Carbon
    {
        return Carbon::now($timezone)->startOfDay();
    }

    public function runMorningPolls(string $timezone): void
    {
        $today = $this->today($timezone)->toDateString();
        $morningLine = (string) config('bot.poll.morning_text', '@all Что в работе?');

        foreach (PollChannel::query()->pollActive()->get() as $channel) {
            try {
                if (DailyPollSession::query()->where('poll_channel_id', $channel->id)->where('poll_date', $today)->exists()) {
                    continue;
                }

                $year = (int) Carbon::now($timezone)->year;
                $usedFactIds = FactUsage::query()->where('usage_year', $year)->pluck('fact_id');
                $fact = Fact::query()->where('is_active', true)->whereNotIn('id', $usedFactIds)->inRandomOrder()->first();

                if (! $fact) {
                    Log::warning('Morning poll skipped: no unused facts this year', ['channel' => $channel->id]);

                    continue;
                }

                $body = $morningLine."\n".$fact->body;
                $msg = $this->rocket->sendMessage($channel->rocket_room_id, $body);

                $mid = (string) ($msg['_id'] ?? '');
                if ($mid === '') {
                    throw new RocketChatException('Morning poll: missing message id in API response.');
                }

                DB::transaction(function () use ($channel, $today, $mid, $fact, $year): void {
                    DailyPollSession::query()->create([
                        'poll_channel_id' => $channel->id,
                        'poll_date' => $today,
                        'rocket_room_id' => $channel->rocket_room_id,
                        'morning_message_id' => $mid,
                        'fact_id' => $fact->id,
                    ]);

                    FactUsage::query()->create([
                        'fact_id' => $fact->id,
                        'usage_year' => $year,
                        'used_at' => now(),
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('Morning poll failed', [
                    'channel_id' => $channel->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function runMorningReminders(string $timezone): void
    {
        $today = $this->today($timezone)->toDateString();
        $botId = $this->rocket->getBotRocketUserId();

        foreach (PollChannel::query()->pollActive()->get() as $channel) {
            try {
                $session = DailyPollSession::query()
                    ->where('poll_channel_id', $channel->id)
                    ->where('poll_date', $today)
                    ->where('morning_reminder_sent', false)
                    ->first();

                if (! $session) {
                    continue;
                }

                $expectedMap = $this->expectedRespondersMapForReminders($channel, $today);
                if ($expectedMap === []) {
                    Log::info('Утреннее напоминание пропущено: нет ожидаемых участников (пустые «Теги команд», нет состава, или все исключены исключениями/периодами отсутствий).', [
                        'channel_id' => $channel->id,
                    ]);
                    $session->update(['morning_reminder_sent' => true]);

                    continue;
                }

                $tmid = $session->morning_message_id;
                $thread = $this->rocket->getThreadMessages($tmid, 100);
                $responded = $this->collectRespondedUsernames($thread, $tmid, $botId);

                $absentDisplay = [];
                foreach ($expectedMap as $lower => $display) {
                    if (! in_array($lower, $responded, true)) {
                        $absentDisplay[] = $display;
                    }
                }

                if ($absentDisplay === []) {
                    $session->update(['morning_reminder_sent' => true]);

                    continue;
                }

                $mentionsPayload = [];
                $mentionParts = [];
                foreach ($absentDisplay as $display) {
                    $rcUser = $this->rocket->getUserByUsername($display);
                    if ($rcUser !== null) {
                        $row = [
                            '_id' => $rcUser['_id'],
                            'username' => $rcUser['username'],
                        ];
                        if (($rcUser['name'] ?? '') !== '') {
                            $row['name'] = $rcUser['name'];
                        }
                        $mentionsPayload[] = $row;
                        $mentionParts[] = '@'.$rcUser['username'];
                    } else {
                        $mentionParts[] = '@'.ltrim(trim($display), '@');
                    }
                }

                if ($mentionsPayload === []) {
                    Log::warning('Morning reminder: users.info did not resolve Rocket.Chat ids; mentions may not notify', [
                        'channel_id' => $channel->id,
                        'usernames' => $absentDisplay,
                    ]);
                }

                // Как в клиенте RC (ForwardMessageModal + prependReplies): невидимая ссылка на msg=…
                // даёт блок «переслано», а не markdown-цитату (> …) и не attachment-preview.
                $permalink = $this->rocket->buildRoomMessagePermalink(
                    $channel->room_type,
                    $channel->name,
                    $tmid,
                );

                $mentionLine = implode(' ', $mentionParts);
                $reminder = '[ ]('.$permalink.")\n".$mentionLine;

                $this->rocket->sendMessage(
                    $channel->rocket_room_id,
                    $reminder,
                    $tmid,
                    null,
                    $mentionsPayload !== [] ? $mentionsPayload : null,
                );

                $session->update(['morning_reminder_sent' => true]);
            } catch (\Throwable $e) {
                Log::error('Morning reminder failed', [
                    'channel_id' => $channel->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Ожидаемые ответы в треде по полю канала «Теги команд»: Rocket.Chat Teams (teams.members),
     * при пустом ответе API токен трактуется как логин пользователя.
     *
     * @return array<string, string> lowercase username => логин Rocket.Chat
     */
    private function expectedRespondersMapForChannel(PollChannel $channel): array
    {
        $raw = trim((string) ($channel->team_tags ?? ''));
        if ($raw === '') {
            return [];
        }

        $tokens = preg_split('/\s*,\s*/', $raw) ?: [];
        $map = [];
        $resolvedByTag = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            $withAt = str_starts_with($token, '@') ? $token : '@'.$token;
            $tagKey = Str::lower(ltrim($token, '@'));
            if ($tagKey === '') {
                continue;
            }

            if (! array_key_exists($tagKey, $resolvedByTag)) {
                $fromRc = $this->rocket->getTeamMembersUsernames($withAt);
                if ($fromRc === []) {
                    $resolvedByTag[$tagKey] = [ltrim($token, '@')];
                } else {
                    $resolvedByTag[$tagKey] = $fromRc;
                }
            }

            foreach ($resolvedByTag[$tagKey] as $username) {
                $display = ltrim(trim((string) $username), '@');
                $k = Str::lower($display);
                if ($k === '') {
                    continue;
                }
                if (! array_key_exists($k, $map)) {
                    $map[$k] = $display;
                }
            }
        }

        return $map;
    }

    /**
     * Состав для напоминаний: команды минус постоянные исключения канала и минус глобальные периоды отсутствий на дату.
     *
     * @return array<string, string> lowercase username => логин Rocket.Chat
     */
    private function expectedRespondersMapForReminders(PollChannel $channel, string $today): array
    {
        $map = $this->expectedRespondersMapForChannel($channel);

        foreach ($this->parseReminderExcludeUsernames($channel->reminder_exclude_tags) as $ex) {
            unset($map[$ex]);
        }

        foreach (ReminderAbsencePeriod::usernamesAbsentOnDate($today) as $ex) {
            unset($map[$ex]);
        }

        return $map;
    }

    /**
     * Логины из поля «не тегать» (через запятую, как @user1, @user2).
     *
     * @return list<string> lowercase
     */
    private function parseReminderExcludeUsernames(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $tokens = preg_split('/\s*,\s*/', $raw) ?: [];
        $seen = [];
        foreach ($tokens as $token) {
            $k = ReminderAbsencePeriod::normalizeUsername((string) $token);
            if ($k !== '') {
                $seen[$k] = true;
            }
        }

        return array_keys($seen);
    }

    /** Строка тегов для дневного опроса (как в поле канала, через пробел). */
    private function channelTeamTagsLine(PollChannel $channel): string
    {
        $raw = trim((string) ($channel->team_tags ?? ''));
        if ($raw === '') {
            return '';
        }

        $tokens = preg_split('/\s*,\s*/', $raw) ?: [];
        $parts = [];
        foreach ($tokens as $token) {
            $t = trim($token);
            if ($t !== '') {
                $parts[] = str_starts_with($t, '@') ? $t : '@'.$t;
            }
        }

        return implode(' ', $parts);
    }

    public function runDayPolls(string $timezone): void
    {
        $today = $this->today($timezone)->toDateString();
        $dayLine = (string) config('bot.poll.day_text', '@all Что в работе?');

        foreach (PollChannel::query()->pollActive()->get() as $channel) {
            try {
                $session = DailyPollSession::query()
                    ->where('poll_channel_id', $channel->id)
                    ->where('poll_date', $today)
                    ->where('day_poll_sent', false)
                    ->first();

                if (! $session) {
                    continue;
                }

                $tmid = $session->morning_message_id;
                $tagPart = '@all'; // $this->channelTeamTagsLine($channel);
                $text = trim($tagPart.' '.$dayLine);

                $msg = $this->rocket->sendMessage($channel->rocket_room_id, $text, $tmid);
                $mid = (string) ($msg['_id'] ?? '');
                if ($mid === '') {
                    throw new RocketChatException('Day poll: missing message id in API response.');
                }

                $session->update([
                    'day_poll_sent' => true,
                    'day_message_id' => $mid,
                ]);
            } catch (\Throwable $e) {
                Log::error('Day poll failed', [
                    'channel_id' => $channel->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function runDayReminders(string $timezone): void
    {
        $today = $this->today($timezone)->toDateString();
        $botId = $this->rocket->getBotRocketUserId();

        foreach (PollChannel::query()->pollActive()->get() as $channel) {
            try {
                $session = DailyPollSession::query()
                    ->where('poll_channel_id', $channel->id)
                    ->where('poll_date', $today)
                    ->where('day_poll_sent', true)
                    ->whereNotNull('day_message_id')
                    ->where('day_reminder_sent', false)
                    ->first();

                if (! $session) {
                    continue;
                }

                $expectedMap = $this->expectedRespondersMapForReminders($channel, $today);
                if ($expectedMap === []) {
                    Log::info('Дневное напоминание пропущено: нет ожидаемых участников (пустые «Теги команд», нет состава, или все исключены исключениями/периодами отсутствий).', [
                        'channel_id' => $channel->id,
                    ]);
                    $session->update(['day_reminder_sent' => true]);

                    continue;
                }

                $thread = $this->rocket->getThreadMessages($session->morning_message_id, 100);
                $responded = $this->collectRespondedUsernamesAfterMessage(
                    $thread,
                    (string) $session->day_message_id,
                    $botId,
                );

                $absentDisplay = [];
                foreach ($expectedMap as $lower => $display) {
                    if (! in_array($lower, $responded, true)) {
                        $absentDisplay[] = $display;
                    }
                }

                if ($absentDisplay === []) {
                    $session->update(['day_reminder_sent' => true]);

                    continue;
                }

                $mentionsPayload = [];
                $mentionParts = [];
                foreach ($absentDisplay as $display) {
                    $rcUser = $this->rocket->getUserByUsername($display);
                    if ($rcUser !== null) {
                        $row = [
                            '_id' => $rcUser['_id'],
                            'username' => $rcUser['username'],
                        ];
                        if (($rcUser['name'] ?? '') !== '') {
                            $row['name'] = $rcUser['name'];
                        }
                        $mentionsPayload[] = $row;
                        $mentionParts[] = '@'.$rcUser['username'];
                    } else {
                        $mentionParts[] = '@'.ltrim(trim($display), '@');
                    }
                }

                if ($mentionsPayload === []) {
                    Log::warning('Day reminder: users.info did not resolve Rocket.Chat ids; mentions may not notify', [
                        'channel_id' => $channel->id,
                        'usernames' => $absentDisplay,
                    ]);
                }

                $permalink = $this->rocket->buildRoomMessagePermalink(
                    $channel->room_type,
                    $channel->name,
                    (string) $session->day_message_id,
                );

                $mentionLine = implode(' ', $mentionParts);
                $reminder = '[ ]('.$permalink.")\n".$mentionLine;

                $this->rocket->sendMessage(
                    $channel->rocket_room_id,
                    $reminder,
                    $session->morning_message_id,
                    null,
                    $mentionsPayload !== [] ? $mentionsPayload : null,
                );

                $session->update(['day_reminder_sent' => true]);
            } catch (\Throwable $e) {
                Log::error('Day reminder failed', [
                    'channel_id' => $channel->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function exportActiveChannelMessages(): string
    {
        $dir = config('bot.poll.export_path');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stamp = now()->format('Y-m-d_His');
        $target = $dir.DIRECTORY_SEPARATOR.$stamp;
        mkdir($target, 0755, true);

        $channels = PollChannel::query()->pollActive()->get();
        $meta = [];

        foreach ($channels as $ch) {
            try {
                $messages = $this->rocket->getAllMessagesInRoom($ch->rocket_room_id, $ch->room_type);
                $meta[] = [
                    'id' => $ch->id,
                    'rocket_room_id' => $ch->rocket_room_id,
                    'name' => $ch->name,
                    'room_type' => $ch->room_type,
                    'message_count' => count($messages),
                ];
                file_put_contents(
                    $target.DIRECTORY_SEPARATOR.$ch->rocket_room_id.'_messages.json',
                    json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                );
            } catch (\Throwable $e) {
                Log::error('Export failed for channel', [
                    'channel' => $ch->id,
                    'error' => $e->getMessage(),
                ]);
                $meta[] = [
                    'id' => $ch->id,
                    'rocket_room_id' => $ch->rocket_room_id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        file_put_contents(
            $target.DIRECTORY_SEPARATOR.'channels.json',
            json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );

        foreach ($channels as $ch) {
            $ch->forceFill(['last_exported_at' => now()])->save();
        }

        return $target;
    }

    /**
     * @param  list<array<string, mixed>>  $threadMessages
     * @return list<string> lowercase usernames
     */
    private function collectRespondedUsernames(array $threadMessages, string $tmid, ?string $botId): array
    {
        $seen = [];
        foreach ($threadMessages as $m) {
            if (! is_array($m)) {
                continue;
            }
            $id = (string) ($m['_id'] ?? '');
            if ($id === '' || $id === $tmid) {
                continue;
            }
            if (isset($m['t']) && is_string($m['t']) && $m['t'] !== '') {
                continue;
            }
            $uid = (string) ($m['u']['_id'] ?? '');
            if ($botId !== null && $uid === $botId) {
                continue;
            }
            $uname = Str::lower(trim((string) ($m['u']['username'] ?? '')));
            if ($uname === '') {
                continue;
            }
            $msgText = trim((string) ($m['msg'] ?? ''));
            $hasAttachments = ! empty($m['attachments']) && is_array($m['attachments']) && count($m['attachments']) > 0;
            $hasFiles = ! empty($m['files']) && is_array($m['files']) && count($m['files']) > 0;
            if ($msgText === '' && ! $hasAttachments && ! $hasFiles) {
                continue;
            }
            $seen[$uname] = true;
        }

        return array_keys($seen);
    }

    /**
     * @param  list<array<string, mixed>>  $threadMessages
     * @return list<string> lowercase usernames
     */
    private function collectRespondedUsernamesAfterMessage(array $threadMessages, string $afterMessageId, ?string $botId): array
    {
        $afterTs = null;
        foreach ($threadMessages as $m) {
            if (! is_array($m)) {
                continue;
            }
            if ((string) ($m['_id'] ?? '') !== $afterMessageId) {
                continue;
            }
            $afterTs = $this->messageTimestamp($m);
            break;
        }

        if ($afterTs === null) {
            return [];
        }

        $seen = [];
        foreach ($threadMessages as $m) {
            if (! is_array($m)) {
                continue;
            }
            $id = (string) ($m['_id'] ?? '');
            if ($id === '' || $id === $afterMessageId) {
                continue;
            }
            if (isset($m['t']) && is_string($m['t']) && $m['t'] !== '') {
                continue;
            }
            $uid = (string) ($m['u']['_id'] ?? '');
            if ($botId !== null && $uid === $botId) {
                continue;
            }
            $ts = $this->messageTimestamp($m);
            if ($ts === null || $ts <= $afterTs) {
                continue;
            }
            $uname = Str::lower(trim((string) ($m['u']['username'] ?? '')));
            if ($uname === '') {
                continue;
            }
            $msgText = trim((string) ($m['msg'] ?? ''));
            $hasAttachments = ! empty($m['attachments']) && is_array($m['attachments']) && count($m['attachments']) > 0;
            $hasFiles = ! empty($m['files']) && is_array($m['files']) && count($m['files']) > 0;
            if ($msgText === '' && ! $hasAttachments && ! $hasFiles) {
                continue;
            }
            $seen[$uname] = true;
        }

        return array_keys($seen);
    }

    private function messageTimestamp(array $message): ?int
    {
        $ts = $message['ts'] ?? null;

        if (is_array($ts) && array_key_exists('$date', $ts)) {
            $ts = $ts['$date'];
        }

        if ($ts instanceof \DateTimeInterface) {
            return $ts->getTimestamp();
        }

        if (is_numeric($ts)) {
            return (int) $ts;
        }

        if (! is_string($ts) || trim($ts) === '') {
            return null;
        }

        try {
            return Carbon::parse($ts)->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }
}
