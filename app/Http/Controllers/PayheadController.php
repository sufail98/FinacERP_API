<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payhead;
use Illuminate\Support\Facades\DB;

class PayheadController extends Controller
{

   public function index($Id)
    {
        try {

        $branchId=($Id === 'null' || $Id === null ) ? null : (int) $Id;
            $result = DB::select(
                'SELECT * FROM public.get_all_payheads(?)',
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

    public function show($payheadId)
    {
        try {
            $result = DB::select(
                'SELECT * FROM public.get_payhead_by_id(?)',
                [$payheadId]
            );

            if (empty($result)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message'=> 'Payhead not found'
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

            
            $result = DB::select(
                'SELECT public.insert_payhead(?, ?, ?, ?, ?, ?, ?) ',
                [
                    (Payhead::max('payheadId') ?? 0) + 1,
                    $request->payheadName,
                    $request->type,
                    $request->narration,
                    $request->branchId,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->CreatedUser
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'payhead Inserted Successfully'
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
    public function update(Request $request, $payheadId)
    {
      DB::beginTransaction();

      try {
        $payhead = Payhead::find($payheadId);

        if (!$payhead) {
            return response()->json([
                'status' => 404,
                'error'  => true,
                'message'=> 'payhead not found',
            ], 404);
        }

            $result = DB::select(
            'SELECT * FROM public.update_payhead(?, ?, ?, ?, ?, ?, ?)',
            [
                $payheadId,
                $request->payheadName,
                $request->type,
                $request->narration,
                $request->branchId,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->ModifiedUser
            ]
        );

        //dd($result);


        DB::commit();

        return response()->json([
            'status' => 200,
            'error'  => false,
            'message'=> 'payhead updated successfully',
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
            $PayheadId = Payhead::find($id);
            
            if (empty($PayheadId)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'payhead not found',
                ], 404);
            }

            DB::select('SELECT delete_payhead(?)', [$id]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'payhead deleted successfully',
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
