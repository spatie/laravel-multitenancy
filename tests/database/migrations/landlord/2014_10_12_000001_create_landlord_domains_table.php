<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLandlordDomainsTable extends Migration
{
    public function up()
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('tenant_id')->unsigned()->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');

            $table->string('domain')->unique();
            $table->timestamps();
        });
    }
}
