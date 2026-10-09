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
        // Schema::create('tbl_UserGroup', function (Blueprint $table) {
        //     $table->bigIncrements('usergroupId');
        //     $table->string('usergroupName', 255)->nullable();
        //     $table->tinyInteger('activeStatus')->default(1);
        //     $table->timestamp('CreatedDate')->nullable();
        //     $table->string('CreatedUser', 100)->nullable();
        //     $table->timestamp('ModifedDate')->nullable();
        //     $table->string('ModifedUser', 100)->nullable();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_UserGroup');
    }
};
