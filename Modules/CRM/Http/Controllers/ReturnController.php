<?php

namespace Modules\CRM\Http\Controllers;

use App\Models\{Client, Item, User};
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\{DB, Auth, Notification};
use Modules\CRM\Http\Requests\StoreReturnRequest;
use Modules\CRM\Models\{ReturnItem, ReturnOrder, ReturnType};
use Modules\Notifications\Notifications\GeneralNotification;

class ReturnController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Returns')->only(['index', 'show', 'downloadAttachment']);
        $this->middleware('can:create Returns')->only(['create', 'store']);
        $this->middleware('can:edit Returns')->only(['edit', 'update', 'approve', 'reject', 'deleteAttachment']);
        $this->middleware('can:delete Returns')->only(['destroy']);
    }

    public function index()
    {
        $returns = ReturnOrder::with(['client', 'createdBy', 'approvedBy', 'items'])
            ->latest()
            ->get();

        return view('crm::returns.index', compact('returns'));
    }

    public function create()
    {
        $branches = userBranches();
        $clients = Client::all();
        $items = Item::all();
        $users = User::all();
        $returnTypes = ReturnType::orderBy('title')->get();

        return view('crm::returns.create', compact('clients', 'items', 'branches', 'returnTypes', 'users'));
    }

    public function store(StoreReturnRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $itemsData = $validated['items'];
            unset($validated['items']);

            // Remove attachment from validated data (will be handled by Media Library)
            unset($validated['attachment']);

            $validated['created_by'] = Auth::id();
            $validated['status'] = 'pending';

            $returnOrder = ReturnOrder::create($validated);

            // Handle attachment upload using Media Library
            if ($request->hasFile('attachment')) {
                try {
                    $returnOrder->addMediaFromRequest('attachment')
                        ->toMediaCollection('return-attachments');
                } catch (\Exception $e) {
                    \Log::error('Error uploading attachment: ' . $e->getMessage());
                }
            }

            // Handle multiple images if provided
            if ($request->hasFile('images')) {
                $images = is_array($request->file('images')) ? $request->file('images') : [$request->file('images')];
                foreach ($images as $image) {
                    try {
                        $returnOrder->addMedia($image)
                            ->toMediaCollection('return-images');
                    } catch (\Exception $e) {
                        \Log::error('Error uploading image: ' . $e->getMessage());
                    }
                }
                \Log::info('Images uploaded successfully for return order: ' . $returnOrder->id);
            }

            foreach ($itemsData as $item) {
                ReturnItem::create([
                    'return_id'      => $returnOrder->id,
                    'item_id'        => $item['item_id'],
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $item['unit_price'],
                    'item_condition' => $item['item_condition'] ?? null,
                    'notes'          => $item['notes'] ?? null,
                ]);
            }

            $returnOrder->calculateTotal();
            $returnOrder->update(['refund_amount' => $returnOrder->total_amount]);

            DB::commit();

            // Notify assigned user
            if ($request->boolean('notify_assigned') && $returnOrder->assigned_to) {
                $assignedUser = User::find($returnOrder->assigned_to);
                if ($assignedUser) {
                    $assignedUser->notify(new GeneralNotification(
                        title: __('crm::crm.new_return_assigned'),
                        message: __('crm::crm.return') . ': ' . $returnOrder->return_number,
                        url: route('returns.show', $returnOrder),
                        type: 'info',
                        icon: 'las la-undo'
                    ));
                }
            }

            // Notify all users
            if ($request->boolean('notify_all_users')) {
                $users = User::where('id', '!=', Auth::id())->get();
                Notification::send($users, new GeneralNotification(
                    title: __('crm::crm.new_return_created'),
                    message: __('crm::crm.return') . ': ' . $returnOrder->return_number,
                    url: route('returns.show', $returnOrder),
                    type: 'info',
                    icon: 'las la-undo'
                ));
            }

            return redirect()->route('returns.index')->with('message', __('crm::crm.return_created_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['error' => __('Error: ') . $e->getMessage()]);
        }
    }


    public function show(ReturnOrder $return)
    {
        try {
            $return->load(['client', 'createdBy', 'approvedBy', 'items.item', 'branch']);
            return view('crm::returns.show', compact('return'));
        } catch (\Exception $e) {
            \Log::error('Error showing return: ' . $e->getMessage());
            return redirect()->route('returns.index')->with('error', __('crm::crm.error_loading_return'));
        }
    }

    public function edit(ReturnOrder $return)
    {
        $items = Item::all();
        $users = User::all();
        $branches = userBranches();
        $returnTypes = ReturnType::orderBy('title')->get();
        $return->load('items.item', 'client');

        return view('crm::returns.edit', compact('return', 'items', 'branches', 'returnTypes', 'users'));
    }

    public function update(StoreReturnRequest $request, ReturnOrder $return)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $itemsData = $validated['items'];
            unset($validated['items']);
            unset($validated['attachment']); // Will be handled by Media Library

            $updateData = [
                'client_id'               => $validated['client_id'],
                'return_date'             => $validated['return_date'],
                'return_type'             => $validated['return_type'],
                'original_invoice_number' => $validated['original_invoice_number'],
                'original_invoice_date'   => $validated['original_invoice_date'],
                'branch_id'               => $validated['branch_id'],
                'reason'                  => $validated['reason'],
                'notes'                   => $validated['notes'],
            ];
            
            // Allow status update if provided
            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
            }

            // Handle attachment replacement using Media Library
            if ($request->hasFile('attachment')) {
                try {
                    // Clear old attachments
                    $return->clearMediaCollection('return-attachments');
                    
                    // Add new attachment
                    $return->addMediaFromRequest('attachment')
                        ->toMediaCollection('return-attachments');
                } catch (\Exception $e) {
                    \Log::error('Error uploading attachment: ' . $e->getMessage());
                }
            }

            // Handle multiple images
            if ($request->hasFile('images')) {
                $images = is_array($request->file('images')) ? $request->file('images') : [$request->file('images')];
                foreach ($images as $image) {
                    try {
                        $return->addMedia($image)
                            ->toMediaCollection('return-images');
                    } catch (\Exception $e) {
                        \Log::error('Error uploading image: ' . $e->getMessage());
                    }
                }
                \Log::info('Images updated successfully for return order: ' . $return->id);
            }

            $return->update($updateData);

            $return->items()->delete();

            foreach ($itemsData as $item) {
                ReturnItem::create([
                    'return_id'      => $return->id,
                    'item_id'        => $item['item_id'],
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $item['unit_price'],
                    'item_condition' => $item['item_condition'] ?? null,
                    'notes'          => $item['notes'] ?? null,
                ]);
            }

            $return->calculateTotal();
            $return->update(['refund_amount' => $return->total_amount]);

            DB::commit();

            // Notify assigned user
            if ($request->boolean('notify_assigned') && $return->assigned_to) {
                $assignedUser = User::find($return->assigned_to);
                if ($assignedUser) {
                    $assignedUser->notify(new GeneralNotification(
                        title: __('crm::crm.new_return_assigned'),
                        message: __('crm::crm.return') . ': ' . $return->return_number,
                        url: route('returns.show', $return),
                        type: 'info',
                        icon: 'las la-undo'
                    ));
                }
            }

            // Notify all users
            if ($request->boolean('notify_all_users')) {
                $users = User::where('id', '!=', Auth::id())->get();
                Notification::send($users, new GeneralNotification(
                    title: __('crm::crm.new_return_created'),
                    message: __('crm::crm.return') . ': ' . $return->return_number,
                    url: route('returns.show', $return),
                    type: 'info',
                    icon: 'las la-undo'
                ));
            }

            return redirect()->route('returns.show', $return->id)->with('message', __('crm::crm.return_updated_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['error' => __('Error: ') . $e->getMessage()]);
        }
    }

    public function approve(ReturnOrder $return)
    {
        $return->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        return redirect()->route('returns.show', $return)->with('message', __('crm::crm.return_approved_successfully'));
    }

    public function reject(ReturnOrder $return)
    {
        $return->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
        ]);

        return redirect()->route('returns.show', $return)->with('message', __('crm::crm.return_rejected_successfully'));
    }

    public function destroy(ReturnOrder $return)
    {
        if ($return->status != 'pending') {
            return redirect()->back()->with('error', __('crm::crm.cannot_delete_processed_returns'));
        }

        $return->delete();
        return redirect()->route('returns.index')->with('message', __('crm::crm.return_deleted_successfully'));
    }

    public function downloadAttachment(ReturnOrder $returnOrder, $mediaId = null)
    {
        try {
            if ($mediaId) {
                // Download specific media item
                $media = $returnOrder->getMedia('return-attachments')->where('id', $mediaId)->first()
                    ?? $returnOrder->getMedia('return-images')->where('id', $mediaId)->first();
                
                if (!$media) {
                    return redirect()->back()->with('error', __('crm::crm.attachment_not_found'));
                }
                
                return response()->download($media->getPath(), $media->file_name);
            }
            
            // Download first attachment if no specific ID
            $media = $returnOrder->getFirstMedia('return-attachments');
            
            if (!$media) {
                return redirect()->back()->with('error', __('crm::crm.no_attachment_found'));
            }
            
            return response()->download($media->getPath(), $media->file_name);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('crm::crm.error_downloading_file'));
        }
    }

    public function deleteAttachment($mediaId)
    {
        try {
            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($mediaId);
            
            if (!$media) {
                return response()->json(['success' => false, 'error' => __('crm::crm.attachment_not_found')], 404);
            }
            
            $media->delete();
            return response()->json(['success' => true, 'message' => __('crm::crm.attachment_deleted_successfully')]);
        } catch (\Exception $e) {
            \Log::error('Error deleting attachment: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => __('crm::crm.error_deleting_attachment')], 500);
        }
    }
}
