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
use App\Models\Setting;
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
        // Always answer callback to stop loading animation
        if ($this->callback_query_id) {
            // We'll answer it specifically in methods if needed, otherwise generic answer at the end if not handled?
            // Better to answer immediately if it's a simple navigation.
        }

        if ($this->data === 'boshqarish' || $this->data === 'back') {
             $this->showPanel(true);
        } elseif ($this->data === 'statistika_data') {
            $this->showStats();
        } elseif ($this->data === 'kanallar') {
            $this->showChannels();
        } elseif ($this->data === 'animelar') {
            $this->showAnimes();
        } elseif ($this->data === 'users_manage') {
            $this->showUserManagement();
        } elseif ($this->data === 'broadcast') {
            $this->startBroadcast();
        } elseif ($this->data === 'manage_admins') {
            $this->showAdmins();
        }
        // Channels
        elseif ($this->data === 'add_channel') {
            $this->startAddChannel();
        } elseif (str_starts_with($this->data ?? '', 'del_channel_')) {
            $this->deleteChannel(str_replace('del_channel_', '', $this->data));
        }
        // Animes
        elseif ($this->data === 'add_anime') {
            $this->startAddAnime();
        } elseif ($this->data === 'del_anime_menu') {
            $this->showDeleteAnimeMenu();
        } elseif (str_starts_with($this->data ?? '', 'del_anime_')) {
            $this->deleteAnime(str_replace('del_anime_', '', $this->data));
        } elseif ($this->data === 'edit_anime_menu') {
            $this->showEditAnimeMenu();
        } elseif (str_starts_with($this->data ?? '', 'edit_sel_')) {
            $this->showEditAnimeOptions(str_replace('edit_sel_', '', $this->data));
        } elseif (str_starts_with($this->data ?? '', 'edit_field_')) {
            // format: edit_field_ID_FIELD
            $parts = explode('_', str_replace('edit_field_', '', $this->data));
            $this->startEditAnimeField($parts[0], $parts[1]);
        }
        // Episodes
        elseif ($this->data === 'add_episode_menu') {
            $this->showAddEpisodeMenu();
        } elseif (str_starts_with($this->data ?? '', 'add_ep_select_')) {
            $this->startAddEpisode(str_replace('add_ep_select_', '', $this->data));
        }
        // Users
        elseif ($this->data === 'search_user') {
            $this->startSearchUser();
        } elseif (str_starts_with($this->data ?? '', 'user_vip_')) {
            $this->toggleVip(str_replace('user_vip_', '', $this->data));
        } elseif (str_starts_with($this->data ?? '', 'user_ban_')) {
            $this->toggleBan(str_replace('user_ban_', '', $this->data));
        }
        // Admins
        elseif ($this->data === 'add_admin') {
            $this->startAddAdmin();
        } elseif (str_starts_with($this->data ?? '', 'del_admin_')) {
            $this->deleteAdmin(str_replace('del_admin_', '', $this->data));
        }

        if ($this->callback_query_id) {
             TelegramService::answerCallbackQuery($this->callback_query_id);
        }
    }

    public function handleState($state)
    {
        $step = $state->step;
        $data = $state->data ? json_decode($state->data, true) : [];

        // Channel
        if ($step === 'add_channel_id') {
            $this->processAddChannelId($this->text, $state);
        } elseif ($step === 'add_channel_link') {
            $this->processAddChannelLink($this->text, $state, $data);
        }
        // Anime
        elseif (str_starts_with($step, 'add_anime_')) {
            $this->processAddAnimeStep($step, $this->text, $state, $data);
        } elseif (str_starts_with($step, 'edit_anime_')) {
            $this->processEditAnimeStep($step, $this->text, $state, $data);
        }
        // Episode
        elseif ($step === 'add_episode_number') {
             $this->processAddEpisodeNumber($this->text, $state, $data);
        } elseif ($step === 'add_episode_file') {
             $this->processAddEpisodeFile($state, $data);
        }
        // User
        elseif ($step === 'search_user_id') {
            $this->processSearchUser($this->text, $state);
        }
        // Broadcast
        elseif ($step === 'broadcast_message') {
            $this->processBroadcast($state);
        }
        // Admin
        elseif ($step === 'add_admin_id') {
            $this->processAddAdmin($this->text, $state);
        }
    }

    public function showPanel($edit = false)
    {
        $keyboard = json_encode([
            'inline_keyboard' => [
                [['text' => "📊 Statistika", 'callback_data' => "statistika_data"], ['text' => "📨 Xabar yuborish", 'callback_data' => "broadcast"]],
                [['text' => "📢 Kanallar", 'callback_data' => "kanallar"], ['text' => "🎥 Animelar", 'callback_data' => "animelar"]],
                [['text' => "👥 Foydalanuvchilar", 'callback_data' => "users_manage"], ['text' => "👮‍♂️ Adminlar", 'callback_data' => "manage_admins"]],
                [['text' => "◀️ Orqaga", 'callback_data' => "back"]] // Actually back exits admin mode usually, or stays here.
            ]
        ]);

        $text = "<b>Admin Panel:</b>\nBo'limni tanlang:";

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

    // --- Broadcast ---
    protected function startBroadcast()
    {
        $this->setState('broadcast_message');
        TelegramService::sendMessage($this->chat_id, "<b>Xabarni yuboring (Text, Rasm, Video, Forward):</b>\nBekor qilish uchun /cancel");
    }

    protected function processBroadcast($state)
    {
        if ($this->text === '/cancel') {
            $state->delete();
            TelegramService::sendMessage($this->chat_id, "Bekor qilindi.");
            $this->showPanel();
            return;
        }

        // Logic to broadcast
        set_time_limit(0); // Prevent timeout for long lists
        $users = User::pluck('telegram_id');
        $count = 0;

        TelegramService::sendMessage($this->chat_id, "Xabar yuborish boshlandi...");

        $msgId = $this->message_id;

        foreach ($users as $uid) {
            $res = TelegramService::copyMessage($uid, $this->chat_id, $msgId);
            if ($res['ok']) $count++;
            // Small delay to avoid flood limits
            usleep(50000);
        }

        $state->delete();
        TelegramService::sendMessage($this->chat_id, "<b>Xabar $count ta foydalanuvchiga yuborildi.</b>");
        $this->showPanel();
    }

    // --- User Management ---
    protected function showUserManagement()
    {
        $keyboard = json_encode(['inline_keyboard' => [
            [['text' => "🔎 ID orqali qidirish", 'callback_data' => "search_user"]],
            [['text' => "◀️ Orqaga", 'callback_data' => "boshqarish"]]
        ]]);
        TelegramService::editMessageText($this->chat_id, $this->message_id, "<b>Foydalanuvchilar boshqaruvi:</b>", $keyboard);
    }

    protected function startSearchUser()
    {
        $this->setState('search_user_id');
        TelegramService::sendMessage($this->chat_id, "<b>Foydalanuvchi ID sini kiriting:</b>");
    }

    protected function processSearchUser($text, $state)
    {
        $user = User::where('telegram_id', $text)->first();
        if (!$user) {
            TelegramService::sendMessage($this->chat_id, "Foydalanuvchi topilmadi.");
            $state->delete();
            return;
        }

        $state->delete();
        $this->showUserActions($user);
    }

    protected function showUserActions($user)
    {
        $status = $user->status === 'VIP' ? "⭐️ VIP" : "👤 Oddiy";
        $ban = $user->ban_status === 'ban' ? "🔴 Ban qilingan" : "🟢 Aktiv";

        $text = "<b>Foydalanuvchi:</b> <a href='tg://user?id={$user->telegram_id}'>{$user->name}</a>\n";
        $text .= "ID: {$user->telegram_id}\n";
        $text .= "Status: $status\n";
        $text .= "Holati: $ban\n";
        $text .= "Balans: {$user->balance}\n";

        $keyboard = json_encode(['inline_keyboard' => [
            [['text' => ($user->status === 'VIP' ? "VIP ni olish" : "VIP berish"), 'callback_data' => "user_vip_" . $user->telegram_id]],
            [['text' => ($user->ban_status === 'ban' ? "Bandan olish" : "Ban qilish"), 'callback_data' => "user_ban_" . $user->telegram_id]],
            [['text' => "◀️ Orqaga", 'callback_data' => "users_manage"]]
        ]]);

        TelegramService::sendMessage($this->chat_id, $text, $keyboard);
    }

    protected function toggleVip($id)
    {
        $user = User::where('telegram_id', $id)->first();
        if ($user) {
            $user->status = ($user->status === 'VIP') ? 'Oddiy' : 'VIP';
            $user->save();
            TelegramService::answerCallbackQuery($this->callback_query_id, "Status o'zgartirildi!");
            $this->showUserActions($user); // Refresh
        }
    }

    protected function toggleBan($id)
    {
        $user = User::where('telegram_id', $id)->first();
        if ($user) {
            $user->ban_status = ($user->ban_status === 'ban') ? 'unban' : 'ban';
            $user->save();
            TelegramService::answerCallbackQuery($this->callback_query_id, "Holat o'zgartirildi!");
            $this->showUserActions($user); // Refresh
        }
    }

    // --- Admin Management ---
    protected function showAdmins()
    {
        $adminList = Setting::get('admin_list');
        $admins = explode("\n", $adminList);

        $buttons = [];
        foreach ($admins as $admin) {
            if (empty($admin)) continue;
            $buttons[] = [['text' => "🗑 $admin", 'callback_data' => "del_admin_$admin"]];
        }
        $buttons[] = [['text' => "➕ Admin qo'shish", 'callback_data' => "add_admin"]];
        $buttons[] = [['text' => "◀️ Orqaga", 'callback_data' => "boshqarish"]];

        TelegramService::editMessageText($this->chat_id, $this->message_id, "<b>Adminlar ro'yxati:</b>", json_encode(['inline_keyboard' => $buttons]));
    }

    protected function startAddAdmin()
    {
        $this->setState('add_admin_id');
        TelegramService::sendMessage($this->chat_id, "<b>Yangi admin ID sini kiriting:</b>");
    }

    protected function processAddAdmin($text, $state)
    {
        if (!is_numeric($text)) {
            TelegramService::sendMessage($this->chat_id, "Noto'g'ri ID.");
            return;
        }

        $current = Setting::get('admin_list');
        $admins = array_map('trim', explode("\n", $current));

        if (!in_array($text, $admins)) {
            $admins[] = $text;
            Setting::set('admin_list', implode("\n", $admins));
            TelegramService::sendMessage($this->chat_id, "Admin qo'shildi!");
        } else {
            TelegramService::sendMessage($this->chat_id, "Bu ID allaqachon admin.");
        }
        $state->delete();
        $this->showPanel();
    }

    protected function deleteAdmin($id)
    {
        $current = Setting::get('admin_list');
        $admins = explode("\n", $current);
        $newAdmins = array_filter($admins, fn($a) => trim($a) != $id);
        Setting::set('admin_list', implode("\n", $newAdmins));

        TelegramService::answerCallbackQuery($this->callback_query_id, "Admin o'chirildi!");
        $this->showAdmins();
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
            [['text' => "✏️ Anime tahrirlash", 'callback_data' => "edit_anime_menu"]],
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
             $fileInfo = $this->getFileInfo();
             if (!$fileInfo) {
                 TelegramService::sendMessage($this->chat_id, "Iltimos, rasm yoki video yuboring.");
                 return;
             }
             $data['file_id'] = $fileInfo['id'];
             $data['file_type'] = $fileInfo['type'];

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
                'file_type' => $data['file_type'] ?? 'photo',
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

    // Edit Anime
    protected function showEditAnimeMenu()
    {
         // Show search or list last 10
         $animes = Anime::latest()->limit(10)->get();
         $buttons = [];
         foreach ($animes as $anime) {
             $buttons[] = ['text' => "✏️ " . $anime->name, 'callback_data' => "edit_sel_" . $anime->id];
         }
         $buttons[] = ['text' => "◀️ Orqaga", 'callback_data' => "animelar"];
         TelegramService::editMessageText($this->chat_id, $this->message_id, "<b>Tahrirlash uchun anime tanlang:</b>", json_encode(['inline_keyboard' => array_chunk($buttons, 1)]));
    }

    protected function showEditAnimeOptions($id)
    {
        $anime = Anime::find($id);
        if (!$anime) {
            TelegramService::answerCallbackQuery($this->callback_query_id, "Anime topilmadi!");
            return;
        }

        $keyboard = json_encode(['inline_keyboard' => [
            [['text' => "Nomini o'zgartirish", 'callback_data' => "edit_field_{$id}_name"]],
            [['text' => "Rasmini o'zgartirish", 'callback_data' => "edit_field_{$id}_photo"]],
            [['text' => "◀️ Orqaga", 'callback_data' => "edit_anime_menu"]]
        ]]);

        TelegramService::editMessageText($this->chat_id, $this->message_id, "<b>{$anime->name}</b> ni tahrirlash:", $keyboard);
    }

    protected function startEditAnimeField($id, $field)
    {
        $this->setState('edit_anime_' . $field, ['id' => $id]);
        $msg = $field === 'photo' ? "Yangi rasm/video yuboring:" : "Yangi nomni kiriting:";
        TelegramService::sendMessage($this->chat_id, "<b>$msg</b>");
    }

    protected function processEditAnimeStep($step, $text, $state, $data)
    {
        $anime = Anime::find($data['id']);
        if (!$anime) {
             TelegramService::sendMessage($this->chat_id, "Anime topilmadi.");
             $state->delete();
             return;
        }

        if ($step === 'edit_anime_name') {
            $anime->update(['name' => $text]);
            TelegramService::sendMessage($this->chat_id, "Nom o'zgartirildi!");
        } elseif ($step === 'edit_anime_photo') {
            $fileInfo = $this->getFileInfo();
            if ($fileInfo) {
                $anime->update([
                    'file_id' => $fileInfo['id'],
                    'file_type' => $fileInfo['type']
                ]);
                TelegramService::sendMessage($this->chat_id, "Rasm o'zgartirildi!");
            } else {
                 TelegramService::sendMessage($this->chat_id, "Fayl yuboring.");
                 return;
            }
        }

        $state->delete();
        $this->showEditAnimeOptions($anime->id);
    }


    protected function showDeleteAnimeMenu()
    {
         $animes = Anime::latest()->limit(10)->get();
         $buttons = [];
         foreach ($animes as $anime) {
             $buttons[] = ['text' => "❌ " . $anime->name, 'callback_data' => "del_anime_" . $anime->id];
         }
         $buttons[] = ['text' => "◀️ Orqaga", 'callback_data' => "animelar"];
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
        $animes = Anime::latest()->limit(10)->get();
        $buttons = [];
        foreach ($animes as $anime) {
             $buttons[] = ['text' => $anime->name, 'callback_data' => "add_ep_select_" . $anime->id];
        }
        $buttons[] = ['text' => "◀️ Orqaga", 'callback_data' => "animelar"];

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
         $fileInfo = $this->getFileInfo();
         if (!$fileInfo) {
             TelegramService::sendMessage($this->chat_id, "Iltimos, video yuboring.");
             return;
         }

         AnimeEpisode::create([
             'anime_id' => $data['anime_id'],
             'episode_number' => $data['episode_number'],
             'file_id' => $fileInfo['id'],
             'file_type' => $fileInfo['type']
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

    protected function getFileInfo()
    {
        if (isset($this->update['message']['video'])) {
            return ['id' => $this->update['message']['video']['file_id'], 'type' => 'video'];
        } elseif (isset($this->update['message']['photo'])) {
            return ['id' => end($this->update['message']['photo'])['file_id'], 'type' => 'photo'];
        } elseif (isset($this->update['message']['document'])) {
             return ['id' => $this->update['message']['document']['file_id'], 'type' => 'document'];
        }
        return null;
    }
}
