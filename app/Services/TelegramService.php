<?php
/**
 * Telegram Service
 * Author: SamDevX
 */

namespace App\Services;

use GuzzleHttp\Client;

class TelegramService
{
    protected static $client;
    protected static $token;

    public static function setClient($client)
    {
        self::$client = $client;
    }

    public static function init($token)
    {
        self::$token = $token;
        if (!self::$client) {
            self::$client = new Client([
                'base_uri' => "https://api.telegram.org/bot{$token}/",
                'timeout'  => 10.0,
            ]);
        }
    }

    public static function request($method, $data = [])
    {
        if (!self::$client) {
            if (isset($_ENV['BOT_TOKEN'])) {
                self::init($_ENV['BOT_TOKEN']);
            } else {
                throw new \Exception("Telegram Bot Token not configured.");
            }
        }

        try {
            $response = self::$client->post($method, [
                'json' => $data
            ]);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            // Log error
            error_log("Telegram API Error: " . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    public static function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = 'HTML')
    {
        return self::request('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => $parse_mode,
            'reply_markup' => $reply_markup
        ]);
    }

    public static function editMessageText($chat_id, $message_id, $text, $reply_markup = null, $parse_mode = 'HTML')
    {
         return self::request('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => $parse_mode,
            'reply_markup' => $reply_markup
        ]);
    }

    public static function editMessageReplyMarkup($chat_id, $message_id, $reply_markup = null)
    {
         return self::request('editMessageReplyMarkup', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'reply_markup' => $reply_markup
        ]);
    }

    public static function answerCallbackQuery($callback_query_id, $text = null, $show_alert = false)
    {
        return self::request('answerCallbackQuery', [
            'callback_query_id' => $callback_query_id,
            'text' => $text,
            'show_alert' => $show_alert
        ]);
    }

    public static function sendPhoto($chat_id, $photo, $caption = null, $reply_markup = null)
    {
        return self::request('sendPhoto', [
            'chat_id' => $chat_id,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }

    public static function sendVideo($chat_id, $video, $caption = null, $reply_markup = null)
    {
        return self::request('sendVideo', [
            'chat_id' => $chat_id,
            'video' => $video,
            'caption' => $caption,
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }

    public static function deleteMessage($chat_id, $message_id)
    {
        return self::request('deleteMessage', [
            'chat_id' => $chat_id,
            'message_id' => $message_id
        ]);
    }

    public static function getChatMember($chat_id, $user_id)
    {
        return self::request('getChatMember', [
            'chat_id' => $chat_id,
            'user_id' => $user_id
        ]);
    }
}
