<?php

declare(strict_types=1);

namespace Modules\Fleet\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Fleet\Http\Requests\DriverRequest;
use Modules\Shipping\Models\Driver;
use RealRashid\SweetAlert\Facades\Alert;

class DriverController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view Fleet Drivers')->only(['index', 'show']);
        $this->middleware('permission:create Fleet Drivers')->only(['create', 'store']);
        $this->middleware('permission:edit Fleet Drivers')->only(['edit', 'update']);
        $this->middleware('permission:delete Fleet Drivers')->only(['destroy']);
    }

    public function index()
    {
        $drivers = Driver::withoutGlobalScopes()
            ->with(['branch'])
            ->latest()
            ->paginate(15);

        return view('fleet::drivers.index', compact('drivers'));
    }

    public function create()
    {
        $branches = userBranches();

        return view('fleet::drivers.create', compact('branches'));
    }

    public function store(DriverRequest $request)
    {
        try {
            Driver::create($request->validated());
            Alert::toast(__('messages.created_successfully'), 'success');

            return redirect()->route('fleet.drivers.index');
        } catch (\Exception $e) {
            Alert::toast(__('messages.error_occurred'), 'error');

            return redirect()->back();
        }
    }

    public function show(int $id)
    {
        $driver = Driver::withoutGlobalScopes()
            ->with([
                'branch',
                'vehicles.vehicleType',
                'trips' => fn ($q) => $q->with('vehicle')->latest()->limit(10),
            ])
            ->findOrFail($id);

        return view('fleet::drivers.show', compact('driver'));
    }

    public function edit(int $id)
    {
        $driver   = Driver::withoutGlobalScopes()->findOrFail($id);
        $branches = userBranches();

        return view('fleet::drivers.edit', compact('driver', 'branches'));
    }

    public function update(DriverRequest $request, int $id)
    {
        try {
            $driver = Driver::withoutGlobalScopes()->findOrFail($id);
            $driver->update($request->validated());
            Alert::toast(__('messages.updated_successfully'), 'success');

            return redirect()->route('fleet.drivers.index');
        } catch (\Exception $e) {
            Alert::toast(__('messages.error_occurred'), 'error');

            return redirect()->back();
        }
    }

    public function destroy(int $id)
    {
        try {
            $driver = Driver::withoutGlobalScopes()->findOrFail($id);

            if ($driver->vehicles()->exists()) {
                Alert::toast(__('fleet::fleet.cannot_delete_driver_with_vehicles'), 'error');

                return redirect()->route('fleet.drivers.index');
            }

            if ($driver->trips()->exists()) {
                Alert::toast(__('fleet::fleet.cannot_delete_driver_with_trips'), 'error');

                return redirect()->route('fleet.drivers.index');
            }

            $driver->delete();
            Alert::toast(__('messages.deleted_successfully'), 'success');
        } catch (\Exception $e) {
            Alert::toast(__('messages.error_occurred'), 'error');
        }

        return redirect()->route('fleet.drivers.index');
    }
}
