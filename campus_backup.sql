-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               10.4.32-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.21.0.7344
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for campus_app
CREATE DATABASE IF NOT EXISTS `campus_app` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `campus_app`;

-- Dumping structure for table campus_app.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'bi-box',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.categories: ~8 rows (approximately)
DELETE FROM `categories`;
INSERT INTO `categories` (`id`, `name`, `description`, `icon`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 'Food & Meals', 'Hot meals, suya, BBQ, rice, etc.', 'bi-cup-hot', 1, '2026-08-24 09:56:40', '2026-08-24 09:56:40'),
	(2, 'Perfumes & Cosmetics', 'Perfumes, makeup, beauty products', 'bi-droplet', 1, '2026-08-24 09:56:40', '2026-08-24 09:56:40'),
	(3, 'Provisions', 'Food items, groceries, ingredients', 'bi-box-seam', 1, '2026-08-24 09:56:40', '2026-08-24 09:56:40'),
	(4, 'Snacks & Drinks', 'Chips, biscuits, soft drinks, water', 'bi-cup-straw', 1, '2026-08-24 09:56:40', '2026-08-24 09:56:40'),
	(5, 'Electronics', 'Phones, chargers, gadgets, accessories', 'bi-phone', 1, '2026-08-24 09:56:40', '2026-08-24 09:56:40'),
	(6, 'Books & Stationery', 'Notebooks, pens, calculators, paper', 'bi-book', 1, '2026-08-24 09:56:40', '2026-08-24 09:56:40'),
	(7, 'Health & Beauty', 'Soaps, lotions, toiletries', 'bi-capsule', 1, '2026-08-24 09:56:40', '2026-08-24 09:56:40'),
	(8, 'Sports & Fitness', 'Sports equipment, gym accessories', 'bi-activity', 1, '2026-08-24 09:56:40', '2026-08-24 09:56:40');

-- Dumping structure for table campus_app.notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order','payment','delivery','system') DEFAULT 'order',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.notifications: ~0 rows (approximately)
DELETE FROM `notifications`;

-- Dumping structure for table campus_app.order_items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.order_items: ~0 rows (approximately)
DELETE FROM `order_items`;
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
	(1, 1, 337, 1, 500.00);

-- Dumping structure for table campus_app.orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `rider_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 100.00,
  `promo_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_method` enum('wallet','paystack') DEFAULT 'wallet',
  `paid_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','preparing','ready','delivering','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `vendor_id` (`vendor_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.orders: ~0 rows (approximately)
DELETE FROM `orders`;
INSERT INTO `orders` (`id`, `user_id`, `vendor_id`, `rider_id`, `total_amount`, `delivery_fee`, `promo_code`, `discount_amount`, `payment_reference`, `payment_method`, `paid_at`, `status`, `created_at`) VALUES
	(1, 1, 1, 2, 500.00, 100.00, NULL, 0.00, NULL, 'wallet', NULL, 'completed', '2026-08-24 10:23:54');

-- Dumping structure for table campus_app.products
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 100,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=428 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.products: ~427 rows (approximately)
DELETE FROM `products`;
INSERT INTO `products` (`id`, `vendor_id`, `category_id`, `name`, `description`, `price`, `image_url`, `stock`, `is_available`, `created_at`) VALUES
	(1, 5, 1, 'Beef Suya (10 sticks)', 'Spicy grilled beef with extra yaji', 2500.00, 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600', 100, 1, '2026-08-23 22:57:54'),
	(2, 3, 1, 'Chicken Suya (10 sticks)', 'Tender grilled chicken suya', 3000.00, 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600', 80, 1, '2026-08-23 22:57:54'),
	(3, 5, 1, 'Goat Meat Suya (10 sticks)', 'Delicious goat meat suya', 3500.00, 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600', 60, 1, '2026-08-23 22:57:54'),
	(4, 4, 1, 'Turkey Suya (10 sticks)', 'Premium turkey suya', 4000.00, 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600', 50, 1, '2026-08-23 22:57:54'),
	(5, 1, 1, 'Fish Suya (5 pieces)', 'Grilled fish with spices', 2000.00, 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600', 70, 1, '2026-08-23 22:57:54'),
	(6, 2, 1, 'Mixed Suya Platter', 'Combination of beef, chicken and goat', 5000.00, 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600', 40, 1, '2026-08-23 22:57:54'),
	(7, 2, NULL, 'Extra Yaji (Pepper)', 'Additional spicy suya pepper', 200.00, 'https://images.unsplash.com/photo-1583119022894-919a68a3d0e3?w=600', 500, 1, '2026-08-23 22:57:54'),
	(8, 1, 1, 'Suya Wrap', 'Suya in soft flatbread', 1500.00, 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600', 100, 1, '2026-08-23 22:57:54'),
	(9, 5, 1, 'BBQ Chicken (Full)', 'Whole grilled chicken with BBQ sauce', 4500.00, 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?w=600', 60, 1, '2026-08-23 22:57:54'),
	(10, 4, 1, 'BBQ Chicken (Half)', 'Half grilled chicken', 2500.00, 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?w=600', 80, 1, '2026-08-23 22:57:54'),
	(11, 2, 1, 'BBQ Wings (6 pieces)', 'Spicy BBQ chicken wings', 2000.00, 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?w=600', 100, 1, '2026-08-23 22:57:54'),
	(12, 3, 1, 'BBQ Ribs', 'Tender BBQ pork ribs', 3500.00, 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?w=600', 50, 1, '2026-08-23 22:57:54'),
	(13, 1, 1, 'BBQ Fish', 'Grilled fish with BBQ glaze', 3000.00, 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?w=600', 70, 1, '2026-08-23 22:57:54'),
	(14, 5, 1, 'BBQ Sausage', 'Grilled sausage with spices', 1500.00, 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?w=600', 120, 1, '2026-08-23 22:57:54'),
	(15, 1, 1, 'Jollof Rice (Small)', 'Party jollof rice small portion', 800.00, 'https://images.unsplash.com/photo-1596797038530-2c107229654b?w=600', 200, 1, '2026-08-23 22:57:54'),
	(16, 4, 1, 'Jollof Rice (Medium)', 'Party jollof rice medium portion', 1200.00, 'https://images.unsplash.com/photo-1596797038530-2c107229654b?w=600', 200, 1, '2026-08-23 22:57:54'),
	(17, 3, 1, 'Jollof Rice (Large)', 'Party jollof rice large portion', 1800.00, 'https://images.unsplash.com/photo-1596797038530-2c107229654b?w=600', 200, 1, '2026-08-23 22:57:54'),
	(18, 2, 1, 'Fried Rice (Small)', 'Chinese fried rice small', 1000.00, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600', 150, 1, '2026-08-23 22:57:54'),
	(19, 5, 1, 'Fried Rice (Medium)', 'Chinese fried rice medium', 1500.00, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600', 150, 1, '2026-08-23 22:57:54'),
	(20, 3, 1, 'Fried Rice (Large)', 'Chinese fried rice large', 2200.00, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600', 150, 1, '2026-08-23 22:57:54'),
	(21, 2, 1, 'Coconut Rice', 'Special coconut rice', 2000.00, 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600', 100, 1, '2026-08-23 22:57:54'),
	(22, 3, 1, 'Ofada Rice', 'Traditional ofada rice with sauce', 2500.00, 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600', 80, 1, '2026-08-23 22:57:54'),
	(23, 3, 1, 'White Rice & Stew', 'Plain rice with tomato stew', 1500.00, 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600', 120, 1, '2026-08-23 22:57:54'),
	(24, 2, NULL, 'Pounded Yam & Egusi', 'Smooth pounded yam with egusi soup', 2000.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 100, 1, '2026-08-23 22:57:54'),
	(25, 5, NULL, 'Pounded Yam & Ogbono', 'Pounded yam with ogbono soup', 2000.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 100, 1, '2026-08-23 22:57:54'),
	(26, 5, NULL, 'Pounded Yam & Efo Riro', 'Pounded yam with vegetable soup', 2200.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 90, 1, '2026-08-23 22:57:54'),
	(27, 2, NULL, 'Amala & Ewedu', 'Yam flour swallow with ewedu', 1800.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Amala%2B%26%2BEwedu', 100, 1, '2026-08-23 22:57:54'),
	(28, 3, NULL, 'Amala & Gbegiri', 'Amala with bean soup', 1800.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Amala%2B%26%2BGbegiri', 100, 1, '2026-08-23 22:57:54'),
	(29, 2, NULL, 'Eba & Egusi', 'Garri swallow with egusi', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Eba%2B%26%2BEgusi', 120, 1, '2026-08-23 22:57:54'),
	(30, 4, NULL, 'Eba & Ogbono', 'Garri with ogbono soup', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Eba%2B%26%2BOgbono', 120, 1, '2026-08-23 22:57:54'),
	(31, 3, NULL, 'Semovita & Soup', 'Semovita with choice of soup', 1600.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600', 110, 1, '2026-08-23 22:57:54'),
	(32, 2, NULL, 'Wheat & Soup', 'Wheat swallow with soup', 1700.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600', 100, 1, '2026-08-23 22:57:54'),
	(33, 3, 3, 'Indomie (Special)', 'Indomie with egg and sausage', 800.00, 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=600', 300, 1, '2026-08-23 22:57:54'),
	(34, 1, 3, 'Indomie (Regular)', 'Plain indomie noodles', 400.00, 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=600', 400, 1, '2026-08-23 22:57:54'),
	(35, 2, NULL, 'Burger (Beef)', 'Beef burger with fries', 1500.00, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600', 150, 1, '2026-08-23 22:57:54'),
	(36, 1, NULL, 'Burger (Chicken)', 'Chicken burger with fries', 1600.00, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600', 150, 1, '2026-08-23 22:57:54'),
	(37, 2, NULL, 'Burger (Fish)', 'Fish burger with fries', 1400.00, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600', 120, 1, '2026-08-23 22:57:54'),
	(38, 5, NULL, 'Hot Dog', 'Sausage in bun with toppings', 800.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Hot%2BDog', 200, 1, '2026-08-23 22:57:54'),
	(39, 3, NULL, 'Shawarma (Chicken)', 'Chicken shawarma wrap', 1200.00, 'https://images.unsplash.com/photo-1633321702518-7feccafb94d5?w=600', 180, 1, '2026-08-23 22:57:54'),
	(40, 1, NULL, 'Shawarma (Beef)', 'Beef shawarma wrap', 1400.00, 'https://images.unsplash.com/photo-1633321702518-7feccafb94d5?w=600', 150, 1, '2026-08-23 22:57:54'),
	(41, 4, NULL, 'Pizza (Small)', 'Small pizza (6 slices)', 2500.00, 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600', 80, 1, '2026-08-23 22:57:54'),
	(42, 4, NULL, 'Pizza (Medium)', 'Medium pizza (8 slices)', 3500.00, 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600', 80, 1, '2026-08-23 22:57:54'),
	(43, 4, NULL, 'Pizza (Large)', 'Large pizza (12 slices)', 5000.00, 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600', 60, 1, '2026-08-23 22:57:54'),
	(44, 1, NULL, 'Meat Pie', 'Savory meat pie', 500.00, 'https://images.unsplash.com/photo-1572383672419-ab35444a6541?w=600', 250, 1, '2026-08-23 22:57:54'),
	(45, 1, NULL, 'Fish Pie', 'Fish-filled pie', 600.00, 'https://images.unsplash.com/photo-1572383672419-ab35444a6541?w=600', 200, 1, '2026-08-23 22:57:54'),
	(46, 3, NULL, 'Chicken Pie', 'Chicken pie', 700.00, 'https://images.unsplash.com/photo-1572383672419-ab35444a6541?w=600', 200, 1, '2026-08-23 22:57:54'),
	(47, 4, NULL, 'Sausage Roll', 'Flaky sausage roll', 400.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Sausage%2BRoll', 300, 1, '2026-08-23 22:57:54'),
	(48, 3, NULL, 'Spring Rolls (3pcs)', 'Crispy spring rolls', 600.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Spring%2BRolls%2B%283pcs%29', 200, 1, '2026-08-23 22:57:54'),
	(49, 4, NULL, 'Moin Moin', 'Steamed bean pudding', 400.00, 'https://images.unsplash.com/photo-1596560548464-f010549b84d7?w=600', 250, 1, '2026-08-23 22:57:54'),
	(50, 4, NULL, 'Akara (5 pieces)', 'Bean cakes', 300.00, 'https://images.unsplash.com/photo-1572383672419-ab35444a6541?w=600', 300, 1, '2026-08-23 22:57:54'),
	(51, 3, NULL, 'Egusi Soup', 'Melon seed soup', 1500.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600', 100, 1, '2026-08-23 22:57:54'),
	(52, 2, NULL, 'Ogbono Soup', 'African mango soup', 1500.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600', 100, 1, '2026-08-23 22:57:54'),
	(53, 1, NULL, 'Efo Riro', 'Vegetable soup', 1600.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Efo%2BRiro', 90, 1, '2026-08-23 22:57:54'),
	(54, 3, NULL, 'Banga Soup', 'Palm nut soup', 1800.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600', 80, 1, '2026-08-23 22:57:54'),
	(55, 3, NULL, 'Pepper Soup (Goat)', 'Spicy goat meat pepper soup', 2000.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600', 100, 1, '2026-08-23 22:57:54'),
	(56, 4, NULL, 'Pepper Soup (Fish)', 'Fish pepper soup', 1800.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600', 100, 1, '2026-08-23 22:57:54'),
	(57, 3, NULL, 'Pepper Soup (Chicken)', 'Chicken pepper soup', 1600.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600', 120, 1, '2026-08-23 22:57:54'),
	(58, 4, NULL, 'Catfish Pepper Soup', 'Fresh catfish pepper soup', 2500.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600', 80, 1, '2026-08-23 22:57:54'),
	(59, 2, NULL, 'Tomato Stew', 'Rich tomato stew', 1000.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600', 150, 1, '2026-08-23 22:57:54'),
	(60, 2, NULL, 'Palm Oil Stew', 'Traditional palm oil stew', 1200.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600', 120, 1, '2026-08-23 22:57:54'),
	(61, 2, NULL, 'Yam Porridge', 'Soft yam pottage', 1200.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 100, 1, '2026-08-23 22:57:54'),
	(62, 1, NULL, 'Yam & Egg Sauce', 'Boiled yam with egg sauce', 1000.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 120, 1, '2026-08-23 22:57:54'),
	(63, 1, NULL, 'Yam & Stew', 'Boiled yam with tomato stew', 1000.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 120, 1, '2026-08-23 22:57:54'),
	(64, 2, NULL, 'Plantain Porridge', 'Unripe plantain pottage', 1000.00, 'https://images.unsplash.com/photo-1603569283847-aa295f0d016a?w=600', 100, 1, '2026-08-23 22:57:54'),
	(65, 5, NULL, 'Fried Plantain', 'Ripe plantain slices (5pcs)', 500.00, 'https://images.unsplash.com/photo-1603569283847-aa295f0d016a?w=600', 200, 1, '2026-08-23 22:57:54'),
	(66, 4, NULL, 'Boiled Plantain', 'Boiled unripe plantain', 400.00, 'https://images.unsplash.com/photo-1603569283847-aa295f0d016a?w=600', 150, 1, '2026-08-23 22:57:54'),
	(67, 4, NULL, 'Boiled Yam', 'Boiled yam tubers', 600.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 150, 1, '2026-08-23 22:57:54'),
	(68, 1, NULL, 'Boiled Corn', 'Boiled corn cobs', 300.00, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600', 200, 1, '2026-08-23 22:57:54'),
	(69, 3, NULL, 'Roasted Corn', 'Roasted corn cobs', 400.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Roasted%2BCorn', 200, 1, '2026-08-23 22:57:54'),
	(70, 4, NULL, 'Roasted Plantain', 'Roasted plantain (boli)', 300.00, 'https://images.unsplash.com/photo-1603569283847-aa295f0d016a?w=600', 250, 1, '2026-08-23 22:57:54'),
	(71, 5, NULL, 'Roasted Yam', 'Roasted yam slices', 500.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 150, 1, '2026-08-23 22:57:54'),
	(72, 4, NULL, 'Abacha (African Salad)', 'Cassava flakes salad', 1000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Abacha%2B%28African%2BSalad%29', 100, 1, '2026-08-23 22:57:54'),
	(73, 4, NULL, 'Nkwobi', 'Spicy cow foot', 2500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Nkwobi', 60, 1, '2026-08-23 22:57:54'),
	(74, 5, NULL, 'Isiewu (Spicy)', 'Spicy cow foot', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Isiewu%2B%28Spicy%29', 100, 1, '2026-08-23 22:57:54'),
	(75, 5, NULL, 'Isiewu (Mild)', 'Mild cow foot', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Isiewu%2B%28Mild%29', 100, 1, '2026-08-23 22:57:54'),
	(76, 4, NULL, 'Kuli Kuli', 'Groundnut cake', 200.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Kuli%2BKuli', 300, 1, '2026-08-23 22:57:54'),
	(77, 3, NULL, 'Kilishi', 'Dried beef jerky', 1000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Kilishi', 150, 1, '2026-08-23 22:57:54'),
	(78, 2, 1, 'Tsire (Suya Spice)', 'Suya spice mix', 300.00, 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600', 400, 1, '2026-08-23 22:57:54'),
	(79, 5, NULL, 'Groundnut (Roasted)', 'Roasted peanuts', 300.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Groundnut%2B%28Roasted%29', 300, 1, '2026-08-23 22:57:54'),
	(80, 4, NULL, 'Coconut (Fresh)', 'Fresh coconut', 500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Coconut%2B%28Fresh%29', 100, 1, '2026-08-23 22:57:54'),
	(81, 2, NULL, 'Medium Fish & Efo Riro', 'Medium portion of Fish with Efo Riro soup', 2200.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 102, 1, '2026-08-23 22:57:54'),
	(82, 1, NULL, 'Large Goat & Efo Riro', 'Large portion of Goat with Efo Riro soup', 3655.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Large%2BGoat%2B%26%2BEfo%2BRiro', 107, 1, '2026-08-23 22:57:54'),
	(83, 3, NULL, 'Extra Large Beef & Ogbono', 'Extra Large portion of Beef with Ogbono soup', 2839.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 111, 1, '2026-08-23 22:57:54'),
	(84, 4, NULL, 'Medium Turkey & Pepper', 'Medium portion of Turkey with Pepper soup', 2094.00, 'https://images.unsplash.com/photo-1583119022894-919a68a3d0e3?w=600', 99, 1, '2026-08-23 22:57:54'),
	(85, 2, NULL, 'Large Chicken & Ogbono', 'Large portion of Chicken with Ogbono soup', 1617.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 98, 1, '2026-08-23 22:57:54'),
	(86, 2, NULL, 'Medium Fish & Pepper', 'Medium portion of Fish with Pepper soup', 3261.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 133, 1, '2026-08-23 22:57:54'),
	(87, 3, NULL, 'Large Beef & Egusi', 'Large portion of Beef with Egusi soup', 3269.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 57, 1, '2026-08-23 22:57:54'),
	(88, 3, NULL, 'Extra Large Goat & Pepper', 'Extra Large portion of Goat with Pepper soup', 3555.00, 'https://images.unsplash.com/photo-1583119022894-919a68a3d0e3?w=600', 56, 1, '2026-08-23 22:57:54'),
	(89, 1, NULL, 'Small Chicken & Banga', 'Small portion of Chicken with Banga soup', 3535.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 65, 1, '2026-08-23 22:57:54'),
	(90, 2, NULL, 'Medium Fish & Pepper', 'Medium portion of Fish with Pepper soup', 3752.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 126, 1, '2026-08-23 22:57:54'),
	(91, 5, NULL, 'Medium Turkey & Efo Riro', 'Medium portion of Turkey with Efo Riro soup', 1550.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Medium%2BTurkey%2B%26%2BEfo%2BRiro', 86, 1, '2026-08-23 22:57:54'),
	(92, 1, NULL, 'Medium Turkey & Banga', 'Medium portion of Turkey with Banga soup', 2331.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Medium%2BTurkey%2B%26%2BBanga', 118, 1, '2026-08-23 22:57:54'),
	(93, 2, NULL, 'Medium Chicken & Ogbono', 'Medium portion of Chicken with Ogbono soup', 2261.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 66, 1, '2026-08-23 22:57:54'),
	(94, 2, NULL, 'Extra Large Turkey & Egusi', 'Extra Large portion of Turkey with Egusi soup', 2572.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Extra%2BLarge%2BTurkey%2B%26%2BEgusi', 142, 1, '2026-08-23 22:57:54'),
	(95, 1, NULL, 'Extra Large Turkey & Banga', 'Extra Large portion of Turkey with Banga soup', 2830.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Extra%2BLarge%2BTurkey%2B%26%2BBanga', 76, 1, '2026-08-23 22:57:54'),
	(96, 4, NULL, 'Large Beef & Pepper', 'Large portion of Beef with Pepper soup', 3452.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 74, 1, '2026-08-23 22:57:54'),
	(97, 5, NULL, 'Large Fish & Efo Riro', 'Large portion of Fish with Efo Riro soup', 1724.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 54, 1, '2026-08-23 22:57:54'),
	(98, 5, NULL, 'Extra Large Chicken & Egusi', 'Extra Large portion of Chicken with Egusi soup', 2390.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 69, 1, '2026-08-23 22:57:54'),
	(99, 4, NULL, 'Small Beef & Efo Riro', 'Small portion of Beef with Efo Riro soup', 3533.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 109, 1, '2026-08-23 22:57:54'),
	(100, 2, NULL, 'Medium Turkey & Efo Riro', 'Medium portion of Turkey with Efo Riro soup', 3398.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Medium%2BTurkey%2B%26%2BEfo%2BRiro', 67, 1, '2026-08-23 22:57:54'),
	(101, 1, NULL, 'Large Goat & Egusi', 'Large portion of Goat with Egusi soup', 2296.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Large%2BGoat%2B%26%2BEgusi', 114, 1, '2026-08-23 22:57:54'),
	(102, 5, NULL, 'Medium Fish & Egusi', 'Medium portion of Fish with Egusi soup', 3521.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 129, 1, '2026-08-23 22:57:54'),
	(103, 2, NULL, 'Large Goat & Banga', 'Large portion of Goat with Banga soup', 2746.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Large%2BGoat%2B%26%2BBanga', 110, 1, '2026-08-23 22:57:54'),
	(104, 5, NULL, 'Medium Fish & Pepper', 'Medium portion of Fish with Pepper soup', 3883.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 135, 1, '2026-08-23 22:57:54'),
	(105, 2, NULL, 'Large Fish & Banga', 'Large portion of Fish with Banga soup', 3356.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 117, 1, '2026-08-23 22:57:54'),
	(106, 3, NULL, 'Large Fish & Pepper', 'Large portion of Fish with Pepper soup', 3472.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 70, 1, '2026-08-23 22:57:54'),
	(107, 4, NULL, 'Small Chicken & Egusi', 'Small portion of Chicken with Egusi soup', 2678.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 81, 1, '2026-08-23 22:57:54'),
	(108, 4, NULL, 'Extra Large Fish & Banga', 'Extra Large portion of Fish with Banga soup', 2781.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 128, 1, '2026-08-23 22:57:54'),
	(109, 3, NULL, 'Small Fish & Banga', 'Small portion of Fish with Banga soup', 3124.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 77, 1, '2026-08-23 22:57:54'),
	(110, 1, NULL, 'Small Turkey & Efo Riro', 'Small portion of Turkey with Efo Riro soup', 1640.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Small%2BTurkey%2B%26%2BEfo%2BRiro', 62, 1, '2026-08-23 22:57:54'),
	(111, 2, NULL, 'Medium Chicken & Efo Riro', 'Medium portion of Chicken with Efo Riro soup', 2418.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 132, 1, '2026-08-23 22:57:54'),
	(112, 3, NULL, 'Small Turkey & Egusi', 'Small portion of Turkey with Egusi soup', 3305.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Small%2BTurkey%2B%26%2BEgusi', 88, 1, '2026-08-23 22:57:54'),
	(113, 4, NULL, 'Medium Fish & Efo Riro', 'Medium portion of Fish with Efo Riro soup', 2613.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 85, 1, '2026-08-23 22:57:54'),
	(114, 1, NULL, 'Small Turkey & Egusi', 'Small portion of Turkey with Egusi soup', 3331.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Small%2BTurkey%2B%26%2BEgusi', 89, 1, '2026-08-23 22:57:54'),
	(115, 3, NULL, 'Medium Turkey & Ogbono', 'Medium portion of Turkey with Ogbono soup', 1943.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Medium%2BTurkey%2B%26%2BOgbono', 81, 1, '2026-08-23 22:57:54'),
	(116, 4, NULL, 'Large Chicken & Pepper', 'Large portion of Chicken with Pepper soup', 2072.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 136, 1, '2026-08-23 22:57:54'),
	(117, 5, NULL, 'Large Turkey & Efo Riro', 'Large portion of Turkey with Efo Riro soup', 3822.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Large%2BTurkey%2B%26%2BEfo%2BRiro', 119, 1, '2026-08-23 22:57:54'),
	(118, 5, NULL, 'Large Chicken & Egusi', 'Large portion of Chicken with Egusi soup', 2107.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 96, 1, '2026-08-23 22:57:54'),
	(119, 5, NULL, 'Small Beef & Efo Riro', 'Small portion of Beef with Efo Riro soup', 3659.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 81, 1, '2026-08-23 22:57:54'),
	(120, 4, NULL, 'Medium Chicken & Banga', 'Medium portion of Chicken with Banga soup', 3257.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 108, 1, '2026-08-23 22:57:54'),
	(121, 3, NULL, 'Medium Fish & Ogbono', 'Medium portion of Fish with Ogbono soup', 2878.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 57, 1, '2026-08-23 22:57:54'),
	(122, 5, NULL, 'Large Beef & Egusi', 'Large portion of Beef with Egusi soup', 3740.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 119, 1, '2026-08-23 22:57:54'),
	(123, 3, NULL, 'Large Fish & Banga', 'Large portion of Fish with Banga soup', 2015.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 109, 1, '2026-08-23 22:57:54'),
	(124, 1, NULL, 'Small Turkey & Ogbono', 'Small portion of Turkey with Ogbono soup', 3456.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Small%2BTurkey%2B%26%2BOgbono', 137, 1, '2026-08-23 22:57:54'),
	(125, 3, NULL, 'Extra Large Goat & Banga', 'Extra Large portion of Goat with Banga soup', 3165.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Extra%2BLarge%2BGoat%2B%26%2BBanga', 85, 1, '2026-08-23 22:57:54'),
	(126, 4, NULL, 'Extra Large Chicken & Egusi', 'Extra Large portion of Chicken with Egusi soup', 1592.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 125, 1, '2026-08-23 22:57:54'),
	(127, 3, NULL, 'Large Fish & Ogbono', 'Large portion of Fish with Ogbono soup', 2099.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 103, 1, '2026-08-23 22:57:54'),
	(128, 2, NULL, 'Extra Large Goat & Egusi', 'Extra Large portion of Goat with Egusi soup', 3289.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Extra%2BLarge%2BGoat%2B%26%2BEgusi', 117, 1, '2026-08-23 22:57:54'),
	(129, 3, NULL, 'Small Turkey & Banga', 'Small portion of Turkey with Banga soup', 1825.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Small%2BTurkey%2B%26%2BBanga', 62, 1, '2026-08-23 22:57:54'),
	(130, 1, NULL, 'Large Beef & Egusi', 'Large portion of Beef with Egusi soup', 2624.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 96, 1, '2026-08-23 22:57:54'),
	(131, 1, NULL, 'Small Fish & Egusi', 'Small portion of Fish with Egusi soup', 1581.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 123, 1, '2026-08-23 22:57:54'),
	(132, 1, NULL, 'Extra Large Chicken & Efo Riro', 'Extra Large portion of Chicken with Efo Riro soup', 3520.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 97, 1, '2026-08-23 22:57:54'),
	(133, 1, NULL, 'Small Goat & Banga', 'Small portion of Goat with Banga soup', 3844.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Small%2BGoat%2B%26%2BBanga', 91, 1, '2026-08-23 22:57:54'),
	(134, 5, NULL, 'Extra Large Goat & Egusi', 'Extra Large portion of Goat with Egusi soup', 2178.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Extra%2BLarge%2BGoat%2B%26%2BEgusi', 97, 1, '2026-08-23 22:57:54'),
	(135, 5, NULL, 'Extra Large Beef & Ogbono', 'Extra Large portion of Beef with Ogbono soup', 3723.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 128, 1, '2026-08-23 22:57:54'),
	(136, 1, NULL, 'Small Goat & Banga', 'Small portion of Goat with Banga soup', 1981.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Small%2BGoat%2B%26%2BBanga', 57, 1, '2026-08-23 22:57:54'),
	(137, 5, NULL, 'Small Goat & Banga', 'Small portion of Goat with Banga soup', 3126.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Small%2BGoat%2B%26%2BBanga', 62, 1, '2026-08-23 22:57:54'),
	(138, 4, NULL, 'Small Beef & Pepper', 'Small portion of Beef with Pepper soup', 1991.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 88, 1, '2026-08-23 22:57:54'),
	(139, 3, NULL, 'Medium Beef & Ogbono', 'Medium portion of Beef with Ogbono soup', 3995.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 71, 1, '2026-08-23 22:57:54'),
	(140, 2, NULL, 'Extra Large Fish & Ogbono', 'Extra Large portion of Fish with Ogbono soup', 3411.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 76, 1, '2026-08-23 22:57:54'),
	(141, 3, NULL, 'Large Beef & Efo Riro', 'Large portion of Beef with Efo Riro soup', 2133.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 58, 1, '2026-08-23 22:57:54'),
	(142, 4, NULL, 'Extra Large Fish & Banga', 'Extra Large portion of Fish with Banga soup', 2316.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 50, 1, '2026-08-23 22:57:54'),
	(143, 3, NULL, 'Medium Beef & Pepper', 'Medium portion of Beef with Pepper soup', 2817.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 131, 1, '2026-08-23 22:57:54'),
	(144, 1, NULL, 'Small Chicken & Egusi', 'Small portion of Chicken with Egusi soup', 2534.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 118, 1, '2026-08-23 22:57:54'),
	(145, 2, NULL, 'Medium Goat & Egusi', 'Medium portion of Goat with Egusi soup', 3413.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Medium%2BGoat%2B%26%2BEgusi', 95, 1, '2026-08-23 22:57:54'),
	(146, 3, NULL, 'Large Turkey & Pepper', 'Large portion of Turkey with Pepper soup', 3126.00, 'https://images.unsplash.com/photo-1583119022894-919a68a3d0e3?w=600', 53, 1, '2026-08-23 22:57:54'),
	(147, 3, NULL, 'Small Beef & Ogbono', 'Small portion of Beef with Ogbono soup', 3426.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 107, 1, '2026-08-23 22:57:54'),
	(148, 2, NULL, 'Extra Large Turkey & Egusi', 'Extra Large portion of Turkey with Egusi soup', 3120.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Extra%2BLarge%2BTurkey%2B%26%2BEgusi', 123, 1, '2026-08-23 22:57:54'),
	(149, 2, NULL, 'Medium Chicken & Ogbono', 'Medium portion of Chicken with Ogbono soup', 1835.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 123, 1, '2026-08-23 22:57:54'),
	(150, 5, NULL, 'Medium Chicken & Pepper', 'Medium portion of Chicken with Pepper soup', 3059.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 80, 1, '2026-08-23 22:57:54'),
	(151, 4, NULL, 'Medium Chicken & Efo Riro', 'Medium portion of Chicken with Efo Riro soup', 2722.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 77, 1, '2026-08-23 22:57:54'),
	(152, 5, NULL, 'Small Beef & Banga', 'Small portion of Beef with Banga soup', 1547.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 91, 1, '2026-08-23 22:57:54'),
	(153, 2, NULL, 'Small Turkey & Egusi', 'Small portion of Turkey with Egusi soup', 3550.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Small%2BTurkey%2B%26%2BEgusi', 75, 1, '2026-08-23 22:57:54'),
	(154, 1, NULL, 'Medium Turkey & Pepper', 'Medium portion of Turkey with Pepper soup', 2479.00, 'https://images.unsplash.com/photo-1583119022894-919a68a3d0e3?w=600', 110, 1, '2026-08-23 22:57:54'),
	(155, 4, NULL, 'Small Chicken & Egusi', 'Small portion of Chicken with Egusi soup', 3853.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 104, 1, '2026-08-23 22:57:54'),
	(156, 1, NULL, 'Medium Turkey & Ogbono', 'Medium portion of Turkey with Ogbono soup', 1994.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Medium%2BTurkey%2B%26%2BOgbono', 120, 1, '2026-08-23 22:57:54'),
	(157, 5, NULL, 'Large Goat & Ogbono', 'Large portion of Goat with Ogbono soup', 2750.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Large%2BGoat%2B%26%2BOgbono', 59, 1, '2026-08-23 22:57:54'),
	(158, 3, NULL, 'Extra Large Goat & Ogbono', 'Extra Large portion of Goat with Ogbono soup', 2700.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Extra%2BLarge%2BGoat%2B%26%2BOgbono', 81, 1, '2026-08-23 22:57:54'),
	(159, 1, NULL, 'Extra Large Goat & Egusi', 'Extra Large portion of Goat with Egusi soup', 3731.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Extra%2BLarge%2BGoat%2B%26%2BEgusi', 80, 1, '2026-08-23 22:57:54'),
	(160, 2, NULL, 'Extra Large Beef & Banga', 'Extra Large portion of Beef with Banga soup', 3759.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 55, 1, '2026-08-23 22:57:54'),
	(161, 5, NULL, 'Extra Large Chicken & Efo Riro', 'Extra Large portion of Chicken with Efo Riro soup', 1772.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 70, 1, '2026-08-23 22:57:54'),
	(162, 5, NULL, 'Small Beef & Pepper', 'Small portion of Beef with Pepper soup', 1518.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 92, 1, '2026-08-23 22:57:54'),
	(163, 3, NULL, 'Small Fish & Ogbono', 'Small portion of Fish with Ogbono soup', 3035.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 120, 1, '2026-08-23 22:57:54'),
	(164, 5, NULL, 'Large Fish & Banga', 'Large portion of Fish with Banga soup', 3833.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 108, 1, '2026-08-23 22:57:54'),
	(165, 2, NULL, 'Large Beef & Pepper', 'Large portion of Beef with Pepper soup', 2758.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 119, 1, '2026-08-23 22:57:54'),
	(166, 5, NULL, 'Extra Large Chicken & Efo Riro', 'Extra Large portion of Chicken with Efo Riro soup', 3490.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 118, 1, '2026-08-23 22:57:54'),
	(167, 4, NULL, 'Medium Goat & Efo Riro', 'Medium portion of Goat with Efo Riro soup', 2816.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Medium%2BGoat%2B%26%2BEfo%2BRiro', 75, 1, '2026-08-23 22:57:54'),
	(168, 4, NULL, 'Extra Large Turkey & Banga', 'Extra Large portion of Turkey with Banga soup', 3476.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Extra%2BLarge%2BTurkey%2B%26%2BBanga', 126, 1, '2026-08-23 22:57:54'),
	(169, 4, NULL, 'Small Turkey & Egusi', 'Small portion of Turkey with Egusi soup', 1897.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Small%2BTurkey%2B%26%2BEgusi', 51, 1, '2026-08-23 22:57:54'),
	(170, 5, NULL, 'Large Turkey & Banga', 'Large portion of Turkey with Banga soup', 3490.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Large%2BTurkey%2B%26%2BBanga', 120, 1, '2026-08-23 22:57:54'),
	(171, 1, NULL, 'Small Goat & Pepper', 'Small portion of Goat with Pepper soup', 3562.00, 'https://images.unsplash.com/photo-1583119022894-919a68a3d0e3?w=600', 83, 1, '2026-08-23 22:57:54'),
	(172, 3, NULL, 'Medium Fish & Efo Riro', 'Medium portion of Fish with Efo Riro soup', 3942.00, 'https://images.unsplash.com/photo-1534604973900-c43ab4c2e0ab?w=600', 149, 1, '2026-08-23 22:57:54'),
	(173, 1, NULL, 'Small Turkey & Pepper', 'Small portion of Turkey with Pepper soup', 3585.00, 'https://images.unsplash.com/photo-1583119022894-919a68a3d0e3?w=600', 120, 1, '2026-08-23 22:57:54'),
	(174, 3, NULL, 'Extra Large Turkey & Efo Riro', 'Extra Large portion of Turkey with Efo Riro soup', 2119.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Extra%2BLarge%2BTurkey%2B%26%2BEfo%2BRiro', 77, 1, '2026-08-23 22:57:54'),
	(175, 5, NULL, 'Large Beef & Efo Riro', 'Large portion of Beef with Efo Riro soup', 3375.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 80, 1, '2026-08-23 22:57:54'),
	(176, 5, NULL, 'Small Chicken & Banga', 'Small portion of Chicken with Banga soup', 2997.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 110, 1, '2026-08-23 22:57:54'),
	(177, 5, NULL, 'Large Goat & Egusi', 'Large portion of Goat with Egusi soup', 1951.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Large%2BGoat%2B%26%2BEgusi', 138, 1, '2026-08-23 22:57:54'),
	(178, 1, NULL, 'Small Beef & Egusi', 'Small portion of Beef with Egusi soup', 1857.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 92, 1, '2026-08-23 22:57:54'),
	(179, 3, NULL, 'Small Chicken & Efo Riro', 'Small portion of Chicken with Efo Riro soup', 2650.00, 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=600', 94, 1, '2026-08-23 22:57:54'),
	(180, 2, NULL, 'Small Goat & Egusi', 'Small portion of Goat with Egusi soup', 1679.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Small%2BGoat%2B%26%2BEgusi', 137, 1, '2026-08-23 22:57:54'),
	(181, 5, 3, 'Indomie (Pack of 5)', 'Indomie noodles 5-pack', 600.00, 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=600', 500, 1, '2026-08-23 22:57:54'),
	(182, 2, 3, 'Indomie (Pack of 10)', 'Indomie noodles 10-pack', 1100.00, 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=600', 400, 1, '2026-08-23 22:57:54'),
	(183, 5, 3, 'Indomie (Pack of 20)', 'Indomie noodles 20-pack', 2100.00, 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=600', 300, 1, '2026-08-23 22:57:54'),
	(184, 4, 3, 'Indomie Chicken', 'Chicken flavor indomie', 120.00, 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=600', 1000, 1, '2026-08-23 22:57:54'),
	(185, 5, 3, 'Indomie Beef', 'Beef flavor indomie', 120.00, 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=600', 1000, 1, '2026-08-23 22:57:54'),
	(186, 2, 3, 'Indomie Shrimp', 'Shrimp flavor indomie', 120.00, 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=600', 1000, 1, '2026-08-23 22:57:54'),
	(187, 5, 3, 'Indomie Curry', 'Curry flavor indomie', 120.00, 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=600', 1000, 1, '2026-08-23 22:57:54'),
	(188, 8, NULL, 'Golden Mimi', 'Golden Mimi noodles', 100.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Golden%2BMimi', 800, 1, '2026-08-23 22:57:54'),
	(189, 7, NULL, 'Debono', 'Debono noodles', 90.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Debono', 800, 1, '2026-08-23 22:57:54'),
	(190, 5, NULL, 'Supa Noodles', 'Supa noodles', 80.00, 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec841?w=600', 800, 1, '2026-08-23 22:57:54'),
	(191, 3, 3, 'Garri (Ijebu)', 'Ijebu garri 1kg', 800.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 200, 1, '2026-08-23 22:57:54'),
	(192, 4, 3, 'Garri (White)', 'White garri 1kg', 600.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 250, 1, '2026-08-23 22:57:54'),
	(193, 3, 3, 'Garri (Yellow)', 'Yellow garri 1kg', 700.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 250, 1, '2026-08-23 22:57:54'),
	(194, 2, 3, 'Garri (Large Bag)', 'Garri 5kg bag', 3500.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 100, 1, '2026-08-23 22:57:54'),
	(195, 1, 1, 'Rice (Local 1kg)', 'Local rice 1kg', 900.00, 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600', 150, 1, '2026-08-23 22:57:54'),
	(196, 3, 1, 'Rice (Foreign 1kg)', 'Foreign rice 1kg', 1100.00, 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600', 150, 1, '2026-08-23 22:57:54'),
	(197, 5, 1, 'Rice (5kg)', 'Rice 5kg bag', 5000.00, 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600', 80, 1, '2026-08-23 22:57:54'),
	(198, 1, 1, 'Rice (10kg)', 'Rice 10kg bag', 9500.00, 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600', 50, 1, '2026-08-23 22:57:54'),
	(199, 5, 1, 'Rice (25kg)', 'Rice 25kg bag', 22000.00, 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600', 30, 1, '2026-08-23 22:57:54'),
	(200, 3, 3, 'Beans (Brown)', 'Brown beans 1kg', 1200.00, 'https://images.unsplash.com/photo-1515543904379-3d757afe72e3?w=600', 100, 1, '2026-08-23 22:57:54'),
	(201, 3, 3, 'Beans (Honey)', 'Honey beans 1kg', 1500.00, 'https://images.unsplash.com/photo-1515543904379-3d757afe72e3?w=600', 100, 1, '2026-08-23 22:57:54'),
	(202, 3, 3, 'Beans (Oloyin)', 'Oloyin beans 1kg', 1400.00, 'https://images.unsplash.com/photo-1515543904379-3d757afe72e3?w=600', 100, 1, '2026-08-23 22:57:54'),
	(203, 1, NULL, 'Yam (Small)', 'Small yam tuber', 800.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 100, 1, '2026-08-23 22:57:54'),
	(204, 3, NULL, 'Yam (Medium)', 'Medium yam tuber', 1200.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 80, 1, '2026-08-23 22:57:54'),
	(205, 5, NULL, 'Yam (Large)', 'Large yam tuber', 1800.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 60, 1, '2026-08-23 22:57:54'),
	(206, 5, NULL, 'Yam (Bag)', 'Bag of yam (10 tubers)', 10000.00, 'https://images.unsplash.com/photo-1626202158825-1c63c2883454?w=600', 20, 1, '2026-08-23 22:57:54'),
	(207, 3, NULL, 'Plantain (Bunch)', 'Bunch of plantain', 1500.00, 'https://images.unsplash.com/photo-1603569283847-aa295f0d016a?w=600', 50, 1, '2026-08-23 22:57:54'),
	(208, 1, NULL, 'Plantain (Single)', 'Single plantain', 200.00, 'https://images.unsplash.com/photo-1603569283847-aa295f0d016a?w=600', 200, 1, '2026-08-23 22:57:54'),
	(209, 7, NULL, 'Milo (Small)', 'Milo tin small', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Milo%2B%28Small%29', 100, 1, '2026-08-23 22:57:54'),
	(210, 6, NULL, 'Milo (Medium)', 'Milo tin medium', 2500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Milo%2B%28Medium%29', 80, 1, '2026-08-23 22:57:54'),
	(211, 7, NULL, 'Milo (Large)', 'Milo tin large', 4500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Milo%2B%28Large%29', 60, 1, '2026-08-23 22:57:54'),
	(212, 8, NULL, 'Bournvita (Small)', 'Bournvita small', 1200.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Bournvita%2B%28Small%29', 100, 1, '2026-08-23 22:57:54'),
	(213, 7, NULL, 'Bournvita (Medium)', 'Bournvita medium', 2200.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Bournvita%2B%28Medium%29', 80, 1, '2026-08-23 22:57:54'),
	(214, 7, NULL, 'Bournvita (Large)', 'Bournvita large', 4000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Bournvita%2B%28Large%29', 60, 1, '2026-08-23 22:57:54'),
	(215, 7, NULL, 'Horlicks', 'Horlicks malt', 2000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Horlicks', 80, 1, '2026-08-23 22:57:54'),
	(216, 8, NULL, 'Ovaltine', 'Ovaltine malt', 1800.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Ovaltine', 80, 1, '2026-08-23 22:57:54'),
	(217, 7, NULL, 'Sugar (1kg)', 'White sugar 1kg', 800.00, 'https://images.unsplash.com/photo-1581441363688-d62f121433bf?w=600', 150, 1, '2026-08-23 22:57:54'),
	(218, 7, NULL, 'Sugar (500g)', 'White sugar 500g', 450.00, 'https://images.unsplash.com/photo-1581441363688-d62f121433bf?w=600', 200, 1, '2026-08-23 22:57:54'),
	(219, 7, NULL, 'Sugar (Cubes)', 'Sugar cubes pack', 300.00, 'https://images.unsplash.com/photo-1581441363688-d62f121433bf?w=600', 200, 1, '2026-08-23 22:57:54'),
	(220, 8, NULL, 'Salt (Iodized)', 'Iodized salt 1kg', 400.00, 'https://images.unsplash.com/photo-1581441363688-d62f121433bf?w=600', 200, 1, '2026-08-23 22:57:54'),
	(221, 8, NULL, 'Salt (Table)', 'Table salt 500g', 250.00, 'https://images.unsplash.com/photo-1581441363688-d62f121433bf?w=600', 250, 1, '2026-08-23 22:57:54'),
	(222, 8, NULL, 'Tomato Paste (Small)', 'Tomato paste small tin', 300.00, 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=600', 300, 1, '2026-08-23 22:57:54'),
	(223, 6, NULL, 'Tomato Paste (Large)', 'Tomato paste large tin', 600.00, 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=600', 250, 1, '2026-08-23 22:57:54'),
	(224, 7, NULL, 'Fresh Tomatoes (1kg)', 'Fresh tomatoes 1kg', 800.00, 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=600', 150, 1, '2026-08-23 22:57:54'),
	(225, 8, NULL, 'Fresh Tomatoes (Basket)', 'Basket of tomatoes', 3000.00, 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=600', 50, 1, '2026-08-23 22:57:54'),
	(226, 7, NULL, 'Onions (1kg)', 'Onions 1kg', 600.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Onions%2B%281kg%29', 150, 1, '2026-08-23 22:57:54'),
	(227, 8, NULL, 'Onions (Bag)', 'Bag of onions', 5000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Onions%2B%28Bag%29', 30, 1, '2026-08-23 22:57:54'),
	(228, 8, NULL, 'Pepper (Fresh)', 'Fresh pepper 1kg', 1000.00, 'https://images.unsplash.com/photo-1583119022894-919a68a3d0e3?w=600', 100, 1, '2026-08-23 22:57:54'),
	(229, 6, NULL, 'Pepper (Dry)', 'Dry pepper 500g', 800.00, 'https://images.unsplash.com/photo-1583119022894-919a68a3d0e3?w=600', 150, 1, '2026-08-23 22:57:54'),
	(230, 7, NULL, 'Scotch Bonnet', 'Scotch bonnet pepper', 500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Scotch%2BBonnet', 200, 1, '2026-08-23 22:57:54'),
	(231, 1, NULL, 'Vegetable Oil (1L)', 'Vegetable oil 1 liter', 1200.00, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600', 150, 1, '2026-08-23 22:57:54'),
	(232, 3, NULL, 'Vegetable Oil (2L)', 'Vegetable oil 2 liters', 2300.00, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600', 100, 1, '2026-08-23 22:57:54'),
	(233, 5, NULL, 'Vegetable Oil (5L)', 'Vegetable oil 5 liters', 5500.00, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600', 50, 1, '2026-08-23 22:57:54'),
	(234, 6, NULL, 'Palm Oil (1L)', 'Palm oil 1 liter', 1500.00, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600', 100, 1, '2026-08-23 22:57:54'),
	(235, 6, NULL, 'Palm Oil (2L)', 'Palm oil 2 liters', 2800.00, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600', 80, 1, '2026-08-23 22:57:54'),
	(236, 4, NULL, 'Eggs (Crate)', 'Crate of eggs (30)', 2500.00, 'https://images.unsplash.com/photo-1587486936739-7b421984a61e?w=600', 100, 1, '2026-08-23 22:57:54'),
	(237, 5, NULL, 'Eggs (Half Crate)', 'Half crate (15)', 1300.00, 'https://images.unsplash.com/photo-1587486936739-7b421984a61e?w=600', 150, 1, '2026-08-23 22:57:54'),
	(238, 4, NULL, 'Eggs (Per Piece)', 'Single egg', 90.00, 'https://images.unsplash.com/photo-1587486936739-7b421984a61e?w=600', 500, 1, '2026-08-23 22:57:54'),
	(239, 1, NULL, 'Bread (Loaf)', 'Loaf of bread', 800.00, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600', 200, 1, '2026-08-23 22:57:54'),
	(240, 3, NULL, 'Bread (Sliced)', 'Sliced bread', 900.00, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600', 200, 1, '2026-08-23 22:57:54'),
	(241, 1, NULL, 'Bread (Wheat)', 'Wheat bread', 1000.00, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600', 150, 1, '2026-08-23 22:57:54'),
	(242, 3, NULL, 'Bread (Family)', 'Family size bread', 1200.00, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600', 150, 1, '2026-08-23 22:57:54'),
	(243, 8, NULL, 'Spaghetti (Pack)', 'Spaghetti pack', 600.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Spaghetti%2B%28Pack%29', 200, 1, '2026-08-23 22:57:54'),
	(244, 7, NULL, 'Macaroni (Pack)', 'Macaroni pack', 550.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Macaroni%2B%28Pack%29', 200, 1, '2026-08-23 22:57:54'),
	(245, 8, NULL, 'Pasta (Pack)', 'Pasta pack', 500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Pasta%2B%28Pack%29', 250, 1, '2026-08-23 22:57:54'),
	(246, 7, NULL, 'Cornflakes', 'Cornflakes cereal', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Cornflakes', 100, 1, '2026-08-23 22:57:54'),
	(247, 8, NULL, 'Golden Morn', 'Golden Morn cereal', 800.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Golden%2BMorn', 150, 1, '2026-08-23 22:57:54'),
	(248, 6, NULL, 'Custard (Pack)', 'Custard powder', 600.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Custard%2B%28Pack%29', 200, 1, '2026-08-23 22:57:54'),
	(249, 9, NULL, 'Dior Sauvage (100ml)', 'Original Dior Sauvage EDT', 15000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Dior%2BSauvage%2B%28100ml%29', 30, 1, '2026-08-23 22:57:54'),
	(250, 9, NULL, 'Dior Sauvage (50ml)', 'Dior Sauvage EDT 50ml', 10000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Dior%2BSauvage%2B%2850ml%29', 40, 1, '2026-08-23 22:57:54'),
	(251, 11, NULL, 'Chanel Coco Mademoiselle', 'Chanel perfume 100ml', 18000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Chanel%2BCoco%2BMademoiselle', 25, 1, '2026-08-23 22:57:54'),
	(252, 11, NULL, 'Chanel No. 5', 'Classic Chanel No. 5', 20000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Chanel%2BNo.%2B5', 20, 1, '2026-08-23 22:57:54'),
	(253, 11, NULL, 'Versace Eros', 'Versace Eros EDT 100ml', 12000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Versace%2BEros', 35, 1, '2026-08-23 22:57:54'),
	(254, 9, NULL, 'Versace Bright Crystal', 'Versace Bright Crystal', 11000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Versace%2BBright%2BCrystal', 35, 1, '2026-08-23 22:57:54'),
	(255, 11, NULL, 'Armani Code', 'Armani Code perfume', 13000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Armani%2BCode', 30, 1, '2026-08-23 22:57:54'),
	(256, 11, NULL, 'Gucci Flora', 'Gucci Flora perfume', 14000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Gucci%2BFlora', 25, 1, '2026-08-23 22:57:54'),
	(257, 11, NULL, 'Tom Ford Black Orchid', 'Tom Ford perfume', 25000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Tom%2BFord%2BBlack%2BOrchid', 15, 1, '2026-08-23 22:57:54'),
	(258, 11, NULL, 'Creed Aventus', 'Creed Aventus clone', 8000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Creed%2BAventus', 40, 1, '2026-08-23 22:57:54'),
	(259, 9, NULL, 'Body Spray (Adidas)', 'Adidas body spray', 2500.00, 'https://images.unsplash.com/photo-1608248597279-f99d160bfbc8?w=600', 100, 1, '2026-08-23 22:57:54'),
	(260, 9, NULL, 'Body Spray (Nivea)', 'Nivea body spray', 2000.00, 'https://images.unsplash.com/photo-1608248597279-f99d160bfbc8?w=600', 120, 1, '2026-08-23 22:57:54'),
	(261, 9, NULL, 'Body Spray (Old Spice)', 'Old Spice body spray', 2200.00, 'https://images.unsplash.com/photo-1608248597279-f99d160bfbc8?w=600', 100, 1, '2026-08-23 22:57:54'),
	(262, 9, NULL, 'Body Spray (Axe)', 'Axe body spray', 2300.00, 'https://images.unsplash.com/photo-1608248597279-f99d160bfbc8?w=600', 100, 1, '2026-08-23 22:57:54'),
	(263, 9, NULL, 'Body Spray (Rexona)', 'Rexona body spray', 1800.00, 'https://images.unsplash.com/photo-1608248597279-f99d160bfbc8?w=600', 150, 1, '2026-08-23 22:57:54'),
	(264, 9, NULL, 'Deodorant (Roll-on)', 'Roll-on deodorant', 1500.00, 'https://images.unsplash.com/photo-1608248597279-f99d160bfbc8?w=600', 200, 1, '2026-08-23 22:57:54'),
	(265, 9, NULL, 'Deodorant (Stick)', 'Deodorant stick', 1800.00, 'https://images.unsplash.com/photo-1608248597279-f99d160bfbc8?w=600', 150, 1, '2026-08-23 22:57:54'),
	(266, 11, 2, 'Perfume Oil (Small)', 'Perfume oil 6ml', 3000.00, 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=600', 100, 1, '2026-08-23 22:57:54'),
	(267, 9, 2, 'Perfume Oil (Medium)', 'Perfume oil 12ml', 5000.00, 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=600', 80, 1, '2026-08-23 22:57:54'),
	(268, 11, 2, 'Perfume Oil (Large)', 'Perfume oil 25ml', 8000.00, 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=600', 60, 1, '2026-08-23 22:57:54'),
	(269, 11, 2, 'Lipstick (Matte)', 'Matte lipstick', 2500.00, 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=600', 100, 1, '2026-08-23 22:57:54'),
	(270, 11, 2, 'Lipstick (Gloss)', 'Lip gloss', 2000.00, 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=600', 120, 1, '2026-08-23 22:57:54'),
	(271, 11, 2, 'Lipstick (Long Last)', 'Long lasting lipstick', 3000.00, 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=600', 80, 1, '2026-08-23 22:57:54'),
	(272, 11, NULL, 'Lip Balm', 'Moisturizing lip balm', 1000.00, 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=600', 200, 1, '2026-08-23 22:57:54'),
	(273, 9, 2, 'Foundation', 'Liquid foundation', 4000.00, 'https://images.unsplash.com/photo-1596462502278-27bfdd403348?w=600', 80, 1, '2026-08-23 22:57:54'),
	(274, 9, NULL, 'Powder', 'Face powder', 3000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Powder', 100, 1, '2026-08-23 22:57:54'),
	(275, 9, NULL, 'Concealer', 'Face concealer', 2500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Concealer', 100, 1, '2026-08-23 22:57:54'),
	(276, 11, NULL, 'Blush', 'Blush on', 2500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Blush', 100, 1, '2026-08-23 22:57:54'),
	(277, 9, NULL, 'Mascara', 'Eye mascara', 3000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Mascara', 80, 1, '2026-08-23 22:57:54'),
	(278, 9, NULL, 'Eye Shadow Palette', 'Eye shadow palette', 5000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Eye%2BShadow%2BPalette', 60, 1, '2026-08-23 22:57:54'),
	(279, 9, NULL, 'Eyeliner', 'Liquid eyeliner', 2000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Eyeliner', 100, 1, '2026-08-23 22:57:54'),
	(280, 11, 6, 'Eye Pencil', 'Eye brow pencil', 1500.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=600', 150, 1, '2026-08-23 22:57:54'),
	(281, 11, NULL, 'Face Cream (Nivea)', 'Nivea face cream', 2500.00, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600', 100, 1, '2026-08-23 22:57:54'),
	(282, 9, NULL, 'Face Cream (Garnier)', 'Garnier face cream', 3000.00, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600', 80, 1, '2026-08-23 22:57:54'),
	(283, 9, NULL, 'Face Cream (Olay)', 'Olay face cream', 3500.00, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600', 70, 1, '2026-08-23 22:57:54'),
	(284, 11, 7, 'Body Lotion (Small)', 'Body lotion 200ml', 2000.00, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600', 150, 1, '2026-08-23 22:57:54'),
	(285, 11, 7, 'Body Lotion (Large)', 'Body lotion 400ml', 3500.00, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600', 100, 1, '2026-08-23 22:57:54'),
	(286, 9, NULL, 'Body Cream', 'Body cream', 3000.00, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600', 100, 1, '2026-08-23 22:57:54'),
	(287, 11, NULL, 'Shea Butter', 'Pure shea butter', 2000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Shea%2BButter', 120, 1, '2026-08-23 22:57:54'),
	(288, 9, 7, 'Soap (Lux)', 'Lux soap bar', 400.00, 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600', 300, 1, '2026-08-23 22:57:54'),
	(289, 11, 7, 'Soap (Dettol)', 'Dettol soap bar', 500.00, 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600', 250, 1, '2026-08-23 22:57:54'),
	(290, 9, 7, 'Soap (Imperial Leather)', 'Imperial Leather', 450.00, 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600', 250, 1, '2026-08-23 22:57:54'),
	(291, 11, 7, 'Soap (Pearl)', 'Pearl soap', 350.00, 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600', 300, 1, '2026-08-23 22:57:54'),
	(292, 9, NULL, 'Body Wash', 'Body wash 500ml', 2500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Body%2BWash', 100, 1, '2026-08-23 22:57:54'),
	(293, 11, NULL, 'Shower Gel', 'Shower gel', 2000.00, 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600', 120, 1, '2026-08-23 22:57:54'),
	(294, 12, NULL, 'Coca Cola (Can)', 'Coca Cola 33cl can', 300.00, 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=600', 500, 1, '2026-08-23 22:57:54'),
	(295, 13, NULL, 'Coca Cola (Bottle)', 'Coca Cola 50cl bottle', 400.00, 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=600', 400, 1, '2026-08-23 22:57:54'),
	(296, 12, NULL, 'Coca Cola (1.5L)', 'Coca Cola 1.5 liter', 700.00, 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=600', 200, 1, '2026-08-23 22:57:54'),
	(297, 12, NULL, 'Coca Cola (2L)', 'Coca Cola 2 liter', 900.00, 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=600', 150, 1, '2026-08-23 22:57:54'),
	(298, 13, NULL, 'Fanta (Can)', 'Fanta orange can', 300.00, 'https://images.unsplash.com/photo-1625772299848-391b6a87d7b3?w=600', 500, 1, '2026-08-23 22:57:54'),
	(299, 13, NULL, 'Fanta (Bottle)', 'Fanta 50cl', 400.00, 'https://images.unsplash.com/photo-1625772299848-391b6a87d7b3?w=600', 400, 1, '2026-08-23 22:57:54'),
	(300, 12, NULL, 'Sprite (Can)', 'Sprite can', 300.00, 'https://images.unsplash.com/photo-1625772299848-391b6a87d7b3?w=600', 500, 1, '2026-08-23 22:57:54'),
	(301, 12, NULL, 'Sprite (Bottle)', 'Sprite 50cl', 400.00, 'https://images.unsplash.com/photo-1625772299848-391b6a87d7b3?w=600', 400, 1, '2026-08-23 22:57:54'),
	(302, 12, 4, 'Malt (Amstel)', 'Amstel malt drink', 400.00, 'https://images.unsplash.com/photo-1563227812-0ea4c22e6cc8?w=600', 400, 1, '2026-08-23 22:57:54'),
	(303, 13, 4, 'Malt (Hi-Malt)', 'Hi-Malt drink', 350.00, 'https://images.unsplash.com/photo-1563227812-0ea4c22e6cc8?w=600', 450, 1, '2026-08-23 22:57:54'),
	(304, 12, 4, 'Malt (Dublin)', 'Dublin malt', 350.00, 'https://images.unsplash.com/photo-1563227812-0ea4c22e6cc8?w=600', 450, 1, '2026-08-23 22:57:54'),
	(305, 12, 4, 'Malt (Nonic)', 'Nonic malt', 300.00, 'https://images.unsplash.com/photo-1563227812-0ea4c22e6cc8?w=600', 500, 1, '2026-08-23 22:57:54'),
	(306, 12, NULL, 'Water (Small)', 'Small water 50cl', 100.00, 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=600', 1000, 1, '2026-08-23 22:57:54'),
	(307, 12, NULL, 'Water (Medium)', 'Medium water 75cl', 150.00, 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=600', 800, 1, '2026-08-23 22:57:54'),
	(308, 12, NULL, 'Water (Large)', 'Large water 1.5L', 300.00, 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=600', 400, 1, '2026-08-23 22:57:54'),
	(309, 12, 4, 'Juice (Maltina)', 'Maltina juice', 200.00, 'https://images.unsplash.com/photo-1563227812-0ea4c22e6cc8?w=600', 500, 1, '2026-08-23 22:57:54'),
	(310, 12, NULL, 'Juice (La Casera)', 'La Casera juice', 250.00, 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=600', 400, 1, '2026-08-23 22:57:54'),
	(311, 12, NULL, 'Juice (Chivita)', 'Chivita juice', 300.00, 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=600', 350, 1, '2026-08-23 22:57:54'),
	(312, 13, NULL, 'Juice (Five Alive)', 'Five Alive juice', 350.00, 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=600', 300, 1, '2026-08-23 22:57:54'),
	(313, 13, NULL, 'Energy Drink (Red Bull)', 'Red Bull energy drink', 800.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Energy%2BDrink%2B%28Red%2BBull%29', 200, 1, '2026-08-23 22:57:54'),
	(314, 13, NULL, 'Energy Drink (Monster)', 'Monster energy', 900.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Energy%2BDrink%2B%28Monster%29', 150, 1, '2026-08-23 22:57:54'),
	(315, 13, NULL, 'Energy Drink (Power Horse)', 'Power Horse', 500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Energy%2BDrink%2B%28Power%2BHorse%29', 300, 1, '2026-08-23 22:57:54'),
	(316, 12, 4, 'Chips (Lays)', 'Lays potato chips', 500.00, 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?w=600', 200, 1, '2026-08-23 22:57:54'),
	(317, 12, 4, 'Chips (Doritos)', 'Doritos chips', 600.00, 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?w=600', 150, 1, '2026-08-23 22:57:54'),
	(318, 13, 4, 'Chips (Pringles)', 'Pringles can', 1200.00, 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?w=600', 100, 1, '2026-08-23 22:57:54'),
	(319, 13, 4, 'Chips (Local)', 'Local potato chips', 200.00, 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?w=600', 400, 1, '2026-08-23 22:57:54'),
	(320, 13, 4, 'Biscuits (Oreo)', 'Oreo biscuits', 600.00, 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=600', 200, 1, '2026-08-23 22:57:54'),
	(321, 13, 4, 'Biscuits (Tuc)', 'Tuc crackers', 500.00, 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=600', 250, 1, '2026-08-23 22:57:54'),
	(322, 13, 4, 'Biscuits (Jacob)', 'Jacob crackers', 450.00, 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=600', 250, 1, '2026-08-23 22:57:54'),
	(323, 13, 4, 'Biscuits (Digestive)', 'Digestive biscuits', 550.00, 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=600', 200, 1, '2026-08-23 22:57:54'),
	(324, 13, 4, 'Biscuits (Cream Crackers)', 'Cream crackers', 400.00, 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=600', 300, 1, '2026-08-23 22:57:54'),
	(325, 12, NULL, 'Chocolate (Cadbury)', 'Cadbury chocolate', 800.00, 'https://images.unsplash.com/photo-1606312619070-d48b4c652a52?w=600', 150, 1, '2026-08-23 22:57:54'),
	(326, 13, NULL, 'Chocolate (Dairy Milk)', 'Dairy Milk chocolate', 900.00, 'https://images.unsplash.com/photo-1606312619070-d48b4c652a52?w=600', 120, 1, '2026-08-23 22:57:54'),
	(327, 12, NULL, 'Chocolate (KitKat)', 'KitKat chocolate', 500.00, 'https://images.unsplash.com/photo-1606312619070-d48b4c652a52?w=600', 200, 1, '2026-08-23 22:57:54'),
	(328, 13, NULL, 'Chocolate (Snickers)', 'Snickers bar', 600.00, 'https://images.unsplash.com/photo-1606312619070-d48b4c652a52?w=600', 150, 1, '2026-08-23 22:57:54'),
	(329, 12, NULL, 'Chocolate (Twix)', 'Twix chocolate', 600.00, 'https://images.unsplash.com/photo-1606312619070-d48b4c652a52?w=600', 150, 1, '2026-08-23 22:57:54'),
	(330, 13, NULL, 'Chocolate (M&M)', 'M&M chocolate', 700.00, 'https://images.unsplash.com/photo-1606312619070-d48b4c652a52?w=600', 150, 1, '2026-08-23 22:57:54'),
	(331, 1, NULL, 'Groundnut (Pack)', 'Roasted groundnut pack', 300.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Groundnut%2B%28Pack%29', 300, 1, '2026-08-23 22:57:54'),
	(332, 12, NULL, 'Cashew (Pack)', 'Cashew nuts pack', 1000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Cashew%2B%28Pack%29', 100, 1, '2026-08-23 22:57:54'),
	(333, 12, NULL, 'Almonds (Pack)', 'Almonds pack', 1200.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Almonds%2B%28Pack%29', 80, 1, '2026-08-23 22:57:54'),
	(334, 12, NULL, 'Mixed Nuts', 'Mixed nuts pack', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Mixed%2BNuts', 80, 1, '2026-08-23 22:57:54'),
	(335, 13, NULL, 'Popcorn', 'Popcorn pack', 400.00, 'https://images.unsplash.com/photo-1578849278619-e73505e9610f?w=600', 200, 1, '2026-08-23 22:57:54'),
	(336, 13, NULL, 'Cheese Balls', 'Cheese balls pack', 300.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Cheese%2BBalls', 250, 1, '2026-08-23 22:57:54'),
	(337, 1, NULL, 'Meat Snacks', 'Meat snacks pack', 500.00, 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=600', 149, 1, '2026-08-23 22:57:54'),
	(338, 8, 7, 'Toothpaste (Colgate)', 'Colgate toothpaste', 600.00, 'https://images.unsplash.com/photo-1559650656-5d1d361ad10e?w=600', 200, 1, '2026-08-23 22:57:54'),
	(339, 8, 7, 'Toothpaste (Close Up)', 'Close Up toothpaste', 550.00, 'https://images.unsplash.com/photo-1559650656-5d1d361ad10e?w=600', 200, 1, '2026-08-23 22:57:54'),
	(340, 8, 7, 'Toothpaste (Sensodyne)', 'Sensodyne toothpaste', 1200.00, 'https://images.unsplash.com/photo-1559650656-5d1d361ad10e?w=600', 100, 1, '2026-08-23 22:57:54'),
	(341, 6, NULL, 'Toothbrush (Soft)', 'Soft toothbrush', 300.00, 'https://images.unsplash.com/photo-1559650656-5d1d361ad10e?w=600', 300, 1, '2026-08-23 22:57:54'),
	(342, 7, NULL, 'Toothbrush (Medium)', 'Medium toothbrush', 350.00, 'https://images.unsplash.com/photo-1559650656-5d1d361ad10e?w=600', 250, 1, '2026-08-23 22:57:54'),
	(343, 8, NULL, 'Toothbrush (Electric)', 'Electric toothbrush', 3000.00, 'https://images.unsplash.com/photo-1559650656-5d1d361ad10e?w=600', 50, 1, '2026-08-23 22:57:54'),
	(344, 8, NULL, 'Mouthwash', 'Mouthwash 500ml', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Mouthwash', 100, 1, '2026-08-23 22:57:54'),
	(345, 6, NULL, 'Dental Floss', 'Dental floss', 500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Dental%2BFloss', 150, 1, '2026-08-23 22:57:54'),
	(346, 8, NULL, 'Shampoo (Small)', 'Shampoo 200ml', 1000.00, 'https://images.unsplash.com/photo-1526947425960-945c6e72858f?w=600', 150, 1, '2026-08-23 22:57:54'),
	(347, 7, NULL, 'Shampoo (Medium)', 'Shampoo 400ml', 1800.00, 'https://images.unsplash.com/photo-1526947425960-945c6e72858f?w=600', 100, 1, '2026-08-23 22:57:54'),
	(348, 7, NULL, 'Shampoo (Large)', 'Shampoo 750ml', 3000.00, 'https://images.unsplash.com/photo-1526947425960-945c6e72858f?w=600', 80, 1, '2026-08-23 22:57:54'),
	(349, 8, NULL, 'Conditioner', 'Hair conditioner', 2000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Conditioner', 100, 1, '2026-08-23 22:57:54'),
	(350, 8, NULL, 'Hair Cream', 'Hair cream', 1500.00, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600', 120, 1, '2026-08-23 22:57:54'),
	(351, 7, NULL, 'Hair Gel', 'Hair gel', 1200.00, 'https://images.unsplash.com/photo-1526947425960-945c6e72858f?w=600', 150, 1, '2026-08-23 22:57:54'),
	(352, 8, NULL, 'Pomade', 'Hair pomade', 1000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Pomade', 150, 1, '2026-08-23 22:57:54'),
	(353, 6, 7, 'Soap (Antibacterial)', 'Antibacterial soap', 600.00, 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600', 200, 1, '2026-08-23 22:57:54'),
	(354, 7, 7, 'Soap (Medicated)', 'Medicated soap', 700.00, 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600', 150, 1, '2026-08-23 22:57:54'),
	(355, 8, NULL, 'Hand Sanitizer', 'Hand sanitizer 500ml', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Hand%2BSanitizer', 200, 1, '2026-08-23 22:57:54'),
	(356, 7, NULL, 'Hand Wash', 'Liquid hand wash', 1200.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Hand%2BWash', 150, 1, '2026-08-23 22:57:54'),
	(357, 6, NULL, 'Razor (Disposable)', 'Disposable razor', 300.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Razor%2B%28Disposable%29', 300, 1, '2026-08-23 22:57:54'),
	(358, 6, NULL, 'Razor Blades', 'Razor blades pack', 500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Razor%2BBlades', 200, 1, '2026-08-23 22:57:54'),
	(359, 7, NULL, 'Shaving Cream', 'Shaving cream', 800.00, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600', 150, 1, '2026-08-23 22:57:54'),
	(360, 8, NULL, 'Aftershave', 'Aftershave lotion', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Aftershave', 100, 1, '2026-08-23 22:57:54'),
	(361, 6, NULL, 'Tissue (Pack)', 'Tissue paper pack', 400.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Tissue%2B%28Pack%29', 300, 1, '2026-08-23 22:57:54'),
	(362, 8, 6, 'Toilet Paper', 'Toilet paper roll', 500.00, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600', 250, 1, '2026-08-23 22:57:54'),
	(363, 6, 6, 'Paper Towels', 'Paper towels', 600.00, 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=600', 200, 1, '2026-08-23 22:57:54'),
	(364, 6, NULL, 'Napkins', 'Napkins pack', 300.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Napkins', 300, 1, '2026-08-23 22:57:54'),
	(365, 6, NULL, 'Detergent (Small)', 'Detergent powder 1kg', 1000.00, 'https://images.unsplash.com/photo-1583947215259-38e31be8751f?w=600', 150, 1, '2026-08-23 22:57:54'),
	(366, 8, NULL, 'Detergent (Large)', 'Detergent powder 5kg', 4500.00, 'https://images.unsplash.com/photo-1583947215259-38e31be8751f?w=600', 80, 1, '2026-08-23 22:57:54'),
	(367, 6, 7, 'Liquid Soap', 'Liquid soap 1L', 1200.00, 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600', 120, 1, '2026-08-23 22:57:54'),
	(368, 7, NULL, 'Disinfectant', 'Disinfectant 1L', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Disinfectant', 100, 1, '2026-08-23 22:57:54'),
	(369, 8, NULL, 'Air Freshener', 'Air freshener spray', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Air%2BFreshener', 100, 1, '2026-08-23 22:57:54'),
	(370, 7, NULL, 'Mosquito Repellent', 'Mosquito repellent', 1200.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Mosquito%2BRepellent', 120, 1, '2026-08-23 22:57:54'),
	(371, 6, NULL, 'Insecticide Spray', 'Insecticide spray', 1800.00, 'https://images.unsplash.com/photo-1608248597279-f99d160bfbc8?w=600', 100, 1, '2026-08-23 22:57:54'),
	(372, 14, 5, 'Phone Charger (Android)', 'Android phone charger', 1500.00, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600', 200, 1, '2026-08-23 22:57:54'),
	(373, 14, 5, 'Phone Charger (iPhone)', 'iPhone charger', 2500.00, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600', 150, 1, '2026-08-23 22:57:54'),
	(374, 14, 5, 'Power Bank (10000mAh)', 'Power bank 10000mAh', 8000.00, 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=600', 100, 1, '2026-08-23 22:57:54'),
	(375, 14, 5, 'Power Bank (20000mAh)', 'Power bank 20000mAh', 12000.00, 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=600', 80, 1, '2026-08-23 22:57:54'),
	(376, 14, NULL, 'USB Cable', 'USB charging cable', 800.00, 'https://placehold.co/600x400/dc3545/ffffff?text=USB%2BCable', 300, 1, '2026-08-23 22:57:54'),
	(377, 14, 5, 'Earphone (Wired)', 'Wired earphone', 1500.00, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600', 200, 1, '2026-08-23 22:57:54'),
	(378, 15, 5, 'Earphone (Wireless)', 'Wireless earphone', 5000.00, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600', 100, 1, '2026-08-23 22:57:54'),
	(379, 15, NULL, 'Headphone', 'Over-ear headphone', 6000.00, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600', 80, 1, '2026-08-23 22:57:54'),
	(380, 15, NULL, 'Bluetooth Speaker', 'Bluetooth speaker', 7000.00, 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600', 80, 1, '2026-08-23 22:57:54'),
	(381, 14, NULL, 'Flash Drive (8GB)', 'USB flash drive 8GB', 2500.00, 'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=600', 150, 1, '2026-08-23 22:57:55'),
	(382, 15, NULL, 'Flash Drive (16GB)', 'USB flash drive 16GB', 3500.00, 'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=600', 120, 1, '2026-08-23 22:57:55'),
	(383, 14, NULL, 'Flash Drive (32GB)', 'USB flash drive 32GB', 5000.00, 'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=600', 100, 1, '2026-08-23 22:57:55'),
	(384, 15, NULL, 'Flash Drive (64GB)', 'USB flash drive 64GB', 8000.00, 'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=600', 80, 1, '2026-08-23 22:57:55'),
	(385, 15, NULL, 'Memory Card (16GB)', 'SD card 16GB', 3000.00, 'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=600', 120, 1, '2026-08-23 22:57:55'),
	(386, 14, NULL, 'Memory Card (32GB)', 'SD card 32GB', 4500.00, 'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=600', 100, 1, '2026-08-23 22:57:55'),
	(387, 14, NULL, 'Memory Card (64GB)', 'SD card 64GB', 7000.00, 'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=600', 80, 1, '2026-08-23 22:57:55'),
	(388, 14, NULL, 'Phone Case', 'Phone protective case', 1500.00, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600', 200, 1, '2026-08-23 22:57:55'),
	(389, 14, NULL, 'Screen Protector', 'Phone screen protector', 800.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Screen%2BProtector', 300, 1, '2026-08-23 22:57:55'),
	(390, 15, NULL, 'Phone Stand', 'Phone stand holder', 1200.00, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600', 150, 1, '2026-08-23 22:57:55'),
	(391, 14, NULL, 'Laptop Stand', 'Laptop stand', 5000.00, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600', 60, 1, '2026-08-23 22:57:55'),
	(392, 15, NULL, 'Extension Box', 'Extension box 4-way', 2500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Extension%2BBox', 100, 1, '2026-08-23 22:57:55'),
	(393, 15, NULL, 'Extension Box (Surge)', 'Surge protector', 4000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Extension%2BBox%2B%28Surge%29', 80, 1, '2026-08-23 22:57:55'),
	(394, 15, NULL, 'Bulb (LED)', 'LED bulb', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Bulb%2B%28LED%29', 150, 1, '2026-08-23 22:57:55'),
	(395, 14, NULL, 'Torchlight', 'Rechargeable torchlight', 3000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Torchlight', 100, 1, '2026-08-23 22:57:55'),
	(396, 15, NULL, 'Lantern', 'Rechargeable lantern', 5000.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Lantern', 80, 1, '2026-08-23 22:57:55'),
	(397, 16, 6, 'Exercise Book (40 pages)', 'Exercise book 40 pages', 200.00, 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=600', 500, 1, '2026-08-23 22:57:55'),
	(398, 16, 6, 'Exercise Book (60 pages)', 'Exercise book 60 pages', 300.00, 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=600', 400, 1, '2026-08-23 22:57:55'),
	(399, 16, 6, 'Exercise Book (80 pages)', 'Exercise book 80 pages', 400.00, 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=600', 350, 1, '2026-08-23 22:57:55'),
	(400, 16, 6, 'Hardcover Book', 'Hardcover notebook', 800.00, 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=600', 200, 1, '2026-08-23 22:57:55'),
	(401, 16, NULL, 'Diary', 'Daily diary planner', 1200.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Diary', 150, 1, '2026-08-23 22:57:55'),
	(402, 16, NULL, 'Biro (Blue)', 'Blue biro pen', 100.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=600', 1000, 1, '2026-08-23 22:57:55'),
	(403, 16, NULL, 'Biro (Black)', 'Black biro pen', 100.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=600', 1000, 1, '2026-08-23 22:57:55'),
	(404, 16, NULL, 'Biro (Red)', 'Red biro pen', 100.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=600', 800, 1, '2026-08-23 22:57:55'),
	(405, 16, NULL, 'Biro Pack (10)', 'Pack of 10 biros', 800.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=600', 300, 1, '2026-08-23 22:57:55'),
	(406, 16, 6, 'Pen (Gel)', 'Gel pen', 200.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=600', 500, 1, '2026-08-23 22:57:55'),
	(407, 16, 6, 'Pen (Highlighter)', 'Highlighter pen', 250.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=600', 400, 1, '2026-08-23 22:57:55'),
	(408, 16, 6, 'Pencil (HB)', 'HB pencil', 100.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=600', 800, 1, '2026-08-23 22:57:55'),
	(409, 16, 6, 'Pencil Pack', 'Pack of 12 pencils', 800.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=600', 200, 1, '2026-08-23 22:57:55'),
	(410, 16, 6, 'Mechanical Pencil', 'Mechanical pencil', 400.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=600', 300, 1, '2026-08-23 22:57:55'),
	(411, 16, 6, 'Pencil Sharpener', 'Pencil sharpener', 150.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=600', 400, 1, '2026-08-23 22:57:55'),
	(412, 16, NULL, 'Eraser', 'Pencil eraser', 100.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Eraser', 500, 1, '2026-08-23 22:57:55'),
	(413, 16, NULL, 'Ruler (15cm)', '15cm ruler', 150.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Ruler%2B%2815cm%29', 400, 1, '2026-08-23 22:57:55'),
	(414, 16, NULL, 'Ruler (30cm)', '30cm ruler', 250.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Ruler%2B%2830cm%29', 300, 1, '2026-08-23 22:57:55'),
	(415, 16, 6, 'Calculator', 'Scientific calculator', 3500.00, 'https://images.unsplash.com/photo-1587145820266-a5951ee9683e?w=600', 100, 1, '2026-08-23 22:57:55'),
	(416, 16, 6, 'Calculator (Basic)', 'Basic calculator', 1500.00, 'https://images.unsplash.com/photo-1587145820266-a5951ee9683e?w=600', 150, 1, '2026-08-23 22:57:55'),
	(417, 16, NULL, 'Stapler', 'Office stapler', 1500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Stapler', 100, 1, '2026-08-23 22:57:55'),
	(418, 16, NULL, 'Stapler Pins', 'Stapler pins box', 300.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Stapler%2BPins', 300, 1, '2026-08-23 22:57:55'),
	(419, 16, 6, 'Paper Clips', 'Paper clips box', 200.00, 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=600', 400, 1, '2026-08-23 22:57:55'),
	(420, 16, NULL, 'Binder Clips', 'Binder clips pack', 400.00, 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=600', 250, 1, '2026-08-23 22:57:55'),
	(421, 16, NULL, 'Glue Stick', 'Glue stick', 300.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Glue%2BStick', 300, 1, '2026-08-23 22:57:55'),
	(422, 16, NULL, 'Cello Tape', 'Cello tape', 250.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Cello%2BTape', 350, 1, '2026-08-23 22:57:55'),
	(423, 16, NULL, 'Scissors', 'Office scissors', 500.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Scissors', 200, 1, '2026-08-23 22:57:55'),
	(424, 16, 6, 'A4 Paper (Ream)', 'A4 paper ream (500 sheets)', 4500.00, 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=600', 80, 1, '2026-08-23 22:57:55'),
	(425, 16, 6, 'A4 Paper (Pack)', 'A4 paper pack (100 sheets)', 1000.00, 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=600', 150, 1, '2026-08-23 22:57:55'),
	(426, 16, NULL, 'File Folder', 'File folder', 300.00, 'https://placehold.co/600x400/dc3545/ffffff?text=File%2BFolder', 300, 1, '2026-08-23 22:57:55'),
	(427, 16, NULL, 'Ring Binder', 'Ring binder', 1200.00, 'https://placehold.co/600x400/dc3545/ffffff?text=Ring%2BBinder', 100, 1, '2026-08-23 22:57:55');

-- Dumping structure for table campus_app.promo_codes
CREATE TABLE IF NOT EXISTS `promo_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `min_order` decimal(10,2) DEFAULT 0.00,
  `max_uses` int(11) DEFAULT 0,
  `applicable_to` enum('all','specific') DEFAULT 'all',
  `current_uses` int(11) DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.promo_codes: ~0 rows (approximately)
DELETE FROM `promo_codes`;

-- Dumping structure for table campus_app.promo_products
CREATE TABLE IF NOT EXISTS `promo_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `promo_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_promo_product` (`promo_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `promo_products_ibfk_1` FOREIGN KEY (`promo_id`) REFERENCES `promo_codes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promo_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.promo_products: ~0 rows (approximately)
DELETE FROM `promo_products`;

-- Dumping structure for table campus_app.referral_rewards
CREATE TABLE IF NOT EXISTS `referral_rewards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referrer_id` int(11) NOT NULL,
  `referee_id` int(11) NOT NULL,
  `reward_amount` decimal(10,2) NOT NULL,
  `order_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `referrer_id` (`referrer_id`),
  KEY `referee_id` (`referee_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `referral_rewards_ibfk_1` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `referral_rewards_ibfk_2` FOREIGN KEY (`referee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `referral_rewards_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.referral_rewards: ~0 rows (approximately)
DELETE FROM `referral_rewards`;

-- Dumping structure for table campus_app.reviews
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor` (`vendor_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.reviews: ~0 rows (approximately)
DELETE FROM `reviews`;

-- Dumping structure for table campus_app.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `hostel_address` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','rider','admin') DEFAULT 'student',
  `referral_code` varchar(20) DEFAULT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `referral_code` (`referral_code`),
  KEY `referred_by` (`referred_by`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`referred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.users: ~2 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `hostel_address`, `latitude`, `longitude`, `password`, `role`, `referral_code`, `referred_by`, `created_at`) VALUES
	(1, 'SIMON AWOJIDE', 'simple4real08@gmail.com', '07062641090', NULL, NULL, NULL, '$2y$10$BPod6o.Z36P8iVLDQ7N7BOaXZGNNZAoxG/.cNn/tgCp1E5jfZP06y', 'admin', NULL, NULL, '2026-08-23 22:25:24'),
	(2, 'Test Rider', 'rider@test.com', '08098765432', 'Campus Rider Hub', NULL, NULL, '$2y$10$Gp033PUZGGtw7sH9VG2H3.Nn9O72alc9KI9HYTwJwDCmusIA1LvQe', 'rider', NULL, NULL, '2026-08-24 10:14:39');

-- Dumping structure for table campus_app.vendors
CREATE TABLE IF NOT EXISTS `vendors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_name` varchar(100) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `location` varchar(100) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.vendors: ~19 rows (approximately)
DELETE FROM `vendors`;
INSERT INTO `vendors` (`id`, `shop_name`, `owner_name`, `email`, `password`, `phone`, `category`, `location`, `latitude`, `longitude`, `account_name`, `account_number`, `bank_name`, `wallet_balance`, `is_active`, `average_rating`, `total_reviews`) VALUES
	(1, 'Campus Suya King', 'Musa Ibrahim', 'suyaking@campus.ng', '$2y$10$W6QJV6g9ZpnZE3lXhsPiYud1TqHgXObkh2I8QcXuswa6XHIaTinly', '08012345678', 'Food', 'Male Hostel Area', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(2, 'Mama Nkechi Kitchen', 'Nkechi Okafor', NULL, NULL, NULL, 'Food', 'Female Hostel Area', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(3, 'BBQ Paradise', 'Chidi Ahmed', NULL, NULL, NULL, 'Food', 'Student Union Building', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(4, 'Rice & Stew Corner', 'Aisha Bello', NULL, NULL, NULL, 'Food', 'Faculty of Engineering', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(5, 'Fast Bites NG', 'Tunde Williams', NULL, NULL, NULL, 'Food', 'Library Area', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(6, 'Campus Mart', 'John Okonkwo', NULL, NULL, NULL, 'Provisions', 'Main Campus Road', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(7, 'Quick Shop', 'Fatima Hassan', NULL, NULL, NULL, 'Provisions', 'Hostel Junction', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(8, 'Mega Store', 'Emmanuel Osei', NULL, NULL, NULL, 'Provisions', 'Faculty of Science', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(9, 'Luxury Scents NG', 'Chioma Ade', NULL, NULL, NULL, 'Perfumes', 'Student Union Building', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(10, 'Beauty Palace', 'Zainab Mohammed', NULL, NULL, NULL, 'Cosmetics', 'Female Hostel', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(11, 'Fragrance Hub', 'David Eze', NULL, NULL, NULL, 'Perfumes', 'Male Hostel', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(12, 'Refreshment Zone', 'Ibrahim Sule', NULL, NULL, NULL, 'Snacks', 'Sports Complex', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(13, 'Snack Attack', 'Grace Nwosu', NULL, NULL, NULL, 'Snacks', 'Faculty of Arts', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(14, 'Tech Hub NG', 'Chuka Obi', NULL, NULL, NULL, 'Electronics', 'ICT Center', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(15, 'Gadget World', 'Amina Yusuf', NULL, NULL, NULL, 'Electronics', 'Faculty of Engineering', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(16, 'Book Haven', 'Peter Okoye', NULL, NULL, NULL, 'Books', 'Library', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(17, 'Stationery Plus', 'Blessing Edo', NULL, NULL, NULL, 'Stationery', 'Faculty of Social Sciences', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(18, 'Health First', 'Dr. Sarah Ahmed', NULL, NULL, NULL, 'Health', 'Medical Center', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0),
	(19, 'Fitness Zone', 'Mike Johnson', NULL, NULL, NULL, 'Sports', 'Gymnasium', NULL, NULL, NULL, NULL, NULL, 0.00, 1, 0.00, 0);

-- Dumping structure for table campus_app.wallet_transactions
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.wallet_transactions: ~0 rows (approximately)
DELETE FROM `wallet_transactions`;

-- Dumping structure for table campus_app.wallets
CREATE TABLE IF NOT EXISTS `wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.wallets: ~2 rows (approximately)
DELETE FROM `wallets`;
INSERT INTO `wallets` (`id`, `user_id`, `balance`) VALUES
	(1, 1, 29900.00),
	(2, 2, 0.00);

-- Dumping structure for table campus_app.withdrawal_requests
CREATE TABLE IF NOT EXISTS `withdrawal_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_id` (`vendor_id`),
  CONSTRAINT `withdrawal_requests_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table campus_app.withdrawal_requests: ~0 rows (approximately)
DELETE FROM `withdrawal_requests`;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
