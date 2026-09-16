<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_libraries', function (Blueprint $table) {
            $table->id();

            $table->string('crop_name');
            $table->string('variety')->nullable();
            $table->string('category')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['crop_name', 'variety']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_libraries');
    }
};