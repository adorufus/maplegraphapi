<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tiktok_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token');
            $table->integer('expires_in');
            $table->string('open_id');
            $table->integer('refresh_expires_in');
            $table->string('refresh_token');
            $table->string('scope');
            $table->string('token_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tiktok_tokens');
    }
};
