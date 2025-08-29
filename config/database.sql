-- Courses
CREATE TABLE `coursetable` (
  `courseID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `courseName` VARCHAR(150) NOT NULL UNIQUE,
  `courseDesc` TEXT DEFAULT NULL,
  `courseStatus` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`courseID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Majors
CREATE TABLE `majortable` (
  `majorID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `majorName` VARCHAR(150) NOT NULL UNIQUE,
  `majorDesc` TEXT DEFAULT NULL,
  `majorStatus` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`majorID`),
  UNIQUE KEY `uq_major_name` (`majorName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Users
CREATE TABLE `UserTable` (
  `userID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `firstName` VARCHAR(100) DEFAULT NULL,
  `middleName` VARCHAR(100) DEFAULT NULL,
  `lastName` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role_type` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  `userStatus` ENUM('active', 'pending') NOT NULL DEFAULT 'active',
  `dateCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateDeleted` TIMESTAMP NULL DEFAULT NULL,
  `added_by` VARCHAR(255) DEFAULT NULL,
  `edited_by` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Students
CREATE TABLE `StudentInformation` (
  `studentID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `studentNo` VARCHAR(50) NOT NULL UNIQUE,
  `course_ID` INT UNSIGNED NOT NULL,
  `majorID` INT UNSIGNED NOT NULL,
  `firstName` VARCHAR(100) NOT NULL,
  `lastName` VARCHAR(100) NOT NULL,
  `middleName` VARCHAR(100) DEFAULT NULL,
  `birthDate` DATE DEFAULT NULL,
  `studentStatus` ENUM('Regular', 'Irregular', 'Transferee', 'Returnee', 'Graduated') NOT NULL DEFAULT 'Regular',
  `yearLevel` ENUM('1st Year', '2nd Year', '3rd Year', '4th Year') NOT NULL,
  `contactNo` VARCHAR(20) DEFAULT NULL,
  `dateCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateDeleted` TIMESTAMP NULL DEFAULT NULL,
  `added_by` VARCHAR(255) DEFAULT NULL,
  `edited_by` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`studentID`),
  KEY `idx_student_course` (`course_ID`),
  KEY `idx_student_major` (`majorID`),
  CONSTRAINT `fk_student_course` FOREIGN KEY (`course_ID`) REFERENCES `coursetable` (`courseID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_student_major` FOREIGN KEY (`majorID`) REFERENCES `majortable` (`majorID`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Document Types
CREATE TABLE `DocumentsType` (
  `documentID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `documentCode` VARCHAR(50) NOT NULL UNIQUE,
  `documentName` VARCHAR(255) NOT NULL,
  `documentDesc` TEXT DEFAULT NULL,
  `documentStatus` ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
  `procTime` ENUM('1 day', '2 days', '3 days', '1 week', '2 weeks', '1 month') NOT NULL,
  `dateCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateDeleted` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`documentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Requests
CREATE TABLE `RequestTable` (
  `requestID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `requestCode` VARCHAR(80) NOT NULL UNIQUE,
  `documentID` INT UNSIGNED NOT NULL,
  `userID` INT UNSIGNED NOT NULL,
  `studentID` INT UNSIGNED NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `relationship` VARCHAR(255) NOT NULL,
  `dateRequest` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datePickUp` DATE DEFAULT NULL,
  `dateRelease` DATETIME DEFAULT NULL,
  `dateUpdated` DATETIME DEFAULT NULL,
  `requestStatus` ENUM('pending', 'approved', 'ready to pickup', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
  `authorizationImage` TEXT DEFAULT NULL,
  `nameOfReceiver` VARCHAR(255) DEFAULT NULL,
  `edited_by` VARCHAR(255) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `sVerify` TINYINT(1) DEFAULT 0,
  `dateCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateDeleted` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`requestID`),
  KEY `idx_request_document` (`documentID`),
  KEY `idx_request_user` (`userID`),
  KEY `idx_request_student` (`studentID`),
  CONSTRAINT `fk_request_document` FOREIGN KEY (`documentID`) REFERENCES `DocumentsType` (`documentID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_request_user` FOREIGN KEY (`userID`) REFERENCES `UserTable` (`userID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_request_student` FOREIGN KEY (`studentID`) REFERENCES `StudentInformation` (`studentID`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Supporting Images
CREATE TABLE `supportingimage` (
  `supID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supNo` VARCHAR(50) NOT NULL UNIQUE,
  `requestID` INT UNSIGNED NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `additionalimage` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`supID`),
  UNIQUE KEY `uq_supNo` (`supNo`),
  KEY `idx_sup_request` (`requestID`),
  CONSTRAINT `fk_sup_request` FOREIGN KEY (`requestID`) REFERENCES `RequestTable` (`requestID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- API-only simple document list
CREATE TABLE `DocumentTable` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `documentName` VARCHAR(150) NOT NULL,
  `documentType` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;