<?php

use App\Models\Organization;

it('turns a contact number into the form wa.me expects', function (string $written, string $dialled): void {
    expect((new Organization(['phone' => $written]))->whatsappNumber())->toBe($dialled);
})->with([
    'local, spaced' => ['0772 123 456', '263772123456'],
    'local, run together' => ['0772123456', '263772123456'],
    'international with a plus' => ['+263 772 123 456', '263772123456'],
    'international in brackets' => ['(+263) 772-123-456', '263772123456'],
    'a Harare landline' => ['024 2700 000', '263242700000'],
]);

it('has no number to offer when none is on record', function (?string $phone): void {
    expect((new Organization(['phone' => $phone]))->whatsappNumber())->toBeNull();
})->with([
    'nothing stored' => [null],
    'an empty string' => [''],
    'punctuation only' => ['---'],
    'too short to dial' => ['12345'],
]);

it('shares the national body\'s contact details with every public page', function (): void {
    [$national] = organizationTree();
    $national->forceFill(['phone' => '0772 123 456'])->save();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('federation.name', $national->name)
            ->where('federation.whatsapp', '263772123456'));
});

it('offers no WhatsApp number when the national body has not given one', function (): void {
    organizationTree();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('federation.whatsapp', null));
});
