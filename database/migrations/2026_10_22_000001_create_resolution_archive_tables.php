<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resolution_repositories', function (Blueprint $t) {
            $t->increments('id');
            $t->string('code', 30)->unique();
            $t->string('name', 100);
            $t->string('root_path', 700);
        });
        Schema::create('resolution_folders', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('repository_id');
            $t->string('relative_path', 700);
            $t->foreign('repository_id')->references('id')->on('resolution_repositories')->restrictOnDelete();
            $t->unique(['repository_id', 'relative_path'], 'resolution_folder_location');
        });
        Schema::create('resolution_series', function (Blueprint $t) {
            $t->increments('id');
            $t->string('code', 12)->unique();
            $t->string('name', 100);
        });
        DB::table('resolution_series')->insert([
            ['code' => 'RDR', 'name' => 'Resolución Directoral Regional'],
            ['code' => 'RD', 'name' => 'Resolución Directoral'],
            ['code' => 'ROI', 'name' => 'ROI (código del archivo)'],
        ]);
        Schema::create('resolutions', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('folder_id');
            $t->unsignedInteger('series_id')->nullable();
            $t->string('filename', 255);
            $t->unsignedSmallInteger('year')->nullable();
            $t->unsignedInteger('number_start')->nullable();
            $t->unsignedInteger('number_end')->nullable();
            $t->unsignedBigInteger('size_bytes');
            $t->foreign('folder_id')->references('id')->on('resolution_folders')->restrictOnDelete();
            $t->foreign('series_id')->references('id')->on('resolution_series')->restrictOnDelete();
            $t->unique(['folder_id', 'filename'], 'resolution_file_location');
            $t->index(['year', 'number_start'], 'resolution_year_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolutions');
        Schema::dropIfExists('resolution_series');
        Schema::dropIfExists('resolution_folders');
        Schema::dropIfExists('resolution_repositories');
    }
};
