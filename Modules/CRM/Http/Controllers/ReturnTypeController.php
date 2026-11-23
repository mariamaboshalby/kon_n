<?php

declare(strict_types=1);

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\ReturnType;
use RealRashid\SweetAlert\Facades\Alert;

class ReturnTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Return Types')->only(['index']);
        $this->middleware('can:create Return Types')->only(['create', 'store']);
        $this->middleware('can:edit Return Types')->only(['edit', 'update']);
        $this->middleware('can:delete Return Types')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $sortField = in_array($request->get('sort', 'id'), ['id', 'title', 'created_at']) ? $request->get('sort', 'id') : 'id';
        $sortDirection = in_array($request->get('direction', 'asc'), ['asc', 'desc']) ? $request->get('direction', 'asc') : 'asc';

        $returnTypes = ReturnType::query()->orderBy($sortField, $sortDirection)->paginate(20)->withQueryString();

        return view('crm::return-types.index', compact('returnTypes', 'sortField', 'sortDirection'));
    }

    public function create()
    {
        return view('crm::return-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);
        ReturnType::create($validated);
        Alert::toast(__('crm::crm.return_type_created_successfully'), 'success');
        return redirect()->route('return-types.index');
    }

    public function edit(int $id)
    {
        $returnType = ReturnType::findOrFail($id);
        return view('crm::return-types.edit', compact('returnType'));
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);
        ReturnType::findOrFail($id)->update($validated);
        Alert::toast(__('crm::crm.return_type_updated_successfully'), 'success');
        return redirect()->route('return-types.index');
    }

    public function destroy(int $id)
    {
        try {
            ReturnType::findOrFail($id)->delete();
            Alert::toast(__('crm::crm.return_type_deleted_successfully'), 'success');
        } catch (\Exception) {
            Alert::toast(__('crm::crm.error_deleting_return_type'), 'error');
        }
        return redirect()->route('return-types.index');
    }
}