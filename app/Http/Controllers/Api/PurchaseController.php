<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Paymentin;
use App\Models\Purchase;
use App\Models\Paymentout;
use App\Models\Party;
use App\Models\Tenant;
use App\Models\TenantUnit;
use App\Models\Product;
use App\Models\Purchaseproduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    public function createPurchaseInvoice(Request $request){
       
        $validator = Validator::make($request->all(), [
        "party_id" => "required|integer",
        "phone_number" => "required|string",
        "po_number" => "required|numeric",
        "po_date" => "required|string",
        "purchase_description" => "nullable|string",
        "purchase_image" => "nullable|image|mimes:jpeg,png,jpg,gif,svg",
        "total_amount" => "required|integer",
        "paid_amount" => "required|integer",
        "payment_type_id" => "required|integer",
        "invoice_no" => "nullable|string",
        "reference_no" => "nullable|string",
        
        "items" => "required|array",
        "items.*.product_id" => "required|integer",
        "items.*.quantity" => "required|integer",
        "items.*.unit_id" => "required|integer",
        "items.*.price_per_unit" => "nullable|integer",
        "items.*.discount_percentage" => "nullable|integer",
        "items.*.discount_amount" => "nullable|integer",
        "items.*.tax_amount" => "nullable|integer",
        "items.*.tax_percentage" => "nullable|integer",
        "items.*.product_amount" => "required|integer", 
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }
        $user = auth()->user(); 
        $searchMainTenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();
        $searchTenantUnit = TenantUnit::where('tenant_id', $searchMainTenant->id)->where('isactive', 1)->first();
        $purchase = new Purchase();
        $purchase->party_id = $request->party_id;
        $purchase->phone_number = $request->phone_number;
        $purchase->po_number = $request->po_number;
        $purchase->po_date = $request->po_date;
        $purchase->purchase_description = $request->purchase_description;
        // $purchase->purchase_image = $request->purchase_image;
        $purchase->total_amount = $request->total_amount;
        $purchase->paid_amount = $request->paid_amount;
        $purchase->payment_type_id = $request->payment_type_id;
        $purchase->tenant_unit_id = $searchTenantUnit->id;
        $purchase->status = 'Purchase';
        $purchase->invoice_no = $request->invoice_no;
        $purchase->reference_no = $request->reference_no;
        
        if ($request->hasFile('purchase_image')) {
            $image = $request->file('purchase_image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('uploads/purchase_images', $imageName, 'public');
        
            $purchase->purchase_image = $imagePath; // Save path in the database
        }
        
        $purchase->save();


        foreach ($request->items as $item) {
            $purchaseproduct = new Purchaseproduct();
            $purchaseproduct->product_id = $item['product_id'];
            $purchaseproduct->quantity = $item['quantity'];
            $purchaseproduct->unit_id= $item['unit_id'];
            $purchaseproduct->priceperunit = $item['price_per_unit'];
            $purchaseproduct->discount_percentage = $item['discount_percentage'] ?? null;
            $purchaseproduct->discount_amount = $item['discount_amount'] ?? null;
            $purchaseproduct->tax_percentage = $item['tax_percentage']?? null;
            $purchaseproduct->tax_amount = $item['tax_amount'] ?? null;
            $purchaseproduct->total_amount = $item['product_amount'];
            $purchaseproduct->purchase_id = $purchase->id;
            $purchaseproduct->save();
            $searchMainTenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();

            $product = Product::where('id', $item['product_id']) // Fixed reference
                ->where('tenant_id', $searchMainTenant->id)
                ->with([
                    'productUnitConversion',
                    'pricing',
                    'wholesalePrice',
                    'stock',
                    'onlineStore',
                    'images',
                    'purchasePrice'
                ])
                ->first(); 

        if($product->productUnitConversion->product_base_unit_id == $item['unit_id'] ){
            $secondarystock = $product->productUnitConversion->conversion_rate * $item['quantity'];
            $product->stock->secondaryunit_stock_value = $product->stock->secondaryunit_stock_value + $secondarystock;
            $product->stock->product_stock = $product->stock->product_stock + $item['quantity'];
            $product->stock->previous_stock = $product->stock->previous_stock + $item['quantity'];
            $product->stock->save();

        }
        if($product->productUnitConversion->product_secondary_unit_id  ==  $item['unit_id']){
            $product->stock->secondaryunit_stock_value = $product->stock->secondaryunit_stock_value + $item['quantity'];
            $product->stock->product_stock = $product->stock->secondaryunit_stock_value/$product->productUnitConversion->conversion_rate;
            $increasedquantity = $item['quantity']/ $product->productUnitConversion->conversion_rate;
            $product->stock->previous_stock = $product->stock->previous_stock + $increasedquantity;
            $product->stock->save();
        }
    }
        return response()->json([
            'message' => 'product purchased successfully'
        ], 200);
    }








    // public function getPurchaseData(Request $request){
    // $validator = Validator::make($request->all(), [
    //     'salefilter' => "nullable",
    //     "startdate" => "required_if:salefilter,Custom|date_format:d/m/Y",
    //     "enddate" => "required_if:salefilter,Custom|date_format:d/m/Y|after_or_equal:startdate",
    // ]);

    // if ($validator->fails()) {
    //     return response()->json([
    //         'message' => 'Validation failed',
    //         'errors' => $validator->errors(),
    //     ], 400);
    // }

    // try {
    //     switch ($request->salefilter) {
    //         case "This month":
    //             $startdate = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
    //             $enddate = \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d');
    //             break;

    //         case "Last month":
    //             $startdate = \Carbon\Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
    //             $enddate = \Carbon\Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
    //             break;

    //         case "Last quarter":
    //             $currentMonth = \Carbon\Carbon::now()->month;
    //             $currentQuarter = ceil($currentMonth / 3);
    //             $lastQuarter = $currentQuarter - 1;
    //             $year = \Carbon\Carbon::now()->year;

    //             if ($lastQuarter == 0) {
    //                 $lastQuarter = 4;
    //                 $year--;
    //             }

    //             $startdate = \Carbon\Carbon::createFromDate($year, ($lastQuarter - 1) * 3 + 1, 1)->startOfMonth()->format('Y-m-d');
    //             $enddate = \Carbon\Carbon::createFromDate($year, $lastQuarter * 3, 1)->endOfMonth()->format('Y-m-d');
    //             break;

    //         case "This year":
    //             $startdate = \Carbon\Carbon::now()->startOfYear()->format('Y-m-d');
    //             $enddate = \Carbon\Carbon::now()->endOfYear()->format('Y-m-d');
    //             break;

    //         case "Custom":
    //             $startdate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->startdate)->format('Y-m-d');
    //             $enddate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->enddate)->format('Y-m-d');
    //             break;

    //         default:
    //             return response()->json([
    //                 'message' => 'Invalid filter provided.',
    //             ], 400);
    //     }
    // } catch (\Exception $e) {
    //     return response()->json([
    //         'message' => 'Date conversion failed',
    //         'error' => $e->getMessage(),
    //     ], 400);
    // }

    // // Fetch the sales data within the date range
    // $purchaseQuery = Purchase::whereBetween('created_at', [$startdate, $enddate]);
    //  // Sum total_amount and paid_amount
    //  $totalAmount = $purchaseQuery->sum('total_amount');
    //  $paidAmount = $purchaseQuery->sum('paid_amount');
    //  $unpaidAmount = $totalAmount - $paidAmount;

    // if (!empty($request->party_id)) {
    //     $purchaseQuery->where('party_id', $request->party_id);
    // }
    // $purchase = $purchaseQuery->get();
  
    // if ($purchase->isEmpty()) {
    //     return response()->json([
    //         'message' => 'No data found for the given date range',
    //     ], 200);
    // }

    // return response()->json([
    //     'message' => 'Purchase data retrieved successfully',
    //     'total_amount' => $totalAmount,
    //     'paid_amount' => $paidAmount,
    //     'unpaid_amount' => $unpaidAmount,
    //     'data' => $purchase,
    // ], 200);
    // }


    public function getPurchaseData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'salefilter' => "nullable",
            "startdate" => "required_if:salefilter,Custom|date_format:d/m/Y",
            "enddate" => "required_if:salefilter,Custom|date_format:d/m/Y|after_or_equal:startdate",
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }
    
        try {
            switch ($request->salefilter) {
                case "This month":
                    $startdate = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
                    $enddate = \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d');
                    break;
    
                case "Last month":
                    $startdate = \Carbon\Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                    $enddate = \Carbon\Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                    break;
    
                case "Last quarter":
                    $currentMonth = \Carbon\Carbon::now()->month;
                    $currentQuarter = ceil($currentMonth / 3);
                    $lastQuarter = $currentQuarter - 1;
                    $year = \Carbon\Carbon::now()->year;
    
                    if ($lastQuarter == 0) {
                        $lastQuarter = 4;
                        $year--;
                    }
    
                    $startdate = \Carbon\Carbon::createFromDate($year, ($lastQuarter - 1) * 3 + 1, 1)->startOfMonth()->format('Y-m-d');
                    $enddate = \Carbon\Carbon::createFromDate($year, $lastQuarter * 3, 1)->endOfMonth()->format('Y-m-d');
                    break;
    
                case "This year":
                    $startdate = \Carbon\Carbon::now()->startOfYear()->format('Y-m-d');
                    $enddate = \Carbon\Carbon::now()->endOfYear()->format('Y-m-d');
                    break;
    
                case "Custom":
                    $startdate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->startdate)->format('Y-m-d');
                    $enddate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->enddate)->format('Y-m-d');
                    break;
    
                default:
                    return response()->json([
                        'message' => 'Invalid filter provided.',
                    ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Date conversion failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    
        // Fetch the purchases in date range
        $purchaseQuery = Purchase::whereBetween('created_at', [$startdate, $enddate]);
    
        if (!empty($request->party_id)) {
            $purchaseQuery->where('party_id', $request->party_id);
        }
    
        // Load related party and purchase products
        $purchase = $purchaseQuery->with(['party', 'purchaseproducts'])->get();
    
        // Calculate sums after filtering
        $totalAmount = $purchase->sum('total_amount');
        $paidAmount = $purchase->sum('paid_amount');
        $unpaidAmount = $totalAmount - $paidAmount;
    
        if ($purchase->isEmpty()) {
            return response()->json([
                'message' => 'No data found for the given date range',
            ], 200);
        }
    
        return response()->json([
            'message' => 'Purchase data retrieved successfully',
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'unpaid_amount' => $unpaidAmount,
            'data' => $purchase->map(function ($item) {
                return [
                    'id' => $item->id,
                    'party_id' => $item->party_id,
                    'party_name' => $item->party->party_name ?? null, // Make sure your column is 'name'
                    'total_amount' => $item->total_amount,
                    'invoice_no' => $item->invoice_no,
                    'payment_status' => $item->status,
                    'paid_amount' => $item->paid_amount,
                    'created_at' => $item->created_at->format('Y-m-d'),
                    'item_quantity_total' => $item->purchaseproducts->sum('quantity'),
                ];
            }),
        ], 200);
    }
    







    public function paymentOut(Request $request){
        $validator = Validator::make($request->all(), [
            'party_id' => 'required|integer',
            'payment_type_id' => 'required|integer',
            'paymentout_description' => 'nullable|string',
            'paymentout_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'paid_amount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $searchparty = Party::where('id', $request->party_id)->first();

        if($searchparty->topayortorecive == 0 && $searchparty->opening_balance >= $request->paid_amount){
           $searchparty->opening_balance = $searchparty->opening_balance - $request->paid_amount;
           $searchparty->save(); 
        }else{
            return response()->json([
                'message' => 'Payment failed',
            ], 404);
        }
        if (!$searchparty) {
            return response()->json([
                'message' => 'Party not found',
            ], 404);
        }



        $paymentout = new Paymentout();
        $paymentout->party_id = $request->party_id;
        $paymentout->payment_type_id = $request->payment_type_id;
        $paymentout->paymentout_description = $request->paymentout_description;
        $paymentout->paid_amount = $request->paid_amount;

        // Handle image upload
        if ($request->hasFile('paymentout_image')) {
            $imagePath = $request->file('paymentout_image')->store('paymentouts', 'public');
            $paymentout->paymentout_image = $imagePath;
        }
        $paymentout->save();

        return response()->json([
            'message' => 'Payment out saved successfully',
            'data' => $paymentout
        ], 200);
    }



    public function createPurchaseOrderInvoice(Request $request){
        $validator = Validator::make($request->all(), [
            "party_id" => "required|integer",
            "phone_number" => "required|string",
            "po_number" => "required|numeric",
            "po_date" => "required|string",
            "purchase_description" => "nullable|string",
            "purchase_image" => "nullable",
            "total_amount" => "required|integer",
            "paid_amount" => "required|integer",
            "payment_type_id" => "required|integer",
            "items" => "required|array",
            "items.*.product_id" => "required|integer",
            "items.*.quantity" => "required|integer",
            "items.*.unit_id" => "required|integer",
            "items.*.price_per_unit" => "nullable|integer",
            "items.*.discount_percentage" => "nullable|integer",
            "items.*.discount_amount" => "nullable|integer",
            "items.*.tax_amount" => "nullable|integer",
            "items.*.tax_percentage" => "nullable|integer",
            "items.*.total_amount" => "required|integer", 
            ]);
        
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }
    
            $purchase = new Purchase();
            $purchase->party_id = $request->party_id;
            $purchase->phone_number = $request->phone_number;
            $purchase->po_number = $request->po_number;
            $purchase->po_date = $request->po_date;
            $purchase->purchase_description = $request->purchase_description;
            // $purchase->purchase_image = $request->purchase_image;
            $purchase->total_amount = $request->total_amount;
            $purchase->paid_amount = $request->paid_amount;
            $purchase->payment_type_id = $request->payment_type_id;
            $purchase->status = 'Order Overdue';
            $purchase->save();
    
            foreach ($request->items as $item) {
                $purchaseproduct = new Purchaseproduct();
                $purchaseproduct->product_id = $item['product_id'];
                $purchaseproduct->quantity = $item['quantity'];
                $purchaseproduct->unit_id= $item['unit_id'];
                $purchaseproduct->priceperunit = $item['price_per_unit'];
                $purchaseproduct->discount_percentage = $item['discount_percentage'] ?? null;
                $purchaseproduct->discount_amount = $item['discount_amount'] ?? null;
                $purchaseproduct->tax_percentage = $item['tax_percentage']?? null;
                $purchaseproduct->tax_amount = $item['tax_amount'] ?? null;
                $purchaseproduct->total_amount = $item['total_amount'];
                $purchaseproduct->purchase_id = $purchase->id;
                $purchaseproduct->save();
            }
        
        
            return response()->json([
                'message' => 'Purchase in added successfully'
            ], 200);
    }


    public function convertPurchaseOrderToPurchase(Request $request){
        $validator = Validator::make($request->all(), [
            "purchase_id" => "required|numeric|exists:purchases,id",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $purchase = Purchase::findOrFail($request->purchase_id);
        $purchase->status = "Purchase";
        $purchase->save();   

        return response()->json([
            'message' => 'Purchase converted to Purchase successfully'
        ], 200);

    }


    public function purchaseReturn(Request $request){
        $validator = Validator::make($request->all(), [
            "party_id" => "required|integer",
            "phone_number" => "required|string",
            "po_number" => "required|numeric",
            "po_date" => "required|string",
            "purchase_description" => "nullable|string",
            "purchase_image" => "nullable",
            "total_amount" => "required|integer",
            "paid_amount" => "required|integer",
            "payment_type_id" => "required|integer",
            "items" => "required|array",
            "items.*.product_id" => "required|integer",
            "items.*.quantity" => "required|integer",
            "items.*.unit_id" => "required|integer",
            "items.*.price_per_unit" => "nullable|integer",
            "items.*.discount_percentage" => "nullable|integer",
            "items.*.discount_amount" => "nullable|integer",
            "items.*.tax_amount" => "nullable|integer",
            "items.*.tax_percentage" => "nullable|integer",
            "items.*.total_amount" => "required|integer", 
            ]);
        
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }
    
            $purchase = new Purchase();
            $purchase->party_id = $request->party_id;
            $purchase->phone_number = $request->phone_number;
            $purchase->po_number = $request->po_number;
            $purchase->po_date = $request->po_date;
            $purchase->purchase_description = $request->purchase_description;
            // $purchase->purchase_image = $request->purchase_image;
            $purchase->total_amount = $request->total_amount;
            $purchase->paid_amount = $request->paid_amount;
            $purchase->payment_type_id = $request->payment_type_id;
            $purchase->status = 'Debit Note';
            $purchase->save();
    
            foreach ($request->items as $item) {
                $purchaseproduct = new Purchaseproduct();
                $purchaseproduct->product_id = $item['product_id'];
                $purchaseproduct->quantity = $item['quantity'];
                $purchaseproduct->unit_id= $item['unit_id'];
                $purchaseproduct->priceperunit = $item['price_per_unit'];
                $purchaseproduct->discount_percentage = $item['discount_percentage'] ?? null;
                $purchaseproduct->discount_amount = $item['discount_amount'] ?? null;
                $purchaseproduct->tax_percentage = $item['tax_percentage']?? null;
                $purchaseproduct->tax_amount = $item['tax_amount'] ?? null;
                $purchaseproduct->total_amount = $item['total_amount'];
                $purchaseproduct->purchase_id = $purchase->id;
                $purchaseproduct->save();
            }
            return response()->json([
                'message' => 'Purchase debit note added successfully'
            ], 200);
    }
}
