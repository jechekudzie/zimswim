<?php

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Season;
use App\Support\OrganizationContext;
use App\Support\Roles;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    seedRoles();
    Season::factory()->create();
    [$this->national, $this->province, $this->club] = organizationTree();
});

/**
 * Read a streamed download back as rows.
 *
 * @return array<int, array<int, string>>
 */
function csvRows(TestResponse $response): array
{
    $body = ltrim($response->streamedContent(), "\u{FEFF}");
    $handle = fopen('php://memory', 'r+');
    fwrite($handle, $body);
    rewind($handle);

    $rows = [];

    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
        $rows[] = $row;
    }

    fclose($handle);

    return $rows;
}

it('exports the swimmer register as a csv', function (): void {
    Member::factory()->create([
        'organization_id' => $this->club->getKey(),
        'first_name' => 'Rutendo',
        'last_name' => 'Moyo',
        'status' => MemberStatus::Active,
    ]);

    $user = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $response = $this->actingAs($user)->get(route('admin.exports.members'));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload();

    $rows = csvRows($response);

    expect($rows[0])->toContain('Membership number', 'Last name', 'Club')
        ->and($rows)->toHaveCount(2)
        ->and($rows[1])->toContain('Moyo', 'Rutendo');
});

it('exports only the rows the active filter leaves on the page', function (): void {
    Member::factory()->create([
        'organization_id' => $this->club->getKey(),
        'last_name' => 'Active',
        'status' => MemberStatus::Active,
    ]);

    Member::factory()->create([
        'organization_id' => $this->club->getKey(),
        'last_name' => 'Lapsed',
        'status' => MemberStatus::Lapsed,
    ]);

    $user = userWithRole(Roles::BOARD_MEMBER, $this->national);

    $rows = csvRows(
        $this->actingAs($user)->get(route('admin.exports.members', ['status' => 'lapsed'])),
    );

    expect($rows)->toHaveCount(2)
        ->and($rows[1])->toContain('Lapsed');
});

it('keeps an export inside the organisation the user is acting in', function (): void {
    $otherProvince = Organization::factory()->province($this->national)->create();
    $otherClub = Organization::factory()->club($otherProvince)->create();

    Member::factory()->create([
        'organization_id' => $this->club->getKey(),
        'last_name' => 'Inscope',
    ]);

    Member::factory()->create([
        'organization_id' => $otherClub->getKey(),
        'last_name' => 'Elsewhere',
    ]);

    $user = userWithRole(Roles::CLUB_ADMINISTRATOR, $this->club);
    app(OrganizationContext::class)->set($this->club);

    $rows = csvRows($this->actingAs($user)->get(route('admin.exports.members')));

    expect($rows)->toHaveCount(2)
        ->and($rows[1])->toContain('Inscope')
        ->and(collect($rows)->flatten())->not->toContain('Elsewhere');
});

it('refuses an export to someone who may not see the listing', function (): void {
    $user = userWithRole(Roles::SWIMMER, $this->club);

    $this->actingAs($user)
        ->get(route('admin.exports.users'))
        ->assertForbidden();
});
