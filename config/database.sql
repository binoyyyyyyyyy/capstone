-- Courses
CREATE TABLE `coursetable` (
  `courseID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `courseName` VARCHAR(150) NOT NULL UNIQUE,
  PRIMARY KEY (`courseID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Majors
CREATE TABLE `majortable` (
  `majorID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `majorName` VARCHAR(150) NOT NULL,
  `majorCode` VARCHAR(50) NULL,
  PRIMARY KEY (`majorID`),
  UNIQUE KEY `uq_major_name` (`majorName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users
CREATE TABLE `UserTable` (
  `userID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `firstName` VARCHAR(100) NULL,
  `middleName` VARCHAR(100) NULL,
  `lastName` VARCHAR(100) NULL,
  `fullName` VARCHAR(200) NULL,
  `email` VARCHAR(190) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role_type` ENUM('admin', 'registrar', 'staff') NOT NULL DEFAULT 'staff',
  `userStatus` ENUM('active', 'inactive', 'pending', 'suspended') NOT NULL DEFAULT 'active',
  `added_by` VARCHAR(150) NULL,
  `edited_by` VARCHAR(150) NULL,
  `dateCreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateDeleted` DATETIME NULL,
  PRIMARY KEY (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Students
CREATE TABLE `StudentInformation` (
  `studentID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `studentNo` VARCHAR(50) NOT NULL UNIQUE,
  `firstName` VARCHAR(100) NOT NULL,
  `lastName` VARCHAR(100) NOT NULL,
  `middleName` VARCHAR(100) NULL,
  `birthDate` DATE NULL,
  `course_ID` INT UNSIGNED NOT NULL,
  `majorID` INT UNSIGNED NOT NULL,
  `contactNo` VARCHAR(50) NULL,
  `studentStatus` ENUM('Regular', 'Irregular', 'Transferee', 'Returnee', 'Graduated') NOT NULL DEFAULT 'Regular',
  `yearLevel` ENUM('1st Year', '2nd Year', '3rd Year', '4th Year') NULL,
  `added_by` VARCHAR(150) NULL,
  `edited_by` VARCHAR(150) NULL,
  `dateCreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateDeleted` DATETIME NULL,
  PRIMARY KEY (`studentID`),
  KEY `idx_student_course` (`course_ID`),
  KEY `idx_student_major` (`majorID`),
  CONSTRAINT `fk_student_course` FOREIGN KEY (`course_ID`) REFERENCES `coursetable` (`courseID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_student_major` FOREIGN KEY (`majorID`) REFERENCES `majortable` (`majorID`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Document types offered
CREATE TABLE `DocumentsType` (
  `documentID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `documentCode` VARCHAR(50) NOT NULL UNIQUE,
  `documentName` VARCHAR(150) NOT NULL,
  `documentDesc` TEXT NULL,
  `documentStatus` ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
  `procTime` ENUM('1 day', '2 days', '3 days', '1 week', '2 weeks', '1 month') NOT NULL,
  `dateDeleted` DATETIME NULL,
  PRIMARY KEY (`documentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Requests
CREATE TABLE `RequestTable` (
  `requestID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `requestCode` VARCHAR(80) NOT NULL UNIQUE,
  `documentID` INT UNSIGNED NOT NULL,
  `userID` INT UNSIGNED NOT NULL,
  `studentID` INT UNSIGNED NOT NULL,
  `dateRequest` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datePickUp` DATE NULL,
  `dateRelease` DATETIME NULL,
  `dateUpdated` DATETIME NULL,
  `requestStatus` ENUM('pending', 'approved', 'ready to pickup', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
  `authorizationImage` VARCHAR(255) NULL,
  `nameOfReceiver` VARCHAR(200) NULL,
  `relationship` VARCHAR(50) NULL,
  `email` VARCHAR(190) NULL,
  `edited_by` VARCHAR(150) NULL,
  `sverify` TINYINT(1) NOT NULL DEFAULT 0,
  `dateCreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateDeleted` DATETIME NULL,
  PRIMARY KEY (`requestID`),
  KEY `idx_request_student` (`studentID`),
  KEY `idx_request_document` (`documentID`),
  KEY `idx_request_user` (`userID`),
  CONSTRAINT `fk_request_document` FOREIGN KEY (`documentID`) REFERENCES `DocumentsType` (`documentID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_request_student` FOREIGN KEY (`studentID`) REFERENCES `StudentInformation` (`studentID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_request_user` FOREIGN KEY (`userID`) REFERENCES `UserTable` (`userID`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Uploaded images supporting requests
CREATE TABLE `supportingimage` (
  `supNo` VARCHAR(50) NOT NULL,
  `requestID` INT UNSIGNED NOT NULL,
  `image` VARCHAR(255) NULL,
  `additionalimage` VARCHAR(255) NULL,
  PRIMARY KEY (`supNo`),
  KEY `idx_sup_request` (`requestID`),
  CONSTRAINT `fk_sup_request` FOREIGN KEY (`requestID`) REFERENCES `RequestTable` (`requestID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API-only simple document list
CREATE TABLE `DocumentTable` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `documentName` VARCHAR(150) NOT NULL,
  `documentType` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;