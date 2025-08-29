-- Fix for RequestTable userID to allow NULL values
-- This allows students to submit requests without requiring admin login

-- First, drop the foreign key constraint
ALTER TABLE `RequestTable` DROP FOREIGN KEY `fk_request_user`;

-- Modify the userID column to allow NULL values
ALTER TABLE `RequestTable` MODIFY COLUMN `userID` INT UNSIGNED NULL;

-- Re-add the foreign key constraint with NULL handling
ALTER TABLE `RequestTable` ADD CONSTRAINT `fk_request_user` 
FOREIGN KEY (`userID`) REFERENCES `UserTable` (`userID`) 
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Add a comment explaining the purpose
ALTER TABLE `RequestTable` MODIFY COLUMN `userID` INT UNSIGNED NULL COMMENT 'Admin/Staff ID who processed the request. NULL if no admin was logged in during submission.';
