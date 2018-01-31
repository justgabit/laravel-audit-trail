<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuditTrail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('audit-trail.connection'))
            ->create(config('audit-trail.table_name'), function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('parent_id')->nullable();
                $table->string('action_id');
                $table->unsignedInteger('user_id')->nullable();
                $table->longText('action_data')->nullable();
                $table->text('action_brief')->nullable();
                $table->string('table_name')->nullable();
                $table->unsignedInteger('table_id')->nullable();

                $table->dateTime('date');

                $table->foreign('parent_id')
                    ->references('id')
                    ->on(config('audit-trail.table_name'))
                    ->onDelete('restrict')
                    ->onUpdate('restrict');

                $table->foreign('action_id')
                    ->references('id')
                    ->on(config('audit-trail.table_name') . '_actions')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->index('user_id');
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection(config('audit-trail.connection'))
            ->dropIfExists(config('audit-trail.table_name'));
    }
}
