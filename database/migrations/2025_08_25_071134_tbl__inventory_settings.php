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
        // Schema::create('tbl_InventorySettings', function (Blueprint $table) {
        //     $table->bigIncrements('InventorySettingsId');
        //     $table->string('NegativeStock', 255)->nullable();
        //     $table->string('stockValueCalculation',255)->nullable();
        //     $table->boolean('SalesPriceUpdateByCostPricePercentage')->nullable();
        //     $table->boolean('maintainGodown')->nullable();
        //     $table->boolean('maintainRack')->nullable();
        //     $table->boolean('lowStockReminder')->nullable();
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
        Schema::dropIfExists('tbl_InventorySettings');
    }
};
