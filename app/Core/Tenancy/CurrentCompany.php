<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use App\Modules\Company\Models\Branch;
use App\Modules\Company\Models\Company;
use LogicException;

final class CurrentCompany
{
    private ?Company $company = null;

    private ?Branch $branch = null;

    public function setCompany(Company $company): void
    {
        $this->company = $company;
        $this->branch = null;
    }

    public function setBranch(Branch $branch): void
    {
        if ($this->company === null || $branch->company_id !== $this->company->getKey()) {
            throw new LogicException('La sucursal no pertenece a la compañía actual.');
        }

        $this->branch = $branch;
    }

    public function hasCompany(): bool
    {
        return $this->company !== null;
    }

    public function hasBranch(): bool
    {
        return $this->branch !== null;
    }

    public function company(): Company
    {
        return $this->company ?? throw new LogicException('No se ha establecido la compañía actual.');
    }

    public function branch(): Branch
    {
        return $this->branch ?? throw new LogicException('No se ha establecido la sucursal actual.');
    }
}
