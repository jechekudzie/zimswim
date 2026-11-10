<?php

namespace App\Console\Commands;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Support\Roles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Opens the first administrator account on a server.
 *
 * This exists because three things have to be true together and none of them
 * is obvious: the account must be verified, or every backend route bounces it
 * to the verify-email wall; the role must be assigned inside an organisation,
 * because roles here are team-scoped and one assigned without a team silently
 * grants nothing; and the account has to be attached to that organisation to
 * have anything to act in. A tinker one-liner gets one of the three wrong and
 * leaves somebody locked out of a server they just provisioned.
 */
class MakeAdministrator extends Command
{
    protected $signature = 'zimswim:make-admin
        {name? : The person\'s full name}
        {email? : The address they will sign in with}
        {--organization= : Slug of the body they act in, defaulting to the national one}
        {--invite : Email them a link to set their own password instead of printing one}';

    protected $description = 'Open a system administrator account';

    public function handle(): int
    {
        $name = $this->argument('name') ?? $this->ask('Full name');
        $email = $this->argument('email') ?? $this->ask('Email address');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $organization = $this->organization();

        if ($organization === null) {
            $this->error('No organisation to attach them to. Run the OrganizationSeeder first.');

            return self::FAILURE;
        }

        if (Role::where('name', Roles::SYSTEM_ADMINISTRATOR)->doesntExist()) {
            $this->error('The system administrator role does not exist. Run the RolesAndPermissionsSeeder first.');

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();
        $password = $existing === null ? Str::password(16) : null;

        $user = DB::transaction(function () use ($existing, $name, $email, $password, $organization): User {
            $user = $existing ?? User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'current_organization_id' => $organization->getKey(),
                'is_active' => true,
            ]);

            /*
             * `email_verified_at` is not fillable, so it has to be forced on
             * rather than passed to create() — where it would be dropped in
             * silence. Without it every backend route redirects to the
             * verify-email screen, and the first administrator on a server
             * has nobody to send them a verification link.
             */
            $user->forceFill([
                'is_active' => true,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'current_organization_id' => $user->current_organization_id ?? $organization->getKey(),
            ])->save();

            if (! $user->organizations()->whereKey($organization->getKey())->exists()) {
                $user->organizations()->attach($organization->getKey(), [
                    'is_default' => $user->organizations()->count() === 0,
                ]);
            }

            // Roles are scoped to an organisation. Assigning one without
            // setting the team first stores it against no team at all, which
            // grants nothing and is invisible until somebody is locked out.
            setPermissionsTeamId($organization->getKey());
            $user->assignRole(Roles::SYSTEM_ADMINISTRATOR);
            setPermissionsTeamId(null);

            return $user;
        });

        $this->newLine();
        $this->info($existing === null ? 'Account opened.' : 'Existing account promoted.');
        $this->table(['', ''], [
            ['Name', $user->name],
            ['Email', $user->email],
            ['Role', 'System administrator'],
            ['Organisation', $organization->name],
        ]);

        if ($this->option('invite')) {
            $user->notify(new AccountCreated($organization->name, 'System administrator'));
            $this->info('An invitation to set a password is on its way to '.$user->email.'.');

            return self::SUCCESS;
        }

        if ($password === null) {
            $this->line('That account already had a password, so it has been left alone.');
            $this->line('Use "Forgot your password?" on the sign-in page if it is not known.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('Password (shown once — store it in a password manager now):');
        $this->line('  '.$password);
        $this->newLine();
        $this->line('Change it after signing in, under Settings. Re-run with --invite to');
        $this->line('send a set-your-own-password link instead, once mail is configured.');

        return self::SUCCESS;
    }

    private function organization(): ?Organization
    {
        $slug = $this->option('organization');

        if (is_string($slug) && $slug !== '') {
            return Organization::where('slug', $slug)->first();
        }

        return Organization::where('type', OrganizationType::National)->first()
            ?? Organization::query()->orderBy('id')->first();
    }
}
