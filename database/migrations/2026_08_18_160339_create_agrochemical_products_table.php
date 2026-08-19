<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agrochemical_products', function (Blueprint $table) {
            $table->id();

            $table->string('product_name');

            $table->enum('input_type', [
                'fertilizer',
                'pesticide',
                'herbicide'
            ]);

            $table->string('brand_name')->nullable();

            $table->string('active_ingredient')->nullable();

            $table->string('unit')->nullable();

            $table->decimal('default_unit_cost', 10, 2)->nullable();

            $table->boolean('is_preloaded')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agrochemical_products');
    }
};