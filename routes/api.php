<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CostCentreController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\GodownController;
use App\Http\Controllers\PricingLevelController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\GeneralSettingsController;
use App\Http\Controllers\FinanceSettingsController;
use App\Http\Controllers\InventorySettingsController;
use App\Http\Controllers\SalesSettingsController;
use App\Http\Controllers\PurchaseSettingsController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\AccountGroupController;
use App\Http\Controllers\UserPrivilegeController;
use App\Http\Controllers\ProductMainGroupController;
use App\Http\Controllers\ProductGroupController;
use App\Http\Controllers\TaxMasterController;
use App\Http\Controllers\AccountLedgerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\FinancialYearController;
use App\Http\Controllers\CurrencyConversionController;
use App\Http\Controllers\ShippingAddressController;
use App\Http\Controllers\SalesMasterController;
use App\Http\Controllers\SuffixPrefixController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\WorkLocationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SalesReturnMasterController;
use App\Http\Controllers\ReceiptMasterController;
use App\Http\Controllers\QuotationMasterController;
use App\Http\Controllers\ProformaInvoiceMasterController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\DeliveryNoteMasetrController;
use App\Http\Controllers\PaymentMasterController;
use App\Http\Controllers\ContraMasetrController;
use App\Http\Controllers\JournalMasetrController;
use App\Http\Controllers\PurchaseOrderMasetrController;
use App\Http\Controllers\PurchaseMasetrController;
use App\Http\Controllers\MaterialReceiptMasterController;
use App\Http\Controllers\PurchaseReturnMasterController;
use App\Http\Controllers\PhysicalStockMasterController;
use App\Http\Controllers\DamageStockMasterController;
use App\Http\Controllers\PayableMasterController;
use App\Http\Controllers\ReceivableMasterController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CalculationController;
use App\Http\Controllers\StockTransferMasterController;
use App\Http\Controllers\OfferRateController;
use App\Http\Controllers\ProductPurchasePriceController;
use App\Http\Controllers\PurchaseCartMasterController;
use App\Http\Controllers\PurchaseQuotationController;
use App\Http\Controllers\RportFileDevController;
use App\Http\Controllers\UsedStockMasterController;
use App\Http\Controllers\SalesOrderPaymentController;
use App\Http\Controllers\VanExicutiveController;
use App\Http\Controllers\VanExicutiveSettingsController;
use App\Http\Controllers\ZatcaRegistrationController;
use App\Http\Controllers\SectionPrivilegeController;
use App\Http\Controllers\TransactionBatch;
use App\Http\Controllers\GeneralReminderController;
use App\Http\Controllers\PayheadController;
use App\Http\Controllers\SalarySettingsController;
use App\Http\Controllers\StaffAttendanceMasterController;
use App\Http\Controllers\SalaryAdvancePaymentController;
use App\Http\Controllers\BonusDeductionMasterController;
use App\Http\Controllers\ManufacturingJournalController;
use App\Http\Controllers\SalaryMasterController;
use App\Http\Controllers\StaffHolidayRegisterController;
use App\Http\Controllers\VanDayRegisterController;
use App\Http\Controllers\BarcodePrintAlignmentController;
use App\Http\Controllers\QuickAccessController;
use App\Http\Controllers\MailController;




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/debug-check', function () {
    $token = \Laravel\Sanctum\PersonalAccessToken::latest()->first();
    return response()->json([
        'expires_at'      => $token?->expires_at,
        'now'             => now()->toDateTimeString(),
        'timezone'        => config('app.timezone'),
        'sanctum_expiry'  => config('sanctum.expiration'),
        'is_expired'      => $token?->expires_at
            ? now()->greaterThan($token->expires_at)
            : 'expires_at is NULL',
    ]);
});


Route::post('/get-organization', [OrganizationController::class, 'GetOrganization']);
Route::get('/organizations', [OrganizationController::class, 'index']);
Route::post('/app-version-update', [OrganizationController::class, 'AppVserionUpdate']);
Route::get('/save-default-branchdb-data/{db}', [CostCentreController::class, 'storedefaultdata']);
Route::post('zatca/pending-vouchers', [ZatcaRegistrationController::class, 'pedingVoucher']);
Route::post('zatca/update-fields', [SalesMasterController::class, 'UpdateZatcaFieldsWithOutAuth']);
Route::get('/tax-masters-print', [TaxMasterController::class, 'taxprint']);
Route::get('/get-sales-byId-print/{id}', [SalesMasterController::class, 'taxshow']);

Route::post('/sales-list-by-customer', [SalesMasterController::class, 'SalesListByCustomer']);

// Auth
// Route::post('/login', [UserController::class, 'login']);

// POS
Route::prefix('pos')->group(function () {
    Route::post('/login', [UserController::class, 'Poslogin']);
});

Route::get('/save-default-maindb-data', [UserController::class, 'storedefaultdata']);
Route::get('/check-auth', [UserController::class, 'checkAuth']);

Route::middleware(['database.switcher'])->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::post('app/login', [UserController::class, 'Applogin']);
});





// Route::middleware(['auth:sanctum', 'database.switcher'])->group(function () {
Route::middleware(['database.switcher', 'auth:sanctum', 'token.expiry'])->group(function () {

    Route::get('/me', [UserController::class, 'me']);
    Route::get('app/dashboard', [UserController::class, 'appDashboard'])->name('app.dashboard');
    Route::post('/dashboard', [UserController::class, 'dashboard']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
    Route::get('/database-context', [UserController::class, 'getDatabaseContext']);
    Route::get('/test-database-connection', [UserController::class, 'testDatabaseConnection']);

    // UserGroup
    Route::get('/user-groups', [UserGroupController::class, 'index']);
    Route::get('/active-user-groups', [UserGroupController::class, 'active']);
    Route::get('/get-user-group-byId/{id}', [UserGroupController::class, 'show']);
    Route::post('/save-user-group', [UserGroupController::class, 'store']);
    Route::post('/update-user-group/{id}', [UserGroupController::class, 'update']);
    Route::get('/delete-user-group/{id}', [UserGroupController::class, 'destroy']);
    Route::get('/toggle-user-group-status/{id}', [UserGroupController::class, 'toggleStatus']);

    // User
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/get-user-byId/{id}', [UserController::class, 'show']);
    Route::post('/check-username', [UserController::class, 'checkUsername']);
    Route::post('/save-user', [UserController::class, 'store']);
    Route::post('/update-user/{id}', [UserController::class, 'update']);
    Route::get('/delete-user/{id}', [UserController::class, 'destroy']);
    Route::get('/toggle-user-status/{id}', [UserController::class, 'toggleStatus']);

    // Company
    Route::get('/company', [CompanyController::class, 'index']);
    Route::get('/company-with-context', [CompanyController::class, 'indexWithContext']);
    Route::get('/company/{id}', [CompanyController::class, 'show']);
    Route::post('/update-company/{id}', [CompanyController::class, 'update']);

    // Branch
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/get-branch-byId/{id}', [BranchController::class, 'show']);
    Route::post('/save-branch', [BranchController::class, 'store']);
    Route::post('/update-branch/{id}', [BranchController::class, 'update']);
    Route::get('/delete-branch/{id}', [BranchController::class, 'destroy']);
    Route::get('/toggle-branch-status/{id}', [BranchController::class, 'toggleStatus']);

    // Cost Centre
    Route::get('/cost-centres', [CostCentreController::class, 'index']);
    Route::get('/get-cost-centre-byId/{id}', [CostCentreController::class, 'show']);
    Route::post('/save-cost-centre', [CostCentreController::class, 'store']);
    Route::post('/update-cost-centre/{id}', [CostCentreController::class, 'update']);
    Route::get('/delete-cost-centre/{id}', [CostCentreController::class, 'destroy']);
    Route::post('/costcentre-report', [CostCentreController::class, 'costcentrereport']);


    // Unit
    Route::get('/units', [UnitController::class, 'index']);
    Route::get('/get-unit-byId/{id}', [UnitController::class, 'show']);
    Route::post('/save-unit', [UnitController::class, 'store']);
    Route::post('/update-unit/{id}', [UnitController::class, 'update']);
    Route::get('/delete-unit/{id}', [UnitController::class, 'destroy']);

    // Brand
    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/get-brand-byId/{id}', [BrandController::class, 'show']);
    Route::post('/save-brand', [BrandController::class, 'store']);
    Route::post('/update-brand/{id}', [BrandController::class, 'update']);
    Route::get('/delete-brand/{id}', [BrandController::class, 'destroy']);

    // Godown
    Route::get('/godowns/{id}', [GodownController::class, 'index']);
    Route::get('/get-godown-byId/{id}', [GodownController::class, 'show']);
    Route::post('/save-godown', [GodownController::class, 'store']);
    Route::post('/update-godown/{id}', [GodownController::class, 'update']);
    Route::get('/delete-godown/{id}', [GodownController::class, 'destroy']);

    // Pricing Level
    Route::get('/pricing-levels', [PricingLevelController::class, 'index']);
    Route::get('/get-pricing-level-byId/{id}', [PricingLevelController::class, 'show']);
    Route::post('/save-pricing-level', [PricingLevelController::class, 'store']);
    Route::post('/update-pricing-level/{id}', [PricingLevelController::class, 'update']);
    Route::get('/delete-pricing-level/{id}', [PricingLevelController::class, 'destroy']);

    // Route
    Route::get('/routes', [RouteController::class, 'index']);
    Route::get('/get-route-byId/{id}', [RouteController::class, 'show']);
    Route::post('/save-route', [RouteController::class, 'store']);
    Route::post('/update-route/{id}', [RouteController::class, 'update']);
    Route::get('/delete-route/{id}', [RouteController::class, 'destroy']);

    // Area
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('/get-area-byId/{id}', [AreaController::class, 'show']);
    Route::post('/save-area', [AreaController::class, 'store']);
    Route::post('/update-area/{id}', [AreaController::class, 'update']);
    Route::get('/delete-area/{id}', [AreaController::class, 'destroy']);

    // Market
    Route::get('/markets', [MarketController::class, 'index']);
    Route::get('/get-market-byId/{id}', [MarketController::class, 'show']);
    Route::post('/save-market', [MarketController::class, 'store']);
    Route::post('/update-market/{id}', [MarketController::class, 'update']);
    Route::get('/delete-market/{id}', [MarketController::class, 'destroy']);

    // General Settings
    Route::get('/general-settings', [GeneralSettingsController::class, 'index']);
    Route::post('/update-general-setting/{id}', [GeneralSettingsController::class, 'update']);
    Route::get('/remove-files/{id}/image/{type}', [GeneralSettingsController::class, 'removeImage']);


    //quickaccess
    Route::post('/update-quickaccess/{id}', [QuickAccessController::class, 'update']);
    Route::get('/get-all-quickaccess/{id}', [QuickAccessController::class, 'index']);


    //barcodesettings
    Route::post('/update-barcode-setting/{id}', [BarcodePrintAlignmentController::class, 'update']);
    Route::get('/get-all-barcodesettings/{id}', [BarcodePrintAlignmentController::class, 'index']);



    // Finance Settings
    Route::get('/finance-settings', [FinanceSettingsController::class, 'index']);
    Route::post('/update-finance-setting/{id}', [FinanceSettingsController::class, 'update']);

    // Inventory Settings
    Route::get('/inventory-settings', [InventorySettingsController::class, 'index']);
    Route::post('/update-inventory-setting/{id}', [InventorySettingsController::class, 'update']);

    // Sales Settings
    Route::get('/sales-settings', [SalesSettingsController::class, 'index']);
    Route::post('/update-sales-setting/{id}', [SalesSettingsController::class, 'update']);

    // Purchase Settings
    Route::get('/purchase-settings', [PurchaseSettingsController::class, 'index']);
    Route::post('/update-purchase-setting/{id}', [PurchaseSettingsController::class, 'update']);

    // Currency
    Route::get('/currencies', [CurrencyController::class, 'index']);
    Route::get('/get-currency-byId/{id}', [CurrencyController::class, 'show']);
    Route::post('/save-currency', [CurrencyController::class, 'store']);
    Route::post('/update-currency/{id}', [CurrencyController::class, 'update']);
    Route::get('/delete-currency/{id}', [CurrencyController::class, 'destroy']);
    Route::get('/currency-with-conversions', [CurrencyController::class, 'currency_with_conversion']);

    // User Privilege
    Route::get('/privileges', [UserPrivilegeController::class, 'index']);
    Route::get('/get-privileges-byId/{id}', [UserPrivilegeController::class, 'show']);
    Route::post('/save-privileges', [UserPrivilegeController::class, 'savePrivileges']);

    // Section Privilege
    Route::get('/section-privileges/{id}', [SectionPrivilegeController::class, 'index']);
    Route::get('/get-section-privileges-byId/{userGroupId}/{branchId}', [SectionPrivilegeController::class, 'show']);
    Route::post('/save-section-privilege', [SectionPrivilegeController::class, 'savePrivileges']);

    // AccountGroup
    Route::get('/accountgroups', [AccountGroupController::class, 'index']);
    Route::get('/get-accountgroup-byId/{id}', [AccountGroupController::class, 'show']);
    Route::post('/save-accountgroup', [AccountGroupController::class, 'store']);
    Route::post('/update-accountgroup/{id}', [AccountGroupController::class, 'update']);
    Route::get('/delete-accountgroup/{id}', [AccountGroupController::class, 'destroy']);
    Route::post('/bank-customer-supplier-accountgroups', [AccountGroupController::class, 'getAccountGroups']);
    Route::get('/save-default-accountgroup', [AccountGroupController::class, 'storedefaultdata']);
    Route::post('/accountgroup-detailed-report', [AccountGroupController::class, 'AccountgroupDetailedReport']);
    Route::post('/all-branch-group-ledger-balance', [AccountGroupController::class, 'BranchwsiecustomerSupplierbalance']);
    Route::get('/account-groups-chart/{groupId}', [AccountGroupController::class, 'accountGroupForChart']);
    Route::post('/account-group-report', [AccountGroupController::class, 'accountGroupReport']);
    Route::post('/bill-balance-report', [AccountGroupController::class, 'billbalanceReport']);

    // Account Ledger
    Route::get('/all-account-ledgers/{id}', [AccountLedgerController::class, 'All']);
    Route::post('/account-ledgers', [AccountLedgerController::class, 'index']);
    Route::get('/get-account-ledger-byId/{id}', [AccountLedgerController::class, 'show']);
    Route::post('/save-account-ledger', [AccountLedgerController::class, 'store']);
    Route::post('/update-account-ledger/{id}', [AccountLedgerController::class, 'update']);
    Route::get('/delete-account-ledger/{id}', [AccountLedgerController::class, 'destroy']);
    Route::get('/save-default-accountledger', [AccountLedgerController::class, 'storedefaultdata']);
    Route::post('/bank-account-ledgers', [AccountLedgerController::class, 'bankledgers']);
    Route::post('/customer-supplier-account-ledgers', [AccountLedgerController::class, 'customersupplierLedgers']);
    Route::get('/get-ledger-balance', [AccountLedgerController::class, 'getLedgerBalance']);
    Route::post('/accountledger-detailed-report', [AccountLedgerController::class, 'AccountledgerDetailedReport']);
    Route::post('/address-book-report', [AccountLedgerController::class, 'addressBookReport']);
    Route::post('/day-book', [AccountLedgerController::class, 'DayBook']);
    Route::post('/generate-ledger-code', [AccountLedgerController::class, 'generateLedgerCode']);
    Route::prefix('profit-and-loss')->group(function () {
        Route::post('/analysis', [AccountLedgerController::class, 'ProfitAndLossReport']);
        Route::post('/detailed', [AccountLedgerController::class, 'ProfitAndLossDetailedReport']);
        Route::post('/analysis-detailed-trial-balance', [AccountLedgerController::class, 'TrailBalance']);
        Route::post('/analysis-up-to-date', [AccountLedgerController::class, 'upToDate']);
        // Route::post('/opening-stock-fifo', [AccountLedgerController::class, 'openingStockFIFO']);
        


    });
    Route::prefix('accountsummary')->group(function () {
    Route::post('/income-expense-report', [AccountLedgerController::class, 'ledgerAccountReport']);
    });

    Route::prefix('incomeandexpenditure')->group(function () {
    Route::post('/allreports', [AccountLedgerController::class, 'incomeAndExpenditureAllReports']);
    




    });
    
    Route::prefix('balance-sheet')->group(function () {
        Route::post('/', [AccountLedgerController::class, 'BalanceSheet']);
        Route::post('/detailed', [AccountLedgerController::class, 'Detailed']);
        // Route::post('/stock-opening-value-fifo', [AccountLedgerController::class, 'openingStockValueFIFO']);
    });
    Route::post('/cash-flow-summary', [AccountLedgerController::class, 'CashFlow']);
    Route::post('/fund-flow', [AccountLedgerController::class, 'fundFlow']);
    Route::post('/fund-flow-detailed', [AccountLedgerController::class, 'fundFlowDetailed']);
    Route::post('/fund-flow-account-ledgers', [AccountLedgerController::class, 'AccountLedgerViewAllByCondition']);
    Route::post('/account-ledger-report', [AccountLedgerController::class, 'accountLedgerReport']);
    Route::post('/account-ledger-view', [AccountLedgerController::class, 'AccountLedgerView']);
    Route::post('/party-balance', [AccountLedgerController::class, 'GetPartyBalance']);
    Route::post('/party-balance-against', [AccountLedgerController::class, 'PartyBalanceAgainst']);

    // calculation Method
    Route::prefix('calculation-method')->group(function () {
        Route::post('profit-and-loss-opening-stock-fifo', [AccountLedgerController::class, 'openingStockFIFO']);
        Route::post('balance-sheet-stock-opening-value-fifo', [AccountLedgerController::class, 'openingStockValueFIFO']);
        Route::post('stock-value-date-by-fifo', [CalculationController::class, 'StockValueOnDateByFIFO']);
        Route::post('stock-opening-value-avco', [CalculationController::class, 'openingStockValueAVCO']);
        Route::post('opening-stock-avco-balance-sheet', [CalculationController::class, 'openingStockAVCOBalanceSheet']);
        Route::post('opening-stock-avco', [CalculationController::class, 'openingStockAVCO']);
        Route::post('stock-opening-high-cost', [CalculationController::class, 'openingStockHighCost']);
        Route::post('opening-stock-high-cost-balance-sheet', [CalculationController::class, 'openingStockHighCostBalanceSheet']);
        Route::post('stock-value-date-by-highCost', [CalculationController::class, 'StockValueOnDateByHighCost']);
        Route::post('opening-stock-low-cost', [CalculationController::class, 'openingStockLowCost']);
        Route::post('balance-sheet-opening-stock-low-cost', [CalculationController::class, 'LowCostForOpeningStockForBlncSheet']);
        Route::post('stock-value-on-date-by-low-cost', [CalculationController::class, 'StockValueOnDateByLowCost']);
        Route::post('stock-opening-value-last-purchase-rate', [CalculationController::class, 'openingStockLastPurchaseRate']);
        Route::post('stock-opening-value-last-purchase-rate-balance-sheet', [CalculationController::class, 'openingStockLastPurchaseRateBalanceSheet']);
        Route::post('stock-value-last-purchase-rate', [CalculationController::class, 'stockValueByLastPurchaseRate']);
    });
    Route::prefix('ageing')->group(function () {
        Route::post('/payable', [AccountLedgerController::class, 'payable']);
        Route::post('/receivable', [AccountLedgerController::class, 'receivable']);
    });

    // ProductMainGroup
    Route::get('/product-main-groups', [ProductMainGroupController::class, 'index']);
    Route::get('/get-product-main-group-byId/{id}', [ProductMainGroupController::class, 'show']);
    Route::post('/save-product-main-group', [ProductMainGroupController::class, 'store']);
    Route::post('/update-product-main-group/{id}', [ProductMainGroupController::class, 'update']);
    Route::get('/delete-product-main-group/{id}', [ProductMainGroupController::class, 'destroy']);
    Route::get('/product-code-generation-byId/{id}', [ProductMainGroupController::class, 'productcode']);

    // ProductGroup
    Route::get('/product-groups', [ProductGroupController::class, 'index']);
    Route::get('/get-product-group-byId/{id}', [ProductGroupController::class, 'show']);
    Route::post('/save-product-group', [ProductGroupController::class, 'store']);
    Route::post('/update-product-group/{id}', [ProductGroupController::class, 'update']);
    Route::get('/delete-product-group/{id}', [ProductGroupController::class, 'destroy']);
    Route::get('/get-subgroup-byId/{id}', [ProductGroupController::class, 'subgroup']);

    // TaxMaster
    Route::get('/tax-masters', [TaxMasterController::class, 'index']);
    Route::get('/get-tax-master-byId/{id}', [TaxMasterController::class, 'show']);
    Route::post('/save-tax-master', [TaxMasterController::class, 'store']);
    Route::post('/update-tax-master/{id}', [TaxMasterController::class, 'update']);
    Route::get('/delete-tax-master/{id}', [TaxMasterController::class, 'destroy']);
    Route::post('/tax-summery-report  ', [TaxMasterController::class, 'TaxReportByVoucherwise']);
    Route::post('/tax-detailed-report', [TaxMasterController::class, 'TaxDetailedReport']);
    Route::prefix('tax-consolidated')->group(function () {
        Route::post('/tax-return-standard-rated-sales-report', [TaxMasterController::class, 'standardRatedSalesReport']);
        Route::post('/tax-return-zero-rated-domestic-sales-report', [TaxMasterController::class, 'zeroRatedDomesticSalesReport']);
        Route::post('/tax-return-exempt-sales-report', [TaxMasterController::class, 'ExceptSales']);
        Route::post('/tax-return-standard-rated-purchase', [TaxMasterController::class, 'StandardRatedPurchase']);
        Route::post('/tax-return-zero-rated-domestic-purchase', [TaxMasterController::class, 'TaxReturnZeroRatedDomesticPurchase']);
        Route::post('/tax-return-exempt-purchase', [TaxMasterController::class, 'TaxReturnExemptPurchase']);
        Route::post('/vat-credit-carryforward', [TaxMasterController::class, 'getVatPaid']);

    });


    // Product
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/bom', [ProductController::class, 'getBomData']);
    Route::get('/products-grid-fill', [ProductController::class, 'productsGridFill']);
    Route::get('/products-grid-fill-withstock', [ProductController::class, 'productsGridFillwithstock']);
    Route::get('/get-product-byId/{id}', [ProductController::class, 'show']);
    Route::get('/view-product-byId/{id}', [ProductController::class, 'view']);
    Route::post('/save-product', [ProductController::class, 'store']);
    Route::post('/update-product/{id}', [ProductController::class, 'update']);
    Route::get('/delete-product/{id}', [ProductController::class, 'destroy']);
    Route::post('/product-search', [ProductController::class, 'searchProducts']);
    Route::get('/get-product-unit-sales-details-byId/{id}', [ProductController::class, 'product_unit_sales_details']);
    Route::post('/product-movement-report', [ProductController::class, 'ProductMovement']);
    Route::post('/product-wise-sales-summary-report', [ProductController::class, 'ProductWiseSalesSummaryReport']);
    Route::post('/product-search-vocuher-wise', [ProductController::class, 'ProductSearchVocuherWise']);
    Route::post('/get-product-stock', [ProductController::class, 'getProductStock']);

    // Financial Year
    Route::get('/financial-years', [FinancialYearController::class, 'index']);
    Route::get('/get-financial-year-byId/{id}', [FinancialYearController::class, 'show']);
    Route::post('/save-financial-year', [FinancialYearController::class, 'store']);
    Route::post('/update-financial-year/{id}', [FinancialYearController::class, 'update']);
    Route::get('/delete-financial-year/{id}', [FinancialYearController::class, 'destroy']);
    Route::get('/toggle-financial-year-status/{id}', [FinancialYearController::class, 'toggleStatus']);

    // Currency Conversion
    Route::get('/currency-conversions/{branchId?}', [CurrencyConversionController::class, 'index']);
    Route::get('/get-currency-conversion-byId/{id}', [CurrencyConversionController::class, 'show']);
    Route::post('/save-currency-conversion', [CurrencyConversionController::class, 'store']);
    Route::post('/update-currency-conversion/{id}', [CurrencyConversionController::class, 'update']);
    Route::get('/delete-currency-conversion/{id}', [CurrencyConversionController::class, 'destroy']);

    // Shipping Address
    Route::get('/shipping-addresses', [ShippingAddressController::class, 'index']);
    Route::get('/get-shipping-address-byId/{id}', [ShippingAddressController::class, 'show']);
    Route::post('/save-shipping-address', [ShippingAddressController::class, 'store']);
    Route::post('/update-shipping-address/{id}', [ShippingAddressController::class, 'update']);
    Route::get('/delete-shipping-address/{id}', [ShippingAddressController::class, 'destroy']);

    // Suffix Prefix
    Route::get('/suffix-prefix-by-voucherType-yearId', [SuffixPrefixController::class, 'show']);
    Route::post('/save-suffix-prefix', [SuffixPrefixController::class, 'store']);
    Route::post('/update-suffix-prefix/{id}', [SuffixPrefixController::class, 'update']);
    Route::get('/delete-suffix-prefix/{id}', [SuffixPrefixController::class, 'destroy']);

    // Designation
    Route::get('/designations', [DesignationController::class, 'index']);
    Route::get('/get-designation-byId/{id}', [DesignationController::class, 'show']);
    Route::post('/save-designation', [DesignationController::class, 'store']);
    Route::post('/update-designation/{id}', [DesignationController::class, 'update']);
    Route::get('/delete-designation/{id}', [DesignationController::class, 'destroy']);

    // Department
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/get-department-byId/{id}', [DepartmentController::class, 'show']);
    Route::post('/save-department', [DepartmentController::class, 'store']);
    Route::post('/update-department/{id}', [DepartmentController::class, 'update']);
    Route::get('/delete-department/{id}', [DepartmentController::class, 'destroy']);

    // WorkLocation
    Route::get('/worklocations', [WorkLocationController::class, 'index']);
    Route::get('/get-worklocation-byId/{id}', [WorkLocationController::class, 'show']);
    Route::post('/save-worklocation', [WorkLocationController::class, 'store']);
    Route::post('/update-worklocation/{id}', [WorkLocationController::class, 'update']);
    Route::get('/delete-worklocation/{id}', [WorkLocationController::class, 'destroy']);

    // Employee
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::get('/get-employee-byId/{id}', [EmployeeController::class, 'show']);
    Route::post('/save-employee', [EmployeeController::class, 'store']);
    Route::post('/update-employee/{id}', [EmployeeController::class, 'update']);
    Route::get('/delete-employee/{id}', [EmployeeController::class, 'destroy']);
    Route::get('/get-generated-voucherNo', [EmployeeController::class, 'generateVoucher']);
    // sales
    Route::post('/sales', [SalesMasterController::class, 'index']);
    Route::get('/get-sales-byId/{id}', [SalesMasterController::class, 'show']);
    Route::post('/save-sales', [SalesMasterController::class, 'store']);
    Route::post('/update-sales/{id}', [SalesMasterController::class, 'update']);
    Route::get('/delete-sales/{id}/{branchid}', [SalesMasterController::class, 'destroy']);
    Route::post('/get-sales-invoice-quotation-list', [SalesMasterController::class, 'SalesInvoiceQuotationList']);
    Route::post('/get-sales-invoice-quotation-details', [SalesMasterController::class, 'SalesInvoiceQuotationDetails']);
    Route::post('/get-sales-invoice-proforma-list', [SalesMasterController::class, 'SalesInvoiceProformaList']);
    Route::post('/get-sales-invoice-proforma-details', [SalesMasterController::class, 'SalesInvoiceProformaDetails']);
    Route::post('/get-sales-invoice-sales-order-list', [SalesMasterController::class, 'SalesInvoiceSalesOrderList']);
    Route::post('/get-sales-invoice-sales-order-details', [SalesMasterController::class, 'SalesInvoiceSalesOrderDetails']);
    Route::post('/get-sales-invoice-delivery-note-list', [SalesMasterController::class, 'SalesInvoiceDeliveryNoteList']);
    Route::post('/get-sales-invoice-delivery-note-details', [SalesMasterController::class, 'SalesInvoiceDeliveryNoteDetails']);
    Route::post('/price-list-report', [SalesMasterController::class, 'PriceListRepot']);
    Route::post('/stock-value-report', [SalesMasterController::class, 'StockValueRepot']);
    Route::post('/product-stock-report', [SalesMasterController::class, 'ProductStockReport']);
    Route::post('/get-last-sales-price-and-sales-quantity', [SalesMasterController::class, 'SalesInvoiceFillProductHistory']);
    Route::post('/sales-report', [SalesMasterController::class, 'salesreport']);
    Route::post('/sales-day-report', [SalesMasterController::class, 'salesDayreport']);
    Route::post('/update-zatca-fields', [SalesMasterController::class, 'UpdateZatcaFields']);
    Route::post('/all-sales-data', [SalesMasterController::class, 'allSalesApis']);
    Route::post('/get-all-product-stock', [SalesMasterController::class, 'getAllProductStock']);

    Route::post('/notifications/reorder', [SalesMasterController::class, 'reorderStock']);




    // sales or purchase history
    Route::post('/product-movement', [SalesMasterController::class, 'ProductMovement']);
    Route::post('/sales-order-report', [SalesMasterController::class, 'SalesOrderReport']);

    // areawise_sales_report
    Route::post('/areawise-sales-report', [SalesMasterController::class, 'areaWiseSalesReport']);
    Route::post('/product-vs-salesman', [SalesMasterController::class, 'productVsSalesman']);
    Route::post('/productwise-saleman-report', [SalesMasterController::class, 'ProductwiseSalemanFill']);
    Route::post('/sales-manwise-bills-pending', [SalesMasterController::class, 'SalesManwiseBillsPending']);

Route::post('/salesmaster-grouped-data', [SalesMasterController::class, 'customerInfo']);

    // sales return
    Route::post('/sales-returns', [SalesReturnMasterController::class, 'index']);
    Route::get('/get-sales-return-byId/{id}', [SalesReturnMasterController::class, 'show']);
    Route::post('/save-sales-return', [SalesReturnMasterController::class, 'store']);
    Route::post('/update-sales-return/{id}', [SalesReturnMasterController::class, 'update']);
    Route::get('/delete-sales-return/{id}/{branchId}', [SalesReturnMasterController::class, 'destroy']);
    Route::get('/get-sales-data-forreturn-byId/{id}', [SalesReturnMasterController::class, 'salesDataToReturn']);
    Route::post('/get-invoices-for-return', [SalesReturnMasterController::class, 'getInvoicesForReturn']);
    Route::post('/sales-man-wise-sales-report', [SalesReturnMasterController::class, 'SalesManWiseReport']);
    Route::post('/sales-man-wise-sales-order-report', [SalesReturnMasterController::class, 'SalesManWiseSalesOrderReport']);
    Route::post('/sales-return-summary-report', [SalesReturnMasterController::class, 'SalesReturnSummaryReport']);
    Route::post('/sales-return-detailed-report', [SalesReturnMasterController::class, 'salesReturnDetailed']);
    Route::post('/sales-summary-report', [SalesReturnMasterController::class, 'salesSummaryReport']);
    Route::get('/sales-data-toreturn-byinvoiceno/{id}', [SalesReturnMasterController::class, 'salesDataToReturnbyinvoiceNo']);




    // sales receipt
    Route::post('/sales-receipts', [ReceiptMasterController::class, 'index']);
    Route::get('/get-sales-receipt-byId/{id}', [ReceiptMasterController::class, 'show']);
    Route::post('/save-sales-receipt', [ReceiptMasterController::class, 'store']);
    Route::post('/update-sales-receipt/{id}', [ReceiptMasterController::class, 'update']);
    Route::get('/delete-sales-receipt/{id}', [ReceiptMasterController::class, 'destroy']);
    Route::post('/receipt-report', [ReceiptMasterController::class, 'receiptReport']);
    Route::post('/all-finance-data', [ReceiptMasterController::class, 'allFinanceApis']);
    // report
    Route::group(['prefix' => 'receipts-payment-against'], function () {
        Route::post('/view', [ReceiptMasterController::class, 'receiptPaymentAgainstView']);
        Route::post('/get', [ReceiptMasterController::class, 'receiptPaymentAgainstGet']);
    });
    // sales Quotation
    Route::post('/sales-quotations', [QuotationMasterController::class, 'index']);
    Route::get('/get-sales-quotation-byId/{id}', [QuotationMasterController::class, 'show']);
    Route::post('/save-sales-quotation', [QuotationMasterController::class, 'store']);
    Route::post('/update-sales-quotation/{id}', [QuotationMasterController::class, 'update']);
    Route::get('/delete-sales-quotation/{id}', [QuotationMasterController::class, 'destroy']);
    Route::post('/quotation-report-detailed', [QuotationMasterController::class, 'quotationReport']);
    Route::post('/quotation-report-summary', [QuotationMasterController::class, 'quotationReportSummary']);
    Route::get('/approve-sales-quotation/{id}', [QuotationMasterController::class, 'approve']);


    // Proforma Invoice
    Route::post('/proforma-invoices', [ProformaInvoiceMasterController::class, 'index']);
    Route::get('/get-proforma-invoice-byId/{id}', [ProformaInvoiceMasterController::class, 'show']);
    Route::post('/save-proforma-invoice', [ProformaInvoiceMasterController::class, 'store']);
    Route::post('/update-proforma-invoice/{id}', [ProformaInvoiceMasterController::class, 'update']);
    Route::get('/delete-proforma-invoice/{id}', [ProformaInvoiceMasterController::class, 'destroy']);
    Route::post('/quotation-no-by-date', [ProformaInvoiceMasterController::class, 'getQuotationByDate']);
    Route::get('/get-quotation-details-byId/{id}', [ProformaInvoiceMasterController::class, 'getQuotationDetails']);
    Route::post('/proforma-invoice-detailed-report', [ProformaInvoiceMasterController::class, 'proformaInvoiceReport']);
    Route::post('/get-quotation-for-proforma', [ProformaInvoiceMasterController::class, 'getQuotationForProforma']);
    // Sales Order
    Route::post('/sales-orders', [SalesOrderController::class, 'index']);
    Route::post('/sales-orders-status-change', [SalesOrderController::class, 'salesOrderStatus']);
    Route::get('/get-sales-order-byId/{id}', [SalesOrderController::class, 'show']);
    Route::post('/save-sales-order', [SalesOrderController::class, 'store']);
    Route::post('/update-sales-order/{id}',  [SalesOrderController::class, 'update']);
    Route::get('/delete-sales-order/{id}', [SalesOrderController::class, 'destroy']);
    Route::post('/get-sales-order-quotation-list', [SalesOrderController::class, 'SalesOrderQuotationList']);
    Route::post('/get-sales-order-proforma-list', [SalesOrderController::class, 'SalesOrderProformaList']);
    Route::post('/get-quotation-details-for-sales-order', [SalesOrderController::class, 'SalesOrderquotationDetails']);
    Route::post('/get-proforma-details-for-sales-order', [SalesOrderController::class, 'SalesOrderproformaDetails']);
    Route::post('/sales-order-vs-product', [SalesOrderController::class, 'salesOrderVsProduct']);

    // Delivery Note
    Route::post('/delivery-notes', [DeliveryNoteMasetrController::class, 'index']);
    Route::get('/get-delivery-note-byId/{id}', [DeliveryNoteMasetrController::class, 'show']);
    Route::post('/save-delivery-note', [DeliveryNoteMasetrController::class, 'store']);
    Route::post('/update-delivery-note/{id}', [DeliveryNoteMasetrController::class, 'update']);
    Route::get('/delete-delivery-note/{id}', [DeliveryNoteMasetrController::class, 'destroy']);
    Route::post('/get-delivery-note-sales-quotation-list', [DeliveryNoteMasetrController::class, 'DeliveryNoteQuotationList']);
    Route::post('/get-delivery-note-sales-quotation-details', [DeliveryNoteMasetrController::class, 'DeliveryNoteQuotationDetails']);
    Route::post('/get-delivery-note-proforma-list', [DeliveryNoteMasetrController::class, 'DeliveryNoteProformaList']);
    Route::post('/get-delivery-note-proforma-details', [DeliveryNoteMasetrController::class, 'DeliveryNoteProformaDetails']);
    Route::post('/get-delivery-note-sales-order-list', [DeliveryNoteMasetrController::class, 'DeliveryNoteSalesOrderList']);
    Route::post('/get-delivery-note-sales-order-details', [DeliveryNoteMasetrController::class, 'DeliveryNoteSalesOrderDetails']);
    Route::post('/delivery-note-detailed-report', [DeliveryNoteMasetrController::class, 'DeliveryNoteReport']);

    // Payment Voucher
    Route::post('/payment-vouchers', [PaymentMasterController::class, 'index']);
    Route::get('/get-payment-voucher-byId/{id}', [PaymentMasterController::class, 'show']);
    Route::post('/save-payment-voucher', [PaymentMasterController::class, 'store']);
    Route::post('/update-payment-voucher/{id}', [PaymentMasterController::class, 'update']);
    Route::get('/delete-payment-voucher/{id}', [PaymentMasterController::class, 'destroy']);
    Route::post('/payment-report', [PaymentMasterController::class, 'paymentReport']);

    // Contra Voucher
    Route::post('/contra-vouchers', [ContraMasetrController::class, 'index']);
    Route::get('/get-contra-voucher-byId/{id}', [ContraMasetrController::class, 'show']);
    Route::post('/save-contra-voucher', [ContraMasetrController::class, 'store']);
    Route::post('/update-contra-voucher/{id}', [ContraMasetrController::class, 'update']);
    Route::get('/delete-contra-voucher/{id}', [ContraMasetrController::class, 'destroy']);
    Route::post('/contra-report', [ContraMasetrController::class, 'ContraReport']);
    // Journal Voucher
    Route::post('/journal-vouchers', [JournalMasetrController::class, 'index']);
    Route::get('/get-journal-voucher-byId/{id}', [JournalMasetrController::class, 'show']);
    Route::post('/save-journal-voucher', [JournalMasetrController::class, 'store']);
    Route::post('/update-journal-voucher/{id}', [JournalMasetrController::class, 'update']);
    Route::get('/delete-journal-voucher/{id}', [JournalMasetrController::class, 'destroy']);
    Route::post('journal-report', [JournalMasetrController::class, 'journalReport']);

    // Purchase Order
    Route::post('/purchase-orders', [PurchaseOrderMasetrController::class, 'index']);
    Route::get('/get-purchase-order-byId/{id}', [PurchaseOrderMasetrController::class, 'show']);
    Route::post('/save-purchase-order', [PurchaseOrderMasetrController::class, 'store']);
    Route::post('/update-purchase-order/{id}', [PurchaseOrderMasetrController::class, 'update']);
    Route::get('/delete-purchase-order/{id}', [PurchaseOrderMasetrController::class, 'destroy']);
    Route::post('/purchase-order-report', [PurchaseOrderMasetrController::class, 'purchaseOrder']);
    Route::post('/purchase-order-against-quotation-list', [PurchaseOrderMasetrController::class, 'purchaseOrderAgainstQuotationList']);
    Route::post('/purchase-order-against-quotation-details', [PurchaseOrderMasetrController::class, 'purchaseOrderAgainstQuotationDetails']);



    // Purchase Invoice
    Route::post('/purchase-invoices', [PurchaseMasetrController::class, 'index']);
    Route::get('/get-purchase-invoice-byId/{id}', [PurchaseMasetrController::class, 'show']);
    Route::post('/save-purchase-invoice', [PurchaseMasetrController::class, 'store']);
    Route::post('/update-purchase-invoice/{id}', [PurchaseMasetrController::class, 'update']);
    Route::get('/delete-purchase-invoice/{id}', [PurchaseMasetrController::class, 'destroy']);
    Route::post('/purchase-report', [PurchaseMasetrController::class, 'purchasereport']);
    Route::post('/purchase-day-report', [PurchaseMasetrController::class, 'purchasedayreport']);
    Route::post('/all-purchase-data', [PurchaseMasetrController::class, 'allPurchaseApis']);
    Route::post('/purchase-invoice-material-receipt-list', [PurchaseMasetrController::class, 'PurchaseInvoiceMaterialReceiptlist']);
    Route::post('/purchase-invoice-material-receipt-details', [PurchaseMasetrController::class, 'PurchaseInvoiceMaterialReceiptDetails']);
    Route::post('/purchase-invoice-against-order-list', [PurchaseMasetrController::class, 'PurchaseInvoiceAgainstOrderlist']);
    Route::post('/purchase-invoice-against-order-details', [PurchaseMasetrController::class, 'PurchaseInvoiceAgainstOrderDetails']);
    Route::post('/purchase-history', [PurchaseMasetrController::class, 'purchaseHistory']);
Route::post('/supplier-last-purchase-rate', [PurchaseMasetrController::class, 'getSupplierLastPurchaseRate']);




    Route::prefix('purchase')->group(function () {
        Route::post('/report-summary', [PurchaseMasetrController::class, 'PurchaseReportSummary']);
        Route::post('/invoice-report-detailed', [PurchaseMasetrController::class, 'PurchaseInvoiceDetailedReport']);
        Route::post('/day-report', [PurchaseMasetrController::class, 'PurchaseDayReport']);
        Route::post('/invoiceForReturn', [PurchaseMasetrController::class, 'PurchaseInvoiceForReturn']);
    });


    // Purchase Return
    Route::post('/purchase-returns', [PurchaseReturnMasterController::class, 'index']);
    Route::get('/get-purchase-return-byId/{id}', [PurchaseReturnMasterController::class, 'show']);
    Route::post('/save-purchase-return', [PurchaseReturnMasterController::class, 'store']);
    Route::post('/update-purchase-return/{id}', [PurchaseReturnMasterController::class, 'update']);
    Route::get('/delete-purchase-return/{id}', [PurchaseReturnMasterController::class, 'destroy']);
    Route::post('/purchase-return-report', [PurchaseReturnMasterController::class, 'purchaseReturnreport']);

    Route::prefix('purchase-return')->group(function () {
        Route::post('/report-summary', [PurchaseReturnMasterController::class, 'PurchaseReturnReport']);
        Route::post('/report-detailed', [PurchaseReturnMasterController::class, 'PurchaseReturnReportDetailed']);
    });

    // Material Receipt
    Route::post('/material-receipts', [MaterialReceiptMasterController::class, 'index']);
    Route::get('/get-material-receipt-byId/{id}', [MaterialReceiptMasterController::class, 'show']);
    Route::post('/save-material-receipt', [MaterialReceiptMasterController::class, 'store']);
    Route::post('/update-material-receipt/{id}', [MaterialReceiptMasterController::class, 'update']);
    Route::get('/delete-material-receipt/{id}', [MaterialReceiptMasterController::class, 'destroy']);
    Route::post('/material-receipt-report', [MaterialReceiptMasterController::class, 'materialReceiptReport']);
    Route::post('/material-receipt-purchase-order-list', [MaterialReceiptMasterController::class, 'materialreceiptpurchaseorderlist']);
    Route::post('/material-receipt-purchase-order-details', [MaterialReceiptMasterController::class, 'materialreceiptpurchaseorderDetails']);


    // Physical Stock
    Route::post('/physical-stocks', [PhysicalStockMasterController::class, 'index']);
    Route::get('/get-physical-stock-byId/{id}', [PhysicalStockMasterController::class, 'show']);
    Route::post('/save-physical-stock', [PhysicalStockMasterController::class, 'store']);
    Route::post('/update-physical-stock/{id}', [PhysicalStockMasterController::class, 'update']);
    Route::get('/delete-physical-stock/{id}', [PhysicalStockMasterController::class, 'destroy']);
    Route::post('/physical-stock-report', [PhysicalStockMasterController::class, 'physicalStockReport']);
    Route::post('/all-inventory-data', [PhysicalStockMasterController::class, 'allInventoryApis']);


    // Damage Stock
    Route::post('/damage-stocks', [DamageStockMasterController::class, 'index']);
    Route::get('/get-damage-stock-byId/{id}', [DamageStockMasterController::class, 'show']);
    Route::post('/save-damage-stock', [DamageStockMasterController::class, 'store']);
    Route::post('/update-damage-stock/{id}', [DamageStockMasterController::class, 'update']);
    Route::get('/delete-damage-stock/{id}', [DamageStockMasterController::class, 'destroy']);
    Route::post('/damage-stock-report', [DamageStockMasterController::class, 'damageStockReport']);


    // Payable Voucher
    Route::post('/payable-vouchers', [PayableMasterController::class, 'index']);
    Route::get('/get-payable-voucher-byId/{id}', [PayableMasterController::class, 'show']);
    Route::post('/save-payable-voucher', [PayableMasterController::class, 'store']);
    Route::post('/update-payable-voucher/{id}', [PayableMasterController::class, 'update']);
    Route::get('/delete-payable-voucher/{id}', [PayableMasterController::class, 'destroy']);
    Route::post('/payable-voucher-report', [PayableMasterController::class, 'payableVoucherReport']);


    // Receivable Voucher
    Route::post('/receivable-vouchers', [ReceivableMasterController::class, 'index']);
    Route::get('/get-receivable-voucher-byId/{id}', [ReceivableMasterController::class, 'show']);
    Route::post('/save-receivable-voucher', [ReceivableMasterController::class, 'store']);
    Route::post('/update-receivable-voucher/{id}', [ReceivableMasterController::class, 'update']);
    Route::get('/delete-receivable-voucher/{id}', [ReceivableMasterController::class, 'destroy']);
    Route::post('/daybook-summery', [ReceivableMasterController::class, 'daybookSummery']);
    Route::post('/receivable-voucher-report', [ReceivableMasterController::class, 'receivableVoucherReport']);


    //Batch
    Route::get('/batches', [BatchController::class, 'index']);
    Route::get('/get-batch-byId/{id}', [BatchController::class, 'show']);
    Route::post('/save-batch', [BatchController::class, 'store']);
    Route::post('/update-batch/{id}', [BatchController::class, 'update']);
    Route::get('/delete-batch/{id}', [BatchController::class, 'destroy']);

    // Stock Transfer
    Route::get('/stock-transfers/{id}', [StockTransferMasterController::class, 'index']);
    Route::get('/get-stock-transfer-byId/{id}/{branchId}', [StockTransferMasterController::class, 'show']);
    Route::post('/save-stock-transfer', [StockTransferMasterController::class, 'store']);
    Route::post('/update-stock-transfer/{id}', [StockTransferMasterController::class, 'update']);
    Route::get('/delete-stock-transfer/{id}', [StockTransferMasterController::class, 'destroy']);
    Route::post('/accept-stock-transfer/{id}', [StockTransferMasterController::class, 'AcceptTransfer']);
    Route::post('/stock-transfer-report', [StockTransferMasterController::class, 'stockTransferReport']);


    // offer rate
    Route::prefix('offer-rate')->group(function () {
        Route::get('/', [OfferRateController::class, 'index']);
        Route::get('/get-offer-rate-byId/{id}', [OfferRateController::class, 'show']);
        Route::post('/save-offer-rate', [OfferRateController::class, 'store']);
        Route::get('/delete-offer-rate/{id}', [OfferRateController::class, 'destroy']);
        Route::post('/update-offer-rate/{id}', [OfferRateController::class, 'update']);
        Route::post('/filter', [OfferRateController::class, 'filter']);
    });
    Route::prefix('product-purchase')->group(function () {
        Route::get('/', [ProductPurchasePriceController::class, 'index']);
        Route::get('/show-byId/{id}', [ProductPurchasePriceController::class, 'show']);
        Route::post('/store', [ProductPurchasePriceController::class, 'store']);
        Route::get('/delete/{id}', [ProductPurchasePriceController::class, 'destroy']);
        Route::post('/update/{id}', [ProductPurchasePriceController::class, 'update']);
    });
    Route::prefix('used-stock')->group(function () {
        Route::get('/', [UsedStockMasterController::class, 'index']);
        Route::get('/show-byId/{id}', [UsedStockMasterController::class, 'show']);
        Route::post('/store', [UsedStockMasterController::class, 'store']);
        Route::get('/delete/{id}', [UsedStockMasterController::class, 'destroy']);
        Route::post('/update/{id}', [UsedStockMasterController::class, 'update']);
        Route::post('/report', [UsedStockMasterController::class, 'usedStockReport']);
    });
    Route::prefix('purchase-quotation')->group(function () {
        Route::post('/', [PurchaseQuotationController::class, 'index']);
        Route::get('/show-byId/{id}', [PurchaseQuotationController::class, 'show']);
        Route::post('/store', [PurchaseQuotationController::class, 'store']);
        Route::get('/delete/{id}', [PurchaseQuotationController::class, 'destroy']);
        Route::post('/update/{id}', [PurchaseQuotationController::class, 'update']);
        Route::post('/report', [PurchaseQuotationController::class, 'report']);
    });
    Route::prefix('purchase-cart')->group(function () {
        Route::post('/',  [PurchaseCartMasterController::class, 'index']);
        Route::get('/show-byId/{id}', [PurchaseCartMasterController::class, 'show']);
        Route::post('/store', [PurchaseCartMasterController::class, 'store']);
        Route::get('/delete/{id}', [PurchaseCartMasterController::class, 'destroy']);
        Route::post('/update/{id}', [PurchaseCartMasterController::class, 'update']);
        Route::post('/report', [PurchaseCartMasterController::class, 'report']);
    });
    Route::prefix('dashboard')->group(function () {
        Route::post('/cash-balance',  [UserController::class, 'cashBalance']);
        Route::post('/bank-balance',  [UserController::class, 'getBankBalance']);
        Route::post('/customer-balance',  [UserController::class, 'getCustomerBalance']);
        Route::post('/supplier-balance',  [UserController::class, 'getSupplierBalance']);
        Route::post('/top-selling-products',  [UserController::class, 'topSellingProducts']);
        Route::post('/sales-graph',  [UserController::class, 'salesGraph']);
        Route::get('/user-wise-sales/{branchId}',  [UserController::class, 'userSales']);
        Route::post('/stock-value',  [UserController::class, 'stockValue']);
        Route::post('/low-stock',  [UserController::class, 'lowStock']);
        Route::post('/product-group-wise-sales-summary-daily',  [UserController::class, 'productGroupWiseSalesSummary']);
    });
    Route::prefix('sales-order-payment')->group(function () {
        Route::get('/',  [SalesOrderPaymentController::class, 'index']);
        Route::get('/show-byId/{id}', [SalesOrderPaymentController::class, 'show']);
        Route::post('/store', [SalesOrderPaymentController::class, 'store']);
        Route::get('/delete/{id}', [SalesOrderPaymentController::class, 'destroy']);
        Route::post('/update/{id}', [SalesOrderPaymentController::class, 'update']);
        Route::post('/report', [SalesOrderPaymentController::class, 'SalesOrderPaymentReport']);
    });
    Route::prefix('zatca')->group(function () {
        Route::get('/{id}',  [ZatcaRegistrationController::class, 'index']);
        Route::get('/preinvoicehash/{id}',  [ZatcaRegistrationController::class, 'preinvoicehash']);
        Route::get('/show-byId/{id}', [ZatcaRegistrationController::class, 'show']);
        Route::post('/store', [ZatcaRegistrationController::class, 'store']);
        Route::get('/delete/{id}', [ZatcaRegistrationController::class, 'destroy']);
        Route::post('/update/{id}', [ZatcaRegistrationController::class, 'update']);
        Route::post('/zatca-invoices', [ZatcaRegistrationController::class, 'getInvoices']);
        Route::post('/update-qrlink', [ZatcaRegistrationController::class, 'updateQrLink']);
        Route::post('/save-zatca-response', [ZatcaRegistrationController::class, 'ZatcaResponseStore']);
    });
    Route::prefix('history')->group(function () {
        Route::post('/sales',  [ProductController::class, 'SalesHistory']);
        Route::post('/purchase',  [ProductController::class, 'PurchaseHistory']);
    });
    Route::prefix('van-exicutive')->group(function () {
        Route::get('/{branchId}',  [VanExicutiveController::class, 'index']);
        Route::get('/show-byId/{id}', [VanExicutiveController::class, 'show']);
        Route::post('/store', [VanExicutiveController::class, 'store']);
        Route::get('/delete/{id}', [VanExicutiveController::class, 'destroy']);
        Route::post('/update/{id}', [VanExicutiveController::class, 'update']);
    });
    Route::prefix('van-exicutive-settings')->group(function () {
        Route::get('/{branchId}',  [VanExicutiveSettingsController::class, 'index']);
        Route::get('/show-byId/{id}', [VanExicutiveSettingsController::class, 'show']);
        Route::post('/store', [VanExicutiveSettingsController::class, 'store']);
        Route::get('/delete/{id}', [VanExicutiveSettingsController::class, 'destroy']);
        Route::post('/update/{id}', [VanExicutiveSettingsController::class, 'update']);
    });
    Route::prefix('report-file-dev')->group(function () {
        Route::get('/{branchId}',  [RportFileDevController::class, 'index']);
        Route::get('/show-byId/{id}', [RportFileDevController::class, 'show']);
        Route::post('/store', [RportFileDevController::class, 'store']);
        Route::delete('/delete/{id}', [RportFileDevController::class, 'destroy']);
        Route::post('/update/{id}', [RportFileDevController::class, 'update']);
        Route::get('/show-by-distinct/{branchId}', [RportFileDevController::class, 'showByDistinct']);
    });
    Route::prefix('transaction-batch')->group(function () {
        Route::get('/{branchId}',  [TransactionBatch::class, 'index']);
        Route::get('by-voucher/{voucherType}/{branchId}',  [TransactionBatch::class, 'showByVoucherType']);
        Route::get('/show-byId/{id}', [TransactionBatch::class, 'show']);
        Route::post('/store', [TransactionBatch::class, 'store']);
        Route::delete('/delete/{id}', [TransactionBatch::class, 'destroy']);
        Route::post('/update/{id}', [TransactionBatch::class, 'update']);
    });
    Route::prefix('general-reminder')->group(function () {
        Route::get('/{branchId}',  [GeneralReminderController::class, 'index']);
        Route::get('/show-byId/{id}', [GeneralReminderController::class, 'show']);
        Route::post('/store', [GeneralReminderController::class, 'store']);
        Route::delete('/delete/{id}', [GeneralReminderController::class, 'destroy']);
        Route::post('/update/{id}', [GeneralReminderController::class, 'update']);
    });



    //payhead
    Route::post('/save-payhead', [PayheadController::class, 'store']);
    Route::post('/update-payhead/{id}', [PayheadController::class, 'update']);
    Route::get('/payhead-byId/{id}', [PayheadController::class, 'show']);
    Route::get('/getall-payhead/{branchId}',  [PayheadController::class, 'index']);
    Route::get('/delete-payhead/{id}', [PayheadController::class, 'destroy']);


    //salarysettings
    Route::post('/save-salarysettingsanddetaiils', [SalarySettingsController::class, 'store']);
    Route::post('/update-salarysettingsanddetails/{id}', [SalarySettingsController::class, 'update']);
    Route::get('/salarysettings-byId/{id}', [SalarySettingsController::class, 'show']);
    Route::get('/getall-salarysettings/{branchId}',  [SalarySettingsController::class, 'index']);
    Route::get('/delete-salarysettings/{id}', [SalarySettingsController::class, 'destroy']);
    Route::post('/salary-report', [SalarySettingsController::class, 'fillGrid']);
    Route::post('/monthly-attandance-report', [StaffAttendanceMasterController::class, 'fillGrid']);
    Route::post('/staff-attandance-report', [StaffAttendanceMasterController::class, 'StaffAttendanceReport']);

    //staffattendencemaster
    Route::post('/save-staffattendencemaster', [StaffAttendanceMasterController::class, 'store']);
    Route::post('/update-staffattendencemaster/{id}', [StaffAttendanceMasterController::class, 'update']);
    Route::get('/staffattendencemaster-byId/{id}', [StaffAttendanceMasterController::class, 'show']);
    Route::get('/getall-staffattendencemaster/{branchId}',  [StaffAttendanceMasterController::class, 'index']);
    Route::get('/delete-staffattendencemaster/{id}', [StaffAttendanceMasterController::class, 'destroy']);


    //salaryadvancepayment
    Route::post('/save-salaryadvancepayment', [SalaryAdvancePaymentController::class, 'store']);
    Route::post('/update-salaryadvancepayment/{id}', [SalaryAdvancePaymentController::class, 'update']);
    Route::get('/salaryadvancepayment-byId/{id}', [SalaryAdvancePaymentController::class, 'show']);
    Route::get('/getall-salaryadvancepayment/{branchId}',  [SalaryAdvancePaymentController::class, 'index']);
    Route::get('/delete-salaryadvancepayment/{id}', [SalaryAdvancePaymentController::class, 'destroy']);


    //bonusdeductionmaster
    Route::post('/save-bonusdeductionmaster', [BonusDeductionMasterController::class, 'store']);
    Route::post('/update-bonusdeductionmaster/{id}', [BonusDeductionMasterController::class, 'update']);
    Route::get('/bonusdeductionmaster-byId/{id}', [BonusDeductionMasterController::class, 'show']);
    Route::get('/getall-bonusdeductionmaster/{branchId}',  [BonusDeductionMasterController::class, 'index']);
    Route::get('/delete-bonusdeductionmaster/{id}', [BonusDeductionMasterController::class, 'destroy']);

    //staffholiday
    Route::post('/save-staffholidayregister', [StaffHolidayRegisterController::class, 'store']);
    Route::post('/update-staffholidayregister/{id}', [StaffHolidayRegisterController::class, 'update']);
    Route::post('/staffholidayregisters', [StaffHolidayRegisterController::class, 'index']);
    Route::get('/get-staffholidayregisters-byId/{id}', [StaffHolidayRegisterController::class, 'show']);
    Route::get('/delete-staffholidayregisters/{id}', [StaffHolidayRegisterController::class, 'destroy']);

    //salarymaster
    Route::post('/save-salarymasteranddetails', [SalaryMasterController::class, 'store']);
    Route::post('/update-salarymasteranddetails/{id}', [SalaryMasterController::class, 'update']);
    Route::get('/get-salarymaster-byId/{id}', [SalaryMasterController::class, 'show']);
    Route::post('/salarymasters', [SalaryMasterController::class, 'index']);
    Route::get('/delete-salarymaster/{id}', [SalaryMasterController::class, 'destroy']);

    Route::post('/save-manufacturing-journal', [ManufacturingJournalController::class, 'store']);
    Route::get('/manufacturing-journal-by-id/{id}', [ManufacturingJournalController::class, 'show']);
    Route::post('/manufacturing-journal', [ManufacturingJournalController::class, 'index']);
    Route::post('/update-manufacturing-journal/{id}', [ManufacturingJournalController::class, 'update']);
    Route::get('/manufacturing-journal-delete/{id}', [ManufacturingJournalController::class, 'destroy']);


    //vandayregister
    Route::post('/save-vandayregister', [VanDayRegisterController::class, 'store']);
    Route::post('/update-vandayregister/{id}', [VanDayRegisterController::class, 'update']);
    Route::get('/get-all-vandayregister/{id}', [VanDayRegisterController::class, 'index']);
    Route::post('/ledger/bulk-upload', [AccountLedgerController::class, 'bulkUpload']);
    Route::post('/product/bulk-upload', [ProductController::class, 'importProducts']);
    Route::post('/send-mail', [MailController::class, 'send']);
    Route::post('/inventory-reports', [ProductController::class, 'InvendoryReports']);
    Route::post('/sales-summary-report-data', [SalesReturnMasterController::class, 'SaleSummaryReportData']);
});