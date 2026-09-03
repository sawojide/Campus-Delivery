<?php
require 'includes/db.php';

// This function maps product names to specific, accurate high-quality images
function getSmartImage($productName) {
    $name = strtolower($productName);
    
    // 1. FOOD & MEALS
    if (strpos($name, 'suya') !== false) return 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600'; // BBQ/Grill
    if (strpos($name, 'jollof') !== false) return 'https://images.unsplash.com/photo-1596797038530-2c107229654b?w=600'; // Jollof Rice
    if (strpos($name, 'fried rice') !== false) return 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600'; // Fried Rice
    if (strpos($name, 'indomie') !== false || strpos($name, 'noodle') !== false) return 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=600'; // Noodles
    if (strpos($name, 'bbq') !== false || strpos($name, 'barbecue') !== false) return 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?w=600'; // BBQ
    if (strpos($name, 'burger') !== false) return 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600'; // Burger
    if (strpos($name, 'pizza') !== false) return 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600'; // Pizza
    if (strpos($name, 'shawarma') !== false) return 'https://images.unsplash.com/photo-1633321702518-7feccafb94d5?w=600'; // Shawarma
    if (strpos($name, 'yam') !== false) return 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600'; // Yam
    if (strpos($name, 'plantain') !== false || strpos($name, 'boli') !== false) return 'https://images.unsplash.com/photo-1603569283847-aa295f0d016a?w=600'; // Plantain
    if (strpos($name, 'egg') !== false) return 'https://images.unsplash.com/photo-1587486936739-7b421984a61e?w=600'; // Eggs
    if (strpos($name, 'bread') !== false) return 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600'; // Bread
    if (strpos($name, 'meat pie') !== false || strpos($name, 'pie') !== false) return 'https://images.unsplash.com/photo-1572383672419-ab35444a6541?w=600'; // Pie
    if (strpos($name, 'rice') !== false) return 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600'; // Generic Rice
    if (strpos($name, 'beans') !== false) return 'https://images.unsplash.com/photo-1515543904379-3d757afe72e3?w=600'; // Beans
    if (strpos($name, 'garri') !== false) return 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600'; // Garri (Cassava)
    if (strpos($name, 'soup') !== false || strpos($name, 'stew') !== false) return 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600'; // Soup/Stew
    if (strpos($name, 'pepper soup') !== false) return 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600'; // Pepper soup
    if (strpos($name, 'moin moin') !== false || strpos($name, 'akara') !== false) return 'https://images.unsplash.com/photo-1596560548464-f010549b84d7?w=600'; // Moin Moin
    if (strpos($name, 'fish') !== false) return 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600'; // Fish
    if (strpos($name, 'chicken') !== false) return 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600'; // Chicken
    if (strpos($name, 'beef') !== false || strpos($name, 'meat') !== false) return 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600'; // Beef

    // 2. DRINKS & SNACKS
    if (strpos($name, 'coke') !== false || strpos($name, 'coca cola') !== false) return 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=600'; // Coke
    if (strpos($name, 'fanta') !== false || strpos($name, 'sprite') !== false) return 'https://images.unsplash.com/photo-1625772299848-391b6a87d7b3?w=600'; // Fanta/Sprite
    if (strpos($name, 'water') !== false) return 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=600'; // Water
    if (strpos($name, 'malt') !== false) return 'https://images.unsplash.com/photo-1563227812-0ea4c22e6cc8?w=600'; // Malt
    if (strpos($name, 'juice') !== false) return 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=600'; // Juice
    if (strpos($name, 'chips') !== false || strpos($name, 'crisps') !== false) return 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?w=600'; // Chips
    if (strpos($name, 'chocolate') !== false) return 'https://images.unsplash.com/photo-1606312619070-d48b4c652a52?w=600'; // Chocolate
    if (strpos($name, 'biscuit') !== false || strpos($name, 'cracker') !== false) return 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=600'; // Biscuits
    if (strpos($name, 'popcorn') !== false) return 'https://images.unsplash.com/photo-1578849278619-e73505e9610f?w=600'; // Popcorn

    // 3. PERFUMES & COSMETICS
    if (strpos($name, 'perfume') !== false || strpos($name, 'fragrance') !== false) return 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=600'; // Perfume
    if (strpos($name, 'lipstick') !== false || strpos($name, 'lip') !== false) return 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=600'; // Lipstick
    if (strpos($name, 'foundation') !== false || strpos($name, 'makeup') !== false) return 'https://images.unsplash.com/photo-1596462502278-27bfdd403348?w=600'; // Makeup
    if (strpos($name, 'soap') !== false || strpos($name, 'shower') !== false) return 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600'; // Soap
    if (strpos($name, 'lotion') !== false || strpos($name, 'cream') !== false) return 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600'; // Lotion
    if (strpos($name, 'deodorant') !== false || strpos($name, 'spray') !== false) return 'https://images.unsplash.com/photo-1608248597279-f99d160bfbc8?w=600'; // Deodorant

    // 4. TOILETRIES & PROVISIONS
    if (strpos($name, 'toothpaste') !== false || strpos($name, 'toothbrush') !== false) return 'https://images.unsplash.com/photo-1559650656-5d1d361ad10e?w=600'; // Dental
    if (strpos($name, 'shampoo') !== false || strpos($name, 'hair') !== false) return 'https://images.unsplash.com/photo-1526947425960-945c6e72858f?w=600'; // Haircare
    if (strpos($name, 'detergent') !== false || strpos($name, 'soap') !== false) return 'https://images.unsplash.com/photo-1583947215259-38e31be8751f?w=600'; // Detergent
    if (strpos($name, 'oil') !== false) return 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600'; // Oil
    if (strpos($name, 'tomato') !== false) return 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=600'; // Tomato
    if (strpos($name, 'pepper') !== false) return 'https://images.unsplash.com/photo-1583119022894-919a68a3d0e3?w=600'; // Pepper
    if (strpos($name, 'sugar') !== false || strpos($name, 'salt') !== false) return 'https://images.unsplash.com/photo-1581441363688-d62f121433bf?w=600'; // Sugar/Salt

    // 5. ELECTRONICS & GADGETS
    if (strpos($name, 'phone') !== false || strpos($name, 'iphone') !== false || strpos($name, 'android') !== false) return 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600'; // Phone
    if (strpos($name, 'laptop') !== false || strpos($name, 'computer') !== false) return 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600'; // Laptop
    if (strpos($name, 'charger') !== false || strpos($name, 'power bank') !== false) return 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=600'; // Charger
    if (strpos($name, 'earphone') !== false || strpos($name, 'headphone') !== false) return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600'; // Headphones
    if (strpos($name, 'speaker') !== false) return 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600'; // Speaker
    if (strpos($name, 'flash drive') !== false || strpos($name, 'memory') !== false) return 'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=600'; // USB

    // 6. BOOKS & STATIONERY
    if (strpos($name, 'book') !== false || strpos($name, 'notebook') !== false || strpos($name, 'exercise') !== false) return 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=600'; // Books
    if (strpos($name, 'pen') !== false || strpos($name, 'biro') !== false || strpos($name, 'pencil') !== false) return 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=600'; // Pens
    if (strpos($name, 'calculator') !== false) return 'https://images.unsplash.com/photo-1587145820266-a5951ee9683e?w=600'; // Calculator
    if (strpos($name, 'paper') !== false) return 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=600'; // Paper

    // FALLBACK: If no specific keyword matches, generate a clean, branded placeholder with the product name
    $safeName = urlencode(str_replace(' ', '+', $productName));
    return "https://placehold.co/600x400/dc3545/ffffff?text={$safeName}";
}

// --- EXECUTE THE UPDATE ---
try {
    // 1. Fetch all products
    $stmt = $pdo->query("SELECT id, name FROM products");
    $products = $stmt->fetchAll();
    
    $updateStmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?");
    $updatedCount = 0;

    // 2. Loop and update
    foreach ($products as $product) {
        $newImage = getSmartImage($product['name']);
        $updateStmt->execute([$newImage, $product['id']]);
        $updatedCount++;
    }

    echo "<div style='text-align:center; margin-top:50px; font-family:Arial; padding: 40px;'>";
    echo "<h1 style='color:green;'> Images Updated Successfully!</h1>";
    echo "<h3>Updated <strong>" . number_format($updatedCount) . "</strong> products with specific images.</h3>";
    echo "<p style='font-size:18px; margin:20px;'>Go check your browse page now. It will look completely different!</p>";
    echo "<a href='browse.php' style='display:inline-block; padding:15px 30px; background:#dc3545; color:white; text-decoration:none; border-radius:5px; font-size:18px;'>View Updated Products</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>