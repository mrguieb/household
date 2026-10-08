-- UPGRADE for existing installs (MariaDB, as bundled with XAMPP). Safe to run more than once.
USE household_db;
ALTER TABLE households ADD COLUMN IF NOT EXISTS household_code VARCHAR(20) NULL AFTER interviewer;
UPDATE households SET household_code=CONCAT('HH-',YEAR(created_at),'-',LPAD(id,4,'0')) WHERE household_code IS NULL;

CREATE TABLE IF NOT EXISTS audit_log(id INT AUTO_INCREMENT PRIMARY KEY,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,user_id INT NULL,username VARCHAR(50),action VARCHAR(12) NOT NULL,household_id INT NULL,details VARCHAR(1000),ip VARCHAR(45),INDEX(action),INDEX(username),INDEX(household_id),INDEX(created_at));
CREATE TABLE IF NOT EXISTS dup_ignore(gkey CHAR(32) PRIMARY KEY,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
