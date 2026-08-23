<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Notification Type
            |--------------------------------------------------------------------------
            |
            | manual
            | harvest
            | input_application
            | weather
            |
            */

            $table->string('type');

            $table->string('title');

            $table->text('message')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Scheduled Time
            |--------------------------------------------------------------------------
            */

            $table->timestamp('scheduled_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Notification Status
            |--------------------------------------------------------------------------
            */

            $table->timestamp('read_at')->nullable();

            $table->timestamp('dismissed_at')->nullable();

            $table->timestamp('snoozed_until')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Optional Related Model
            |--------------------------------------------------------------------------
            |
            | Example:
            | related_type = Crop
            | related_id = 5
            |
            */

            $table->string('related_type')->nullable();

            $table->unsignedBigInteger('related_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Additional Data
            |--------------------------------------------------------------------------
            */

            $table->json('data')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index([
                'user_id',
                'type'
            ]);

            $table->index([
                'related_type',
                'related_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};