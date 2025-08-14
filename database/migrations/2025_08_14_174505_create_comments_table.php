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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->foreignIdFor(Post::class)->constrained('posts')->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('comments')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['post_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
