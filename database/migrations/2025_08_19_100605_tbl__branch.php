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
        // Schema::create('tbl_Branch', function (Blueprint $table) {
    
            // $table->bigIncrements('branchId'); // Primary Key

            // Foreign keys (optional, can add foreign constraints later)
            // $table->unsignedBigInteger('companyId')->nullable();
            // $table->unsignedBigInteger('currencyId')->nullable();

            // $table->string('branchName', 255)->nullable();
            // $table->string('branchNameFL', 255)->nullable();

            // $table->string('address', 255)->nullable();
            // $table->string('addressFL', 255)->nullable();

            // $table->string('streetName', 255)->nullable();
            // $table->string('streetNameFL', 255)->nullable();

            // $table->string('buildingNo', 50)->nullable();
            // $table->string('buildingNoFL', 50)->nullable();

            // $table->string('additionalNumber', 50)->nullable();
            // $table->string('additionalNumberFL', 50)->nullable();

            // $table->string('cityName', 100)->nullable();
            // $table->string('cityNameFL', 100)->nullable();

            // $table->string('district', 100)->nullable();
            // $table->string('districtFL', 100)->nullable();

            // $table->string('country', 100)->nullable();
            // $table->string('countryFL', 100)->nullable();

            // $table->string('postalCode', 20)->nullable();
            // $table->string('postalCodeFL', 20)->nullable();

            // $table->string('phoneNo', 20)->nullable();
            // $table->string('fax', 20)->nullable();
            // $table->string('mobile', 20)->nullable();
            // $table->string('email', 150)->nullable();
            // $table->string('web', 150)->nullable();

            // $table->string('taxNo', 50)->nullable();
            // $table->string('panNo', 50)->nullable();
            // $table->string('crNo', 50)->nullable();

            // $table->string('logo', 255)->nullable();

            // $table->boolean('mainBranch')->default(false);
            // $table->date('startDate')->nullable();

            // $table->string('dbName', 150)->nullable();

            // $table->boolean('activeStatus')->default(true);
            // $table->string('subscriptionNo', 100)->nullable();

            // $table->timestamp('createdDate')->nullable();
            // $table->string('createdUser', 100)->nullable();
            // $table->timestamp('modifiedDate')->nullable();
            // $table->string('modifiedUser', 100)->nullable();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_Branch');
    }
};
