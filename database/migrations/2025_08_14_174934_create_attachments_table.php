<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Post::class)->constrained('posts');
            $table->foreignIdFor(User::class)->constrained('users');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type');
            $table->string('type');
            $table->unsignedBigInteger('download_count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
