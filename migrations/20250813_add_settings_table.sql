CREATE TABLE IF NOT EXISTS settings (
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


