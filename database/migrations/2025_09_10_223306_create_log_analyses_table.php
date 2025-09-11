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
        Schema::create('log_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('original_filename');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('file_hash', 64)->index();
            $table->string('log_format')->nullable();
            $table->bigInteger('total_entries')->default(0);
            $table->integer('threats_detected')->default(0);
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('progress_percentage')->default(0);
            $table->bigInteger('entries_processed')->default(0);
            $table->json('analysis_results')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->decimal('processing_duration_seconds', 8, 3)->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'processing_status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_analyses');
    }
};
