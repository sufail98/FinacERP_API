<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Exception;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ZatcaRegistration;
use App\Models\ZatcaResponse;
use App\Models\SalesMaster;
use App\Services\DatabaseService;
use Illuminate\Support\Facades\Crypt;

class ZatcaRegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        try {
            $zatcaData = DB::select('SELECT * FROM public.get_all_zatca_registration(?)', [$id]);

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $zatcaData
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function preinvoicehash($id)
    {
        try {
            $zatcaData =  ZatcaRegistration::where('branchId', $id)->first();

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Previous Invoice Hash fetched successfully',
                'data'   => $zatcaData->PrevInvoiceHash ?? null
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $ztaca = ZatcaRegistration::where('branchId', $request->branchId)->first();

            if ($ztaca) {

                // ✅ UPDATE
                DB::statement('SELECT update_tbl_zatca_registration(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                    $ztaca->zatca_registrationId,
                    $request->ProfectionBinaryToken,
                    $request->PrivateKey,
                    $request->PublicKey,
                    $request->ProductionSecret,
                    $request->BinaryToken,
                    $request->Auth_BinarySecurityToken_PCSID,
                    $request->branchId,
                    $request->modifiedUser,
                    $request->PrevInvoiceHash
                ]);

                return response()->json([
                    'status' => 200,
                    'error' => false,
                    'message' => 'Zatca Registration updated successfully'
                ]);
            } else {

                // ✅ INSERT
                $nextId = DB::table('tbl_Zatca_Registration')->max('zatca_registrationId') + 1;

                DB::statement('SELECT insert_tbl_zatca_registration(
                    ?::bigint,
                    ?::text,
                    ?::varchar,
                    ?::varchar,
                    ?::text,
                    ?::text,
                    ?::text,
                    ?::integer,
                    ?::timestamptz,
                    ?::varchar,
                    ?::text
                )', [
                    $nextId,
                    $request->ProfectionBinaryToken,
                    $request->PrivateKey,
                    $request->PublicKey,
                    $request->ProductionSecret,
                    $request->BinaryToken,
                    $request->Auth_BinarySecurityToken_PCSID,
                    $request->branchId,
                    now(),
                    $request->createdUser,
                    $request->PrevInvoiceHash
                ]);

                return response()->json([
                    'status' => 200,
                    'error' => false,
                    'message' => 'Zatca Registration inserted successfully'
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // public function store(Request $request)
    // {


    //     try {

    //         $nextId = DB::table('tbl_Zatca_Registration')->max('zatca_registrationId') + 1;


    //         DB::statement('SELECT insert_tbl_zatca_registration(
    //         ?::bigint,
    //         ?::text,
    //         ?::varchar,
    //         ?::varchar,
    //         ?::text,
    //         ?::text,
    //         ?::text,
    //         ?::integer,
    //         ?::timestamptz,
    //         ?::varchar
    //     )', [
    //             $nextId,
    //             $request->ProfectionBinaryToken,
    //             $request->PrivateKey,
    //             $request->PublicKey,
    //             $request->ProductionSecret,
    //             $request->BinaryToken,
    //             $request->Auth_BinarySecurityToken_PCSID,
    //             $request->branchId,
    //             now(),
    //             $request->createdUser
    //         ]);

    //         return response()->json([
    //             'status' => 200,
    //             'error' => false,
    //             'message' => 'Zatca Registration inserted successfully',
    //         ]);
    //     } catch (QueryException $e) {
    //         return response()->json([
    //             'status' => 500,
    //             'error' => true,
    //             'message' => 'Database Error: ' . $e->getMessage()
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'status' => 500,
    //             'error' => true,
    //             'message' => 'Error: ' . $e->getMessage()
    //         ]);
    //     }
    // }





    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $data = DB::select(
                'SELECT * FROM public.get_van_executive_by_id(?)',
                [$id]
            );

            if (empty($data)) {
                return response()->json([
                    'status'  => 404,
                    'error'   => false,
                    'data'    => [],
                    'message' => 'Van executive not found by this id'
                ], 404);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Van executive fetched successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {


        try {
            DB::statement('SELECT update_tbl_zatca_registration(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $id,
                $request->ProfectionBinaryToken,
                $request->PrivateKey,
                $request->PublicKey,
                $request->ProductionSecret,
                $request->BinaryToken,
                $request->Auth_BinarySecurityToken_PCSID,
                $request->branchId,
                $request->modifiedUser
            ]);

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Zatca Registration updated successfully'
            ]);
        } catch (QueryException $e) {
            return response()->json(['status' => 500, 'error' => true, 'message' => 'Database Error: ' . $e->getMessage()]);
        } catch (Exception $e) {
            return response()->json(['status' => 500, 'error' => true, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $result = DB::select(
                'SELECT delete_tbl_zatca_registration(?) AS deleted',
                [$id]
            );

            if (!$result || !$result[0]->deleted) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Zatca Registration not found'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Zatca Registration deleted successfully'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => 'Database Error: ' . $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function pedingVoucher(Request $request)
    {
        try {

            $encryptedDb = Crypt::encryptString((string) $request->db);
            DatabaseService::switchToDatabase($encryptedDb);

            $fromDate = $request->fromDate;
            $toDate = $request->toDate . ' 23:59:59';
            $voucherType = $request->voucherType;

            $result = DB::selectOne(
                'SELECT public.fn_get_pending_zatca_vouchers_json(?::timestamp, ?::timestamp, ?::varchar) AS data',
                [$fromDate, $toDate, $voucherType]
            );

            $data = [];

            if ($result && $result->data) {
                $data = is_string($result->data)
                    ? json_decode($result->data, true)
                    : $result->data;
            }

            return response()->json([
                'success' => true,
                'message' => 'Pending ZATCA vouchers fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getInvoices(Request $request)
    {
        try {

            $fromDate = $request->fromDate;
            $toDate = $request->toDate;

            $data = SalesMaster::select([
                'salesMasterId',
                'invoiceNo',
                'customerName',
                'totalAmount',
                'totalTax',
                'qr_link',
                // ✅ Combined Date + BillTime
                DB::raw("(\"date\"::timestamp + TO_TIMESTAMP(\"billTime\", 'HH:MI AM')::time) as \"billDateTime\"")
            ])
                ->whereBetween(DB::raw('"date"::date'), [$fromDate, $toDate])
                //   ->whereRaw("COALESCE(UPPER(TRIM(\"Zatcastatus\")), '') NOT IN ('CLEARED','REPORTED')")
                ->orderBy('salesMasterId', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'ZATCA invoices fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function updateQrLink(Request $request)
    {
        try {
            $qrLink = $request->input('qr_link');
            $id = $request->input('salesMasterId');

            $updated = SalesMaster::where('salesMasterId', $id)->update(['qr_link' => $qrLink]);

            if ($updated === 0) {
                return response()->json(['status' => 404, 'error' => true, 'message' => 'Invoice not found'], 404);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'QR link updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }





    public function ZatcaResponseStore(Request $request)
    {
        try {
            $record = ZatcaResponse::create([
                'zid' => (ZatcaResponse::max('zid') ?? 0) + 1,
                'SalesMasterId' => $request->SalesMasterId,
                'ZatcaResponse' => $request->ZatcaResponse,
                'CreatedDate'   => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'error'   => false,
                'message' => 'Zatca response saved successfully',
                'data'    => $record,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => 'Failed to save Zatca response',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
