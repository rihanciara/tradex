<?php

namespace Modules\AdvancedReports\Http\Controllers;

use App\Product;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use Illuminate\Http\Request;
use App\Utils\TransactionUtil;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Modules\AdvancedReports\Entities\AwardCatalog;

class AwardCatalogController extends Controller
{
    protected $transactionUtil;
    protected $moduleUtil;
    protected $businessUtil;

    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, BusinessUtil $businessUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display award catalog management page
     */
    public function index()
    {
        if (!auth()->user()->can('customer_recognition.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get products for linking (optional)
        $products = Product::where('business_id', $business_id)
            ->where('type', '!=', 'modifier')
            ->select(['id', 'name', 'sku'])
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->prepend(__('None'), '');

        return view('advancedreports::award-catalog.index')
            ->with(compact('products'));
    }

/**
     * Get award catalog data for DataTables - WITH CURRENCY FORMATTING
     * Update this method in your AwardCatalogController.php
     */
    public function getData(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $query = AwardCatalog::with(['product'])
            ->where('business_id', $business_id);

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $actions = '<div class="btn-group">';
                
                $actions .= '<button type="button" class="btn btn-info btn-xs edit-catalog-item" 
                    data-id="' . $row->id . '">
                    <i class="fa fa-edit"></i> ' . __('messages.edit') . '
                </button>';

                $actions .= '<button type="button" class="btn btn-danger btn-xs delete-catalog-item" 
                    data-id="' . $row->id . '" 
                    data-name="' . $row->award_name . '">
                    <i class="fa fa-trash"></i> ' . __('messages.delete') . '
                </button>';

                $actions .= '</div>';
                return $actions;
            })
            ->editColumn('award_name', function ($row) {
                $display = '<strong>' . $row->award_name . '</strong>';
                if (!empty($row->description)) {
                    $display .= '<br><small class="text-muted">' . $row->description . '</small>';
                }
                return $display;
            })
            ->editColumn('product_id', function ($row) {
                if ($row->product) {
                    return '<span class="label label-info">' . $row->product->name . '</span>';
                }
                return '<span class="text-muted">No Product Link</span>';
            })
            ->editColumn('monetary_value', function ($row) {
                return '<span class="text-success"><strong>' . 
                    $this->transactionUtil->num_f($row->monetary_value, true) . '</strong></span>';
            })
            ->editColumn('stock_required', function ($row) {
                if ($row->stock_required) {
                    $color = $row->stock_quantity > 0 ? 'success' : 'danger';
                    return '<span class="label label-' . $color . '">
                        Stock: ' . $row->stock_quantity . '
                    </span>';
                }
                return '<span class="label label-default">No Stock Required</span>';
            })
            ->editColumn('is_active', function ($row) {
                $checked = $row->is_active ? 'checked' : '';
                return '<input type="checkbox" class="toggle-active" data-id="' . $row->id . '" ' . $checked . '>';
            })
            ->rawColumns(['action', 'award_name', 'product_id', 'monetary_value', 'stock_required', 'is_active'])
            ->make(true);
    }

    /**
     * Get award catalog for customer awards - WITH CURRENCY FORMATTING
     * Add this method to CustomerRecognitionController.php
     */
    public function getAwardCatalog(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        
        $catalog = AwardCatalog::getActiveForBusiness($business_id);
        
        return response()->json([
            'success' => true,
            'catalog' => $catalog->map(function ($item) {
                return [
                    'id' => $item->id,
                    'award_name' => $item->award_name,
                    'description' => $item->description,
                    'monetary_value' => $item->monetary_value,
                    'monetary_value_formatted' => $this->transactionUtil->num_f($item->monetary_value, true),
                    'stock_required' => $item->stock_required,
                    'stock_quantity' => $item->stock_quantity,
                    'can_be_awarded' => $item->canBeAwarded()
                ];
            })
        ]);
    }

    /**
     * Store new award catalog item
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'award_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_id' => 'nullable|integer|exists:products,id',
            'point_threshold' => 'nullable|integer|min:0',
            'monetary_value' => 'required|numeric|min:0',
            'stock_required' => 'boolean',
            'stock_quantity' => 'required_if:stock_required,true|integer|min:0',
            'award_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $business_id = $request->session()->get('user.business_id');

            $data = $request->only([
                'award_name', 'description', 'product_id', 'point_threshold',
                'monetary_value', 'stock_required', 'stock_quantity'
            ]);

            $data['business_id'] = $business_id;
            $data['stock_required'] = $request->has('stock_required');
            $data['is_active'] = true;

            // Handle image upload
            if ($request->hasFile('award_image')) {
                $image = $request->file('award_image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs('award_images', $filename, 'public');
                $data['award_image'] = $path;
            }

            $catalog_item = AwardCatalog::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Award catalog item created successfully',
                'item' => $catalog_item
            ]);

        } catch (\Exception $e) {
            \Log::error('Award Catalog Store Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update award catalog item
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('customer_recognition.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'award_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_id' => 'nullable|integer|exists:products,id',
            'point_threshold' => 'nullable|integer|min:0',
            'monetary_value' => 'required|numeric|min:0',
            'stock_required' => 'boolean',
            'stock_quantity' => 'required_if:stock_required,true|integer|min:0',
            'award_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $business_id = $request->session()->get('user.business_id');

            $catalog_item = AwardCatalog::where('business_id', $business_id)
                ->findOrFail($id);

            $data = $request->only([
                'award_name', 'description', 'product_id', 'point_threshold',
                'monetary_value', 'stock_required', 'stock_quantity'
            ]);

            $data['stock_required'] = $request->has('stock_required');

            // Handle image upload
            if ($request->hasFile('award_image')) {
                // Delete old image if exists
                if ($catalog_item->award_image) {
                    Storage::disk('public')->delete($catalog_item->award_image);
                }

                $image = $request->file('award_image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs('award_images', $filename, 'public');
                $data['award_image'] = $path;
            }

            $catalog_item->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Award catalog item updated successfully',
                'item' => $catalog_item
            ]);

        } catch (\Exception $e) {
            \Log::error('Award Catalog Update Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete award catalog item
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('customer_recognition.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $catalog_item = AwardCatalog::where('business_id', $business_id)
                ->findOrFail($id);

            // Check if item has been used in awards
            $used_in_awards = $catalog_item->awards()->count();

            if ($used_in_awards > 0) {
                // Don't delete, just deactivate
                $catalog_item->update(['is_active' => false]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Award catalog item deactivated (was used in ' . $used_in_awards . ' awards)'
                ]);
            }

            // Delete image if exists
            if ($catalog_item->award_image) {
                Storage::disk('public')->delete($catalog_item->award_image);
            }

            $catalog_item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Award catalog item deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Award Catalog Delete Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $id = $request->get('id');

            $catalog_item = AwardCatalog::where('business_id', $business_id)
                ->findOrFail($id);

            $catalog_item->update([
                'is_active' => !$catalog_item->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'is_active' => $catalog_item->is_active
            ]);

        } catch (\Exception $e) {
            \Log::error('Award Catalog Toggle Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show/Get a specific catalog item (Add this method to AwardCatalogController.php)
     */
    public function show(Request $request, $id)
    {
        if (!auth()->user()->can('customer_recognition.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            
            $catalog_item = AwardCatalog::with(['product'])
                ->where('business_id', $business_id)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'item' => $catalog_item
            ]);

        } catch (\Exception $e) {
            \Log::error('Award Catalog Show Error: ' . $e->getMessage());
            return response()->json(['error' => 'Item not found'], 404);
        }
    }
}