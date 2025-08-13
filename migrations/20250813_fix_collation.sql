-- Normalize collations to utf8mb4_unicode_ci
ALTER DATABASE `ecommerce_admin` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Helper: apply to key tables used in new features
ALTER TABLE `orders` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `customers` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `feedback` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `reviews` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `coupons` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

