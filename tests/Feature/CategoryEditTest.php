<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_edit_loads_existing_classifications_and_updates_category_data(): void
    {
        $this->seed(MarketplaceUserSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $category = Category::query()->create([
            'title' => 'Refreshments & Event Support',
            'slug' => 'refreshments-event-support',
            'classification_name' => ['Catering', 'Event Support'],
            'subcategory' => ['Refreshments', 'Event Planning'],
            'description' => 'Category description.',
        ]);

        $this->actingAs($admin)
            ->get(route('categories.edit', $category))
            ->assertOk()
            ->assertSee('Refreshments & Event Support')
            ->assertSee('initialItems: ["Catering","Event Support"]', false)
            ->assertSee('initialItems: ["Refreshments","Event Planning"]', false)
            ->assertDontSee('$services');

        $this->actingAs($admin)
            ->put(route('categories.update', $category), [
                'title' => 'Refreshments & Event Support Updated',
                'slug' => 'refreshments-event-support',
                'classification_name' => ['Catering', 'Event Support'],
                'subcategory' => ['Refreshments', 'Event Planning'],
                'description' => 'Updated category description.',
            ])
            ->assertRedirect(route('categories.index'));

        $this->assertSame('Refreshments & Event Support Updated', $category->fresh()->title);
        $this->assertSame(['Catering', 'Event Support'], $category->fresh()->classification_name);
        $this->assertSame(['Refreshments', 'Event Planning'], $category->fresh()->subcategory);
        $this->assertSame('Updated category description.', $category->fresh()->description);
    }
}
