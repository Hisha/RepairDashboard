-- System Settings definition

CREATE TABLE `SystemSettings` (
	`SystemSettingsId` int(11) NOT NULL AUTO_INCREMENT,
	`Errors_Active` bit(1) NOT NULL COMMENT '0 for disabled errors or 1 for enabled errors',
	PRIMARY KEY (`SystemSettingsId`)
)ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO SystemSettings(Errors_Active) VALUES (1);

-- Excel List definition 

CREATE TABLE `excellist` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `excel_name` VARCHAR(255) NOT NULL,
    `table_name` VARCHAR(255) NOT NULL,
    `header_row` INT NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_excel_name` (`excel_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('CAV REQUISITIONS', 'cav_requisitions', 1, 1);
INSERT INTO `excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Inventory', 'inventory', 3, 1);
INSERT INTO `excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Batteries', 'batteries', 3, 1);
INSERT INTO `excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('DRMO', 'drmo', 4, 1);
