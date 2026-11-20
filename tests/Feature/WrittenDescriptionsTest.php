<?php

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Gala;
use App\Models\Organization;
use App\Models\Venue;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->admin = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);
});

it('stores a written description as the markup a page may show', function (): void {
    $gala = Gala::factory()->create();

    $gala->update([
        'description' => '<h2 onclick="steal()">Key points</h2>'
            .'<script>steal()</script>'
            .'<ul><li><p>Ages 11 and over</p></li></ul>'
            .'<p><a href="javascript:alert(1)">tap</a> for rules</p>',
    ]);

    // Sanitised on the way in, so nothing downstream has to remember to.
    expect(DB::table('galas')->where('id', $gala->getKey())->value('description'))
        ->toBe('<p>Key points</p><ul><li>Ages 11 and over</li></ul><p>tap for rules</p>');
});

it('reads a description written before the editor as the prose it is', function (): void {
    // A seeder, an import or a console wrote plain words here, and plain
    // words are not markup: printing them as markup would eat the ampersand
    // and run the two paragraphs together.
    $gala = Gala::factory()->create();

    DB::table('galas')
        ->where('id', $gala->getKey())
        ->update(['description' => "Ages 11 & over.\n\nEntries close on the 4th."]);

    expect($gala->fresh()?->description)
        ->toBe('<p>Ages 11 &amp; over.</p><p>Entries close on the 4th.</p>');
});

it('will not print markup written straight into the table', function (): void {
    $gala = Gala::factory()->create();

    DB::table('galas')
        ->where('id', $gala->getKey())
        ->update(['description' => '<p onclick="steal()">Note</p><script>steal()</script>']);

    expect($gala->fresh()?->description)->toBe('<p>Note</p>');
});

it('leaves a description nobody has written as nothing at all', function (): void {
    $gala = Gala::factory()->create(['description' => null]);

    // Not an empty paragraph, so a page can still ask whether there is one.
    expect($gala->description)->toBeNull()
        ->and(Gala::factory()->create(['description' => '   '])->description)->toBeNull();
});

it('treats every written field the same way', function (string $model, string $field): void {
    /** @var Model $record */
    $record = $model::factory()->create([$field => '<p>Words <b>here</b></p><script>bad()</script>']);

    expect($record->getAttribute($field))->toBe('<p>Words <strong>here</strong></p>');
})->with([
    [Gala::class, 'description'],
    [Organization::class, 'description'],
    [Course::class, 'description'],
    [CourseLesson::class, 'content'],
    [Venue::class, 'notes'],
]);

it('sends the about page the words without the markup', function (): void {
    $this->national->update(['description' => '<p>One national home for <strong>Zimbabwean</strong> swimming.</p>']);

    // The standfirst under the page title is one line of text, with nowhere
    // for a paragraph of markup to go.
    $this->get(route('about'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('national.description', 'One national home for Zimbabwean swimming.')
            ->etc());
});

it('shows a gala description on the public page as markup', function (): void {
    $gala = Gala::factory()->sanctioned()->create([
        'description' => '<p>Ages 11 and over.</p><ul><li>Bring a <strong>club cap</strong></li></ul>',
    ]);

    $this->get(route('galas.show', $gala->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('gala.description', '<p>Ages 11 and over.</p><ul><li>Bring a <strong>club cap</strong></li></ul>')
            ->etc());
});
