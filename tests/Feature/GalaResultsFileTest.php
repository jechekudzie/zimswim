<?php

use App\Models\Document;
use App\Models\Gala;
use App\Models\Season;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();

    $this->gala = Gala::factory()->sanctioned()->create([
        'name' => 'National Sprint Gala',
        'season_id' => Season::factory()->create()->getKey(),
        'host_organization_id' => $this->province->getKey(),
        'is_published' => true,
    ]);
});

it('attaches the MeetManager export to a gala', function (): void {
    Storage::fake('local');

    $this->actingAs(userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national))
        ->post(route('admin.galas.results-files.store', $this->gala), [
            'title' => 'Full results, day 1',
            'file' => UploadedFile::fake()->create('results.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect();

    $document = Document::sole();

    expect($document->gala_id)->toBe($this->gala->getKey())
        ->and($document->category)->toBe('results')
        ->and($document->mime_type)->toBe('application/pdf');

    Storage::disk('local')->assertExists($document->file_path);
});

it('takes only a PDF, which is what MeetManager exports', function (): void {
    Storage::fake('local');

    $this->actingAs(userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national))
        ->post(route('admin.galas.results-files.store', $this->gala), [
            'title' => 'Results',
            'file' => UploadedFile::fake()->create('results.xlsx', 20),
        ])
        ->assertSessionHasErrors('file');

    expect(Document::count())->toBe(0);
});

it('keeps a club secretary from publishing results for a gala', function (): void {
    Storage::fake('local');

    $this->actingAs(userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club))
        ->post(route('admin.galas.results-files.store', $this->gala), [
            'title' => 'Results',
            'file' => UploadedFile::fake()->create('results.pdf', 20, 'application/pdf'),
        ])
        ->assertForbidden();

    expect(Document::count())->toBe(0);
});

it('hands the file to anybody once the gala is published', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('gala-results/x.pdf', '%PDF-1.4 fake');

    $document = Document::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'category' => 'results',
        'file_path' => 'gala-results/x.pdf',
        'is_public' => true,
    ]);

    $this->get(route('results.file', [$this->gala, $document]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('will not hand out results for a gala that is not published', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('gala-results/x.pdf', '%PDF-1.4 fake');

    $this->gala->update(['is_published' => false]);

    $document = Document::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'category' => 'results',
        'file_path' => 'gala-results/x.pdf',
        'is_public' => true,
    ]);

    $this->get(route('results.file', [$this->gala, $document]))->assertNotFound();
});

it('will not serve a file through a gala it does not belong to', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('gala-results/x.pdf', '%PDF-1.4 fake');

    $other = Gala::factory()->sanctioned()->create([
        'season_id' => $this->gala->season_id,
        'host_organization_id' => $this->province->getKey(),
        'is_published' => true,
    ]);

    $document = Document::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'category' => 'results',
        'file_path' => 'gala-results/x.pdf',
        'is_public' => true,
    ]);

    $this->get(route('results.file', [$other, $document]))->assertNotFound();
});

it('offers the file on the gala results page', function (): void {
    Storage::fake('local');

    Document::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'category' => 'results',
        'title' => 'Full results, day 1',
        'file_path' => 'gala-results/x.pdf',
        'is_public' => true,
    ]);

    $this->get(route('results.show', $this->gala))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('resultsFiles', 1)
            ->where('resultsFiles.0.title', 'Full results, day 1')
            ->etc());
});

it('removes the file from disk when it is taken down', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('gala-results/x.pdf', '%PDF-1.4 fake');

    $document = Document::factory()->create([
        'gala_id' => $this->gala->getKey(),
        'category' => 'results',
        'file_path' => 'gala-results/x.pdf',
    ]);

    $this->actingAs(userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national))
        ->delete(route('admin.galas.results-files.destroy', [$this->gala, $document]))
        ->assertRedirect();

    expect(Document::count())->toBe(0);
    Storage::disk('local')->assertMissing('gala-results/x.pdf');
});
