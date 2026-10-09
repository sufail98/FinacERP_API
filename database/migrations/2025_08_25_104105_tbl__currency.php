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
        // Schema::create('tbl_Currency', function (Blueprint $table) {
        //     $table->bigIncrements('currencyId');
        //     $table->string('currencySymbol', 255)->nullable();
        //     $table->string('currencyName', 255)->nullable();
        //     $table->string('subunitName', 255)->nullable();
        //     $table->integer('noOfDecimalPlace')->nullable();
        //     $table->text('narration')->nullable();
        //     $table->boolean('default')->nullable();
        //     $table->string('branchId', 100)->nullable();
        //     $table->timestamp('CreatedDate')->nullable();
        //     $table->string('CreatedUser', 100)->nullable();
        //     $table->timestamp('ModifiedDate')->nullable();
        //     $table->string('ModifiedUser', 100)->nullable();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_Currency');
    }
};
