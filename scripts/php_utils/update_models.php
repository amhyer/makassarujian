<?php

$dir = __DIR__ . '/app/Models/';

$tenantBelongs = "use App\Modules\Tenant\Traits\BelongsToTenant;\n\nclass {MODEL} extends Model\n{\n    use BelongsToTenant;\n\n    protected \$guarded = ['id'];\n}";
$tenantBelongsUuid = "use Illuminate\Database\Eloquent\Concerns\HasUuids;\nuse App\Modules\Tenant\Traits\BelongsToTenant;\n\nclass {MODEL} extends Model\n{\n    use HasUuids, BelongsToTenant;\n\n    protected \$guarded = ['id'];\n}";
$uuidOnly = "use Illuminate\Database\Eloquent\Concerns\HasUuids;\n\nclass {MODEL} extends Model\n{\n    use HasUuids;\n\n    protected \$guarded = ['id'];\n}";
$normal = "class {MODEL} extends Model\n{\n    protected \$guarded = ['id'];\n}";

$modelsConfig = [
    'Tenant' => "use Illuminate\Database\Eloquent\Concerns\HasUuids;\nuse Illuminate\Database\Eloquent\SoftDeletes;\n\nclass Tenant extends Model\n{\n    use HasUuids, SoftDeletes;\n\n    protected \$guarded = ['id'];\n}",
    'Classes' => $tenantBelongs,
    'Subject' => $tenantBelongs,
    'Question' => $tenantBelongs,
    'Option' => $normal,
    'Exam' => $tenantBelongs,
    'ExamSession' => $normal, 
    'ExamParticipant' => $normal,
    'Attempt' => $uuidOnly, 
    'AttemptQuestion' => $normal,
    'Answer' => $normal,
    'Result' => $normal,
    'AuditLog' => $normal, 
];

foreach ($modelsConfig as $model => $template) {
    $file = $dir . $model . '.php';
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $pattern = "/class {$model} extends Model\n\{[^\}]*\}/s";
        $replacement = str_replace('{MODEL}', $model, $template);
        $content = preg_replace($pattern, $replacement, $content);
        file_put_contents($file, $content);
        echo "Updated {$model}.php\n";
    }
}
