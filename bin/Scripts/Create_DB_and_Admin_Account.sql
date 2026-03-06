- -----------------------------------------------------
-- Create RepairDashboard database.
-- -----------------------------------------------------

CREATE DATABASE IF NOT EXISTS RepairDashboard DEFAULT CHARACTER SET utf8 ;

-- -----------------------------------------------------
-- Set RepairDashboard as use database.
-- -----------------------------------------------------

USE RepairDashboard;

-- -----------------------------------------------------
-- Create RepairDashboard Admin user.
-- -----------------------------------------------------

CREATE USER 'RepairDasher'@'localhost' IDENTIFIED BY 'P@ssw0rdP@ssw0rd';
GRANT ALL PRIVILEGES ON `RepairDashboard`.* TO `RepairDasher`@`localhost`;
ALTER USER 'RepairDasher'@'localhost' REQUIRE NONE WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;

CREATE USER 'RepairDasher'@'%' IDENTIFIED BY 'P@ssw0rdP@ssw0rd';
GRANT ALL PRIVILEGES ON `RepairDashboard`.* TO `RepairDasher`@`%`;
ALTER USER 'RepairDasher'@'%' REQUIRE NONE WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0; 
