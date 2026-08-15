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
    Schema::create('farms', function (Blueprint $table) {
        $table->id();

        $table->foreignId('user_id')
              ->constrained('users')
              ->cascadeOnDelete();

        $table->string('farm_name');

        $table->string('location');

        $table->string('district');

        $table->string('province')->nullable();

        $table->decimal('total_area', 10, 2);

        $table->string('area_unit')->default('acres');

        $table->text('description')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::dropIfExists('farms');
}
};
