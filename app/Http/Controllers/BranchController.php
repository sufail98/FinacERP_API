<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function index()
    {
        try {
            // Call PostgreSQL function to get all branches
            $branches = DB::select('SELECT * FROM public.get_all_branch()');

            // Map logo path to full URL
            $branches = array_map(function ($branch) {
                if (!empty($branch->logo)) {
                    $branch->logoUrl = asset('storage/' . $branch->logo);
                } else {
                    $branch->logoUrl = null;
                }
                return $branch;
            }, $branches);

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Branches retrieved successfully',
                'data' => $branches
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $branch = DB::select(
                'SELECT * FROM public.get_branch_byId(?)',
                [$id]
            );

            if (empty($branch)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Branch not found'
                ], 404);
            }

            $branch = $branch[0]; // single row

            // Convert logo path to full URL
            if (!empty($branch->logo)) {
                $branch->logoUrl = asset('storage/' . $branch->logo);
            } else {
                $branch->logoUrl = null;
            }

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Branch retrieved successfully',
                'data'   => $branch
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function store(Request $request)
    {
        //dd($request->all());
        DB::beginTransaction();

        try {
            // 1️⃣ Handle logo upload using Laravel Storage
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();

                // Store file in storage/app/public/branch_logos
                $logoPath = $file->storeAs('branch_logos', $filename, 'public');

                // $logoPath will be something like "branch_logos/abc123.png"
            }

            // 2️⃣ Call PostgreSQL function with proper parameters
            $result = DB::selectOne(
                'SELECT public.insert_branch(
                ?,?,?,?,?,?,?,?,?,?,
                ?,?,?,?,?,?,?,?,?,?,
                ?,?,?,?,?,?,?,?,?,?,
                ?,?,?,?,?,?,?,?
            ) AS "branchId"',
                [
                    $request->companyId,
                    $request->currencyId,
                    $request->branchName,
                    $request->branchNameFL,
                    $request->address,
                    $request->addressFL,
                    $request->streetName,
                    $request->streetNameFL,
                    $request->buildingNo,
                    $request->buildingNoFL,
                    $request->additionalNumber,
                    $request->additionalNumberFL,
                    $request->cityName,
                    $request->cityNameFL,
                    $request->district,
                    $request->districtFL,
                    $request->country,
                    $request->countryFL,
                    $request->postalCode,
                    $request->postalCodeFL,
                    $request->phoneNo,
                    $request->fax,
                    $request->mobile,
                    $request->email,
                    $request->web,
                    $request->taxNo,
                    $request->panNo,
                    $request->crNo,
                    $request->mainBranch ?? false,
                    $request->startDate,
                    $request->dbName,
                    $request->activeStatus ?? true,
                    $request->subscriptionNo,
                    $request->timezone ?? 'UTC',   // ✅ Added here
                    now()->format('Y-m-d H:i:s'),
                    $request->createdUser,
                    $request->branchCode,
                    $logoPath
                ]
            );

            DB::commit();

            return response()->json([
                'status'   => 201,
                'error'    => false,
                'message'  => 'Branch created successfully',

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

            // 1️⃣ Get existing branch (for old logo)
            $branch = DB::table('tbl_Branch')
                ->where('branchId', $id)
                ->first();

            if (!$branch) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Branch not found'
                ], 404);
            }

            // 2️⃣ Handle logo upload
            $logoPath = $branch->logo;

            // if ($request->hasFile('logo')) {

            //     // delete old logo
            //     if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            //         Storage::disk('public')->delete($logoPath);
            //     }

            //     $logoPath = $request->file('logo')
            //         ->store('branch_logos', 'public');
            // }

            if ($request->hasFile('logo')) {

                // Ensure directory exists and set directory permission
                $dir = Storage::disk('public')->path('branch_logos');
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                } else {
                    @chmod($dir, 0775);
                }

                // Delete old logo
                if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                    Storage::disk('public')->delete($logoPath);
                }

                // Store new logo
                $logoPath = $request->file('logo')->store('branch_logos', 'public');

                // Set file permission to 775
                $fullPath = Storage::disk('public')->path($logoPath);
                @chmod($fullPath, 0775);
            }
            // 3️⃣ Call PostgreSQL update function
            $result = DB::select(
                'SELECT public.update_branch(
                ?,?,?,?,?,?,?,?,?,?,
                ?,?,?,?,?,?,?,?,?,?,
                ?,?,?,?,?,?,?,?,?,?,
                ?,?,?,?,?,?,?,?,?
            ) AS "branchId"',
                [
                    $id,                              // branchId
                    $request->companyId,
                    $request->currencyId,
                    $request->branchName,
                    $request->branchNameFL,
                    $request->address,
                    $request->addressFL,
                    $request->streetName,
                    $request->streetNameFL,
                    $request->buildingNo,
                    $request->buildingNoFL,
                    $request->additionalNumber,
                    $request->additionalNumberFL,
                    $request->cityName,
                    $request->cityNameFL,
                    $request->district,
                    $request->districtFL,
                    $request->country,
                    $request->countryFL,
                    $request->postalCode,
                    $request->postalCodeFL,
                    $request->phoneNo,
                    $request->fax,
                    $request->mobile,
                    $request->email,
                    $request->web,
                    $request->taxNo,
                    $request->panNo,
                    $request->crNo,
                    $request->mainBranch ?? false,
                    $request->startDate,
                    $request->dbName,
                    $request->activeStatus ?? true,
                    $request->subscriptionNo,
                    $request->timezone ?? $branch->timezone ?? 'UTC',

                    now(),                           // modifiedDate
                    $request->modifiedUser,
                    $request->branchCode,
                    $logoPath
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Branch updated successfully',
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
    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            // 1️⃣ Get branch (for logo path)
            $branch = DB::table('tbl_Branch')
                ->where('branchId', $id)
                ->first();

            if (!$branch) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Branch not found'
                ], 404);
            }

            // 2️⃣ Delete logo if exists
            if ($branch->logo && Storage::disk('public')->exists($branch->logo)) {
                Storage::disk('public')->delete($branch->logo);
            }

            // 3️⃣ Call delete function
            $result = DB::select(
                'SELECT public.delete_branch(?) AS "deleted"',
                [$id]
            );

            if (!$result[0]->deleted) {
                DB::rollBack();

                return response()->json([
                    'status' => 400,
                    'error'  => true,
                    'message' => 'Unable to delete branch'
                ], 400);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Branch deleted successfully'
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

    /**
     * Toggle activeStatus between active (1) and inactive (0)
     */
    public function toggleStatus($id)
    {
        DB::beginTransaction();

        try {
            $result = DB::select(
                'SELECT public.toggle_branch_status(?) AS "activeStatus"',
                [$id]
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Branch status updated successfully',
                'data'   => [
                    'branchId'     => $id,
                    'activeStatus' => $result[0]->activeStatus
                ]
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
}