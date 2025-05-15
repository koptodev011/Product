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
class PartyController extends Controller
{



    // public function getParties() {
    //     $user = auth()->user();
    //     $parties = Party::where('user_id', $user->id)
    //         ->with(['shippingAddresses', 'additionalFields'])
    //         ->where('isactive', 1)
    //         ->get();
    
    //     if ($parties->isEmpty()) {
    //         return response()->json([
    //             'message' => 'Parties not found or inactive'
    //         ], 404);
    //     }
    //     return response()->json($parties, 200);
    // }



public function getParties()
{
    $user = auth()->user();
    $parties = Party::where('user_id', $user->id)
        ->with(['shippingAddresses', 'additionalFields'])
        ->where('is_delete', 0)
        ->get();

    if ($parties->isEmpty()) {
        return response()->json([
            'message' => 'Parties not found or inactive'
        ], 404);
    }


    return response()->json([
        'parties' => $parties
    ], 200);
}

    public function getParty(Request $request) {
        $validator = Validator::make($request->all(), [
            'party_id' => 'required|numeric'
        ]);
        $user = auth()->user();
        $searchForMainTenant = Tenant::where('user_id', $user->id)
            ->where('isactive', 1)
            ->first();
        $parties = Party::where('tenant_id', $searchForMainTenant->id)
        ->with(['shippingAddresses', 'additionalFields'])
        ->where('id', $request->party_id)->first();

        if (!$parties) {
            return response()->json([
                'message' => 'Party not found or inactive'
            ], 404);
        }
        return response()->json($parties, 200);
    }

  
  
  
    public function getPartyGroup(){
        $user = auth()->user();
        $searchForMainTenant = Tenant::where('user_id', $user->id)
            ->where('isactive', 1)
            ->first();
        $partyGroups = Partygroup::where('tenant_id', $searchForMainTenant->id)
            ->where('is_delete', 0)
            ->get();
    
        if ($partyGroups->isEmpty()) {
            return response()->json([
                'message' => 'Party group not found'
            ], 404);
        }
    
        $partyGroupsWithCount = $partyGroups->map(function ($partyGroup) {
            $partyGroup->party_count = Party::where('group_id', $partyGroup->id)->count();
            return $partyGroup;
        });
    
        return response()->json($partyGroupsWithCount, 200);
    }

 





public function addParty(Request $request)
{
    $validator = Validator::make($request->all(), [
        'party_name' => 'required|string',
        'tin_number' => 'nullable|string',
        'phone_number' => 'nullable|numeric|digits:10',
        'email' => 'nullable',
        'billing_address' => 'nullable|string',
        'opening_balance' => 'nullable|numeric',
        'topayortorecive' => 'nullable|boolean',
        'creditlimit' => 'nullable|numeric',
        'shipping_addresses' => 'nullable|array',
        'shipping_addresses.*.shipping_addresses' => 'nullable|string',
        // 'addational_fields' => 'nullable|array',
        // 'addational_fields.*.addational_field_name' => 'nullable|string',
        // 'addational_fields.*.addational_field_data' => 'nullable|string',
        'group_id' => 'nullable|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 400);
    }

    $user = auth()->user();
    $tenant = Tenant::where('user_id', $user->id)
        ->where('isactive', 1)
        ->first();

    if (!$tenant) {
        return response()->json([
            'message' => 'Tenant not found or inactive'
        ], 404);
    }

    $searchparty = Party::where('email', $request->email)
        ->where('tenant_id', $tenant->id)
        ->where('is_delete', 0)
        ->first();

    if ($searchparty) {
        return response()->json([
            'message' => 'Party with this email already exists'
        ], 400);
    }

    // ✅ Cast numeric/boolean fields properly
    $openingBalance = $request->opening_balance !== null ? (int) $request->opening_balance : null;
    $topayortorecive = $request->topayortorecive !== null ? (bool) $request->topayortorecive : null;
    $creditLimit = $request->creditlimit !== null ? (int) $request->creditlimit : null;
    $groupId = $request->group_id !== null ? (int) $request->group_id : null;

    // ✅ Create Party
    $party = new Party();
    $party->party_name = $request->party_name;
    $party->TIN_number = $request->tin_number;
    $party->email = $request->email;
    $party->phone_number = $request->phone_number;
    $party->billing_address = $request->billing_address;
    $party->opening_balance = $openingBalance;
    $party->topayortorecive = $topayortorecive;
    $party->creditlimit = $creditLimit;
    $party->user_id = $user->id;
    $party->tenant_id = $tenant->id;
    $party->group_id = $groupId;
    $party->save();

    // ✅ Save Shipping Addresses
    if (is_array($request->shipping_addresses)) {
        foreach ($request->shipping_addresses as $shipping_address) {
            ShippingAddress::create([
                'shipping_address' => $shipping_address['shipping_addresses'] ?? null,
                'party_id' => $party->id,
            ]);
        }
    }

    // Optional: Save Additional Fields
    // if (is_array($request->addational_fields)) {
    //     foreach ($request->addational_fields as $field) {
    //         Partyaddationalfields::create([
    //             'addational_field_name' => $field['addational_field_name'] ?? null,
    //             'addational_field_data' => $field['addational_field_data'] ?? null,
    //             'party_id' => $party->id,
    //         ]);
    //     }
    // }

    return response()->json(['message' => 'Party created successfully'], 200);
}







    public function addPartyGroup(Request $request){
        $user = auth()->user();
        $searchForMainTenant = Tenant::where('user_id', $user->id)
            ->where('isactive', 1)
            ->first();

        $validator = Validator::make($request->all(), [
            'group_name' => 'required|string',
        ]);

        $partygroup = new Partygroup();
        $partygroup->group_name = $request->group_name;
        $partygroup->tenant_id = $searchForMainTenant->id;
        $partygroup->save();

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }
        return response()->json(['message' => 'Party group created successfully'], 200);
    }



    







    public function updatePartyDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'party_id' => 'required|numeric',
            'party_name' => 'nullable|string',
            'tin_number' => 'nullable|string',
            'phone_number' => 'nullable|numeric|digits:10',
            'email' => 'nullable|email|unique:parties,email,' . $request->party_id,
            'billing_address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'topayortorecive' => 'nullable|boolean',
            'creditlimit' => 'nullable|numeric',
            'group_id' => 'nullable|numeric',
            'shipping_addresses' => 'nullable|array',
            'shipping_addresses.*.shipping_address_id' => 'nullable|numeric',
            'shipping_addresses.*.shipping_addresses' => 'nullable|string',
            'addational_fields' => 'nullable|array',
            'addational_fields.*.addational_field_id' => 'nullable|numeric',
            'addational_fields.*.addational_field_name' => 'nullable|string',
            'addational_fields.*.addational_field_data' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Find the party for the authenticated user
        $user = auth()->user();
        $party = Party::where('id', $request->party_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$party) {
            return response()->json([
                'message' => 'Party not found or inactive',
            ], 404);
        }

        // Update party details
        $party->update([
            'party_name' => $request->party_name,
            'phone_number' => $request->phone_number,
            'tin_number' => $request->tin_number,
            'email' => $request->email,
            'billing_address' => $request->billing_address,
            'opening_balance' => $request->opening_balance,
            'topayortorecive' => $request->topayortorecive,
            'creditlimit' => $request->creditlimit,
            'group_id' => $request->group_id,
        ]);

        // Handle shipping addresses
        if ($request->filled('shipping_addresses')) {
            foreach ($request->shipping_addresses as $shippingAddress) {
                if (!empty($shippingAddress['shipping_address_id'])) {
                    $shippingAddressRecord = $party->shippingAddresses()
                        ->where('id', $shippingAddress['shipping_address_id'])
                        ->first();

                    if ($shippingAddressRecord) {
                        $shippingAddressRecord->update([
                            'shipping_address' => $shippingAddress['shipping_addresses'],
                        ]);
                    } else {
                        $party->shippingAddresses()->create([
                            'party_id' => $party->id,
                            'shipping_address' => $shippingAddress['shipping_addresses'],
                        ]);
                    }
                } else {
                    $party->shippingAddresses()->create([
                        'party_id' => $party->id,
                        'shipping_address' => $shippingAddress['shipping_addresses'],
                    ]);
                }
            }
        }
        if ($request->filled('addational_fields')) {
            foreach ($request->addational_fields as $additionalField) {
                if (!empty($additionalField['addational_field_id'])) {
                    $additionalFieldRecord = $party->additionalFields()
                        ->where('id', $additionalField['addational_field_id'])
                        ->first();

                    if ($additionalFieldRecord) {
                        $additionalFieldRecord->update([
                            'addational_field_name' => $additionalField['addational_field_name'],
                            'addational_field_data' => $additionalField['addational_field_data'],
                        ]);
                    } else {
                        $party->additionalFields()->create([
                            'party_id' => $party->id,
                            'addational_field_name' => $additionalField['addational_field_name'],
                            'addational_field_data' => $additionalField['addational_field_data'],
                        ]);
                    }
                } else {
                    $party->additionalFields()->create([
                        'party_id' => $party->id,
                        'addational_field_name' => $additionalField['addational_field_name'],
                        'addational_field_data' => $additionalField['addational_field_data'],
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Party details updated successfully'
        ]);
    }










public function scheduleReminder(Request $request) {
    $validator = Validator::make($request->all(), [
        'party_id' => 'required',
        'reminder_frequency' => 'required',
        'send_copy' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 400);
    }
    $setreminder = new Partyschedulereminder();
    $setreminder->party_id = $request->party_id;
    $setreminder->reminder_frequency = $request->reminder_frequency;
    $setreminder->send_copy = $request->send_copy;
    $setreminder->save();
    return response()->json([
        'message' => 'Reminder scheduled successfully',
        'data' => $setreminder
    ]);
}









public function getPartyReminder(Request $request) {
    $validator = Validator::make($request->all(), [
        'party_id' => 'required',
    ]);
    $setreminder = Partyschedulereminder::where('party_id', $request->party_id)->first();
    if (!$setreminder) {
        return response()->json([
            'message' => 'Reminder not found'
        ], 404);
    }
    return response()->json([
        'message' => 'Reminder found',
        'data' => $setreminder
    ]);
}


public function deleteParty(Request $request)
{
    $validator = Validator::make($request->all(), [
        'party_ids' => 'required|array',
        'party_ids.*' => 'required|numeric'
    ]);
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }
    $user = auth()->user();
    $searchForMainTenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();

    if (!$searchForMainTenant) {
        return response()->json(['message' => 'Tenant not found or inactive'], 404);
    }

    $parties = Party::whereIn('id', $request->party_ids)
        ->where('tenant_id', $searchForMainTenant->id)
        ->get();

    if ($parties->isEmpty()) {
        return response()->json(['message' => 'Parties not found'], 404);
    }

    foreach ($parties as $party) {
        $party->is_delete = 1;
        $party->save();
    }

    return response()->json(['message' => 'Parties deleted successfully'], 200);
    
}




public function updatePartyGroup(Request $request)
{
    $validator = Validator::make($request->all(), [
        'group_id' => 'required|numeric',
        'group_name' => 'required|string',
    ]);
   
    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 400);
    }

    $user = auth()->user();
    $searchForMainTenant = Tenant::where('user_id', $user->id)
        ->where('isactive', 1)
        ->first();
    $partyGroup = Partygroup::where('id', $request->group_id)
        ->where('tenant_id', $searchForMainTenant->id)
        ->first();
    
    if (!$partyGroup) {
        return response()->json([
            'message' => 'Party group not found or inactive',
        ], 404);
    }

    $partyGroup->update([
        'group_name' => $request->group_name,
    ]);

    return response()->json([
        'message' => 'Party group updated successfully',
    ]);
}




public function deletePartyGroup(Request $request)
{
    $validator = Validator::make($request->all(), [
        'group_ids' => 'required|array',
        'group_ids.*' => 'required|numeric',
    ]);
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }
    $user = auth()->user();
    $searchForMainTenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();

    if (!$searchForMainTenant) {
        return response()->json(['message' => 'Tenant not found or inactive'], 404);
    }

    $partyGroups = Partygroup::whereIn('id', $request->group_ids)
        ->where('tenant_id', $searchForMainTenant->id)
        ->get();

    if ($partyGroups->isEmpty()) {
        return response()->json(['message' => 'Party groups not found'], 404);
    }
    $searchForGeneralCategory = Partygroup::where('tenant_id', $searchForMainTenant->id)
        ->where('group_name', 'General')
        ->first();

    foreach ($partyGroups as $partyGroup) {
        $partyGroup->is_delete = 1;
        $partyGroup->save();
        Party::where('group_id', $partyGroup->id)
            ->update(['group_id' => $searchForGeneralCategory->id]);
    }


    return response()->json(['message' => 'Party groups deleted successfully'], 200);
}


// public function getPartiesByGroup(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'group_id' => 'required|numeric',
//     ]);
//     if ($validator->fails()) {
//         return response()->json(['error' => $validator->errors()], 400);
//     }
//     $user = auth()->user();
//     $searchForMainTenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();

//     if (!$searchForMainTenant) {
//         return response()->json(['message' => 'Tenant not found or inactive'], 404);
//     }

//     $parties = Party::where('tenant_id', $searchForMainTenant->id)
//         ->where('group_id', $request->group_id)
//         ->where('is_delete', 0)
//         ->get();
    
//     $searchCategory = Partygroup::where('tenant_id', $searchForMainTenant->id)
//         ->where('id', $request->group_id)
//         ->first();
//     $searchParties = Party::where('tenant_id', $searchForMainTenant->id)
//         ->where('group_id', $request->group_id)
//         ->where('is_delete', 0)
//         ->get();

//     if ($parties->isEmpty()) {
//         return response()->json(['message' => 'Parties not found'], 404);
//     }

//     $receivedamount = Party::where('tenant_id', $searchForMainTenant->id)
//         ->where('group_id', $request->group_id)
//         ->where('is_delete', 0)
//         ->where('topayortorecive', 1)
//         ->sum('opening_balance');
    
//     $topayamount = Party::where('tenant_id', $searchForMainTenant->id)
//         ->where('group_id', $request->group_id)
//         ->where('is_delete', 0)
//         ->where('topayortorecive', 0)
//         ->sum('opening_balance');
    
//     if($receivedamount>$topayamount){
//         $amount = $receivedamount - $topayamount;
//         $amountstatus = "Receive";
//     }else{
//         $amount = $topayamount - $receivedamount;
//         $amountstatus = "ToPay";
//     }

//     return response()->json(['parties' => $parties], 200);

// }


public function getPartiesByGroup(Request $request)
{
    $validator = Validator::make($request->all(), [
        'group_id' => 'required|numeric',
    ]);
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }
    $user = auth()->user();
    $searchForMainTenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();

    if (!$searchForMainTenant) {
        return response()->json(['message' => 'Tenant not found or inactive'], 404);
    }

    $searchCategory = Partygroup::where('tenant_id', $searchForMainTenant->id)
        ->where('id', $request->group_id)
        ->first();

    if (!$searchCategory) {
        return response()->json(['message' => 'Party group not found'], 404);
    }

    $parties = Party::where('tenant_id', $searchForMainTenant->id)
        ->where('group_id', $request->group_id)
        ->where('is_delete', 0)
        ->get();
    
 

    $receivedAmount = Party::where('tenant_id', $searchForMainTenant->id)
        ->where('group_id', $request->group_id)
        ->where('is_delete', 0)
        ->where('topayortorecive', 1)
        ->sum('opening_balance');
    
    $toPayAmount = Party::where('tenant_id', $searchForMainTenant->id)
        ->where('group_id', $request->group_id)
        ->where('is_delete', 0)
        ->where('topayortorecive', 0)
        ->sum('opening_balance');
    
    if ($receivedAmount > $toPayAmount) {
        $amount = $receivedAmount - $toPayAmount;
        $amountStatus = "Receive";
    } else {
        $amount = $toPayAmount - $receivedAmount;
        $amountStatus = "ToPay";
    }

    return response()->json([
        'category' => $searchCategory,
        'amount' => $amount,
        'amount_status' => $amountStatus,
        'parties' => $parties,
    ], 200);
}


public function moveToThisGroup(Request $request){
    $validator = Validator::make($request->all(), [
        'group_id' => 'required|numeric',
        'party_ids' => 'required|array',
        'party_ids.*' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $user = auth()->user();
    $searchForMainTenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();


    if (!$searchForMainTenant) {
        return response()->json(['message' => 'Tenant not found or inactive'], 404);
    }

    $parties = Party::whereIn('id', $request->party_ids)
        ->where('tenant_id', $searchForMainTenant->id)
        ->where('is_delete', 0)
        ->get();
    if ($parties->isEmpty()) {
        return response()->json(['message' => 'Parties not found'], 404);
    }

    foreach ($parties as $party) {
        $party->group_id = $request->group_id;
        $party->save();
    }

    return response()->json(['message' => 'Parties moved to the new group successfully'], 200);
}


public function getPartiesExceptSelectedCategory(Request $request){
    $validator = Validator::make($request->all(), [
        'group_id' => 'required|numeric',
    ]);
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }
    $user = auth()->user();
    $searchForMainTenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();

    if (!$searchForMainTenant) {
        return response()->json(['message' => 'Tenant not found or inactive'], 404);
    }

    $parties = Party::where('tenant_id', $searchForMainTenant->id)
        ->where('is_delete', 0)
        ->where('group_id', '!=', $request->group_id)
        ->get();

    if ($parties->isEmpty()) {
        return response()->json(['message' => 'Parties not found'], 404);
    }

    return response()->json(['parties' => $parties], 200);
}


public function changePartyStatus(Request $request){
    $user = auth()->user();
    $searchForMainTenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();
    $validator = Validator::make($request->all(), [
        'party_id' => 'required|numeric',
    ]);
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }
    $searchForParty = Party::where('id',$request->party_id)->where('tenant_id',$searchForMainTenant->id)->first();
    if (!$searchForParty) {
        return response()->json(['message' => 'Party not found'], 404);
    }
    $searchForParty->isactive = !$searchForParty->isactive;
    $searchForParty->save();
    return response()->json(['message' => 'Party status changed successfully'], 200);
}


// public function getPartyTransaction(Request $request){
//     $validator = Validator::make($request->all(), [
//         'party_id' => 'required|numeric',
//     ]);
//     if ($validator->fails()) {
//         return response()->json(['error' => $validator->errors()], 400);
//     }
//     $sales = Sale::where('party_id', $request->party_id)->get();
//     $purchases = Purchase::where('party_id', $request->party_id)->get();

//     return response()->json([
//         'sales' => $sales,
//         'purchases' => $purchases
//     ], 200);
// }

public function getPartyTransaction(Request $request)
{
    $validator = Validator::make($request->all(), [
        'party_id' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $party = Party::find($request->party_id);
    if (!$party) {
        return response()->json(['error' => 'Party not found'], 404);
    }

    $transactions = [];

    // $sales = Sale::where('party_id', $request->party_id)->get();
    $sales = Sale::with('productsales')->where('party_id', $request->party_id)->get();
    foreach ($sales as $sale) {
        $productQuantities = $sale->productsales->pluck('quantity');
        $productCount = $sale->productsales->count();
        $party = Party::find($request->party_id);

        $transactions[] = [
            'party name' => $party->party_name,
            'status' => $sale->status,
            'invoice_no' => $sale->invoice_no,
            'datetime' => $sale->created_at->toDateTimeString(),
            'total' => $sale->total_amount,
            'balance' => $party->opening_balance,
            'Payment Status' => $sale->sales_status,
            'quantity' => $productCount,
        ];
    }
    // $purchases = Purchase::where('party_id', $request->party_id)->get();
    $purchases = Purchase::with('purchaseproducts')->where('party_id', $request->party_id)->get();
    // return response()->json($purchases);

    foreach ($purchases as $purchase) {
        $productCount = $sale->productsales->count();
        $party = Party::find($request->party_id);
        $transactions[] = [
            'party name' => $party->party_name,
            'status' => $purchase->status,
            'invoice_no' => $purchase->invoice_no,
            'datetime' => $purchase->created_at->toDateTimeString(),
            'total' => $purchase->total_amount,
            'balance' => $party->opening_balance,
            'Payment Status' => "Paid",
            'quantity' => $productCount,
        ];
    }

    // Optional: sort transactions by datetime
    usort($transactions, function ($a, $b) {
        return strtotime($a['datetime']) <=> strtotime($b['datetime']);
    });

    return response()->json([
        'opening_balance' => $party->opening_balance,
        'transactions' => $transactions,
    ], 200);
}


}

