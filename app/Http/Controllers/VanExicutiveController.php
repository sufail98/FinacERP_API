<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VanExicutiveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($branchId)
    {
        try {
            $vanExecutives = DB::select('SELECT * FROM public.get_all_van_executives(?)', [$branchId]);

            if (empty($vanExecutives)) {
                return response()->json([
                    'status'  => 200,
                    'error'   => false,
                    'data'    => [],
                    'message' => 'No van executives found'
                ]);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $vanExecutives,
                'message' => 'Van executives retrieved successfully'
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
                'SELECT insert_van_executive(?,?,?,?,?,?,?,?,?,?) AS executiveid',
                [
                    $request->ExecutiveName,
                    $request->Address,
                    $request->Email,
                    $request->PhoneNo,
                    $request->GodownId,
                    $request->userName,
                    $request->password,
                    $request->Status,
                    $request->branchId,
                    $request->CreatedUser
                ]
            );

            if (empty($result)) {
                return response()->json([
                    'status'  => 200,
                    'error'   => false,
                    'data'    => [],
                    'message' => 'Van executive not inserted'
                ]);
            }

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'data'    => [
                    'ExecutiveId' => $result[0]->executiveid
                ],
                'message' => 'Van executive inserted successfully'
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
                'SELECT update_van_executive(?,?,?,?,?,?,?,?,?,?,?) AS updated',
                [
                    $id,
                    $request->ExecutiveName,
                    $request->Address,
                    $request->Email,
                    $request->PhoneNo,
                    $request->GodownId,
                    $request->userName,
                    $request->password,
                    $request->Status,
                    $request->branchId,
                    $request->ModifiedUser
                ]
            );



            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Van executive updated successfully'
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
                'SELECT delete_van_executive(?) AS deleted',
                [$id]
            );


            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Van executive deleted successfully'
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
