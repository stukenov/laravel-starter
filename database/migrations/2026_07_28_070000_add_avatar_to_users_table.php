<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RULE(files): the column holds a path, the disk holds the file — AGENTS.md.
 *
 * The starter's own use of the upload story, and the reason it is here
 * rather than in an example entity: a feature with no consumer is a
 * feature nobody exercises. The nav has always drawn initials, and the
 * component had a prop for a picture that nothing ever filled — that
 * prop was removed for exactly that reason. Now something fills it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
};
