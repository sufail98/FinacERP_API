<?php

namespace App\Http\Controllers;

use App\Models\CostCentre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use App\Services\DatabaseService;

use Carbon\Carbon;

class CostCentreController extends Controller
{
    public function index()
    {
        try {
        $result = DB::select('SELECT * FROM public.get_all_costcentres()');

        return response()->json([
            'status' => 200,
            'error'  => false,
            'data'   => $result
        ], 200);

        } catch (\Throwable $e) {
        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage()
        ], 500);
        }
    }


    public function show($id)
    {
        try {
        $result = DB::selectOne(
            'SELECT * FROM public.get_costcentre_byid(?)',
            [$id]
        );

        if (!$result) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Cost Centre not found'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'error'  => false,
            'data'   => $result
        ], 200);

        } catch (\Throwable $e) {
        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage()
        ], 500);
        }
    }


    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            // 🔢 Calculate next costCentreId manually
            $nextId = (CostCentre::max('costCentreId') ?? 0) + 1;

            // 🔁 Call PostgreSQL function
            $result = DB::select(
                'SELECT public.insert_costcentre(?,?,?,?,?,?) AS out_costCentreId',
                [
                    $nextId,
                    $request->CostCentre,
                    $request->Narration,
                    $request->CreatedUser,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->branchId,
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Cost Centre Inserted Successfully',
                'costCentreId' => $nextId
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


    public function update(Request $request, $costCentreId)
    {
      DB::beginTransaction();

        try {
        $results = DB::select(
            'SELECT public.update_costcentre(?,?,?,?,?,?) AS out_costcentreid',
            [
                (int) $costCentreId,
                (string) $request->CostCentre,
                (string) $request->Narration,
                (string) $request->ModifiedUser,
                now()->format('Y-m-d H:i:s'),
                (int) $request->branchId
            ]
            );

          if (empty($results) || $results[0]->out_costcentreid === null) {
            DB::rollBack();
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Cost Centre not found or update failed'
            ], 404);
            }

          DB::commit();

         return response()->json([
            'status'       => 200,
            'error'        => false,
            'costCentreId' => $results[0]->out_costcentreid,
            'message'      => 'Cost Centre updated successfully'
        ], 200);

          } catch (\Throwable $e) {
          DB::rollBack();
          return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage()
        ], 500);
        }
     }


   public function destroy($costCentreId)
   {
      DB::beginTransaction();

        try {
         $results = DB::select(
            'SELECT public.delete_costcentre(?) AS out_costcentreid',
            [(int) $costCentreId]
         );

         if (empty($results) || $results[0]->out_costcentreid === null) {
            DB::rollBack();
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Cost Centre not found'
            ], 404);
         }

         DB::commit();

         return response()->json([
            'status'       => 200,
            'error'        => false,
            'message'      => 'Cost Centre deleted successfully'
         ], 200);

        } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage()
        ], 500);
        }
    }
    


    public function costcentrereport(Request $request)
    {
        DB::beginTransaction();

        try {

            
            $result = DB::select(
                'SELECT * from public.cost_centre_report(?,?,?,?,?)',
                [
                    $request->fromdate,
                    $request->todate,
                    $request->branchid,
                    $request->currencyid,
                    $request->costcentreid,
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Cost Centre report retrieved Successfully',
                'data'   => $result
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
















    

    public function storedefaultdata($db)
	{
		DB::beginTransaction();
		try {
            $encryptedDb = Crypt::encryptString((string) $db);
            $result = DatabaseService::switchToDatabase($encryptedDb);

            if (!$result['success']) {
                return response()->json($result, 500);
            }


            // Insert default tbl_Currency
            $currency = [
                ['currencyId' => 1,'currencySymbol' => 'Dh' ,  'currencyName'  => 'Dirham'       , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'United Arab Emirates', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 2,'currencySymbol' => 'Af' ,  'currencyName'  => 'Afghani'      , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Afghanistan', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 3,'currencySymbol' => 'A$' ,  'currencyName'  => 'Dollar'       , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Australia', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 4,'currencySymbol' => 'Tk' ,  'currencyName'  => 'Taka'         , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Bangladesh', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 5,'currencySymbol' => 'D'  ,  'currencyName'  => 'Dinar'        , 'subunitName' =>  null,     'noOfDecimalPlace' => 3, 'narration' =>  'Bahrain', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 6,'currencySymbol' => 'C$' ,  'currencyName'  => 'Dollar'       , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Canada', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 7,'currencySymbol' => 'SFr',  'currencyName'  => 'Franc'        , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Switzerland', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 8,'currencySymbol' => 'Y'  ,  'currencyName'  => 'Yuan Renminbi', 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'China', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 9,'currencySymbol' => 'DM' ,  'currencyName'  => 'Deutsche Mark', 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Germany', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 10,'currencySymbol' => '£E' ,  'currencyName'  => 'Pound'        , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Egypt', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 11,'currencySymbol' => 'pts',  'currencyName'  => 'Peseta'       , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Spain', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 12,'currencySymbol' => 'F'  ,  'currencyName'  => 'Franc'        , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'France', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 13,'currencySymbol' => '£'  ,  'currencyName'  => 'Pound'        , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'United Kingdom', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 14,'currencySymbol' => 'HK$',  'currencyName'  => 'Dollar'       , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Hong Kong', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 15,'currencySymbol' => 'Rp' ,  'currencyName'  => 'Rupiah'       , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Indonesia', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 16,'currencySymbol' => 'Rs' ,  'currencyName'  => 'Rupee'        , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'India', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 17,'currencySymbol' => 'Rls',  'currencyName'  => 'Rial'         , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Iran' , 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 18,'currencySymbol' => 'Lit',  'currencyName'  => 'Lira'         , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Italy', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 19,'currencySymbol' => '¥'  ,  'currencyName'  => 'Yen'          , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Japan', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 20,'currencySymbol' => 'Wn' ,  'currencyName'  => 'Won'          , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Korea (North)', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 21,'currencySymbol' => 'W'  ,  'currencyName'  => 'Won'          , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Korea (South)', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 22,'currencySymbol' => 'Rf' ,  'currencyName'  => 'Rufiyaa'      , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Maldives' , 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 23,'currencySymbol' => 'RM' ,  'currencyName'  => 'Ringgit'      , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Malaysia' , 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 24,'currencySymbol' => 'NRs',  'currencyName'  => 'Rupee'        , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Nepal', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 25,'currencySymbol' => 'NZ$',  'currencyName'  => 'Dollar'       , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'New Zealand', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 26,'currencySymbol' => 'RO' ,  'currencyName'  => 'Rial'         , 'subunitName' =>  null,     'noOfDecimalPlace' => 3, 'narration' =>  'Oman' , 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 27,'currencySymbol' => 'PRs',  'currencyName'  => 'Rupee'        , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Pakistan', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 28,'currencySymbol' => 'QR' ,  'currencyName'  => 'Rial'         , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Qatar', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 29,'currencySymbol' => 'p.' ,  'currencyName'  => 'Ruble'        , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Russia' , 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 30,'currencySymbol' => 'SAR',  'currencyName'  => 'Saudi Riyal'  , 'subunitName' =>  'Halala', 'noOfDecimalPlace' => 2, 'narration' =>  'Saudi Arabia' , 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 31,'currencySymbol' => 'S$' ,  'currencyName'  => 'Dollar'       , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Singapore', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 32,'currencySymbol' => 'TL' ,  'currencyName'  => 'Lira'         , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Turkey' , 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 33,'currencySymbol' => 'NT$',  'currencyName'  => 'New Dollar'   , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Taiwan' , 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 34,'currencySymbol' => '$'  ,  'currencyName'  => 'Dollar'       , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'United States', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 35,'currencySymbol' => 'YR' ,  'currencyName'  => 'Rial'         , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Yemen', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],  
                ['currencyId' => 36,'currencySymbol' => 'AED',  'currencyName'  => 'Dirham'       , 'subunitName' =>  null,     'noOfDecimalPlace' => 2, 'narration' =>  'Dubai', 'default' => true, 'branchId' => 1,'CreatedDate' => now(),'CreatedUser' => null,'ModifiedDate' => null,'ModifiedUser' => null],
            ];
            DB::table('tbl_Currency')->insert($currency);

            // Get currencyId for SAR
            $currencyId = DB::table('tbl_Currency')
                ->where('currencySymbol', 'SAR')
                ->where('currencyName', 'Saudi Riyal')
                ->value('currencyId');

            // Insert default tbl_CurrencyConversion
            if ($currencyId) {
                DB::table('tbl_CurrencyConversion')->insert([
                    'currencyConversionId' =>1,
                    'currencyId'      => $currencyId,
                    'date'            => now()->startOfDay(), // like 2025-12-18 00:00:00
                    'rate'            => 1,
                    'narration'       => null,
                    'branchId'        => 1,
                    'CreatedDate'     => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'     => 1,
                    'ModifiedDate'    => null,
                    'ModifiedUser'    => null,
                ]);
            }

            // Insert default branch
            $branchData = [
                [
                    'branchId'          => 1,
                    'companyId'         => 1,
                    'currencyId'        => $currencyId ?? 1, 
                    'branchName'        => 'Branch 1',
                    'branchNameFL'      => 'Branch 1',
                    'address'           => 'Address',
                    'addressFL'         => 'Address',
                    'streetName'        => 'Street',
                    'streetNameFL'      => 'Street',
                    'buildingNo'        => '123',
                    'buildingNoFL'      => '123',
                    'additionalNumber'  => '11',
                    'additionalNumberFL'=> '11',
                    'cityName'          => 'City',
                    'cityNameFL'        => 'City',
                    'district'          => 'District Name',
                    'districtFL'        => 'District Name',
                    'country'           => 'country Name',
                    'countryFL'         => 'country Name',
                    'postalCode'        => '123456',
                    'postalCodeFL'      => '123456',
                    'phoneNo'           =>  1234567890,
                    'fax'               =>  '001-000-2112',
                    'mobile'            =>  1234567890,
                    'email'             =>  'dummy@gmail.com',
                    'web'               =>  'www.dummy.com',
                    'taxNo'             =>  '27ABCDE1234F1Z5',
                    'panNo'             =>  'AFZPK7190K',
                    'crNo'              =>  '55656',
                    'logo'              =>  '',
                    'mainBranch'        =>  true,
                    'startDate'         =>  '2025-08-01',
                    'dbName'            =>  null,
                    // 'dbName'            =>  'rashidpppmna_finac_branch1_db',
                    'activeStatus'      =>  true,
                    'subscriptionNo'    =>  'SUBS-2025-001',
                    'createdDate'      => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'createdUser'      => null,
                    'modifiedDate'     => null,
                    'modifiedUser'     => null,
                    'branchCode'       => 'MAIN',
                ],
            ];
            DB::table('tbl_Branch')->insert($branchData);

            // // Insert default User Group
            $userData = [
                [
                    'usergroupId'     => 1,
                    'usergroupName'   => 'Super Admin',
                    'activeStatus'    => 1,
                    'CreatedDate'    => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'    => null,
                    'ModifiedDate'    => null,
                    'ModifiedUser'    => null,
                ],
            ];
            DB::table('tbl_UserGroup')->insert($userData);

            // // Insert default user
            $userData = [
                [
                    'userId'         => 1,
                    'userName'       => 'admin',
                    'Password'       => '$2y$12$vRdxsvjODogTnKxUW2MHN.iKv0dmU3bsd4Uq3gMm0a2HHwQ7TYDTK', //hashed 123456
                    'phoneNo'        => 1234567890,
                    'email'          =>  'user@gmail.com',
                    'profilePhoto'   => null,
                    'Narration'      => null,
                    'branchIds'      => '["1"]',
                    'UserRoleId'     => 1,
                    'ActiveStatus'   => 1,
                    'CreatedDate'    => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'    => null,
                    'ModifiedDate'    => null,
                    'ModifiedUser'    => null,
                ],
            ];
            DB::table('tbl_User')->insert($userData);


            // $userprivilegedata = ["Company Details","Branches","Master","Finance","Account Ledger","Account Group","Cost Center",
            // "Bank","Customer","Supplier","Inventory","Batch","Product Creation","Product Main Group","Product Group",
            // "Unit","Brand","Godown","Tax Master","Sales","Pricing Level","Route","Area","Market","General","Designation",
            // "Department","Work Location","User","Users","User Group","Payroll","Employee","Transaction","Sales Quotation",
            // "Proforma Invoice","Sales Order","Delivery Note","Sales Invoice","Sales Return","Purchase","Purchase Order",
            // "Material Receipt","Purchase Invoice","Purchase Return","Receipt Voucher","Payment Voucher","Contra Voucher",
            // "Journal Voucher","Payable Voucher","Receivable Voucher","Physical Stock","Damage Stock","Godown Transfer",
            // "Reports","Ledger Report","Account Group Report","Party Balance","Ageing Report","Day Book Summary",
            // "Supplier Report","Customer Statement","Supplier Statement","Cash Book","Bank Book","Payment Report",
            // "Receipt Report","Day Book","Customer Address Book","Supplier Address Book","Contra","Journal",
            // "Detailed Ledger Report","Tax Summary Report","Tax Detailed Report","Tax Consolidated Report",
            // "Cost Centre Report","Payable Voucher Report","Chart of Account","Finance Statements",
            // "Profit & Loss Analysis","Balance Sheet","Fund Flow","Fund Flow Detailed","Trial Balance",
            // "Sales Day Report","Sales Report","Sales Summary Report","Sales Order Report",
            // "Sales Order vs Product Report","Sales Return Report","Delivery Note Report",
            // "Proforma Invoice Report","Salesman Wise Sales Report","Salesman Wise Sales Order Report",
            // "Salesman Wise Bills Pending","Area Wise Sales Report","Product Vs Salesman Report",
            // "Product Wise Salesman Report","Product Wise Sales Summary","Purchase Day Report",
            // "Purchase Report","Purchase Return Report","Material Receipt Report","Purchase Order Report",
            // "Price List Report","Stock Value Report","Stock Report","Product Movement Report",
            // "Physical Stock Report","Search","Product Search (Voucher Wise)","Settings & Tools",
            // "Financial Year","New","Edit","Change","Close Financial Year","Currency","Exchange Rate",
            // "Suffix Prefix Settings","Settings"];

            // $userprivilege = [];

            // foreach ($userprivilegedata as $name) {
            //     $userprivilege[] = [
            //         'usergroup_id' => 1,
            //         'window_name'  => $name,
            //         'can_all'      => true,
            //         'can_add'      => true,
            //         'can_edit'     => true,
            //         'can_delete'   => true,
            //         'can_post'     => true,
            //         'can_view'     => true,
            //         'can_home'     => true,
            //         'CreatedDate'  => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
            //     ];
            // }
            // DB::table('tbl_UserPrivileges')->insert($userprivilege);

			// Insert default tbl_AccountGroup
            $accountgroup = [
				['groupId' => 0, 'AccountGroupCode' => 'P','accountGroupName' => 'Primary','groupUnder' => 0,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 1, 'AccountGroupCode' => 'EX','accountGroupName' => 'Expense','groupUnder' => 0,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 2, 'AccountGroupCode' => 'IN','accountGroupName' => 'Income', 'groupUnder' => 0,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 3, 'AccountGroupCode' => 'LI','accountGroupName' => 'Liability','groupUnder' => 0,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 4, 'AccountGroupCode' => 'AS','accountGroupName' => 'Assets', 'groupUnder' => 0,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 5, 'AccountGroupCode' => 'BA','accountGroupName' => 'Bank Account','groupUnder' => 9,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 6, 'AccountGroupCode' => 'BOD', 'accountGroupName' => 'Bank OD Account','groupUnder' => 20, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 7, 'AccountGroupCode' => 'CA','accountGroupName' => 'Capital Account','groupUnder' => 3,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 8, 'AccountGroupCode' => 'CH','accountGroupName' => 'Cash In Hand','groupUnder' => 9,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 9, 'AccountGroupCode' => 'CAS', 'accountGroupName' => 'Current Assets','groupUnder' => 4,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 10,'AccountGroupCode' => 'CL','accountGroupName' => 'Current Liability','groupUnder' => 3,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 11,'AccountGroupCode' => 'STDA','accountGroupName' => 'Short Term Deposits (Asset)','groupUnder' => 9, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 12,'AccountGroupCode' => 'DE','accountGroupName' => 'Direct Expenses','groupUnder' => 1,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 13,'AccountGroupCode' => 'DI','accountGroupName' => 'Direct Incomes','groupUnder' => 2,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 14,'AccountGroupCode' => 'DT','accountGroupName' => 'Duties & Taxes','groupUnder' => 10, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 15,'AccountGroupCode' => 'FA','accountGroupName' => 'Fixed Assets','groupUnder' => 4,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 16,'AccountGroupCode' => 'IE','accountGroupName' => 'Indirect Expenses','groupUnder' => 1,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 17,'AccountGroupCode' => 'II','accountGroupName' => 'Indirect Incomes', 'groupUnder' => 2,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 18,'AccountGroupCode' => 'INV', 'accountGroupName' => 'Investments','groupUnder' => 4,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 19,'AccountGroupCode' => 'LAA', 'accountGroupName' => 'Loans & Advances(Asset)','groupUnder' => 4,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 20,'AccountGroupCode' => 'LLL', 'accountGroupName' => 'Loans & Liability(Liability)','groupUnder' => 3,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 21,'AccountGroupCode' => 'ME','accountGroupName' => 'MiscellaneousExpenses','groupUnder' => 4,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 22,'AccountGroupCode' => 'PROV','accountGroupName' => 'Provisions','groupUnder' => 10, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 23,'AccountGroupCode' => 'PA','accountGroupName' => 'Purchase Accounts','groupUnder' => 1,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 24,'AccountGroupCode' => 'RS','accountGroupName' => 'Reserves & Surplus','groupUnder' => 7,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 25,'AccountGroupCode' => 'SA','accountGroupName' => 'Sales Accounts','groupUnder' => 2,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 26,'AccountGroupCode' => 'SL','accountGroupName' => 'Secured Loans', 'groupUnder' => 20, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 27,'AccountGroupCode' => 'SH','accountGroupName' => 'Stock In Hand', 'groupUnder' => 9,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 28,'AccountGroupCode' => 'SC','accountGroupName' => 'Sundry Creditors', 'groupUnder' => 10, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 29,'AccountGroupCode' => 'SD','accountGroupName' => 'Sundry Debtors','groupUnder' => 9,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 30,'AccountGroupCode' => 'SAA', 'accountGroupName' => 'Suspense Account(Asset)','groupUnder' => 4,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 31,'AccountGroupCode' => 'SAL', 'accountGroupName' => 'Suspense Account(Liability)','groupUnder' => 3,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 32,'AccountGroupCode' => 'UL','accountGroupName' => 'Unsecured Loans','groupUnder' => 20, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 33,'AccountGroupCode' => 'PCA', 'accountGroupName' => 'Proprietor Current Account','groupUnder' => 7, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 34,'AccountGroupCode' => 'CF','accountGroupName' => 'Chits Or Funds','groupUnder' => 18, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 35,'AccountGroupCode' => 'AP','accountGroupName' => 'Accounts Payables','groupUnder' => 10, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 36,'AccountGroupCode' => 'AR','accountGroupName' => 'Accounts Receivables','groupUnder' => 9,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 37,'AccountGroupCode' => 'AI','accountGroupName' => 'Accrued Income','groupUnder' => 9,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 38,'AccountGroupCode' => 'LTDA','accountGroupName' => 'Long Term Deposits(Asset)', 'groupUnder' => 4,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 39,'AccountGroupCode' => 'STLL','accountGroupName' => 'Short Term Loans(Liability)','groupUnder' => 10,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 40,'AccountGroupCode' => 'STLA','accountGroupName' => 'Short Term Loans & Advances(Asset)','groupUnder' => 9,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 41,'AccountGroupCode' => 'PP','accountGroupName' => 'P D C Payable', 'groupUnder' => 10, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 42,'AccountGroupCode' => 'PR','accountGroupName' => 'P D C Receivable', 'groupUnder' => 9,'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 43,'AccountGroupCode' => 'S','accountGroupName' => 'Supplier', 'groupUnder' => 28, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 44,'AccountGroupCode' => 'C','accountGroupName' => 'Customer', 'groupUnder' => 29, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 45,'AccountGroupCode' => 'OE','accountGroupName' => 'Operating Expense','groupUnder' => 16, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 46,'AccountGroupCode' => 'NOE', 'accountGroupName' => 'Non Operating Expense','groupUnder' => 16, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 47,'AccountGroupCode' => 'AC','accountGroupName' => 'Additional Cost','groupUnder' => 12, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
				['groupId' => 48,'AccountGroupCode' => 'PC','accountGroupName' => 'Production cost','groupUnder' => 12, 'narration' => 'Default Group', 'LedgerNextNo' => 1, 'default' => true],
			];
			DB::table('tbl_AccountGroup')->insert($accountgroup);

			// Insert default tbl_AccountLedger
            $accountledger = [
                ['ledgerId' => 1, 'ledgerName' => 'Cash', 'groupId' => 8, 'ledgerCode' => 1, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 2, 'ledgerName' => 'Vat payable', 'groupId' => 10, 'ledgerCode' => 2, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 3, 'ledgerName' => 'Vat Refundable', 'groupId' => 9, 'ledgerCode' => 3, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 4, 'ledgerName' => 'P D C Payable', 'groupId' => 41, 'ledgerCode' => 4, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 5, 'ledgerName' => 'P D C Receivable', 'groupId' => 42, 'ledgerCode' => 5, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 6, 'ledgerName' => 'Purchase Account', 'groupId' => 23, 'ledgerCode' => 6, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 7, 'ledgerName' => 'Sales Account', 'groupId' => 25, 'ledgerCode' => 7, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 8, 'ledgerName' => 'Interest Receivable', 'groupId' => 9, 'ledgerCode' => 8, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 9, 'ledgerName' => 'Interest Payable', 'groupId' => 10, 'ledgerCode' => 9, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 10, 'ledgerName' => 'Salary', 'groupId' => 16, 'ledgerCode' => 10, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 11, 'ledgerName' => 'Rent', 'groupId' => 16, 'ledgerCode' => 11, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 12, 'ledgerName' => 'Advertisement', 'groupId' => 16, 'ledgerCode' => 12, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 13, 'ledgerName' => 'Transportation', 'groupId' => 12, 'ledgerCode' => 13, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 14, 'ledgerName' => 'Wages', 'groupId' => 12, 'ledgerCode' => 14, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 15, 'ledgerName' => 'Loading and Unloading', 'groupId' => 12, 'ledgerCode' => 15, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 16, 'ledgerName' => 'Bank Charges', 'groupId' => 16, 'ledgerCode' => 16, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 17, 'ledgerName' => 'Interest Paid', 'groupId' => 16, 'ledgerCode' => 17, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 18, 'ledgerName' => 'Interest Received', 'groupId' => 17, 'ledgerCode' => 18, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 19, 'ledgerName' => 'Discount Allowed', 'groupId' => 16, 'ledgerCode' => 19, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 20, 'ledgerName' => 'Discount Received', 'groupId' => 17, 'ledgerCode' => 20, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 21, 'ledgerName' => 'Forex Gain/Loss', 'groupId' => 16, 'ledgerCode' => 21, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 22, 'ledgerName' => 'Advance Payment', 'groupId' => 9, 'ledgerCode' => 22, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 23, 'ledgerName' => 'Excise Duty', 'groupId' => 12, 'ledgerCode' => 23, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 27, 'ledgerName' => 'Service Account', 'groupId' => 25, 'ledgerCode' => 27, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 28, 'ledgerName' => 'Tailoring Income', 'groupId' => 13, 'ledgerCode' => 28, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 29, 'ledgerName' => 'Round Off', 'groupId' => 13, 'ledgerCode' => 29, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 30, 'ledgerName' => 'Tax On Sales', 'groupId' => 14, 'ledgerCode' => 30, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
                ['ledgerId' => 31, 'ledgerName' => 'Tax On Purcahse', 'groupId' => 14, 'ledgerCode' => 31, 'default' => true, 'branchId' => '[{"crOrDr": "Cr","branchId": 1,"openingBalance": 0}]'],
            ];

			DB::table('tbl_AccountLedger')->insert($accountledger);

			// Insert default CostCentre
            $CostCentre = [
                [
                    'costCentreId'      => 1,
                    'CostCentre'      => 'Default',
                    'Narration'       => null,
                    'CreatedDate'     => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'     => 1,
                    'ModifiedDate'     => null,
                    'ModifiedUser'     => null,
                ],
            ];
            DB::table('tbl_CostCentre')->insert($CostCentre);

            // Insert default tbl_Unit
            $unit = [
                [
                    'unitId'        => 1,
                    'UnitName'      => 'PCS',
                    'Narration'     => null,
                    'CreatedDate'   => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'   => 1,
                    'ModifiedDate'   => null,
                    'ModifiedUser'   => null,
                ],
            ];
            DB::table('tbl_Unit')->insert($unit);

            // Insert default tbl_Brand
            $brand = [
                [
                    'brandId'       => 1,
                    'brandName'      => 'NA',
                    'Narration'     => null,
                    'CreatedDate'   => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'   => 1,
                    'ModifiedDate'   => null,
                    'ModifiedUser'   => null,
                ],
            ];
            DB::table('tbl_Brand')->insert($brand);

             // Insert default tbl_Godown
            $godown = [
                [
                    'GodownId'      =>  1,
                    'GodownName'      => 'Default Godown',
                    'Narration'     => null,
                    'CreatedDate'   => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'   => 1,
                    'ModifiedDate'   => null,
                    'ModifiedUser'   => null,
                    'branchId'       => 1,
                    'IsDefault'      => true,
                ],
            ];
            DB::table('tbl_Godown')->insert($godown);

            // Insert default tbl_PricingLevel
            $pricing = [
                [
                    'PricingLevelId'    => 1,
                    'PricingLevelName'  => 'Default',
                    'Narration'         => null,
                    'CreatedDate'       => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'       => 1,
                    'ModifiedDate'      => null,
                    'ModifiedUser'      => null,
                ],
            ];
            DB::table('tbl_PricingLevel')->insert($pricing);

            // Insert default tbl_Route
            $route = [
                [
                    'RouteId'           => 1,
                    'RouteName'         => 'NA',
                    'Narration'         => null,
                    'CreatedDate'       => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'       => 1,
                    'ModifiedDate'      => null,
                    'ModifiedUser'      => null,
                ],
            ];
            DB::table('tbl_Route')->insert($route);

            // Insert default tbl_Area
            $area = [
                [
                    'AreaId'            => 1,
                    'AreaName'         => 'NA',
                    'Narration'         => null,
                    'CreatedDate'       => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'       => 1,
                    'ModifiedDate'      => null,
                    'ModifiedUser'      => null,
                ],
            ];
            DB::table('tbl_Area')->insert($area);

            // Insert default tbl_Market
            $market = [
                [
                    'MarketId'          =>  1,
                    'MarketName'         => 'NA',
                    'Narration'         => null,
                    'CreatedDate'       => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'       => 1,
                    'ModifiedDate'      => null,
                    'ModifiedUser'      => null,
                ],
            ];
            DB::table('tbl_Market')->insert($market);

            // Insert default tbl_ProductMainGroup
            $productmaingroup = [
                [
                    'groupCode'           => '001',
                    'groupName'           => 'General',
                    'scaleGroup'          => false,
                    'productCodeLength'   => 5,
                    'nextNumber'          => 1,
                    'branchId'            => 1,
                    'CreatedDate'         => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'         => 1,
                    'ModifiedDate'        => null,
                    'ModifiedUser'        => null,
                ],
            ];
            DB::table('tbl_ProductMainGroup')->insert($productmaingroup);

            // Insert default tbl_TaxMaster
            $tax = [
                ['taxId'=> 1,'taxName' => 'N/A','rate'=> 0,'calculatingMode'=> 'product','active'=> true,'narration'=> null,'branchId'=> 1,'CreatedDate'=> now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),'CreatedUser'=> 1,'ModifiedDate'=> null,'ModifiedUser'=> null],
                ['taxId'=> 2,'taxName'=> 'Vat @ 15%','rate'=> 15,'calculatingMode'=> 'product','active'=> true,'narration'=> null,'branchId'=> 1,'CreatedDate'=> now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),'CreatedUser'=> 1,'ModifiedDate'=> null,'ModifiedUser'=> null],
            ];
            DB::table('tbl_TaxMaster')->insert($tax);

            // Insert default tbl_ProductGroup
            $productgroup = [
                ['groupId'=> 1,'groupName'=> 'General','category' => 'category-1','narration' => null,'branchId'=> 1,'CreatedDate'=> now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),'CreatedUser'=> 1,'ModifiedDate'=> null,'ModifiedUser'=> null],
                ['groupId'=> 2,'groupName'=> 'General','category' => 'category-2','narration' => null,'branchId'=> 1,'CreatedDate'=> now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),'CreatedUser'=> 1,'ModifiedDate'=> null,'ModifiedUser'=> null],
                ['groupId'=> 3,'groupName'=> 'General','category' => 'category-3','narration' => null,'branchId'=> 1,'CreatedDate'=> now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),'CreatedUser'=> 1,'ModifiedDate'=> null,'ModifiedUser'=> null],
                ['groupId'=> 4,'groupName'=> 'General','category' => 'category-4','narration' => null,'branchId'=> 1,'CreatedDate'=> now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),'CreatedUser'=> 1,'ModifiedDate'=> null,'ModifiedUser'=> null],
            ];
            DB::table('tbl_ProductGroup')->insert($productgroup);


            $fromDate = Carbon::now()->startOfYear();  // Jan 1 YYYY
            $toDate   = Carbon::now()->endOfYear();    // Dec 31 YYYY

            // Insert into tbl_FinancialYear
            $yearId = DB::table('tbl_FinancialYear')->insertGetId([
                'yearId' => 1,
                'fromDate'     => $fromDate,
                'toDate'       => $toDate,
                'closed'       => false,
                'CreatedDate'  => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                'CreatedUser'  => 1,
                'ModifiedDate' => null,
                'ModifiedUser' => null,
            ], 'yearId'); // return PK

            // Insert months into tbl_FinancialYearMonth
            $current = $fromDate->copy();
            while ($current->lte($toDate)) {
                DB::table('tbl_FinancialYearMonth')->insert([
                    'yearId'      => $yearId,
                    'MonthName'   => $current->format('F-Y'), // e.g. January-2025
                    'closed'      => false,
                    'CreatedDate' => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser' => 1,
                    'ModifiedDate'=> null,
                    'ModifiedUser'=> null,
                ]);
                $current->addMonth()->startOfMonth();
            }


            // Insert default tbl_GeneralSettings
            $tbl_GeneralSettings = [
                [
                    'branchId'                  => 1,
                    'ActivateTax'               => false,
                    'costCentre'                => false,
                    'AccountPosting'            => false,
                    'negativeCashTransaction'   => 'Block',
                    'AccountCalculationMethod'  => 'CrDr',
                    'decimalPart'               => 2,
                    'dateformat'                => 'dd-MM-yyyy',
                    'taxType'                   => 'Applicable to product',
                    'taxincluded'               => false,
                    'taxCessincluded'           => false,
                    'branchHeader'              => null,
                    'branchFooter'              => null,
                    'zatcaType'                 => 'Phase 1',
                    'RoundOff'                  => 0,
                    'RoundOffDigit'             => 2,
                    'showCurrencyprefix'        => false,
                    'askConfirmationSave'       => false,
                    'askConfirmationEdit'       => false,
                    'askConfirmationDelete'     => false,
                    'askConfirmationClear'      => false,
                    'askConfirmationRowRemove'  => false,
                    'askConfirmationPrint'      => false,
                    'askConfirmationClose'      => false,
                    'CreatedDate'               => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'               => 1,
                    'ModifedDate'               => null,
                    'ModifedUser'               => null,
                    'taxLedgerId'               => 30, // Tax On Sales ledgerid
                    'formType'                  => 'Tax Invoice',
                ],
            ];
            DB::table('tbl_GeneralSettings')->insert($tbl_GeneralSettings);

            // Insert default tbl_FinanceSettings
            $tbl_FinanceSettings = [
                [
                    'ShowAllTransactions'       => false,
                    'MaintainBillbyBill'        => true,
                    'multiCurrency'             => false,
                    'showLedgerbalance'         => false,
                    'CloseAfterSave'            => false,
                    'branchId'                  => 1,
                    'createdDate'               => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'createdUser'               => 1,
                    'modifiedDate'              => null,
                    'modifiedUser'              => null,
                    'DefaultCashAccount'        => 1, // Cash ledgerid
                ],
            ];
            DB::table('tbl_FinanceSettings')->insert($tbl_FinanceSettings);


            // Insert default tbl_InventorySettings
            $tbl_InventorySettings = [
                [
                    'NegativeStock'                          => 'Block',
                    'stockValueCalculation'                  => 'Average Cost',
                    'SalesPriceUpdateByCostPricePercentage'  => false,
                    'maintainGodown'                         => false,
                    'maintainRack'                           => false,
                    'lowStockReminder'                       => false,
                    'CloseAfterSave'                         => false,
                    'branchId'                               => 1,
                    'CreatedDate'                            => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'                            => 1,
                ],
            ];
            DB::table('tbl_InventorySettings')->insert($tbl_InventorySettings);

            // Insert default tbl_SalesSettings
            $tbl_SalesSettings = [
                [
                    'showProductDescription'                 => false,
                    'showFeeQtyColumn'                       => false,
                    'showLineDiscount'                       => false,
                    'showPurchaserate'                       => false,
                    'ShowPartNo'                             => false,
                    'ActivateSalesRetention'                 => false,
                    'showBillProfit'                         => false,
                    'showBillDiscountAmount'                 => false,
                    'showBillDiscountPerc'                   => false,
                    'CloseAfterSave'                         => false,
                    'DefaultPaymentMode'                     => 'cash',
                    'branchId'                               => 1,
                    'CreatedDate'                            => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'                            => 1,
                ],
            ];
            DB::table('tbl_SalesSettings')->insert($tbl_SalesSettings);

            // Insert default tbl_PurchaseSettings
            $tbl_PurchaseSettings = [
                [
                    'VendorInvoiceNoChecking'         => false,
                    'showProductDescription'          => false,
                    'showFeeQtyColumn'                => false,
                    'ActivatePurchaseRetention'       => false,
                    'ChangeSalesPrice'                => false,
                    'showLineDiscount'                => false,
                    'CloseAfterSave'                  => false,
                    'branchId'                        => 1,
                    'CreatedDate'                     => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    'CreatedUser'                     => 1,
                ],
            ];
            DB::table('tbl_PurchaseSettings')->insert($tbl_PurchaseSettings);


            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Default data added successfully',
                'database' => $result['database_name']
            ], 201);
		} catch (\Throwable $e) {
			DB::rollBack();
			return response()->json([
				'status' => 500,
				'error' => true,
				'message' => $e->getMessage(),
			], 500);
		}
	}
}
