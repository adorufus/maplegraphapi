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
        Schema::create('graph_calculated_data', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->bigInteger('reach');
            $table->bigInteger('total_interactions');
            $table->bigInteger('comments');
            $table->bigInteger('ig_reels_avg_watch_time');
            $table->bigInteger('ig_reels_video_view_total_time');
            $table->bigInteger('likes');
            $table->bigInteger('plays');
            $table->bigInteger('saved');
            $table->bigInteger('shares');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('graph_calculated_data');
    }
};
