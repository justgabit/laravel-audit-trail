<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('audit-trail.connection'))
            ->table(config('audit-trail.table_name'), function (Blueprint $table) {
                $table->text('table_id')->change();
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
