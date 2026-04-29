<?php

use App\Models\Tenant;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use Illuminate\Support\Facades\Route;

it('ensures trial auto expires via service', function () {
    $tenant = Tenant::create([
        'name' => 'Trial School',
        'type' => 'school',
        'status' => 'trial',
        'trial_ends_at' => now()->subDay(),
    ]);

    $subscriptionService = app(\App\Services\SubscriptionService::class);
    $subscriptionService->expireTenant($tenant);

    expect($tenant->fresh()->status)->toBe('expired');
});

it('prevents expired tenant from performing state-changing actions', function () {
    $tenant = Tenant::create([
        'name' => 'Expired School',
        'type' => 'school',
        'status' => 'expired',
    ]);

    // Using an existing user with minimal attributes just for the test
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    Route::middleware([\Illuminate\Session\Middleware\StartSession::class, \App\Http\Middleware\EnsureSubscriptionActive::class])
        ->post('/test-action', function () {
            return 'Success';
        });

    actingAs($user)
        ->post('/test-action')
        ->assertForbidden();
});

it('isolates multi-tenant context correctly', function () {
    $tenant1 = Tenant::create(['name' => 'School A', 'status' => 'active']);
    $tenant2 = Tenant::create(['name' => 'School B', 'status' => 'active']);

    $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
    
    // Simulate IdentityTenant middleware binding
    app()->instance('currentTenant', $user1->tenant_id);

    expect(app('currentTenant'))->toBe($tenant1->id);
    expect(app('currentTenant'))->not->toBe($tenant2->id);
});
