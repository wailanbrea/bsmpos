<?php

declare(strict_types=1);

namespace App\Core\Concerns;

use App\Core\Tenancy\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            $currentCompany = app(CurrentCompany::class);

            if (! $currentCompany->hasCompany()) {
                $builder->whereRaw('1 = 0');

                return;
            }

            /** @var Model $model */
            $model = $builder->getModel();
            $builder->where($model->qualifyColumn('company_id'), $currentCompany->company()->getKey());
        });
    }
}
