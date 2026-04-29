<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $user;
    protected $subject;
    protected $class;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        Role::create(['name' => 'School Admin']);
        Role::create(['name' => 'guru']);

        // Setup Tenant and User
        $this->tenant = Tenant::create([
            'name' => 'Test School',
            'slug' => 'test-school',
            'status' => 'active'
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'admin@test.com'
        ]);
        $this->user->assignRole('School Admin');

        // Setup Metadata
        $this->subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Matematika',
            'code' => 'MTK'
        ]);

        $this->class = Classes::create([
            'tenant_id' => $this->tenant->id,
            'name' => '10A',
            'level' => '10'
        ]);

        // Mock IdentifyTenant
        app()->instance('tenant_id', $this->tenant->id);
    }

    public function test_it_can_create_a_question_with_options()
    {
        $payload = [
            'subject_id' => $this->subject->id,
            'class_id' => $this->class->id,
            'type' => 'mcq',
            'content' => 'Berapa 1 + 1?',
            'difficulty' => 'easy',
            'options' => [
                ['content' => '1', 'is_correct' => false],
                ['content' => '2', 'is_correct' => true],
                ['content' => '3', 'is_correct' => false],
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/questions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.content.question_text', 'Berapa 1 + 1?');

        $this->assertDatabaseHas('questions', [
            'content->question_text' => 'Berapa 1 + 1?'
        ]);
    }

    public function test_it_fails_if_no_option_is_correct()
    {
        $payload = [
            'subject_id' => $this->subject->id,
            'class_id' => $this->class->id,
            'type' => 'mcq',
            'content' => 'Pilih salah satu?',
            'difficulty' => 'easy',
            'options' => [
                ['content' => 'A', 'is_correct' => false],
                ['content' => 'B', 'is_correct' => false],
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/questions', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options']);
    }

    public function test_it_cannot_access_questions_from_another_tenant()
    {
        // Create another tenant and its question
        $otherTenant = Tenant::create(['name' => 'Other', 'slug' => 'other']);
        $otherQuestion = Question::factory()->create([
            'tenant_id' => $otherTenant->id,
            'subject_id' => $this->subject->id, // Assuming shared metadata for simplicity or create new
            'class_id' => $this->class->id,
            'created_by' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/questions/{$otherQuestion->id}");

        $response->assertStatus(404);
    }

    public function test_it_can_soft_delete_a_question()
    {
        $question = Question::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subject_id' => $this->subject->id,
            'class_id' => $this->class->id,
            'created_by' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/questions/{$question->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('questions', ['id' => $question->id]);
    }
}
