<?php

namespace Tests\Feature;

use App\Filament\Widgets\DashboardWelcomeIllustration;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardWelcomeIllustrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_dashboard_shows_the_role_appropriate_illustration(): void
    {
        $cases = [
            'admin' => 'school-field.png',
            'guru' => 'teacher-students.png',
            'orang_tua' => 'parent-child.png',
        ];

        foreach ($cases as $role => $image) {
            $user = User::role($role)->firstOrFail();
            $user->update(['must_change_password' => false]);

            $this->actingAs($user);

            Livewire::test(DashboardWelcomeIllustration::class)
                ->assertSee("images/dashboard/{$image}", false);
        }
    }
}
