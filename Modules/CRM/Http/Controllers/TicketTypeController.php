<?php

declare(strict_types=1);

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Models\TicketType;
use RealRashid\SweetAlert\Facades\Alert;

class TicketTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Ticket Types')->only(['index']);
        $this->middleware('can:create Ticket Types')->only(['create', 'store']);
        $this->middleware('can:edit Ticket Types')->only(['edit', 'update']);
        $this->middleware('can:delete Ticket Types')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $sortField = in_array($request->get('sort', 'id'), ['id', 'title', 'created_at']) ? $request->get('sort', 'id') : 'id';
        $sortDirection = in_array($request->get('direction', 'asc'), ['asc', 'desc']) ? $request->get('direction', 'asc') : 'asc';

        $ticketTypes = TicketType::query()->orderBy($sortField, $sortDirection)->paginate(20)->withQueryString();

        return view('crm::ticket-types.index', compact('ticketTypes', 'sortField', 'sortDirection'));
    }

    public function create()
    {
        return view('crm::ticket-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);
        TicketType::create($validated);
        Alert::toast(__('crm::crm.ticket_type_created_successfully'), 'success');
        return redirect()->route('ticket-types.index');
    }

    public function edit(int $id)
    {
        $ticketType = TicketType::findOrFail($id);
        return view('crm::ticket-types.edit', compact('ticketType'));
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);
        TicketType::findOrFail($id)->update($validated);
        Alert::toast(__('crm::crm.ticket_type_updated_successfully'), 'success');
        return redirect()->route('ticket-types.index');
    }

    public function destroy(int $id)
    {
        try {
            TicketType::findOrFail($id)->delete();
            Alert::toast(__('crm::crm.ticket_type_deleted_successfully'), 'success');
        } catch (\Exception) {
            Alert::toast(__('crm::crm.error_deleting_ticket_type'), 'error');
        }
        return redirect()->route('ticket-types.index');
    }
}