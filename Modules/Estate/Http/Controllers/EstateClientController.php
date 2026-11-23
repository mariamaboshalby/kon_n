<?php

declare(strict_types=1);

namespace Modules\Estate\Http\Controllers;

use App\Models\Client;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\CRM\Models\ClientContact;
use Modules\CRM\Models\Lead;
use Modules\Estate\Models\PropertyStakeholder;

class EstateClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Properties');
    }

    /** IDs of clients who have at least one property */
    private function estateClientIds(): array
    {
        return PropertyStakeholder::where('stakeholder_type', Client::class)
            ->distinct()
            ->pluck('stakeholder_id')
            ->toArray();
    }

    /** Clients with properties */
    public function index(): View
    {
        $clientIds = $this->estateClientIds();

        $clients = Client::whereIn('id', $clientIds)
            ->withCount([
                'properties as owned_count'  => fn ($q) => $q->where('property_stakeholders.role', 'owner'),
                'properties as rented_count' => fn ($q) => $q->where('property_stakeholders.role', 'tenant'),
                'properties as bought_count' => fn ($q) => $q->where('property_stakeholders.role', 'buyer'),
            ])
            ->orderBy('cname')
            ->paginate(20);

        return view('estate::estate-clients.index', compact('clients'));
    }

    /** Single client with properties, contacts, leads */
    public function show(Client $client): View
    {
        $properties = $client->properties()
            ->with(['propertyType'])
            ->withPivot(['role', 'is_primary'])
            ->latest()
            ->get();

        $contacts = $client->contacts()->latest()->get();
        $leads    = $client->leads()->with(['status', 'assignedTo'])->latest()->get();

        return view('estate::estate-clients.show', compact('client', 'properties', 'contacts', 'leads'));
    }

    /** Contacts of estate clients only */
    public function contacts(): View
    {
        $clientIds = $this->estateClientIds();

        $contacts = ClientContact::whereIn('client_id', $clientIds)
            ->with('client')
            ->latest()
            ->paginate(20);

        return view('estate::estate-clients.contacts', compact('contacts'));
    }

    /** Leads of estate clients only */
    public function leads(): View
    {
        $clientIds = $this->estateClientIds();

        $leads = Lead::whereIn('client_id', $clientIds)
            ->with(['client', 'status', 'assignedTo'])
            ->latest()
            ->paginate(20);

        return view('estate::estate-clients.leads', compact('leads'));
    }

    /** Show create client form (Estate context) */
    public function createClient(): View
    {
        $clientTypes = \Modules\CRM\Models\ClientType::all();
        $categories  = \Modules\CRM\Models\ClientCategory::all();

        return view('estate::estate-clients.create-client', compact('clientTypes', 'categories'));
    }

    /** Store new client and redirect back to Estate */
    public function storeClient(\App\Http\Requests\ClientRequest $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['created_by'] = auth()->id();

        Client::create($data);

        return redirect()
            ->route('estate.clients.index')
            ->with('message', __('estate::estate.client_created_successfully'));
    }

    /** Redirect to CRM contact create with client pre-selected */
    public function createContact(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('client-contacts.create', ['client_id' => $request->query('client_id')]);
    }
}
