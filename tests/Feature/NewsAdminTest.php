<?php

use App\Enums\OrganizationType;
use App\Models\Post;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->editor = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);
});

it('writes an article and puts it on the news page', function (): void {
    $this->actingAs($this->editor)
        ->post(route('admin.news.store'), [
            'title' => 'The 2027 season opens in Bulawayo',
            'category' => 'news',
            'excerpt' => 'Entries open on the first of March.',
            'body' => '<p>The season opens with the <strong>provincial gala</strong>.</p>',
            'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

    $post = Post::where('title', 'The 2027 season opens in Bulawayo')->firstOrFail();

    expect($post->body)->toBe('<p>The season opens with the <strong>provincial gala</strong>.</p>')
        ->and($post->author_id)->toBe($this->editor->getKey())
        // Stamped with where it was written, which is what decides who may
        // edit it afterwards.
        ->and($post->organization_id)->toBe($this->national->getKey());

    $this->get(route('news.show', $post->slug))->assertOk();
});

it('reduces an article to what a page may show', function (): void {
    $this->actingAs($this->editor)
        ->post(route('admin.news.store'), [
            'title' => 'Gala report',
            'category' => 'news',
            'body' => '<h1 style="color:red">Shouting</h1><script>steal()</script><p>Real words</p>',
            'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

    expect(Post::where('title', 'Gala report')->value('body'))
        ->toBe('<p>Shouting</p><p>Real words</p>');
});

it('refuses an article with nothing in it', function (): void {
    // An editor left empty still sends a paragraph, which clears the
    // "required" rule while saying nothing at all.
    $this->actingAs($this->editor)
        ->post(route('admin.news.store'), [
            'title' => 'Empty',
            'category' => 'news',
            'body' => '<p></p>',
        ])
        ->assertSessionHasErrors('body');

    expect(Post::where('title', 'Empty')->exists())->toBeFalse();
});

it('keeps an article with no date off the public page', function (): void {
    $draft = Post::factory()->create(['published_at' => null]);

    $this->get(route('news.show', $draft->slug))->assertNotFound();
    $this->get(route('news.index'))->assertOk()->assertDontSee($draft->title);
});

it('keeps an article dated tomorrow off the page until tomorrow', function (): void {
    $scheduled = Post::factory()->create(['published_at' => now()->addDay()]);

    $this->get(route('news.show', $scheduled->slug))->assertNotFound();

    $this->travel(2)->days();

    $this->get(route('news.show', $scheduled->slug))->assertOk();
});

it('tells the writer which articles are drafts and which are waiting', function (): void {
    Post::factory()->create(['title' => 'Written', 'published_at' => null]);
    Post::factory()->create(['title' => 'Waiting', 'published_at' => now()->addWeek()]);
    Post::factory()->create(['title' => 'Out', 'published_at' => now()->subWeek()]);

    $this->actingAs($this->editor)
        ->get(route('admin.news.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/news/index')
            ->where('posts.data', fn ($posts): bool => collect($posts)
                ->pluck('status', 'title')
                ->all() === ['Written' => 'draft', 'Waiting' => 'scheduled', 'Out' => 'published'])
            ->etc());
});

it('keeps one province out of another province\'s articles', function (): void {
    $otherProvince = $this->national->children()->create([
        'name' => 'Matabeleland North',
        'type' => OrganizationType::Province,
    ]);

    $theirs = Post::factory()->create(['organization_id' => $otherProvince->getKey()]);
    $provincialEditor = userWithRole(Roles::PROVINCIAL_ADMINISTRATOR, $this->province);

    $this->actingAs($provincialEditor)
        ->get(route('admin.news.edit', $theirs->slug))
        ->assertForbidden();
});

it('lets a province write and edit its own', function (): void {
    $ours = Post::factory()->create(['organization_id' => $this->province->getKey()]);
    $provincialEditor = userWithRole(Roles::PROVINCIAL_ADMINISTRATOR, $this->province);

    $this->actingAs($provincialEditor)
        ->get(route('admin.news.edit', $ours->slug))
        ->assertOk();
});

it('keeps the news away from somebody who cannot manage content', function (): void {
    $swimmer = userWithRole(Roles::SWIMMER, $this->club);

    $this->actingAs($swimmer)->get(route('admin.news.index'))->assertForbidden();
    $this->actingAs($swimmer)
        ->post(route('admin.news.store'), ['title' => 'Mine', 'category' => 'news', 'body' => '<p>Hi</p>'])
        ->assertForbidden();
});

it('keeps a photograph and drops the one it replaces', function (): void {
    Storage::fake('public');

    $post = Post::factory()->create(['organization_id' => $this->national->getKey()]);

    $this->actingAs($this->editor)
        ->put(route('admin.news.update', $post->slug), [
            'title' => $post->title,
            'category' => 'news',
            'body' => '<p>Words</p>',
            'cover_image' => UploadedFile::fake()->image('gala.jpg'),
        ])->assertRedirect();

    $first = $post->fresh()?->cover_image_path;
    expect($first)->not->toBeNull();
    Storage::disk('public')->assertExists($first);

    $this->actingAs($this->editor)
        ->put(route('admin.news.update', $post->slug), [
            'title' => $post->title,
            'category' => 'news',
            'body' => '<p>Words</p>',
            'cover_image' => UploadedFile::fake()->image('better.jpg'),
        ])->assertRedirect();

    // The old one goes only once the new one is safely saved.
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($post->fresh()?->cover_image_path);
});
