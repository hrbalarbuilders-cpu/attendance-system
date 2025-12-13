-- Create geo_settings table for office location (geo-fencing)
-- Run this SQL in phpMyAdmin if table doesn't exist

CREATE TABLE IF NOT EXISTS `geo_settings` (
  `id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL DEFAULT 'Office',
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `radius_meters` int(11) NOT NULL DEFAULT 150,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default location (you can update via admin panel)
INSERT INTO `geo_settings` (`id`, `location_name`, `latitude`, `longitude`, `radius_meters`) 
VALUES (1, 'Office', 20.420399, 72.870863, 150)
ON DUPLICATE KEY UPDATE 
  `location_name` = VALUES(`location_name`),
  `latitude` = VALUES(`latitude`),
  `longitude` = VALUES(`longitude`),
  `radius_meters` = VALUES(`radius_meters`);


