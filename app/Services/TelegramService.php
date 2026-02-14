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
            } elseif (isset($GLOBALS['BOT_TOKEN'])) { // Fallback for some setups
                 self::init($GLOBALS['BOT_TOKEN']);
            } else {
                // Return error instead of throwing to prevent crash if not configured in one specific place
                error_log("Telegram Bot Token not configured.");
                return ['ok' => false, 'description' => "Token not configured"];
            }
        }

        try {
            $response = self::$client->post($method, [
                'json' => $data
            ]);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            error_log("Telegram API Error ($method): " . $e->getMessage());
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

    public static function sendDocument($chat_id, $document, $caption = null, $reply_markup = null)
    {
        return self::request('sendDocument', [
            'chat_id' => $chat_id,
            'document' => $document,
            'caption' => $caption,
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ]);
    }

    public static function copyMessage($chat_id, $from_chat_id, $message_id, $caption = null, $reply_markup = null)
    {
        $data = [
            'chat_id' => $chat_id,
            'from_chat_id' => $from_chat_id,
            'message_id' => $message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup
        ];
        if ($caption !== null) {
            $data['caption'] = $caption;
        }
        return self::request('copyMessage', $data);
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
