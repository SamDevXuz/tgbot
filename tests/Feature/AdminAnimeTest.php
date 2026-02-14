<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Bot\BotHandler;
use App\Services\TelegramService;
use App\Models\Anime;
use App\Models\User;
use App\Models\UserState;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;

class AdminAnimeTest extends TestCase
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

    public function test_admin_can_start_add_anime_flow()
    {
        // 1. Send 'add_anime' callback
        $update = [
            'callback_query' => [
                'id' => 'cb_id',
                'from' => ['id' => $this->adminId, 'first_name' => 'Admin'],
                'message' => ['chat' => ['id' => $this->chatId], 'message_id' => 100],
                'data' => 'add_anime'
            ]
        ];

        $handler = new BotHandler($update);
        $handler->handle();

        // Check state
        $state = UserState::where('telegram_id', $this->adminId)->first();
        $this->assertEquals('add_anime_name', $state->step);
    }

    public function test_admin_can_complete_add_anime_flow()
    {
        // Step 1: Start
        $this->sendCallback('add_anime');

        // Step 2: Send Name
        $this->sendMessage('Naruto');
        $this->assertState('add_anime_photo');
        $data = $this->getStateData();
        $this->assertEquals('Naruto', $data['name']);

        // Step 3: Send Photo
        $this->sendPhoto('file_123');
        $this->assertState('add_anime_country');

        // Step 4: Send Country
        $this->sendMessage('Japan');
        $this->assertState('add_anime_lang');

        // Step 5: Send Language
        $this->sendMessage('Uzbek');
        $this->assertState('add_anime_year');

        // Step 6: Send Year
        $this->sendMessage('2002');
        $this->assertState('add_anime_genre');

        // Step 7: Send Genre
        $this->sendMessage('Action');

        // Assert Anime Created
        $this->assertNull(UserState::where('telegram_id', $this->adminId)->first()); // State cleared

        $anime = Anime::where('name', 'Naruto')->first();
        $this->assertNotNull($anime);
        $this->assertEquals('Japan', $anime->country);
        $this->assertEquals('Action', $anime->genre);
        $this->assertEquals('file_123', $anime->file_id);
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

    protected function sendPhoto($fileId)
    {
        $update = [
            'message' => [
                'message_id' => rand(1000, 9999),
                'from' => ['id' => $this->adminId, 'first_name' => 'Admin'],
                'chat' => ['id' => $this->chatId],
                'photo' => [
                    ['file_id' => 'thumb_' . $fileId],
                    ['file_id' => $fileId] // The last one is used
                ]
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
