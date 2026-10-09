<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RportFileDevController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($branchId)
    {
        try {
            $reports = DB::select('SELECT * FROM get_report_file_dev_by_branch(?)', [$branchId]);

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $reports
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ]);
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
            $nextModuleId = DB::table('tbl_ReportFileDev')->max('moduleId') + 1;

            DB::select('
    SELECT insert_tbl_report_file_dev(
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
', [
                $nextModuleId,
                $request->formName,
                $request->formType,
                $request->printType,
                $request->reportName,
                $request->isDefault ?? false,
                $request->isActive ?? true,
                $request->extraDate,
                $request->extra1,
                $request->ReportFormat,
                $request->branchId,
                $request->CreatedDate,
                $request->CreatedUser

            ]);

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'ReportFileDev inserted successfully via PG function'
            ]);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'unique_reportfiledev')) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Record already exists with same formName and printType'
                ], 409);
            }

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $report = DB::select('SELECT * FROM get_report_file_dev_by_id(?)', [$id]);

            if (empty($report)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Report not found'
                ]);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ]);
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
            DB::select(
                'SELECT update_report_file_dev(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $id,
                    $request->formName,
                    $request->formType,
                    $request->printType,
                    $request->reportName,
                    $request->isDefault ?? false,
                    $request->isActive ?? true,
                    $request->extraDate,
                    $request->extra1,
                    $request->ReportFormat,
                    $request->branchId,
                    now(), // ModifiedDate
                    $request->ModifiedUser ?? 'system'
                ]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Report updated successfully'
            ]);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'unique_reportfiledev')) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Record already exists with same formName and printType'
                ], 409);
            }
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::select('SELECT delete_report_file_dev_by_id(?)', [$id]);

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Report deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function showByDistinct($branchId)
    {
        try {

            $rows = DB::select("
            SELECT \"formName\", \"formType\", \"printType\"
            FROM get_report_file_dev_by_branch(?)
        ", [$branchId]);

            $result = [
                "formName" => [],
                "formType" => [],
                "printType" => []
            ];

            foreach ($rows as $row) {
                if (!in_array($row->formName, $result["formName"])) {
                    $result["formName"][] = $row->formName;
                }

                if (!in_array($row->formType, $result["formType"])) {
                    $result["formType"][] = $row->formType;
                }

                if (!in_array($row->printType, $result["printType"])) {
                    $result["printType"][] = $row->printType;
                }
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $result
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