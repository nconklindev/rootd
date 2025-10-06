<?php

namespace App\Http\Middleware;

use App\Inspiring;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $user = $request->user();

        return
            array_merge(parent::share($request), [
                'name' => config('app.name'),
                'quote' => ['message' => trim($message), 'author' => trim($author)],
                'auth' => [
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'username' => $user->username,
                        'roles' => $user->getRoleNames(),
                        'email_verified_at' => $user->email_verified_at,
                    ] : null,
                ],
                'flash' => [
                    'message' => fn() => $request->session()->get('message'),
                    'success' => fn() => $request->session()->get('success'),
                ],
                'ziggy' => [
                    ...(new Ziggy)->toArray(),
                    'location' => $request->url(),
                ],
                'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
                'siteData' => [
                    'categories' => Category::query()
                        ->select(['name', 'slug', 'color'])
                        ->withCount('posts')
                        ->orderByDesc('posts_count')
                        ->orderBy('name')
                        ->limit(10)
                        ->get()
                        ->map(fn($category) => [
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'color' => $category->color,
                            'post_count' => $category->posts_count,
                        ]),
                ],
            ]);
    }
}
