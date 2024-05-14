<?php
// create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_ta', function (Blueprint $table) {
            $table->string('twitch_id', 255);
            $table->string('login', 255);
            $table->string('display_name', 255);
            $table->string('type', 50);
            $table->string('broadcaster_type', 50);
            $table->text('description');
            $table->string('profile_image_url', 255);
            $table->string('offline_image_url', 255);
            $table->integer('view_count');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_ta');
    }
};
