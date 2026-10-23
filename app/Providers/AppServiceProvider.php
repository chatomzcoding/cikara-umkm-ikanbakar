<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\ErrorPageSetting;
use App\Models\Role;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\ErrorPageSettingPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
        $this->app->singleton(AuditLogger::class);
        require_once app_path('Helpers/main.php');
        require_once app_path('Helpers/data.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerGeneratedPermissions();
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureAuditEvents();
    }

    protected function configureAuditEvents(): void
    {
        Event::listen(Login::class, fn (Login $event) => app(AuditLogger::class)->record(
            event: 'login',
            description: 'Login berhasil',
            subject: $event->user,
            actor: $event->user,
        ));

        Event::listen(Logout::class, fn (Logout $event) => app(AuditLogger::class)->record(
            event: 'logout',
            description: 'Logout',
            subject: $event->user,
            actor: $event->user,
        ));

        Event::listen(Failed::class, fn (Failed $event) => app(AuditLogger::class)->record(
            event: 'login_failed',
            description: 'Percobaan login gagal',
            subject: $event->user,
            metadata: ['login' => $event->credentials['email'] ?? $event->credentials['username'] ?? null],
            actor: $event->user,
        ));
    }

    protected function registerGeneratedPermissions(): void
    {
        $paths = [
            ...(glob(config_path('permissions/*.php')) ?: []),
            ...(glob(base_path('Modules/*/config/permissions/*.php')) ?: []),
        ];
        $groups = config('permissions.groups', []);

        foreach ($paths as $path) {
            $groups = array_replace($groups, require $path);
        }

        config(['permissions.groups' => $groups]);
    }

    protected function configureAuthorization(): void
    {
        Gate::before(fn (User $user): ?bool => $user->hasRole('superadmin') ? true : null);

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(ErrorPageSetting::class, ErrorPageSettingPolicy::class);
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
