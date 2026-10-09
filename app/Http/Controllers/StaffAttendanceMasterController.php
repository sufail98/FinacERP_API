<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StaffAttendanceMaster;
use App\Models\AttendanceDetails;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StaffAttendanceMasterController extends Controller
{
    public function index($branchId = null)
    {
        try {

            // ✅ Convert "null" string to real null
            $branchId = ($branchId === 'null' || $branchId === null) ? null : (int) $branchId;

            // ✅ Get Masters
            $masters = DB::select(
                'SELECT * FROM public.get_all_staff_attendance_master(?)',
                [$branchId]
            );

            // ✅ Get Details
            $details = DB::select(
                'SELECT * FROM public.get_all_attendance_details(?)',
                [$branchId]
            );

            // ✅ Group details by masterId
            $groupedDetails = [];

            foreach ($details as $row) {
                $groupedDetails[$row->attendanceMasterId][] = $row;
            }

            // ✅ Attach details to master
            foreach ($masters as &$master) {
                $master->details = $groupedDetails[$master->attendanceMasterId] ?? [];
            }

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $masters
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        try {

            // ✅ 1. Get Master
            $master = DB::select(
                'SELECT * FROM public.get_staff_attendance_master_by_id(?)',
                [$id]
            );

            if (empty($master)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Attendance master not found'
                ], 404);
            }

            // ✅ 2. Get Details
            $details = DB::select(
                'SELECT * FROM public.get_attendance_details_by_master_id(?)',
                [$id]
            );

            // ✅ 3. Combine Response
            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => [
                    'master'  => $master[0],
                    'details' => $details
                ]
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            // ✅ Generate Master ID
            $attendanceMasterId = (StaffAttendanceMaster::max('attendanceMasterId') ?? 0) + 1;

            $createdDate = now()->format('Y-m-d H:i:s');
            $createdUser = $request->CreatedUser;

            // ✅ Insert Master
            $master = DB::select(
                'SELECT * FROM public.insert_staff_attendance_master(?, ?, ?, ?, ?, ?)',
                [
                    $attendanceMasterId,
                    $request->date,
                    $request->narration,
                    $request->branchId,
                    $createdDate,
                    $createdUser
                ]
            );

            if ($master[0]->status != 200) {
                DB::rollBack();
                return response()->json($master[0]);
            }


            // ✅ Prepare Details ID
            $detailsId = (AttendanceDetails::max('attendanceDetailsId') ?? 0);

            // ✅ Loop details
            foreach ($request->details as $detail) {

                $detailsId++;

                $result = DB::select(
                    'SELECT * FROM public.insert_attendance_details(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )',
                    [
                        $detailsId,
                        $attendanceMasterId,
                        $detail['employeeId'],
                        $detail['status'],
                        $detail['OTMnt'],
                        $request->branchId,
                        $createdDate,
                        $createdUser,
                        null, // ModifiedDate
                        null  // ModifiedUser
                    ]
                );

                if ($result[0]->status_code != 200) {
                    DB::rollBack();
                    return response()->json($result[0]);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Attendance inserted successfully'
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

    public function update(Request $request, $attendanceMasterId)
    {
        DB::beginTransaction();

        try {

            $modifiedDate = now()->format('Y-m-d H:i:s');
            $modifiedUser = $request->ModifiedUser;

            // ✅ Check Master exists
            $master = StaffAttendanceMaster::find($attendanceMasterId);

            if (!$master) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'attendanceMasterId not found'
                ], 404);
            }

            // ✅ 1. Update Master
            $update = DB::select(
                'SELECT * FROM public.update_staff_attendance_master(?, ?, ?, ?, ?, ?)',
                [
                    $attendanceMasterId,
                    $request->date,
                    $request->narration,
                    $request->branchId,
                    $modifiedDate,
                    $modifiedUser
                ]
            );

            if ($update[0]->status != 200) {
                DB::rollBack();
                return response()->json($update[0]);
            }

            // ✅ 2. Delete Old Details
            DB::select(
                'SELECT * FROM public.delete_attendance_details_by_master(?)',
                [$attendanceMasterId]
            );



            // ✅ 3. Insert New Details
            $detailsId = (AttendanceDetails::max('attendanceDetailsId') ?? 0);

            foreach ($request->details as $detail) {

                $detailsId++;

                $result = DB::select(
                    'SELECT * FROM public.insert_attendance_details(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )',
                    [
                        $detailsId,
                        $attendanceMasterId,
                        $detail['employeeId'],
                        $detail['status'],
                        $detail['OTMnt'],
                        $request->branchId,
                        $master->CreatedDate,     // keep original created
                        $master->CreatedUser,
                        $modifiedDate,            // new modified
                        $modifiedUser
                    ]
                );

                if ($result[0]->status_code != 200) {
                    DB::rollBack();
                    return response()->json($result[0]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Attendance updated successfully'
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            // ✅ 1. Delete Attendance Details first
            DB::select(
                'SELECT * FROM public.delete_attendance_details_by_master(?)',
                [$id]
            );

            // ✅ 2. Delete Master
            $result = DB::select(
                'SELECT * FROM public.delete_staff_attendance_master(?)',
                [$id]
            );

            DB::commit();

            return response()->json([
                'status'  => $result[0]->status,
                'error'   => false,
                'message' => $result[0]->message
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function fillGrid(Request $request)
    {
        try {

            $branchId       = (string) $request->branch_id;
            $designationId  = (string) $request->designation_id;
            $statusFilter   = (string) $request->status;
            $month          = Carbon::parse($request->month)->format('Y-m-d');

            $employees = DB::select(
                'SELECT * FROM public.employeeviewwithcodebydesignationandbranchid(?::varchar, ?::varchar, ?::varchar)',
                [
                    $branchId,
                    $designationId,
                    $statusFilter
                ]
            );

            $finalData = [];

            foreach ($employees as $index => $emp) {

                $attendance = DB::select(
                    'SELECT * FROM public.attendancereportfill(?::date, ?::integer)',
                    [
                        $month,
                        (int) $emp->employeeId
                    ]
                );

                $attendanceByDay = [];

                foreach ($attendance as $att) {
                    $day = Carbon::parse($att->date)->day;
                    $attendanceByDay[$day] = $att->status;
                }

                $finalData[] = [
                    'sl_no'        => $index + 1,
                    'employeeId'   => $emp->employeeId,
                    'employeeName' => $emp->employeeName,
                    'employeeCode' => $emp->employeeCode,
                    'attendance'   => $attendanceByDay
                ];
            }

            return response()->json($finalData);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'ART04: ' . $e->getMessage()
            ], 500);
        }
    }

    public function StaffAttendanceReport(Request $request)
    {
        try {

            $data = DB::select(
                'SELECT * FROM public.staff_attendance_report(?, ?, ?, ?)',
                [
                    $request->branchId,
                    $request->date,
                    $request->userId,
                    $request->employeeId
                ]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $data
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}