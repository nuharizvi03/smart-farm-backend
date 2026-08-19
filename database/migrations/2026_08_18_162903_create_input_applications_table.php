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
        Schema::create('input_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('crop_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('agrochemical_product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('input_type', [
                'fertilizer',
                'pesticide',
                'herbicide'
            ]);

            $table->string('product_name');

            $table->date('application_date');

            // Changed from quantity to quantity_applied
            $table->decimal('quantity_applied', 10, 2);

            $table->enum('unit', [
                'kg',
                'L',
                'g'
            ]);

            $table->decimal('unit_cost', 10, 2);

            $table->decimal('total_cost', 12, 2);

            $table->decimal('recommended_dosage', 10, 2)
                ->nullable();

            $table->string('dosage_unit')
                ->nullable();

            $table->date('next_application_date')
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
        Schema::dropIfExists('input_applications');
    }
};