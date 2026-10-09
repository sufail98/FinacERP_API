<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AreaController extends Controller
{
    /**
     * Display a listing of areas
     */
    public function index()
    {
        $areas = DB::select('SELECT * FROM get_areas()');

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Areas retrieved successfully',
            'data' => $areas,
        ]);
    }

    /**
     * Display the specified area
     */
    public function show($id)
    {
        $area = DB::select('SELECT * FROM get_area_by_id(?)', [$id]);
        
        if (!$area) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Area not found',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Area retrieved successfully',
            'data' => $area[0],
        ]);
    }

    /**
     * Store a newly created area
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
		try {
            // Check if area name already exists
            $existingArea = Area::whereRaw('LOWER("AreaName") = ?', [strtolower($request->AreaName)])->first();
            if ($existingArea) {
                return response()->json([
                    'status' => 422,
                    'error' => true,
                    'message' => 'Area Name Already Exists !',
                ], 422);
            }

            $maxId = (Area::max('AreaId') ?? 0) + 1;

            $result = DB::select('SELECT create_area(?, ?, ?, ?, ?) AS id', [
                $maxId,
                $request->AreaName,
                $request->Narration,
                $request->CreatedUser,
                $request->branchId,
            ]);

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Area created successfully'
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
     * Update the specified area
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
		try {
            $area = Area::find($id);
            
            if (!$area) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Area not found',
                ], 404);
            }

            // Check if new area name already exists (excluding current area)
            if ($request->has('AreaName') && $request->AreaName !== $area->AreaName) {
                $existingArea = Area::whereRaw('LOWER("AreaName") = ?', [strtolower($request->AreaName)])
                    ->where('AreaId', '!=', $id)
                    ->first();
                    
                if ($existingArea) {
                    return response()->json([
                        'status' => 422,
                        'error' => true,
                        'message' => 'Area Name Already Exists !',
                    ], 422);
                }
            }

            DB::select('SELECT update_area(?, ?, ?, ?)', [
                $id,
                $request->AreaName,
                $request->Narration,
                $request->ModifiedUser,
            ]);

            DB::commit();
            $area->refresh();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Area updated successfully'
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
     * Remove the specified area
     */
    public function destroy($id)
    {
        DB::beginTransaction();
		try {
            $area = Area::find($id);
            
            if (!$area) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Area not found',
                ], 404);
            }

            DB::select('SELECT delete_area(?)', [$id]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Area deleted successfully',
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
