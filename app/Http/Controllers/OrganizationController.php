<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::all();
        return response()->json([
            'status' => 200,
            'error' => false,
            'messages' => 'Organization Retrieved Successfully',
            'organizations' => $organizations,
        ], 200);
    }
    public function AppVserionUpdate(Request $request)
    {
        $appVersion = $request->input('AppVersion');
        $id = $request->input('OrganizationId');

        // Update the AppVersion for the specified organization
        $updated = Organization::where('OrganizationId', $id)->update(['AppVersion' => $appVersion]);

        if ($updated) {
            return response()->json([
                'status' => 200,
                'error' => false,
                'messages' => 'App Version Updated Successfully',
            ], 200);
        } else {
            return response()->json([
                'status' => 400,
                'error' => true,
                'messages' => 'Failed to Update App Version',
            ], 400);
        }
    }

    public function GetOrganization(Request $request)
    {
        $slno = $request->slno;
        $organization = DB::select('SELECT * FROM public.get_organization_by_serial(?)', [$slno]);

        // Check if found
        if (empty($organization)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'messages' => 'Organization Not Found',
            ], 404);
        }

        // Encrypt DatabaseName for each company
        $organization = array_map(function ($company) {
            // Convert stdClass to array first if needed
            $company = (array) $company;

            if (!empty($company['DatabaseName'])) {
                $company['DatabaseNameDecrypt'] = $company['DatabaseName'];
                $company['DatabaseName'] = Crypt::encryptString($company['DatabaseName']);
            }

            return $company;
        }, $organization);

        return response()->json([
            'status' => 200,
            'error' => false,
            'messages' => 'Organization Retrieved Successfully',
            'organization_details' => $organization,
        ], 200);
    }
}
