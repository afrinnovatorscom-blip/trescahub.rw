<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShopCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator = User::factory()->create();
        $permissions = collect(['admin.access', 'products.edit'])
            ->map(fn ($slug) => Permission::query()->firstOrCreate(['slug' => $slug], [
                'name' => $slug,
            ]));
        $this->administrator->directPermissions()->attach($permissions->pluck('id'));
    }

    public function test_parent_category_can_be_created_without_subcategories(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('shop-categories.store'), [
                'name' => 'Office Equipment',
                'description' => 'Equipment for the workplace.',
                'status' => 'active',
            ])
            ->assertRedirect(route('shop-categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('shop_categories', [
            'name' => 'Office Equipment',
            'slug' => 'office-equipment',
            'description' => 'Equipment for the workplace.',
            'parent_id' => null,
            'active' => true,
        ]);
        $this->assertSame(1, Shopcategory::query()->count());
    }

    public function test_parent_and_multiple_subcategories_are_saved_together(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('shop-categories.store'), [
                'name' => 'Computers',
                'status' => 'active',
                'subcategories' => [
                    ['name' => 'Laptops', 'description' => 'Portable computers.'],
                    ['name' => 'Desktops', 'description' => 'Desktop computers.'],
                ],
            ])
            ->assertRedirect(route('shop-categories.index'));

        $parent = Shopcategory::query()->where('slug', 'computers')->firstOrFail();
        $this->assertSame(2, $parent->subcategories()->count());
        $this->assertDatabaseHas('shop_categories', [
            'name' => 'Laptops',
            'slug' => 'laptops',
            'description' => 'Portable computers.',
            'parent_id' => $parent->id,
            'active' => true,
        ]);
        $this->assertDatabaseHas('shop_categories', [
            'name' => 'Desktops',
            'slug' => 'desktops',
            'description' => 'Desktop computers.',
            'parent_id' => $parent->id,
            'active' => true,
        ]);
    }

    public function test_parent_category_can_be_created_with_one_subcategory(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('shop-categories.store'), [
                'name' => 'Networking',
                'status' => 'active',
                'subcategories' => [['name' => 'Routers']],
            ])
            ->assertRedirect(route('shop-categories.index'));

        $parent = Shopcategory::query()->where('slug', 'networking')->firstOrFail();
        $this->assertSame(1, $parent->subcategories()->count());
        $this->assertSame('Routers', $parent->subcategories()->firstOrFail()->name);
    }

    public function test_duplicate_subcategory_slugs_receive_unique_suffixes(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('shop-categories.store'), [
                'name' => 'Audio Equipment',
                'status' => 'active',
                'subcategories' => [
                    ['name' => 'Headphones'],
                    ['name' => 'Headphones'],
                ],
            ])
            ->assertRedirect(route('shop-categories.index'));

        $this->assertDatabaseHas('shop_categories', [
            'name' => 'Headphones',
            'slug' => 'headphones',
        ]);
        $this->assertDatabaseHas('shop_categories', [
            'name' => 'Headphones',
            'slug' => 'headphones-1',
        ]);
    }

    public function test_invalid_category_and_subcategory_values_are_rejected(): void
    {
        $this->actingAs($this->administrator)
            ->from(route('shop-categories.create'))
            ->post(route('shop-categories.store'), [
                'name' => '',
                'status' => 'active',
            ])
            ->assertRedirect(route('shop-categories.create'))
            ->assertSessionHasErrors('name');

        $this->actingAs($this->administrator)
            ->from(route('shop-categories.create'))
            ->post(route('shop-categories.store'), [
                'name' => 'Audio',
                'status' => 'active',
                'subcategories' => [['name' => '']],
            ])
            ->assertRedirect(route('shop-categories.create'))
            ->assertSessionHasErrors('subcategories.0.name');

        $this->actingAs($this->administrator)
            ->from(route('shop-categories.create'))
            ->post(route('shop-categories.store'), [
                'name' => 'Audio',
                'status' => 'paused',
            ])
            ->assertRedirect(route('shop-categories.create'))
            ->assertSessionHasErrors('status');
    }

    public function test_duplicate_parent_slug_is_rejected(): void
    {
        Shopcategory::query()->create(['name' => 'Existing', 'slug' => 'existing']);

        $this->actingAs($this->administrator)
            ->from(route('shop-categories.create'))
            ->post(route('shop-categories.store'), [
                'name' => 'Existing',
                'status' => 'active',
            ])
            ->assertRedirect(route('shop-categories.create'))
            ->assertSessionHasErrors('slug')
            ->assertSessionHasInput('name', 'Existing');
    }

    public function test_failed_child_save_rolls_back_the_parent_and_all_children(): void
    {
        DB::unprepared(
            "CREATE TRIGGER fail_shop_subcategory BEFORE INSERT ON shop_categories
             WHEN NEW.parent_id IS NOT NULL
             BEGIN SELECT RAISE(ABORT, 'Child insert failed'); END"
        );

        $this->actingAs($this->administrator)
            ->post(route('shop-categories.store'), [
                'name' => 'Rollback Test',
                'status' => 'active',
                'subcategories' => [['name' => 'Child']],
            ])
            ->assertServerError();

        $this->assertDatabaseMissing('shop_categories', ['slug' => 'rollback-test']);
        $this->assertSame(0, Shopcategory::query()->count());
    }

    public function test_editing_a_category_preserves_existing_subcategory_product_assignments(): void
    {
        $parent = Shopcategory::query()->create(['name' => 'Kitchen', 'slug' => 'kitchen']);
        $subcategory = $parent->subcategories()->create([
            'name' => 'Cookware',
            'slug' => 'cookware',
        ]);
        $product = ShopProduct::query()->create([
            'product_name' => 'Saucepan',
            'slug' => 'saucepan',
            'status' => 'in_stock',
            'price' => 5000,
            'working_days' => 2,
            'shop_category_id' => $parent->id,
            'subcategory_id' => $subcategory->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]);

        $this->actingAs($this->administrator)
            ->put(route('shop-categories.update', $parent), [
                'name' => 'Kitchen & Dining',
                'slug' => 'kitchen-dining',
                'description' => 'Kitchen products.',
                'status' => 'inactive',
                'subcategories' => [[
                    'id' => $subcategory->id,
                    'name' => 'Cookware',
                    'slug' => 'cookware',
                    'description' => 'Pots and pans.',
                ]],
            ])
            ->assertRedirect(route('shop-categories.index'));

        $this->assertDatabaseHas('shop_categories', [
            'id' => $subcategory->id,
            'parent_id' => $parent->id,
            'description' => 'Pots and pans.',
            'active' => false,
        ]);
        $this->assertSame($subcategory->id, $product->fresh()->subcategory_id);
    }

    public function test_inactive_parent_hides_assigned_products_even_if_child_is_active(): void
    {
        $parent = Shopcategory::query()->create([
            'name' => 'Inactive Parent',
            'slug' => 'inactive-parent',
            'active' => false,
        ]);
        $subcategory = $parent->subcategories()->create([
            'name' => 'Active Child',
            'slug' => 'active-child',
            'active' => true,
        ]);
        $product = ShopProduct::query()->create([
            'product_name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'status' => 'in_stock',
            'price' => 5000,
            'working_days' => 2,
            'shop_category_id' => $parent->id,
            'subcategory_id' => $subcategory->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]);

        $this->assertFalse($product->isPubliclyVisible());
        $this->get('/shop')->assertDontSee('Hidden Product');
        $this->get(route('shop-products.show', $product))->assertNotFound();
    }

    public function test_category_creation_requires_catalog_permissions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('shop-categories.create'))
            ->assertForbidden();
    }

    public function test_category_creation_requires_products_edit_permission(): void
    {
        $user = User::factory()->create();
        $accessPermission = Permission::query()->firstOrCreate(
            ['slug' => 'admin.access'],
            ['name' => 'Admin Access']
        );
        $user->directPermissions()->attach($accessPermission->id);

        $this->actingAs($user)
            ->get(route('shop-categories.create'))
            ->assertForbidden();
    }
}
