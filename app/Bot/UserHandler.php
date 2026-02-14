<?php
/**
 * User Handler
 * Author: SamDevX
 */

namespace App\Bot;

use App\Models\User;
use App\Models\Anime;
use App\Models\AnimeEpisode;
use App\Models\AnimeVote;
use App\Models\SavedAnime;
use App\Models\Short;
use App\Models\Setting;
use App\Models\UserState;
use App\Services\TelegramService;

class UserHandler
{
    protected $chat_id;
    protected $user_id;
    protected $text;
    protected $data;
    protected $message_id;
    protected $callback_query_id;

    public function __construct($chat_id, $user_id, $text, $data, $message_id, $callback_query_id = null)
    {
        $this->chat_id = $chat_id;
        $this->user_id = $user_id;
        $this->text = $text;
        $this->data = $data;
        $this->message_id = $message_id;
        $this->callback_query_id = $callback_query_id;
    }

    public function handle()
    {
        if ($this->data) {
            $this->handleCallback();
        } else {
            $this->handleMessage();
        }
    }

    public function handleState($state)
    {
        $step = $state->step;

        if ($step === 'search_name') {
            $results = Anime::where('name', 'LIKE', "%{$this->text}%")->limit(10)->get();
            if ($results->isEmpty()) {
                TelegramService::sendMessage($this->chat_id, "Hech narsa topilmadi.");
            } else {
                $buttons = [];
                foreach ($results as $anime) {
                    $buttons[] = [['text' => $anime->name, 'callback_data' => "loadAnime={$anime->id}"]];
                }
                TelegramService::sendMessage($this->chat_id, "<b>Natijalar:</b>", json_encode(['inline_keyboard' => $buttons]));
            }
            $state->delete();
        }
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

        // Global search by name if text is sent without state (optional feature, kept from original)
        if (strlen($this->text) > 2) {
             $results = Anime::where('name', 'LIKE', "%{$this->text}%")->limit(5)->get();
             if ($results->isNotEmpty()) {
                 $buttons = [];
                 foreach ($results as $anime) {
                     $buttons[] = [['text' => $anime->name, 'callback_data' => "loadAnime={$anime->id}"]];
                 }
                 TelegramService::sendMessage($this->chat_id, "<b>Qidiruv natijalari:</b>", json_encode(['inline_keyboard' => $buttons]));
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
        } elseif ($data === 'subscribe') {
            TelegramService::answerCallbackQuery($this->callback_query_id, "Tez orada...", true);
        } elseif ($data === 'searchByName') {
            $this->setState('search_name');
            TelegramService::editMessageText($this->chat_id, $this->message_id, "<b>Anime nomini kiriting:</b>", json_encode(['inline_keyboard' => [[['text' => 'Ortga', 'callback_data' => 'back']]]]));
        } elseif (str_starts_with($data, 'loadAnime=')) {
            $id = explode('=', $data)[1];
            TelegramService::deleteMessage($this->chat_id, $this->message_id);
            $this->showAnime($id);
        } elseif (str_starts_with($data, 'yuklanolish=')) {
             $parts = explode('=', $data);
             $this->showEpisode($parts[1], $parts[2] ?? 1);
        } elseif (str_starts_with($data, 'like_')) {
            $this->handleVote(str_replace('like_', '', $data), 'like');
        } elseif (str_starts_with($data, 'dislike_')) {
            $this->handleVote(str_replace('dislike_', '', $data), 'dislike');
        } elseif (str_starts_with($data, 'save_')) {
            $this->handleSave(str_replace('save_', '', $data));
        } elseif ($data === 'delete') {
            TelegramService::deleteMessage($this->chat_id, $this->message_id);
        }
    }

    protected function handleVote($animeId, $type)
    {
        $vote = AnimeVote::where('user_id', $this->user_id)->where('anime_id', $animeId)->first();

        if ($vote) {
            if ($vote->type === $type) {
                $vote->delete();
                TelegramService::answerCallbackQuery($this->callback_query_id, "Ovoz bekor qilindi.");
            } else {
                $vote->update(['type' => $type]);
                TelegramService::answerCallbackQuery($this->callback_query_id, "Ovoz o'zgartirildi.");
            }
        } else {
            AnimeVote::create(['user_id' => $this->user_id, 'anime_id' => $animeId, 'type' => $type]);
            TelegramService::answerCallbackQuery($this->callback_query_id, "Ovoz qabul qilindi.");
        }

        $this->updateAnimeMessage($animeId);
    }

    protected function handleSave($animeId)
    {
        $saved = SavedAnime::where('user_id', $this->user_id)->where('anime_id', $animeId)->first();
        if ($saved) {
            $saved->delete();
            TelegramService::answerCallbackQuery($this->callback_query_id, "Saqlanganlardan olib tashlandi.");
        } else {
            SavedAnime::create(['user_id' => $this->user_id, 'anime_id' => $animeId]);
            TelegramService::answerCallbackQuery($this->callback_query_id, "Saqlandi!");
        }
        $this->updateAnimeMessage($animeId);
    }

    protected function updateAnimeMessage($animeId)
    {
        $anime = Anime::find($animeId);
        if (!$anime) return;

        $likes = AnimeVote::where('anime_id', $animeId)->where('type', 'like')->count();
        $dislikes = AnimeVote::where('anime_id', $animeId)->where('type', 'dislike')->count();

        $isSaved = SavedAnime::where('user_id', $this->user_id)->where('anime_id', $animeId)->exists();
        $saveText = $isSaved ? "✅ Saqlangan" : "♥️ Saqlash";

        $keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => "YUKLAB OLISH 📥", 'callback_data' => "yuklanolish={$anime->id}=1"]],
                [['text' => "♥️ {$likes}", 'callback_data' => "like_{$anime->id}"], ['text' => "💔 {$dislikes}", 'callback_data' => "dislike_{$anime->id}"]],
                [['text' => $saveText, 'callback_data' => "save_{$anime->id}"]]
            ]
        ]);

        TelegramService::editMessageReplyMarkup($this->chat_id, $this->message_id, $keyboard);
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
            'keyboard' => [[['text' => "◀️ Orqaga"]]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $text = Setting::get('start_text', "<b>Assalomu alaykum!</b> Botimizga xush kelibsiz.");

        if ($edit) {
             TelegramService::editMessageText($this->chat_id, $this->message_id, $text, json_encode($buttons));
        } else {
             TelegramService::sendMessage($this->chat_id, $text, json_encode($buttons));
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

        $likes = AnimeVote::where('anime_id', $id)->where('type', 'like')->count();
        $dislikes = AnimeVote::where('anime_id', $id)->where('type', 'dislike')->count();

        $isSaved = SavedAnime::where('user_id', $this->user_id)->where('anime_id', $id)->exists();
        $saveText = $isSaved ? "✅ Saqlangan" : "♥️ Saqlash";

        $keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => "YUKLAB OLISH 📥", 'callback_data' => "yuklanolish={$anime->id}=1"]],
                [['text' => "♥️ {$likes}", 'callback_data' => "like_{$anime->id}"], ['text' => "💔 {$dislikes}", 'callback_data' => "dislike_{$anime->id}"]],
                [['text' => $saveText, 'callback_data' => "save_{$anime->id}"]]
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

    protected function setState($step, $data = [])
    {
        UserState::updateOrCreate(
            ['telegram_id' => $this->user_id],
            ['step' => $step, 'data' => json_encode($data)]
        );
    }
}
