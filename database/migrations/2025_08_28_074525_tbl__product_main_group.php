<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::create('tbl_ProductMainGroup', function (Blueprint $table) {
    //         $table->bigIncrements('groupCode');
    //         $table->string('groupName', 255)->nullable();
    //         $table->boolean('scaleGroup')->nullable();
    //         $table->integer('productCodeLength')->nullable();
    //         $table->integer('nextNumber')->nullable();
    //         $table->string('branchId', 100)->nullable();
    //         $table->timestamp('CreatedDate')->nullable();
    //         $table->string('CreatedUser', 100)->nullable();
    //         $table->timestamp('ModifiedDate')->nullable();
    //         $table->string('ModifiedUser', 100)->nullable();
    //     });
    // }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_ProductMainGroup');
    }
};
