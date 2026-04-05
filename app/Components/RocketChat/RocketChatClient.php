<?php

namespace App\Components\RocketChat;

use App\Components\RocketChat\Contracts\RocketChatClientContract;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

/**
 * Rocket.Chat REST через Guzzle: логин и все вызовы идут одним клиентом с заголовками X-User-Id / X-Auth-Token.
 * Опционально: ROCKETCHAT_USER_ID + ROCKETCHAT_AUTH_TOKEN (Personal Access Token) вместо пароля.
 */
class RocketChatClient implements RocketChatClientContract
{
    private ?Client $http = null;

    private ?string $userId = null;

    private ?string $authToken = null;

    private ?string $botRocketUserId = null;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly string $personalAccessUserId = '',
        private readonly string $personalAccessToken = '',
    ) {}

    public static function fromConfig(array $botConfig): self
    {
        $rc = $botConfig['rocketchat'] ?? [];
        $url = rtrim((string) ($rc['url'] ?? ''), '/');

        return new self(
            $url,
            (string) ($rc['username'] ?? ''),
            (string) ($rc['password'] ?? ''),
            trim((string) ($rc['auth_user_id'] ?? '')),
            trim((string) ($rc['auth_token'] ?? '')),
        );
    }

    public function getBotRocketUserId(): ?string
    {
        $this->ensureAuthenticated();

        return $this->botRocketUserId;
    }

    public function ensureAuthenticated(): void
    {
        if ($this->baseUrl === '') {
            throw new RocketChatException('ROCKETCHAT_URL is not configured.');
        }

        $patUid = $this->personalAccessUserId;
        $patTok = $this->personalAccessToken;
        if ($patUid !== '' && $patTok !== '') {
            $this->userId = $patUid;
            $this->authToken = $patTok;
            $this->botRocketUserId = $patUid;

            return;
        }

        if ($this->userId !== null && $this->authToken !== null) {
            return;
        }

        $response = $this->client()->post('login', [
            'json' => [
                'user' => $this->username,
                'password' => $this->password,
            ],
        ]);

        $this->applyLoginResponse($response);
    }

    /**
     * @return list<array{id: string, name: string, type: string}>
     */
    public function listAllChannelRooms(): array
    {
        $this->ensureAuthenticated();
        $out = [];
        $offset = 0;
        $page = 100;

        do {
            $data = $this->apiGet('channels.list', ['count' => $page, 'offset' => $offset]);
            $channels = $data['channels'] ?? [];
            if (! is_array($channels)) {
                $channels = [];
            }
            foreach ($channels as $ch) {
                if (! is_array($ch)) {
                    continue;
                }
                $out[] = [
                    'id' => (string) ($ch['_id'] ?? ''),
                    'name' => (string) ($ch['name'] ?? ''),
                    'type' => 'c',
                ];
            }
            $count = count($channels);
            $offset += $count;
        } while ($count >= $page);

        return $out;
    }

    /**
     * @return list<array{id: string, name: string, type: string}>
     */
    public function listAllPrivateGroups(): array
    {
        $this->ensureAuthenticated();
        $out = [];
        $offset = 0;
        $page = 100;

        do {
            $data = $this->apiGet('groups.list', ['count' => $page, 'offset' => $offset]);
            $groups = $data['groups'] ?? [];
            if (! is_array($groups)) {
                $groups = [];
            }
            foreach ($groups as $gr) {
                if (! is_array($gr)) {
                    continue;
                }
                $out[] = [
                    'id' => (string) ($gr['_id'] ?? ''),
                    'name' => (string) ($gr['name'] ?? ''),
                    'type' => 'p',
                ];
            }
            $count = count($groups);
            $offset += $count;
        } while ($count >= $page);

        return $out;
    }

    /**
     * @return list<array{id: string, name: string, type: string}>
     */
    public function listAllDirectRooms(): array
    {
        $this->ensureAuthenticated();
        $out = [];
        $offset = 0;
        $page = 100;

        do {
            $data = $this->apiGet('im.list', ['count' => $page, 'offset' => $offset]);
            $ims = $data['ims'] ?? [];
            if (! is_array($ims)) {
                $ims = [];
            }
            foreach ($ims as $im) {
                if (! is_array($im)) {
                    continue;
                }
                $name = '';
                if (! empty($im['usernames']) && is_array($im['usernames'])) {
                    $name = implode(', ', $im['usernames']);
                }
                $rid = (string) ($im['_id'] ?? '');
                $out[] = [
                    'id' => $rid,
                    'name' => $name !== '' ? $name : $rid,
                    'type' => 'd',
                ];
            }
            $count = count($ims);
            $offset += $count;
        } while ($count >= $page);

        return $out;
    }

    /**
     * @return list<array{id: string, name: string, type: string}>
     */
    public function listAllChatRooms(): array
    {
        return array_merge(
            $this->listAllChannelRooms(),
            $this->listAllPrivateGroups(),
            $this->listAllDirectRooms(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAllMessagesInRoom(string $roomId, string $roomType, int $pageSize = 100): array
    {
        $this->ensureAuthenticated();
        $endpoint = match ($roomType) {
            'c' => 'channels.history',
            'p' => 'groups.history',
            'd' => 'im.history',
            default => throw new RocketChatException('Unknown room type: '.$roomType),
        };

        $messages = [];
        $offset = 0;

        do {
            $data = $this->apiGet($endpoint, [
                'roomId' => $roomId,
                'count' => $pageSize,
                'offset' => $offset,
            ]);
            $batch = $data['messages'] ?? [];
            if (! is_array($batch)) {
                $batch = [];
            }
            foreach ($batch as $m) {
                $messages[] = $m;
            }
            $n = count($batch);
            $offset += $n;
        } while ($n >= $pageSize);

        return $messages;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getThreadMessages(string $tmid, int $pageSize = 100): array
    {
        $this->ensureAuthenticated();
        $messages = [];
        $offset = 0;

        do {
            $data = $this->apiGet('chat.getThreadMessages', [
                'tmid' => $tmid,
                'count' => $pageSize,
                'offset' => $offset,
            ]);
            $batch = $data['messages'] ?? [];
            if (! is_array($batch)) {
                $batch = [];
            }
            foreach ($batch as $m) {
                $messages[] = $m;
            }
            $n = count($batch);
            $offset += $n;
        } while ($n >= $pageSize);

        return $messages;
    }

    /**
     * @return array{_id: string, username: string, name?: string}|null
     */
    public function getUserByUsername(string $username): ?array
    {
        $this->ensureAuthenticated();
        $trimmed = trim($username);
        if ($trimmed === '') {
            return null;
        }

        $candidates = array_unique(array_filter([
            $trimmed,
            Str::lower($trimmed),
        ]));

        foreach ($candidates as $try) {
            $res = $this->client()->get('users.info', [
                'headers' => $this->authHeaders(),
                'query' => ['username' => $try],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            if (! is_array($data) || empty($data['success'])) {
                continue;
            }
            $user = $data['user'] ?? null;
            if (! is_array($user) || ($user['_id'] ?? '') === '' || ($user['username'] ?? '') === '') {
                continue;
            }

            $out = [
                '_id' => (string) $user['_id'],
                'username' => (string) $user['username'],
            ];
            if (! empty($user['name']) && is_string($user['name'])) {
                $out['name'] = $user['name'];
            }

            return $out;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function getTeamMembersUsernames(string $mentionTagOrTeamName): array
    {
        $this->ensureAuthenticated();
        $teamName = ltrim(trim($mentionTagOrTeamName), '@');
        if ($teamName === '') {
            return [];
        }

        $out = [];
        $offset = 0;
        $page = 100;

        do {
            $data = $this->apiGetSoft('teams.members', [
                'teamName' => $teamName,
                'count' => $page,
                'offset' => $offset,
            ]);
            if ($data === null) {
                if ($out === []) {
                    return [];
                }

                break;
            }

            $members = $data['members'] ?? [];
            if (! is_array($members)) {
                $members = [];
            }
            foreach ($members as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $user = $row['user'] ?? null;
                if (! is_array($user)) {
                    continue;
                }
                $un = trim((string) ($user['username'] ?? ''));
                if ($un !== '') {
                    $out[] = $un;
                }
            }

            $count = count($members);
            $offset += $count;
        } while ($count >= $page);

        return array_values(array_unique($out));
    }

    /**
     * @param  list<array<string, mixed>>|null  $attachments
     * @param  list<array<string, string>>|null  $mentions
     * @return array<string, mixed>
     */
    public function sendMessage(
        string $roomId,
        string $text,
        ?string $tmid = null,
        ?array $attachments = null,
        ?array $mentions = null,
    ): array {
        $this->ensureAuthenticated();

        // Rocket.Chat 7.4+ ломает chat.postMessage строгим oneOf (roomId vs channel, лишние поля).
        // chat.sendMessage с вложенным message { rid, msg, tmid, … } — совместимый путь.
        $message = [
            'rid' => $roomId,
            'msg' => $text,
        ];
        if ($tmid !== null && $tmid !== '') {
            $message['tmid'] = $tmid;
        }
        if ($attachments !== null && $attachments !== []) {
            $message['attachments'] = $attachments;
        }
        if ($mentions !== null && $mentions !== []) {
            $cleanMentions = [];
            foreach ($mentions as $m) {
                if (! is_array($m)) {
                    continue;
                }
                $id = (string) ($m['_id'] ?? '');
                $user = (string) ($m['username'] ?? '');
                if ($id === '' || $user === '') {
                    continue;
                }
                $cleanMentions[] = ['_id' => $id, 'username' => $user];
            }
            if ($cleanMentions !== []) {
                $message['mentions'] = $cleanMentions;
            }
        }

        $res = $this->client()->post('chat.sendMessage', [
            'headers' => $this->authHeaders(),
            'json' => ['message' => $message],
        ]);
        $data = $this->decodeApiResponse($res);

        return (array) ($data['message'] ?? []);
    }

    public function buildRoomMessagePermalink(string $roomType, string $roomName, string $messageId): string
    {
        $segment = $roomType === 'p' ? 'group' : 'channel';

        return rtrim($this->baseUrl, '/').'/'.$segment.'/'.rawurlencode($roomName).'?msg='.$messageId;
    }

    /**
     * @return array<string, mixed>
     */
    private function apiGet(string $endpoint, array $query): array
    {
        $res = $this->client()->get($endpoint, [
            'headers' => $this->authHeaders(),
            'query' => $query,
        ]);

        return $this->decodeApiResponse($res);
    }

    /**
     * GET без исключения при ошибке (для опциональных endpoint вроде teams.members).
     *
     * @return array<string, mixed>|null
     */
    private function apiGetSoft(string $endpoint, array $query): ?array
    {
        $res = $this->client()->get($endpoint, [
            'headers' => $this->authHeaders(),
            'query' => $query,
        ]);

        $code = $res->getStatusCode();
        $raw = (string) $res->getBody();
        $data = json_decode($raw, true);

        if ($code !== 200 || ! is_array($data) || empty($data['success'])) {
            return null;
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        $this->ensureAuthenticated();

        return [
            'X-User-Id' => (string) $this->userId,
            'X-Auth-Token' => (string) $this->authToken,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeApiResponse(ResponseInterface $response): array
    {
        $code = $response->getStatusCode();
        $raw = (string) $response->getBody();
        $data = json_decode($raw, true);

        if (! is_array($data)) {
            $snippet = strlen($raw) > 200 ? substr($raw, 0, 200).'…' : $raw;
            if ($code === 401) {
                throw new RocketChatException('Unauthorized'.($snippet !== '' ? ': '.strip_tags($snippet) : ' (HTTP 401). Проверьте логин/пароль или задайте ROCKETCHAT_USER_ID и ROCKETCHAT_AUTH_TOKEN (Personal Access Token).'));
            }

            throw new RocketChatException('Ответ Rocket.Chat не JSON (HTTP '.$code.')'.($snippet !== '' ? ': '.$snippet : ''));
        }

        if ($code === 401) {
            $msg = (string) ($data['error'] ?? $data['message'] ?? 'Unauthorized');

            throw new RocketChatException($msg.' (HTTP 401). Для серверов с отключённым логином по паролю используйте Personal Access Token: ROCKETCHAT_USER_ID + ROCKETCHAT_AUTH_TOKEN в .env).');
        }

        if ($code < 200 || $code >= 300) {
            throw new RocketChatException((string) ($data['error'] ?? $data['message'] ?? 'Rocket.Chat HTTP '.$code));
        }

        if (array_key_exists('success', $data) && $data['success'] === false) {
            throw new RocketChatException((string) ($data['error'] ?? $data['message'] ?? 'API success=false'));
        }

        return $data;
    }

    private function applyLoginResponse(ResponseInterface $response): void
    {
        $code = $response->getStatusCode();
        $payload = $this->decodeJson((string) $response->getBody());

        if ($code === 401) {
            $msg = (string) ($payload['error'] ?? $payload['message'] ?? 'Unauthorized');

            throw new RocketChatException($msg.' (HTTP 401). Убедитесь в ROCKETCHAT_URL, логине и пароле бота; при необходимости создайте Personal Access Token в профиле пользователя и укажите ROCKETCHAT_USER_ID + ROCKETCHAT_AUTH_TOKEN.');
        }

        $status = $payload['status'] ?? null;
        if ($status !== 'success' || empty($payload['data'])) {
            $err = $payload['error'] ?? $payload['message'] ?? 'Rocket.Chat login failed';

            throw new RocketChatException(is_string($err) ? $err : 'Rocket.Chat login failed');
        }

        $data = $payload['data'];
        $this->userId = (string) ($data['userId'] ?? '');
        $this->authToken = (string) ($data['authToken'] ?? '');
        $this->botRocketUserId = isset($data['me']['_id']) ? (string) $data['me']['_id'] : $this->userId;

        if ($this->userId === '' || $this->authToken === '') {
            throw new RocketChatException('Rocket.Chat login response missing credentials.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $raw): array
    {
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    private function client(): Client
    {
        if ($this->http === null) {
            if ($this->baseUrl === '') {
                throw new RocketChatException('ROCKETCHAT_URL is not configured.');
            }
            $this->http = new Client([
                'base_uri' => rtrim($this->baseUrl, '/').'/api/v1/',
                'timeout' => 90,
                'connect_timeout' => 30,
                'http_errors' => false,
            ]);
        }

        return $this->http;
    }
}
