<?php

namespace App\Http\Controllers;

use App\Models\PricingLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PricingLevelController extends Controller
{
    public function index()
    {
      try {
        $data = DB::select(
            'SELECT * FROM public.get_all_pricing_level()'
        );

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Pricing Level List',
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
        $result = DB::select(
            'SELECT * FROM public.get_pricing_level_byId(?)',
            [$id]
        );

        if (empty($result)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Pricing Level not found',
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Pricing Level',
            'data'    => $result[0], // single record
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
        // ✅ Check for duplicate PricingLevelName (case-insensitive)
        if (
            $request->has('PricingLevelName') &&
            PricingLevel::whereRaw(
                'LOWER("PricingLevelName") = ?',
                [strtolower($request->PricingLevelName)]
            )->exists()
        ) {
            return response()->json([
                'status'  => 409,
                'error'   => true,
                'message' => 'Pricing Level Name Already Exists !',
            ], 409);
        }

        // ✅ Generate next PricingLevelId
        $maxId = (PricingLevel::max('PricingLevelId') ?? 0) + 1;

        // ✅ Call PostgreSQL insert function
        DB::select(
            'SELECT public.insert_pricing_level(?, ?, ?, ?, ?, ?)',
            [
                $maxId,
                $request->PricingLevelName,
                $request->Narration,
                $request->branchId,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->CreatedUser
            ]
        );

        DB::commit();

        return response()->json([
            'status'  => 201,
            'error'   => false,
            'message' => 'Pricing Level inserted successfully',
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
        // ✅ Check if Pricing Level exists
        $exists = PricingLevel::where('PricingLevelId', $id)->exists();
        if (!$exists) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Pricing Level not found',
            ], 404);
        }

        // ✅ Duplicate name check (exclude current ID)
        if (
            $request->has('PricingLevelName') &&
            PricingLevel::whereRaw(
                'LOWER("PricingLevelName") = ? AND "PricingLevelId" <> ?',
                [strtolower($request->PricingLevelName), $id]
            )->exists()
        ) {
            return response()->json([
                'status'  => 409,
                'error'   => true,
                'message' => 'Pricing Level Name Already Exists !',
            ], 409);
        }

        // ✅ Call PostgreSQL update function
        $result = DB::select(
            'SELECT public.update_pricing_level(?, ?, ?, ?, ?, ?) AS id',
            [
                $id,
                $request->PricingLevelName,
                $request->Narration,
                $request->branchId,
                $request->ModifiedUser, // 🔴 fixed typo (ModifedUser → ModifiedUser)
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s')
            ]
        );

        DB::commit();

        // ✅ Safety check (in case row deleted after exists check)
        if (empty($result) || $result[0]->id === null) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Pricing Level not found',
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Pricing Level Updated Successfully',
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
            'SELECT public.delete_pricing_level(?) AS id',
            [$id]
        );

        DB::commit();

        // ✅ NOT FOUND
        if (empty($result) || $result[0]->id === null) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Pricing Level not found',
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Pricing Level deleted successfully',
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
