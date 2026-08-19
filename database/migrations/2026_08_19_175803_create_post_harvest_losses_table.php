<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_harvest_losses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('harvest_id')
                ->constrained()
                ->cascadeOnDelete();

            // Quantity lost
            $table->decimal('quantity_lost', 10, 2);

            // kg / units
            $table->string('unit');

            // Reason for the loss
            $table->string('reason');

            // Financial value of the loss
            $table->decimal('loss_amount', 12, 2)
                ->default(0);

            $table->text('notes')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_harvest_losses');
    }
};