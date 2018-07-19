<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaravelPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auth_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->unsignedInteger('owner_id')->nullable();
            $table->timestamps();

            $table->unique('name');
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->timestamps();

            $table->unique('name');
        });

        Schema::create('auth_group_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('auth_group_id');
            $table->unsignedInteger('permission_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->foreign('auth_group_id')
                ->references('id')
                ->on('auth_groups')
                ->onDelete('cascade');

            $table->unique(['permission_id', 'auth_group_id'], 'lp_agp_unique');
        });

        Schema::create('auth_group_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('auth_group_id');
            $table->unsignedInteger('user_id');
            $table->string('role', 100)->nullable();

            $table->foreign('auth_group_id')
                ->references('id')
                ->on('auth_groups')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique(['user_id', 'auth_group_id'], 'lp_agu_unique');
        });

        Schema::create('auth_group_user_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('auth_group_user_id');
            $table->unsignedInteger('permission_id');


            $table->foreign('auth_group_user_id')
                ->references('id')
                ->on('auth_group_users')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->unique(['auth_group_user_id', 'permission_id'], 'lp_agup_unique');
        });

        Schema::table(config('permissions.tables.users'), function (Blueprint $table) {
             $table->unsignedInteger('current_auth_group_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auth_group_users');
        Schema::dropIfExists('auth_group_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('auth_groups');

        Schema::table(config('permissions.models.user'), function (Blueprint $table) {
            $table->dropColumn('current_auth_group_id');
        });
    }
}