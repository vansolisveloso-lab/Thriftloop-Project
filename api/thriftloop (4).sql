-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2026 at 08:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `thriftloop`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `province` varchar(100) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed','cancelled','refunded') DEFAULT 'pending',
  `payment_method` enum('online','cod') NOT NULL DEFAULT 'online',
  `order_status` enum('pending','processing','packed','shipped','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  `payment_reference` varchar(150) DEFAULT NULL,
  `tracking_number` varchar(150) DEFAULT NULL,
  `courier` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `first_name`, `last_name`, `email`, `phone`, `address`, `city`, `postal_code`, `province`, `subtotal`, `shipping_fee`, `total`, `payment_status`, `payment_method`, `order_status`, `payment_reference`, `tracking_number`, `courier`, `created_at`, `updated_at`) VALUES
(2, 'TL-2026-001', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09171234567', '123 Thrift St', 'Pasig', '1600', 'Metro Manila', 1398.00, 100.00, 1498.00, 'paid', 'online', 'delivered', NULL, NULL, NULL, '2026-09-22 12:12:37', '2026-09-22 12:27:05'),
(4, 'TL-2026-002', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09171234567', '123 Thrift St', 'Pasig', '1600', 'Metro Manila', 649.00, 100.00, 749.00, 'refunded', 'online', 'cancelled', NULL, NULL, NULL, '2026-09-22 12:17:01', '2026-09-22 14:13:40'),
(17, 'TL-2026-003', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Cavite', 1398.00, 100.00, 1498.00, 'pending', 'online', 'delivered', NULL, NULL, NULL, '2026-09-23 00:34:48', '2026-09-23 01:08:51'),
(18, 'TL-2026-004', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09874561235', 'blk 1 lot 2', 'dsfsfsfs', '32424', 'Cavite', 1997.00, 100.00, 2097.00, 'pending', 'online', 'pending', NULL, NULL, NULL, '2026-09-23 01:12:32', '2026-09-23 01:12:32'),
(19, 'TL-2026-005', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Cavite', 499.00, 100.00, 599.00, 'pending', 'online', 'pending', NULL, NULL, NULL, '2026-09-23 03:30:10', '2026-09-23 03:30:10'),
(20, 'TL-2026-006', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Cavite', 499.00, 100.00, 599.00, 'pending', 'online', 'cancelled', NULL, NULL, NULL, '2026-09-23 04:09:41', '2026-09-23 06:31:10'),
(21, 'TL-2026-007', 'kaizem', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Laguna', 799.00, 100.00, 899.00, 'pending', 'online', 'cancelled', NULL, 'FL01923841324', 'FLASH EXPRESS', '2026-09-23 04:11:04', '2026-09-23 06:31:08'),
(22, 'TL-2026-008', 'kaizen kenn', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Laguna', 1498.00, 100.00, 1598.00, 'paid', 'online', 'delivered', NULL, NULL, NULL, '2026-09-23 04:14:49', '2026-09-23 04:27:07'),
(23, 'TL-2026-009', 'ivan', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Batangas', 649.00, 100.00, 749.00, 'pending', 'online', 'delivered', NULL, '9182301824', 'LALAMOG', '2026-09-23 05:08:25', '2026-09-23 05:48:46'),
(24, 'TL-2026-010', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Batangas', 300.00, 100.00, 400.00, 'pending', 'online', 'cancelled', NULL, NULL, NULL, '2026-09-23 06:15:48', '2026-09-23 06:31:03'),
(25, 'TL-2026-011', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Batangas', 899.00, 100.00, 999.00, 'pending', 'online', 'cancelled', NULL, NULL, NULL, '2026-09-23 06:19:22', '2026-09-23 06:31:00'),
(26, 'TL-2026-012', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Batangas', 22323.00, 100.00, 22423.00, 'pending', 'online', 'cancelled', NULL, NULL, NULL, '2026-09-23 06:24:12', '2026-09-23 06:30:58'),
(27, 'TL-2026-013', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Batangas', 799.80, 100.00, 899.80, 'pending', 'online', 'pending', NULL, '1515626655', 'lalamog', '2026-09-23 06:33:54', '2026-09-24 05:54:22'),
(28, 'TL-2026-014', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Batangas', 500.00, 100.00, 600.00, 'pending', 'online', 'pending', NULL, NULL, NULL, '2026-09-24 05:56:19', '2026-09-24 05:56:19'),
(29, 'TL-2026-015', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Batangas', 499.00, 100.00, 599.00, 'paid', 'online', 'processing', NULL, '815549516519', 'lalamog', '2026-09-24 08:00:47', '2026-09-26 00:48:49'),
(30, 'TL-2026-016', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Cavite', 499.00, 100.00, 599.00, 'pending', 'online', 'cancelled', NULL, NULL, NULL, '2026-09-26 00:47:41', '2026-09-26 00:56:17'),
(31, 'TL-2026-017', 'Juan', 'Dela Cruz', 'juan@gmail.com', '09063384288', 'Sunny Brooke', 'Gen trias', '4108', 'Cavite', 959647.00, 100.00, 959747.00, 'paid', 'online', 'shipped', NULL, '12525455165', 'lalamove', '2026-09-26 01:14:49', '2026-09-26 01:17:04'),
(39, 'TL-2026-018', 'John Carlo', 'Dinco', 'jcdinco69@gmail.com', '09063384256', 'Lhinette', 'Tanza', '4108', 'Cavite', 899.00, 100.00, 999.00, 'paid', 'online', 'pending', 'cs_a3ad0e7a37137e9a02ff87bd', NULL, NULL, '2026-10-07 12:17:51', '2026-10-07 12:25:10');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`) VALUES
(2, 2, 1, 'Classic T-Shirt', 499.00, 1, 499.00),
(3, 2, 2, 'Denim Jacket', 899.00, 1, 899.00),
(5, 4, 5, 'Vintage Polo', 649.00, 1, 649.00),
(18, 17, 2, 'Denim Jacket', 899.00, 1, 899.00),
(19, 17, 1, 'Classic T-Shirt', 499.00, 1, 499.00),
(20, 18, 1, 'Classic T-Shirt', 499.00, 1, 499.00),
(21, 18, 2, 'Denim Jacket', 899.00, 1, 899.00),
(22, 18, 3, 'Graphic Tee', 599.00, 1, 599.00),
(23, 19, 1, 'Classic T-Shirt', 499.00, 1, 499.00),
(24, 20, 1, 'Classic T-Shirt', 499.00, 1, 499.00),
(25, 21, 4, 'Cargo Pants', 799.00, 1, 799.00),
(26, 22, 3, 'Graphic Tee', 599.00, 1, 599.00),
(27, 22, 16, 'polo', 899.00, 1, 899.00),
(28, 23, 5, 'Vintage Polo', 649.00, 1, 649.00),
(29, 24, 15, 'polo', 300.00, 1, 300.00),
(30, 25, 2, 'Denim Jacket', 899.00, 1, 899.00),
(31, 26, 14, 'asdasasd', 22323.00, 1, 22323.00),
(32, 27, 17, 'jacket', 799.80, 1, 799.80),
(33, 28, 18, 'hoodie', 500.00, 1, 500.00),
(34, 29, 19, 'STRIPES POLOSHIRT', 499.00, 1, 499.00),
(35, 30, 19, 'STRIPES POLOSHIRT', 499.00, 1, 499.00),
(36, 31, 19, 'STRIPES POLOSHIRT', 499.00, 1, 499.00),
(37, 31, 20, 'blelasd', 959148.00, 1, 959148.00),
(45, 39, 21, 'CARGO PANTS', 899.00, 1, 899.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 1,
  `image_url` varchar(500) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `status` enum('available','sold','inactive') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `name`, `category`, `description`, `price`, `stock`, `image_url`, `type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'TL-001', 'Classic T-Shirt', 'TOPS / PRE-LOVED', 'Classic pre-loved everyday t-shirt.', 499.00, 1, '/products/tl-001.jpg', 'CLASSIC', 'sold', '2026-09-22 04:25:19', '2026-09-23 06:31:10'),
(2, 'TL-002', 'Denim Jacket', 'OUTERWEAR / VINTAGE', 'Vintage denim jacket.', 899.00, 1, '/products/tl-002.jpg', 'DENIM', 'sold', '2026-09-22 04:25:19', '2026-09-23 06:31:00'),
(3, 'TL-003', 'Graphic Tee', 'TOPS / Y2K', 'Pre-loved graphic t-shirt.', 599.00, 0, '/products/tl-003.jpg', 'GRAPHIC', 'sold', '2026-09-22 04:25:19', '2026-09-23 04:14:49'),
(4, 'TL-004', 'Cargo Pants', 'BOTTOMS / UTILITY', 'Utility style cargo pants.', 799.00, 1, '/products/tl-004.webp', 'CARGO', 'sold', '2026-09-22 04:25:19', '2026-09-23 06:31:08'),
(5, 'TL-005', 'Vintage Polo', 'TOPS / RETRO', 'Retro vintage polo shirt.', 649.00, 0, '/products/tl-005.jpg', 'POLO', 'sold', '2026-09-22 04:25:19', '2026-09-23 05:08:25'),
(14, 'TL-006', 'asdasasd', 'as', 'asd', 22323.00, 1, NULL, 'as', 'sold', '2026-09-23 00:15:58', '2026-09-23 06:30:58'),
(15, 'TL-007', 'polo', 'top', 'clean', 300.00, 1, '/products/tl-007.jpg', 'classic', 'sold', '2026-09-23 00:20:48', '2026-09-23 06:31:03'),
(16, 'TL-008', 'polo', 'top', 'neat, st', 899.00, 0, '/products/tl-008.jpg', 'classic', 'sold', '2026-09-23 04:12:06', '2026-09-23 04:14:49'),
(17, 'TL-009', 'jacket', 'hoodie', '300gsm', 799.80, 0, NULL, 'pinoy', 'sold', '2026-09-23 06:33:16', '2026-09-23 06:33:54'),
(18, 'TL-010', 'hoodie', 'tops', 'hood', 500.00, 0, '/products/tl-010.jpg', 'vintage', 'sold', '2026-09-24 05:55:31', '2026-09-24 05:56:19'),
(19, 'TL-011', 'STRIPES POLOSHIRT', 'POLOSHIRT', 'BADING', 499.00, 7, '/products/tl-011.jpg', 'CLASSIC', 'available', '2026-09-24 05:58:42', '2026-09-26 01:14:49'),
(20, 'TL-012', 'blelasd', 'asd', 'blaba', 959148.00, 0, NULL, 'dassaf', 'sold', '2026-09-24 07:29:21', '2026-09-26 01:14:49'),
(21, 'TL-013', 'CARGO PANTS', 'PANTS', 'BAGGY PANTS FOR TEENS', 899.00, 1, '/products/tl-013.jpg', 'BAGGY', 'sold', '2026-09-24 07:31:02', '2026-10-07 12:17:51'),
(22, 'TL-014', 'POLO', 'TOP', NULL, 498.63, 1, NULL, 'LOOSE', 'available', '2026-09-24 07:31:27', '2026-09-24 07:31:27'),
(23, 'TL-015', 'nyak', 'top', 'almost new', 200.00, 1, NULL, 'classic', 'available', '2026-09-26 00:56:10', '2026-09-26 00:56:10'),
(35, 'TL-016', 'Addidas Shoes for men', 'Shoes', 'Masarap sa paa', 300.00, 1, '/products/tl-016.jpg', 'Rubber shoes', 'available', '2026-10-07 14:36:58', '2026-10-07 14:36:58');

-- --------------------------------------------------------

--
-- Table structure for table `social_posts`
--

CREATE TABLE `social_posts` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `post_id` varchar(150) DEFAULT NULL,
  `post_url` varchar(500) DEFAULT NULL,
  `status` enum('pending','posted','failed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tracking_events`
--

CREATE TABLE `tracking_events` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `google_id`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ThriftLoop', 'Admin', 'admin@thriftloop.com', '$2y$10$PXV6z/MAY/Ntz51AR/86jOalFfAFbohsCejQxu6/D83QjnkLv0p3a', NULL, 'admin', 'active', '2026-09-22 10:08:14', '2026-09-22 10:08:14'),
(2, 'Juan', 'Dela Cruz', 'juan@gmail.com', '$2y$10$dTu42JWwr94kRRbLRUXfMup0r6F2ewNEFrEKSVVsF8zbsps6gE8iC', NULL, 'customer', 'active', '2026-09-22 10:08:22', '2026-09-22 10:25:40'),
(7, 'Ivan', 'Strong', 'ivanstrong@gmail.com', '$2y$10$mBhkM9oh6f.hGwYeeDbd1upOUuivHvjhHoE1pR1Cn2qwvjd2bfq26', NULL, 'customer', 'active', '2026-09-22 14:01:10', '2026-09-22 14:01:10'),
(14, 'John Carlo', '', 'jcdinco69@gmail.com', '$2y$10$qu4NAO42xr4nBwzrnxcgfuljtltlnuMtzfQTPM9VVmCmq.Fivt1tm', '113646599861217091551', 'customer', 'active', '2026-10-07 10:05:15', '2026-10-07 10:05:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`);

--
-- Indexes for table `social_posts`
--
ALTER TABLE `social_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tracking_events`
--
ALTER TABLE `tracking_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `users_google_id` (`google_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `social_posts`
--
ALTER TABLE `social_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tracking_events`
--
ALTER TABLE `tracking_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `social_posts`
--
ALTER TABLE `social_posts`
  ADD CONSTRAINT `social_posts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tracking_events`
--
ALTER TABLE `tracking_events`
  ADD CONSTRAINT `tracking_events_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
