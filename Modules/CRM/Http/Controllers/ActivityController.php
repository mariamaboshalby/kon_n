<?php

namespace Modules\CRM\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Modules\CRM\Models\Activity;
use Modules\CRM\Models\ActivityType;
use Modules\Notifications\Notifications\GeneralNotification;
use RealRashid\SweetAlert\Facades\Alert;
use Modules\CRM\Http\Requests\ActivityRequest;

class ActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view Activities')->only(['index', 'show']);
        $this->middleware('permission:create Activities')->only(['create', 'store']);
        $this->middleware('permission:edit Activities')->only(['edit', 'update']);
        $this->middleware('permission:delete Activities')->only(['destroy']);
    }

    public function index()
    {
        $activities = Activity::with(['client', 'assignedUser'])->latest()->paginate(20);
        return view('crm::activities.index', compact('activities'));
    }

    public function create()
    {
        $branches = userBranches();
        $users = User::pluck('name', 'id');
        $activityTypes = ActivityType::orderBy('title')->get();
        return view('crm::activities.create', compact('users', 'branches', 'activityTypes'));
    }

    public function store(ActivityRequest $request)
    {
        try {
            $activity = Activity::create($request->validated());

            if ($request->boolean('notify_assigned') && $activity->assigned_to) {
                $assignedUser = User::find($activity->assigned_to);
                if ($assignedUser) {
                    $assignedUser->notify(new GeneralNotification(
                        title: __('crm::crm.new_activity_assigned'),
                        message: $activity->title ?? __('crm::crm.activities'),
                        url: route('activities.index'),
                        type: 'info',
                        icon: 'las la-calendar-check'
                    ));
                }
            }

            if ($request->boolean('notify_all_users')) {
                $users = User::where('id', '!=', Auth::id())->get();
                Notification::send($users, new GeneralNotification(
                    title: __('crm::crm.new_activity_created'),
                    message: $activity->title ?? __('crm::crm.activities'),
                    url: route('activities.index'),
                    type: 'info',
                    icon: 'las la-calendar-check'
                ));
            }

            Alert::toast(__('crm::crm.activity_added_successfully'), 'success');
            return redirect()->route('activities.index');
        } catch (\Exception) {
            Alert::toast(__('crm::crm.error_processing_data'), 'error');
            return redirect()->back()->withInput();
        }
    }

    public function show($id)
    {
        // $activity = Activity::findOrFail($id);
        // return view('crm::activities.show', compact('activity'));
    }

    public function edit(Activity $activity)
    {
        $users = User::pluck('name', 'id');
        $activityTypes = ActivityType::orderBy('title')->get();
        return view('crm::activities.edit', compact('activity', 'users', 'activityTypes'));
    }

    public function update(ActivityRequest $request, Activity $activity)
    {
        try {
            $activity->update($request->validated());

            if ($request->boolean('notify_assigned') && $activity->assigned_to) {
                $assignedUser = User::find($activity->assigned_to);
                if ($assignedUser) {
                    $assignedUser->notify(new GeneralNotification(
                        title: __('crm::crm.new_activity_assigned'),
                        message: $activity->title ?? __('crm::crm.activities'),
                        url: route('activities.index'),
                        type: 'info',
                        icon: 'las la-calendar-check'
                    ));
                }
            }

            if ($request->boolean('notify_all_users')) {
                $users = User::where('id', '!=', Auth::id())->get();
                Notification::send($users, new GeneralNotification(
                    title: __('crm::crm.new_activity_created'),
                    message: $activity->title ?? __('crm::crm.activities'),
                    url: route('activities.index'),
                    type: 'info',
                    icon: 'las la-calendar-check'
                ));
            }

            Alert::toast(__('crm::crm.activity_updated_successfully'), 'success');
            return redirect()->route('activities.index');
        } catch (\Exception) {
            Alert::toast(__('crm::crm.error_processing_data'), 'error');
            return redirect()->back()->withInput();
        }
    }

    public function destroy(Activity $activity)
    {
        try {
            $activity->delete();
            Alert::toast(__('crm::crm.activity_deleted_successfully'), 'success');
        } catch (\Exception) {
            Alert::toast(__('crm::crm.error_deleting_activity'), 'error');
        }
        return redirect()->route('activities.index');
    }
}
