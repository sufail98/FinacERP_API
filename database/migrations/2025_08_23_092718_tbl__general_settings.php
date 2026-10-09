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
        Schema::create('tbl_GeneralSettings', function (Blueprint $table) {
            // $table->bigIncrements('generalSettingsId');
            // $table->string('branchId', 255)->nullable();
            // $table->boolean('ActivateTax')->nullable();
            // $table->boolean('costCentre')->nullable();
            // $table->boolean('AccountPosting')->nullable();
            // $table->string('negativeCashTransaction',255)->nullable();
            // $table->string('AccountCalculationMethod', 255)->nullable();
            // $table->integer('decimalPart')->nullable();
            // $table->string('dateformat',255)->nullable();
            // $table->string('taxType',255)->nullable();
            // $table->boolean('taxincluded')->nullable();
            // $table->boolean('taxCessincluded')->nullable();
            // $table->string('branchHeader', 255)->nullable();
            // $table->string('branchFooter', 255)->nullable();
            // $table->string('zatcaType', 255)->nullable();
            // $table->integer('RoundOff')->nullable();
            // $table->string('RoundOffDigit', 255)->nullable();
            // $table->boolean('showCurrencyprefix')->nullable();
            // $table->boolean('askConfirmationSave')->nullable();
            // $table->boolean('askConfirmationEdit')->nullable();
            // $table->boolean('askConfirmationDelete')->nullable();
            // $table->boolean('askConfirmationClear')->nullable();
            // $table->boolean('askConfirmationRowRemove')->nullable();
            // $table->boolean('askConfirmationPrint')->nullable();
            // $table->boolean('askConfirmationClose')->nullable();
            // $table->timestamp('createdDate')->nullable();
            // $table->string('createdUser', 100)->nullable();
            // $table->timestamp('modifiedDate')->nullable();
            // $table->string('modifiedUser', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_GeneralSettings');
    }
};
