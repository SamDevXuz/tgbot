<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Bot\BotHandler;
use App\Services\TelegramService;
use App\Models\Anime;
use App\Models\User;
use App\Models\AnimeVote;
use App\Models\SavedAnime;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;

class UserAnimeTest extends TestCase
{
    protected $userId = 54321;
    protected $chatId = 54321;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Telegram Client
        $mock = Mockery::mock(Client::class);
        $mock->shouldReceive('post')->andReturn(new Response(200, [], json_encode(['ok' => true])));
        TelegramService::setClient($mock);

        // Create User
        User::create([
            'telegram_id' => $this->userId,
            'name' => 'User',
            'username' => 'user',
            'status' => 'Oddiy'
        ]);

        // Create Sample Anime
        Anime::create([
            'name' => 'One Piece',
            'file_id' => 'file_op',
            'episodes_count' => 1000,
            'country' => 'Japan',
            'language' => 'Japanese',
            'year' => '1999',
            'genre' => 'Adventure',
            'views' => 0,
            'likes' => 0,
            'dislikes' => 0
        ]);
    }

    public function test_user_can_search_anime_by_name()
    {
        // Simulate sending text "One Piece"
        $this->sendMessage('One Piece');

        // Unfortunately, without mocking sendMessage to capture arguments, I can't easily assert the response text directly here.
        // But I can check if no exception occurred.
        // To do better assertions, I should spy on the TelegramService or the Mock Client.
        // But let's rely on the fact that if logic is broken, it might throw error or not reach the query.

        // Wait, I can verify side effects. But search doesn't have side effects on DB (except maybe logs).

        // Let's improve the test by checking if BotHandler logic works.
        // We can check if `Anime::where` was called? No, it's integration.

        // I will trust the code coverage for now. If I had a spy on TelegramService::sendMessage, I could check arguments.
        // Let's try to mock the specific call to capture arguments.

        $mock = Mockery::mock(Client::class);
        $mock->shouldReceive('post')->with(
            Mockery::pattern('/sendMessage/'),
            Mockery::on(function($args) {
                // Check if text contains "Qidiruv natijalari"
                $body = $args['json'];
                return str_contains($body['text'], 'Qidiruv natijalari') && str_contains($body['reply_markup'], 'One Piece');
            })
        )->once()->andReturn(new Response(200, [], json_encode(['ok' => true])));

        // Also allow other calls (like getChatMember or update user)
        $mock->shouldReceive('post')->andReturn(new Response(200, [], json_encode(['ok' => true])));

        TelegramService::setClient($mock);

        $this->sendMessage('One Piece');
    }

    public function test_user_can_view_anime_details()
    {
        $anime = Anime::first();

        $mock = Mockery::mock(Client::class);
        $mock->shouldReceive('post')->with(
            Mockery::pattern('/sendPhoto/'),
            Mockery::on(function($args) use ($anime) {
                $body = $args['json'];
                return $body['photo'] === $anime->file_id && str_contains($body['caption'], $anime->name);
            })
        )->once()->andReturn(new Response(200, [], json_encode(['ok' => true])));

        $mock->shouldReceive('post')->andReturn(new Response(200, [], json_encode(['ok' => true])));
        TelegramService::setClient($mock);

        $this->sendCallback("loadAnime={$anime->id}");

        // Verify view count incremented
        $this->assertEquals(1, $anime->fresh()->views);
    }

    public function test_user_can_vote_like()
    {
        $anime = Anime::first();

        $this->sendCallback("like_{$anime->id}");

        $vote = AnimeVote::where('user_id', $this->userId)->where('anime_id', $anime->id)->first();
        $this->assertNotNull($vote);
        $this->assertEquals('like', $vote->type);

        // Toggle (remove like)
        $this->sendCallback("like_{$anime->id}");
        $this->assertNull(AnimeVote::where('user_id', $this->userId)->where('anime_id', $anime->id)->first());
    }

    public function test_user_can_save_anime()
    {
        $anime = Anime::first();

        $this->sendCallback("save_{$anime->id}");

        $saved = SavedAnime::where('user_id', $this->userId)->where('anime_id', $anime->id)->first();
        $this->assertNotNull($saved);

        // Toggle (remove save)
        $this->sendCallback("save_{$anime->id}");
        $this->assertNull(SavedAnime::where('user_id', $this->userId)->where('anime_id', $anime->id)->first());
    }

    protected function sendCallback($data)
    {
        $update = [
            'callback_query' => [
                'id' => 'cb_' . rand(1000, 9999),
                'from' => ['id' => $this->userId, 'first_name' => 'User'],
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
                'from' => ['id' => $this->userId, 'first_name' => 'User'],
                'chat' => ['id' => $this->chatId],
                'text' => $text
            ]
        ];
        (new BotHandler($update))->handle();
    }
}
