<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DesignationController extends Controller
{
    public function index()
    {
        try {
            $result = DB::select(
                'SELECT * FROM public.get_all_designation()'
            );

            if (empty($result)) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'No Designations Found',
                ]);
            }

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $result
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $result = DB::select(
                'SELECT * FROM public.get_designation_byid(?)',
                [$id]
            );

            if (empty($result)) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Designation Not Found',
                ]);
            }

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $result[0]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            /* -------------------- Validation -------------------- */


            /* ---------------- Duplicate Name Check --------------- */
            if (
                Designation::whereRaw(
                    'LOWER("designationName") = ?',
                    [strtolower($request->designationName)]
                )->exists()
            ) {
                return response()->json([
                    'status'  => 409,
                    'error'   => true,
                    'message' => 'Designation Name Already Exists !',
                ]);
            }

            /* ------------------ Generate ID ---------------------- */
            $maxId = (Designation::max('designationId') ?? 0) + 1;

            /* ------------- Call PostgreSQL Function -------------- */
            DB::statement(
                'SELECT public.insert_designation(?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $maxId,
                    $request->designationName,
                    $request->leaveDays,
                    $request->advancePercentage,
                    $request->narration,
                    $request->branchId,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->CreatedUser,
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Designation Created Successfully',
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

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            /* ---------- Duplicate Name Check (Exclude Self) ---------- */
            if (
                $request->has('designationName') &&
                Designation::whereRaw(
                    'LOWER("designationName") = ?',
                    [strtolower($request->designationName)]
                )
                ->where('designationId', '!=', $id)
                ->exists()
            ) {
                return response()->json([
                    'status'  => 409,
                    'error'   => true,
                    'message' => 'Designation Name Already Exists !',
                ]);
            }

            /* ---------- Check If Record Exists ---------- */
            if (!Designation::where('designationId', $id)->exists()) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Designation Not Found',
                ]);
            }

            /* ---------- Call PostgreSQL Update Function ---------- */
            $result = DB::select(
                'SELECT public.update_designation(?, ?, ?, ?, ?, ?, ?, ?) AS message',
                [
                    $id,
                    $request->designationName,
                    $request->leaveDays,
                    $request->advancePercentage,
                    $request->narration,
                    $request->branchId,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->ModifiedUser,
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => $result[0]->message ?? 'Designation Updated Successfully',
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

    public function destroy($designationId)
    {
        try {
            // Call the PostgreSQL function using the same name
            $result = DB::select('SELECT public.delete_designation(:designationId) AS message', [
                'designationId' => $designationId
            ]);

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => $result[0]->message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}