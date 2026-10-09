<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;

use App\Models\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;



class GeneralSettingsController extends Controller
{
    /**
     * Display general settings
     */
    public function index()
    {
        // Call PostgreSQL get all function
        $settings = DB::select('SELECT * FROM public.get_all_general_settings()');

        // Convert result to collection for mapping
        $settingsWithUrls = collect($settings)->map(function ($setting) {
            // Convert stdClass to array
            $settingData = (array) $setting;

            // Append full URL for branchHeader
            if (!empty($settingData['branchHeader'])) {
                $settingData['branchHeader'] = Storage::disk('public')
                    ->url($settingData['branchHeader']);
            }

            // Append full URL for branchFooter
            if (!empty($settingData['branchFooter'])) {
                $settingData['branchFooter'] = Storage::disk('public')
                    ->url($settingData['branchFooter']);
            }

            return $settingData;
        });

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'General settings retrieved successfully',
            'data'    => $settingsWithUrls,
        ]);
    }


    /**
     * Update the specified general setting
     */
    public function update(Request $request, $id)
    {
        //   dd($request->CompanyLetterPad);
        $user = GeneralSettings::find($id);
        // dd($request);
        // Default to existing paths
        $branchHeader = $user->branchHeader;
        $branchFooter = $user->branchFooter;

        /* ========== Handle Header ========== */
        if ($request->hasFile('branchHeader')) {
            // Delete old file
            if ($branchHeader && Storage::disk('public')->exists($branchHeader)) {
                Storage::disk('public')->delete($branchHeader);
            }

            $file = $request->file('branchHeader');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();

            $destination = public_path('storage/general_settings');
            if (!file_exists($destination)) mkdir($destination, 0775, true);

            $file->move($destination, $filename);
            $branchHeader = 'general_settings/' . $filename;
        } elseif ($request->branchHeader) {
            // If frontend sent a path (string), keep it
            $branchHeader = $request->branchHeader;
        }

        /* ========== Handle Footer ========== */
        if ($request->hasFile('branchFooter')) {
            if ($branchFooter && Storage::disk('public')->exists($branchFooter)) {
                Storage::disk('public')->delete($branchFooter);
            }

            $file = $request->file('branchFooter');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();

            $destination = public_path('storage/general_settings');
            if (!file_exists($destination)) mkdir($destination, 0775, true);

            $file->move($destination, $filename);
            $branchFooter = 'general_settings/' . $filename;
        } elseif ($request->branchFooter) {
            $branchFooter = $request->branchFooter;
        }
        $CompanyLetterPad = $user->CompanyLetterPad;
        if ($request->hasFile('CompanyLetterPad')) {

            if ($CompanyLetterPad && Storage::disk('public')->exists(str_replace(asset('storage/'), '', $CompanyLetterPad))) {
                Storage::disk('public')->delete(str_replace(asset('storage/'), '', $CompanyLetterPad));
            }

            $file = $request->file('CompanyLetterPad');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();

            $destination = public_path('storage/general_settings');
            if (!file_exists($destination)) mkdir($destination, 0775, true);

            $file->move($destination, $filename);

            $CompanyLetterPad = asset('storage/general_settings/' . $filename);
            //   dd($CompanyLetterPad);
        } elseif ($request->CompanyLetterPad) {
            // If frontend sends already full URL or path
            $CompanyLetterPad = $request->CompanyLetterPad;
            // dd($CompanyLetterPad);
        }
        /* ========== Call PostgreSQL Function ========== */
        $result = DB::selectOne(
            'SELECT public.update_general_settings(
        ?,?,?,?,?,?,?,?,?,?,
        ?,?,?,?,?,?,?,?,?,?,
        ?,?,?,?,?,?,?,?,?,?,?,?
    ) AS result',
            [
                $id,
                $request->branchId,
                $request->ActivateTax,
                $request->costCentre,
                $request->AccountPosting,
                $request->negativeCashTransaction,
                $request->AccountCalculationMethod,
                $request->decimalPart,
                $request->dateformat,
                $request->taxType,
                $request->taxincluded,
                $request->taxCessincluded,
                $branchHeader,
                $branchFooter,
                $CompanyLetterPad,
                $request->zatcaType,
                $request->RoundOff,
                $request->RoundOffDigit,
                $request->showCurrencyprefix,
                $request->askConfirmationSave,
                $request->askConfirmationEdit,
                $request->askConfirmationDelete,
                $request->askConfirmationClear,
                $request->askConfirmationRowRemove,
                $request->askConfirmationPrint,
                $request->askConfirmationClose,
                $request->activateVanSale ?? false,  // ✅ FIXED + SAFE
                $request->boolean('showCurrentDayCashbalanceInDashboard'),
                now(),
                $request->ModifedUser,
                $request->taxLedgerId,
                $request->formType
            ]
        );
        ActivityHelper::logActivity(
            auth()->id() ?? 0,                    // UserId
            'Sales Settings',                     // ActivityType
            'Sales Settings',                     // VoucherType
            'UPDATED',                            // Action
            $request->branchId,                   // BranchId
            $request->ModifiedUser ?? 'System',   // CreatedUser
            $id,                                  // MasterId
            null,                                 // VoucherNo
            now(),                                // VoucherDate
            null,                                 // OldAmount
            null,                                 // NewAmount
            $request->systemId                       // SystemId
        );
        /* ========== Response ========== */
        if (!$result || !$result->result) {
            return response()->json([
                'status' => 404,
                'error'  => true,
                'message' => 'General settings not found or update failed'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'error'  => false,
            'message' => 'General settings updated successfully'
        ]);
    }

    public function removeImage($id, $type)
    {
        $user = GeneralSettings::find($id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Record not found'
            ], 404);
        }

        if (!in_array($type, ['header', 'footer', 'letterpad'])) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Invalid image type'
            ], 400);
        }

        $column = $type === 'header' ? 'branchHeader' : ($type === 'footer' ? 'branchFooter' : 'CompanyLetterPad');
        $filePath = $user->$column;

        // Delete file from storage
        if ($type === 'letterpad') {
            $filePath = str_replace(asset('storage/'), '', $filePath);
        }
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        // Update DB
        $user->$column = null;
        $user->save();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => ucfirst($type) . ' image removed successfully'
        ]);
    }
}