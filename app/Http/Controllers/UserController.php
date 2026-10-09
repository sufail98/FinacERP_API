<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\POSUser;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\Currency;
use App\Models\CurrencyConversion;
use App\Models\SalesSettings;
use App\Models\AccountLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\Traits\DatabaseContext;
use App\Services\DatabaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    use DatabaseContext;
    public function index()
    {
        $users = User::with('userGroup')->get();

        // Transform the data to include user group name
        $usersWithGroup = $users->map(function ($user) {
            $userData = $user->toArray();
            $userData['userGroupName'] = $user->userGroup ? $user->userGroup->usergroupName : '';
            return $userData;
        });

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Users',
            'data' => $usersWithGroup,
        ]);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Users',
            'data' => $user,
        ]);
    }
    public function Applogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userName' => 'required|string',
            'Password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('userName', $request->userName)->first();
        if (!$user || !Hash::check($request->Password, $user->Password)) {
            return response()->json([
                'status' => 401,
                'error' => true,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!$user->ActiveStatus) {
            return response()->json([
                'status' => 403,
                'error' => true,
                'message' => 'User is deactivated',
            ], 403);
        }

        // $token = $user->createToken('authToken')->plainTextToken;
        $token = $user->createToken(
            'authToken',
            [],
            Carbon::now()->addHours(24)
        )->plainTextToken;


        // Build branch details from user's branchIds
        $branches = [];
        $branchIds = is_array($user->branchIds) ? $user->branchIds : [];
        if (!empty($branchIds)) {
            $branchRecords = Branch::whereIn('branchId', $branchIds)->orderBy('branchId', 'asc')->get(['branchId', 'branchName', 'dbName', 'branchCode']);
            $branches = $branchRecords->map(function ($branch) {
                return [
                    'branchId' => (string) $branch->branchId,
                    'branchName' => $branch->branchName,
                    'branchCode' => $branch->branchCode,
                    'dbNameEncrypted' => Crypt::encryptString((string) $branch->dbName),
                    // 'dbName' => $branch->dbName,
                ];
            })->values();
        }

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => $user,
                'branches' => $branches,
                'dashboardUrl' => 'https://test.finacerp.com/#/dashboard'
            ],
        ]);
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userName' => 'required|string',
            'Password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('userName', $request->userName)->first();
        if (!$user || !Hash::check($request->Password, $user->Password)) {
            return response()->json([
                'status' => 401,
                'error' => true,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!$user->ActiveStatus) {
            return response()->json([
                'status' => 403,
                'error' => true,
                'message' => 'User is deactivated',
            ], 403);
        }

        // $token = $user->createToken('authToken')->plainTextToken;


        $now = Carbon::now('Asia/Kolkata');

        // Set 5:30 AM IST today
        $expiry = Carbon::today('Asia/Kolkata')->setTime(05, 30, 0);

        // If already past 5:30 AM, set to tomorrow 5:30 AM
        if ($now->greaterThanOrEqualTo($expiry)) {
            $expiry->addDay();
        }

        // ✅ Save as UTC to DB
        $tokenResult            = $user->createToken('authToken');
        $tokenModel             = $tokenResult->accessToken;
        $tokenModel->expires_at = $expiry->utc();
        $tokenModel->save();
        $token = $tokenResult->plainTextToken;

        $branches = [];
        $branchIds = is_array($user->branchIds) ? $user->branchIds : [];
        if (!empty($branchIds)) {
            $branchRecords = Branch::whereIn('branchId', $branchIds)->orderBy('branchId', 'asc')->get(['branchId', 'branchName', 'dbName', 'branchCode']);
            $branches = $branchRecords->map(function ($branch) {
                return [
                    'branchId' => (string) $branch->branchId,
                    'branchName' => $branch->branchName,
                    'branchCode' => $branch->branchCode,
                    'dbNameEncrypted' => Crypt::encryptString((string) $branch->dbName),
                    // 'dbName' => $branch->dbName,
                ];
            })->values();
        }

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => $user,
                'branches' => $branches,
            ],
        ]);
    }

    /**
     * Check if user is authenticated
     */
    public function checkAuth(Request $request)
    {
        // Check if user is authenticated via token
        if ($request->user()) {
            $user = $request->user();

            // Build branch details from user's branchIds
            $branches = [];
            $defaultdata = [];
            $branchIds = is_array($user->branchIds) ? $user->branchIds : [];

            if (!empty($branchIds)) {
                $branchRecords = Branch::whereIn('branchId', $branchIds)->orderBy('branchId', 'asc')
                    ->get();

                foreach ($branchRecords as $branch) {

                    $bankdetails = SalesSettings::where('branchId', $branch->branchId)
                        ->first(['DefaultPaymentMode', 'DefaultBankAccountForInvoicePrint']);

                    $bankdata = null;
                    if ($bankdetails && $bankdetails->DefaultBankAccountForInvoicePrint) {
                        $bankdata = AccountLedger::where('ledgerId', $bankdetails->DefaultBankAccountForInvoicePrint)
                            ->first(['bankaccname', 'ibanno', 'accountNo', 'bankBranchName', 'bankSwiftCode']);
                    }

                    // Add branch info
                    $branches[] = [
                        'branchId'              => (string) $branch->branchId,
                        'companyId'             => $branch->companyId,
                        'currencyId'            => $branch->currencyId,
                        'branchName'            => $branch->branchName,
                        'branchNameFL'            => $branch->branchNameFL,
                        'address'               => $branch->address,
                        'addressFL'             => $branch->addressFL,
                        'streetName'            => $branch->streetName,
                        'streetNameFL'          => $branch->streetNameFL,
                        'buildingNo'            => $branch->buildingNo,
                        'buildingNoFL'          => $branch->buildingNoFL,
                        'additionalNumber'      => $branch->additionalNumber,
                        'additionalNumberFL'    => $branch->additionalNumberFL,
                        'cityName'              => $branch->cityName,
                        'cityNameFL'            => $branch->cityNameFL,
                        'district'              => $branch->district,
                        'districtFL'            => $branch->districtFL,
                        'country'               => $branch->country,
                        'countryFL'            => $branch->countryFL,
                        'postalCode'           => $branch->postalCode,
                        'phoneNo'              => $branch->phoneNo,
                        'fax'                  => $branch->fax,
                        'mobile'               => $branch->mobile,
                        'email'                => $branch->email,
                        'web'                  => $branch->web,
                        'taxNo'                => $branch->taxNo,
                        'panNo'                => $branch->panNo,
                        'crNo'                 => $branch->crNo,
                        'logo'                 => $branch->logo,
                        'mainBranch'           => $branch->mainBranch,
                        'startDate'            => $branch->startDate,
                        'activeStatus'         => $branch->activeStatus,
                        'subscriptionNo'       => $branch->subscriptionNo,
                        'createdDate'          => $branch->createdDate,
                        'createdUser'          => $branch->createdUser,
                        'modifiedDate'         => $branch->modifiedDate,
                        'modifiedUser'         => $branch->modifiedUser,
                        'branchCode'           => $branch->branchCode,
                        'maindb'          => Crypt::encryptString(env('finac_db', 'default_db_name')),
                        // 'maindb'          => Crypt::encryptString(env('rashidpppmna_finac_db', 'default_db_name')),
                        'dbNameEncrypted' => Crypt::encryptString((string) $branch->dbName),
                        'bankdata' => $bankdata,
                    ];

                    // Switch to branch DB
                    // $switchResult = DatabaseService::switchToDatabase(
                    //     Crypt::encryptString($branch->dbName)
                    // );

                    // if ($switchResult['success']) {
                    // }

                    // Fetch active financial year
                    $activeYear = FinancialYear::where('closed', false)->first();
                    $Currency = Currency::where('currencyId', $branch->currencyId)->first();
                    $CurrencyConversion = CurrencyConversion::where('currencyId', $branch->currencyId)
                        ->where('branchId', $branch->branchId)
                        ->orderBy('date', 'desc')
                        ->first();

                    if ($activeYear || $Currency) {
                        $defaultdata[] = [
                            'branchId'           => (string) $branch->branchId,
                            'branchName'         => $branch->branchName,
                            'financialYear'      => $activeYear,
                            'currency'           => $Currency,
                            'currencyConversion' => $CurrencyConversion,
                        ];
                    }
                }
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'User is logged in',
                'data'    => [
                    'isLoggedIn'  => true,
                    'user'        => $user,
                    'branches'    => $branches,
                    'defaultdata' => $defaultdata,
                ]
            ]);
        }

        // Check if Authorization header exists
        $token = $request->bearerToken();
        if ($token) {
            // Try to find the token in database
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            // if ($personalAccessToken && !$personalAccessToken->expires_at) {
            if (
                $personalAccessToken &&
                (
                    is_null($personalAccessToken->expires_at) ||
                    $personalAccessToken->expires_at->isFuture()
                )
            ) {

                // Token exists and is not expired, get the user
                $user = $personalAccessToken->tokenable;
                if ($user) {
                    // Build branch details from user's branchIds
                    $branches = [];
                    $defaultdata = [];
                    $branchIds = is_array($user->branchIds) ? $user->branchIds : [];
                    if (!empty($branchIds)) {
                        $branchRecords = Branch::whereIn('branchId', $branchIds)->orderBy('branchId', 'asc')->get();
                        foreach ($branchRecords as $branch) {

                            $bankdetails = SalesSettings::where('branchId', $branch->branchId)
                                ->first(['DefaultPaymentMode', 'DefaultBankAccountForInvoicePrint']);

                            $bankdata = null;
                            if ($bankdetails && $bankdetails->DefaultBankAccountForInvoicePrint) {
                                $bankdata = AccountLedger::where('ledgerId', $bankdetails->DefaultBankAccountForInvoicePrint)
                                    ->first(['bankaccname', 'ibanno', 'accountNo', 'bankBranchName', 'bankSwiftCode']);
                            }
                            $branches[] = [
                                'branchId'              => (string) $branch->branchId,
                                'companyId'             => $branch->companyId,
                                'currencyId'            => $branch->currencyId,
                                'branchName'            => $branch->branchName,
                                'branchNameFL'            => $branch->branchNameFL,
                                'address'               => $branch->address,
                                'addressFL'             => $branch->addressFL,
                                'streetName'            => $branch->streetName,
                                'streetNameFL'          => $branch->streetNameFL,
                                'buildingNo'            => $branch->buildingNo,
                                'buildingNoFL'          => $branch->buildingNoFL,
                                'additionalNumber'      => $branch->additionalNumber,
                                'additionalNumberFL'    => $branch->additionalNumberFL,
                                'cityName'              => $branch->cityName,
                                'cityNameFL'            => $branch->cityNameFL,
                                'district'              => $branch->district,
                                'districtFL'            => $branch->districtFL,
                                'country'               => $branch->country,
                                'countryFL'            => $branch->countryFL,
                                'postalCode'           => $branch->postalCode,
                                'phoneNo'              => $branch->phoneNo,
                                'fax'                  => $branch->fax,
                                'mobile'               => $branch->mobile,
                                'email'                => $branch->email,
                                'web'                  => $branch->web,
                                'taxNo'                => $branch->taxNo,
                                'panNo'                => $branch->panNo,
                                'crNo'                 => $branch->crNo,
                                'logo'                 => $branch->logo,
                                'mainBranch'           => $branch->mainBranch,
                                'startDate'            => $branch->startDate,
                                'activeStatus'         => $branch->activeStatus,
                                'subscriptionNo'       => $branch->subscriptionNo,
                                'createdDate'          => $branch->createdDate,
                                'createdUser'          => $branch->createdUser,
                                'modifiedDate'         => $branch->modifiedDate,
                                'modifiedUser'         => $branch->modifiedUser,
                                'branchCode'           => $branch->branchCode,
                                'maindb'          => Crypt::encryptString(env('finac_db', 'default_db_name')),
                                // 'maindb'          => Crypt::encryptString(env('rashidpppmna_finac_db', 'default_db_name')),
                                'dbNameEncrypted' => Crypt::encryptString((string) $branch->dbName),
                                'bankdata' => $bankdata,
                            ];

                            // $switchResult = DatabaseService::switchToDatabase(
                            //     Crypt::encryptString($branch->dbName)
                            // );

                            // if ($switchResult['success']) {
                            // }


                            $activeYear = FinancialYear::where('closed', false)->first();
                            $Currency = Currency::where('currencyId', $branch->currencyId)->first();
                            $CurrencyConversion = CurrencyConversion::where('currencyId', $branch->currencyId)
                                ->where('branchId', $branch->branchId)
                                ->orderBy('date', 'desc')
                                ->first();

                            if ($activeYear || $Currency) {
                                $defaultdata[] = [
                                    'branchId'           => (string) $branch->branchId,
                                    'branchName'         => $branch->branchName,
                                    'financialYear'      => $activeYear,
                                    'currency'           => $Currency,
                                    'currencyConversion' => $CurrencyConversion,
                                ];
                            }
                        }
                    }
                    return response()->json([
                        'status' => 200,
                        'error' => false,
                        'message' => 'User is logged in',
                        'data' => [
                            'isLoggedIn' => true,
                            'user' => $user,
                            'branches' => $branches,
                            'defaultdata' => $defaultdata,
                        ]
                    ]);
                }
            }
        }

        // User is not logged in
        return response()->json([
            'status' => 401,
            'error' => true,
            'message' => 'User is not logged in',
            'data' => [
                'isLoggedIn' => false,
                'message' => 'No valid authentication found'
            ]
        ], 401);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Me',
            'data' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Logged out',
        ]);
    }

    /**
     * Check if username already exists
     */
    private function checkUsernameExists($username, $excludeUserId = null)
    {
        $query = User::whereRaw('LOWER("userName") = ?', [strtolower($username)]);

        if ($excludeUserId) {
            $query->where('userId', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * Check username availability
     */
    public function checkUsername(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userName' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $username = $request->userName;
        $exists = $this->checkUsernameExists($username);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => $exists ? 'Username already exists' : 'Username is available',
            'data' => [
                'userName' => $username,
                'available' => !$exists,
                'exists' => $exists,
            ],
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Check if username already exists
            if ($this->checkUsernameExists($request->userName)) {
                return response()->json([
                    'status' => 422,
                    'error' => true,
                    'message' => 'Username already exists',
                ], 422);
            }

            // Normalize branchIds to array if provided as JSON string or comma-separated
            $branchIdsInput = $request->input('branchIds');
            if (is_string($branchIdsInput)) {
                $decoded = json_decode($branchIdsInput, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $request->merge(['branchIds' => $decoded]);
                } else {
                    $parts = array_filter(array_map('trim', preg_split('/[,\s]+/', $branchIdsInput)), 'strlen');
                    if (!empty($parts)) {
                        $request->merge(['branchIds' => array_map('intval', $parts)]);
                    }
                }
            } elseif (is_numeric($branchIdsInput)) {
                $request->merge(['branchIds' => [(int) $branchIdsInput]]);
            }

            $user = new User();
            $user->userId = (User::max('userId') ?? 0) + 1;
            $user->userName = $request->userName;
            $user->Password = Hash::make($request->Password);
            $user->phoneNo = $request->phoneNo;
            $user->email = $request->email;
            $user->Narration = $request->Narration;
            $user->branchIds = $request->branchIds;
            $user->UserRoleId = $request->UserRoleId;
            $user->ActiveStatus = $request->ActiveStatus ?? 1;
            $user->CreatedDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            $user->CreatedUser = $request->CreatedUser;
            $user->user_lang = $request->user_lang;

            if ($request->hasFile('profilePhoto')) {
                // $path = $request->file('profilePhoto')->store('user_photos', 'public');
                // $user->profilePhoto = $path;


                $file = $request->file('profilePhoto');
                $extension = $file->getClientOriginalExtension();
                $filename = uniqid() . '.' . $extension;

                $destination = public_path('storage/user_photos');
                if (!file_exists($destination)) {
                    mkdir($destination, 0775, true);
                }

                $file->move($destination, $filename);
                $user->profilePhoto = 'user_photos/' . $filename;
            }

            $user->save();

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'User created successfully',
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'User not found',
                ], 404);
            }

            // Check if new username already exists (excluding current user)
            if ($request->has('userName') && $request->userName !== $user->userName) {
                if ($this->checkUsernameExists($request->userName, $id)) {
                    return response()->json([
                        'status' => 422,
                        'error' => true,
                        'message' => 'Username already exists',
                    ], 422);
                }
            }

            // Normalize branchIds to array if provided as JSON string or comma-separated
            $branchIdsInput = $request->input('branchIds');
            if (is_string($branchIdsInput)) {
                $decoded = json_decode($branchIdsInput, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $request->merge(['branchIds' => $decoded]);
                } else {
                    $parts = array_filter(array_map('trim', preg_split('/[,\s]+/', $branchIdsInput)), 'strlen');
                    if (!empty($parts)) {
                        $request->merge(['branchIds' => array_map('intval', $parts)]);
                    }
                }
            } elseif (is_numeric($branchIdsInput)) {
                $request->merge(['branchIds' => [(int) $branchIdsInput]]);
            }

            if ($request->has('userName')) $user->userName = $request->userName;
            if ($request->has('Password') && $request->Password) $user->Password = Hash::make($request->Password);
            if ($request->has('phoneNo')) $user->phoneNo = $request->phoneNo;
            if ($request->has('email')) $user->email = $request->email;
            if ($request->has('Narration')) $user->Narration = $request->Narration;
            if ($request->has('branchIds')) $user->branchIds = $request->branchIds;
            if ($request->has('UserRoleId')) $user->UserRoleId = $request->UserRoleId;
            if ($request->has('ActiveStatus')) $user->ActiveStatus = $request->ActiveStatus;
            if ($request->has('user_lang')) $user->user_lang = $request->user_lang;

            if ($request->hasFile('profilePhoto')) {
                $oldPhotoPath = $user->getRawOriginal('profilePhoto');
                if ($oldPhotoPath && Storage::disk('public')->exists($oldPhotoPath)) {
                    Storage::disk('public')->delete($oldPhotoPath);
                }
                // $path = $request->file('profilePhoto')->store('user_photos', 'public');
                // $user->profilePhoto = $path;

                $file = $request->file('profilePhoto');
                $extension = $file->getClientOriginalExtension();
                $filename = uniqid() . '.' . $extension;

                $destination = public_path('storage/user_photos');
                if (!file_exists($destination)) {
                    mkdir($destination, 0775, true);
                }

                $file->move($destination, $filename);
                $user->profilePhoto = 'user_photos/' . $filename;
            }

            $user->ModifiedDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            $user->ModifiedUser = $request->ModifiedUser;
            $user->save();
            DB::commit();
            $user->refresh();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'User updated successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'User not found',
                ], 404);
            }

            $user->delete();

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        DB::beginTransaction();
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'User not found',
                ], 404);
            }

            // Toggle the ActiveStatus
            // ActiveStatus: 1 = Active, 0 = Inactive
            $user->ActiveStatus = !$user->ActiveStatus;
            $user->ModifiedDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            $user->ModifiedUser = 'system';
            $user->save();

            DB::commit();

            $statusText = $user->ActiveStatus ? 'activated' : 'deactivated';

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => "User {$statusText} successfully"
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::find($request->userId);
            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'User not found',
                ], 404);
            }

            $user->Password = Hash::make($request->Password);
            $user->ModifiedDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            $user->ModifiedUser = $request->ModifiedUser;
            $user->save();

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Password changed successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current database context information
     */
    public function getDatabaseContext(Request $request)
    {
        // Debug: Show all request information
        $debugInfo = [
            'request_method' => $request->method(),
            'request_url' => $request->url(),
            'request_headers' => $request->headers->all(),
            'request_attributes' => $request->attributes->all(),
            'has_database_context' => $this->hasDatabaseContext($request),
            'current_database_connection' => $this->getCurrentDatabaseConnection($request),
            'current_database_name' => $this->getCurrentDatabaseName($request),
            'middleware_ran' => $request->attributes->has('current_database_connection'),
            'all_attributes_keys' => array_keys($request->attributes->all()),
        ];

        // Check if database context is available
        if (!$this->hasDatabaseContext($request)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'No database context available - Debug info below',
                'debug_info' => $debugInfo,
                'error_details' => 'The middleware may not have run or database switching failed'
            ], 400);
        }

        // Get the actual database context
        $context = $this->getDatabaseContext($request);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Current database context',
            'data' => $context,
            'debug_info' => $debugInfo,
        ]);
    }

    /**
     * Test database connection with current context
     */
    public function testDatabaseConnection(Request $request)
    {
        if (!$this->hasDatabaseContext($request)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'No database context available',
            ], 400);
        }

        try {
            // Test the current database connection
            $dbName = $this->getCurrentDatabaseName($request);
            $connection = $this->getCurrentDatabaseConnection($request);

            // Try to get some data from the current database
            $userCount = User::count();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Database connection test successful',
                'data' => [
                    'database_name' => $dbName,
                    'connection_name' => $connection,
                    'user_count' => $userCount,
                    'test_successful' => true
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => 'Database connection test failed',
                'data' => [
                    'error' => $e->getMessage(),
                    'test_successful' => false
                ],
            ], 500);
        }
    }












    public function storedefaultdata()
    {
        DB::beginTransaction();
        try {
            // Insert default company
            $companyData = [
                [
                    'companyName'      => 'Company',
                    'companyNameFL'    => 'Company',
                    'companyAddress'   => 'Address',
                    'phoneNo'          => 1234567890,
                    'AlternativePhoneNo' => 1234567890,
                    'companyLogo'      => '',
                    'createdDate'      => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'modifiedDate'     => null,
                    'modifiedUser'     => null,
                ],
            ];
            DB::table('tbl_Company')->insert($companyData);

            // Insert default branch
            $branchData = [
                [
                    'branchId'          => 1,
                    'companyId'         => 1,
                    'currencyId'        => 30, // SAR
                    'branchName'        => 'Branch 1',
                    'branchNameFL'      => 'Branch 1',
                    'address'           => 'Address',
                    'addressFL'         => 'Address',
                    'streetName'        => 'Street',
                    'streetNameFL'      => 'Street',
                    'buildingNo'        => '123',
                    'buildingNoFL'      => '123',
                    'additionalNumber'  => '11',
                    'additionalNumberFL' => '11',
                    'cityName'          => 'City',
                    'cityNameFL'        => 'City',
                    'district'          => 'District Name',
                    'districtFL'        => 'District Name',
                    'country'           => 'country Name',
                    'countryFL'         => 'country Name',
                    'postalCode'        => '123456',
                    'postalCodeFL'      => '123456',
                    'phoneNo'           =>  1234567890,
                    'fax'               =>  '001-000-2112',
                    'mobile'            =>  1234567890,
                    'email'             =>  'dummy@gmail.com',
                    'web'               =>  'www.dummy.com',
                    'taxNo'             =>  '27ABCDE1234F1Z5',
                    'panNo'             =>  'AFZPK7190K',
                    'crNo'              =>  '55656',
                    'logo'              =>  '',
                    'mainBranch'        =>  true,
                    'startDate'         =>  '2025-08-01',
                    'dbName'            =>  null,
                    // 'dbName'            =>  'rashidpppmna_finac_branch1_db',
                    'activeStatus'      =>  true,
                    'subscriptionNo'    =>  'SUBS-2025-001',
                    'createdDate'      => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'createdUser'      => null,
                    'modifiedDate'     => null,
                    'modifiedUser'     => null,
                ],
            ];
            DB::table('tbl_Branch')->insert($branchData);

            // Insert default User Group
            $userData = [
                [
                    'usergroupId'     => 1,
                    'usergroupName'   => 'Super Admin',
                    'activeStatus'    => 1,
                    'CreatedDate'    => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'    => null,
                    'ModifiedDate'    => null,
                    'ModifiedUser'    => null,
                ],
            ];
            DB::table('tbl_UserGroup')->insert($userData);

            // Insert default user
            $userData = [
                [
                    'userId'         => 1,
                    'userName'       => 'user',
                    'Password'       => '$2y$12$vRdxsvjODogTnKxUW2MHN.iKv0dmU3bsd4Uq3gMm0a2HHwQ7TYDTK', //hashed 123456
                    'phoneNo'        => 1234567890,
                    'email'          =>  'user@gmail.com',
                    'profilePhoto'   => null,
                    'Narration'      => null,
                    'branchIds'      => '["1"]',
                    'UserRoleId'     => 1,
                    'ActiveStatus'   => 1,
                    'CreatedDate'    => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'    => null,
                    'ModifiedDate'    => null,
                    'ModifiedUser'    => null,
                ],
            ];
            DB::table('tbl_User')->insert($userData);

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Default data added successfully',
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }








    // Pos
    public function Poslogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userName' => 'required|string',
            'Password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = POSUser::where('userName', $request->userName)->first();
        if (!$user || !Hash::check($request->Password, $user->Password)) {
            return response()->json([
                'status' => 401,
                'error' => true,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!$user->Active) {
            return response()->json([
                'status' => 403,
                'error' => true,
                'message' => 'User is deactivated',
            ], 403);
        }

        // $token = $user->createToken('authToken')->plainTextToken;
        $token = $user->createToken(
            'authToken',
            [],
            Carbon::now()->addHours(24)
        )->plainTextToken;


        // Build branch details from user's branchIds
        $branches = [];
        $branchIds = is_array($user->branchIds) ? $user->branchIds : [];
        if (!empty($branchIds)) {
            $branchRecords = Branch::whereIn('branchId', $branchIds)->get(['branchId', 'branchName', 'dbName']);
            $branches = $branchRecords->map(function ($branch) {
                return [
                    'branchId' => (string) $branch->branchId,
                    'branchName' => $branch->branchName,
                    'dbNameEncrypted' => Crypt::encryptString((string) $branch->dbName),
                ];
            })->values();
        }

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => $user,
                'branches' => $branches,
            ],
        ]);
    }

    public function appDashboard(Request $request)
    {
        // dd($request->all());
        $sales = DB::select('SELECT * FROM get_totalamount_and_sales_countby_date_branch(?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->branchId          // branchId
        ]);

        $purchase = DB::select('SELECT * FROM get_purchase_totalamount_and_countby_date_branch(?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->branchId          // branchId
        ]);

        $financesettings = collect(DB::select('SELECT * FROM public.get_all_finance_settings()'));

        $finance = $financesettings
            ->where('branchId', $request->branchId)
            ->first();

        $DefaultCashAccount = $finance->DefaultCashAccount ?? null;
        $DefaultBankAccount = $finance->DefaultBankAccount ?? null;

        if (!$DefaultCashAccount || !$DefaultBankAccount) {

            $cashBalance = collect(DB::select('SELECT * FROM get_total_cash_bank_balance_bygroup(?,?)', [
                8,                           // groupId   Cash In Hand id = 8
                $request->branchId          // branchId
            ]))->first();

            $bankBalance = collect(DB::select('SELECT * FROM get_total_cash_bank_balance_bygroup(?,?)', [
                5,                           // groupId  Bank account id = 5
                $request->branchId          // branchId
            ]))->first();
        } else {

            $cashBalance = collect(DB::select('SELECT * FROM get_current_ledger_balance(?,?,?)', [
                $DefaultCashAccount,          // ledgerId
                $request->branchId,          // branchId
                $request->currencyId         // currencyId
            ]))->first();

            $bankBalance = collect(DB::select('SELECT * FROM get_current_ledger_balance(?,?,?)', [
                $DefaultBankAccount,          // ledgerId
                $request->branchId,          // branchId
                $request->currencyId         // currencyId
            ]))->first();
        }

        $customerbalance = DB::select('SELECT * FROM public.accountgroup_detailed_report(?, ?, ?, ?, ?, ?, ?, ?)', [
            $request->fromDate,
            $request->toDate,
            (int) $request->branchId,
            44, // customer group id
            (int) $request->currencyId,
            (bool) $request->isMainGroup,
            (bool) $request->isShowOpeningBalance,
            'All'
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'dashboard Datas',
            'sales' => $sales,
            'purchase' => $purchase,
            'cashBalance' => $cashBalance ?? null,
            'bankBalance' => $bankBalance ?? null,
            'customerBalance' => $customerbalance,
        ]);
    }
    public function dashboard(Request $request)
    {
        $sales = DB::select(
            'SELECT * FROM get_totalamount_and_sales_countby_date_branch(?,?,?,?)',
            [
                $request->fromDate,
                $request->toDate,
                $request->branchId,
                $request->boolean('isMainBranch')
            ]
        );

        $purchase = DB::select(
            'SELECT * FROM get_purchase_totalamount_and_countby_date_branch(?,?,?)',
            [
                $request->fromDate,
                $request->toDate,
                $request->branchId
            ]
        );

        $financesettings = collect(
            DB::select('SELECT * FROM public.get_all_finance_settings()')
        );

        $generalSettings = collect(
            DB::select('SELECT * FROM public.get_all_general_settings()')
        );

        $finance = $financesettings
            ->where('branchId', $request->branchId)
            ->first();

        $general = $generalSettings
            ->where('branchId', $request->branchId)
            ->first();

        $DefaultCashAccount = $finance->DefaultCashAccount ?? null;
        $DefaultBankAccount = $finance->DefaultBankAccount ?? null;

        /*
     * CASH BALANCE
     *
     * If showCurrentDayCashbalanceInDashboard = true,
     * get cash movement ONLY for toDate.
     */
        if (($general->showCurrentDayCashbalanceInDashboard ?? false) == true) {

            $cashBalance = collect(DB::select(
                'SELECT * FROM get_total_cash_bank_balance_bygroup(?, ?, ?, ?)',
                [
                    8,                           // Cash group
                    (int) $request->branchId,
                    $request->toDate,            // from date = toDate
                    $request->toDate             // to date   = toDate
                ]
            ))->first();
        } else {

            /*
         * Normal Cash Balance
         */
            if (!$DefaultCashAccount) {

                $cashBalance = collect(DB::select(
                    'SELECT * FROM get_total_cash_bank_balance_bygroup(?, ?)',
                    [
                        8,
                        (int) $request->branchId
                    ]
                ))->first();
            } else {

                $cashBalance = collect(DB::select(
                    'SELECT * FROM get_current_ledger_balance(?, ?, ?)',
                    [
                        $DefaultCashAccount,
                        (int) $request->branchId,
                        (int) $request->currencyId
                    ]
                ))->first();
            }
        }

        /*
     * BANK BALANCE
     *
     * Bank calculation is independent from cash calculation.
     */
        if (!$DefaultBankAccount) {

            $bankBalance = collect(DB::select(
                'SELECT * FROM get_total_cash_bank_balance_bygroup(?, ?)',
                [
                    5,                          // Bank group
                    (int) $request->branchId
                ]
            ))->first();
        } else {

            $bankBalance = collect(DB::select(
                'SELECT * FROM get_current_ledger_balance(?, ?, ?)',
                [
                    $DefaultBankAccount,
                    (int) $request->branchId,
                    (int) $request->currencyId
                ]
            ))->first();
        }

        /*
     * CUSTOMER BALANCE
     */
        $customerbalance = DB::select(
            'SELECT * FROM public.accountgroup_detailed_report(?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $request->fromDate,
                $request->toDate,
                (int) $request->branchId,
                44,
                (int) $request->currencyId,
                (bool) $request->isMainGroup,
                (bool) $request->isShowOpeningBalance,
                'All'
            ]
        );

        /*
     * PAYABLE / RECEIVABLE
     */
        $payableReceivable = collect(
            DB::select(
                'SELECT * FROM public.get_dashboard_payable_receivable(?, ?, ?, ?, ?, ?)',
                [
                    $request->fromDate,
                    $request->toDate,
                    (int) $request->branchId,
                    (int) $request->currencyId,
                    (bool) $request->isShowOpeningBalance,
                    'All'
                ]
            )
        )->first();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'dashboard Datas',
            'sales' => $sales,
            'purchase' => $purchase,
            'cashBalance' => $cashBalance ?? null,
            'bankBalance' => $bankBalance ?? null,
            'customerBalance' => $customerbalance,

            // These were reversed in your original code
            'totalPayable' => $payableReceivable->totalPayable ?? 0,
            'totalReceivable' => $payableReceivable->totalReceivable ?? 0,
        ]);
    }
    public function cashBalance(Request $request)
    {
        return response()->json([
            'status' => 200,
            'error' => false,
            'cashBalance' => DB::select(
                'SELECT * FROM get_total_cash_bank_balance_bygroup(?,?)',
                [8, $request->branchId]
            )
        ]);
    }
    public function getBankBalance(Request $request)
    {
        return response()->json([
            'status' => 200,
            'error' => false,
            'bankBalance' => DB::select(
                'SELECT * FROM get_total_cash_bank_balance_bygroup(?,?)',
                [5, $request->branchId]
            )
        ]);
    }
    public function getCustomerBalance(Request $request)
    {
        $customerBalance = DB::select(
            'SELECT * FROM public.accountgroup_detailed_report(?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $request->fromDate,
                $request->toDate,
                (int) $request->branchId,
                44,                                     // Customer Group ID
                (int) $request->currencyId,
                (bool) $request->isMainGroup,
                (bool) $request->isShowOpeningBalance,
                'All'
            ]
        );

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Customer Balance Data',
            'customerBalance' => $customerBalance
        ]);
    }
    public function getSupplierBalance(Request $request)
    {
        $supplierBalance = DB::select(
            'SELECT * FROM public.accountgroup_detailed_report(?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $request->fromDate,
                $request->toDate,
                (int) $request->branchId,
                43,                                     // Supplier Group ID
                (int) $request->currencyId,
                (bool) $request->isMainGroup,
                (bool) $request->isShowOpeningBalance,
                'All'
            ]
        );

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Supplier Balance Data',
            'supplierBalance' => $supplierBalance
        ]);
    }

    public function topSellingProducts(Request $request)
    {
        $filterType = $request->input('filterType');
        $branchId   = $request->input('branchId');
        $fromDate   = $request->input('fromDate');
        $toDate     = $request->input('toDate');


        if (!$filterType || !$branchId) {
            return response()->json([
                'status' => 422,
                'error'  => true,
                'message' => 'filterType and branchId are required'
            ], 422);
        }

        if ($filterType === 'custom' && (!$fromDate || !$toDate)) {
            return response()->json([
                'status' => 422,
                'error'  => true,
                'message' => 'fromDate and toDate are required for custom filter'
            ], 422);
        }

        try {
            $data = DB::select(
                'SELECT * FROM get_top_selling_products(?, ?, ?, ?)',
                [
                    $filterType,
                    $branchId,
                    $fromDate,
                    $toDate
                ]
            );

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function salesGraph(Request $request)
    {
        $period       = $request->input('period');
        $branchId     = $request->input('branchId');
        $fromDate     = $request->input('fromDate');
        $toDate       = $request->input('toDate');
        $isMainBranch = $request->boolean('isMainBranch');

        if (!$period || !$branchId) {
            return response()->json([
                'status'  => 422,
                'error'   => true,
                'message' => 'period and branchId are required'
            ], 422);
        }

        if ($period === 'custom' && (!$fromDate || !$toDate)) {
            return response()->json([
                'status'  => 422,
                'error'   => true,
                'message' => 'fromDate and toDate required for custom'
            ], 422);
        }

        $rows = DB::select(
            'SELECT * FROM public.get_sales_graph_ui(?, ?, ?, ?, ?)',
            [
                $period,
                (int) $branchId,
                $isMainBranch,
                $fromDate ?: null,
                $toDate ?: null
            ]
        );

        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'name'  => $row->label,
                'value' => (float) $row->total_amount
            ];
        }

        return response()->json([
            'period'       => $period,
            'isMainBranch' => $isMainBranch,
            'data'         => $data
        ]);
    }
    public function userSales($branchId)
    {
        try {

            $sales = DB::select(
                'SELECT * FROM get_user_wise_sales_latest_year(:branchId)',
                ['branchId' => $branchId]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $sales
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function stockValue(Request $request)
    {
        $inventorySettingsId = $request->inventorySettingsId;

        try {

            $stockData = DB::select(
                'SELECT * FROM fn_calculate_stock_value(:inventorySettingsId)',
                ['inventorySettingsId' => $inventorySettingsId]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $stockData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function lowStock(Request $request)
    {
        try {
            $branchId = $request->branchId ?? null;

            $data = DB::select(
                "SELECT * FROM get_lowest_stock_products(?)",
                [$branchId]
            );

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function productGroupWiseSalesSummary(Request $request)
    {
        try {
            $branchId = $request->branchId;

            $data = DB::select(
                'SELECT * FROM get_today_top10_group1_sales_summary(?)',
                [$branchId]
            );

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}