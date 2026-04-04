<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nvrs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip');
            $table->integer('port')->default(554);
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable(); // Hikvision, Dahua, etc.
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nvrs');
    }
};