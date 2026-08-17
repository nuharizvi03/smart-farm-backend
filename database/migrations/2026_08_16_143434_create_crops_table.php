<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crops', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plot_id')
                ->constrained('plots')
                ->cascadeOnDelete();

            $table->string('crop_name');

            $table->string('variety')->nullable();

            $table->date('planting_date');

            $table->date('expected_harvest_date')->nullable();

            $table->string('season')->nullable();

            $table->string('status')->default('planned');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crops');
    }
};