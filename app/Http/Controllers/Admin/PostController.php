<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\Content\RichText;
use App\Support\Images;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Writing the news.
 *
 * An article is published by giving it a date rather than by ticking a box,
 * because that date is what the public page orders by and what a reader sees
 * under the headline. Leaving it empty keeps the article a draft; setting it
 * in the future schedules it, and the public page will not show it until the
 * day comes.
 */
class PostController extends Controller
{
    public function __construct(private readonly OrganizationContext $context) {}

    /** Categories an article can be filed under. */
    private const CATEGORIES = ['news', 'announcement', 'result', 'officials'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Post::class);

        return Inertia::render('admin/news/index', [
            'posts' => $this->filtered($request)
                ->paginate(20)
                ->withQueryString()
                ->through(fn (Post $post): array => [
                    'id' => $post->getKey(),
                    'slug' => $post->slug,
                    'title' => $post->title,
                    'category' => $post->category,
                    'excerpt' => $post->excerpt,
                    'author' => $post->author?->name,
                    'organization' => $post->organization?->name,
                    'published_at' => $post->published_at?->toDateString(),
                    'status' => $this->status($post),
                    'is_featured' => $post->is_featured,
                ]),
            'filters' => [
                'search' => $request->string('search')->toString() ?: null,
                'status' => $request->string('status')->toString() ?: null,
            ],
            'statuses' => [
                ['value' => 'published', 'label' => 'Published'],
                ['value' => 'scheduled', 'label' => 'Scheduled'],
                ['value' => 'draft', 'label' => 'Draft'],
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Post::class);

        return Inertia::render('admin/news/form', [
            'post' => null,
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Post::class);

        $post = Post::create($this->validated($request) + [
            'author_id' => $request->user()?->getKey(),
            // Stamped with where it was written, which is what decides who
            // may edit it afterwards.
            'organization_id' => $this->context->currentId(),
        ]);

        Images::replace($request, $post, 'cover_image', 'cover_image_path', 'news');

        return to_route('admin.news.edit', $post)
            ->with('success', $post->title.' has been saved.');
    }

    public function edit(Post $post): Response
    {
        $this->authorize('update', $post);

        return Inertia::render('admin/news/form', [
            'post' => [
                'id' => $post->getKey(),
                'slug' => $post->slug,
                'title' => $post->title,
                'category' => $post->category,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'cover_image_url' => Images::url($post->cover_image_path),
                // A datetime-local field wants no timezone suffix on it.
                'published_at' => $post->published_at?->format('Y-m-d\TH:i'),
                'is_featured' => $post->is_featured,
                'status' => $this->status($post),
                'author' => $post->author?->name,
            ],
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $post->update($this->validated($request));
        Images::replace($request, $post, 'cover_image', 'cover_image_path', 'news');

        return back()->with('success', $post->title.' has been updated.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        Images::forget($post, 'cover_image_path');
        $post->delete();

        return to_route('admin.news.index')->with('success', $post->title.' has been removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:100000'],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'body.required' => 'An article needs something to say.',
            'cover_image.max' => 'That picture is over 5MB. A photograph for the web rarely needs to be.',
        ]);

        unset($validated['cover_image']);

        /*
         * An editor left empty still sends a paragraph, which clears the
         * "required" rule while saying nothing at all.
         */
        if (RichText::toPlain($validated['body']) === '') {
            throw ValidationException::withMessages(['body' => 'An article needs something to say.']);
        }

        return $validated;
    }

    /**
     * @return Builder<Post>
     */
    private function filtered(Request $request): Builder
    {
        return Post::query()
            ->with(['author:id,name', 'organization:id,name'])
            ->when($request->string('search')->toString(), function (Builder $query, string $search): void {
                $like = '%'.str_replace('%', '\%', $search).'%';
                $query->where('title', 'ilike', $like);
            })
            ->when($request->string('status')->toString(), fn (Builder $query, string $status) => match ($status) {
                'published' => $query->whereNotNull('published_at')->where('published_at', '<=', now()),
                'scheduled' => $query->where('published_at', '>', now()),
                default => $query->whereNull('published_at'),
            })
            // Drafts first, because they are the ones still wanting work;
            // then the most recent of everything else.
            ->orderByRaw('published_at is null desc')
            ->orderByDesc('published_at');
    }

    private function status(Post $post): string
    {
        return match (true) {
            $post->published_at === null => 'draft',
            $post->published_at->isFuture() => 'scheduled',
            default => 'published',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function categories(): array
    {
        return array_map(
            fn (string $category): array => ['value' => $category, 'label' => Str::headline($category)],
            self::CATEGORIES,
        );
    }
}
