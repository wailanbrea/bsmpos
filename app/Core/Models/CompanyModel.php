<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Base model for operational records owned by the selected company.
 *
 * Models extending this class fail closed when a company context is absent.
 */
abstract class CompanyModel extends Model
{
    use BelongsToCompany;
}
