<?php

namespace App\Http\Controllers;

use App\Models\TaxMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DatabaseService;
use Illuminate\Support\Facades\Crypt;

class TaxMasterController extends Controller
{
    /**
     * List TaxMaster
     */
    public function index()
    {
        try {
            $taxMaster = DB::select(
                'SELECT * FROM public.get_all_tax_master() ORDER BY "taxName" ASC'
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Tax Master retrieved successfully',
                'data'    => $taxMaster,
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function taxprint()
    {
        try {
            $encryptedDb = Crypt::encryptString('finacsoftware_arr');
            DatabaseService::switchToDatabase($encryptedDb);

            $taxMaster = DB::select(
                'SELECT * FROM public.get_all_tax_master() ORDER BY "taxName" ASC'
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Tax Master retrieved successfully',
                'data'    => $taxMaster,
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Get TaxMaster by id
     */
    public function show($id)
    {
        try {
            $result = DB::select(
                'SELECT * FROM public.get_tax_master_byId(?)',
                [$id]
            );

            if (empty($result)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Tax Master not found',
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Tax Master retrieved successfully',
                'data'   => $result[0],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Create currency
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Duplicate tax name check
            if (
                $request->has('taxName') &&
                TaxMaster::whereRaw('LOWER("taxName") = ?', [strtolower($request->taxName)])->exists()
            ) {
                return response()->json([
                    'status' => 409,
                    'error'  => true,
                    'message' => 'Tax Name Already Exists !',
                ], 409);
            }

            $result = DB::select(
                'SELECT public.insert_tax_master(
                ?, ?, ?, ?, ?, ?, ?, ?
            ) AS taxid',
                [
                    $request->taxName,
                    $request->rate,
                    $request->calculatingMode,
                    $request->active,
                    $request->narration,
                    $request->branchId,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->CreatedUser
                ]
            );

            DB::commit();

            return response()->json([
                'status' => 201,
                'error'  => false,
                'message' => 'Tax Master created successfully',
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Update currency
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            /* ---------- CHECK EXISTENCE ---------- */
            $exists = DB::select(
                'SELECT 1 FROM public.get_tax_master_byId(?)',
                [$id]
            );

            if (empty($exists)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Tax Master not found',
                ], 404);
            }

            /* ---------- DUPLICATE TAX NAME CHECK ---------- */
            if ($request->has('taxName')) {
                $duplicate = DB::select(
                    'SELECT 1
                 FROM public."tbl_TaxMaster"
                 WHERE LOWER("taxName") = LOWER(?)
                   AND "taxId" <> ?',
                    [$request->taxName, $id]
                );

                if (!empty($duplicate)) {
                    return response()->json([
                        'status' => 409,
                        'error'  => true,
                        'message' => 'Tax Name Already Exists !',
                    ], 409);
                }
            }

            /* ---------- CALL UPDATE FUNCTION ---------- */
            $result = DB::select(
                'SELECT public.update_tax_master(?,?,?,?,?,?,?,?,?)',
                [
                    $id,
                    $request->taxName,
                    $request->rate,
                    $request->calculatingMode,
                    $request->active,
                    $request->narration,
                    $request->branchId,
                    $request->ModifiedUser,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                ]
            );

            if (empty($result) || $result[0]->update_tax_master === null) {
                DB::rollBack();
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Tax Master not found',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Tax Master updated successfully',
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Delete TaxMaster
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            /* ---------- CALL DELETE FUNCTION ---------- */
            $result = DB::select(
                'SELECT public.delete_tax_master(?)',
                [$id]
            );

            if (empty($result) || $result[0]->delete_tax_master === null) {
                DB::rollBack();
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Tax Master not found',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Tax Master deleted successfully',
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function TaxReportByVoucherwise(Request $request)
    {
        try {
            $fromDate   = $request->fromDate;
            $toDate     = $request->toDate;
            $taxId      = $request->taxId;
            $type       = $request->type ?? 'All';
            $input      = (bool) $request->input;
            $branchId   = $request->filled('branchId') ? (int) $request->branchId : null;
            $optional   = (bool) ($request->optional ?? false);
            $currencyId = (int)  $request->currencyId;
            $formType   = $request->formType ?? 'All';

            $data = DB::select(
                'SELECT * FROM public.tax_report_by_voucherwise_fill(
            ?, ?, ?, ?, ?, ?, ?, ?, ?
        )',
                [
                    $fromDate,
                    $toDate,
                    $taxId,
                    $type,
                    $input,
                    $branchId,
                    $optional,
                    $currencyId,
                    $formType
                ]
            );
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data' => $data,
                'message' => 'Tax Report Fetched successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function TaxDetailedReport(Request $request)
    {
        try {
            $branchId = $request->filled('branchId') ? (int) $request->branchId : null;

            $data = DB::select(
                '
            SELECT *
            FROM public.tax_return_detailed_report(
                ?::timestamp,
                ?::timestamp,
                ?::bigint,
                ?::boolean,
                ?::varchar,
                ?::text,
                ?::integer,
                ?::integer
            )
            ',
                [
                    $request->fromDate,
                    $request->toDate,
                    $branchId,
                    filter_var($request->dummy, FILTER_VALIDATE_BOOLEAN),
                    null,
                    $request->voucherType,
                    $request->cashOrParty,
                    $request->taxId
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Tax Detailed Report Fetched successfully',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function standardRatedSalesReport(Request $request)
    {
        try {
            $fromDate = $request->from_date;
            $toDate   = $request->to_date;
            $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;

            $data = DB::select(
                'SELECT * FROM public.tax_return_standard_rated_sales(?, ?, ?)',
                [$fromDate, $toDate, $branchId]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Tax return standard rated sales report fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function zeroRatedDomesticSalesReport(Request $request)
    {
        try {

            $fromDate = $request->input('from_date');
            $toDate   = $request->input('to_date');
            $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

            $result = DB::select(
                'SELECT * FROM public.tax_return_zero_rated_domestic_sales(?, ?, ?)',
                [
                    $fromDate,
                    $toDate,
                    $branchId
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $result,
                'message' => 'Tax Return Zero-rated Domestic Sales Report fetched successfully'
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function ExceptSales(Request $request)
    {
        try {
            $fromDate = $request->from_date;
            $toDate = $request->to_date;
            $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
            $data = DB::select(
                'SELECT * FROM public.tax_return_exempt_sales(?,?,?)',
                [
                    $fromDate,
                    $toDate,
                    $branchId
                ]
            );
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'ExceptSales fetched successfully'
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function StandardRatedPurchase(Request $request)
    {
        try {
            $fromDate = $request->from_date;
            $toDate = $request->to_date;
            $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
            $data = DB::select(
                'SELECT * FROM public.tax_return_standard_rated_purchase(?,?,?)',
                [
                    $fromDate,
                    $toDate,
                    $branchId
                ]
            );
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Standard Rated Purchase fetched successfully'
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function TaxReturnZeroRatedDomesticPurchase(Request $request)
    {
        try {
            $fromDate = $request->from_date;
            $toDate = $request->to_date;
            $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
            $data = DB::select(
                'SELECT * FROM public.tax_return_zero_rated_domestic_purchase(?,?,?)',
                [
                    $fromDate,
                    $toDate,
                    $branchId
                ]
            );
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Tax Return Zero Rated Domestic Purchase fetched successfully'
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function TaxReturnExemptPurchase(Request $request)
    {
        try {
            $fromDate = $request->from_date;
            $toDate = $request->to_date;
            $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
            $data = DB::select(
                'SELECT * FROM public.tax_return_exempt_purchase(?,?,?)',
                [
                    $fromDate,
                    $toDate,
                    $branchId
                ]
            );
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Tax Return Exempt Purchase fetched successfully'
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getVatPaid(Request $request)
    {
        try {
            $taxLedgerId = $request->input('taxLedgerId');
            $branchId    = $request->input('branchId');
            $fromDate    = $request->input('fromDate');
            $toDate      = $request->input('toDate');

            $result = DB::select(
                'SELECT public.get_vat_paid(?, ?, ?, ?) AS vat_paid',
                [
                    $taxLedgerId,
                    $branchId,
                    $fromDate,
                    $toDate
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'VAT paid fetched successfully.',
                'data'    => $result[0]->vat_paid ?? 0
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => 'Failed to fetch VAT paid.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}