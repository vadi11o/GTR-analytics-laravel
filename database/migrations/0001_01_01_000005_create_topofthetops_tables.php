<?php
// create_top_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topGames', function (Blueprint $table) {
            $table->id('game_id');
            $table->string('game_name', 70);
        });

        Schema::create('topVideos', function (Blueprint $table) {
            $table->string('video_id', 255)->primary();
            $table->string('game_id', 255);
            $table->string('title', 255);
            $table->string('views',255);
            $table->string('user_name', 255);
            $table->string('duration', 255);
            $table->string('created_at', 255);
        });

        Schema::create('topOfTheTops', function (Blueprint $table) {
            $table->string('game_id', 255)->primary();
            $table->string('game_name', 255);
            $table->string('user_name', 255);
            $table->string('total_videos',255);
            $table->string('total_views',255);
            $table->string('most_viewed_title', 255);
            $table->string('most_viewed_views',255);
            $table->string('most_viewed_duration', 255);
            $table->string('most_viewed_created_at', 255);
            $table->timestamp('ultima_actualizacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topGames');
        Schema::dropIfExists('topVideos');
        Schema::dropIfExists('topOfTheTops');
    }
};
