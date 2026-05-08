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
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('CAV REQUISITIONS', 'cav_requisitions_north', 1, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('DRMO', 'drmo_inventory', 4, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Installed', 'installed', 3, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Inventory', 'inventory', 3, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Receipts', 'receipts', 4, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Repairs', 'repairs', 3, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Shipments', 'shipments', 3, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('Procurement Tracker2026', 'procurements', 1, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('CAV REQUISITIONS SOUTH', 'cav_requisitions_south', 1, 1);
INSERT INTO `SYS_excellist` (`excel_name`, `table_name`, `header_row`, `is_active`) VALUES ('DRMO NONInventory', 'drmo_noninventory', 4, 1);

-- Program Mapping

CREATE TABLE SYS_program_mapping (
   source_program VARCHAR(50) PRIMARY KEY,
   normalized_program VARCHAR(50) NOT NULL,
   north_south VARCHAR(5) NOT NULL DEFAULT 'north',
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('EDL','EL1/CANES','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('EL1','EL1/CANES','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('EL1/CANES','EL1/CANES','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('ATFP','EFJ','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('EFJ','EFJ','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('SIWCS','EFJ','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('SIWCS/EFJ','EFJ','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('SIWCS/ATFP','EFJ','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('ANM','EFJ','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('ANQ','EFJ','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('HYDRA','EFJ','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('EGY','EFJ','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('EGH','EFJ','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('AJ5','AJ5','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('JXA/N94','JXA/N94','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('AAL/Q70','AAL/Q70','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('BC5','BC5','north');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('CNBW','SSEE INC E/F','south');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('CNVV','SCCTV','south');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('CPBQ','NAVMACS','south');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('CPFW','URC109','south');
INSERT INTO `SYS_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('CPYG','DEPOT','south');

-- Repair Program Mapping

CREATE TABLE SYS_repair_program_mapping (
   source_program VARCHAR(50) PRIMARY KEY,
   normalized_program VARCHAR(50) NOT NULL
   north_south VARCHAR(5) NOT NULL DEFAULT 'north'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('LIPTM00252_PBL_DVDBM','EL1/CANES','north');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('LIPTM00252_PBL_DVDKW','BC5','north');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('LIPTM00252_PBL_NTCSS','AJ5','north');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('LIPTM00252_PBL_SIWCS','EFJ','north');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('LIPTM00252_PBL_HYDRA','EFJ','north');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('LIPTM00252_PBL_N94','JXA/N94','north');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('LIPTM00252_PBL_CANES','EL1/CANES','north');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('LIPTM00252_PBL_Q70','AAL/Q70','north');
INSERT INTO `SYS_repair_program_mapping` (`source_program`, `normalized_program`, `north_south`) VALUES('LIPTM00252_PBW_Q70','AAL/Q70','north');

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

INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('AAL/Q70','C4I PBL-O METRICS','Mike Patterson','Q70');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('AJ5','C4I PBL-O METRICS','Kyle Youtz','AJ5');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('BC5','C4I PBL-O METRICS','Dan Miller','BC5');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('EFJ','C4I PBL-O METRICS','Melissa Trusch','EFJ');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('EL1/CANES','C4I PBL-O METRICS','Dane Nearhoof','EL1/EDL');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('JXA/N94','C4I PBL-O METRICS','Jenna Pickings','N94');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('DEPOT','PBL-O METRICS','- -','DEPOT');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('SSEE INC E/F','PBL-O METRICS','- -','SSEE INC E/F');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('SCCTV','PBL-O METRICS','- -','SCCTV');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('NAVMACS','PBL-O METRICS','- -','NAVMACS');
INSERT INTO `SYS_powerpoint_filler` (`program`, `title`, `pm`, `programname`) VALUES ('URC109','PBL-O METRICS','- -','URC109');

-- Last Update Table --

CREATE TABLE SYS_last_update (
    id INT NOT NULL AUTO_INCREMENT,
    updatefield VARCHAR(50) NOT NULL,
    uploaddate DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_updatefield (updatefield)
);

-- Drive Destruction Log Table --

CREATE TABLE drive_destruction_log (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Item Info
    part_number VARCHAR(100) NOT NULL,
    serial_number VARCHAR(100) NOT NULL,
    niin VARCHAR(20) NULL,
    description VARCHAR(255) NULL,
    quantity INT DEFAULT 1,

    -- Destruction Info
    destruction_method ENUM('Degauss', 'Punch', 'Both', 'Shredded') NOT NULL,
    destruction_date DATE NOT NULL,

    -- Destroyer Signoff
    destroyer_name VARCHAR(100) NULL,
    destroyer_signature_path VARCHAR(255) NULL,
    destroyer_signed_at DATETIME NULL,

    -- Witness / Approver Signoff
    witness_name VARCHAR(100) NULL,
    witness_signature_path VARCHAR(255) NULL,
    witness_signed_at DATETIME NULL,

    -- Status / Notes
    status ENUM('Pending', 'Partially Signed', 'Completed', 'Voided') DEFAULT 'Pending',
    notes TEXT NULL,

    -- Void Tracking (Audit Critical)
    void_reason TEXT NULL,
    voided_by VARCHAR(100) NULL,
    voided_at DATETIME NULL,

    -- Tracking
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100) NOT NULL DEFAULT '',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Drive Destruction Audit Table --

CREATE TABLE drive_destruction_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    performed_by VARCHAR(100) NULL,
    action_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    details TEXT NULL,

    FOREIGN KEY (record_id) REFERENCES drive_destruction_log(id)
);

-- Add indexes--

CREATE INDEX idx_destruction_date ON drive_destruction_log(destruction_date);
CREATE INDEX idx_destroyer_name ON drive_destruction_log(destroyer_name);
CREATE INDEX idx_witness_name ON drive_destruction_log(witness_name);
CREATE INDEX idx_status ON drive_destruction_log(status);


-------Views !!!!Make sure tables exist before trying to make!!!!!----------

CREATE VIEW cav_requisitions AS
SELECT * FROM cav_requisitions_north
UNION ALL
SELECT * FROM cav_requisitions_south;



CREATE OR REPLACE VIEW drmo AS
SELECT
    di.date,
    di.document_number,
    di.niin,
    di.part,
    di.nomenclature,
    di.qty,
    di.unit_price,
    COALESCE(l.lrc, di.program) AS program
FROM drmo_inventory di
LEFT JOIN LMS21Data l
    ON di.niin = l.niin

UNION ALL

SELECT
    ni.date,
    ni.document_number,
    ni.niin,
    ni.part,
    ni.nomenclature,
    ni.qty,
    ni.unit_price,
    COALESCE(l.lrc, ni.program) AS program
FROM drmo_noninventory ni
LEFT JOIN LMS21Data l
    ON ni.niin = l.niin
WHERE NOT EXISTS (
    SELECT 1
    FROM drmo_inventory di2
    WHERE di2.document_number = ni.document_number
);

CREATE INDEX idx_lms21data_niin ON LMS21Data(niin);
CREATE INDEX idx_drmo_inventory_docno ON drmo_inventory(document_number);
CREATE INDEX idx_drmo_noninventory_docno ON drmo_noninventory(document_number);
