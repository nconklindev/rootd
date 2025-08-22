<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserPostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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
Route::delete('/posts/{post}', [PostController::class, 'destroy'])->middleware(['auth'])->name('posts.destroy');

// User Posts (single GET)
Route::get('/{user:name}/posts', [UserPostController::class, 'index'])->middleware(['auth'])->name('users.posts');

// Categories
Route::get('/categories', [CategoryController::class, 'index'])->middleware(['auth'])->name('categories.index');
Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->middleware(['auth'])->name('categories.show');

// Comments routes
Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->middleware(['auth'])->name('posts.comments.store');
Route::post('/comments/{comment}/like', [CommentController::class, 'like'])->middleware(['auth'])->name('comments.like');
Route::delete('/comments/{comment}/like', [CommentController::class, 'unlike'])->middleware(['auth'])->name('comments.unlike');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
