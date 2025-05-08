<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Party;
use App\Models\ShippingAddress;
use App\Models\Partyaddationalfields;
use App\Models\User;
use App\Models\Partygroup;
use App\Models\Partyschedulereminder;
use App\Models\Tenant;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\TenantUnit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class Homecontroller extends Controller
{
    public function homePageData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'searchfilter' => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = auth()->user();
        $tenant = Tenant::where('user_id', $user->id)->first();
        $tenant_unit = TenantUnit::where('tenant_id', $tenant->id)->first();
    
        $parties = Party::where('tenant_id', $tenant->id)
            ->with(['shippingAddresses', 'additionalFields'])
            ->where('isactive', 1)
            ->where('is_delete', 0)
            ->get();
        
        $totalreceavable = $parties->where('topayortoreceive', 1)->sum('opening_balance');
        $totalpayable = $parties->where('topayortoreceive', 0)->sum('opening_balance');
    
        $filter = $request->searchfilter;
        $startDate = now();
        $endDate = now();
        $interval = 'daily'; // default
    
        switch ($filter) {
            case 'Last month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                $interval = 'daily';
                break;
            case 'This week':
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfWeek();
                $interval = 'daily';
                break;
            case 'This month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                $interval = 'daily';
                break;
            case 'This Quarter':
                $startDate = now()->firstOfQuarter();
                $endDate = now()->lastOfQuarter();
                $interval = 'monthly';
                break;
            case 'Half year':
                $startDate = now()->month <= 6 
                    ? now()->startOfYear()
                    : now()->startOfYear()->addMonths(6);
                $endDate = now()->month <= 6 
                    ? now()->startOfYear()->addMonths(6)->subDay()
                    : now()->endOfYear();
                $interval = 'monthly';
                break;
            case 'This year':
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
                $interval = 'monthly';
                break;
        }
    
        // Totals in date range
        $totalsales = Sale::where('tenant_unit_id', $tenant_unit->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');
    
        $totalpurchase = Purchase::where('tenant_unit_id', $tenant_unit->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');
    
        // Prepare Graph Data
        $xAxis = [];
        $salesData = [];
        $current = $startDate->copy();
    
        while ($current <= $endDate) {
            if ($interval === 'daily') {
                $label = $current->format('d/m');
                $salesAmount = Sale::where('tenant_unit_id', $tenant_unit->id)
                    ->whereDate('created_at', $current)
                    ->sum('total_amount');
                $current->addDay();
            } else { // monthly
                $label = $current->format('M Y');
                $salesAmount = Sale::where('tenant_unit_id', $tenant_unit->id)
                    ->whereYear('created_at', $current->year)
                    ->whereMonth('created_at', $current->month)
                    ->sum('total_amount');
                $current->addMonth();
            }
    
            $xAxis[] = $label;
            $salesData[] = $salesAmount;
        }
    
        // yAxis logic (rounded to nearest 500)
        $max = !empty($salesData) ? max($salesData) : 0;
        $yMax = ceil($max / 500) * 500;
        $yAxis = range(0, $yMax > 0 ? $yMax : 500, 500);
    
        return response()->json([
            'totalreceavable' => $totalreceavable,
            'totalpayable' => $totalpayable,
            'totalsales' => $totalsales,
            'totalpurchase' => $totalpurchase,
            'graph' => [
                'xAxis' => $xAxis,
                'yAxis' => $yAxis,
                'data' => $salesData
            ]
        ]);
    }
    

}




