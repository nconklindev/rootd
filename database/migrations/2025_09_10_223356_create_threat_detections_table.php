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
        Schema::create('threat_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_analysis_id')->constrained()->onDelete('cascade');
            $table->string('threat_type');
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->text('description');
            $table->bigInteger('line_number')->nullable();
            $table->text('raw_log_entry');
            $table->string('source_ip')->nullable();
            $table->timestamp('timestamp_detected')->nullable();
            $table->integer('confidence_score')->default(50);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['log_analysis_id', 'severity']);
            $table->index(['threat_type', 'severity']);
            $table->index('source_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threat_detections');
    }
};
