<?php
/**
 * Bot Logic Handler
 * Author: SamDevX
 */

namespace App\Bot;

use App\Models\User;
use App\Models\UserState;
use App\Models\Setting;
use App\Models\Channel;
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

        // Admin State Handling
        if ($this->isAdmin() && $state && $state->step && !$this->data && (
            str_starts_with($state->step, 'add_channel_') ||
            str_starts_with($state->step, 'add_anime_') ||
            str_starts_with($state->step, 'add_episode_')
        )) {
            $adminHandler = new AdminHandler($this->chat_id, $this->user_id, $this->text, $this->data, $this->message_id, $this->update);
            $adminHandler->handleState($state);
            return;
        }

        // User State Handling
        if ($state && $state->step && !$this->data) {
             $userHandler = new UserHandler($this->chat_id, $this->user_id, $this->text, $this->data, $this->message_id);
             $userHandler->handleState($state);
             return;
        }

        if ($this->data) {
            if ($this->isAdmin() && (
                $this->data === 'boshqarish' ||
                $this->data === 'statistika_data' ||
                $this->data === 'kanallar' ||
                $this->data === 'animelar' ||
                $this->data === 'add_channel' ||
                str_starts_with($this->data, 'del_channel_') ||
                $this->data === 'add_anime' ||
                $this->data === 'del_anime_menu' ||
                str_starts_with($this->data, 'del_anime_') ||
                $this->data === 'add_episode_menu' ||
                str_starts_with($this->data, 'add_ep_select_')
            )) {
                $adminHandler = new AdminHandler($this->chat_id, $this->user_id, $this->text, $this->data, $this->message_id, $this->update);
                $adminHandler->handle();
                return;
            }
        } else {
             if ($this->text === '🗄 Boshqarish' && $this->isAdmin()) {
                 $adminHandler = new AdminHandler($this->chat_id, $this->user_id, $this->text, $this->data, $this->message_id, $this->update);
                 $adminHandler->showPanel();
                 return;
             }

             if ($this->text === '📊 Statistika' && $this->isAdmin()) {
                 $adminHandler = new AdminHandler($this->chat_id, $this->user_id, $this->text, $this->data, $this->message_id, $this->update);
                 $adminHandler->showStats();
                 return;
             }
        }

        // Default to UserHandler
        $userHandler = new UserHandler($this->chat_id, $this->user_id, $this->text, $this->data, $this->message_id);
        $userHandler->handle();
    }

    protected function checkSubscription()
    {
        if ($this->user && $this->user->status === 'VIP') return true;
        if (in_array($this->user_id, $this->admin_ids)) return true;

        $channels = Channel::all();
        $notSubscribed = [];

        foreach ($channels as $channel) {
            if ($channel->type === 'request') {
                continue;
            }

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

    protected function isAdmin()
    {
        return in_array($this->user_id, $this->admin_ids);
    }
}
