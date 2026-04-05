<?php

namespace App\Components\RocketChat\Contracts;

/**
 * Контракт публичного API клиента Rocket.Chat (реализация — {@see \App\Components\RocketChat\RocketChatClient}).
 */
interface RocketChatClientContract
{
    public function ensureAuthenticated(): void;

    public function getBotRocketUserId(): ?string;

    /**
     * @return list<array{id: string, name: string, type: string}>
     */
    public function listAllChatRooms(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function getAllMessagesInRoom(string $roomId, string $roomType, int $pageSize = 100): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function getThreadMessages(string $tmid, int $pageSize = 100): array;

    /**
     * Пользователь по логину (для массива mentions в chat.postMessage).
     *
     * @return array{_id: string, username: string, name?: string}|null
     */
    public function getUserByUsername(string $username): ?array;

    /**
     * Участники Rocket.Chat Teams по имени команды (как в теге @developers → developers).
     * Нужны права бота (например view-all-teams). Пустой массив — Teams нет, имя неверно или API недоступен.
     *
     * @return list<string> логины без ведущего @
     */
    public function getTeamMembersUsernames(string $mentionTagOrTeamName): array;

    /**
     * @param  list<array<string, mixed>>|null  $attachments
     * @param  list<array{_id: string, username: string, name?: string}>|null  $mentions
     * @return array<string, mixed>
     */
    public function sendMessage(
        string $roomId,
        string $text,
        ?string $tmid = null,
        ?array $attachments = null,
        ?array $mentions = null,
    ): array;

    public function buildRoomMessagePermalink(string $roomType, string $roomName, string $messageId): string;
}
