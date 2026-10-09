<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VanExicutiveSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($branchId = null) // branchId optional
    {
        try {
            $vanExecutiveSettings = DB::select(
                'SELECT * FROM public.get_all_van_executive_settings(?)',
                [$branchId]
            );

            if (empty($vanExecutiveSettings)) {
                return response()->json([
                    'status'  => 200,
                    'error'   => false,
                    'data'    => [],
                    'message' => 'No van executive settings found'
                ]);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $vanExecutiveSettings,
                'message' => 'Van executive settings retrieved successfully'
            ]);
        } catch (\Exception $e) {
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
            $result = DB::select(
                'SELECT insert_van_executive_settings(?,?,?,?,?,?,?,?) AS executiveid',
                [
                    $request->executiveId,
                    $request->cashAccountLedgerId,
                    $request->salesAccountLedgerId,
                    $request->salesTargetAmount,
                    $request->bankAccountLedgerId,
                    $request->branchId,
                    now(),
                    $request->CreatedUser
                ]
            );

            if (empty($result)) {
                return response()->json([
                    'status'  => 200,
                    'error'   => false,
                    'data'    => [],
                    'message' => 'Van executive settings not inserted'
                ]);
            }

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'data'    => [
                    'ExecutiveId' => $result[0]->executiveid
                ],
                'message' => 'Van executive settings inserted successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $data = DB::select(
                'SELECT * FROM public.get_van_executive_settings_by_id(?)',
                [$id]
            );

            if (empty($data)) {
                return response()->json([
                    'status'  => 404,
                    'error'   => false,
                    'data'    => [],
                    'message' => 'Van executive settings not found by this id'
                ], 404);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Van executive settings fetched successfully'
            ], 200);
        } catch (\Exception $e) {
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
    public function update(Request $request, string $id)
    {
        try {
            $result = DB::select(
                'SELECT update_van_executive_settings(?,?,?,?,?,?,?,?,?) AS updated',
                [
                    $id,
                    $request->executiveId,
                    $request->cashAccountLedgerId,
                    $request->salesAccountLedgerId,
                    $request->salesTargetAmount,
                    $request->bankAccountLedgerId,
                    $request->branchId,
                    now(),
                    $request->ModifiedUser
                ]
            );



            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Van executive settings updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $result = DB::select(
                'SELECT delete_van_executive_settings(?) AS deleted',
                [$id]
            );



            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Van executive settings deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
