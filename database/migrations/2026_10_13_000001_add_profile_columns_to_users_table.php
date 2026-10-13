<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('student')->after('password');
            $table->string('status', 20)->default('active')->after('role');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('nis', 30)->nullable()->after('phone');
            $table->string('nip', 30)->nullable()->after('nis');
            $table->string('title', 60)->nullable()->after('nip');
            $table->string('avatar_path')->nullable()->after('title');
            $table->string('bio', 500)->nullable()->after('avatar_path');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'status']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'status']);
            $table->dropIndex(['name']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'role', 'status', 'phone', 'nis', 'nip', 'title', 'avatar_path', 'bio', 'last_login_at',
            ]);
        });
    }
};
