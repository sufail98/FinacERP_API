<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schema::create('tbl_SalesSettings', function (Blueprint $table) {
        //     $table->bigIncrements('SalesSettingsId');
        //     $table->boolean('showProductDescription')->nullable();
        //     $table->boolean('showFeeQtyColumn')->nullable();
        //     $table->boolean('showLineDiscount')->nullable();
        //     $table->boolean('showPurchaserate')->nullable();
        //     $table->boolean('ShowPartNo')->nullable();
        //     $table->boolean('ActivateSalesRetention')->nullable();
        //     $table->boolean('showBillProfit')->nullable();
        //     $table->boolean('showBillDiscountAmount')->nullable();
        //     $table->boolean('showBillDiscountPerc')->nullable();
        //     $table->timestamp('createdDate')->nullable();
        //     $table->string('createdUser', 100)->nullable();
        //     $table->timestamp('modifiedDate')->nullable();
        //     $table->string('modifiedUser', 100)->nullable();

        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_SalesSettings');
    }
};
