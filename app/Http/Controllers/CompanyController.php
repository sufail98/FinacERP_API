<?php

namespace App\Http\Controllers;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Traits\DatabaseContext;


class CompanyController extends Controller
{
    use DatabaseContext;
    public function index() {
        $companies = Company::all();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Companies',
            'data' => $companies
        ]);
    }

    public function show($id) {
        $company = Company::findOrFail($id);
        
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Company details',
            'data' => $company
        ]);
    }
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
		try {

            $company = Company::findOrFail($id);

            $company->companyName = $request->companyName;
            $company->companyNameFL = $request->companyNameFL;
            $company->companyAddress = $request->companyAddress;
            $company->phoneNo = $request->phoneNo;
            $company->AlternativePhoneNo = $request->AlternativePhoneNo;
            $company->createdDate = $request->createdDate ?? null;
            $company->modifiedDate = $request->modifiedDate ?? null;
            $company->modifiedUser = $request->modifiedUser ?? null;

            // Handle logo upload
            if ($request->hasFile('companyLogo')) {
                // Delete old file if exists
                if ($company->companyLogo && Storage::disk('public')->exists($company->companyLogo)) {
                    Storage::disk('public')->delete($company->companyLogo);
                }

                // Store new file
                // $path = $request->file('companyLogo')->store('company_logos', 'public');
                // $company->companyLogo = $path;



                $file = $request->file('companyLogo');
				$extension = $file->getClientOriginalExtension();

				$filename = uniqid() . '.' . $extension;
	
				$destination = public_path('storage/company_logos');
				if (!file_exists($destination)) {
					mkdir($destination, 0775, true);
				}

				$file->move($destination, $filename);
				$company->companyLogo = 'company_logos/' . $filename;
            }

            $company->save();
            DB::commit();

            // Refresh the model to get the updated data with accessors
            $company->refresh();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Company updated successfully',
                'data' => $company
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

    /**
     * Get companies with database context information
     */
    public function indexWithContext(Request $request) {
        $companies = Company::all();
        $dbContext = $this->getDatabaseContext($request);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Companies with database context',
            'data' => [
                'companies' => $companies,
                'database_context' => $dbContext
            ]
        ]);
    }
}
