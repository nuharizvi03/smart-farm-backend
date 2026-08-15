<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_id')
                ->constrained('farms')
                ->cascadeOnDelete();

            $table->string('plot_name');

            $table->decimal('area', 10, 2);

            $table->string('area_unit')->default('acres');

            $table->string('soil_type')->nullable();

            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plots');
    }
};