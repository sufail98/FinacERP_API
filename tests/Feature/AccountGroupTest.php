<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AccountGroup;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AccountGroupTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
        
        // Create a test branch
        $this->branch = Branch::factory()->create();
    }

    /** @test */
    public function it_can_list_all_account_groups()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/account-groups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);
    }

    /** @test */
    public function it_can_create_an_account_group()
    {
        $accountGroupData = [
            'AccountGroupCode' => 'AG001',
            'accountGroupName' => 'Test Account Group',
            'groupUnder' => null,
            'narration' => 'Test narration',
            'LedgerNextNo' => 1,
            'branchId' => $this->branch->branchId
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/save-account-group', $accountGroupData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'groupId',
                    'AccountGroupCode',
                    'accountGroupName',
                    'groupUnder',
                    'narration',
                    'LedgerNextNo',
                    'branchId',
                    'CreatedDate',
                    'CreatedUser',
                    'ModifiedDate',
                    'ModifiedUser'
                ],
                'message'
            ]);

        $this->assertDatabaseHas('tbl_AccountGroup', [
            'AccountGroupCode' => 'AG001',
            'accountGroupName' => 'Test Account Group'
        ]);
    }

    /** @test */
    public function it_can_show_an_account_group()
    {
        $accountGroup = AccountGroup::factory()->create([
            'branchId' => $this->branch->branchId
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/get-account-group-byId/{$accountGroup->groupId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_account_group()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/get-account-group-byId/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_update_an_account_group()
    {
        $accountGroup = AccountGroup::factory()->create([
            'branchId' => $this->branch->branchId
        ]);

        $updateData = [
            'accountGroupName' => 'Updated Account Group Name',
            'narration' => 'Updated narration'
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/update-account-group/{$accountGroup->groupId}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Account group updated successfully'
            ]);

        $this->assertDatabaseHas('tbl_AccountGroup', [
            'groupId' => $accountGroup->groupId,
            'accountGroupName' => 'Updated Account Group Name'
        ]);
    }

    /** @test */
    public function it_can_delete_an_account_group()
    {
        $accountGroup = AccountGroup::factory()->create([
            'branchId' => $this->branch->branchId
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/delete-account-group/{$accountGroup->groupId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Account group deleted successfully'
            ]);

        $this->assertDatabaseMissing('tbl_AccountGroup', [
            'groupId' => $accountGroup->groupId
        ]);
    }

    /** @test */
    public function it_cannot_delete_account_group_with_child_groups()
    {
        $parentGroup = AccountGroup::factory()->create([
            'branchId' => $this->branch->branchId
        ]);

        $childGroup = AccountGroup::factory()->create([
            'groupUnder' => $parentGroup->groupId,
            'branchId' => $this->branch->branchId
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/delete-account-group/{$parentGroup->groupId}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete account group with child groups'
            ]);
    }

    /** @test */
    public function it_can_get_account_groups_by_branch()
    {
        $accountGroup = AccountGroup::factory()->create([
            'branchId' => $this->branch->branchId
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/account-groups-by-branch/{$this->branch->branchId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_account_groups_hierarchy()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/account-groups-hierarchy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);
    }
}
