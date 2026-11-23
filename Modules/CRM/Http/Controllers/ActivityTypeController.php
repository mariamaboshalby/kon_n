<?php

declare(strict_types=1);

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\ActivityType;
use RealRashid\SweetAlert\Facades\Alert;

class ActivityTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Activity Types')->only(['index']);
        $this->middleware('can:create Activity Types')->only(['create', 'store']);
        $this->middleware('can:edit Activity Types')->only(['edit', 'update']);
        $this->middleware('can:delete Activity Types')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $sortField = in_array($request->get('sort', 'id'), ['id', 'title', 'created_at']) ? $request->get('sort', 'id') : 'id';
        $sortDirection = in_array($request->get('direction', 'asc'), ['asc', 'desc']) ? $request->get('direction', 'asc') : 'asc';

        $activityTypes = ActivityType::query()->orderBy($sortField, $sortDirection)->paginate(20)->withQueryString();

        return view('crm::activity-types.index', compact('activityTypes', 'sortField', 'sortDirection'));
    }

    public function create()
    {
        return view('crm::activity-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);
        ActivityType::create($validated);
        Alert::toast(__('crm::crm.activity_type_created_successfully'), 'success');
        return redirect()->route('activity-types.index');
    }

    public function edit(int $id)
    {
        $activityType = ActivityType::findOrFail($id);
        return view('crm::activity-types.edit', compact('activityType'));
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);
        ActivityType::findOrFail($id)->update($validated);
        Alert::toast(__('crm::crm.activity_type_updated_successfully'), 'success');
        return redirect()->route('activity-types.index');
    }

    public function destroy(int $id)
    {
        try {
            ActivityType::findOrFail($id)->delete();
            Alert::toast(__('crm::crm.activity_type_deleted_successfully'), 'success');
        } catch (\Exception) {
            Alert::toast(__('crm::crm.error_deleting_activity_type'), 'error');
        }
        return redirect()->route('activity-types.index');
    }
}