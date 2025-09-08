<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPostController;
use App\Http\Controllers\VulnerabilityController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Posts routes
Route::get('/posts', [PostController::class, 'index'])->middleware(['auth'])->name('posts.index');
Route::get('/posts/me', [PostController::class, 'myPosts'])->middleware(['auth'])->name('posts.me');
Route::get('/posts/create', [PostController::class, 'create'])->middleware(['auth'])->name('posts.create');
Route::post('/posts', [PostController::class, 'store'])->middleware(['auth'])->name('posts.store');
Route::get('/posts/{post}', [PostController::class, 'show'])->middleware(['auth'])->name('posts.show');
Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->middleware(['auth'])->name('posts.edit');
Route::put('/posts/{post}', [PostController::class, 'update'])->middleware(['auth'])->name('posts.update');
Route::post('/posts/{id}/like', [PostController::class, 'like'])->middleware(['auth'])->name('posts.like');
Route::delete('/posts/{id}/like', [PostController::class, 'unlike'])->middleware(['auth'])->name('posts.unlike');
Route::delete('/posts/{post:id}', [PostController::class, 'destroy'])->middleware(['auth'])->name('posts.destroy');

// User Profile and Posts
Route::get('/u/{user:username}', [UserController::class, 'show'])->middleware(['auth'])->name('users.show');
Route::post('/u/{user:username}/follow', [UserController::class, 'follow'])->middleware(['auth'])->name('users.follow');
Route::get('/u/{user:username}/posts', [UserPostController::class, 'index'])->middleware(['auth'])->name('users.posts');

// Categories
Route::get('/categories', [CategoryController::class, 'index'])->middleware(['auth'])->name('categories.index');
Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->middleware(['auth'])->name('categories.show');

// Wiki

// Vulnerability Database
Route::middleware(['auth', 'permission:create_vulnerabilities'])->group(function () {
    Route::get('/vulnerabilities/create', [VulnerabilityController::class, 'create'])->name('vulnerabilities.create');
    Route::post('/vulnerabilities', [VulnerabilityController::class, 'store'])->name('vulnerabilities.store');
    Route::get('/vulnerabilities/{vulnerability}/edit', [VulnerabilityController::class, 'edit'])->name('vulnerabilities.edit');
    Route::put('/vulnerabilities/{vulnerability}', [VulnerabilityController::class, 'update'])->name('vulnerabilities.update');
});

Route::middleware(['auth', 'permission:view_vulnerabilities'])->group(function () {
    Route::get('/vulnerabilities', [VulnerabilityController::class, 'index'])->name('vulnerabilities.index');
    Route::get('/vulnerabilities/{vulnerability}', [VulnerabilityController::class, 'show'])->name('vulnerabilities.show');
});

Route::delete('/vulnerabilities/{vulnerability}', [VulnerabilityController::class, 'destroy'])->name('vulnerabilities.destroy');

// Feed
Route::get('/feed', [FeedController::class, 'index'])->middleware(['auth'])->name('feed');

// Tags
Route::get('/tags', [TagController::class, 'index'])->middleware(['auth'])->name('tags.index');
Route::get('/tags/{tag:slug}', [TagController::class, 'show'])->middleware(['auth'])->name('tags.show');

// Comments routes
Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->middleware(['auth'])->name('posts.comments.store');
Route::post('/comments/{comment}/like', [CommentController::class, 'like'])->middleware(['auth'])->name('comments.like');
Route::delete('/comments/{comment}/like', [CommentController::class, 'unlike'])->middleware(['auth'])->name('comments.unlike');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
