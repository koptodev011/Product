<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Sale;
use App\Models\TenantUnit;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Productsale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Exports\ExportSales;
use App\Exports\ExportPurchase;
use App\Exports\ExportDaybook;
use App\Exports\ExportEstimateQuotation;
use Excel;
use Illuminate\Support\Facades\Storage;
class ReportsController extends Controller
{



public function dayBookReport(Request $request)
{
    $user = auth()->user();
    $maintenant = Tenant::where('user_id', $user->id)
        ->where('isactive', 1)
        ->first();

    $tenantunit = TenantUnit::with(['user', 'businesstype', 'businesscategory', 'state', 'city'])
        ->where('tenant_id', $maintenant->id)
        ->where('isactive', 1)
        ->first();

    $selectedDate = $request->has('date') && !empty($request->date)
        ? $request->date
        : now()->toDateString();

    $sales = Sale::where('tenant_unit_id', $tenantunit->id)
        ->whereDate('created_at', $selectedDate)
        ->get()
        ->map(function ($sale) {
            return [
                'billing_name' => $sale->billing_name,
                'status' => 'sale',
                'total_amount' => $sale->total_amount,
                'invoice_no' => $sale->invoice_no ?? null,
                'Reference_no' => $sale->Reference_no ?? null,
                'money_out' => 0,
                'money_in' => $sale->total_amount,
                'created_at' => $sale->created_at,
            ];
        });

    $purchases = Purchase::with('party')
        ->where('tenant_unit_id', $tenantunit->id)
        ->whereDate('created_at', $selectedDate)
        ->get()
        ->map(function ($purchase) {
            return [
                'billing_name' => optional($purchase->party)->party_name,
                'status' => 'Purchase',
                'total_amount' => $purchase->total_amount,
                'invoice_no' => $purchase->invoice_no ?? null,
                'Reference_no' => $purchase->Reference_no ?? null,
                'money_out' => $purchase->total_amount,
                'money_in' => 0,
                'created_at' => $purchase->created_at,
            ];
        });

    $totalMoneyIn = collect($sales)->sum('money_in');
    $totalMoneyOut = collect($purchases)->sum('money_out');
    $totalMoney = $totalMoneyIn - $totalMoneyOut;

    // Merge safely using collect()
    $mergedData = collect($sales)->merge(collect($purchases))->sortByDesc('created_at')->values();
    return response()->json([
        'date' => $selectedDate,
        'total_money_in' => $totalMoneyIn,
        'total_money_out' => $totalMoneyOut,
        'total_money' => $totalMoney,
        'data' => $mergedData,
    ]);
}



public function allTransaction(Request $request){
    $user = auth()->user();
    $maintenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();

    $tenantunit = TenantUnit::with(['user', 'businesstype', 'businesscategory', 'state', 'city']) 
        ->where('tenant_id', $maintenant->id)
        ->where('isactive', 1)
        ->first();
   
    $sales = Sale::where('tenant_unit_id', $tenantunit->id)
        ->get();
    $purchases = Purchase::where('tenant_unit_id', $tenantunit->id)
        ->get();
    $mergedData = $sales->merge($purchases);
    return response()->json([
        'data' => $mergedData
    ]);
}


  public function profitLossReport(Request $request){
    $user = auth()->user();
    $maintenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();

    $tenantunit = TenantUnit::with(['user', 'businesstype', 'businesscategory', 'state', 'city']) 
        ->where('tenant_id', $maintenant->id)
        ->where('isactive', 1)
        ->first();

    $sales = Sale::where('tenant_unit_id', $tenantunit->id)
        ->whereDate('created_at', now()->toDateString())
        ->get();

    $purchases = Purchase::where('tenant_unit_id', $tenantunit->id)
        ->whereDate('created_at', now()->toDateString())
        ->get();

    $totalSales = $sales->sum('total_amount');
    $totalPurchases = $purchases->sum('total_amount');
    $profit = $totalSales - $totalPurchases;

    return response()->json([
        'total_sales' => $totalSales,
        'total_purchases' => $totalPurchases,
        'profit' => $profit
    ]);}


   
    public function billWiseProfit(Request $request)
    {
        $user = auth()->user();
        $maintenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();
    
        $tenantunit = TenantUnit::with(['user', 'businesstype', 'businesscategory', 'state', 'city'])
            ->where('tenant_id', $maintenant->id)
            ->where('isactive', 1)
            ->first();
    
        $sales = Sale::with('productSales')->where('tenant_unit_id', $tenantunit->id)->get();
        dd($sales);
        $formattedSales = [];
    
        // Loop through each sale
        foreach ($sales as $sale) {
            $saleData = [
                'sale_id' => $sale->id,
                'products' => []
            ];
            foreach ($sale->productSales as $productSale) {
                $product = Product::where('id', $productSale->product_id)
                    ->with([
                        'productUnitConversion',
                        'pricing',
                        'wholesalePrice',
                        'stock',
                        'onlineStore',
                        'images',
                        'purchasePrice'
                    ])
                    ->where('id', $productSale->product_id)
                    ->first();
                    dd($product);

                $saleData['products'][] = $product;
                return response()->json([
                  'data' => $formattedSales
              ]);
            }
            $formattedSales[] = $saleData;
        }
        return response()->json([
            'data' => $formattedSales
        ]);
    }


    
    public function excelSalesReports(Request $request)
{
    $user = auth()->user();

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
                $startdate = now()->startOfMonth()->format('Y-m-d');
                $enddate = now()->endOfMonth()->format('Y-m-d');
                break;
            case "Last month":
                $startdate = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $enddate = now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case "Last quarter":
                $currentMonth = now()->month;
                $currentQuarter = ceil($currentMonth / 3);
                $lastQuarter = $currentQuarter - 1;
                $year = now()->year;
                if ($lastQuarter == 0) {
                    $lastQuarter = 4;
                    $year--;
                }
                $startdate = \Carbon\Carbon::createFromDate($year, ($lastQuarter - 1) * 3 + 1, 1)->startOfMonth()->format('Y-m-d');
                $enddate = \Carbon\Carbon::createFromDate($year, $lastQuarter * 3, 1)->endOfMonth()->format('Y-m-d');
                break;
            case "This year":
                $startdate = now()->startOfYear()->format('Y-m-d');
                $enddate = now()->endOfYear()->format('Y-m-d');
                break;
            case "Custom":
                $startdate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->startdate)->format('Y-m-d');
                $enddate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->enddate)->format('Y-m-d');
                break;
            default:
                return response()->json(['message' => 'Invalid filter provided.'], 400);
        }
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Date conversion failed',
            'error' => $e->getMessage(),
        ], 400);
    }

    $maintenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();
    if (!$maintenant) {
        return response()->json(['message' => 'No active tenant found for this user.'], 404);
    }

    $tenantUnit = TenantUnit::where('tenant_id', $maintenant->id)->where('isactive', 1)->first();
    if (!$tenantUnit) {
        return response()->json(['message' => 'No active tenant unit found.'], 404);
    }

    $salesQuery = Sale::withCount('productSales')
        ->where('tenant_unit_id', $tenantUnit->id)
        ->where('status', 'sale')
        ->whereBetween('created_at', [$startdate, $enddate]);

    if (!empty($request->party_id)) {
        $salesQuery->where('party_id', $request->party_id);
    }

    $sales = $salesQuery->get();

    if ($sales->isEmpty()) {
        return response()->json(['message' => 'No sales data found.'], 200);
    }
    $fileName = 'sales_report_' . now()->format('Ymd_His') . '.xlsx';
    $filePath = 'reports/' . $fileName;

    Excel::store(new ExportSales($sales), $filePath, 'public');

    return response()->json([
        'message' => 'Sales data exported to Excel successfully.',
        'file_path' => Storage::url($filePath), // example: /storage/reports/sales_report_20250502_121212.xlsx
    ]);
}

public function excelPurchaseReport(Request $request){
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
    $purchaseQuery = Purchase::whereBetween('created_at', [$startdate, $enddate]);

    if (!empty($request->party_id)) {
        $purchaseQuery->where('party_id', $request->party_id);
    }
    $purchase = $purchaseQuery->with(['party', 'purchaseproducts'])->get();
    $fileName = 'purchase_report_' . now()->format('Ymd_His') . '.xlsx';
    $filePath = 'reports/' . $fileName;

    Excel::store(new ExportPurchase($purchase), $filePath, 'public');

    return response()->json([
        'message' => 'Sales data exported to Excel successfully.',
        'file_path' => Storage::url($filePath), 
    ]);
}


public function excelDayBookReport(Request $request){

    $user = auth()->user();
    $maintenant = Tenant::where('user_id', $user->id)
        ->where('isactive', 1)
        ->first();

    $tenantunit = TenantUnit::with(['user', 'businesstype', 'businesscategory', 'state', 'city'])
        ->where('tenant_id', $maintenant->id)
        ->where('isactive', 1)
        ->first();

    $selectedDate = $request->has('date') && !empty($request->date)
        ? $request->date
        : now()->toDateString();

    $sales = Sale::where('tenant_unit_id', $tenantunit->id)
        ->whereDate('created_at', $selectedDate)
        ->get()
        ->map(function ($sale) {
            return [
                'billing_name' => $sale->billing_name,
                'status' => 'sale',
                'total_amount' => $sale->total_amount,
                'invoice_no' => $sale->invoice_no ?? null,
                'Reference_no' => $sale->Reference_no ?? null,
                'money_out' => 0,
                'money_in' => $sale->total_amount,
                'created_at' => $sale->created_at,
            ];
        });

    $purchases = Purchase::with('party')
        ->where('tenant_unit_id', $tenantunit->id)
        ->whereDate('created_at', $selectedDate)
        ->get()
        ->map(function ($purchase) {
            return [
                'billing_name' => optional($purchase->party)->party_name,
                'status' => 'Purchase',
                'total_amount' => $purchase->total_amount,
                'invoice_no' => $purchase->invoice_no ?? null,
                'Reference_no' => $purchase->Reference_no ?? null,
                'money_out' => $purchase->total_amount,
                'money_in' => 0,
                'created_at' => $purchase->created_at,
            ];
        });

    $totalMoneyIn = collect($sales)->sum('money_in');
    $totalMoneyOut = collect($purchases)->sum('money_out');
    $totalMoney = $totalMoneyIn - $totalMoneyOut;

    // Merge safely using collect()
    $mergedData = collect($sales)->merge(collect($purchases))->sortByDesc('created_at')->values();
    $fileName = 'daybook_report_' . now()->format('Ymd_His') . '.xlsx';
    $filePath = 'reports/' . $fileName;
    Excel::store(new ExportDaybook($mergedData), $filePath, 'public');
    return response()->json([
        'message' => 'Daybook data exported to Excel successfully.',
        'file_path' => Storage::url($filePath)
    ]);
}



// public function excelAllTransactionReport(Request $request)
// {
//     $user = auth()->user();
//     $maintenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();

//     $tenantunit = TenantUnit::with(['user', 'businesstype', 'businesscategory', 'state', 'city']) 
//         ->where('tenant_id', $maintenant->id)
//         ->where('isactive', 1)
//         ->first();
   
//     $sales = Sale::where('tenant_unit_id', $tenantunit->id)->get();
//     $purchases = Purchase::where('tenant_unit_id', $tenantunit->id)->get();

//     $mergedData = $sales->merge($purchases);

//     $mappedData = $mergedData->map(function ($item) {
//         return [
//             'invoice_no'     => $item->invoice_no ?? null,
//             'billing_name'   => $item->billing_name ?? null,
//             'status'         => $item->status ?? null,
//             'payment_status' => $item->sales_status ?? null,
//             'total_amount'   => $item->total_amount ?? null,
//             'created_at'     => $item->created_at ? $item->created_at->toDateTimeString() : null,
//         ];
//     });

//     return response()->json([
//         'data' => $mappedData
//     ]);
// }

public function excelAllTransactionReport(Request $request)
{
    $user = auth()->user();
    $maintenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();

    $tenantunit = TenantUnit::with(['user', 'businesstype', 'businesscategory', 'state', 'city']) 
        ->where('tenant_id', $maintenant->id)
        ->where('isactive', 1)
        ->first();

    $sales = Sale::where('tenant_unit_id', $tenantunit->id)->get();
    $purchases = Purchase::where('tenant_unit_id', $tenantunit->id)->get();
    
    // Normalize sales
    $mappedSales = $sales->map(function ($sale) {
        return [
            'invoice_no'     => $sale->invoice_no ?? null,
            'billing_name'   => $sale->billing_name ?? null,
            'status'         => $sale->status ?? null,
            'payment_status' => $sale->sales_status ?? null,
            'total_amount'   => $sale->total_amount ?? null,
            'created_at'     => $sale->created_at ?? null,
            'type'           => 'Sale',
        ];
    });

    // Normalize purchases
    $mappedPurchases = $purchases->map(function ($purchase) {
        return [
            'invoice_no'     => $purchase->invoice_number ?? null,
            'billing_name'   => $purchase->supplier_name ?? null,
            'status'         => $purchase->status ?? null,
            'payment_status' => $purchase->payment_status ?? null,
            'total_amount'   => $purchase->amount ?? null,
            'created_at'     => $purchase->created_at ?? null,
            'type'           => 'Purchase',
        ];
    });

    // Merge and sort
    $mergedData = $mappedSales->merge($mappedPurchases)->sortByDesc('created_at')->values();
    return response()->json([
        'data' => $mergedData
    ]);
    // Export to Excel
    $fileName = 'daybook_report_' . now()->format('Ymd_His') . '.xlsx';
    $filePath = 'reports/' . $fileName;
    Excel::store(new ExportDaybook($mergedData), $filePath, 'public');

    return response()->json([
        'message' => 'Report generated successfully.',
        'file_path' => Storage::url($filePath),
        'data' => $mergedData,
    ]);
}

public function excelEstimatQuotation(Request $request){
    $user = auth()->user();
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

    // Retrieve active tenant for the authenticated user
    $maintenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();

    if (!$maintenant) {
        return response()->json([
            'message' => 'No active tenant found for this user.',
        ], 404);
    }

    // Get the first active TenantUnit
    $tenantUnit = TenantUnit::with(['user', 'businesstype', 'businesscategory', 'state', 'city'])
        ->where('tenant_id', $maintenant->id)
        ->where('isactive', 1)
        ->first();

    if (!$tenantUnit) {
        return response()->json([
            'message' => 'No active tenant unit found.',
        ], 404);
    }

    $salesQuery = Sale::with(['productSales'])
        ->withCount('productSales')
        ->where('tenant_unit_id', $tenantUnit->id)
        ->where('status', 'Quotation Open')
        ->whereBetween('created_at', [$startdate, $enddate]);

    $totalAmount = $salesQuery->sum('total_amount');
    $receivedAmount = $salesQuery->sum('received_amount');
    $paidAmount = $totalAmount - $receivedAmount;
    
    if (!empty($request->party_id)) {
        $salesQuery->where('party_id', $request->party_id);
    }

    $sales = $salesQuery->get();
    
    $fileName = 'estimate_quotation_report_' . now()->format('Ymd_His') . '.xlsx';
    $filePath = 'reports/' . $fileName;
    Excel::store(new ExportEstimateQuotation($sales), $filePath, 'public');

    return response()->json([
        'message' => 'Report generated successfully.',
        'file_path' => Storage::url($filePath)
    ]);
    
}

}