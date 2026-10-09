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
        // Schema::create('tbl_User', function (Blueprint $table) {
        //     $table->bigIncrements('userId');
        //     $table->string('userName', 255)->nullable();
        //     $table->string('Password', 255)->nullable();
        //     $table->string('phoneNo', 255)->nullable();
        //     $table->string('email', 255)->nullable();
        //     $table->string('profilePhoto', 255)->nullable();
        //     $table->text('Narration')->nullable();
        //     $table->json('branchIds')->nullable();
        //     $table->integer('UserRoleId')->nullable();
        //     $table->string('user_lang', 255)->nullable(); 
        //     $table->tinyInteger('ActiveStatus')->default(1);
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
        Schema::dropIfExists('tbl_User');
    }
};
