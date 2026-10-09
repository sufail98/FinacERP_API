<?php

namespace App\Http\Controllers;

use App\Models\Godown;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GodownController extends Controller
{
    public function index($id)
    {
       try {
        // ✅ Call DB function
        $branchId = ($id === 'null' || $id === null) ? null : (int) $id;
        $data = DB::select('SELECT * FROM public.get_all_godown(?)', [$branchId]);

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Godown List',
            'data'    => $data,
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
        // ✅ Call DB function
        $result = DB::select('SELECT * FROM public.get_godown_byId(?)', [$id]);

        // ✅ Handle not found
        if (empty($result)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Godown not found',
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Godown details',
            'data'    => $result[0], // return single record
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
        // ✅ Check for duplicate GodownName (case-insensitive)
        if (
            $request->has('GodownName') &&
            Godown::whereRaw('LOWER("GodownName") = ?', [strtolower($request->GodownName)])
                ->exists()
        ) {
            return response()->json([
                'status'  => 409,
                'error'   => true,
                'message' => 'Godown Name Already Exists !',
            ], 409);
        }

        // ✅ Generate next GodownId
        $maxId = (Godown::max('GodownId') ?? 0) + 1;

        // ✅ Call PostgreSQL insert function
        DB::select(
            'SELECT public.insert_godown(?, ?, ?, ?, ?, ?, ?) AS id',
            [
                $maxId,
                $request->GodownName,
                $request->Narration,
                $request->branchId,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->CreatedUser,
                $request->IsDefault
            ]
        );

        DB::commit();

        return response()->json([
            'status'  => 201,
            'error'   => false,
            'message' => 'Godown inserted successfully',
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
        // ✅ Check duplicate GodownName (exclude current record)
        if (
            $request->has('GodownName') &&
            Godown::whereRaw('LOWER("GodownName") = ?', [strtolower($request->GodownName)])
                ->where('GodownId', '!=', $id)
                ->exists()
        ) {
            return response()->json([
                'status'  => 409,
                'error'   => true,
                'message' => 'Godown Name Already Exists !',
            ], 409);
        }

        // ✅ Call PostgreSQL update function
        $result = DB::select(
            'SELECT public.update_godown(?, ?, ?, ?, ?, ?, ?) AS id',
            [
                $id,
                $request->GodownName,
                $request->Narration,
                $request->branchId,
                $request->ModifiedUser,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->IsDefault
            ]
        );

        DB::commit();

        if (empty($result) || $result[0]->id === null) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Godown not found',
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Godown Updated Successfully',
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

    public function destroy($id)
    {
       DB::beginTransaction();

       try {
        $result = DB::select(
            'SELECT public.delete_godown(?) AS id',
            [$id]
        );

        DB::commit();

        if (empty($result) || $result[0]->id === null) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Godown not found',
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Godown deleted successfully',
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

}
