-- Runs once, on the first start of an empty MySQL volume.
CREATE DATABASE IF NOT EXISTS `ctp_testing` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `ctp_legacy` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
GRANT ALL PRIVILEGES ON `ctp_testing`.* TO 'ctp'@'%';
GRANT ALL PRIVILEGES ON `ctp_legacy`.* TO 'ctp'@'%';
FLUSH PRIVILEGES;
