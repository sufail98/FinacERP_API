<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeneralReminderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($branchId)
    {
        try {

            $data = DB::select(
                "SELECT * FROM public.get_general_reminders_by_branch(?)",
                [$branchId]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $data
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'error' => true,
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

            $id = DB::selectOne(
                "SELECT public.insert_general_reminder(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $request->title,
                    $request->description,
                    $request->start_date,
                    $request->start_time,
                    $request->end_date,
                    $request->end_time,
                    $request->all_day,
                    $request->recurrence,
                    $request->reminder_before,
                    $request->priority,
                    $request->status,
                    $request->userId,
                    $request->branchId,
                    $request->CreatedUser
                ]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Reminder Created Successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
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
                "SELECT * FROM public.get_general_reminder_by_id(?)",
                [$id]
            );

            if (empty($data)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Reminder Not Found'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $data
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'error' => true,
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

            $result = DB::selectOne(
                "SELECT public.update_general_reminder(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) AS updated",
                [
                    $id,
                    $request->title,
                    $request->description,
                    $request->start_date,
                    $request->start_time,
                    $request->end_date,
                    $request->end_time,
                    $request->all_day,
                    $request->recurrence,
                    $request->reminder_before,
                    $request->priority,
                    $request->status,
                    $request->userId,
                    $request->branchId,
                    $request->ModifiedUser
                ]
            );

            if (!$result || !$result->updated) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Reminder Not Found'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Reminder Updated Successfully'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $result = DB::selectOne(
                "SELECT public.delete_general_reminder(?) AS deleted",
                [$id]
            );

            if (!$result || !$result->deleted) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Reminder Not Found'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Reminder Deleted Successfully'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
