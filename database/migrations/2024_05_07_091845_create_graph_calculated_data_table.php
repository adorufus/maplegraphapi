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
            $table->string('reach')->collation('utf8mb4_unicode_ci');
            $table->string('total_interactions')->collation('utf8mb4_unicode_ci');
            $table->string('comments')->collation('utf8mb4_unicode_ci');
            $table->string('ig_reels_avg_watch_time')->collation('utf8mb4_unicode_ci');
            $table->string('ig_reels_video_view_total_time')->collation('utf8mb4_unicode_ci');
            $table->string('likes')->collation('utf8mb4_unicode_ci');
            $table->string('plays')->collation('utf8mb4_unicode_ci');
            $table->string('saved')->collation('utf8mb4_unicode_ci');
            $table->string('shares')->collation('utf8mb4_unicode_ci');
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
