-- System Settings definition

CREATE TABLE `SystemSettings` (
	`SystemSettingsId` int(11) NOT NULL AUTO_INCREMENT,
	`Errors_Active` bit(1) NOT NULL COMMENT '0 for disabled errors or 1 for enabled errors',
	PRIMARY KEY (`SystemSettingsId`)
)ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO SystemSettings(Errors_Active) VALUES (1);

