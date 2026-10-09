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
        // Schema::create('tbl_FinanceSettings', function (Blueprint $table) {
        //     $table->bigIncrements('FinanceSettingsId');
        //     $table->boolean('ShowAllTransactions')->nullable();
        //     $table->boolean('MaintainBillbyBill')->nullable();
        //     $table->boolean('multiCurrency')->nullable();
        //     $table->boolean('showLedgerbalance')->nullable();
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
        Schema::dropIfExists('tbl_FinanceSettings');
    }
};
