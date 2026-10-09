<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryAdvancePayment;
use Illuminate\Support\Facades\DB;

class SalaryAdvancePaymentController extends Controller
{
    public function index($Id)
    {
        try {

        $branchId=($Id === 'null' || $Id === null ) ? null : (int) $Id;
            $result = DB::select(
                'SELECT * FROM public.get_all_salary_advance_payment(?)',
                [$branchId]
                
            );

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $result
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($advanceMasterId)
    {
        try {
            $result = DB::select(
                'SELECT * FROM public.get_salary_advance_payment_by_id(?)',
                [$advanceMasterId]
            );

            if (empty($result)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message'=> 'advanceMasterId not found'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $result[0]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message'=> $e->getMessage()
            ], 500);
        }
    }
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            DB::select(
                    'SELECT public.insert_salary_advance_payment(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        (SalaryAdvancePayment::max('advanceMasterId') ?? 0) + 1,
                        $request->voucherNo,
                        $request->suffixPrefixId,
                        $request->advanceNo,
                        $request->date,
                        $request->employeeId,
                        $request->ledgerId,
                        $request->amount,
                        $request->month,
                        $request->narration,
                        $request->chequeNo,
                        $request->chequeDate,
                        $request->branchId,
                        now(),
                        $request->CreatedUser
                    ]
                );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'SalaryAdvancePayment Inserted Successfully'
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $advanceMasterId)
    {
      DB::beginTransaction();

      try {
        $SalaryAdvancePayment = SalaryAdvancePayment::find($advanceMasterId);

        if (!$SalaryAdvancePayment) {
            return response()->json([
                'status' => 404,
                'error'  => true,
                'message'=> 'advanceMasterId not found',
            ], 404);
        }

            $result = DB::select(
            'SELECT public.update_salary_advance_payment(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $advanceMasterId,   // ✅ MUST BE FIRST
                $request->voucherNo,
                (int)$request->suffixPrefixId,
                $request->advanceNo,
                $request->date,
                $request->employeeId,
                $request->ledgerId,
                $request->amount,
                $request->month,
                $request->narration,
                $request->chequeNo,
                $request->chequeDate,
                $request->branchId,
                now(),
                $request->ModifiedUser
            ]
        );


        //dd($result);


        DB::commit();

        return response()->json([
            'status' => 200,
            'error'  => false,
            'message'=> 'advanceMasterId updated successfully',
        ]);

      } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'status' => 500,
            'error'  => true,
            'message'=> $e->getMessage(),
        ], 500);
      }
    }
    public function destroy($id)
    {
        DB::beginTransaction();
		try {
            $advanceMasterId = SalaryAdvancePayment::find($id);
            
            if (empty($advanceMasterId)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'salaryadvanceid not found',
                ], 404);
            }

            DB::select('SELECT delete_salary_advance_payment(?)', [$id]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'salaryadvance deleted successfully',
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

}
