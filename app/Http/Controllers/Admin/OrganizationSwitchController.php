<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
        ]);

        $user = $request->user();
        $organization = Organization::query()->findOrFail((int) $validated['organization_id']);

        abort_unless(
            $user->reachableOrganizations()->contains('id', $organization->getKey()),
            403,
            'You do not have access to that organisation.',
        );

        $request->session()->put('organization_id', $organization->getKey());
        $user->forceFill(['current_organization_id' => $organization->getKey()])->save();

        return back()->with('success', 'Now working in '.$organization->name.'.');
    }
}
