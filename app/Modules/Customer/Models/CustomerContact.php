<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;

final class CustomerContact extends Model
{
    protected $fillable = [
        'customer_id',
        'name',
        'role',
        'phone',
        'email',
    ];
}
