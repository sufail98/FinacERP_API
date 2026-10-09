<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalarySettings;
use App\Models\SalarySettingsDetails;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class SalarySettingsController extends Controller
{

    public function index($Id = null)
    {
        try {

            $branchId = is_numeric($Id) ? (int)$Id : null;

            // ✅ Get Master
            $masters = DB::select(
                'SELECT * FROM public.get_all_salary_settings(?)',
                [$branchId]
            );

            // ✅ Get All Details
            $details = DB::select(
                'SELECT * FROM public.get_all_salary_settings_details(?)',
                [$branchId]
            );

            // ✅ Map details to each master
            $data = [];

            foreach ($masters as $master) {

                $masterDetails = array_values(array_filter($details, function ($d) use ($master) {
                    return $d->salarySettingsId == $master->salarySettingsId;
                }));

                $data[] = [
                    'master'  => $master,
                    'details' => $masterDetails
                ];
            }

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $data
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($salarySettingsId)
    {
        try {

            // ✅ Get Master
            $master = DB::select(
                'SELECT * FROM public.get_salary_settings_by_id(?)',
                [$salarySettingsId]
            );

            if (empty($master)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'salarySettingsId not found'
                ], 404);
            }

            // ✅ Get Details (by masterId - IMPORTANT)
            $details = DB::select(
                'SELECT * FROM public.get_salary_settings_details_by_master(?)',
                [$salarySettingsId]
            );

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

            $salarySettingsId = (SalarySettings::max('salarySettingsId') ?? 0) + 1;

            $createdDate = now()->format('Y-m-d H:i:s');
            $createdUser = $request->CreatedUser;

            // ✅ Insert Master
            $master = DB::select(
                'SELECT * FROM public.insert_salary_settings(?, ?, ?, ?, ?, ?)',
                [
                    $salarySettingsId,
                    $request->employeeId,
                    $request->date,
                    $request->branchId,
                    $createdDate,
                    $createdUser
                ]
            );

            if ($master[0]->status != 200) {
                DB::rollBack();
                return response()->json($master[0]);
            }

            // if (empty($request->details)) {
            //     DB::rollBack();
            //     return response()->json([
            //         'status' => 400,
            //         'error'  => true,
            //         'message'=> 'Details required'
            //     ]);
            // }

            $detailsId = SalarySettingsDetails::max('salarySettingsDetailsId') ?? 0;

            foreach ($request->details as $detail) {

                $detailsId++;

                $result = DB::select(
                    'SELECT * FROM public.insert_salary_settings_details(
                        ?::bigint,
                        ?::integer,
                        ?::integer,
                        ?::varchar,
                        ?::numeric,
                        ?::integer,
                        ?::integer,
                        ?::timestamp,
                        ?::varchar,
                        NULL,
                        NULL
                    )',
                    [
                        $detailsId,
                        $salarySettingsId,
                        $detail['payheadId'],
                        $detail['AmtOrPer'],
                        $detail['amount'],
                        $detail['payheadIdOf'] ?? null,
                        $request->branchId,
                        $createdDate,
                        $createdUser
                    ]
                );

                if ($result[0]->status != 200) {
                    DB::rollBack();
                    return response()->json($result[0]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 201,
                'error'  => false,
                'message' => 'Inserted successfully'
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




    public function update(Request $request, $salarySettingsId)
    {
        DB::beginTransaction();

        try {

            // ✅ Check master exists
            $salarySettings = SalarySettings::find($salarySettingsId);

            if (!$salarySettings) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'SalarySettings not found',
                ], 404);
            }

            // ✅ Prepare Modified values (MASTER)
            $modifiedDate = now()->format('Y-m-d H:i:s');
            $modifiedUser = $request->ModifiedUser;

            // ✅ Update Master
            $master = DB::select(
                'SELECT * FROM public.update_salary_settings(?, ?, ?, ?, ?, ?)',
                [
                    $salarySettingsId,
                    $request->employeeId,
                    $request->date,
                    $request->branchId,
                    $modifiedDate,
                    $modifiedUser
                ]
            );

            if ($master[0]->status != 200) {
                DB::rollBack();
                return response()->json($master[0]);
            }

            // ✅ Delete old details using function
            $delete = DB::select(
                'SELECT * FROM public.delete_salary_settings_details_by_master(?)',
                [$salarySettingsId]
            );

            if (!in_array($delete[0]->status, [200, 404])) {
                DB::rollBack();
                return response()->json($delete[0]);
            }

            // ❌ Validate details
            // if (empty($request->details)) {
            //     DB::rollBack();
            //     return response()->json([
            //         'status' => 400,
            //         'error'  => true,
            //         'message'=> 'Details required'
            //     ]);
            // }

            // ✅ Prepare new detail ID
            $detailsId = SalarySettingsDetails::max('salarySettingsDetailsId') ?? 0;

            // ✅ Insert new details (with SAME modified values as master)
            foreach ($request->details as $detail) {

                $detailsId++;

                $result = DB::select(
                    'SELECT * FROM public.insert_salary_settings_details(
                        ?::bigint,
                        ?::integer,
                        ?::integer,
                        ?::varchar,
                        ?::numeric,
                        ?::integer,
                        ?::integer,
                        ?::timestamp,
                        ?::varchar,
                        ?::timestamp,
                        ?::varchar
                    )',
                    [
                        $detailsId,
                        $salarySettingsId,
                        $detail['payheadId'],
                        $detail['AmtOrPer'],
                        $detail['amount'],
                        $detail['payheadIdOf'] ?? null,
                        $request->branchId,

                        // 🔥 SAME as master
                        $modifiedDate,   // CreatedDate
                        $modifiedUser,   // CreatedUser
                        $modifiedDate,   // ModifiedDate
                        $modifiedUser    // ModifiedUser
                    ]
                );

                if ($result[0]->status != 200) {
                    DB::rollBack();
                    return response()->json($result[0]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Salary settings updated successfully'
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $salarySettings = SalarySettings::find($id);

            if (!$salarySettings) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'salarySettingsId not found',
                ], 404);
            }

            // ✅ Delete Details FIRST
            $details = DB::select(
                'SELECT * FROM public.delete_salary_settings_details_by_master(?)',
                [$id]
            );

            if ($details[0]->status != 200) {
                DB::rollBack();
                return response()->json($details[0]);
            }

            // ✅ Then Delete Master
            $master = DB::select(
                'SELECT * FROM public.delete_salary_settings(?)',
                [$id]
            );

            if ($master[0]->status != 200) {
                DB::rollBack();
                return response()->json($master[0]);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'SalarySettings and details deleted successfully',
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function fillGrid(Request $request)
    {
        $designationId = (string)($request->input('designation_id', '0'));
        $branchId      = (int)($request->input('branch_id'));
        $categoryId    = (string)($request->input('category_id', '0'));
        $cashOrBankId  = (string)($request->input('cash_or_bank_id', '0'));

        $month = Carbon::parse($request->input('month'))
            ->startOfMonth()
            ->toDateString();

        $details = DB::select(
            'select * from public."SalaryVoucherViewByDesignationAndBranchIdGridFill"(?, ?, ?, ?, ?)',
            [$designationId, $month, $branchId, $categoryId, $cashOrBankId]
        );

        $masterId = '';
        if (!empty($details) && isset($details[0]->salarymasterid)) {
            $masterId = (string)$details[0]->salarymasterid;
        }

        $salaryDetails1Ids = collect($details)
            ->pluck('salarydetails1id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $payheadMapBySalaryDetails1Id = [];

        if (!empty($salaryDetails1Ids)) {

            $rows2 = DB::table('tbl_SalaryDetails2 as sd2')
                ->select([
                    'sd2.SalaryDetails1Id as salarydetails1id',
                    'sd2.PayheadId as payheadid',
                    'sd2.Amount as amount',
                ])
                ->whereIn('sd2.SalaryDetails1Id', $salaryDetails1Ids)
                ->get();

            foreach ($rows2 as $r) {
                $sid = (string)$r->salarydetails1id;
                $pid = (string)$r->payheadid;

                $payheadMapBySalaryDetails1Id[$sid][$pid] = (string)$r->amount;
            }
        }

        $gridRows = [];
        $totalPaidSalary = 0.0;
        $totalSalary     = 0.0;

        foreach ($details as $i => $row) {

            $sid = (string)($row->salarydetails1id ?? '');

            $payheads = $sid !== ''
                ? ($payheadMapBySalaryDetails1Id[$sid] ?? [])
                : [];

            $gridRow = [
                'employeeId'    => (string)($row->employeeid ?? ''),
                'sl_no'         => $i + 1,
                'employeeName'  => (string)($row->employeename ?? ''),
                'employeeCode'  => (string)($row->employeecode ?? ''),
                'salaryDate'    => (string)($row->salarydate ?? ''),
                'bonus'         => (string)($row->bonus ?? '0'),
                'deduction'     => (string)($row->deduction ?? '0'),
                'advance'       => (string)($row->amount ?? '0'),
                'lop'           => (string)($row->lop ?? '0'),
                'paidSalary'    => (string)($row->salary ?? '0'),
            ];

            foreach ($payheads as $payheadId => $amt) {
                $gridRow[$payheadId] = $amt;
            }

            /* ✅ Calculate Salary */
            $calculatedSalary = $this->calculateSalary($gridRow, $payheads);
            $gridRow['salary'] = (string)$calculatedSalary;

            if ($gridRow['paidSalary'] !== '') {
                $totalPaidSalary += (float)$gridRow['paidSalary'];
                $totalSalary     += (float)$gridRow['salary'];
            }

            $gridRows[] = $gridRow;
        }

        if (!empty($gridRows)) {
            $gridRows[] = [
                'employeeName' => 'Total :',
                'paidSalary'   => (string)$totalPaidSalary,
                'salary'       => (string)$totalSalary,
            ];
        }

        return response()->json([
            'masterId' => $masterId,
            'rows'     => $gridRows,
            'sum'      => (string)$totalSalary,
        ]);
    }


    private function calculateSalary(array $row, array $payheads): float
    {

        return (float)($row['paidSalary'] ?? 0);
    }
}