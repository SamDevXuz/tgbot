<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use App\Services\TelegramService;
use App\Models\User;
use App\Models\Setting;

abstract class TestCase extends BaseTestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->runMigrations();
        $this->seedSettings();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Capsule::disconnect();
        parent::tearDown();
    }

    protected function setUpDatabase()
    {
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    protected function runMigrations()
    {
        $schema = Capsule::schema();

        // Users Table
        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id')->unique();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('balance_bonus', 15, 2)->default(0);
            $table->integer('referrals_count')->default(0);
            $table->enum('status', ['Oddiy', 'VIP'])->default('Oddiy');
            $table->string('ban_status')->default('unban');
            $table->timestamp('vip_expires_at')->nullable();
            $table->bigInteger('referrer_id')->nullable();
            $table->timestamps();
        });

        // User States
        $schema->create('user_states', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id')->unique();
            $table->string('step')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        // Settings
        $schema->create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Animes Table
        $schema->create('animes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_id')->nullable();
            $table->integer('episodes_count')->default(0);
            $table->string('country')->nullable();
            $table->string('language')->nullable();
            $table->string('year')->nullable();
            $table->string('genre')->nullable();
            $table->integer('views')->default(0);
            $table->integer('likes')->default(0);
            $table->integer('dislikes')->default(0);
            $table->timestamps();
        });

        // Anime Episodes
        $schema->create('anime_episodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anime_id');
            $table->string('file_id');
            $table->integer('episode_number');
            $table->timestamps();
            $table->foreign('anime_id')->references('id')->on('animes')->onDelete('cascade');
        });

        // Shorts
        $schema->create('shorts', function (Blueprint $table) {
            $table->id();
            $table->string('file_id');
            $table->string('name');
            $table->string('time');
            $table->string('anime_id')->nullable();
            $table->timestamps();
        });

        // Channels
        $schema->create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('channel_id');
            $table->string('type');
            $table->string('link');
            $table->integer('required_members')->default(0);
            $table->integer('current_members')->default(0);
            $table->timestamps();
        });

        // Saved Animes
        $schema->create('saved_animes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->unsignedBigInteger('anime_id');
            $table->timestamps();
            $table->foreign('anime_id')->references('id')->on('animes')->onDelete('cascade');
        });

        // Comments
        $schema->create('comments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->unsignedBigInteger('anime_id');
            $table->text('message');
            $table->timestamps();
        });

        // Anime Votes
        $schema->create('anime_votes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->unsignedBigInteger('anime_id');
            $table->enum('type', ['like', 'dislike']);
            $table->timestamps();
            $table->foreign('anime_id')->references('id')->on('animes')->onDelete('cascade');
            $table->unique(['user_id', 'anime_id']);
        });
    }

    protected function seedSettings()
    {
         // Default Settings
         $defaultSettings = [
            'currency' => "so'm",
            'vip_price' => '25000',
            'status' => 'active',
            'studio_name' => 'Anime Studio',
            'start_text' => "Assalomu alaykum botimizga xush kelibsiz (:",
            'guide_text' => "Botdan foydalanish qo'llanmasi...",
            'sponsor_text' => "Reklama matni...",
            'admin_list' => '12345', // Default admin for testing
            'channels_force_sub' => '[]',
            'button_search' => '🔎 Qidiruv',
        ];

        foreach ($defaultSettings as $key => $value) {
            Setting::create(['key' => $key, 'value' => $value]);
        }
    }
}
