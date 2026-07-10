<?php

namespace App\Providers;

use App\Core\Models\AuditLog;
use App\Core\Tenancy\CurrentCompany;
use App\Modules\Access\Models\Role;
use App\Modules\Access\Policies\RolePolicy;
use App\Modules\Audit\Policies\AuditLogPolicy;
use App\Modules\Company\Models\Branch;
use App\Modules\Company\Models\Company;
use App\Modules\Company\Policies\BranchPolicy;
use App\Modules\Company\Policies\CompanyPolicy;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Policies\CustomerPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentCompany::class, fn (): CurrentCompany => new CurrentCompany);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));

        RateLimiter::for('registration', fn (Request $request): Limit => Limit::perHour(10)
            ->by($request->ip()));
    }
}
