<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Partygroup;
use App\Models\Productcategory;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;
use App\Models\UserTenantUnit;
use App\Models\TenantUnit;
use App\Models\Otp;
class AuthController extends Controller
{

    
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|digits:10',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }
        
        // Search for user
        $user = User::where('mobile_number', $request->phone_number)->first();
        if ($user) {
            $createToken = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'User logged in successfully',
                'token' => $createToken
            ], 200);
        }

        // Create user
        $user = new User();
        $user->name = $request->has('name') ? $request->name : 'Admin';
        $user->mobile_number = $request->phone_number;
        $user->role_id = 2;
        $user->save();

    
        // Create Tenant
        $tenant = new Tenant();
        $tenant->user_id = $user->id;
        $tenant->phone_number = $request->phone_number;
        $tenant->save();
    
        // Create Tenant Unit
        $tenantUnit = new TenantUnit();
        $tenantUnit->business_name = 'Business Name';
        $tenantUnit->tenant_id = $tenant->id;
        $tenantUnit->phone_number = $request->phone_number;
        $tenantUnit->save();
    
        // Update user with tenant unit ID
        $user->user_tenant_unit_id = $tenantUnit->id;
        $user->save();
    
        // Create UserTenantUnit
        UserTenantUnit::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'tenant_type' => Tenant::class,
        ]);
    
        // Create Product Category
        $productcategory = new Productcategory();
        $productcategory->product_category = 'General';
        $productcategory->tenant_id = $tenant->id;
        $productcategory->save();
    
        // Create Party Group
        $partygroup = new Partygroup();
        $partygroup->group_name = 'General';
        $partygroup->tenant_id = $tenant->id;
        $partygroup->save();
    
        // Generate and store OTP
        $otpCode = rand(100000, 999999);
    
        $existingOtp = Otp::where('user_id', $user->id)->first();
        if ($existingOtp) {
            $existingOtp->otp = $otpCode;
            $existingOtp->save();
        } else {
            $otp = new Otp();
            $otp->user_id = $user->id;
            $otp->otp = $otpCode;
            $otp->save();
        }
        // $createToken = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'User logged in successfully',
            'otp' => $otpCode,
            // 'token' => $createToken
        ], 200);
    }



    


    public function otpVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|digits:10',
            'otp' => 'required|numeric',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }
    
        $user = User::where('mobile_number', $request->phone_number)->first();
    
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }
    
        $otpRecord = Otp::where('user_id', $user->id)
            ->where('otp', $request->otp)
            ->first();
    
        if (!$otpRecord) {
            return response()->json([
                'message' => 'Invalid OTP',
            ], 400);
        }
        $createToken = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'message' => 'OTP verified successfully',
            'token' => $createToken,
        ], 200);
    }
    
   
   
    public function signup(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string',
        'email' => 'required|email|unique:users,email',
        'mobile_number' => 'required|numeric|digits:10|unique:users,mobile_number',
        'password' => 'required|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 400);
    }

    // Create User
    $user = new User();
    $user->name = $request->name;
    $user->email = $request->email;
    $user->mobile_number = $request->mobile_number;
    $user->password = Hash::make($request->password);
    $user->role_id = 2;
    $user->save();

    // Create Tenant
    $tenant = new Tenant();
    $tenant->user_id = $user->id;
    $tenant->phone_number = $request->mobile_number;
    $tenant->save();

    // Update User with Tenant ID
    $tenantUnit = new TenantUnit();
    $tenantUnit->business_name = 'Business Name';
    $tenantUnit->tenant_id = $tenant->id;
    $tenantUnit->phone_number = $request->mobile_number;
    $tenantUnit->save();

    $user->user_tenant_unit_id = $tenantUnit->id;
    $user->save();

    // Insert into UserTenantUnit table
    UserTenantUnit::create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
        'tenant_type' => Tenant::class,
    ]);

    // Create Product Category
    $productcategory = new Productcategory();
    $productcategory->product_category = 'General';
    $productcategory->tenant_id = $tenant->id;
    $productcategory->save();

    // Create Party Group
    $partygroup = new Partygroup();
    $partygroup->group_name = 'General';
    $partygroup->tenant_id = $tenant->id;
    $partygroup->save();

    // Generate Token
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'User created successfully',
        'user' => $user,
        'token' => $token,
    ], 200);
}








public function profile(){
    $user = auth()->user();
    return response()->json([
        'message' => 'User details retrieved successfully',
        'user' => $user
    ], 200);
}



public function staffRoles()
{
    $staffRoles = Role::whereNotIn('id', [1, 2])->get();
    return $staffRoles;
}







// public function addStaff(Request $request)
// {
//     $user = auth()->user();
//     $validator = Validator::make($request->all(), [
//         'emailormobile' => 'required|email|unique:users,email|unique:users,mobile_number',
//     ]);

//     if ($validator->fails()) {
//         return response()->json([
//             'message' => 'Validation failed',
//             'errors' => $validator->errors()
//         ], 400);
//     }
//     $searchMainTanant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();
//     $tenant = TenantUnit::where('tenant_id', $searchMainTanant->id)->where('isactive', 1)->first();


//     $newUser = new User();
//     $newUser->email = $request->email;
//     $newUser->mobile_number = $request->mobile_number;
//     $newUser->user_tenant_unit_id = $tenant->id;

//     // if ($request->hasFile('profile_photo')) {
//     //     $file = $request->file('profile_photo');
//     //     $filename = time() . '_' . $file->getClientOriginalName();
//     //     $path = $file->storeAs('profile_photo', $filename, 'public'); 
//     //     $newUser->profile_photo = 'storage/' . $path; 
//     // }
//     $newUser->save();

//     return response()->json(['message' => 'Staff member created successfully'], 200);
// }




public function addStaff(Request $request)
{
    $user = auth()->user();
    $validator = Validator::make($request->all(), [
        'emailormobile' => 'required',
    ]);
    
    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 400);
    }
    $input = $request->emailormobile;
    if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
        $field = 'email';
        $uniqueValidation = 'unique:users,email';
    } elseif (preg_match('/^\d{10}$/', $input)) {
        // Input is a mobile number
        $field = 'mobile_number';
        $uniqueValidation = 'unique:users,mobile_number';
    } else {
        return response()->json([
            'message' => 'Invalid email or mobile number format'
        ], 400);
    }
    $validator = Validator::make($request->all(), [
        'emailormobile' => $uniqueValidation,
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 400);
    }
    $searchMainTenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();
    $tenant = TenantUnit::where('tenant_id', $searchMainTenant->id)->where('isactive', 1)->first();

    if (!$tenant) {
        return response()->json(['message' => 'Tenant not found'], 404);
    }
    $newUser = new User();
    $newUser->user_tenant_unit_id = $tenant->id;

    if ($field === 'email') {
        $newUser->email = $input;
    } else {
        $newUser->mobile_number = $input;
    }

    // Uncomment this block if profile photo upload is needed
    // if ($request->hasFile('profile_photo')) {
    //     $file = $request->file('profile_photo');
    //     $filename = time() . '_' . $file->getClientOriginalName();
    //     $path = $file->storeAs('profile_photo', $filename, 'public'); 
    //     $newUser->profile_photo = 'storage/' . $path; 
    // }

    $newUser->save();
    return response()->json(['message' => 'Staff member created successfully'], 200);
}




//  public function getAllStaff()
//  {
//      $user = auth()->user();
//      $mainTenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();
//      $tenants = TenantUnit::where('tenant_id', $mainTenant->id)->get();
     
//      if (!$tenant) {
//          return response()->json(['message' => 'Tenant not found'], 404);
//      }
//      $users = User::where('tenant_id', $tenant->id)
//          ->get()
//          ->map(function ($user) {
//              $user->role = $user->role_id == 2 ? 'Admin' : ($user->role_id == 3 ? 'Staff' : 'Unknown');
//              return $user;
//          });
//      return response()->json($users, 200);
//  }
 



public function getAllStaff()
{
    $user = auth()->user();
    $mainTenant = Tenant::where('user_id', $user->id)->where('isactive', 1)->first();
    $tenantUnits = TenantUnit::where('tenant_id', $mainTenant->id)->get();
    if (!$mainTenant) {
        return response()->json(['message' => 'Tenant not found'], 404);
    }
    $tenantUnitIds = $tenantUnits->pluck('id')->toArray();
    $users = User::whereIn('user_tenant_unit_id', $tenantUnitIds)
        ->get()
        ->map(function ($user) {
            $user->role = $user->role_id == 2 ? 'Admin' : ($user->role_id == 3 ? 'Staff' : 'Unknown');
            return $user;
        });
    return response()->json($users, 200);
}



public function updateStaffDetails(Request $request)
{
    $staff = User::where('id', $request->staff_id)->first();

    if (!$staff) {
        return response()->json([
            'message' => 'Staff member not found'
        ], 404);
    }
    if($staff->role_id == 2){
        return response()->json([
            'message' => 'You cannot update the main admin'
        ], 400);
    }
    $validator = Validator::make($request->all(), [
        'staff_id' => 'required|numeric|exists:users,id',
        'name' => 'required|string',
        'email' => 'required|email|unique:users,email,' . $staff->id,
        'mobile_number' => 'required|numeric|digits:10|unique:users,mobile_number,' . $staff->id,
        'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
        'role_id' => 'required|numeric|exists:roles,id'
    ]);
    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 400);
    }
    if ($request->hasFile('profile_photo')) {
        if ($staff->profile_photo) {
            $oldPhotoPath = storage_path('app/public/' . $staff->profile_photo);
            
            if (file_exists($oldPhotoPath)) {
                if (unlink($oldPhotoPath)) {
                    \Log::info('Deleted old image: ' . $oldPhotoPath);
                } else {
                    \Log::error('Failed to delete: ' . $oldPhotoPath);
                }
            } else {
                \Log::warning('Old image not found: ' . $oldPhotoPath);
            }
        }
        $file = $request->file('profile_photo');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('profile_photo', $filename, 'public');
        $staff->profile_photo = 'storage/' . $path;
    }
    // Update staff details
    $staff->update([
        'name' => $request->name,
        'email' => $request->email,
        'mobile_number' => $request->mobile_number,
        'role_id' => $request->role_id,
    ]);
    return response()->json(['message' => 'Staff member updated successfully'], 200);
}









public function deleteStaff(Request $request){
    $staff = User::where('id',$request->staff_id)->first();

    if (!$staff) {
        return response()->json([
            'message' => 'Staff member not found'
        ], 404);
    }
    $staff->delete();
    return response()->json(['message' => 'Staff member deleted successfully'], 200);
  }





}
