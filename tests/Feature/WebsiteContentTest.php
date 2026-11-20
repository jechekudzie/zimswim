<?php

use App\Models\ContentBlock;
use App\Support\Content\ContentCatalogue;
use App\Support\Content\PageContent;
use App\Support\Roles;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->admin = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);
});

it('renders the wording the page ships with when nothing has been changed', function (): void {
    expect(ContentBlock::count())->toBe(0);

    $content = app(PageContent::class)->forPage('home');

    expect($content['home.hero.heading'])
        ->toBe(ContentCatalogue::all()['home.hero.heading']['default']);
});

it('puts a changed wording on the public page', function (): void {
    $this->actingAs($this->admin)
        ->put(route('admin.content.update'), [
            'page' => 'home',
            'blocks' => ['home.hero.heading' => 'Every stroke counts'],
        ])->assertRedirect();

    // Keys carry dots, so the whole map is read rather than dot-indexed.
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('content', fn ($content): bool => $content['home.hero.heading'] === 'Every stroke counts')
            ->etc());
});

it('treats emptying a box as putting the original back', function (): void {
    ContentBlock::create(['key' => 'home.hero.heading', 'value' => 'Something else']);

    $this->actingAs($this->admin)
        ->put(route('admin.content.update'), [
            'page' => 'home',
            'blocks' => ['home.hero.heading' => '   '],
        ])->assertRedirect();

    expect(ContentBlock::where('key', 'home.hero.heading')->exists())->toBeFalse()
        ->and(app(PageContent::class)->forPage('home')['home.hero.heading'])
        ->toBe(ContentCatalogue::all()['home.hero.heading']['default']);
});

it('does not store a value identical to the original', function (): void {
    $original = ContentCatalogue::all()['home.hero.heading']['default'];

    $this->actingAs($this->admin)
        ->put(route('admin.content.update'), [
            'page' => 'home',
            'blocks' => ['home.hero.heading' => $original],
        ])->assertRedirect();

    // Otherwise the table fills with rows that change nothing, and the
    // "changed" marks on the editing screen stop meaning anything.
    expect(ContentBlock::count())->toBe(0);
});

it('ignores a key that is not in the catalogue', function (): void {
    $this->actingAs($this->admin)
        ->put(route('admin.content.update'), [
            'page' => 'home',
            'blocks' => ['home.hero.heading' => 'Fine', 'evil.key' => 'nope'],
        ])->assertRedirect();

    expect(ContentBlock::pluck('key')->all())->toBe(['home.hero.heading']);
});

it('keeps the wording away from somebody who cannot manage content', function (): void {
    $swimmer = userWithRole(Roles::SWIMMER, $this->club);

    $this->actingAs($swimmer)->get(route('admin.content.index'))->assertForbidden();

    $this->actingAs($swimmer)
        ->put(route('admin.content.update'), [
            'page' => 'home',
            'blocks' => ['home.hero.heading' => 'Hacked'],
        ])->assertForbidden();

    expect(ContentBlock::count())->toBe(0);
});

it('leaves the page standing when a row is removed behind its back', function (): void {
    ContentBlock::create(['key' => 'home.hero.lede', 'value' => 'A new opening line']);

    ContentBlock::query()->delete();

    $this->get(route('home'))->assertOk();

    expect(app(PageContent::class)->forPage('home')['home.hero.lede'])
        ->toBe(ContentCatalogue::all()['home.hero.lede']['default']);
});

it('shows every block on the editing screen with what it currently says', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.content.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/content/index')
            ->where('page', 'home')
            ->has('groups', 6)
            ->where('groups.0.blocks.0.value', ContentCatalogue::all()['home.hero.heading']['default'])
            ->where('groups.0.blocks.0.changed', false)
            ->etc());
});
