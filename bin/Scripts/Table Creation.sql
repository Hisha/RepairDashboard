-- System Settings definition

CREATE TABLE `SystemSettings` (
	`SystemSettingsId` int(11) NOT NULL AUTO_INCREMENT,
	`Errors_Active` bit(1) NOT NULL COMMENT '0 for disabled errors or 1 for enabled errors',
	PRIMARY KEY (`SystemSettingsId`)
)ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO SystemSettings(Errors_Active) VALUES (1);

-- Excel List definition 

CREATE TABLE `SYS_excellist` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `excel_name` VARCHAR(255) NOT NULL,
    `table_name` VARCHAR(255) NOT NULL,
    `header_row` INT NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_excel_name` (`excel_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Batteries', 'batteries', 3, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('CAV REQUISITIONS', 'cav_requisitions', 1, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('DRMO', 'drmo', 4, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Installed', 'installed', 3, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Inventory', 'inventory', 3, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Receipts', 'receipts', 4, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Repairs', 'repairs', 3, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Shipments', 'shipments', 3, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Procurement Tracker2026', 'procurements', 1, 1);

-- Program Mapping

CREATE TABLE SYS_program_mapping (
   source_program VARCHAR(50) PRIMARY KEY,
   normalized_program VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('EDL','EL1/CANES');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('EL1','EL1/CANES');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('EL1/CANES','EL1/CANES');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('ATFP','EFJ');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('EFJ','EFJ');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('SIWCS','EFJ');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('SIWCS/EFJ','EFJ');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('SIWCS/ATFP','EFJ');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('ANM','EFJ');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('ANQ','EFJ');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('HYDRA','EFJ');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('EGY','EFJ');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('EGH','EFJ');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('AJ5','AJ5');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('JXA/N94','JXA/N94');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('AAL/Q70','AAL/Q70');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`) VALUES('BC5','BC5');

-- Repair Program Mapping

CREATE TABLE SYS_repair_program_mapping (
   source_program VARCHAR(50) PRIMARY KEY,
   normalized_program VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`) VALUES('LIPTM00252_PBL_DVDBM','EL1/CANES');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`) VALUES('LIPTM00252_PBL_DVDKW','BC5');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`) VALUES('LIPTM00252_PBL_NTCSS','AJ5');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`) VALUES('LIPTM00252_PBL_SIWCS','EFJ');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`) VALUES('LIPTM00252_PBL_HYDRA','EFJ');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`) VALUES('LIPTM00252_PBL_N94','JXA/N94');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`) VALUES('LIPTM00252_PBL_CANES','EL1/CANES');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`) VALUES('LIPTM00252_PBL_Q70','AAL/Q70');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`) VALUES('LIPTM00252_PBW_Q70','AAL/Q70');

-- Power Point Filler
CREATE TABLE `SYS_powerpoint_filler` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`program` VARCHAR(50) NOT NULL,
	`title` VARCHAR(50) NOT NULL,
	`pm` VARCHAR(50) NOT NULL,
	`programname` VARCHAR(50) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_program` (`program`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('AAL/Q70','C4I PBL-O METRICS','--','--');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('AJ5','C4I PBL-O METRICS','Kyle Youtz','AJ5');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('BC5','C4I PBL-O METRICS','Dan Miller','BC5');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('EFJ','C4I PBL-O METRICS','Melissa Trusch','EFJ');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('EL1/CANES','C4I PBL-O METRICS','Dane Nearhoof','EL1/EDL');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('JXA/N94','C4I PBL-O METRICS','Jenna Pickings','N94');

-- Last Update Table --

CREATE TABLE SYS_last_update (
    id INT NOT NULL AUTO_INCREMENT,
    updatefield VARCHAR(50) NOT NULL,
    uploaddate DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_updatefield (updatefield)
);

