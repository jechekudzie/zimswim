<?php

namespace App\Http\Requests\Admin;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MemberRequest extends FormRequest
{
    /**
     * The swimmer being edited, or null when one is being registered.
     */
    public function member(): ?Member
    {
        $member = $this->route('member');

        return $member instanceof Member ? $member : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $scopeIds = app(OrganizationContext::class)->scopeIds();
        $member = $this->member();
        $memberId = $member?->getKey();

        return [
            'organization_id' => ['required', 'integer', Rule::in($scopeIds)],
            'province_organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'membership_number' => [
                'required', 'string', 'max:24',
                Rule::unique('members', 'membership_number')->ignore($memberId),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'status' => ['required', Rule::enum(MemberStatus::class)],
            'school' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'medical_notes' => ['nullable', 'string', 'max:2000'],
            'photo_consent' => ['boolean'],
            'consent_given' => ['boolean'],
            'consent_given_by' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * A swimmer under 18 cannot be registered without a recorded guardian
     * consent, as the Cyber and Data Protection Act requires.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $dateOfBirth = $this->date('date_of_birth');

                if ($dateOfBirth === null || $dateOfBirth->age >= 18) {
                    return;
                }

                if (! $this->boolean('consent_given')) {
                    $validator->errors()->add(
                        'consent_given',
                        'A parent or guardian must consent before a swimmer under 18 can be registered.',
                    );
                }

                if (blank($this->input('consent_given_by'))) {
                    $validator->errors()->add(
                        'consent_given_by',
                        'Record the name of the parent or guardian giving consent.',
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organization_id.in' => 'You can only register swimmers in a club you administer.',
        ];
    }
}
