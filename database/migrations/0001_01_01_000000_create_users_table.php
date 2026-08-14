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
    Schema::create('users', function (Blueprint $table) {
        $table->id();

        $table->string('full_name');

        $table->string('mobile',20);

        $table->string('email')->unique();

        $table->timestamp('email_verified_at')->nullable();

        $table->string('password');

        $table->enum('role',[
            'farmer',
            'extension_officer',
            'admin'
        ])->default('farmer');

        $table->string('district');

        $table->string('province')->nullable();

        $table->string('farm_name')->nullable();

        $table->string('profile_photo')->nullable();

        $table->rememberToken();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::dropIfExists('users');
}
};
