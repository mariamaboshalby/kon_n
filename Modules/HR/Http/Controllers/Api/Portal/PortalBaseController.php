<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers\Api\Portal;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeDevice;
use Modules\HR\Models\EmployeePortalToken;

abstract class PortalBaseController extends Controller
{
    protected function employee(Request $request): Employee
    {
        /** @var Employee $employee */
        $employee = $request->attributes->get('portal_employee');

        return $employee;
    }

    protected function device(Request $request): ?EmployeeDevice
    {
        return $request->attributes->get('portal_device');
    }

    protected function token(Request $request): ?EmployeePortalToken
    {
        return $request->attributes->get('portal_token');
    }
}
