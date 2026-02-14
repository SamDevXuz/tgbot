<?php
/**
 * Bot Logic Handler
 * Author: SamDevX
 */

namespace App\Bot;

use App\Models\User;
use App\Models\UserState;
use App\Models\Anime;
use App\Models\AnimeEpisode;
use App\Models\Setting;
use App\Models\Channel;
use App\Models\Short;
use App\Services\TelegramService;

class BotHandler
{
    protected $update;
    protected $chat_id;
    protected $user_id;
    protected $message_id;
    protected $text;
    protected $data;
    protected $user;
    protected $admin_ids = [];

    public function __construct($update)
    {
        $this->update = $update;
        $this->parseUpdate();

        $adminList = Setting::get('admin_list');
        if (!$adminList && isset($_ENV['ADMIN_ID'])) {
            $adminList = $_ENV['ADMIN_ID'];
        }
        $this->admin_ids = explode("\n", $adminList);

        if ($this->user_id) {
            $firstName = $this->update['message']['from']['first_name'] ?? ($this->update['callback_query']['from']['first_name'] ?? 'User');
            $username = $this->update['message']['from']['username'] ?? ($this->update['callback_query']['from']['username'] ?? null);

            $this->user = User::firstOrCreate(
                ['telegram_id' => $this->user_id],
                [
                    'name' => $firstName,
                    'username' => $username,
                    'referrals_count' => 0,
                    'balance' => 0
                ]
            );

            // Update user info if changed
            if ($this->user->name !== $firstName || $this->user->username !== $username) {
                $this->user->update(['name' => $firstName, 'username' => $username]);
            }

            // Referral Logic
            if ($this->text && str_starts_with($this->text, '/start ')) {
                $refId = str_replace('/start ', '', $this->text);
                if (is_numeric($refId) && $refId != $this->user_id && !$this->user->referrer_id) {
                    $referrer = User::where('telegram_id', $refId)->first();
                    if ($referrer) {
                        $this->user->referrer_id = $refId;
                        $this->user->save();
                        $referrer->increment('referrals_count');
                        // Optional: Add bonus balance
                    }
                }
            }
        }
    }

    protected function parseUpdate()
    {
        if (isset($this->update['message'])) {
            $this->chat_id = $this->update['message']['chat']['id'];
            $this->user_id = $this->update['message']['from']['id'];
            $this->message_id = $this->update['message']['message_id'];
            $this->text = $this->update['message']['text'] ?? '';
        } elseif (isset($this->update['callback_query'])) {
            $this->chat_id = $this->update['callback_query']['message']['chat']['id'];
            $this->user_id = $this->update['callback_query']['from']['id'];
            $this->message_id = $this->update['callback_query']['message']['message_id'];
            $this->data = $this->update['callback_query']['data'];
            $this->text = '';
        } elseif (isset($this->update['chat_join_request'])) {
            $this->handleJoinRequest();
            exit;
        }
    }

    public function handle()
    {
        if (!$this->chat_id) return;

        if ($this->user && $this->user->ban_status === 'ban') {
            return;
        }

        if (!$this->checkSubscription()) {
            return;
        }

        $state = UserState::where('telegram_id', $this->user_id)->first();
        if ($state && $state->step && !$this->data) {
            $this->handleState($state);
            return;
        }

        if ($this->data) {
            $this->handleCallback();
        } else {
            $this->handleMessage();
        }
    }

    protected function checkSubscription()
    {
        if ($this->user && $this->user->status === 'VIP') return true;
        if (in_array($this->user_id, $this->admin_ids)) return true;

        $channels = Channel::all();
        $notSubscribed = [];

        foreach ($channels as $channel) {
            if ($channel->type === 'request') {
                // Simplified check: assume subscribed if it's a request channel
                continue;
            }

            // API call to check member status
            $res = TelegramService::getChatMember($channel->channel_id, $this->user_id);
            if (!isset($res['result']['status']) || in_array($res['result']['status'], ['left', 'kicked'])) {
                $notSubscribed[] = $channel;
            }
        }

        if (count($notSubscribed) > 0) {
            $buttons = [];
            foreach ($notSubscribed as $ch) {
                $buttons[] = [['text' => "Obuna bo'lish", 'url' => $ch->link]];
            }

            $botUsername = Setting::get('bot_username', 'bot');
            $buttons[] = [['text' => "✅ Tekshirish", 'url' => "https://t.me/$botUsername?start=check"]];

            TelegramService::sendMessage(
                $this->chat_id,
                "<b>Botdan foydalanish uchun quyidagi kanallarga obuna bo'ling:</b>",
                json_encode(['inline_keyboard' => array_chunk($buttons, 1)])
            );
            return false;
        }

        return true;
    }

    protected function handleJoinRequest()
    {
        $chat_id = $this->update['chat_join_request']['chat']['id'];
        $user_id = $this->update['chat_join_request']['from']['id'];

        TelegramService::request('approveChatJoinRequest', [
            'chat_id' => $chat_id,
            'user_id' => $user_id
        ]);

        TelegramService::sendMessage($user_id, "<b>Obuna tasdiqlandi! Botdan foydalanishingiz mumkin.</b>");
    }

    protected function handleMessage()
    {
        if ($this->text === '/start' || $this->text === '◀️ Orqaga') {
            $this->sendMainMenu();
            return;
        }

        if (str_starts_with($this->text, '/start ')) {
            $param = str_replace('/start ', '', $this->text);
            if (is_numeric($param)) {
                $this->showAnime($param);
                return;
            } elseif (str_contains($param, '=')) {
                 $parts = explode('=', $param);
                 $this->showEpisode($parts[0], $parts[1]);
                 return;
            }
        }

        if ($this->text === '📊 Statistika' && $this->isAdmin()) {
            $this->showStats();
            return;
        }

        if ($this->text === '🗄 Boshqarish' && $this->isAdmin()) {
             $this->showAdminPanel();
             return;
        }

        // Search by name default fallback if user sends text
        if (strlen($this->text) > 2) {
             $results = Anime::where('name', 'LIKE', "%{$this->text}%")->limit(5)->get();
             if ($results->isNotEmpty()) {
                 $buttons = [];
                 foreach ($results as $anime) {
                     $buttons[] = [['text' => $anime->name, 'callback_data' => "loadAnime={$anime->id}"]];
                 }
                 TelegramService::sendMessage($this->chat_id, "<b>Qidiruv natijalari:</b>", json_encode(['inline_keyboard' => array_chunk($buttons, 1)]));
                 return;
             }
        }
    }

    protected function handleCallback()
    {
        $data = $this->data;

        if ($data === 'back') {
            TelegramService::deleteMessage($this->chat_id, $this->message_id);
            $this->sendMainMenu();
        } elseif ($data === 'shorts') {
            $this->showRandomShort();
        } elseif ($data === 'searchByName') {
            $this->setState('search_name');
            TelegramService::editMessageText($this->chat_id, $this->message_id, "<b>Anime nomini kiriting:</b>", json_encode(['inline_keyboard' => [[['text' => 'Ortga', 'callback_data' => 'back']]]]));
        } elseif ($data === 'searchByCode') {
            $this->setState('search_code');
            TelegramService::editMessageText($this->chat_id, $this->message_id, "<b>Anime kodini kiriting:</b>", json_encode(['inline_keyboard' => [[['text' => 'Ortga', 'callback_data' => 'back']]]]));
        } elseif (str_starts_with($data, 'loadAnime=')) {
            $id = explode('=', $data)[1];
            TelegramService::deleteMessage($this->chat_id, $this->message_id);
            $this->showAnime($id);
        } elseif (str_starts_with($data, 'yuklanolish=')) {
             $parts = explode('=', $data);
             $this->showEpisode($parts[1], $parts[2] ?? 1);
        } elseif ($data === 'boshqarish' && $this->isAdmin()) {
            $this->showAdminPanel(true);
        } elseif ($data === 'statistika_data') {
             $this->showStats();
        } elseif ($data === 'delete') {
            TelegramService::deleteMessage($this->chat_id, $this->message_id);
        }
    }

    protected function showAdminPanel($edit = false)
    {
        $keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => "📊 Statistika", 'callback_data' => "statistika_data"]],
                [['text' => "📢 Kanallar", 'callback_data' => "kanallar"], ['text' => "🎥 Animelar", 'callback_data' => "animelar"]],
                [['text' => "◀️ Orqaga", 'callback_data' => "back"]]
            ]
        ]);

        $text = "<b>Admin Panel:</b>";

        if ($edit) {
            TelegramService::editMessageText($this->chat_id, $this->message_id, $text, $keyboard);
        } else {
            TelegramService::sendMessage($this->chat_id, $text, $keyboard);
        }
    }

    protected function showStats()
    {
        $users = User::count();
        $animes = Anime::count();
        $text = "<b>📊 Statistika:</b>\n\n👤 Foydalanuvchilar: $users\n🎬 Animelar: $animes";
        TelegramService::sendMessage($this->chat_id, $text);
    }

    protected function handleState($state)
    {
        if ($this->text === '/start' || $this->text === '◀️ Orqaga') {
             $state->delete();
             $this->sendMainMenu();
             return;
        }

        if ($state->step === 'search_name') {
            $results = Anime::where('name', 'LIKE', "%{$this->text}%")->limit(10)->get();
            if ($results->isEmpty()) {
                TelegramService::sendMessage($this->chat_id, "Hech narsa topilmadi.");
            } else {
                $buttons = [];
                foreach ($results as $anime) {
                    $buttons[] = [['text' => $anime->name, 'callback_data' => "loadAnime={$anime->id}"]];
                }
                TelegramService::sendMessage($this->chat_id, "<b>Natijalar:</b>", json_encode(['inline_keyboard' => array_chunk($buttons, 1)]));
            }
            $state->delete();
        } elseif ($state->step === 'search_code') {
            if (is_numeric($this->text)) {
                $this->showAnime($this->text);
            } else {
                 TelegramService::sendMessage($this->chat_id, "Faqat raqam yuboring.");
            }
            $state->delete();
        }
    }

    protected function setState($step, $data = [])
    {
        UserState::updateOrCreate(
            ['telegram_id' => $this->user_id],
            ['step' => $step, 'data' => $data]
        );
    }

    protected function sendMainMenu($edit = false)
    {
        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => "🔎 Qidiruv", 'callback_data' => 'searchByName'],
                ],
                [
                    ['text' => "💖 Obunalarim", 'callback_data' => 'subscribe'],
                    ['text' => "🆓 Free Play", 'callback_data' => "shorts"],
                ],
                [
                    ['text' => "🌐 Web Animes", 'web_app' => ['url' => Setting::get('web_url', 'https://google.com')]]
                ]
            ]
        ];

        $replyMarkup = json_encode([
            'keyboard' => $this->isAdmin() ? [[['text' => "🗄 Boshqarish"]]] : [[['text' => "◀️ Orqaga"]]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $text = Setting::get('start_text', "<b>Assalomu alaykum!</b> Botimizga xush kelibsiz.");

        if ($edit) {
             TelegramService::editMessageText($this->chat_id, $this->message_id, $text, json_encode($buttons));
        } else {
             TelegramService::sendMessage($this->chat_id, $text, json_encode($buttons));
             // Send Main Menu Reply Keyboard
             if ($this->isAdmin()) {
                TelegramService::sendMessage($this->chat_id, "Menu", $replyMarkup);
             }
        }
    }

    protected function showAnime($id)
    {
        $anime = Anime::find($id);
        if (!$anime) {
             TelegramService::sendMessage($this->chat_id, "Anime topilmadi.");
             return;
        }

        $anime->increment('views');

        $caption = "<b>🎬 Nomi: {$anime->name}</b>\n\n";
        $caption .= "🎥 Qismi: {$anime->episodes_count}\n";
        $caption .= "🌍 Davlati: {$anime->country}\n";
        $caption .= "🇺🇿 Tili: {$anime->language}\n";
        $caption .= "📆 Yili: {$anime->year}\n";
        $caption .= "🎞 Janri: {$anime->genre}\n";
        $caption .= "👀 Ko'rishlar: {$anime->views}\n";

        $keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => "YUKLAB OLISH 📥", 'callback_data' => "yuklanolish={$anime->id}=1"]],
                [['text' => "♥️ {$anime->likes}", 'callback_data' => "like_{$anime->id}"], ['text' => "💔 {$anime->dislikes}", 'callback_data' => "dislike_{$anime->id}"]],
                [['text' => "♥️ Saqlash", 'callback_data' => "save_{$anime->id}"]]
            ]
        ]);

        if (str_starts_with($anime->file_id, 'B')) {
             TelegramService::sendVideo($this->chat_id, $anime->file_id, $caption, $keyboard);
        } else {
             TelegramService::sendPhoto($this->chat_id, $anime->file_id, $caption, $keyboard);
        }
    }

    protected function showEpisode($animeId, $episodeNum)
    {
        $episode = AnimeEpisode::where('anime_id', $animeId)->where('episode_number', $episodeNum)->first();
        if (!$episode) {
             TelegramService::sendMessage($this->chat_id, "Qism topilmadi.");
             return;
        }

        $anime = Anime::find($animeId);
        $caption = "<b>{$anime->name}</b>\n\n{$episode->episode_number}-qism";

        $prev = $episodeNum - 1;
        $next = $episodeNum + 1;

        $buttons = [];
        $nav = [];
        if ($prev > 0) $nav[] = ['text' => "⬅️ Oldingi", 'callback_data' => "yuklanolish={$animeId}={$prev}"];
        if ($next <= $anime->episodes_count) $nav[] = ['text' => "➡️ Keyingi", 'callback_data' => "yuklanolish={$animeId}={$next}"];

        if (!empty($nav)) $buttons[] = $nav;
        $buttons[] = [['text' => "❌ Yopish", 'callback_data' => "delete"]];

        $keyboard = json_encode(['inline_keyboard' => $buttons]);

        TelegramService::sendVideo($this->chat_id, $episode->file_id, $caption, $keyboard);
    }

    protected function showRandomShort()
    {
        $short = Short::inRandomOrder()->first();
        if (!$short) {
             TelegramService::sendMessage($this->chat_id, "Shorts topilmadi.");
             return;
        }

        $caption = "<b>{$short->name}</b>\n\n{$short->time}";
        $keyboard = json_encode(['inline_keyboard' => [[['text' => "Keyingisi ➡️", 'callback_data' => "shorts"]]]]);

        TelegramService::sendVideo($this->chat_id, $short->file_id, $caption, $keyboard);
    }

    protected function isAdmin()
    {
        return in_array($this->user_id, $this->admin_ids);
    }
}
