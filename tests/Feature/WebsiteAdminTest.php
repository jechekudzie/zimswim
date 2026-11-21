<?php

use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Models\Document;
use App\Models\Gala;
use App\Models\Sponsor;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    seedRoles();
    [$this->national, $this->province, $this->club] = organizationTree();
    $this->editor = userWithRole(Roles::SYSTEM_ADMINISTRATOR, $this->national);
});

describe('sponsors', function (): void {
    it('puts a sponsor on the home page with its mark', function (): void {
        Storage::fake('public');

        $this->actingAs($this->editor)
            ->post(route('admin.sponsors.store'), [
                'name' => 'Old Mutual',
                'tier' => 'principal',
                'website' => 'https://oldmutual.co.zw',
                'position' => 1,
                'is_active' => true,
                'logo' => UploadedFile::fake()->image('mark.png'),
            ])->assertRedirect();

        $sponsor = Sponsor::where('name', 'Old Mutual')->firstOrFail();

        expect($sponsor->logo_path)->not->toBeNull();
        Storage::disk('public')->assertExists($sponsor->logo_path);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('sponsors', fn ($sponsors): bool => collect($sponsors)
                    ->contains(fn (array $row): bool => $row['name'] === 'Old Mutual' && $row['logo_url'] !== null))
                ->etc());
    });

    it('keeps a sponsor that is not showing off the home page', function (): void {
        Sponsor::factory()->create(['name' => 'Quiet Backer', 'is_active' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('sponsors', fn ($sponsors): bool => ! collect($sponsors)
                    ->contains(fn (array $row): bool => $row['name'] === 'Quiet Backer'))
                ->etc());
    });

    it('takes the logo with the sponsor when one is removed', function (): void {
        Storage::fake('public');

        $this->actingAs($this->editor)
            ->post(route('admin.sponsors.store'), [
                'name' => 'Passing Partner',
                'tier' => 'supporter',
                'logo' => UploadedFile::fake()->image('mark.png'),
            ])->assertRedirect();

        $sponsor = Sponsor::where('name', 'Passing Partner')->firstOrFail();
        $logo = $sponsor->logo_path;

        $this->actingAs($this->editor)
            ->delete(route('admin.sponsors.destroy', $sponsor->slug))
            ->assertRedirect();

        // Otherwise the disk fills with marks nothing points at any more.
        Storage::disk('public')->assertMissing($logo);
    });

    it('keeps the sponsor board away from somebody who cannot manage content', function (): void {
        $swimmer = userWithRole(Roles::SWIMMER, $this->club);

        $this->actingAs($swimmer)->get(route('admin.sponsors.index'))->assertForbidden();
    });
});

describe('committees', function (): void {
    it('shows the body being acted in, not the whole federation', function (): void {
        Committee::factory()->create(['organization_id' => $this->national->getKey(), 'name' => 'National Executive']);
        Committee::factory()->create(['organization_id' => $this->province->getKey(), 'name' => 'Provincial Executive']);

        $this->actingAs($this->editor)
            ->get(route('admin.committees.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/committees/index')
                ->where('committees', fn ($committees): bool => collect($committees)->pluck('name')->all() === ['National Executive'])
                ->etc());
    });

    it('adds somebody to a committee and shows them on the about page', function (): void {
        $committee = Committee::factory()->create([
            'organization_id' => $this->national->getKey(),
            'kind' => 'executive',
        ]);

        $this->actingAs($this->editor)
            ->post(route('admin.committees.members.store', $committee), [
                'person_name' => 'Tendai Moyo',
                'role' => 'Chairperson',
                'email' => 'chair@example.org',
                'is_published' => true,
            ])->assertRedirect();

        $this->get(route('about'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('committees', fn ($committees): bool => collect($committees)
                    ->flatMap(fn (array $row): array => $row['members'])
                    ->contains(fn (array $member): bool => $member['name'] === 'Tendai Moyo'))
                ->etc());
    });

    it('keeps somebody held back off the about page without losing them', function (): void {
        $committee = Committee::factory()->create([
            'organization_id' => $this->national->getKey(),
            'kind' => 'executive',
        ]);

        CommitteeMember::factory()->create([
            'committee_id' => $committee->getKey(),
            'person_name' => 'Awaiting Confirmation',
            'is_published' => false,
        ]);

        // This is what the federation needs between somebody standing down
        // and their successor being confirmed.
        $this->get(route('about'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('committees', fn ($committees): bool => ! collect($committees)
                    ->flatMap(fn (array $row): array => $row['members'])
                    ->contains(fn (array $member): bool => $member['name'] === 'Awaiting Confirmation'))
                ->etc());

        expect(CommitteeMember::where('person_name', 'Awaiting Confirmation')->exists())->toBeTrue();
    });

    it('keeps one province out of another body\'s committee', function (): void {
        $theirs = Committee::factory()->create(['organization_id' => $this->national->getKey()]);
        $provincialEditor = userWithRole(Roles::PROVINCIAL_ADMINISTRATOR, $this->province);

        $this->actingAs($provincialEditor)
            ->post(route('admin.committees.members.store', $theirs), [
                'person_name' => 'Not Theirs',
                'role' => 'Member',
            ])->assertForbidden();
    });

    it('refuses a member sent to the wrong committee', function (): void {
        $committee = Committee::factory()->create(['organization_id' => $this->national->getKey()]);
        $other = Committee::factory()->create(['organization_id' => $this->national->getKey()]);
        $member = CommitteeMember::factory()->create(['committee_id' => $other->getKey()]);

        $this->actingAs($this->editor)
            ->delete(route('admin.committees.members.destroy', [$committee, $member]))
            ->assertNotFound();
    });
});

describe('documents', function (): void {
    it('publishes a document to the library', function (): void {
        Storage::fake('local');

        $this->actingAs($this->editor)
            ->post(route('admin.documents.store'), [
                'title' => 'National Handbook 2026/2027',
                'category' => 'handbook',
                'description' => '<p>Everything a club needs for the season.</p>',
                'is_public' => true,
                'file' => UploadedFile::fake()->create('handbook.pdf', 400, 'application/pdf'),
            ])->assertRedirect();

        $document = Document::where('title', 'National Handbook 2026/2027')->firstOrFail();

        Storage::disk('local')->assertExists($document->file_path);

        $this->get(route('library.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('shelves', fn ($shelves): bool => collect($shelves)
                    ->flatMap(fn (array $shelf): array => $shelf['documents'])
                    ->contains(fn (array $row): bool => $row['title'] === 'National Handbook 2026/2027'))
                ->etc());

        $this->get(route('library.download', $document->slug))->assertOk();
    });

    it('will not hand over a document that is being held back', function (): void {
        $draft = Document::factory()->create(['is_public' => false, 'gala_id' => null]);

        // Not public has to mean unreachable, or it means nothing at all.
        $this->get(route('library.download', $draft->slug))->assertNotFound();
    });

    it('keeps a gala results file out of the library', function (): void {
        $gala = Gala::factory()->create();
        $results = Document::factory()->create([
            'title' => 'Day one results',
            'category' => 'results',
            'gala_id' => $gala->getKey(),
            'is_public' => true,
        ]);

        // It belongs on the results page, with the gala it was swum at.
        $this->get(route('library.download', $results->slug))->assertNotFound();

        $this->get(route('library.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('shelves', fn ($shelves): bool => ! collect($shelves)
                    ->flatMap(fn (array $shelf): array => $shelf['documents'])
                    ->contains(fn (array $row): bool => $row['title'] === 'Day one results'))
                ->etc());
    });

    it('takes the file with the document when one is removed', function (): void {
        Storage::fake('local');

        $this->actingAs($this->editor)
            ->post(route('admin.documents.store'), [
                'title' => 'Withdrawn circular',
                'category' => 'general',
                'file' => UploadedFile::fake()->create('circular.pdf', 10, 'application/pdf'),
            ])->assertRedirect();

        $document = Document::where('title', 'Withdrawn circular')->firstOrFail();
        $path = $document->file_path;

        $this->actingAs($this->editor)
            ->delete(route('admin.documents.destroy', $document->slug))
            ->assertRedirect();

        Storage::disk('local')->assertMissing($path);
    });

    it('refuses a file the library is not for', function (): void {
        $this->actingAs($this->editor)
            ->post(route('admin.documents.store'), [
                'title' => 'Not a document',
                'category' => 'general',
                'file' => UploadedFile::fake()->create('script.php', 2),
            ])->assertSessionHasErrors('file');
    });
});
