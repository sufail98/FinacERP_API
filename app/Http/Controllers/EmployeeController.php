<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SuffixPrefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class EmployeeController extends Controller
{
    public function index()
    {
        try {
            $employees = DB::select('SELECT * FROM public.get_all_employee()');

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $employees,
            ], 200);
        } catch (\Exception $e) {
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
            // 1️⃣ Call PostgreSQL function
            $result = DB::select(
                'SELECT * FROM public.get_employee_byId(?)',
                [$id]
            );

            if (empty($result)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Employee Not Found',
                ], 404);
            }

            // 2️⃣ Load employee with relationships using Eloquent
            $item = Employee::with([
                'designation:designationId,designationName',
                'department:departmentId,departmentName',
                'worklocation:workLocationId,workLocationName',
                'route:RouteId,RouteName',
                'area:AreaId,AreaName',
                'market:MarketId,MarketName',
            ])->where('employeeId', $id)->first();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $item,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function generateVoucher(Request $request)
    {
        $voucherType = $request->input('voucherType');
        $branchId    = $request->input('branchId');
        $yearId      = $request->input('yearId');
        $taxType     = $request->input('taxType');

        $codeData = voucherNoGeneration(
            $voucherType,
            $branchId,
            $yearId,
            $taxType
        );

        return response()->json([
            'status'          => 200,
            'error'           => false,
            'message'         => 'Voucher Number Generated',
            'table_pk'        => $codeData['table_pk'],
            'voucherCode'     => $codeData['voucherCode'],
            'voucherNo'       => $codeData['voucherNo'],
            'suffixPrefixId'  => $codeData['suffixPrefixId'],
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $branchId    = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType', 'Sales Invoice');
            $yearId      = (int) $request->input('yearId', 1);

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);

            /* ---------- Employee Photo Upload ---------- */
            $employeePhotoPath = null;
            if ($request->hasFile('employeephoto')) {
                $employeePhotoPath = $request->file('employeephoto')
                    ->store('employee_photo', 'public');
            }

            /* ---------- Call PostgreSQL Function ---------- */
            $result = DB::select(
                'SELECT public.insert_employee(
                ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
            ) AS "employeeId"',
                [
                    // PK + voucher
                    $codeData['table_pk'],                // employeeId
                    $codeData['suffixPrefixId'],          // suffixPrefixId
                    $codeData['voucherNo'],               // voucherNo
                    $codeData['voucherCode'],             // employeeCode

                    // Basic details
                    $request->employeeName,
                    $request->designationId,
                    $request->DepartmentId,
                    $request->workLocationId,
                    $request->dob,
                    $request->meritalStatus,
                    $request->gender,
                    $request->qualification,
                    $request->PermenentAddress,
                    $request->CurrentAddress,
                    $request->Nationality,
                    $request->whatsAppNumber,
                    $request->phoneNo,
                    $request->mobileNo,
                    $request->email,
                    $request->emergencyContactName,
                    $request->emergencyContactNumber,
                    $request->joiningDate,
                    $request->terminationDate,
                    $request->active,
                    $request->narration,
                    $request->bloodGroup,

                    // Location
                    $request->routeId,
                    $request->areaId,
                    $request->marketId,

                    // Passport / Visa
                    $request->passportNo,
                    $request->passportIssueDate,
                    $request->passportExpDate,
                    $request->visaType,
                    $request->labourCardNo,
                    $request->labourCardIssueDate,
                    $request->labourCardExpDate,
                    $request->visaNo,
                    $request->visaExpDate,

                    // Salary / Insurance
                    $request->OTHourlyAmount,
                    $request->InsuranceNumber,
                    $request->InsuranceExpirydate,
                    $request->MOLContractNumber,
                    $request->SponsorName,
                    $request->GOSI_SSOnumber,
                    $request->WPScompliance,

                    // Bank
                    $request->BankName,
                    $request->BankAccountNo,
                    $request->IBAN,
                    $request->SwiftCode,
                    $request->BankBranch,

                    // Photo + misc
                    $employeePhotoPath,
                    $request->documentuploadOptionNeeded,

                    // Audit
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->CreatedUser,
                    $branchId
                ]
            );

            DB::commit();

            return response()->json([
                'status'     => 201,
                'error'      => false,
                'message'    => 'Employee Created Successfully',

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
            // 1️⃣ Check employee exists
            $exists = DB::select(
                'SELECT * FROM public.get_employee_byId(?)',
                [$id]
            );

            if (empty($exists)) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Employee Not Found',
                ], 404);
            }

            // 2️⃣ Handle employee photo
            $employeephoto = $exists[0]->employeephoto ?? null;

            if ($request->hasFile('employeephoto')) {
                if ($employeephoto && Storage::disk('public')->exists($employeephoto)) {
                    Storage::disk('public')->delete($employeephoto);
                }
                $employeephoto = $request
                    ->file('employeephoto')
                    ->store('employee_photo', 'public');
            }

            // 3️⃣ Call PostgreSQL update function
            DB::select(
                'SELECT public.update_employee(
                ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
            )',
                [
                    // PK
                    $id,

                    // Basic details
                    $request->employeeName,
                    $request->designationId,
                    $request->DepartmentId,
                    $request->workLocationId,
                    $request->dob,
                    $request->meritalStatus,
                    $request->gender,
                    $request->qualification,
                    $request->PermenentAddress,
                    $request->CurrentAddress,
                    $request->Nationality,
                    $request->whatsAppNumber,
                    $request->phoneNo,
                    $request->mobileNo,
                    $request->email,
                    $request->emergencyContactName,
                    $request->emergencyContactNumber,
                    $request->joiningDate,
                    $request->terminationDate,
                    $request->active,
                    $request->narration,
                    $request->bloodGroup,

                    // Route / Area / Market
                    $request->routeId,
                    $request->areaId,
                    $request->marketId,

                    // Passport / Visa
                    $request->passportNo,
                    $request->passportIssueDate,
                    $request->passportExpDate,
                    $request->visaType,
                    $request->labourCardNo,
                    $request->labourCardIssueDate,
                    $request->labourCardExpDate,
                    $request->visaNo,
                    $request->visaExpDate,

                    // Finance
                    $request->OTHourlyAmount,
                    $request->InsuranceNumber,
                    $request->InsuranceExpirydate,
                    $request->MOLContractNumber,
                    $request->SponsorName,
                    $request->GOSI_SSOnumber,
                    $request->WPScompliance,

                    // Bank
                    $request->BankName,
                    $request->BankAccountNo,
                    $request->IBAN,
                    $request->SwiftCode,
                    $request->BankBranch,

                    // File + flags
                    $employeephoto,
                    $request->documentuploadOptionNeeded,

                    // Audit
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->ModifiedUser,
                    $request->branchId
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Employee Updated Successfully',
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
            // 1️⃣ Check employee exists (to handle photo cleanup)
            $employee = DB::select(
                'SELECT * FROM public.get_employee_byId(?)',
                [$id]
            );

            if (empty($employee)) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Employee Not Found',
                ], 404);
            }

            // 2️⃣ Delete employee photo from storage
            $photo = $employee[0]->employeephoto ?? null;
            if ($photo && Storage::disk('public')->exists($photo)) {
                Storage::disk('public')->delete($photo);
            }

            // 3️⃣ Call delete function
            $result = DB::select(
                'SELECT public.delete_employee(?) AS deleted',
                [$id]
            );

            if (!$result[0]->deleted) {
                DB::rollBack();
                return response()->json([
                    'status'  => 400,
                    'error'   => true,
                    'message' => 'Employee could not be deleted',
                ], 400);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Employee Deleted Successfully',
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