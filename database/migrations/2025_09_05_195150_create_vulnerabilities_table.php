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
        Schema::create('vulnerabilities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('cve_id')->nullable(); // CVE-2024-12345 format
            $table->text('description');
            $table->enum('severity', ['critical', 'high', 'medium', 'low', 'info'])->default('medium');
            $table->string('affected_product');
            $table->string('affected_versions')->nullable(); // e.g., "1.0.0 - 2.3.4"
            $table->enum('status', ['open', 'in_progress', 'resolved', 'wont_fix', 'duplicate'])->default('open');
            $table->string('reporter_name')->nullable();
            $table->string('reporter_email')->nullable();
            $table->decimal('cvss_score', 3, 1)->nullable(); // CVSS score 0.0-10.0
            $table->text('remediation')->nullable(); // How to fix
            $table->date('discovered_at')->nullable();
            $table->date('disclosed_at')->nullable();
            $table->date('resolved_at')->nullable();
            $table->json('references')->nullable(); // URLs, research papers, etc.
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Who created this record
            $table->timestamps();
            
            $table->index(['severity', 'status']);
            $table->index('affected_product');
            $table->index('discovered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vulnerabilities');
    }
};
