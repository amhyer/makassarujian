<?php

$dir = __DIR__ . '/database/migrations/';
$files = scandir($dir);

function getFile($pattern, $files) {
    foreach($files as $file) {
        if (strpos($file, $pattern) !== false) return $file;
    }
    return null;
}

$migrations = [
    'create_classes_table' => <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('level');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('classes'); }
};
PHP,
    'create_subjects_table' => <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('subjects'); }
};
PHP,
    'create_questions_table' => <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('type')->default('mcq'); // mcq | essay
            $table->string('difficulty')->default('medium');
            $table->json('content');
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('questions'); }
};
PHP,
    'create_options_table' => <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->text('label');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('options'); }
};
PHP,
    'create_exams_table' => <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('school'); // school | tka | tryout
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->integer('duration'); // in minutes
            $table->integer('total_questions');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('exams'); }
};
PHP,
    'create_exam_sessions_table' => <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignUuid('proctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_name');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('exam_sessions'); }
};
PHP,
    'create_exam_participants_table' => <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('exam_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->string('status')->default('not_started');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('exam_participants'); }
};
PHP,
    'create_attempts_table' => <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->float('score')->nullable();
            $table->string('status')->default('ongoing');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('attempts'); }
};
PHP,
    'create_attempt_questions_table' => <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('attempt_id')->constrained('attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->integer('order_no');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('attempt_questions'); }
};
PHP,
    'create_answers_table' => <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('attempt_id')->constrained('attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('option_id')->nullable()->constrained('options')->nullOnDelete();
            $table->text('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('answers'); }
};
PHP,
    'create_results_table' => <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('attempt_id')->constrained('attempts')->cascadeOnDelete();
            $table->float('score');
            $table->integer('correct_count');
            $table->integer('wrong_count');
            $table->integer('ranking')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('results'); }
};
PHP,
    'create_audit_logs_table' => <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('audit_logs'); }
};
PHP
];

foreach ($migrations as $pattern => $content) {
    $file = getFile($pattern, $files);
    if ($file) {
        file_put_contents($dir . $file, $content);
        echo "Updated $file\n";
    }
}
