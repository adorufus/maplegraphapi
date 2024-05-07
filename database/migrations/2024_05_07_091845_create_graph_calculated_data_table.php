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
            $table->string('reach');
            $table->string('total_interactions');
            $table->string('comments');
            $table->string('ig_reels_avg_watch_time');
            $table->string('ig_reels_video_view_total_time');
            $table->string('likes');
            $table->string('plays');
            $table->string('saved');
            $table->string('shares');
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
