<?php
/**
 * Telegram Bot Entry Point
 * Author: SamDevX
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Bot\BotHandler;
use Illuminate\Database\Capsule\Manager as Capsule;

// Check if installed
if (!file_exists(__DIR__ . '/../.env')) {
    die("Please run the installer first.");
}

// Get the update from Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    // Silence is golden
    exit;
}

try {
    // Handle the update
    $handler = new BotHandler($update);
    $handler->handle();
} catch (\Exception $e) {
    error_log("Bot Error: " . $e->getMessage());
}
