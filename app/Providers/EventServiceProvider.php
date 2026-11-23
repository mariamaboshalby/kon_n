<?php

namespace App\Providers;

use Modules\HR\Events\LeaveRequestApproved;
use Modules\HR\Events\LeaveRequestCancelled;
use Modules\HR\Events\LeaveRequestRejected;
use Modules\HR\Events\LeaveRequestSubmitted;
use App\Listeners\LogUserLogin;
use App\Listeners\LogUserLogout;
use App\Listeners\SendTelegramLoginNotification;
use Modules\HR\Listeners\UpdateLeaveBalanceOnApproved;
use Modules\HR\Listeners\UpdateLeaveBalanceOnCancelled;
use Modules\HR\Listeners\UpdateLeaveBalanceOnRejected;
use Modules\HR\Listeners\UpdateLeaveBalanceOnSubmitted;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            LogUserLogin::class,
            SendTelegramLoginNotification::class,
        ],
        Logout::class => [
            LogUserLogout::class,
        ],
        LeaveRequestSubmitted::class => [
            UpdateLeaveBalanceOnSubmitted::class,
        ],
        LeaveRequestApproved::class => [
            UpdateLeaveBalanceOnApproved::class,
        ],
        LeaveRequestRejected::class => [
            UpdateLeaveBalanceOnRejected::class,
        ],
        LeaveRequestCancelled::class => [
            UpdateLeaveBalanceOnCancelled::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
