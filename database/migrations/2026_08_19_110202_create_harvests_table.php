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
    Schema::create('harvests', function (Blueprint $table) {
        $table->id();

        $table->foreignId('crop_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->date('harvest_date');

        $table->decimal('quantity_harvested', 12, 2);

        // kg or units
        $table->enum('unit', [
            'kg',
            'units'
        ]);

        $table->string('quality_grade')
            ->nullable();

        $table->string('storage_location')
            ->nullable();

        $table->text('notes')
            ->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::dropIfExists('harvests');
}
};
