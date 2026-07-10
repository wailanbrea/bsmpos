<?php

declare(strict_types=1);

namespace App\Modules\Product\Policies;

use App\Models\User;
use App\Modules\Product\Models\Product;

final class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->hasCompanyPermission($product->company_id, 'products.view');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasCompanyPermission($product->company_id, 'products.manage');
    }
}
