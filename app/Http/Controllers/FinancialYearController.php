<?php

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class FinancialYearController extends Controller
{
    public function index()
    {
        try {
            $years = DB::select("SELECT * FROM public.get_all_financial_years()");

            $data = collect($years)->map(function ($year) {

                $months = DB::select(
                    "SELECT * FROM public.get_months_by_year(?)",
                    [$year->yearId]
                );

                return [
                    'yearId'      => $year->yearId,
                    'fromDate'    => \Carbon\Carbon::parse($year->fromDate)->format('d-m-Y'),
                    'toDate'      => \Carbon\Carbon::parse($year->toDate)->format('d-m-Y'),
                    'closed'      => (bool) $year->closed,
                    'CreatedUser' => $year->CreatedUser,
                    'months'      => collect($months)->map(function ($m) {
                        return [
                            'monthId'     => $m->monthId,
                            'MonthName'   => $m->MonthName,
                            'closed'      => (bool) $m->closed,
                            'CreatedUser' => $m->CreatedUser,
                        ];
                    }),
                ];
            });

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Financial Years List',
                'data'    => $data,
            ]);
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
            $years = DB::select(
                "SELECT * FROM public.get_financial_year_by_id(?)",
                [$id]
            );

            if (empty($years)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Financial Year not found',
                ], 404);
            }

            $year = $years[0];

            $months = DB::select(
                "SELECT * FROM public.get_months_by_year(?)",
                [$year->yearId]
            );

            $data = [
                'yearId'      => $year->yearId,
                'fromDate'    => \Carbon\Carbon::parse($year->fromDate)->format('d-m-Y'),
                'toDate'      => \Carbon\Carbon::parse($year->toDate)->format('d-m-Y'),
                'closed'      => (bool) $year->closed,
                'CreatedUser' => $year->CreatedUser,
                'months'      => collect($months)->map(function ($m) {
                    return [
                        'monthId'     => $m->monthId,
                        'MonthName'   => $m->MonthName,
                        'closed'      => (bool) $m->closed,
                        'CreatedUser' => $m->CreatedUser,
                    ];
                }),
            ];

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Financial Year Details',
                'data'    => $data,
            ]);
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
        //dd($request->all());
        DB::beginTransaction();

        try {
            $fromDate = Carbon::parse($request->fromDate)->startOfDay();
            $toDate   = Carbon::parse($request->toDate)->endOfDay();

            // 1️⃣ Check overlapping financial years
            $overlap = DB::table('tbl_FinancialYear')
                ->where(function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('fromDate', [$fromDate, $toDate])
                        ->orWhereBetween('toDate', [$fromDate, $toDate])
                        ->orWhere(function ($q) use ($fromDate, $toDate) {
                            $q->where('fromDate', '<=', $fromDate)
                                ->where('toDate', '>=', $toDate);
                        });
                })
                ->exists();

            if ($overlap) {
                return response()->json([
                    'status'  => 409,
                    'error'   => true,
                    'message' => 'A financial year already exists in this date range.',
                ], 409);
            }

            // 2️⃣ Prevent multiple active financial years
            if ($request->closed == false) {
                $activeExists = DB::table('tbl_FinancialYear')
                    ->where('closed', false)
                    ->exists();

                if ($activeExists) {
                    return response()->json([
                        'status'  => 409,
                        'error'   => true,
                        'message' => 'Another active financial year already exists. Please close it before creating a new one.',
                    ], 409);
                }
            }

            // 3️⃣ Insert Financial Year (DB Function)
            $yearResult = DB::select(
                'SELECT public.insert_financial_year(?, ?, ?, ?, ?, ?) AS "yearId"',
                [
                    $fromDate,
                    $toDate,
                    $request->closed,
                    now('Asia/Kolkata'),
                    $request->CreatedUser,
                    $request->branchId
                ]
            );

            $newYearId = $yearResult[0]->yearId;

            // 4️⃣ Insert Months
            $current = $fromDate->copy()->startOfMonth();

            while ($current->lte($toDate)) {

                DB::select(
                    'SELECT public.insert_financialyearmonth(?, ?, ?, ?, ?, ?) AS monthid',
                    [
                        $newYearId,
                        $current->format('F-Y'), // Example: April-2025
                        $request->closed,
                        now('Asia/Kolkata'),
                        $request->CreatedUser,
                        $request->branchId,

                    ]
                );

                $current->addMonth();
            }

            DB::commit();


            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Financial Year Created Successfully',
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
            $fromDate = Carbon::parse($request->fromDate)->startOfDay();
            $toDate   = Carbon::parse($request->toDate)->endOfDay();

            // 1️⃣ Overlap check
            $overlap = DB::table('tbl_FinancialYear')
                ->where('yearId', '!=', $id)
                ->where(function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('fromDate', [$fromDate, $toDate])
                        ->orWhereBetween('toDate', [$fromDate, $toDate])
                        ->orWhere(function ($q) use ($fromDate, $toDate) {
                            $q->where('fromDate', '<=', $fromDate)
                                ->where('toDate', '>=', $toDate);
                        });
                })
                ->exists();

            if ($overlap) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'A financial year already exists in this date range.',
                ], 409);
            }

            // 2️⃣ Only one active year
            if ($request->closed == false) {
                $activeExists = DB::table('tbl_FinancialYear')
                    ->where('closed', false)
                    ->where('yearId', '!=', $id)
                    ->exists();

                if ($activeExists) {
                    return response()->json([
                        'status' => 409,
                        'error' => true,
                        'message' => 'Another active financial year already exists.',
                    ], 409);
                }
            }

            // 3️⃣ Update Financial Year
            DB::statement('SELECT public.update_financial_year(?, ?, ?, ?, ?, ?, ?)', [
                $id,
                $fromDate,
                $toDate,
                $request->closed,
                now('Asia/Kolkata'),
                $request->ModifiedUser,
                $request->branchId
            ]);

            // 4️⃣ Delete Old Months
            DB::statement('SELECT public.delete_financialyearmonth(?)', [$id]);

            // 5️⃣ Recreate Months
            $current = $fromDate->copy()->startOfMonth();

            while ($current->lte($toDate)) {

                DB::statement('SELECT public.insert_financialyearmonth(?, ?, ?, ?, ?, ?)', [
                    $id,
                    $current->format('F-Y'),
                    $request->closed,
                    now('Asia/Kolkata'),   // CreatedDate
                    $request->ModifiedUser,
                    $request->branchId,

                ]);

                $current->addMonth();
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Financial Year Updated Successfully',
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

    public function destroy($yearId)
    {
        DB::beginTransaction();
        try {
            DB::statement('SELECT public.delete_financialyearmonth(?)', [$yearId]);

            // Call the DB function to delete the year and its months
            $result = DB::selectOne(
                'SELECT public.delete_financial_year(?) AS success',
                [$yearId]
            );

            if (!$result->success) {
                DB::rollBack();
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Financial Year not found',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Financial Year Deleted Successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }




    public function toggleStatus($id)
    {
        DB::beginTransaction();
        try {
            $year = FinancialYear::find($id);
            if (!$year) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Financial Year not found',
                ], 404);
            }

            // Step 1: Close all others
            DB::statement("SELECT public.close_other_financial_years(?)", [$id]);

            // Step 2: Activate this one
            DB::statement("SELECT public.activate_financial_year(?)", [$id]);

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Financial Year Activated Successfully',
            ]);
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
