<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade');
            $table->foreignId('camera_id')->constrained('cameras')->onDelete('cascade');
            $table->timestamp('detected_at');
            $table->string('snapshot')->nullable();
            $table->float('confidence')->nullable();
            $table->timestamps();
        });

        // Add indexes for better performance
        Schema::table('detections', function (Blueprint $table) {
            $table->index('detected_at');
            $table->index(['person_id', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detections');
    }
};