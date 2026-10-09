<?php

namespace App\Http\Controllers;

use App\Models\AccountGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountGroupController extends Controller
{
    public function index(Request $request)
    {
        try {

            // 🔹 Original Eloquent query (you wanted this preserved)
            $AccountGroup = AccountGroup::with('parentGroup:groupId,accountGroupName')
                ->where('accountGroupName', '!=', 'Primary')
                ->where('groupId', '!=', 0)
                ->orderBy('accountGroupName', 'asc')
                ->get();

            // 🔹 Optional: override with PostgreSQL function data if needed
            if ($request->has('use_function') && $request->use_function) {
                $AccountGroup = collect(DB::select('SELECT * FROM public.get_all_account_group()'));
            }

            // 🔹 Mapping (same as original)
            $AccountGroup = $AccountGroup->map(function ($group) {
                return [
                    'groupId'          => $group->groupId,
                    'AccountGroupCode' => $group->AccountGroupCode,
                    'accountGroupName' => $group->accountGroupName,
                    'groupUnder'       => $group->groupUnder,
                    'groupUnderName'   => $group->parentGroup->accountGroupName ?? $group->groupUnderName ?? null,
                    'narration'        => $group->narration,
                    'LedgerNextNo'     => $group->LedgerNextNo,
                    'branchId'         => $group->branchId,
                    'default'          => $group->default,
                    'CreatedDate'      => $group->CreatedDate,
                    'CreatedUser'      => $group->CreatedUser,
                    'ModifiedDate'     => $group->ModifiedDate,
                    'ModifiedUser'     => $group->ModifiedUser,
                ];
            });

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'AccountGroup retrieved successfully',
                'data'    => $AccountGroup,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Get currency by id
     */
    public function show($id)
    {
        // Call PostgreSQL function instead of Eloquent
        $AccountGroup = DB::select(
            'SELECT * FROM public.get_account_group_byId(?)',
            [$id]
        );

        if (empty($AccountGroup)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Account Group not found',
            ], 404);
        }

        // Return the first row (single record) just like find()
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Account Group retrieved successfully',
            'data' => $AccountGroup[0],
        ]);
    }


    public function getAccountGroups(Request $request)
    {
        // Expect posted ids as array, e.g. { "group_ids": [5,6] }
        $groupIds = $request->input('group_ids', []);

        if (empty($groupIds)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'No group IDs provided',
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

        $result = \DB::select($sql, $groupIds);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Account groups retrieved successfully',
            'data' => $result,
        ]);
    }



    /**
     * Create currency
     */
    public function store(Request $request)
    {
        //dd($request->all());
        DB::beginTransaction();
        try {

            // Duplicate accountGroupName check
            if (
                $request->filled('accountGroupName') &&
                AccountGroup::whereRaw(
                    'LOWER("accountGroupName") = ?',
                    [strtolower($request->accountGroupName)]
                )->exists()
            ) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Account Group Name Already Exists !',
                ], 409);
            }

            // Manual increment groupId
            $groupId = (AccountGroup::max('groupId') ?? 0) + 1;

            // Call PostgreSQL function
            $result = DB::select(
                'SELECT * FROM public.insert_account_group(?, ?, ?, ?, ?, ?, ?, ?, ? ,?)',
                [
                    $groupId,
                    $request->AccountGroupCode,
                    $request->accountGroupName,
                    $request->groupUnder,
                    $request->narration,
                    $request->LedgerNextNo,
                    $request->branchId,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->CreatedUser,
                    false

                ]
            );

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Account Group created successfully',
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
     * Update currency
     */
    public function update(Request $request, $id)


    {
        // 🔹 Check for duplicate accountGroupName (same as original)
        if (
            $request->has('accountGroupName') &&
            AccountGroup::whereRaw('LOWER("accountGroupName") = ?', [strtolower($request->accountGroupName)])
            ->where('groupId', '!=', $id)
            ->exists()
        ) {
            return response()->json([
                'status'  => 409,
                'error'   => true,
                'message' => 'Account Group Name Already Exists !',
            ], 409);
        }
        DB::beginTransaction();
        try {
            // 🔹 Call PostgreSQL update function
            $AccountGroup = DB::select(
                'SELECT * FROM public.update_account_group(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $id,
                    $request->AccountGroupCode,
                    $request->accountGroupName,
                    $request->groupUnder,
                    $request->narration,
                    $request->LedgerNextNo,
                    $request->branchId,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'), // ModifiedDate
                    $request->ModifiedUser,
                    true
                ]
            );

            if (empty($AccountGroup)) {
                DB::rollBack();
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Account Group not found',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Account Group updated successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Delete currency
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $result = \DB::select(
                'SELECT * FROM public.delete_account_group(?)',
                [$id]
            );

            if (empty($result)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Account Group not found'
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Account Group deleted successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function AccountgroupDetailedReport(Request $request)
    {
        $result = DB::select('SELECT * FROM public.accountgroup_detailed_report(?, ?, ?, ?, ?, ?, ?, ?,?)', [
            $request->fromDate,
            $request->toDate,
            $request->branchId !== null ? (int) $request->branchId : null,
            (int) $request->groupId,
            (int) $request->currencyId,
            (bool) $request->isMainGroup,
            (bool) $request->isShowOpeningBalance,
            $request->costCentreId ?? 'All',
            $request->ledgerId ?? null
        ]);

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Account Group Report Retrieved Successfully',
            'data'    => $result,
        ]);
    }


    public function BranchwsiecustomerSupplierbalance(Request $request)
    {
        $result = DB::select('SELECT * FROM public.get_all_branch_group_ledger_balance(?, ?, ?, ?, ?, ?)', [
            $request->fromDate,
            $request->toDate,
            (int) $request->currencyId,
            (int) $request->groupId,
            (bool) $request->isShowOpeningBalance,
            'All'
        ]);

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Report Retrieved Successfully',
            'data'    => $result,
        ]);
    }



    public function storedefaultdata()
    {
        DB::beginTransaction();
        try {

            $data = [
                ['groupId' => 0, 'AccountGroupCode' => 'P', 'accountGroupName' => 'Primary', 'groupUnder' => 0, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 1, 'AccountGroupCode' => 'EX', 'accountGroupName' => 'Expense', 'groupUnder' => 0, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 2, 'AccountGroupCode' => 'IN', 'accountGroupName' => 'Income', 'groupUnder' => 0, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 3, 'AccountGroupCode' => 'LI', 'accountGroupName' => 'Liability', 'groupUnder' => 0, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 4, 'AccountGroupCode' => 'AS', 'accountGroupName' => 'Assets', 'groupUnder' => 0, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 5, 'AccountGroupCode' => 'BA', 'accountGroupName' => 'Bank Account', 'groupUnder' => 9, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 6, 'AccountGroupCode' => 'BOD', 'accountGroupName' => 'Bank OD Account', 'groupUnder' => 20, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 7, 'AccountGroupCode' => 'CA', 'accountGroupName' => 'Capital Account', 'groupUnder' => 3, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 8, 'AccountGroupCode' => 'CH', 'accountGroupName' => 'Cash In Hand', 'groupUnder' => 9, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 9, 'AccountGroupCode' => 'CAS', 'accountGroupName' => 'Current Assets', 'groupUnder' => 4, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 10, 'AccountGroupCode' => 'CL', 'accountGroupName' => 'Current Liability', 'groupUnder' => 3, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 11, 'AccountGroupCode' => 'STDA', 'accountGroupName' => 'Short Term Deposits (Asset)', 'groupUnder' => 9, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 12, 'AccountGroupCode' => 'DE', 'accountGroupName' => 'Direct Expenses', 'groupUnder' => 1, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 13, 'AccountGroupCode' => 'DI', 'accountGroupName' => 'Direct Incomes', 'groupUnder' => 2, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 14, 'AccountGroupCode' => 'DT', 'accountGroupName' => 'Duties & Taxes', 'groupUnder' => 10, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 15, 'AccountGroupCode' => 'FA', 'accountGroupName' => 'Fixed Assets', 'groupUnder' => 4, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 16, 'AccountGroupCode' => 'IE', 'accountGroupName' => 'Indirect Expenses', 'groupUnder' => 1, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 17, 'AccountGroupCode' => 'II', 'accountGroupName' => 'Indirect Incomes', 'groupUnder' => 2, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 18, 'AccountGroupCode' => 'INV', 'accountGroupName' => 'Investments', 'groupUnder' => 4, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 19, 'AccountGroupCode' => 'LAA', 'accountGroupName' => 'Loans & Advances(Asset)', 'groupUnder' => 4, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 20, 'AccountGroupCode' => 'LLL', 'accountGroupName' => 'Loans & Liability(Liability)', 'groupUnder' => 3, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 21, 'AccountGroupCode' => 'ME', 'accountGroupName' => 'MiscellaneousExpenses', 'groupUnder' => 4, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 22, 'AccountGroupCode' => 'PROV', 'accountGroupName' => 'Provisions', 'groupUnder' => 10, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 23, 'AccountGroupCode' => 'PA', 'accountGroupName' => 'Purchase Accounts', 'groupUnder' => 1, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 24, 'AccountGroupCode' => 'RS', 'accountGroupName' => 'Reserves & Surplus', 'groupUnder' => 7, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 25, 'AccountGroupCode' => 'SA', 'accountGroupName' => 'Sales Accounts', 'groupUnder' => 2, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 26, 'AccountGroupCode' => 'SL', 'accountGroupName' => 'Secured Loans', 'groupUnder' => 20, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 27, 'AccountGroupCode' => 'SH', 'accountGroupName' => 'Stock In Hand', 'groupUnder' => 9, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 28, 'AccountGroupCode' => 'SC', 'accountGroupName' => 'Sundry Creditors', 'groupUnder' => 10, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 29, 'AccountGroupCode' => 'SD', 'accountGroupName' => 'Sundry Debtors', 'groupUnder' => 9, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 30, 'AccountGroupCode' => 'SAA', 'accountGroupName' => 'Suspense Account(Asset)', 'groupUnder' => 4, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 31, 'AccountGroupCode' => 'SAL', 'accountGroupName' => 'Suspense Account(Liability)', 'groupUnder' => 3, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 32, 'AccountGroupCode' => 'UL', 'accountGroupName' => 'Unsecured Loans', 'groupUnder' => 20, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 33, 'AccountGroupCode' => 'PCA', 'accountGroupName' => 'Proprietor Current Account', 'groupUnder' => 7, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 34, 'AccountGroupCode' => 'CF', 'accountGroupName' => 'Chits Or Funds', 'groupUnder' => 18, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 35, 'AccountGroupCode' => 'AP', 'accountGroupName' => 'Accounts Payables', 'groupUnder' => 10, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 36, 'AccountGroupCode' => 'AR', 'accountGroupName' => 'Accounts Receivables', 'groupUnder' => 9, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 37, 'AccountGroupCode' => 'AI', 'accountGroupName' => 'Accrued Income', 'groupUnder' => 9, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 38, 'AccountGroupCode' => 'LTDA', 'accountGroupName' => 'Long Term Deposits(Asset)', 'groupUnder' => 4, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 39, 'AccountGroupCode' => 'STLL', 'accountGroupName' => 'Short Term Loans(Liability)', 'groupUnder' => 10, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 40, 'AccountGroupCode' => 'STLA', 'accountGroupName' => 'Short Term Loans & Advances(Asset)', 'groupUnder' => 9, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 41, 'AccountGroupCode' => 'PP', 'accountGroupName' => 'P D C Payable', 'groupUnder' => 10, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 42, 'AccountGroupCode' => 'PR', 'accountGroupName' => 'P D C Receivable', 'groupUnder' => 9, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 43, 'AccountGroupCode' => 'S', 'accountGroupName' => 'Supplier', 'groupUnder' => 28, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 44, 'AccountGroupCode' => 'C', 'accountGroupName' => 'Customer', 'groupUnder' => 29, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 45, 'AccountGroupCode' => 'OE', 'accountGroupName' => 'Operating Expense', 'groupUnder' => 16, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 46, 'AccountGroupCode' => 'NOE', 'accountGroupName' => 'Non Operating Expense', 'groupUnder' => 16, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 47, 'AccountGroupCode' => 'AC', 'accountGroupName' => 'Additional Cost', 'groupUnder' => 12, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
                ['groupId' => 48, 'AccountGroupCode' => 'PC', 'accountGroupName' => 'Production cost', 'groupUnder' => 12, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
            ];

            AccountGroup::insert($data);

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Default Account Groups Added successfully'
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

    public function accountGroupForChart($groupId)
    {
        try {
            if (!is_numeric($groupId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid groupId'
                ], 400);
            }
            $data = DB::select(
                'SELECT * FROM public.account_group_for_chart(?)',
                [
                    $groupId
                ]
            );
            if (empty($data)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No account groups found'
                ], 404);
            }
            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $data,
                'message' => ' Account Groups Fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function accountGroupReport(Request $request)
    {
        $fromDate = $request->fromDate;
        $toDate = $request->toDate;
        $branchId   = $request->branchId !== null ? (int) $request->branchId : null;
        $currencyId = (int) $request->currencyId;
        try {
            $data = DB::select(
                'SELECT * FROM public.accountgroupreportfill(?, ?, ?, ?)',
                [$fromDate, $toDate, $branchId, $currencyId]
            );

            return response()->json([
                'status' => true,
                'message' => 'Account group report fetched successfully',
                'data' => $data
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function billbalanceReport(Request $request)
    {
        try {
            $data = DB::select(
                'SELECT * FROM "billbalance_detailed_report"(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $request->fromDate,
                    $request->toDate,
                    $request->branchId,
                    $request->groupId,
                    $request->currencyId,
                    $request->isMainGroup,
                    $request->isShowOpeningBalance,
                    $request->costCentreId ?? 'All',
                    $request->ledgerId ?? null,
                    $request->mode,
                ]
            );

            return response()->json([
                'status'  => 200,
                'errors'  => false,
                'data'    => $data,
                'message' => 'Bill Balance Report fetched successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
