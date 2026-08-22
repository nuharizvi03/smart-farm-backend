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
        Schema::table('post_harvest_losses', function (Blueprint $table) {
            $table->date('loss_date')
                ->nullable()
                ->after('harvest_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_harvest_losses', function (Blueprint $table) {
            $table->dropColumn('loss_date');
        });
    }
};