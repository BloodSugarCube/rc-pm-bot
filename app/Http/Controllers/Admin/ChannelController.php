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
                'channels' => PollChannel::query()->orderBy('name')->get(),
                'syncError' => 'Задайте ROCKETCHAT_URL в .env.',
            ]);
        }

        $rocket = app(RocketChatClient::class);

        try {
            $remote = $rocket->listAllChatRooms();
        } catch (RocketChatException $e) {
            return view('admin.channels', [
                'channels' => PollChannel::query()->orderBy('name')->get(),
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
            'channels' => PollChannel::query()->orderBy('name')->get(),
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

        PollChannel::query()->update(['is_poll_active' => false]);
        if ($ids !== []) {
            PollChannel::query()->whereIn('id', $ids)->update(['is_poll_active' => true]);
        }

        $teamTags = $request->input('team_tags', []);
        if (is_array($teamTags)) {
            foreach ($teamTags as $channelId => $value) {
                PollChannel::query()->where('id', (int) $channelId)->update([
                    'team_tags' => trim((string) $value),
                ]);
            }
        }

        return redirect()->route('admin.channels')->with('status', 'Сохранено.');
    }
}
