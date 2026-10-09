<?php

namespace App\Http\Controllers;

use App\Models\AccountLedger;
use App\Models\ShippingAddress;
use App\Models\Partybalance;
use App\Models\LedgerPosting;
use App\Models\AccountGroup;
use App\Models\LedgerDocuments;
use Exception;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpParser\Node\Stmt\Return_;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Imports\AccountLedgerBulkImport;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class AccountLedgerController extends Controller
{
    /**
     * List all AccountLedger
     */
    public function All($id)
    {
        $AccountLedger = DB::select('SELECT * FROM public.get_all_account_ledgers(?)', [$id]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'AccountLedger retrieved successfully',
            'data' => $AccountLedger,
        ]);
    }
    /**
     * List AccountLedger
     */
    public function index(Request $request)
    {
        // show without posted group ids ledgers
        //  Expect posted ids as array, e.g. { "group_ids": [5, 6, 28, 29] }
        $groupIds = $request->input('group_ids', []);
        $branchId = $request->input('branchId');

        if (empty($groupIds)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'No group IDs provided',
                'data' => []
            ], 400);
        }

        if (!$branchId) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Branch ID is required',
                'data' => []
            ], 400);
        }

        // Create placeholders for safe parameter binding
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));

        $sql = "
            WITH RECURSIVE GroupInMainGroupP AS (
                -- Base case
                SELECT
                    \"groupId\",
                    \"groupUnder\",
                    1 AS \"HierarchyLevel\",
                    ARRAY[\"groupId\"] AS path
                FROM \"tbl_AccountGroup\"
                WHERE \"groupId\" IN ($placeholders)

                UNION ALL

                -- Recursive case
                SELECT
                    e.\"groupId\",
                    e.\"groupUnder\",
                    g.\"HierarchyLevel\" + 1,
                    g.path || e.\"groupId\"
                FROM \"tbl_AccountGroup\" e
                JOIN GroupInMainGroupP g
                    ON e.\"groupUnder\" = g.\"groupId\"
                WHERE
                    g.\"HierarchyLevel\" < 10
                    AND NOT e.\"groupId\" = ANY (g.path)   -- 🔥 stops infinite loop
            )

            SELECT DISTINCT
                A.\"groupId\",
                A.\"accountGroupName\",
                A.\"groupUnder\"
            FROM \"tbl_AccountGroup\" A
            JOIN GroupInMainGroupP G
                ON A.\"groupId\" = G.\"groupId\"
            ORDER BY A.\"accountGroupName\" ASC
            ";

        // Run query with bindings
        $result = DB::select($sql, $groupIds);
        // Extract group IDs from result
        $excludedGroupIds = collect($result)->pluck('groupId')->toArray();

        $AccountLedger = AccountLedger::whereNotIn('groupId', $excludedGroupIds)
            ->whereRaw(
                'EXISTS (
                SELECT 1
                FROM jsonb_array_elements("branchId") AS b
                WHERE (b->>\'branchId\')::int = ?
            )',
                [$branchId]
            )
            ->orderBy('ledgerName', 'asc')
            ->get();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'AccountLedger retrieved successfully',
            'data' => $AccountLedger,
        ]);
    }


    public function bankledgers(Request $request)
    {
        // Expect posted ids as array, e.g. { "group_ids": [5, 6] }
        $groupIds = $request->input('group_ids', []);
        $branchId = $request->input('branchId');

        if (empty($groupIds)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'No group IDs provided',
                'data' => []
            ], 400);
        }

        if (!$branchId) {
            return response()->json([
                'status' => 400,
                'error'  => true,
                'message' => 'Branch ID is required',
                'data'   => []
            ], 400);
        }

        // Create placeholders for safe parameter binding
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));

        $sql = "
            WITH RECURSIVE GroupInMainGroupP AS (
                -- Base case
                SELECT
                    \"groupId\",
                    \"groupUnder\",
                    1 AS \"HierarchyLevel\",
                    ARRAY[\"groupId\"] AS path
                FROM \"tbl_AccountGroup\"
                WHERE \"groupId\" IN ($placeholders)

                UNION ALL

                -- Recursive case
                SELECT
                    e.\"groupId\",
                    e.\"groupUnder\",
                    g.\"HierarchyLevel\" + 1,
                    g.path || e.\"groupId\"
                FROM \"tbl_AccountGroup\" e
                JOIN GroupInMainGroupP g
                    ON e.\"groupUnder\" = g.\"groupId\"
                WHERE
                    g.\"HierarchyLevel\" < 10
                    AND NOT e.\"groupId\" = ANY (g.path)
            )

            SELECT DISTINCT
                A.\"groupId\",
                A.\"accountGroupName\",
                A.\"groupUnder\"
            FROM \"tbl_AccountGroup\" A
            JOIN GroupInMainGroupP G
                ON A.\"groupId\" = G.\"groupId\"
            ORDER BY A.\"accountGroupName\" ASC
            ";

        // Run query with bindings
        $result = DB::select($sql, $groupIds);
        // Extract group IDs from result
        $excludedGroupIds = collect($result)->pluck('groupId')->toArray();

        $AccountLedger = AccountLedger::whereIn('groupId', $excludedGroupIds)
            ->whereRaw(
                'EXISTS (
                SELECT 1
                FROM jsonb_array_elements("branchId") AS b
                WHERE (b->>\'branchId\')::int = ?
            )',
                [$branchId]
            )
            ->orderBy('ledgerName', 'asc')
            ->get();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'AccountLedger retrieved successfully',
            'data' => $AccountLedger,
        ]);
    }

    public function customersupplierLedgers(Request $request)
    {
        $ledgerTypes = $request->input('ledgerTypes', []);
        $branchId    = $request->input('branchId');

        if (empty($ledgerTypes)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Not Found ledger Type',
                'data' => []
            ], 400);
        }

        if (!$branchId) {
            return response()->json([
                'status' => 400,
                'error'  => true,
                'message' => 'Branch ID is required',
                'data'   => []
            ], 400);
        }

        $ledgers = AccountLedger::whereIn('ledgerType', $ledgerTypes)
            ->whereRaw(
                'EXISTS (
							SELECT 1
							FROM jsonb_array_elements("branchId") AS b
							WHERE (b->>\'branchId\')::int = ?
						)',
                [$branchId]
            )
            ->orderBy('ledgerName', 'asc')
            ->get();

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'AccountLedger retrieved successfully',
            'data'    => $ledgers,
        ]);
    }

    /**
     * Get AccountLedger by id
     */
    public function show($id)
    {
        $AccountLedger = AccountLedger::with([
            'ShippingAddress',
            'LedgerDocuments',
        ])->find($id);

        if (!$AccountLedger) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'AccountLedger not found',
            ], 404);
        }

        // ✅ Ledger Image (stored in public/ledger_photos)
        if (!empty($AccountLedger->image)) {
            $AccountLedger->image = asset('ledger_photos/' . $AccountLedger->image);
        }

        // ✅ Ledger Documents (stored in public/documents)
        if ($AccountLedger->LedgerDocuments) {
            $AccountLedger->LedgerDocuments->transform(function ($document) {
                if (!empty($document->file)) {
                    $document->file = asset($document->file);
                }
                return $document;
            });
        }

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'AccountLedger retrieved successfully',
            'data' => $AccountLedger,
        ]);
    }


    /**
     * Create AccountLedger
     */
    public function store(Request $request)
    {
        // dd($request->all());
        // Log::debug('ledger Incoming Request:', $request->all());
        DB::beginTransaction();
        try {

            // Check for duplicate ledgerName
            if (
                $request->has('ledgerName') &&
                AccountLedger::whereRaw('LOWER("ledgerName") = ?', [strtolower($request->ledgerName)])->exists()
            ) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Ledger Name Already Exists !',
                ], 409);
            }

            // Check for duplicate ledgerCode
            if (
                $request->has('ledgerCode') &&
                AccountLedger::whereRaw('LOWER("ledgerCode") = ?', [strtolower($request->ledgerCode)])->exists()
            ) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Ledger Code Already Exists !',
                ], 409);
            }

            $AccountLedger = new AccountLedger();

            $maxledgerId = (AccountLedger::max('ledgerId') ?? 0) + 1;
            $AccountLedger->ledgerId = $maxledgerId;
            if ($request->has('ledgerName')) $AccountLedger->ledgerName = $request->ledgerName;
            if ($request->has('groupId')) $AccountLedger->groupId = $request->groupId;
            if ($request->has('billBybill')) $AccountLedger->billBybill = $request->billBybill;
            if ($request->has('narration')) $AccountLedger->narration = $request->narration;
            if ($request->has('nameArb')) $AccountLedger->nameArb = $request->nameArb;
            if ($request->has('accountNo')) $AccountLedger->accountNo = $request->accountNo;
            if ($request->has('address')) $AccountLedger->address = $request->address;
            if ($request->has('phoneNo')) $AccountLedger->phoneNo = $request->phoneNo;
            if ($request->has('faxNo')) $AccountLedger->faxNo = $request->faxNo;
            if ($request->has('email')) $AccountLedger->email = $request->email;
            if ($request->has('creditPeriod')) $AccountLedger->creditPeriod = $request->creditPeriod;
            if ($request->has('creditLimit')) $AccountLedger->creditLimit = $request->creditLimit;
            if ($request->has('pricingLevelId')) $AccountLedger->pricingLevelId = $request->pricingLevelId;
            if ($request->has('currencyId')) $AccountLedger->currencyId = $request->currencyId;
            if ($request->has('interestOrNot')) $AccountLedger->interestOrNot = $request->interestOrNot;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('public/ledger_photos', $filename);
                $AccountLedger->image = $filename;
            }

            $branchDetails = [];

            if ($request->filled('branchDetails') && is_array($request->branchDetails)) {
                $branchDetails = collect($request->branchDetails)
                    ->map(function ($item) {
                        return [
                            'branchId'        => (int) $item['branchId'],
                            'openingBalance'  => (float) $item['openingBalance'],
                            'crOrDr'          => $item['crOrDr'],
                        ];
                    })
                    ->values()
                    ->toArray();

                $AccountLedger->branchId = $branchDetails; // JSON stored
            }

            if ($request->has('marketId')) $AccountLedger->marketId = $request->marketId;
            $AccountLedger->default = false;
            if ($request->has('tinNumber')) $AccountLedger->tinNumber = $request->tinNumber;
            if ($request->has('cstNumber')) $AccountLedger->cstNumber = $request->cstNumber;
            if ($request->has('panNumber')) $AccountLedger->panNumber = $request->panNumber;
            if ($request->has('extraDate')) $AccountLedger->extraDate = $request->extraDate;
            if ($request->has('extra1')) $AccountLedger->extra1 = $request->extra1;
            if ($request->has('extra2')) $AccountLedger->extra2 = $request->extra2;
            if ($request->has('areaId')) $AccountLedger->areaId = $request->areaId;
            if ($request->has('ledgerCode')) $AccountLedger->ledgerCode = $request->ledgerCode;
            if ($request->has('BuildingNo')) $AccountLedger->BuildingNo = $request->BuildingNo;
            if ($request->has('AdditionalNo')) $AccountLedger->AdditionalNo = $request->AdditionalNo;
            if ($request->has('StreetName')) $AccountLedger->StreetName = $request->StreetName;
            if ($request->has('PostboxNo')) $AccountLedger->PostboxNo = $request->PostboxNo;
            if ($request->has('CityName')) $AccountLedger->CityName = $request->CityName;
            if ($request->has('Country')) $AccountLedger->Country = $request->Country;
            if ($request->has('creditLimitStatus')) $AccountLedger->creditLimitStatus = $request->creditLimitStatus;
            if ($request->has('bankaccname')) $AccountLedger->bankaccname = $request->bankaccname;
            if ($request->has('bankname')) $AccountLedger->bankname = $request->bankname;
            if ($request->has('ibanno')) $AccountLedger->ibanno = $request->ibanno;
            if ($request->has('District')) $AccountLedger->District = $request->District;
            if ($request->has('StreetNameArb')) $AccountLedger->StreetNameArb = $request->StreetNameArb;
            if ($request->has('BuildingNoArb')) $AccountLedger->BuildingNoArb = $request->BuildingNoArb;
            if ($request->has('CityNameArb')) $AccountLedger->CityNameArb = $request->CityNameArb;
            if ($request->has('DistrictArb')) $AccountLedger->DistrictArb = $request->DistrictArb;
            if ($request->has('CountryArb')) $AccountLedger->CountryArb = $request->CountryArb;
            if ($request->has('AdditionalNoArb')) $AccountLedger->AdditionalNoArb = $request->AdditionalNoArb;
            if ($request->has('PostboxNoArb')) $AccountLedger->PostboxNoArb = $request->PostboxNoArb;
            if ($request->has('bankBranchName')) $AccountLedger->bankBranchName = $request->bankBranchName;
            if ($request->has('bankSwiftCode')) $AccountLedger->bankSwiftCode = $request->bankSwiftCode;
            if ($request->has('AddressArabic')) $AccountLedger->AddressArabic = $request->AddressArabic;
            if ($request->has('ledgerType')) $AccountLedger->ledgerType = $request->ledgerType;
            if ($request->has('routeId')) $AccountLedger->routeId = $request->routeId;

            $AccountLedger->CreatedDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            if ($request->has('CreatedUser')) $AccountLedger->CreatedUser = $request->CreatedUser;

            $AccountLedger->save();

            // set ledgernextno +1
            AccountGroup::where('groupId', $AccountLedger->groupId)->increment('LedgerNextNo');

            // ========== MODIFIED SECTION: Insert Multiple Shipping Addresses ==========
            if ($request->has('ShippingAddress')) {
                $shippingAddresses = $request->ShippingAddress;

                // Handle single object or array of objects
                if (!isset($shippingAddresses[0])) {
                    // Single shipping address (convert to array)
                    $shippingAddresses = [$shippingAddresses];
                }

                // Check if any address is marked as default
                $hasDefault = collect($shippingAddresses)->contains(function ($address) {
                    return isset($address['Isdefault']) && $address['Isdefault'] == true;
                });

                // If multiple addresses and one is marked as default, unset all existing defaults
                if ($hasDefault) {
                    ShippingAddress::where('ledgerId', $AccountLedger->ledgerId)
                        ->update(['Isdefault' => false]);
                }

                // Insert all shipping addresses
                foreach ($shippingAddresses as $index => $sd) {
                    // If no default is set, make the first one default
                    $isDefault = false;
                    if ($hasDefault) {
                        $isDefault = isset($sd['Isdefault']) && $sd['Isdefault'] == true;
                    } else {
                        $isDefault = ($index === 0); // First address is default
                    }

                    ShippingAddress::create([
                        'ledgerId'        => $AccountLedger->ledgerId,
                        'address1'        => $sd['address1'] ?? null,
                        'address2'        => $sd['address2'] ?? null,
                        'address3'        => $sd['address3'] ?? null,
                        'address4'        => $sd['address4'] ?? null,
                        'Isdefault'       => $isDefault,
                        'CreatedDate'     => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                        'CreatedUser'     => $request->CreatedUser ?? null,
                    ]);
                }
            }
            // ========== END MODIFIED SECTION ==========

            foreach ($branchDetails as $branch) {
                $debit = 0;
                $credit = 0;

                if ($branch['crOrDr'] === 'Cr') {
                    $credit = $branch['openingBalance'];
                } else {
                    $debit = $branch['openingBalance'];
                }

                // Insert Ledger Postings
                LedgerPosting::addPosting([
                    'date'         => $request->activeFinancialYear_fromDate,
                    'voucherType'  => 'Opening Balance',
                    'voucherNo'    => $AccountLedger->ledgerId,
                    'ledgerId'     => $AccountLedger->ledgerId,
                    'debit'        => $debit,
                    'credit'       => $credit,
                    'InvoiceNo'    => $AccountLedger->ledgerCode,
                    'Narration'    => $AccountLedger->narration,
                    'costCentreId' => 1,
                    'PostingType'  => 'Opening',
                    'exchangeRate' => $request->exchangeRate,
                    'exchangeDate' => $request->exchangeDate,
                    'branchId'     => $branch['branchId'],
                    'CreatedUser'  => $AccountLedger->CreatedUser,
                ]);

                $result = AccountGroup::getUnderGroups();
                if ($AccountLedger->billBybill == true && count($result) >= 1) {
                    // Partybalance Posting
                    $debit = $branch['crOrDr'] === 'Dr' ? $branch['openingBalance'] : 0;
                    $credit = $branch['crOrDr'] === 'Cr' ? $branch['openingBalance'] : 0;

                    Partybalance::addPosting([
                        'date'                     => $request->activeFinancialYear_fromDate,
                        'ledgerId'                 => $AccountLedger->ledgerId,
                        'voucherType'              => 'Opening Balance',
                        'voucherNo'                => $AccountLedger->ledgerId,
                        'againstVoucherType'       => 'NA',
                        'againstvoucherNo'         => 'NA',
                        'referenceType'            => 'new',
                        'debit'                    => $debit,
                        'credit'                   => $credit,
                        'creditPeriod'             => $AccountLedger->creditPeriod,
                        'currencyConversionId'     => $request->currencyConversionId,
                        'invoiceNo'                => $AccountLedger->ledgerCode,
                        'referenceNo'              => $AccountLedger->narration,
                        'BillAmount'               => $branch['openingBalance'],
                        'invoiceDate'              => $request->activeFinancialYear_fromDate,
                        'costCentreId'             => 1,
                        'exchangeRate'             => $request->exchangeRate,
                        'exchangeDate'             => $request->exchangeDate,
                        'branchId'                 => $branch['branchId'],
                        'CreatedUser'              => $request->CreatedUser,
                    ]);
                }
            }

            // Handle Document Uploads
            // ✅ Handle Document Uploads
            if ($request->hasFile('documents')) {

                $uploadedFiles = $request->file('documents');

                // ✅ Convert single file to array
                if (!is_array($uploadedFiles)) {
                    $uploadedFiles = [$uploadedFiles];
                }

                foreach ($uploadedFiles as $index => $file) {

                    if (!$file->isValid()) {
                        continue;
                    }

                    $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());

                    $destinationPath = public_path('documents');

                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    $file->move($destinationPath, $filename);

                    $filePath = 'documents/' . $filename;

                    $docData = $request->document[$index] ?? [];

                    LedgerDocuments::create([
                        'ledgerId'      => $AccountLedger->ledgerId,
                        'document_name' => $docData['document_name'] ?? $filename,
                        'file'          => $filePath,
                        'branchId'      => $docData['branchId'] ?? $request->branchId ?? null,
                        'createdUser'   => $request->CreatedUser ?? null,
                        'createdDate'   => now('Asia/Kolkata'),
                    ]);
                }
            }
            // ✅ If only document JSON exists (no new upload)
            elseif ($request->has('document') && is_array($request->document)) {

                foreach ($request->document as $docData) {

                    $filePath = !empty($docData['fileName'])
                        ? 'documents/' . $docData['fileName']
                        : null;

                    LedgerDocuments::create([
                        'ledgerId'      => $AccountLedger->ledgerId,
                        'document_name' => $docData['document_name'] ?? 'Document',
                        'file'          => $filePath,
                        'branchId'      => $docData['branchId'] ?? $request->branchId ?? null,
                        'createdUser'   => $request->CreatedUser ?? null,
                        'createdDate'   => now('Asia/Kolkata'),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'AccountLedger Created Successfully'
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

    /**
     * Update AccountLedger2
     */
    public function update(Request $request, $id)
    {
        $AccountLedger = AccountLedger::find($id);

        if (!$AccountLedger) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'AccountLedger not found',
            ], 404);
        }

        DB::beginTransaction();

        try {

            // ✅ Duplicate checks
            if (
                $request->has('ledgerName') &&
                AccountLedger::whereRaw('LOWER("ledgerName") = ?', [strtolower($request->ledgerName)])
                ->where('ledgerId', '!=', $id)
                ->exists()
            ) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Ledger Name Already Exists !',
                ], 409);
            }

            if (
                $request->has('ledgerCode') &&
                AccountLedger::whereRaw('LOWER("ledgerCode") = ?', [strtolower($request->ledgerCode)])
                ->where('ledgerId', '!=', $id)
                ->exists()
            ) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Ledger Code Already Exists !',
                ], 409);
            }

            // ✅ Update ledger fields safely
            $AccountLedger->fill($request->except([
                'ShippingAddress',
                'branchDetails',
                'document',
                'documents'
            ]));

            // Handle image upload if present
            if ($request->hasFile('image')) {

                $file = $request->file('image');

                if ($file->isValid()) {

                    // ✅ Delete old image from public folder
                    if ($AccountLedger->image) {
                        $oldPath = public_path('ledger_photos/' . $AccountLedger->image);
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }

                    $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());

                    $destinationPath = public_path('ledger_photos');

                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    $file->move($destinationPath, $filename);

                    $AccountLedger->image = $filename;
                }
            }

            // Update branchDetails JSON if provided
            if ($request->filled('branchDetails') && is_array($request->branchDetails)) {
                $branchDetails = collect($request->branchDetails)
                    ->map(function ($item) {
                        return [
                            'branchId'        => (int) $item['branchId'],
                            'openingBalance'  => (float) $item['openingBalance'],
                            'crOrDr'          => $item['crOrDr'],
                        ];
                    })
                    ->values()
                    ->toArray();

                $AccountLedger->branchId = $branchDetails; // Update JSON stored field
            }

            $AccountLedger->ModifiedDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            $AccountLedger->ModifiedUser = $request->ModifiedUser ?? null;

            $AccountLedger->save();

            /*
|--------------------------------------------------------------------------
| ✅ SHIPPING ADDRESS UPDATE
|--------------------------------------------------------------------------
*/

            if ($request->has('ShippingAddress')) {

                $shippingAddresses = $request->ShippingAddress;

                if (!is_array($shippingAddresses)) {
                    $shippingAddresses = [$shippingAddresses];
                }

                $processedIds = [];

                // Check if any address is marked as default
                $hasDefault = collect($shippingAddresses)->contains(function ($address) {
                    return isset($address['Isdefault']) && $address['Isdefault'] == true;
                });

                foreach ($shippingAddresses as $index => $sd) {

                    // Determine if this should be default
                    $isDefault = false;
                    if ($hasDefault) {
                        $isDefault = isset($sd['Isdefault']) && $sd['Isdefault'] == true;
                    } else {
                        $isDefault = ($index === 0); // First address is default
                    }

                    $data = [
                        'ledgerId'     => $AccountLedger->ledgerId,
                        'address1'     => $sd['address1'] ?? null,
                        'address2'     => $sd['address2'] ?? null,
                        'address3'     => $sd['address3'] ?? null,
                        'address4'     => $sd['address4'] ?? null,
                        'Isdefault'    => $isDefault,
                        'ModifiedDate' => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                        'ModifiedUser' => $request->ModifiedUser ?? null,
                    ];

                    if (!empty($sd['addressId'])) {

                        // ✅ UPDATE existing address
                        ShippingAddress::where('addressId', $sd['addressId'])
                            ->where('ledgerId', $AccountLedger->ledgerId)
                            ->update($data);

                        $processedIds[] = $sd['addressId'];
                    } else {

                        // ✅ CREATE new address
                        $newAddress = ShippingAddress::create(array_merge($data, [
                            'CreatedDate' => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                            'CreatedUser' => $request->ModifiedUser ?? null,
                        ]));

                        $processedIds[] = $newAddress->addressId;
                    }
                }

                // ✅ DELETE removed addresses
                if (!empty($processedIds)) {
                    ShippingAddress::where('ledgerId', $AccountLedger->ledgerId)
                        ->whereNotIn('addressId', $processedIds)
                        ->delete();
                }

                // ✅ Ensure only one default address exists
                if ($hasDefault) {
                    // Get the ID of the address that should be default
                    $defaultAddressId = null;
                    foreach ($shippingAddresses as $sa) {
                        if (isset($sa['Isdefault']) && $sa['Isdefault'] == true && !empty($sa['addressId'])) {
                            $defaultAddressId = $sa['addressId'];
                            break;
                        }
                    }

                    // Set all others to non-default
                    if ($defaultAddressId) {
                        ShippingAddress::where('ledgerId', $AccountLedger->ledgerId)
                            ->where('addressId', '!=', $defaultAddressId)
                            ->update(['Isdefault' => false]);
                    }
                } else {
                    // If no default was specified, ensure first address is default
                    $defaultCount = ShippingAddress::where('ledgerId', $AccountLedger->ledgerId)
                        ->where('Isdefault', true)
                        ->count();

                    if ($defaultCount == 0) {
                        ShippingAddress::where('ledgerId', $AccountLedger->ledgerId)
                            ->orderBy('addressId', 'asc')
                            ->first()
                            ?->update(['Isdefault' => true]);
                    }
                }
            }

            /*
        |--------------------------------------------------------------------------
        | ✅ FIXED BRANCH OPENING BALANCE UPDATE
        |--------------------------------------------------------------------------
        */

            if ($request->filled('branchDetails') && is_array($request->branchDetails)) {

                // Delete existing opening balance records for this ledger
                LedgerPosting::where('voucherType', 'Opening Balance')
                    ->where('ledgerId', $AccountLedger->ledgerId)
                    ->delete();

                Partybalance::where('voucherType', 'Opening Balance')
                    ->where('ledgerId', $AccountLedger->ledgerId)
                    ->delete();

                // Create new opening balance records (same logic as store)
                foreach ($request->branchDetails as $branch) {

                    $debit = 0;
                    $credit = 0;

                    if (isset($branch['crOrDr'])) {
                        if ($branch['crOrDr'] === 'Cr') {
                            $credit = $branch['openingBalance'] ?? 0;
                        } else {
                            $debit = $branch['openingBalance'] ?? 0;
                        }
                    }

                    // Insert Ledger Postings
                    LedgerPosting::addPosting([
                        'date'         => $request->activeFinancialYear_fromDate,
                        'voucherType'  => 'Opening Balance',
                        'voucherNo'    => $AccountLedger->ledgerId,
                        'ledgerId'     => $AccountLedger->ledgerId,
                        'debit'        => $debit,
                        'credit'       => $credit,
                        'InvoiceNo'    => $AccountLedger->ledgerCode,
                        'Narration'    => $AccountLedger->narration,
                        'costCentreId' => 1,
                        'PostingType'  => 'Opening',
                        'exchangeRate' => $request->exchangeRate ?? 1,
                        'exchangeDate' => $request->exchangeDate ?? now()->format('Y-m-d H:i:s'),
                        'branchId'     => $branch['branchId'],
                        'CreatedUser'  => $request->ModifiedUser,
                    ]);

                    // Check if bill by bill is enabled and insert party balance
                    $result = AccountGroup::getUnderGroups();
                    if ($AccountLedger->billBybill == true && count($result) >= 1) {

                        $debit = $branch['crOrDr'] === 'Dr' ? ($branch['openingBalance'] ?? 0) : 0;
                        $credit = $branch['crOrDr'] === 'Cr' ? ($branch['openingBalance'] ?? 0) : 0;

                        Partybalance::addPosting([
                            'date'                     => $request->activeFinancialYear_fromDate,
                            'ledgerId'                 => $AccountLedger->ledgerId,
                            'voucherType'              => 'Opening Balance',
                            'voucherNo'                => $AccountLedger->ledgerId,
                            'againstVoucherType'       => 'NA',
                            'againstvoucherNo'         => 'NA',
                            'referenceType'            => 'new',
                            'debit'                    => $debit,
                            'credit'                   => $credit,
                            'creditPeriod'             => $AccountLedger->creditPeriod,
                            'currencyConversionId'     => $request->currencyConversionId ?? null,
                            'invoiceNo'                => $AccountLedger->ledgerCode,
                            'referenceNo'              => $AccountLedger->narration,
                            'BillAmount'               => $branch['openingBalance'] ?? 0,
                            'invoiceDate'              => $request->activeFinancialYear_fromDate,
                            'costCentreId'             => 1,
                            'exchangeRate'             => $request->exchangeRate ?? 1,
                            'exchangeDate'             => $request->exchangeDate ?? now()->format('Y-m-d H:i:s'),
                            'branchId'                 => $branch['branchId'],
                            'CreatedUser'              => $request->ModifiedUser,
                        ]);
                    }
                }
            }

            /*
        |--------------------------------------------------------------------------
        | ✅ DOCUMENT UPDATE (Handle file uploads and existing documents)
        |--------------------------------------------------------------------------
        */

            /*
|--------------------------------------------------------------------------
| ✅ DOCUMENT UPDATE (Safe Logic)
|--------------------------------------------------------------------------
*/

            if ($request->has('document')) {

                $existingDocs = LedgerDocuments::where('ledgerId', $AccountLedger->ledgerId)->get();
                $processedFiles = [];

                foreach ($request->document as $index => $docData) {

                    // ✅ Existing file (no new upload)
                    if (isset($docData['hasNewFile']) && $docData['hasNewFile'] == 0) {

                        if (!empty($docData['fileName'])) {

                            $parsedPath = parse_url($docData['fileName'], PHP_URL_PATH);

                            $relativePath = str_contains($parsedPath, 'documents/')
                                ? substr($parsedPath, strpos($parsedPath, 'documents/'))
                                : $docData['fileName'];

                            $processedFiles[] = $relativePath;
                        }
                    }

                    // ✅ New file upload
                    if (isset($docData['hasNewFile']) && $docData['hasNewFile'] == 1 && $request->hasFile('documents')) {

                        $uploadedFiles = $request->file('documents');

                        if (!is_array($uploadedFiles)) {
                            $uploadedFiles = [$uploadedFiles];
                        }

                        foreach ($uploadedFiles as $file) {

                            if (!$file->isValid()) continue;

                            $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());

                            $destinationPath = public_path('documents');

                            if (!file_exists($destinationPath)) {
                                mkdir($destinationPath, 0755, true);
                            }

                            $file->move($destinationPath, $filename);

                            $filePath = 'documents/' . $filename;

                            LedgerDocuments::create([
                                'ledgerId'      => $AccountLedger->ledgerId,
                                'document_name' => $docData['document_name'] ?? $filename,
                                'file'          => $filePath,
                                'branchId'      => $docData['branchId'] ?? $request->branchId ?? null,
                                'createdUser'   => $request->ModifiedUser ?? null,
                                'createdDate'   => now('Asia/Kolkata'),
                            ]);

                            $processedFiles[] = $filePath;
                        }
                    }
                }

                // ✅ Delete only if document field was sent
                foreach ($existingDocs as $existing) {

                    if (!in_array($existing->file, $processedFiles)) {

                        $filePath = public_path($existing->file);

                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }

                        $existing->delete();
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'AccountLedger Updated Successfully'
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
     * Delete AccountLedger
     */
    public function destroy($id)
    {
        $AccountLedger = AccountLedger::find($id);

        if (!$AccountLedger) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'AccountLedger not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Opening Balance', // voucherType
                $id, // voucherNo
                null   // branchId
            ]);

            DB::select('SELECT delete_partybalance(?, ?)', [
                'Opening Balance', // voucherType
                $id // voucherNo
            ]);

            ShippingAddress::where('ledgerId', $id)->delete();
            $ledgerDocs = LedgerDocuments::where('ledgerId', $id)->get();
            foreach ($ledgerDocs as $doc) {
                if (Storage::exists($doc->file)) {
                    Storage::delete($doc->file);
                }
            }
            LedgerDocuments::where('ledgerId', $id)->delete();
            $AccountLedger->delete();
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'AccountLedger deleted successfully',
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

    public function getLedgerBalance(Request $request)
    {
        $ledgerId = $request->input('ledgerId');
        $branchId = $request->input('branchId');
        $currencyId = $request->input('currencyId');

        // Call PostgreSQL function
        $result = DB::select("
            SELECT * FROM get_current_ledger_balance(?, ?, ?)
        ", [$ledgerId, $branchId, $currencyId]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Ledger Balance',
            'data' => $result[0] ?? null
        ]);
    }

    public function AccountledgerDetailedReport(Request $request)
    {
        $result = DB::select('SELECT * FROM public.accountledger_detailed_report(?, ?, ?, ?, ?, ?, ?)', [
            $request->fromDate,
            $request->toDate,
            $request->branchId !== null ? (int) $request->branchId : null,
            (int) $request->ledgerId,
            (int) $request->currencyId,
            (bool) $request->isShowOpeningBalance,
            $request->costCentreId ?? 'All'
        ]);

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Account Ledger Report Retrieved Successfully',
            'data'    => $result,
        ]);
    }








    public function storedefaultdata()
    {
        DB::beginTransaction();
        try {

            $data = [
                ['ledgerId' => 1, 'ledgerName' => 'Cash', 'groupId' => 8, 'ledgerCode' => 1, 'default' => true],
                ['ledgerId' => 2, 'ledgerName' => 'Vat payable', 'groupId' => 10, 'ledgerCode' => 2, 'default' => true],
                ['ledgerId' => 3, 'ledgerName' => 'Vat Refundable', 'groupId' => 9, 'ledgerCode' => 3, 'default' => true],
                ['ledgerId' => 4, 'ledgerName' => 'P D C Payable', 'groupId' => 41, 'ledgerCode' => 4, 'default' => true],
                ['ledgerId' => 5, 'ledgerName' => 'P D C Receivable', 'groupId' => 42, 'ledgerCode' => 5, 'default' => true],
                ['ledgerId' => 6, 'ledgerName' => 'Purchase Account', 'groupId' => 23, 'ledgerCode' => 6, 'default' => true],
                ['ledgerId' => 7, 'ledgerName' => 'Sales Account', 'groupId' => 25, 'ledgerCode' => 7, 'default' => true],
                ['ledgerId' => 8, 'ledgerName' => 'Interest Receivable', 'groupId' => 9, 'ledgerCode' => 8, 'default' => true],
                ['ledgerId' => 9, 'ledgerName' => 'Interest Payable', 'groupId' => 10, 'ledgerCode' => 9, 'default' => true],
                ['ledgerId' => 10, 'ledgerName' => 'Salary', 'groupId' => 16, 'ledgerCode' => 10, 'default' => true],
                ['ledgerId' => 11, 'ledgerName' => 'Rent', 'groupId' => 16, 'ledgerCode' => 11, 'default' => true],
                ['ledgerId' => 12, 'ledgerName' => 'Advertisement', 'groupId' => 16, 'ledgerCode' => 12, 'default' => true],
                ['ledgerId' => 13, 'ledgerName' => 'Transportation', 'groupId' => 12, 'ledgerCode' => 13, 'default' => true],
                ['ledgerId' => 14, 'ledgerName' => 'Wages', 'groupId' => 12, 'ledgerCode' => 14, 'default' => true],
                ['ledgerId' => 15, 'ledgerName' => 'Loading and Unloading', 'groupId' => 12, 'ledgerCode' => 15, 'default' => true],
                ['ledgerId' => 16, 'ledgerName' => 'Bank Charges', 'groupId' => 16, 'ledgerCode' => 16, 'default' => true],
                ['ledgerId' => 17, 'ledgerName' => 'Interest Paid', 'groupId' => 16, 'ledgerCode' => 17, 'default' => true],
                ['ledgerId' => 18, 'ledgerName' => 'Interest Received', 'groupId' => 17, 'ledgerCode' => 18, 'default' => true],
                ['ledgerId' => 19, 'ledgerName' => 'Discount Allowed', 'groupId' => 16, 'ledgerCode' => 19, 'default' => true],
                ['ledgerId' => 20, 'ledgerName' => 'Discount Received', 'groupId' => 17, 'ledgerCode' => 20, 'default' => true],
                ['ledgerId' => 21, 'ledgerName' => 'Forex Gain/Loss', 'groupId' => 16, 'ledgerCode' => 21, 'default' => true],
                ['ledgerId' => 22, 'ledgerName' => 'Advance Payment', 'groupId' => 9, 'ledgerCode' => 22, 'default' => true],
                ['ledgerId' => 23, 'ledgerName' => 'Excise Duty', 'groupId' => 12, 'ledgerCode' => 23, 'default' => true],
                ['ledgerId' => 27, 'ledgerName' => 'Service Account', 'groupId' => 25, 'ledgerCode' => 27, 'default' => true],
                ['ledgerId' => 28, 'ledgerName' => 'Tailoring Income', 'groupId' => 13, 'ledgerCode' => 28, 'default' => true],
                ['ledgerId' => 29, 'ledgerName' => 'Round Off', 'groupId' => 13, 'ledgerCode' => 29, 'default' => true],
            ];

            AccountLedger::insert($data);

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Default Account Ledgers Added successfully'
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

    // address book report
    public function addressBookReport(Request $request)
    {
        try {
            $request->validate([
                'branchId' => 'required',
                'groupId'  => 'required|integer',
                'name'     => 'nullable|string',
                'address'  => 'nullable|string',
                'phoneno'  => 'nullable|string',
                'email'    => 'nullable|string',
            ]);

            $rows = DB::select(
                'SELECT * FROM addressbookfill(?,?,?,?,?,?)',
                [
                    $request->branchId,
                    $request->groupId,
                    $request->name ?? '',
                    $request->address ?? '',
                    $request->phoneno ?? '',
                    $request->email ?? '',
                ]
            );

            foreach ($rows as $row) {
                $row->data = json_decode($row->data, true);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $rows
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function DetailedLedgerReport(Request $request)
    {
        try {
            $branchId     = $request->branchId;
            $currencyId   = $request->currencyId;
            $fromDate     = $request->fromDate;
            $toDate       = $request->toDate;
            $ledgerId     = $request->ledgerId;
            $ledgerName   = $request->ledgerName ?? '';
            $data = DB::select(
                'SELECT * FROM public."transaction_book_detailed"(?, ?, ?, ?, ?, ?)',
                [
                    $branchId,
                    $currencyId,
                    $fromDate,
                    $toDate,
                    $ledgerId,
                    $ledgerName
                ]
            );

            return response()->json([
                'status' => 200,
                'errors' => false,
                'data' => $data,
                'message' => 'Detailed Ledger Report Data Fetched successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function DayBook(Request $request)
    {
        try {
            $fromDate    = $request->fromDate;
            $toDate      = $request->toDate;
            $branchId   = $request->branchId !== null ? (int) $request->branchId : null;
            $currencyId  = $request->currencyId;
            $isCondensed = $request->isCondensed;
            $ledgerName  = $request->ledgerName ?? '';
            $data = DB::select(
                'SELECT * FROM "day_book"(?, ?, ?, ?, ?, ?)',
                [
                    $fromDate,
                    $toDate,
                    $branchId,
                    $currencyId,
                    $isCondensed,
                    $ledgerName
                ]
            );
            return response()->json([
                'status' => 200,
                'errors' => false,
                'data' => $data,
                'message' => ' Day Book Report Data Fetched successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }


    public function generateLedgerCode(Request $request)
    {
        try {
            DB::beginTransaction();

            $result = DB::selectOne(
                'SELECT generate_ledger_code(?, ?) AS ledger_code',
                [
                    $request->groupId,
                    $request->branchId
                ]
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'errors' => false,
                'data' => $result->ledger_code,
                'message' => ' LedgerCode Generated Successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function ProfitAndLossReport(Request $request)
    {
        try {
            $fromDate   = $request->input('from_date');
            $toDate     = $request->input('to_date');
            $branchId   = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
            $currencyId = (int) $request->input('currency_id');

            $rows = DB::select(
                'SELECT * FROM public.profit_and_loss_analysis(?, ?, ?, ?)',
                [
                    $fromDate,
                    $toDate,
                    $branchId,
                    $currencyId
                ]
            );

            /* Group data by particular */
            $groupedData = collect($rows)
                ->groupBy('particular')
                ->map(function ($items) {
                    return $items->map(function ($row) {
                        return [
                            'groupid'   => $row->groupid,
                            'groupname' => $row->groupname,
                            'debit'     => $row->debit,
                            'credit'    => $row->credit,
                        ];
                    })->values();
                });

            return response()->json([
                'status'  => 200,
                'errors'  => false,
                'data'    => $groupedData,
                'message' => 'Profit And Loss Report Data Fetched successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'errors'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function ProfitAndLossDetailedReport(Request $request)
    {
        try {
            $fromDate   = $request->from_date;
            $toDate     = $request->to_date;
            $branchId   = $request->filled('branch_id') ? (int) $request->branch_id : null;
            $currencyId = (int) $request->currency_id;

            $rows = DB::select(
                'SELECT * FROM public.profit_and_loss_analysis_detailed(?, ?, ?, ?)',
                [
                    $fromDate,
                    $toDate,
                    $branchId,
                    $currencyId
                ]
            );

            if (empty($rows)) {
                return response()->json([
                    'status'  => 204,
                    'errors'  => false,
                    'message' => 'No data found for selected period',
                    'data'    => []
                ], 200);
            }

            /*
        |--------------------------------------------------------------------------
        | Group rows by Particular (Purchase, Sales, etc.)
        |--------------------------------------------------------------------------
        */
            $grouped = [];

            foreach ($rows as $row) {
                $grouped[$row->particular][] = [
                    'ledger_id'   => $row->ledger_id,
                    'ledger_name' => $row->ledger_name,
                    'balance'     => (float) $row->balance
                ];
            }

            return response()->json([
                'status'  => 200,
                'errors'  => false,
                'data'    => $grouped,
                'message' => 'Profit And Loss Detailed Report Data Fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'errors'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function TrailBalance(Request $request)
    {
        try {
            // dd($request);
            $branchId = $request->filled('branchId')
                ? (int) $request->branchId
                : null;
            $rows = DB::select(
                'SELECT * FROM public."profit_and_loss_analysis_detailed_by_groupid_for_trial_balance"( ?, ?, ?, ?, ?, ?, ?)',
                [
                    $request->fromDate,
                    $request->toDate,
                    $branchId,
                    $request->groupId ?? 0,
                    $request->currencyId,
                    $request->ledgerName ?? '',
                    $request->reportType ?? 'Detailed'
                ]
            );

            if (empty($rows)) {
                return response()->json([
                    'status'  => 204,
                    'error'   => false,
                    'message' => 'No data found',
                    'data'    => []
                ]);
            }

            $collection = collect($rows);

            /* ================= GROUP WISE ================= */

            $groupWise = $collection
                ->groupBy('groupId')
                ->map(function ($items) {

                    $groupDebit  = $items->sum(fn($row) => (float) ($row->Debit ?? 0));
                    $groupCredit = $items->sum(fn($row) => (float) ($row->Credit ?? 0));

                    return [
                        'groupId'     => (int) $items->first()->groupId,
                        'groupName'   => $items->first()->groupName,

                        'groupDebit'  => $groupDebit,
                        'groupCredit' => $groupCredit,

                        'ledgers' => $items->map(function ($row) {
                            return [
                                'ledgerId'   => (int) $row->ledgerId,
                                'ledgerName' => $row->ledgerName,
                                'Opening'    => $row->Opening ?? "0",
                                'Debit'      => (float) ($row->Debit ?? 0),
                                'Credit'     => (float) ($row->Credit ?? 0),
                            ];
                        })->values()
                    ];
                })
                ->values();

            /* ================= GRAND TOTAL ================= */

            $grandDebit  = $collection->sum(fn($row) => (float) ($row->Debit ?? 0));
            $grandCredit = $collection->sum(fn($row) => (float) ($row->Credit ?? 0));

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Trial Balance group-wise list fetched successfully',

                'grandTotal' => [
                    'Debit'  => $grandDebit,
                    'Credit' => $grandCredit
                ],

                'data' => $groupWise
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function BalanceSheet(Request $request)
    {
        try {
            $toDate = $request->toDate;
            $branchId   = $request->filled('branchId') ? (int) $request->branchId : null;
            $currencyId = $request->currencyId;
            $data = DB::select(
                'SELECT * FROM public."balance_sheet"( ?, ?, ?)',
                [

                    $toDate,
                    $branchId,

                    $currencyId,

                ]
            );
            if (empty($data)) {
                return response()->json([
                    'status'  => 204,
                    'error'   => false,
                    'message' => 'No data found for selected period',
                    'data'    => []
                ], 200);
            }
            return response()->json([
                'status' => 200,
                'errors' => false,
                'data' => $data,
                'message' => '  Balance Sheet Report Data Fetched successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function Detailed(Request $request)
    {
        try {
            $toDate     = $request->toDate;
            $branchId   = $request->filled('branchId') ? (int) $request->branchId : null;
            $currencyId = $request->currencyId;
            $groupId    = $request->group_id;
            $isAsset    = $request->is_asset;

            $data = DB::select(
                'SELECT * FROM public."balance_sheet_detailed"(?, ?, ?, ?, ?)',
                [
                    $toDate,
                    $branchId,
                    $currencyId,
                    $groupId,
                    $isAsset
                ]
            );

            if (empty($data)) {
                return response()->json([
                    'status'  => 204,
                    'error'   => false,
                    'message' => 'No data found for selected period',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Balance Sheet Detailed Report Data fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function openingStockValueFIFO(Request $request)
    {
        try {
            $date       = $request->date;
            $fromDate   = $request->from_date;
            $branchId   = $request->branch_id;
            $currencyId = $request->currency_id;

            $data = DB::select(
                'SELECT * FROM public."stock_value_on_date_by_fifo_for_opening_stock_for_balancesheet"(?, ?, ?, ?)',
                [
                    $date,
                    $branchId,
                    $currencyId,
                    $fromDate
                ]
            );

            if (empty($data)) {
                return response()->json([
                    'status'  => 200,
                    'error'   => false,
                    'message' => 'No opening stock found',
                    'data'    => []
                ]);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Opening stock value Fetched successfully',
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function upToDate(Request $request)
    {


        try {
            $date       = $request->date;
            $branchId   = $request->branch_id;
            $currencyId = $request->currency_id;

            $data = DB::select(
                'SELECT * FROM public."profit_and_loss_analysis_up_to_date"(?, ?, ?)',
                [
                    $date,
                    $branchId,
                    $currencyId
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'No Profit & Loss data found'
                    : 'Profit & Loss data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch Profit & Loss data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function openingStockFIFO(Request $request)
    {


        try {
            $date        = $request->date;
            $branchId    = $request->branch_id;
            $currencyId  = $request->currency_id;
            $fromDate    = $request->from_date;

            $data = DB::select(
                'SELECT * FROM public."bk_stock_value_on_date_by_fifo_for_opening_stock"(?, ?, ?,?)',
                [
                    $date,
                    $branchId,
                    $currencyId,
                    $fromDate
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'Opening stock FIFO data not found'
                    : 'Opening stock FIFO data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch Profit & Loss data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function CashFlow(Request $request)
    {


        try {
            // $branchId    = $request->branch_id;
            $branchId  = $request->filled('branch_id')
                ? (int) $request->branch_id
                : null;
            $currencyId  = $request->currency_id;
            $fromDate    = $request->from_date;
            $ToDate        = $request->to_date;
            $FilterBy        = $request->filter_by;
            $Month        = $request->month;
            $Year        = $request->year;
            $GroupId        = $request->group_id;
            $LedgerId        = $request->ledger_id;


            $data = DB::select(
                'SELECT * FROM cash_flow_summery(?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $branchId,
                    $currencyId,
                    $fromDate,
                    $ToDate,
                    $FilterBy,
                    $Month,
                    $Year,
                    $GroupId,
                    $LedgerId,
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'No cash flow data found'
                    : 'Cash flow data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch  data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function fundFlow(Request $request)
    {
        try {

            $fromDate   = $request->from_date;
            $toDate     = $request->to_date;
            $branchId   = $request->branch_id !== null ? (int) $request->branch_id : null;
            $currencyId = (int) $request->currency_id;

            $data = DB::select(
                'SELECT * FROM public."fund_flow"(?, ?, ?, ?)',
                [
                    $fromDate,
                    $toDate,
                    $branchId,
                    $currencyId
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'No Fund Flow data found'
                    : 'Fund Flow data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch fund flow data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function fundFlowDetailed(Request $request)
    {
        try {

            $fromDate   = $request->from_date;
            $toDate     = $request->to_date;
            $branchId   = $request->branch_id;
            $currencyId = (int) $request->currency_id;
            // $groupId   = $request->group_id;
            // $isAsset = $request->is_asset;

            $result = DB::select(
                'SELECT * FROM public."fund_flow_detailed"(?, ?, ?, ?)',
                [$fromDate, $toDate, $branchId, $currencyId]
            );
            $data = collect($result)->map(function ($item) {
                return [
                    'groupId'   => $item->groupId,
                    'groupName' => $item->groupName,
                    'ledgers'   => json_decode($item->ledgers, true)
                ];
            });
            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'No Fund Flow Detialed data found'
                    : 'Fund Flow Detialed data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch fund flow data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }





    public function AccountLedgerViewAllByCondition(Request $request)
    {
        try {
            $branchId   = $request->branch_id;
            $groupId   = $request->group_id;
            $startText = $request->start_text;

            $data = DB::select(
                'SELECT * FROM public."account_ledger_view_all_by_condition"(?, ?, ?)',
                [
                    $branchId,
                    $groupId,
                    $startText
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'No account ledgers data found'
                    : 'account ledgers data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch account ledgers',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function payable(Request $request)
    {
        try {
            $ageingDate = $request->ageingDate;
            $optional   = false;
            $branchId   = $request->branchId;
            $status     = $request->status;
            $ledgerId   = $request->ledgerId;
            $currencyId = $request->currencyId;
            $ledgerBy   = $request->ledgerBy;
            $ledgerName = $request->ledgerName ?? '';

            $data = DB::select(
                'SELECT * FROM public."ageing_report_fill_by_payable"(?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $ageingDate,
                    $optional,
                    $branchId,
                    $status,
                    $ledgerId,
                    $currencyId,
                    $ledgerBy,
                    $ledgerName
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'No  data found'
                    : 'Ageing payable data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch account ledgers',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function receivable(Request $request)
    {
        try {
            $ageingDate = $request->ageingDate ?: now()->toDateString();
            $branchId   = $request->branchId   ? (int) $request->branchId   : null;
            $status     = $request->status     ?: null;
            $ledgerId   = $request->ledgerId   ? (int) $request->ledgerId   : null;
            $currencyId = $request->currencyId ? (int) $request->currencyId : null;
            $ledgerBy   = $request->ledgerBy   ? (int) $request->ledgerBy   : null;
            $salesmanId = $request->salesmanId ?: null;
            $ledgerName = $request->ledgerName ?: null;

            $data = DB::select(
                'SELECT * FROM public."ageing_report_fill_by_receivable"(?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $ageingDate,
                    $branchId,
                    $status,
                    $ledgerId,
                    $currencyId,
                    $ledgerBy,
                    $salesmanId,
                    $ledgerName
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data) ? 'No data found' : 'Ageing receivable data fetched successfully',
                'data'    => $data
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch ageing report',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function accountLedgerReport(Request $request)
    {
        try {
            $fromDate   = $request->input('fromDate');
            $toDate     = $request->input('toDate');
            $branchId   = $request->input('branchId');
            $currencyId = $request->input('currencyId');
            $ledgerName = $request->input('ledgerName', '');
            $ledgerReport = DB::select(
                'SELECT * FROM public."account_ledger_report_fill"(?, ?, ?, ?, ?)',
                [$fromDate, $toDate, $branchId, $currencyId, $ledgerName]
            );
            return response()->json([
                'status' => true,
                'message' => 'Ledger report fetched successfully',
                'data' => $ledgerReport,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function AccountLedgerView(Request $request)
    {
        $ledgerId = $request->ledgerId;
        $branchId = $request->branchId;
        try {
            $data = DB::select('SELECT * FROM public.account_ledger_view(?, ?)', [
                $ledgerId,
                $branchId
            ]);

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ledger record not found',
                    'data' => null
                ]);
            }
            $ledger = $data[0];

            if (isset($ledger->branchId) && is_string($ledger->branchId)) {
                $ledger->branchId = json_decode($ledger->branchId);
            }
            return response()->json([
                'success' => true,
                'message' => 'Ledger details retrieved successfully',
                'data' => $ledger
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function GetPartyBalance(Request $request)
    {
        $ledgerId = $request->ledgerId;
        $crOrDr = $request->crOrDr;
        $branchId = $request->branchId;
        try {
            $formattedSide = ucfirst(strtolower($crOrDr));
            $data = DB::select('SELECT * FROM public.party_balance_view_by_ledger_id_new(?, ?, ?)', [
                $ledgerId,
                $formattedSide,
                $branchId
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Data retrieved successfully',
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function PartyBalanceAgainst(Request $request)
    {
        $againstVoucherType = $request->againstVoucherType;
        $againstvoucherNo   = $request->againstvoucherNo ?? $request->p_againstvoucherno;
        $ledgerId           = $request->ledgerId;
        $branchId           = $request->branchId;

        $rawCrOrDr          = str_replace("'", "", $request->crOrDr);
        $formattedSide      = ucfirst(strtolower(trim($rawCrOrDr)));

        try {
            $data = DB::select('SELECT * FROM public.party_balance_get_by_against_new(
            ?::varchar,
            ?::varchar,
            ?::varchar,
            ?::varchar,
            ?::varchar
        )', [
                trim($againstVoucherType),
                trim($againstvoucherNo),
                trim($ledgerId),
                trim($branchId),
                $formattedSide
            ]);

            return response()->json([
                'success' => true,
                'message' => count($data) > 0 ? 'Linked transactions retrieved' : 'No matching records found',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls'
        ]);

        DB::beginTransaction();

        try {

            Excel::import(new AccountLedgerBulkImport, $request->file('file'));

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Bulk Upload Completed Successfully'
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => 400,
                'message' => 'Import Failed: ' . $e->getMessage()
            ], 400);
        }
    }


    public function ledgerAccountReport(Request $request)
    {
        try {

            $fromDate   = $request->input('fromDate');
            $toDate     = $request->input('toDate');
            $branchId   = $request->input('branchId');
            $currencyId = $request->input('currencyId');
            $ledgerName = $request->input('ledgerName', '');

            // Convert string "null" to actual null (same normalization as before)
            $fromDate   = ($fromDate === 'null' || $fromDate === null || $fromDate === '') ? null : $fromDate;
            $toDate     = ($toDate === 'null' || $toDate === null || $toDate === '') ? null : $toDate;
            $branchId   = ($branchId === 'null') ? null : $branchId;
            $currencyId = ($currencyId === 'null') ? null : $currencyId;
            $ledgerName = ($ledgerName === 'null') ? '' : $ledgerName;

            // -----------------------------------------
            // Mirror C#: PublicVariables._fromDate / ._toDate fallback
            // C# never lets these be null — if the textbox is blank it
            // falls back to the app's default date range. Do the same here.
            // Replace these two lines with your actual default-date source
            // (e.g. a config value, a company settings table, etc.)
            // -----------------------------------------
            if ($fromDate === null) {
                $fromDate = now()->startOfMonth()->format('Y-m-d H:i:s'); // matches PublicVariables._fromDate default
            }
            if ($toDate === null) {
                $toDate = now()->format('Y-m-d H:i:s'); // matches PublicVariables._toDate default
            }

            // -----------------------------------------
            // Mirror C#: combo boxes guarantee valid branch/currency selections.
            // Laravel has no such guarantee, so validate explicitly before
            // hitting the DB function (avoids a raw Postgres cast error / 500).
            // -----------------------------------------
            if ($branchId === null || !ctype_digit((string) $branchId)) {
                return response()->json([
                    'status'  => 422,
                    'error'   => true,
                    'message' => 'A valid branchId is required.',
                ], 422);
            }

            if ($currencyId === null || trim($currencyId) === '') {
                return response()->json([
                    'status'  => 422,
                    'error'   => true,
                    'message' => 'A valid currencyId is required.',
                ], 422);
            }

            // -----------------------------------------
            // Income Report
            // -----------------------------------------
            $incomeData = DB::select(
                'SELECT * FROM public.ledger_account_income_report_fill(?, ?, ?, ?, ?)',
                [$fromDate, $toDate, $branchId, $currencyId, $ledgerName]
            );

            // -----------------------------------------
            // Expense Report
            // -----------------------------------------
            $expenseData = DB::select(
                'SELECT * FROM public.ledger_account_expense_report_fill(?, ?, ?, ?, ?)',
                [$fromDate, $toDate, $branchId, $currencyId, $ledgerName]
            );

            // -----------------------------------------
            // Mirror C#: dtbl.Compute("SUM(Amount)", ...) totals shown
            // in lblValAmount for each grid.
            // -----------------------------------------
            $incomeTotal  = round(array_sum(array_column($incomeData, 'amount')), 2);
            $expenseTotal = round(array_sum(array_column($expenseData, 'amount')), 2);

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Ledger account income and expense reports fetched successfully.',
                'data' => [
                    'income'       => $incomeData,
                    'incomeTotal'  => $incomeTotal,
                    'expense'      => $expenseData,
                    'expenseTotal' => $expenseTotal,
                ]
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => 'Failed to fetch ledger account income and expense reports.',
                'details' => $e->getMessage()
            ], 500);
        }
    }



    public function incomeAndExpenditureAllReports(Request $request)
    {
        try {
            // -------------------------------------------------
            // Common Parameters
            // -------------------------------------------------
            $fromDate   = $this->nullify($request->input('fromDate'));
            $toDate     = $this->nullify($request->input('toDate'));
            $branchId   = $this->nullify($request->input('branchId'));
            $currencyId = $this->nullify($request->input('currencyId'));

            // 'condensed' = rbtnContensed checked, 'detailed' = else branch
            $reportMode = $request->input('reportMode', 'condensed');

            // corresponds to chkOpeningFooter.Checked
            $showOpeningInFooter = filter_var(
                $request->input('showOpeningInFooter', false),
                FILTER_VALIDATE_BOOLEAN
            );

            $fromCarbon = Carbon::parse($fromDate);
            $toCarbon   = Carbon::parse($toDate);

            // =================================================
            // 1. Opening Balance (dcProfit in C#)
            //    profitAndLoss up to (fromDate - 1 day)
            // =================================================
            $opening = $this->calculateOpeningBalance(
                $fromCarbon->copy()->subDay()->toDateString(),
                $branchId,
                $currencyId
            );

            // =================================================
            // 2. Condensed Income / Expense (direct groups)
            // =================================================
            $condensedResult = DB::select(
                'SELECT * FROM public.incomeandexpenditureanalysiscondensed(?, ?, ?, ?)',
                [$fromDate, $toDate, $branchId, $currencyId]
            );

            $income  = collect($condensedResult)->where('report_type', 'Income')->values()->all();
            $expense = collect($condensedResult)->where('report_type', 'Expense')->values()->all();
            $advancePaymentSalary = collect($condensedResult)
                ->where('report_type', 'AdvancePaymentSalary')->values()->all();

            $directIncome  = collect($income)->sum('Amount');
            $directExpense = collect($expense)->sum('Amount');

            $data = [
                'openingBalance' => $opening,
                'condensed' => [
                    'income'  => $income,
                    'expense' => $expense,
                    'advancePaymentSalary' => $advancePaymentSalary,
                ],
            ];

            // =================================================
            // 3. Detailed mode extras: per-group ledger drill-down
            //    + cash/bank opening & closing rows
            // =================================================
            if ($reportMode === 'detailed') {

                $data['incomeLedgerDetails'] = $this->ledgerDetailsForGroups(
                    $income,
                    true,
                    $fromDate,
                    $toDate,
                    $branchId,
                    $currencyId
                );

                $data['expenseLedgerDetails'] = $this->ledgerDetailsForGroups(
                    $expense,
                    false,
                    $fromDate,
                    $toDate,
                    $branchId,
                    $currencyId
                );

                $data['cashOrBank'] = [
                    'opening' => DB::select(
                        'SELECT * FROM public.profitandlossanalysiscashorbankuptodate(?, ?, ?)',
                        [$fromDate, $branchId, $currencyId]
                    ),
                    'closing' => DB::select(
                        'SELECT * FROM public.profitandlossanalysiscashorbankuptodate(?, ?, ?)',
                        [$toCarbon->copy()->addDay()->toDateString(), $branchId, $currencyId]
                    ),
                ];
            }

            // =================================================
            // 4. Totals / Grand Total / Closing Balance
            // =================================================
            $data['totals'] = $this->calculateTotals(
                $directIncome,
                $directExpense,
                $opening,
                $showOpeningInFooter
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Income and expenditure reports fetched successfully.',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => 'Failed to fetch income and expenditure reports.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Convert "null"/"" strings coming from the frontend into real null.
     */
    private function nullify($value)
    {
        return ($value === 'null' || $value === '') ? null : $value;
    }

    /**
     * Mirrors the dcProfit loop in FillGrid():
     * sums Income credit, subtracts Expense debit, from the
     * up-to-date profit & loss function evaluated at $asOfDate.
     */
    private function calculateOpeningBalance($asOfDate, $branchId, $currencyId)
    {
        $result = DB::select(
            'SELECT * FROM public.incomeandexpenditureprofitandlossanalysisuptoadate(?, ?, ?)',
            [$asOfDate, $branchId, $currencyId]
        );

        $incomeTotal  = collect($result)->where('report_type', 'Income')->sum('Amount');
        $expenseTotal = collect($result)->where('report_type', 'Expense')->sum('Amount');

        $profit = $incomeTotal - $expenseTotal;

        return [
            'amount' => round(abs($profit), 2),
            'side'   => $profit >= 0 ? 'income' : 'expense', // Liability vs Asset in C#
        ];
    }

    /**
     * For each group in $groups, fetch its ledger breakdown via
     * incomeandexpenditureledgerbygroupid — mirrors the per-group
     * dtbl3 calls inside the detailed (else) branch of FillGrid().
     */
    private function ledgerDetailsForGroups(array $groups, bool $isIncome, $fromDate, $toDate, $branchId, $currencyId)
    {
        $details = [];

        foreach ($groups as $group) {
            $groupId = $group->ID ?? $group['ID'];

            $details[$groupId] = DB::select(
                'SELECT * FROM public.incomeandexpenditureledgerbygroupid(?, ?, ?, ?, ?, ?)',
                [$fromDate, $toDate, $branchId, $groupId, $currencyId, $isIncome]
            );
        }

        return $details;
    }

    /**
     * Mirrors the grand-total / closing-balance section at the
     * bottom of FillGrid(): whichever side (income/expense) wins
     * gets the opening balance added, and the difference becomes
     * the closing balance shown on the losing side.
     */
    private function calculateTotals($directIncome, $directExpense, array $opening, bool $showOpeningInFooter)
    {
        $openingIncome  = $opening['side'] === 'income'  ? $opening['amount'] : 0;
        $openingExpense = $opening['side'] === 'expense' ? $opening['amount'] : 0;

        $grandTotalIncome  = $directIncome + $openingIncome;
        $grandTotalExpense = $directExpense + $openingExpense;

        $closingSide   = $grandTotalExpense > $grandTotalIncome ? 'income' : 'expense';
        $closingAmount = abs($grandTotalExpense - $grandTotalIncome);

        return [
            'directIncome'      => round($directIncome, 2),
            'directExpense'     => round($directExpense, 2),
            'openingIncludedInFooter' => $showOpeningInFooter,
            'openingIncome'     => round($openingIncome, 2),
            'openingExpense'    => round($openingExpense, 2),
            'grandTotalIncome'  => round($grandTotalIncome, 2),
            'grandTotalExpense' => round($grandTotalExpense, 2),
            'closingBalance'    => [
                'amount' => round($closingAmount, 2),
                'side'   => $closingSide, // 'income' => shown green (profit), 'expense' => shown red (loss)
            ],
        ];
    }
}