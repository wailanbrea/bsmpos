<?php

declare(strict_types=1);

namespace App\Core\Enums;

enum ErrorCode: string
{
    case ValidationFailed = 'VALIDATION_FAILED';
    case Unauthorized = 'UNAUTHORIZED';
    case Forbidden = 'FORBIDDEN';
    case NotFound = 'NOT_FOUND';
    case ModuleDisabled = 'MODULE_DISABLED';
    case CompanyContextRequired = 'COMPANY_CONTEXT_REQUIRED';
    case BranchContextRequired = 'BRANCH_CONTEXT_REQUIRED';
    case TenantAccessDenied = 'TENANT_ACCESS_DENIED';
    case CompanyInactive = 'COMPANY_INACTIVE';
    case BranchInactive = 'BRANCH_INACTIVE';
    case PermissionDenied = 'PERMISSION_DENIED';
    case InvalidCredentials = 'INVALID_CREDENTIALS';
    case AccountInactive = 'ACCOUNT_INACTIVE';
    case Conflict = 'CONFLICT';
    case InternalError = 'INTERNAL_ERROR';
}
