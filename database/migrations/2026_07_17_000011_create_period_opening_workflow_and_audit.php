<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahun_akademiks', function (Blueprint $table): void {
            $table->boolean('is_published')->default(false)->after('is_active');
            $table->timestamp('published_at')->nullable()->after('activated_at');
            $table->foreignId('published_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->index(['status', 'is_published'], 'taka_status_publication_index');
        });

        Schema::create('period_readiness_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('taka_id')->constrained('tahun_akademiks')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('purpose', 32)->default('manual');
            $table->string('status', 16);
            $table->unsignedInteger('ready_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('checks');
            $table->timestamp('checked_at');
            $table->timestamps();
            $table->index(['taka_id', 'purpose', 'checked_at'], 'readiness_period_purpose_index');
        });

        Schema::create('period_publications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('taka_id')->constrained('tahun_akademiks')->restrictOnDelete();
            $table->foreignId('readiness_snapshot_id')->constrained('period_readiness_snapshots')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->json('summary');
            $table->timestamp('published_at');
            $table->timestamps();
            $table->unique('taka_id');
        });

        Schema::create('period_copy_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_period_id')->constrained('tahun_akademiks')->restrictOnDelete();
            $table->foreignId('target_period_id')->constrained('tahun_akademiks')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->json('selections');
            $table->string('status', 32);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('conflict_count')->default(0);
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index(['source_period_id', 'target_period_id'], 'period_copy_source_target_index');
        });

        Schema::create('academic_workflow_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('taka_id')->nullable()->constrained('tahun_akademiks')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_reference_id')->nullable();
            $table->string('event', 80);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['taka_id', 'event', 'created_at'], 'academic_audit_period_event_index');
            $table->index(['subject_type', 'subject_id'], 'academic_audit_subject_index');
            $table->index(['actor_type', 'actor_reference_id'], 'academic_audit_actor_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_workflow_audits');
        Schema::dropIfExists('period_copy_runs');
        Schema::dropIfExists('period_publications');
        Schema::dropIfExists('period_readiness_snapshots');
        Schema::table('tahun_akademiks', function (Blueprint $table): void {
            $table->dropIndex('taka_status_publication_index');
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn(['is_published', 'published_at']);
        });
    }
};
