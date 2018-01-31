<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuditTrailActions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('audit-trail.connection'))
            ->create(config('audit-trail.table_name') . '_actions', function (Blueprint $table) {
            $table->string('id');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->primary('id');
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
            ->dropIfExists(config('audit-trail.table_name') . '_actions');
    }
}
