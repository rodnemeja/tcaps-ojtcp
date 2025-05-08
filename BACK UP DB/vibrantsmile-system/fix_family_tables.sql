-- Check and recreate family_codes table
DROP TABLE IF EXISTS `family_codes`;

CREATE TABLE `family_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add sample data
INSERT INTO `family_codes` (`id`, `code`, `name`, `created_by`, `created_at`) 
VALUES (1, 'JJQBSY', 'MEJA', 52, '2025-03-23 05:04:58');

-- Make sure patients table has family_code and family_role columns
ALTER TABLE `patients` 
MODIFY COLUMN `family_code` varchar(10) DEFAULT NULL AFTER `zipcode`,
MODIFY COLUMN `family_role` varchar(50) DEFAULT NULL AFTER `family_code`;

-- Update patient data
UPDATE `patients` SET 
  `family_code` = 'JJQBSY', 
  `family_role` = 'Sibling' 
WHERE `id` = 53;

UPDATE `patients` SET 
  `family_code` = 'JJQBSY', 
  `family_role` = 'Child' 
WHERE `id` = 54;

UPDATE `patients` SET 
  `family_code` = 'JJQBSY', 
  `family_role` = 'Sibling' 
WHERE `id` = 55;

-- Add foreign key constraint
ALTER TABLE `family_codes` 
DROP FOREIGN KEY IF EXISTS `family_codes_ibfk_1`;

-- Add the constraint back
ALTER TABLE `family_codes` 
ADD CONSTRAINT `family_codes_ibfk_1` 
FOREIGN KEY (`created_by`) REFERENCES `patients` (`id`) ON DELETE CASCADE; 