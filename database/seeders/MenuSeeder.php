<?php
 
namespace Database\Seeders;
 
use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
 
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Categories
        $categories = [
            ['name' => 'Meals', 'icon' => '🍚'],
            ['name' => 'Drinks', 'icon' => '🥤'],
            ['name' => 'Snacks', 'icon' => '🍪'],
            ['name' => 'Desserts', 'icon' => '🍦'],
        ];
 
        foreach ($categories as $cat) {
            $createdCat = Category::create($cat);
 
            // ======================== CATEGORY: MEALS ========================
            if ($cat['name'] === 'Meals') {
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Beef Pares Special',
                    'description' => 'Signature slow-cooked beef brisket in sweet soy anise gravy. Served with hot garlic fried rice, hard-boiled egg, toasted garlic, and warm beef consommé soup.',
                    'price' => 110.00,
                    'image_url' => 'https://images.unsplash.com/photo-1512058560366-cd24d77ce80c?q=80&w=500', 
                    'is_available' => true,
                    'is_popular' => true
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Classic Pork Sisig Rice',
                    'description' => 'Sizzling, crispy minced pork face, ears, and chicken liver seasoned with native calamansi, red onions, and wild chilies. Topped with a fresh farm egg on garlic rice.',
                    'price' => 125.00,
                    'image_url' => 'https://images.unsplash.com/photo-1626132647523-66f5bf380027?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => true
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Savory Chicken Adobo',
                    'description' => 'Slow-braised tender chicken thighs in a balanced, rich glaze of naturally fermented soy sauce, sugar cane vinegar, crushed black peppercorns, garlic, and bay leaves.',
                    'price' => 95.00,
                    'image_url' => 'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => false
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Salisbury Burger Steak',
                    'description' => 'Thick, juicy pan-seared beef patty smothered in rich, velvety mushroom gravy. Topped with toasted button mushrooms and served with garlic rice.',
                    'price' => 105.00,
                    'image_url' => 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => true
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Crispy Lechon Kawali Rice',
                    'description' => 'Deep-fried, golden-brown pork belly slab with super crispy skin and juicy fat layers. Served with native vinegar-soy dip and sweet-savory sarsa.',
                    'price' => 135.00,
                    'image_url' => 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => false
                ]);
            }
 
            // ======================== CATEGORY: DRINKS ========================
            if ($cat['name'] === 'Drinks') {
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Buko Pandan Cooler',
                    'description' => 'Chilled fresh young coconut water with sweet pandan-infused jelly strips, soft coconut meat, and creamy milk.',
                    'price' => 45.00,
                    'image_url' => 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => true
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'KainTAU Pearl Milk Tea',
                    'description' => 'Premium black tea leaves statefully brewed, sweetened, blended with creamy milk, and topped with chewy brown sugar tapioca pearls.',
                    'price' => 75.00,
                    'image_url' => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => true
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Iced Matcha Latte',
                    'description' => 'High-grade pure Japanese green tea matcha whisked with fresh whole milk, served over crushed ice with a light caramel drizzle.',
                    'price' => 85.00,
                    'image_url' => 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => false
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Philippine Mango Smoothie',
                    'description' => 'Velvety blend of ripe, sweet Guimaras yellow mangoes, shaved ice, and milk, capped with whipped cream and mango chunks.',
                    'price' => 65.00,
                    'image_url' => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => true
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Chilled Sagada Iced Coffee',
                    'description' => 'Double shot of premium locally sourced Sagada arabica espresso diluted with purified cold water and served over ice.',
                    'price' => 60.00,
                    'image_url' => 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => false
                ]);
            }
 
            // ======================== CATEGORY: SNACKS ========================
            if ($cat['name'] === 'Snacks') {
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Cheesy Fries Supreme',
                    'description' => 'Bucket of golden, crispy thin-cut potatoes heavily dusted with cheddar cheese powder and drizzled with warm cheese sauce.',
                    'price' => 50.00,
                    'image_url' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => true
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Crispy Pork Lumpia Shanghai',
                    'description' => 'Six pieces of gold-fried mini spring rolls stuffed with seasoned ground pork, carrots, and sweet onions. Served with sweet chili dipping sauce.',
                    'price' => 60.00,
                    'image_url' => 'https://images.unsplash.com/photo-1544025162-d76694265947?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => true
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Toasted Chicken Club Sandwich',
                    'description' => 'Double-decker toasted bread layers stuffed with sliced chicken breast, smoked bacon, egg, lettuce, tomato, and garlic aioli.',
                    'price' => 85.00,
                    'image_url' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => false
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Baked Cheesy Macaroni',
                    'description' => 'Elbow macaroni tossed in sweet-style meat sauce, topped with creamy béchamel, and baked to a melted golden cheese crust.',
                    'price' => 70.00,
                    'image_url' => 'https://images.unsplash.com/photo-1543339308-43e59d6b73a6?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => false
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Loaded Beef Nacho Platter',
                    'description' => 'Crispy tortilla chips piled with seasoned ground beef, warm cheese sauce, fresh diced tomatoes, red onions, and jalapeños.',
                    'price' => 95.00,
                    'image_url' => 'https://images.unsplash.com/photo-1513456852971-30c0b8199d4d?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => true
                ]);
            }
 
            // ======================== CATEGORY: DESSERTS ========================
            if ($cat['name'] === 'Desserts') {
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Halo-Halo Supreme',
                    'description' => 'Iconic Filipino dessert layered with sweet beans, ube halaya, leche flan, nata de coco, macapuno, shaved milk-ice, and topped with Ube Ice Cream.',
                    'price' => 85.00,
                    'image_url' => 'https://images.unsplash.com/photo-1505394033-4442a2865d0f?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => true
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Creamy Leche Flan Custard',
                    'description' => 'Velvety, rich, and perfectly smooth egg custard steamed to perfection, bathed in deep, sweet amber caramel syrup.',
                    'price' => 50.00,
                    'image_url' => 'https://images.unsplash.com/photo-1511018556340-d16986a1c194?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => true
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Moist Ube Macapuno Cake',
                    'description' => 'Soft sponge cake layers infused with natural purple yam (ube), filled with sweetened macapuno coconut strips and covered in whipped ube frosting.',
                    'price' => 75.00,
                    'image_url' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => false
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Triple-Chocolate Brownie à la Mode',
                    'description' => 'Dense, chewy, warm brownie slice topped with a scoop of premium vanilla bean ice cream and hot fudge chocolate syrup.',
                    'price' => 65.00,
                    'image_url' => 'https://images.unsplash.com/photo-1564355808539-22fda35bed7e?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => false
                ]);
 
                MenuItem::create([
                    'category_id' => $createdCat->id,
                    'name' => 'Crispy Cheesy Banana Turon',
                    'description' => 'Three pieces of crispy lumpia roll-wrapped sweet saba banana and melting cheddar cheese, fried in caramelized brown sugar.',
                    'price' => 45.00,
                    'image_url' => 'https://images.unsplash.com/photo-1551024601-bec78aea704b?q=80&w=500',
                    'is_available' => true,
                    'is_popular' => true
                ]);
            }
        }
    }
}
