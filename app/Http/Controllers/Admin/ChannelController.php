<?php

namespace App\Http\Controllers\Admin;

use App\Components\RocketChat\RocketChatClient;
use App\Components\RocketChat\RocketChatException;
use App\Http\Controllers\Controller;
use App\Models\PollChannel;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function index()
    {
        if (! filled(config('bot.rocketchat.url'))) {
            return view('admin.channels', [
                'channels' => PollChannel::query()->orderByDesc('is_poll_active')->orderBy('name')->get(),
                'syncError' => 'Задайте ROCKETCHAT_URL в .env.',
            ]);
        }

        $rocket = app(RocketChatClient::class);

        try {
            $remote = $rocket->listAllChatRooms();
        } catch (RocketChatException $e) {
            return view('admin.channels', [
                'channels' => PollChannel::query()->orderByDesc('is_poll_active')->orderBy('name')->get(),
                'syncError' => $e->getMessage(),
            ]);
        }

        foreach ($remote as $r) {
            PollChannel::query()->updateOrCreate(
                ['rocket_room_id' => $r['id']],
                ['name' => $r['name'], 'room_type' => $r['type']],
            );
        }

        return view('admin.channels', [
            'channels' => PollChannel::query()->orderByDesc('is_poll_active')->orderBy('name')->get(),
            'syncError' => null,
        ]);
    }

    public function update(Request $request)
    {
        $ids = $request->input('active', []);
        if (! is_array($ids)) {
            $ids = [];
        }
        $ids = array_map('intval', $ids);

        $dayIds = $request->input('day_active', []);
        if (! is_array($dayIds)) {
            $dayIds = [];
        }
        $dayIds = array_map('intval', $dayIds);

        PollChannel::query()->update(['is_poll_active' => false]);
        if ($ids !== []) {
            PollChannel::query()->whereIn('id', $ids)->update(['is_poll_active' => true]);
        }

        PollChannel::query()->update(['is_day_poll_active' => false]);
        if ($dayIds !== []) {
            PollChannel::query()->whereIn('id', $dayIds)->update(['is_day_poll_active' => true]);
        }

        $teamTags = $request->input('team_tags', []);
        if (is_array($teamTags)) {
            foreach ($teamTags as $channelId => $value) {
                PollChannel::query()->where('id', (int) $channelId)->update([
                    'team_tags' => trim((string) $value),
                ]);
            }
        }

        $reminderExcludes = $request->input('reminder_exclude_tags', []);
        if (is_array($reminderExcludes)) {
            foreach ($reminderExcludes as $channelId => $value) {
                PollChannel::query()->where('id', (int) $channelId)->update([
                    'reminder_exclude_tags' => trim((string) $value),
                ]);
            }
        }

        $this->savePollDays($request);

        return redirect()->route('admin.channels')->with('status', 'Сохранено.');
    }

    /**
     * Дни отправки по каналам. Пустой multi-select не отправляется браузером,
     * поэтому каждая строка формы несёт скрытый маркер poll_days_present[].
     */
    private function savePollDays(Request $request): void
    {
        $present = $request->input('poll_days_present', []);
        if (! is_array($present)) {
            return;
        }

        $allDays = $request->input('poll_days', []);
        if (! is_array($allDays)) {
            $allDays = [];
        }

        foreach ($present as $channelId) {
            $channelId = (int) $channelId;
            $raw = $allDays[$channelId] ?? [];
            $raw = is_array($raw) ? $raw : [$raw];

            $days = array_values(array_unique(array_filter(
                array_map('intval', $raw),
                fn ($d): bool => $d >= 1 && $d <= 7,
            )));
            sort($days);

            PollChannel::query()->where('id', $channelId)->update([
                'poll_days' => $days !== [] ? $days : null,
            ]);
        }
    }
}
