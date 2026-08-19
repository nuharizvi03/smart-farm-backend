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
    Schema::create('sales', function (Blueprint $table) {
        $table->id();

        // Harvest this sale belongs to
        $table->foreignId('harvest_id')
            ->constrained()
            ->cascadeOnDelete();

        // Buyer information
        $table->string('buyer_name');

        $table->string('buyer_contact')
            ->nullable();

        $table->date('sale_date');

        // Quantity sold from the harvest
        $table->decimal('quantity_sold', 12, 2);

        // LKR price per kg/unit
        $table->decimal('price_per_unit', 12, 2);

        /*
         * unpaid
         * partially_paid
         * fully_paid
         */
        $table->enum('payment_status', [
            'unpaid',
            'partially_paid',
            'fully_paid',
        ])->default('unpaid');

        // Recorded when payment status changes
        $table->date('payment_date')
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
    Schema::dropIfExists('sales');
}
};
