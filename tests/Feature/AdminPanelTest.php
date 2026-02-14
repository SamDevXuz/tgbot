<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Bot\BotHandler;
use App\Services\TelegramService;
use App\Models\Channel;
use App\Models\Anime;
use App\Models\User;
use App\Models\UserState;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;

class AdminPanelTest extends TestCase
{
    protected $adminId = 12345;
    protected $chatId = 12345;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Telegram Client
        $mock = Mockery::mock(Client::class);
        $mock->shouldReceive('post')->andReturn(new Response(200, [], json_encode(['ok' => true])));
        TelegramService::setClient($mock);

        // Create Admin User
        User::create([
            'telegram_id' => $this->adminId,
            'name' => 'Admin',
            'username' => 'admin',
            'status' => 'VIP'
        ]);
    }

    public function test_admin_can_add_channel()
    {
        // 1. Start 'add_channel'
        $this->sendCallback('add_channel');

        // Assert state is 'add_channel_id'
        $this->assertState('add_channel_id');

        // 2. Send Channel ID
        $this->sendMessage('-100123456789');

        // Assert state is 'add_channel_link'
        $this->assertState('add_channel_link');
        $data = $this->getStateData();
        $this->assertEquals('-100123456789', $data['id']);

        // 3. Send Channel Link
        $this->sendMessage('https://t.me/+AbcD123');

        // Assert Channel Created
        $channel = Channel::where('channel_id', '-100123456789')->first();
        $this->assertNotNull($channel);
        $this->assertEquals('https://t.me/+AbcD123', $channel->link);

        // Assert state cleared
        $this->assertNull(UserState::where('telegram_id', $this->adminId)->first());
    }

    public function test_admin_can_delete_channel()
    {
        // Create Channel
        $channel = Channel::create([
            'channel_id' => '-100111222333',
            'type' => 'private',
            'link' => 'https://t.me/test',
        ]);

        // Send delete callback
        $this->sendCallback('del_channel_' . $channel->id);

        // Assert deleted
        $this->assertNull(Channel::find($channel->id));
    }

    public function test_admin_can_delete_anime()
    {
        // Create Anime
        $anime = Anime::create([
            'name' => 'To Delete',
            'file_id' => 'file_del',
            'episodes_count' => 0
        ]);

        // Send delete callback
        $this->sendCallback('del_anime_' . $anime->id);

        // Assert deleted
        $this->assertNull(Anime::find($anime->id));
    }

    protected function sendCallback($data)
    {
        $update = [
            'callback_query' => [
                'id' => 'cb_' . rand(1000, 9999),
                'from' => ['id' => $this->adminId, 'first_name' => 'Admin'],
                'message' => ['chat' => ['id' => $this->chatId], 'message_id' => rand(100, 999)],
                'data' => $data
            ]
        ];
        (new BotHandler($update))->handle();
    }

    protected function sendMessage($text)
    {
        $update = [
            'message' => [
                'message_id' => rand(1000, 9999),
                'from' => ['id' => $this->adminId, 'first_name' => 'Admin'],
                'chat' => ['id' => $this->chatId],
                'text' => $text
            ]
        ];
        (new BotHandler($update))->handle();
    }

    protected function assertState($step)
    {
        $state = UserState::where('telegram_id', $this->adminId)->first();
        $this->assertNotNull($state, "State should not be null (expected: $step)");
        $this->assertEquals($step, $state->step);
    }

    protected function getStateData()
    {
        $state = UserState::where('telegram_id', $this->adminId)->first();
        return json_decode($state->data, true);
    }
}
