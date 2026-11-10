<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('public/news', [
            'posts' => Post::query()
                ->published()
                ->orderByDesc('published_at')
                ->paginate(9)
                ->through(fn (Post $post): array => [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'category' => $post->category,
                    'published_at' => $post->published_at?->toDateString(),
                ]),
        ]);
    }

    public function show(Post $post): Response
    {
        abort_unless($post->isPublished(), 404);

        return Inertia::render('public/news-article', [
            'post' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'category' => $post->category,
                'published_at' => $post->published_at?->toDateString(),
            ],
            'related' => Post::query()
                ->published()
                ->whereKeyNot($post->getKey())
                ->orderByDesc('published_at')
                ->limit(3)
                ->get(['title', 'slug', 'excerpt', 'published_at']),
        ]);
    }
}
