<?php

namespace Database\Seeders;

use App\Models\ProductVariation;
use App\Models\Shopcategory;
use App\Models\ShopProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo shop catalog: categories, subcategories and published products.
 * Safe to run several times (records are matched by slug). Do not run on production.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Electronics' => [
                'description' => 'Phones, computers and accessories.',
                'subcategories' => ['Phones & Tablets', 'Computers', 'Accessories'],
            ],
            'Tools & Hardware' => [
                'description' => 'Power tools, hand tools and building supplies.',
                'subcategories' => ['Power Tools', 'Hand Tools'],
            ],
            'Home & Kitchen' => [
                'description' => 'Appliances and essentials for the home.',
                'subcategories' => ['Appliances', 'Cookware'],
            ],
            'Office Supplies' => [
                'description' => 'Furniture, stationery and printing.',
                'subcategories' => ['Furniture', 'Stationery'],
            ],
            'Kids & Toys' => [
                'description' => 'Ride-on toys, games and learning.',
                'subcategories' => ['Ride-on Toys'],
            ],
        ];

        $categories = [];
        foreach ($catalog as $name => $data) {
            $parent = Shopcategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $data['description'], 'parent_id' => null, 'active' => true],
            );
            $categories[$name] = ['model' => $parent, 'subs' => []];

            foreach ($data['subcategories'] as $subName) {
                $categories[$name]['subs'][$subName] = Shopcategory::query()->updateOrCreate(
                    ['slug' => Str::slug($subName)],
                    ['name' => $subName, 'parent_id' => $parent->id, 'active' => true],
                );
            }
        }

        foreach ($this->products() as $index => $product) {
            $variations = $product['variations'] ?? [];
            [$categoryName, $subName] = $product['category'];
            unset($product['variations'], $product['category']);

            $model = ShopProduct::query()->updateOrCreate(
                ['slug' => Str::slug($product['product_name'])],
                $product + [
                    'sku' => 'TH-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'shop_category_id' => $categories[$categoryName]['model']->id,
                    'subcategory_id' => $subName ? $categories[$categoryName]['subs'][$subName]->id : null,
                    'approval_status' => 'approved',
                    'publication_status' => 'published',
                    'is_finished' => false,
                    'approved_at' => now(),
                    'product_type' => $product['product_type'] ?? 'physical',
                    'condition' => $product['condition'] ?? 'new',
                    'low_stock_threshold' => 5,
                    'reserved_quantity' => 0,
                ],
            );

            $model->variations()->delete();
            foreach ($variations as $position => [$label, $value, $price]) {
                ProductVariation::query()->create([
                    'shop_product_id' => $model->id,
                    'feature_label' => $label,
                    'variation_value' => $value,
                    'price' => $price,
                    'is_default' => $position === 0,
                ]);
            }
        }
    }

    private function products(): array
    {
        return [
            [
                'category' => ['Kids & Toys', 'Ride-on Toys'],
                'product_name' => 'Remote Controlled 4-Wheel Electric Ride-on Car for Kids 12V',
                'description' => "Big-size 4-wheel electric ride-on car with a 12V 12AH rechargeable battery and parent remote control.\nLED lights, MP3 music, double seats and swing mode. Suitable for children aged 3 to 8 years.",
                'price' => 178000, 'discount_enabled' => true, 'original_price' => 199000,
                'working_days' => 3, 'is_popular' => true,
                'tracks_inventory' => true, 'stock_quantity' => 12, 'stock_unit' => 'pieces', 'status' => 'in_stock',
                'features' => ['2.4G parent remote control', 'LED lights and MP3 music', 'Double seats with seat belt', 'Rechargeable 12V 12AH battery', 'Charger included'],
                'specifications' => [
                    ['label' => 'Age Range', 'value' => '3 to 8 years'],
                    ['label' => 'Battery', 'value' => '12V 12AH'],
                    ['label' => 'Motor', 'value' => '2 x 380 motor'],
                    ['label' => 'Material', 'value' => 'Plastic'],
                    ['label' => 'Max Load', 'value' => '35 kg'],
                    ['label' => 'Product Size', 'value' => '131 x 73 x 63 cm'],
                    ['label' => 'Gender', 'value' => 'Unisex'],
                    ['label' => 'Charging Time', 'value' => '8–10 hours'],
                ],
                'variations' => [['Color', 'Black', 178000], ['Color', 'Red', 178000], ['Color', 'White', 181500], ['Color', 'Pink', 181500]],
            ],
            [
                'category' => ['Electronics', 'Phones & Tablets'],
                'product_name' => 'Samsung Galaxy A15 Smartphone',
                'description' => '6.5-inch Super AMOLED display, 50MP triple camera and a 5000mAh battery that lasts all day. Dual SIM, Android 14.',
                'price' => 185000, 'working_days' => 1, 'is_popular' => true,
                'tracks_inventory' => true, 'stock_quantity' => 25, 'stock_unit' => 'pieces', 'status' => 'in_stock',
                'features' => ['50MP main camera', '5000mAh battery with 25W fast charging', 'Dual SIM', '12 months warranty'],
                'specifications' => [
                    ['label' => 'Display', 'value' => '6.5" Super AMOLED, 90Hz'],
                    ['label' => 'Battery', 'value' => '5000 mAh'],
                    ['label' => 'Camera', 'value' => '50MP + 5MP + 2MP'],
                    ['label' => 'Operating System', 'value' => 'Android 14'],
                    ['label' => 'Warranty', 'value' => '12 months'],
                ],
                'variations' => [['Storage', '128GB / 4GB RAM', 185000], ['Storage', '256GB / 8GB RAM', 229000]],
            ],
            [
                'category' => ['Electronics', 'Computers'],
                'product_name' => 'HP 250 G9 Laptop Core i5',
                'description' => 'Reliable 15.6-inch business laptop with an Intel Core i5 processor, fast SSD storage and a full-size keyboard with numeric keypad.',
                'price' => 720000, 'discount_enabled' => true, 'original_price' => 790000,
                'working_days' => 2, 'is_popular' => true,
                'tracks_inventory' => true, 'stock_quantity' => 8, 'stock_unit' => 'pieces', 'status' => 'in_stock',
                'features' => ['Intel Core i5 12th Gen', 'Full HD anti-glare display', 'Windows 11 Pro', 'Laptop bag included'],
                'specifications' => [
                    ['label' => 'Processor', 'value' => 'Intel Core i5-1235U'],
                    ['label' => 'Display', 'value' => '15.6" FHD (1920 x 1080)'],
                    ['label' => 'Operating System', 'value' => 'Windows 11 Pro'],
                    ['label' => 'Weight', 'value' => '1.74 kg'],
                    ['label' => 'Warranty', 'value' => '12 months'],
                ],
                'variations' => [['Configuration', '8GB RAM / 256GB SSD', 720000], ['Configuration', '16GB RAM / 512GB SSD', 845000]],
            ],
            [
                'category' => ['Electronics', 'Accessories'],
                'product_name' => 'Wireless Bluetooth Earbuds with Charging Case',
                'description' => 'True wireless earbuds with touch controls, clear calls and up to 24 hours of playback with the charging case.',
                'price' => 25000, 'working_days' => 1,
                'tracks_inventory' => true, 'stock_quantity' => 60, 'stock_unit' => 'pieces', 'status' => 'in_stock',
                'features' => ['Bluetooth 5.3', 'Touch controls', 'Water resistant (IPX5)', 'USB-C charging'],
                'specifications' => [
                    ['label' => 'Battery Life', 'value' => '6 h (24 h with case)'],
                    ['label' => 'Connectivity', 'value' => 'Bluetooth 5.3'],
                    ['label' => 'Water Resistance', 'value' => 'IPX5'],
                ],
                'variations' => [['Color', 'Black', 25000], ['Color', 'White', 25000]],
            ],
            [
                'category' => ['Electronics', 'Accessories'],
                'product_name' => '20000mAh Power Bank Fast Charging',
                'description' => 'High-capacity power bank that charges two devices at once, with a digital battery display.',
                'price' => 32000, 'discount_enabled' => true, 'original_price' => 38000, 'working_days' => 1,
                'tracks_inventory' => true, 'stock_quantity' => 3, 'stock_unit' => 'pieces', 'status' => 'in_stock',
                'features' => ['22.5W fast charging', 'USB-C and 2x USB-A', 'LED battery display'],
                'specifications' => [
                    ['label' => 'Capacity', 'value' => '20000 mAh'],
                    ['label' => 'Output', 'value' => '22.5W max'],
                    ['label' => 'Weight', 'value' => '420 g'],
                ],
            ],
            [
                'category' => ['Tools & Hardware', 'Power Tools'],
                'product_name' => 'Cordless Impact Drill 21V with 2 Batteries',
                'description' => 'Powerful cordless drill for wood, metal and masonry. Comes with two lithium batteries, a charger and a carry case.',
                'price' => 65000, 'working_days' => 2, 'is_popular' => true,
                'tracks_inventory' => true, 'stock_quantity' => 18, 'stock_unit' => 'pieces', 'status' => 'in_stock',
                'features' => ['2 x 21V lithium batteries', '25+1 torque settings', 'LED work light', 'Carry case with drill bits'],
                'specifications' => [
                    ['label' => 'Voltage', 'value' => '21V'],
                    ['label' => 'Max Torque', 'value' => '45 Nm'],
                    ['label' => 'Chuck Size', 'value' => '10 mm'],
                    ['label' => 'Speed', 'value' => '0–1500 rpm'],
                ],
            ],
            [
                'category' => ['Tools & Hardware', 'Power Tools'],
                'product_name' => 'Angle Grinder 850W',
                'description' => '115 mm angle grinder for cutting and grinding metal, tiles and stone. Includes side handle and safety guard.',
                'price' => 42000, 'working_days' => 2,
                'tracks_inventory' => true, 'stock_quantity' => 0, 'stock_unit' => 'pieces', 'status' => 'out_of_stock',
                'features' => ['850W motor', 'Adjustable safety guard', 'Spindle lock for fast disc change'],
                'specifications' => [
                    ['label' => 'Power', 'value' => '850W'],
                    ['label' => 'Disc Diameter', 'value' => '115 mm'],
                    ['label' => 'No-load Speed', 'value' => '11000 rpm'],
                ],
            ],
            [
                'category' => ['Tools & Hardware', 'Hand Tools'],
                'product_name' => '108-Piece Household Tool Set',
                'description' => 'Complete tool kit in a sturdy case: hammer, screwdrivers, pliers, sockets, tape measure and more.',
                'price' => 48000, 'discount_enabled' => true, 'original_price' => 56000, 'working_days' => 2,
                'tracks_inventory' => true, 'stock_quantity' => 22, 'stock_unit' => 'sets', 'status' => 'in_stock',
                'features' => ['108 pieces', 'Chrome vanadium steel', 'Blow-moulded carry case'],
                'specifications' => [
                    ['label' => 'Pieces', 'value' => '108'],
                    ['label' => 'Material', 'value' => 'Chrome vanadium steel'],
                ],
            ],
            [
                'category' => ['Home & Kitchen', 'Appliances'],
                'product_name' => 'Electric Kettle 1.8L Stainless Steel',
                'description' => 'Fast-boil stainless steel kettle with automatic shut-off and boil-dry protection.',
                'price' => 18000, 'working_days' => 1,
                'tracks_inventory' => true, 'stock_quantity' => 40, 'stock_unit' => 'pieces', 'status' => 'in_stock',
                'features' => ['1.8 litre capacity', 'Auto shut-off', '360° cordless base'],
                'specifications' => [
                    ['label' => 'Capacity', 'value' => '1.8 L'],
                    ['label' => 'Power', 'value' => '1500W'],
                    ['label' => 'Material', 'value' => 'Stainless steel'],
                ],
            ],
            [
                'category' => ['Home & Kitchen', 'Appliances'],
                'product_name' => 'Countertop Blender 1.5L',
                'description' => 'Blender with a shatter-resistant jug, 3 speeds and pulse for smoothies, juices and sauces.',
                'price' => 35000, 'working_days' => 2,
                'tracks_inventory' => true, 'stock_quantity' => 15, 'stock_unit' => 'pieces', 'status' => 'in_stock',
                'features' => ['3 speeds + pulse', 'Stainless steel blades', 'Includes grinder cup'],
                'specifications' => [
                    ['label' => 'Capacity', 'value' => '1.5 L'],
                    ['label' => 'Power', 'value' => '600W'],
                ],
            ],
            [
                'category' => ['Home & Kitchen', 'Cookware'],
                'product_name' => 'Non-stick Cookware Set 10 Pieces',
                'description' => 'Pots and pans with non-stick coating and glass lids, suitable for gas and electric cookers.',
                'price' => 75000, 'discount_enabled' => true, 'original_price' => 89000, 'working_days' => 3,
                'tracks_inventory' => true, 'stock_quantity' => 10, 'stock_unit' => 'sets', 'status' => 'in_stock',
                'features' => ['10 pieces with glass lids', 'PFOA-free non-stick coating', 'Heat-resistant handles'],
                'specifications' => [
                    ['label' => 'Pieces', 'value' => '10'],
                    ['label' => 'Compatible With', 'value' => 'Gas, electric, ceramic'],
                ],
            ],
            [
                'category' => ['Office Supplies', 'Furniture'],
                'product_name' => 'Ergonomic Office Chair with Lumbar Support',
                'description' => 'Breathable mesh office chair with adjustable height, armrests and lumbar support for long working hours.',
                'price' => 120000, 'working_days' => 4, 'is_popular' => true,
                'tracks_inventory' => true, 'stock_quantity' => 7, 'stock_unit' => 'pieces', 'status' => 'in_stock',
                'features' => ['Adjustable lumbar support', 'Breathable mesh back', 'Tilt lock', '360° swivel'],
                'specifications' => [
                    ['label' => 'Max Load', 'value' => '120 kg'],
                    ['label' => 'Seat Height', 'value' => '45–55 cm'],
                    ['label' => 'Material', 'value' => 'Mesh, nylon base'],
                ],
                'variations' => [['Color', 'Black', 120000], ['Color', 'Grey', 120000]],
            ],
            [
                'category' => ['Office Supplies', 'Stationery'],
                'product_name' => 'A4 Printing Paper 80gsm (Box of 5 Reams)',
                'description' => 'Bright white A4 paper for laser and inkjet printers and photocopiers. 500 sheets per ream.',
                'price' => 32500, 'working_days' => 1,
                'tracks_inventory' => true, 'stock_quantity' => 100, 'stock_unit' => 'boxes', 'status' => 'in_stock',
                'features' => ['2,500 sheets per box', 'Jam-free printing'],
                'specifications' => [
                    ['label' => 'Size', 'value' => 'A4 (210 x 297 mm)'],
                    ['label' => 'Weight', 'value' => '80 gsm'],
                    ['label' => 'Sheets', 'value' => '5 x 500'],
                ],
            ],
            [
                'category' => ['Office Supplies', null],
                'product_name' => 'Website Design Package for Small Businesses',
                'description' => 'A professional website for your business: up to 5 pages, mobile-friendly design, contact form and one year of hosting.',
                'price' => 350000, 'working_days' => 14,
                'tracks_inventory' => false, 'status' => 'in_stock', 'product_type' => 'service', 'condition' => null,
                'features' => ['Up to 5 pages', 'Mobile-friendly design', '1 year hosting and domain', 'Basic SEO setup'],
                'specifications' => [
                    ['label' => 'Delivery', 'value' => '2 weeks'],
                    ['label' => 'Revisions', 'value' => '2 rounds'],
                ],
                'variations' => [['Package', 'Starter (5 pages)', 350000], ['Package', 'Business (10 pages + blog)', 550000]],
            ],
        ];
    }
}
