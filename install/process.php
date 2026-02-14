<?php
/**
 * Installer Logic
 * Author: SamDevX
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use GuzzleHttp\Client;

// Helper to redirect with error
function redirectWithError($step, $message) {
    $_SESSION['error'] = $message;
    header("Location: index.php?step=$step");
    exit;
}

// Helper to redirect
function redirect($step) {
    header("Location: index.php?step=$step");
    exit;
}

$currentStep = $_POST['step'] ?? 1;

if ($currentStep == 2) {
    // Save Admin info to session
    $adminName = trim($_POST['admin_name']);
    $adminId = trim($_POST['admin_id']);

    if (empty($adminName) || empty($adminId)) {
        redirectWithError(2, "Iltimos, barcha maydonlarni to'ldiring.");
    }

    $_SESSION['admin_name'] = $adminName;
    $_SESSION['admin_id'] = $adminId;

    redirect(3);

} elseif ($currentStep == 3) {
    // Process DB and Token
    $dbHost = trim($_POST['db_host']);
    $dbUser = trim($_POST['db_user']);
    $dbPass = trim($_POST['db_pass']);
    $dbName = trim($_POST['db_name']);
    $botToken = trim($_POST['bot_token']);
    $webhookUrl = rtrim(trim($_POST['webhook_url']), '/');

    if (empty($dbHost) || empty($dbUser) || empty($dbName) || empty($botToken)) {
        redirectWithError(3, "Iltimos, barcha majburiy maydonlarni to'ldiring.");
    }

    // 1. Validate Database Connection
    try {
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $dbHost,
            'database'  => $dbName,
            'username'  => $dbUser,
            'password'  => $dbPass,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Test connection
        $capsule->getConnection()->getPdo();
    } catch (\Exception $e) {
        redirectWithError(3, "Ma'lumotlar bazasiga ulanishda xatolik: " . $e->getMessage());
    }

    // 2. Generate .env file
    $envContent = "APP_NAME=\"Anime Bot\"\n";
    $envContent .= "APP_ENV=production\n\n";
    $envContent .= "DB_CONNECTION=mysql\n";
    $envContent .= "DB_HOST=$dbHost\n";
    $envContent .= "DB_PORT=3306\n";
    $envContent .= "DB_DATABASE=$dbName\n";
    $envContent .= "DB_USERNAME=$dbUser\n";
    $envContent .= "DB_PASSWORD=$dbPass\n\n";
    $envContent .= "BOT_TOKEN=$botToken\n";
    $envContent .= "ADMIN_ID=" . $_SESSION['admin_id'] . "\n";
    $envContent .= "WEBHOOK_URL=$webhookUrl/public/index.php\n"; // Pointing to public entry

    // Write to root .env
    if (file_put_contents(__DIR__ . '/../.env', $envContent) === false) {
        redirectWithError(3, ".env faylini yaratishda xatolik. Iltimos, papkaga yozish huquqini (777) tekshiring.");
    }

    // 3. Migrate Database
    try {
        $schema = Capsule::schema();

        // Users Table
        if (!$schema->hasTable('users')) {
            $schema->create('users', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('telegram_id')->unique();
                $table->string('name')->nullable();
                $table->string('username')->nullable();
                $table->decimal('balance', 15, 2)->default(0);
                $table->decimal('balance_bonus', 15, 2)->default(0); // pul2 in original
                $table->integer('referrals_count')->default(0); // odam in original
                $table->enum('status', ['Oddiy', 'VIP'])->default('Oddiy');
                $table->string('ban_status')->default('unban');
                $table->timestamp('vip_expires_at')->nullable();
                $table->bigInteger('referrer_id')->nullable(); // refid
                $table->timestamps();
            });
        }

        // User States (for bot conversation steps)
        if (!$schema->hasTable('user_states')) {
            $schema->create('user_states', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('telegram_id')->unique();
                $table->string('step')->nullable();
                $table->json('data')->nullable(); // For temporary data storage
                $table->timestamps();
            });
        }

        // Settings (Admin config)
        if (!$schema->hasTable('settings')) {
            $schema->create('settings', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->text('value')->nullable();
                $table->timestamps();
            });

            // Default Settings
            $defaultSettings = [
                'currency' => "so'm",
                'vip_price' => '25000',
                'status' => 'active', // bot status
                'studio_name' => 'Anime Studio',
                'start_text' => "Assalomu alaykum botimizga xush kelibsiz (:",
                'guide_text' => "Botdan foydalanish qo'llanmasi...",
                'sponsor_text' => "Reklama matni...",
                'admin_list' => $_SESSION['admin_id'], // Initial admin
                'channels_force_sub' => '[]',
                'button_search' => '🔎 Qidiruv', // Default button text
            ];

            foreach ($defaultSettings as $key => $value) {
                 Capsule::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => date('Y-m-d H:i:s')]);
            }
        }

        // Animes Table
        if (!$schema->hasTable('animes')) {
            $schema->create('animes', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // nom
                $table->string('file_id')->nullable(); // rams
                $table->integer('episodes_count')->default(0); // qismi
                $table->string('country')->nullable(); // davlat
                $table->string('language')->nullable(); // tili
                $table->string('year')->nullable(); // yili
                $table->string('genre')->nullable(); // janri
                $table->integer('views')->default(0); // qidiruv
                $table->integer('likes')->default(0);
                $table->integer('dislikes')->default(0);
                $table->timestamps();
            });
        }

        // Anime Episodes
        if (!$schema->hasTable('anime_episodes')) {
            $schema->create('anime_episodes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('anime_id');
                $table->string('file_id');
                $table->integer('episode_number'); // qism
                $table->timestamps();

                $table->foreign('anime_id')->references('id')->on('animes')->onDelete('cascade');
            });
        }

        // Shorts
        if (!$schema->hasTable('shorts')) {
            $schema->create('shorts', function (Blueprint $table) {
                $table->id();
                $table->string('file_id'); // shorts_id
                $table->string('name');
                $table->string('time');
                $table->string('anime_id')->nullable();
                $table->timestamps();
            });
        }

        // Channels (Join Requests / Forced Sub)
        if (!$schema->hasTable('channels')) {
            $schema->create('channels', function (Blueprint $table) {
                $table->id();
                $table->string('channel_id'); // channelId
                $table->string('type'); // channelType (request/public)
                $table->string('link'); // channelLink
                $table->integer('required_members')->default(0); // channelUsers
                $table->integer('current_members')->default(0); // nowMembers
                $table->timestamps();
            });
        }

         // Saved Animes (Playlists)
        if (!$schema->hasTable('saved_animes')) {
            $schema->create('saved_animes', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id');
                $table->unsignedBigInteger('anime_id');
                $table->timestamps();

                 $table->foreign('anime_id')->references('id')->on('animes')->onDelete('cascade');
            });
        }

         // Comments
         if (!$schema->hasTable('comments')) {
            $schema->create('comments', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id');
                $table->unsignedBigInteger('anime_id');
                $table->text('message');
                $table->timestamps();
            });
        }

        // Anime Votes (Likes/Dislikes)
        if (!$schema->hasTable('anime_votes')) {
            $schema->create('anime_votes', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id');
                $table->unsignedBigInteger('anime_id');
                $table->enum('type', ['like', 'dislike']);
                $table->timestamps();

                $table->foreign('anime_id')->references('id')->on('animes')->onDelete('cascade');
                // Ensure unique vote per user per anime
                $table->unique(['user_id', 'anime_id']);
            });
        }


    } catch (\Exception $e) {
        redirectWithError(3, "Baza jadvallarini yaratishda xatolik: " . $e->getMessage());
    }

    // 4. Set Webhook
    $webhookUrlFull = $webhookUrl . "/public/index.php"; // Ensure we point to public/index.php
    $apiUrl = "https://api.telegram.org/bot$botToken/setWebhook?url=$webhookUrlFull";

    try {
        $client = new \GuzzleHttp\Client();
        $response = $client->get($apiUrl);
        $result = json_decode($response->getBody(), true);

        if (!$result['ok']) {
            throw new Exception($result['description']);
        }
    } catch (\Exception $e) {
        // Warning but not fatal? No, webhook is essential.
        redirectWithError(3, "Webhook o'rnatishda xatolik: " . $e->getMessage());
    }

    // Get Bot Info for the link
    try {
        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://api.telegram.org/bot$botToken/getMe");
        $botInfo = json_decode($response->getBody(), true);
        $_SESSION['bot_username'] = $botInfo['result']['username'] ?? 'bot';
    } catch (\Exception $e) {
        $_SESSION['bot_username'] = 'bot';
    }


    // Redirect to success
    $_SESSION['install_success'] = true;

    // Pass data to view
    $botLink = "https://t.me/" . ($_SESSION['bot_username'] ?? 'bot');

    view('step4', ['bot_link' => $botLink]); // Directly rendering view to avoid redirect loop issues or lost data
    exit;
}
