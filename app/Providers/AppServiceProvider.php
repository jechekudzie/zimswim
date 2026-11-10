<?php

namespace App\Providers;

use App\Contracts\SmsGateway;
use App\Models\User;
use App\Support\OrganizationContext;
use App\Support\Sms\LogSmsGateway;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(OrganizationContext::class);
        $this->registerSmsGateway();
    }

    /**
     * Resolve the SMS gateway named in config/sms.php, falling back to the log
     * gateway so a missing or mistyped driver never takes the site down.
     */
    protected function registerSmsGateway(): void
    {
        $this->app->singleton(SmsGateway::class, function (): SmsGateway {
            $driver = (string) config('sms.gateway', 'log');

            $gateway = config('sms.gateways.'.$driver);

            if (! is_string($gateway) || ! is_a($gateway, SmsGateway::class, true)) {
                return $this->app->make(LogSmsGateway::class);
            }

            return $this->app->make($gateway);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->registerDevCommands();
    }

    /**
     * Add the scheduler to the processes `php artisan dev` starts.
     *
     * Reverb, the queue worker and Vite are registered by their own packages.
     * The scheduler is what sends the entry-deadline, renewal and accreditation
     * notices, so without it a developer never sees them go out.
     */
    protected function registerDevCommands(): void
    {
        if (class_exists(DevCommands::class)) {
            DevCommands::artisan('schedule:work', 'schedule');
        }
    }

    /**
     * Grant abilities from the roles a user holds at the organisation they are
     * acting in and at every organisation above it, so national roles keep
     * working while a board member looks at one province.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return app(OrganizationContext::class)->permits($user, $ability) ? true : null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
