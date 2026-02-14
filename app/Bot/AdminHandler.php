<?php
/**
 * Admin Handler
 * Author: SamDevX
 */

namespace App\Bot;

use App\Models\User;
use App\Models\Anime;
use App\Models\AnimeEpisode;
use App\Models\Channel;
use App\Models\UserState;
use App\Services\TelegramService;

class AdminHandler
{
    protected $chat_id;
    protected $user_id;
    protected $text;
    protected $data;
    protected $message_id;
    protected $update;
    protected $callback_query_id;

    public function __construct($chat_id, $user_id, $text, $data, $message_id, $update = [], $callback_query_id = null)
    {
        $this->chat_id = $chat_id;
        $this->user_id = $user_id;
        $this->text = $text;
        $this->data = $data;
        $this->message_id = $message_id;
        $this->update = $update;
        $this->callback_query_id = $callback_query_id;
    }

    public function handle()
    {
        if ($this->data === 'boshqarish' || $this->data === 'back') { // back to main panel
             $this->showPanel(true);
        } elseif ($this->data === 'statistika_data') {
            $this->showStats();
        } elseif ($this->data === 'kanallar') {
            $this->showChannels();
        } elseif ($this->data === 'animelar') {
            $this->showAnimes();
        } elseif ($this->data === 'add_channel') {
            $this->startAddChannel();
        } elseif (str_starts_with($this->data ?? '', 'del_channel_')) {
            $this->deleteChannel(str_replace('del_channel_', '', $this->data));
        } elseif ($this->data === 'add_anime') {
            $this->startAddAnime();
        } elseif ($this->data === 'del_anime_menu') {
            $this->showDeleteAnimeMenu();
        } elseif (str_starts_with($this->data ?? '', 'del_anime_')) {
            $this->deleteAnime(str_replace('del_anime_', '', $this->data));
        } elseif ($this->data === 'add_episode_menu') {
            $this->showAddEpisodeMenu();
        } elseif (str_starts_with($this->data ?? '', 'add_ep_select_')) {
            $this->startAddEpisode(str_replace('add_ep_select_', '', $this->data));
        }
    }

    public function handleState($state)
    {
        $step = $state->step;
        $data = $state->data ? json_decode($state->data, true) : [];

        if ($step === 'add_channel_id') {
            $this->processAddChannelId($this->text, $state);
        } elseif ($step === 'add_channel_link') {
            $this->processAddChannelLink($this->text, $state, $data);
        } elseif (str_starts_with($step, 'add_anime_')) {
            $this->processAddAnimeStep($step, $this->text, $state, $data);
        } elseif ($step === 'add_episode_number') {
             $this->processAddEpisodeNumber($this->text, $state, $data);
        } elseif ($step === 'add_episode_file') {
             $this->processAddEpisodeFile($state, $data);
        }
    }

    public function showPanel($edit = false)
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

    public function showStats()
    {
        $users = User::count();
        $animes = Anime::count();
        $text = "<b>📊 Statistika:</b>\n\n👤 Foydalanuvchilar: $users\n🎬 Animelar: $animes";
        TelegramService::sendMessage($this->chat_id, $text);
    }

    // --- Channels Logic ---
    protected function showChannels()
    {
        $channels = Channel::all();
        $buttons = [];
        foreach ($channels as $channel) {
            $buttons[] = [['text' => "🗑 " . ($channel->channel_id), 'callback_data' => "del_channel_" . $channel->id]];
        }
        $buttons[] = [['text' => "➕ Kanal qo'shish", 'callback_data' => "add_channel"]];
        $buttons[] = [['text' => "◀️ Orqaga", 'callback_data' => "boshqarish"]];

        TelegramService::editMessageText($this->chat_id, $this->message_id, "<b>Kanallar ro'yxati:</b>\n(O'chirish uchun ustiga bosing)", json_encode(['inline_keyboard' => $buttons]));
    }

    protected function startAddChannel()
    {
        $this->setState('add_channel_id');
        TelegramService::sendMessage($this->chat_id, "<b>Kanal ID sini kiriting:</b>\nMasalan: -100123456789\n(Kanalga botni admin qiling!)", json_encode(['inline_keyboard' => [[['text' => "Bekor qilish", 'callback_data' => "boshqarish"]]]]));
    }

    protected function processAddChannelId($text, $state)
    {
        if (!is_numeric($text) && !str_starts_with($text, '@')) {
             TelegramService::sendMessage($this->chat_id, "Noto'g'ri ID. Qaytadan kiriting yoki bekor qiling.");
             return;
        }

        $state->update(['step' => 'add_channel_link', 'data' => json_encode(['id' => $text])]);
        TelegramService::sendMessage($this->chat_id, "<b>Kanalga kirish linkini kiriting:</b>\nMasalan: https://t.me/+AbcD...");
    }

    protected function processAddChannelLink($text, $state, $data)
    {
        if (!filter_var($text, FILTER_VALIDATE_URL)) {
             TelegramService::sendMessage($this->chat_id, "Noto'g'ri link. Qaytadan kiriting.");
             return;
        }

        Channel::create([
            'channel_id' => $data['id'],
            'type' => 'private',
            'link' => $text,
            'required_members' => 0,
            'current_members' => 0
        ]);

        $state->delete();
        TelegramService::sendMessage($this->chat_id, "<b>Kanal muvaffaqiyatli qo'shildi!</b>");
        $this->showPanel();
    }

    protected function deleteChannel($id)
    {
        Channel::destroy($id);
        TelegramService::answerCallbackQuery($this->callback_query_id, "Kanal o'chirildi!", true);
        $this->showChannels();
    }

    // --- Anime Logic ---
    protected function showAnimes()
    {
        $keyboard = [
            [['text' => "➕ Anime qo'shish", 'callback_data' => "add_anime"]],
            [['text' => "➕ Qism qo'shish", 'callback_data' => "add_episode_menu"]],
            [['text' => "🗑 Anime o'chirish", 'callback_data' => "del_anime_menu"]],
            [['text' => "◀️ Orqaga", 'callback_data' => "boshqarish"]]
        ];
        TelegramService::editMessageText($this->chat_id, $this->message_id, "<b>Animelar bo'limi:</b>", json_encode(['inline_keyboard' => $keyboard]));
    }

    protected function startAddAnime()
    {
        $this->setState('add_anime_name');
        TelegramService::sendMessage($this->chat_id, "<b>Anime nomini kiriting:</b>", json_encode(['inline_keyboard' => [[['text' => "Bekor qilish", 'callback_data' => "boshqarish"]]]]));
    }

    protected function processAddAnimeStep($step, $text, $state, $data)
    {
        if ($step === 'add_anime_name') {
            $data['name'] = $text;
            $state->update(['step' => 'add_anime_photo', 'data' => json_encode($data)]);
            TelegramService::sendMessage($this->chat_id, "<b>Anime rasmi yoki videosini yuboring:</b>");
        } elseif ($step === 'add_anime_photo') {
             $fileId = $this->getFileId();
             if (!$fileId) {
                 TelegramService::sendMessage($this->chat_id, "Iltimos, rasm yoki video yuboring.");
                 return;
             }
             $data['file_id'] = $fileId;
             $state->update(['step' => 'add_anime_country', 'data' => json_encode($data)]);
             TelegramService::sendMessage($this->chat_id, "<b>Anime davlatini kiriting:</b> (Masalan: Yaponiya)");
        } elseif ($step === 'add_anime_country') {
            $data['country'] = $text;
            $state->update(['step' => 'add_anime_lang', 'data' => json_encode($data)]);
            TelegramService::sendMessage($this->chat_id, "<b>Anime tilini kiriting:</b> (Masalan: O'zbekcha)");
        } elseif ($step === 'add_anime_lang') {
            $data['language'] = $text;
            $state->update(['step' => 'add_anime_year', 'data' => json_encode($data)]);
            TelegramService::sendMessage($this->chat_id, "<b>Anime yilini kiriting:</b> (Masalan: 2023)");
        } elseif ($step === 'add_anime_year') {
            $data['year'] = $text;
            $state->update(['step' => 'add_anime_genre', 'data' => json_encode($data)]);
            TelegramService::sendMessage($this->chat_id, "<b>Anime janrini kiriting:</b> (Masalan: Sarguzasht)");
        } elseif ($step === 'add_anime_genre') {
            $data['genre'] = $text;

            // Create Anime
            $anime = Anime::create([
                'name' => $data['name'],
                'file_id' => $data['file_id'],
                'country' => $data['country'],
                'language' => $data['language'],
                'year' => $data['year'],
                'genre' => $data['genre'],
                'episodes_count' => 0,
                'views' => 0
            ]);

            $state->delete();
            TelegramService::sendMessage($this->chat_id, "<b>Anime muvaffaqiyatli qo'shildi!</b>\nID: {$anime->id}");
            $this->showAnimes();
        }
    }

    protected function showDeleteAnimeMenu()
    {
         // Show search or list last 10
         $animes = Anime::latest()->limit(10)->get();
         $buttons = [];
         foreach ($animes as $anime) {
             $buttons[] = [['text' => "❌ " . $anime->name, 'callback_data' => "del_anime_" . $anime->id]];
         }
         $buttons[] = [['text' => "◀️ Orqaga", 'callback_data' => "animelar"]];
         TelegramService::editMessageText($this->chat_id, $this->message_id, "<b>O'chirish uchun anime tanlang:</b>", json_encode(['inline_keyboard' => array_chunk($buttons, 1)]));
    }

    protected function deleteAnime($id)
    {
        Anime::destroy($id);
        TelegramService::answerCallbackQuery($this->callback_query_id, "Anime o'chirildi!", true);
        $this->showDeleteAnimeMenu();
    }

    // --- Episode Logic ---
    protected function showAddEpisodeMenu()
    {
        // Simple list, better would be search but let's keep it simple for now or use search like "searchByName" in UserHandler
        // Let's prompt for Anime ID or Code.
        // Actually, listing recent animes is easier for UX if few, but searching is scalable.
        // Let's show search buttons or list.
        $animes = Anime::latest()->limit(10)->get();
        $buttons = [];
        foreach ($animes as $anime) {
             $buttons[] = [['text' => $anime->name, 'callback_data' => "add_ep_select_" . $anime->id]];
        }
        $buttons[] = [['text' => "◀️ Orqaga", 'callback_data' => "animelar"]];

        TelegramService::editMessageText($this->chat_id, $this->message_id, "<b>Qism qo'shish uchun animeni tanlang:</b>", json_encode(['inline_keyboard' => array_chunk($buttons, 1)]));
    }

    protected function startAddEpisode($animeId)
    {
        $this->setState('add_episode_number', ['anime_id' => $animeId]);
        TelegramService::sendMessage($this->chat_id, "<b>Qism raqamini kiriting:</b> (Masalan: 1)", json_encode(['inline_keyboard' => [[['text' => "Bekor qilish", 'callback_data' => "boshqarish"]]]]));
    }

    protected function processAddEpisodeNumber($text, $state, $data)
    {
        if (!is_numeric($text)) {
            TelegramService::sendMessage($this->chat_id, "Faqat raqam kiriting.");
            return;
        }

        $data['episode_number'] = $text;
        $state->update(['step' => 'add_episode_file', 'data' => json_encode($data)]);
        TelegramService::sendMessage($this->chat_id, "<b>Qism videosini yuboring:</b>");
    }

    protected function processAddEpisodeFile($state, $data)
    {
         $fileId = $this->getFileId();
         if (!$fileId) {
             TelegramService::sendMessage($this->chat_id, "Iltimos, video yuboring.");
             return;
         }

         AnimeEpisode::create([
             'anime_id' => $data['anime_id'],
             'episode_number' => $data['episode_number'],
             'file_id' => $fileId
         ]);

         // Update total episodes count
         $anime = Anime::find($data['anime_id']);
         if ($anime) {
             $count = AnimeEpisode::where('anime_id', $anime->id)->count();
             $anime->update(['episodes_count' => $count]);
         }

         $state->delete();
         TelegramService::sendMessage($this->chat_id, "<b>Qism muvaffaqiyatli qo'shildi!</b>");
         $this->showAnimes();
    }

    // --- Helpers ---
    protected function setState($step, $data = [])
    {
        UserState::updateOrCreate(
            ['telegram_id' => $this->user_id],
            ['step' => $step, 'data' => json_encode($data)]
        );
    }

    protected function getFileId()
    {
        if (isset($this->update['message']['video'])) {
            return $this->update['message']['video']['file_id'];
        } elseif (isset($this->update['message']['photo'])) {
            return end($this->update['message']['photo'])['file_id'];
        } elseif (isset($this->update['message']['document'])) {
             return $this->update['message']['document']['file_id'];
        }
        return null;
    }
}
