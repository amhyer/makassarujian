<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Classes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_models_with_current_tenant_id_automatically()
    {
        $tenant = Tenant::create(['name' => 'School A']);
        
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        $this->actingAs($user);

        $class = Classes::create(['name' => 'Class A', 'level' => '10']);

        $this->assertEquals($tenant->id, $class->tenant_id);
    }

    public function test_isolates_models_so_users_only_see_their_own_tenant_data()
    {
        $tenantA = Tenant::create(['name' => 'School A']);
        $tenantB = Tenant::create(['name' => 'School B']);

        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($userA);
        Classes::create(['name' => 'Class A', 'level' => '10']);

        $this->actingAs($userB);
        Classes::create(['name' => 'Class B', 'level' => '10']);

        $this->assertEquals(1, Classes::count());
        $this->assertEquals('Class B', Classes::first()->name);

        $this->actingAs($userA);
        $this->assertEquals(1, Classes::count());
        $this->assertEquals('Class A', Classes::first()->name);
    }
}
