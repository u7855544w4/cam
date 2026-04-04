<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nvr_id')->nullable()->constrained('nvrs')->onDelete('set null');
            $table->string('name');
            $table->string('ip')->nullable();
            $table->integer('channel')->nullable(); // NVR channel number
            $table->string('rtsp_url')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};