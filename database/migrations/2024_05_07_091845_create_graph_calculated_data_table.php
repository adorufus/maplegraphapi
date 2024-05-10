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
            $table->bigInteger('reach')->collation('utf8mb4_unicode_ci')->change();
            $table->bigInteger('total_interactions')->collation('utf8mb4_unicode_ci')->change();
            $table->bigInteger('comments')->collation('utf8mb4_unicode_ci')->change();
            $table->bigInteger('ig_reels_avg_watch_time')->collation('utf8mb4_unicode_ci')->change();
            $table->bigInteger('ig_reels_video_view_total_time')->collation('utf8mb4_unicode_ci')->change();
            $table->bigInteger('likes')->collation('utf8mb4_unicode_ci')->change();
            $table->bigInteger('plays')->collation('utf8mb4_unicode_ci')->change();
            $table->bigInteger('saved')->collation('utf8mb4_unicode_ci')->change();
            $table->bigInteger('shares')->collation('utf8mb4_unicode_ci')->change();
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
