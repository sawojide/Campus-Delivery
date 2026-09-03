<?php
require 'includes/db.php';

// Get all products
$stmt = $pdo->query("SELECT id, name FROM products");
$products = $stmt->fetchAll();

// Get vendor IDs by category
$vendors = [];
$stmt = $pdo->query("SELECT id, category FROM vendors");
while ($row = $stmt->fetch()) {
    $vendors[$row['category']][] = $row['id'];
}

echo "<h2>Fixing Product Categories...</h2>";
echo "<pre>";

$updateStmt = $pdo->prepare("UPDATE products SET vendor_id = ? WHERE id = ?");
$fixed = 0;

foreach ($products as $product) {
    $name = strtolower($product['name']);
    $new_vendor_id = null;
    $detected_category = '';
    
    // FOOD category keywords
    $food_keywords = ['suya', 'bbq', 'barbecue', 'jollof', 'rice', 'fried rice', 'indomie', 'noodle', 'burger', 'pizza', 'shawarma', 'yam', 'plantain', 'boli', 'egg', 'bread', 'pie', 'meat pie', 'fish pie', 'chicken pie', 'beans', 'garri', 'soup', 'stew', 'pepper soup', 'moin moin', 'akara', 'fish', 'chicken', 'beef', 'goat', 'turkey', 'pounded yam', 'amala', 'eba', 'semo', 'wheat', 'ofada', 'coconut rice', 'fast food', 'hot dog', 'meat', 'grilled', 'roasted corn', 'roasted yam', 'abacha', 'nkwobi', 'isiewu', 'kuli kuli', 'kilishi', 'tsire', 'groundnut', 'coconut', 'porridge', 'swallow', 'egusi', 'ogbono', 'efo riro', 'banga', 'vegetable'];
    
    // PROVISIONS category keywords
    $provision_keywords = ['indomie (pack', 'indomie chicken', 'indomie beef', 'indomie shrimp', 'indomie curry', 'golden mimi', 'debono', 'supa noodles', 'garri', 'rice (local', 'rice (foreign', 'rice (5kg', 'rice (10kg', 'rice (25kg', 'beans (brown', 'beans (honey', 'beans (oloyin', 'yam (small', 'yam (medium', 'yam (large', 'yam (bag', 'plantain (bunch', 'plantain (single', 'milo', 'bournvita', 'horlicks', 'ovaltine', 'sugar', 'salt', 'tomato paste', 'fresh tomatoes', 'onions', 'pepper (fresh', 'pepper (dry', 'scotch bonnet', 'vegetable oil', 'palm oil', 'eggs (crate', 'eggs (half', 'eggs (per', 'bread (loaf', 'bread (sliced', 'bread (wheat', 'bread (family', 'spaghetti', 'macaroni', 'pasta (pack', 'cornflakes', 'golden morn', 'custard'];
    
    // PERFUMES & COSMETICS keywords
    $perfume_keywords = ['dior', 'chanel', 'versace', 'armani', 'gucci', 'tom ford', 'creed', 'body spray', 'deodorant', 'perfume oil', 'lipstick', 'lip gloss', 'lip balm', 'foundation', 'powder', 'concealer', 'blush', 'mascara', 'eye shadow', 'eyeliner', 'eye pencil', 'face cream', 'body lotion', 'body cream', 'shea butter', 'soap (lux', 'soap (dettol', 'soap (imperial', 'soap (pearl', 'body wash', 'shower gel'];
    
    // SNACKS & DRINKS keywords
    $snack_keywords = ['coca cola', 'coke', 'fanta', 'sprite', 'malt', 'water (small', 'water (medium', 'water (large', 'juice (maltina', 'juice (la casera', 'juice (chivita', 'juice (five', 'energy drink', 'red bull', 'monster', 'power horse', 'chips (lays', 'chips (doritos', 'chips (pringles', 'chips (local', 'biscuits (oreo', 'biscuits (tuc', 'biscuits (jacob', 'biscuits (digestive', 'biscuits (cream', 'chocolate (cadbury', 'chocolate (dairy', 'chocolate (kitkat', 'chocolate (snickers', 'chocolate (twix', 'chocolate (m&m', 'groundnut (pack', 'cashew', 'almonds', 'mixed nuts', 'popcorn', 'cheese balls', 'meat snacks'];
    
    // TOILETRIES keywords
    $toiletry_keywords = ['toothpaste', 'toothbrush', 'mouthwash', 'dental floss', 'shampoo', 'conditioner', 'hair cream', 'hair gel', 'pomade', 'soap (antibacterial', 'soap (medicated', 'hand sanitizer', 'hand wash', 'razor', 'shaving cream', 'aftershave', 'tissue', 'toilet paper', 'paper towels', 'napkins', 'detergent', 'liquid soap', 'disinfectant', 'air freshener', 'mosquito repellent', 'insecticide'];
    
    // ELECTRONICS keywords
    $electronic_keywords = ['phone charger', 'power bank', 'usb cable', 'earphone', 'headphone', 'bluetooth speaker', 'flash drive', 'memory card', 'phone case', 'screen protector', 'phone stand', 'laptop stand', 'extension box', 'bulb', 'torchlight', 'lantern'];
    
    // BOOKS & STATIONERY keywords
    $book_keywords = ['exercise book', 'hardcover book', 'diary', 'biro', 'pen (gel', 'pen (highlighter', 'pencil', 'pencil sharpener', 'eraser', 'ruler', 'calculator', 'stapler', 'stapler pins', 'paper clips', 'binder clips', 'glue stick', 'cello tape', 'scissors', 'a4 paper', 'file folder', 'ring binder'];
    
    // Check each category
    foreach ($food_keywords as $keyword) {
        if (strpos($name, $keyword) !== false) {
            $detected_category = 'Food';
            break;
        }
    }
    
    if (empty($detected_category)) {
        foreach ($provision_keywords as $keyword) {
            if (strpos($name, $keyword) !== false) {
                $detected_category = 'Provisions';
                break;
            }
        }
    }
    
    if (empty($detected_category)) {
        foreach ($perfume_keywords as $keyword) {
            if (strpos($name, $keyword) !== false) {
                $detected_category = 'Perfumes';
                break;
            }
        }
    }
    
    if (empty($detected_category)) {
        foreach ($snack_keywords as $keyword) {
            if (strpos($name, $keyword) !== false) {
                $detected_category = 'Snacks';
                break;
            }
        }
    }
    
    if (empty($detected_category)) {
        foreach ($toiletry_keywords as $keyword) {
            if (strpos($name, $keyword) !== false) {
                $detected_category = 'Provisions'; // Toiletries go under provisions
                break;
            }
        }
    }
    
    if (empty($detected_category)) {
        foreach ($electronic_keywords as $keyword) {
            if (strpos($name, $keyword) !== false) {
                $detected_category = 'Electronics';
                break;
            }
        }
    }
    
    if (empty($detected_category)) {
        foreach ($book_keywords as $keyword) {
            if (strpos($name, $keyword) !== false) {
                $detected_category = 'Books';
                break;
            }
        }
    }
    
    // Assign to a random vendor in that category
    if (!empty($detected_category) && isset($vendors[$detected_category])) {
        $vendor_ids_in_category = $vendors[$detected_category];
        $new_vendor_id = $vendor_ids_in_category[array_rand($vendor_ids_in_category)];
        $updateStmt->execute([$new_vendor_id, $product['id']]);
        $fixed++;
        echo "✅ [{$product['id']}] {$product['name']} → {$detected_category}\n";
    } else {
        echo "⚠️ [{$product['id']}] {$product['name']} → No category matched\n";
    }
}

echo "</pre>";
echo "<h3 style='color:green;'>Fixed {$fixed} products!</h3>";
echo "<p><a href='browse.php'>Go to Browse Page</a></p>";
?>