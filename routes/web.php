<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Posts routes
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/create', [PostController::class, 'create'])->middleware(['auth'])->name('posts.create');
Route::post('/posts', [PostController::class, 'store'])->middleware(['auth'])->name('posts.store');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->middleware(['auth'])->name('posts.edit');
Route::put('/posts/{post}', [PostController::class, 'update'])->middleware(['auth'])->name('posts.update');
Route::delete('/posts/{post}', [PostController::class, 'destroy'])->middleware(['auth'])->name('posts.destroy');

// Comments routes
Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->middleware(['auth'])->name('posts.comments.store');
Route::post('/comments/{comment}/like', [CommentController::class, 'like'])->middleware(['auth'])->name('comments.like');
Route::delete('/comments/{comment}/like', [CommentController::class, 'unlike'])->middleware(['auth'])->name('comments.unlike');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
