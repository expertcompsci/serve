-- MySQL dump 10.16  Distrib 10.3.8-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: psalteco_psalte
-- ------------------------------------------------------
-- Server version	10.3.8-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `db_properties`
--

DROP TABLE IF EXISTS `db_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `db_properties` (
  `database_name` varchar(50) DEFAULT NULL COMMENT 'Human readable name uniqueness across databases not enforced but recommended.',
  `is_test` tinyint(1) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Only latest row is considered current.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employers`
--

DROP TABLE IF EXISTS `employers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(250) NOT NULL,
  `is_principle` tinyint(1) unsigned DEFAULT NULL COMMENT 'Y/N Is primarily an employer (not reqruiter)',
  `notes` text DEFAULT NULL,
  `employs_it` tinyint(1) unsigned DEFAULT NULL COMMENT 'Y/N Does employ IT workers',
  `site_url` varchar(250) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employers_company_name_key` (`company_name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COMMENT='Employers can be the receipent of zero or more submissions. An employer can be a recruiter which can make a submission on behalf of the user.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `event_log`
--

DROP TABLE IF EXISTS `event_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `standard_name` varchar(250) DEFAULT NULL COMMENT 'NULL if this is a user defined event',
  `notes` text DEFAULT NULL,
  `employer_id` int(11) DEFAULT NULL COMMENT 'Optional. Null if employer since deleted',
  `job_board_id` int(11) DEFAULT NULL COMMENT 'Optional. Null if job board since deleted',
  `job_ad_id` int(11) DEFAULT NULL COMMENT 'Optional. Null if job ad since deleted',
  `resume_id` int(11) DEFAULT NULL COMMENT 'Optional. Null if resume since deleted',
  `letter_id` int(11) DEFAULT NULL,
  `submission_id` int(11) DEFAULT NULL,
  `event_datetime` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Don''t use created datetime',
  `operation` set('insert','update','delete') NOT NULL COMMENT 'Set: insert, update, delete',
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_event_log_employers_id` (`employer_id`),
  KEY `fk_event_log_job_board_id` (`job_board_id`),
  KEY `event_log_standard_name_key` (`standard_name`),
  KEY `fk_event_log_job_ad_id` (`job_ad_id`),
  KEY `fk_event_log_resume_id` (`resume_id`),
  CONSTRAINT `fk_event_log_employers_id` FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_event_log_job_ad_id` FOREIGN KEY (`job_ad_id`) REFERENCES `job_ads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_event_log_job_board_id` FOREIGN KEY (`job_board_id`) REFERENCES `job_boards` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_event_log_resume_id` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8 COMMENT='Event log records creation of one of employer, job board, job ad, resume, submission or any user defined event useful for review by the user. In each row exactly one foreign key refrencing any of the aformentioned entities must be non null as that indicates the event type. Each of those entities has a title, name, or purpose etc. that is used as the name of the event that serves as a que to remind the user of the nature of the event.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_content` mediumblob NOT NULL,
  `resume_id` int(11) DEFAULT NULL,
  `letter_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_files_resume_id` (`resume_id`),
  KEY `fk_files_letter_id` (`letter_id`),
  CONSTRAINT `fk_files_letter_id` FOREIGN KEY (`letter_id`) REFERENCES `letters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_files_resume_id` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COMMENT='A separate table for blob type column as searching tables with blob columns is slow. Only id and file_content columns should be part of this table. Created or modify data/times should come from the referencing table.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_ads`
--

DROP TABLE IF EXISTS `job_ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_content` text NOT NULL,
  `title` varchar(250) NOT NULL,
  `notes` text DEFAULT NULL,
  `posted_datetime` datetime DEFAULT NULL COMMENT 'Defaults to created but user should always be made aware of that',
  `source` varchar(250) DEFAULT NULL COMMENT 'e.g. job board, the entity that placed the ad',
  `source_url` varchar(250) DEFAULT NULL COMMENT 'Where ad was seen, not address of advertiser',
  `by_email` tinyint(1) unsigned DEFAULT NULL COMMENT 'Y/N Did ad arrive by email?',
  `employer_id` int(11) DEFAULT NULL COMMENT 'Optional already listed employer',
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_job_ads_employer_id` (`employer_id`),
  CONSTRAINT `fk_job_ads_employer_id` FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='A job ad is discovered on a job board. A job board can even be word of mouth of a person listed in the job board notes/name. ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_boards`
--

DROP TABLE IF EXISTS `job_boards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_boards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL DEFAULT '0',
  `notes` text DEFAULT NULL,
  `site_url` varchar(250) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_boards_name_key` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Job board can be the source or lead of a submission (see submissions)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `letters`
--

DROP TABLE IF EXISTS `letters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `letters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `files_id` int(11) NOT NULL,
  `breadcrumb` int(11) DEFAULT NULL,
  `updated_to` int(11) DEFAULT NULL,
  `updated_from` int(11) DEFAULT NULL,
  `revision_count` int(11) NOT NULL DEFAULT 1,
  `description` varchar(250) NOT NULL,
  `filename` varchar(250) NOT NULL,
  `notes` text DEFAULT NULL,
  `original_basename` varchar(250) NOT NULL,
  `extension` varchar(250) NOT NULL,
  `content_type` varchar(250) NOT NULL,
  `size` int(11) NOT NULL,
  `last_modified` datetime NOT NULL,
  `modified` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_letters_files_id` (`files_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='Cover letters can be used as part of a submission';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `resumes`
--

DROP TABLE IF EXISTS `resumes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resumes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `breadcrumb` int(11) DEFAULT NULL,
  `updated_to` int(11) DEFAULT NULL COMMENT 'resume.id of next newer version of this resume. Null if this resume is the latest version.',
  `updated_from` int(11) DEFAULT NULL COMMENT 'resume.id of the previous version of this resume. Null if this resume is the original version.',
  `revision_count` int(11) NOT NULL DEFAULT 1,
  `filename` varchar(250) NOT NULL,
  `purpose` varchar(250) DEFAULT NULL,
  `original_basename` varchar(250) NOT NULL,
  `extension` varchar(250) NOT NULL,
  `files_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `content_type` varchar(250) NOT NULL,
  `size` int(11) NOT NULL,
  `last_modified` datetime NOT NULL COMMENT 'Datetime of last file modification known at upload time. Not to be confused with modified datetime of this tables rows.',
  `modified` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `resumes_purpose_extension_files_id_key` (`purpose`,`extension`,`files_id`),
  KEY `resumes_files_key` (`files_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='Resumes serves as an index into the files table which stores resume content as a BLOB. Tables with BLOB column(s) are slow to search so search and retrieval ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `submissions`
--

DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `submissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `position` varchar(250) NOT NULL,
  `is_rejected` tinyint(1) unsigned DEFAULT NULL,
  `employer_id` int(11) DEFAULT NULL COMMENT 'Must be associated with an employer',
  `job_ad_id` int(11) DEFAULT NULL COMMENT 'Optionally associated with a job ad',
  `resume_id` int(11) DEFAULT NULL COMMENT 'Must include a resume',
  `letter_id` int(11) DEFAULT NULL COMMENT 'Optional cover letter',
  `contact_position` varchar(250) DEFAULT NULL COMMENT 'Job title or position',
  `contact_first` varchar(250) DEFAULT NULL,
  `contact_last` varchar(250) DEFAULT NULL,
  `contact_initial` varchar(80) DEFAULT NULL,
  `contact_prefix` varchar(80) DEFAULT NULL,
  `contact_email` varchar(250) DEFAULT NULL,
  `contact_phone` varchar(250) DEFAULT NULL,
  `contact_site` varchar(250) DEFAULT NULL COMMENT 'A URL possibly copied from the address bar with lengthy path',
  `notes` text DEFAULT NULL,
  `submitted_datetime` datetime NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_submissions_job_ad_id` (`job_ad_id`),
  KEY `fk_submissions_employer_id_key` (`employer_id`),
  KEY `fk_submissions_letter_id_key` (`letter_id`),
  KEY `fk_submissions_resume_id` (`resume_id`),
  CONSTRAINT `fk_submissions_employer_id` FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`),
  CONSTRAINT `fk_submissions_job_ad_id` FOREIGN KEY (`job_ad_id`) REFERENCES `job_ads` (`id`),
  CONSTRAINT `fk_submissions_letter_id` FOREIGN KEY (`letter_id`) REFERENCES `letters` (`id`),
  CONSTRAINT `fk_submissions_resume_id` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='A submission is made to exactly one employer be it a recruiter or a principle company. It could be made by someone else, like a recruiter, on behalf of the user. If so';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `versions`
--

DROP TABLE IF EXISTS `versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `versions` (
  `version` varchar(50) DEFAULT NULL COMMENT 'Schema version in 1.2.3 format',
  `notes` text DEFAULT NULL,
  `modified` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Identifies schema version and holds any other descriptive information generally not used except for development.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'psalteco_psalte'
--
/*!50003 DROP PROCEDURE IF EXISTS `proc_application` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_application`(
        IN `parameter_company_name` VARCHAR(250),
        IN `parameter_title` VARCHAR(250),
        IN `parameter_ad_content` TEXT,
        IN `parameter_resume_id` INT,
        IN `parameter_source` VARCHAR(250),
        IN `parameter_source_url` VARCHAR(250)
)
    COMMENT 'Make complete application inserting employer, job ad, and submission'
BEGIN
        DECLARE v_employer_id INT DEFAULT NULL;
        START TRANSACTION;
                INSERT INTO employers (
                        company_name,
                        employs_it,
                        is_principle,
                        notes,
                        site_url)
                VALUES (
                        parameter_company_name,
                        0,
                        0,
                        "",
                        "");
                SET v_employer_id = LAST_INSERT_ID();
                INSERT INTO job_ads (
                        ad_content,
                        by_email,
                        employer_id,
                        posted_datetime,
                        source,
                        source_url,
                        title)
                VALUES (
                        parameter_ad_content,
                        0,
                        v_employer_id,
                        NOW(),
                        parameter_source,
                        parameter_source_url,
                        parameter_title);
                INSERT INTO submissions (
                        employer_id,
                        is_rejected,
                        job_ad_id,
                        `position`,
                        resume_id,
                        submitted_datetime)
                VALUES (
                        v_employer_id,
                        0,
                        LAST_INSERT_ID(),
                        parameter_title,
                        parameter_resume_id,
                        NOW());
        COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_commit_letter` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_commit_letter`(
	IN `parameter_id` INT,
	IN `parameter_file_content` MEDIUMBLOB,
	IN `parameter_filename` VARCHAR(250),
	IN `parameter_original_basename` VARCHAR(250),
	IN `parameter_extension` VARCHAR(250),
	IN `parameter_description` VARCHAR(250),
	IN `parameter_content_type` VARCHAR(250),
	IN `parameter_size` INT,
	IN `parameter_last_modified` DATETIME,
	IN `parameter_notes` TEXT











)
BEGIN
  DECLARE v_forward_crumb INT DEFAULT NULL;
  DECLARE v_forward_revision_count INT DEFAULT 0;
  DECLARE v_files_id INT DEFAULT 0;
  START TRANSACTION;
    INSERT INTO files (
        file_content)
    VALUES (
        parameter_file_content);
    SET v_files_id = LAST_INSERT_ID();
    SET v_forward_crumb = (SELECT breadcrumb FROM letters WHERE id = parameter_id);
    SET v_forward_revision_count = (SELECT revision_count FROM letters WHERE id = parameter_id);
    INSERT INTO letters (
		  updated_from,
		  breadcrumb,
		  revision_count,
        filename,
        original_basename,
        extension,
        description,
        notes,
        content_type,
        size,
        last_modified,
		  files_id)
	 VALUES (
			parameter_id,
			v_forward_crumb,
			v_forward_revision_count + 1,
			parameter_filename,
			parameter_original_basename,
			parameter_extension,
			parameter_description,
			parameter_notes,
			parameter_content_type,
			parameter_size,
			parameter_last_modified,
			v_files_id);
	UPDATE files
	 	SET letter_id = LAST_INSERT_ID()
	 	WHERE id = v_files_id;
	UPDATE letters 
		SET 
			updated_to = LAST_INSERT_ID(),
			revision_count = v_forward_revision_count + 1
		WHERE
			id = parameter_id;
    INSERT INTO event_log (
			standard_name,
			operation,
			notes,
			letter_id,
			event_datetime)
	 VALUES (
			parameter_description,
			'update',
			parameter_filename,
			LAST_INSERT_ID(),
			NOW());
  COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_commit_resume` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_commit_resume`(
	IN `parameter_id` INT,
	IN `parameter_file_content` MEDIUMBLOB,
	IN `parameter_filename` VARCHAR(250),
	IN `parameter_original_basename` VARCHAR(250),
	IN `parameter_extension` VARCHAR(250),
	IN `parameter_purpose` VARCHAR(250),
	IN `parameter_content_type` VARCHAR(250),
	IN `parameter_size` INT,
	IN `parameter_last_modified` DATETIME,
	IN `parameter_notes` TEXT













)
    MODIFIES SQL DATA
    COMMENT 'Inserts resume metadata into separate resumes table and content into a table that contains a blob. This is because searching a table that contains a blob column is slow.'
BEGIN
  DECLARE v_forward_crumb INT DEFAULT NULL;
  DECLARE v_forward_revision_count INT DEFAULT 0;
  DECLARE v_files_id INT DEFAULT 0;
  START TRANSACTION;
    INSERT INTO files (
        file_content)
    VALUES (
        parameter_file_content);
    SET v_files_id = LAST_INSERT_ID();
    SET v_forward_crumb = (SELECT breadcrumb FROM resumes WHERE id = parameter_id);
    SET v_forward_revision_count = (SELECT revision_count FROM resumes WHERE id = parameter_id);
    INSERT INTO resumes (
		  updated_from,
		  breadcrumb,
		  revision_count,
        filename,
        original_basename,
        extension,
        purpose,
        notes,
        content_type,
        size,
        last_modified,
		  files_id)
	 VALUES (
			parameter_id,
			v_forward_crumb,
			v_forward_revision_count + 1,
			parameter_filename,
			parameter_original_basename,
			parameter_extension,
			parameter_purpose,
			parameter_notes,
			parameter_content_type,
			parameter_size,
			parameter_last_modified,
			v_files_id);
	 UPDATE files
	 	SET resume_id = LAST_INSERT_ID()
	 	WHERE id = v_files_id;
 	 UPDATE resumes 
		SET 
		  updated_to = LAST_INSERT_ID()
		WHERE
		  id = parameter_id;
    INSERT INTO event_log (
        standard_name,
        operation,
        notes,
		  resume_id,
		  event_datetime)
	 VALUES (
        parameter_purpose,
        'update',
        parameter_filename,
		  LAST_INSERT_ID(),
		  NOW());
  COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_delete_all_letter_revisions` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_delete_all_letter_revisions`(
	IN `parameter_id` INT



)
BEGIN
	DELETE
	FROM letters
	WHERE breadcrumb = (
		SELECT breadcrumb 
		FROM letters 
		WHERE id = parameter_id);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_delete_all_resume_revisions` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_delete_all_resume_revisions`(
	IN `parameter_id` INT




)
    COMMENT 'Deletes head and all other associated revisions given the id of the head'
BEGIN
	DELETE
	FROM resumes
	WHERE breadcrumb = (
		SELECT breadcrumb 
		FROM resumes 
		WHERE id = parameter_id);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_insert_employer` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_insert_employer`(
	IN `parameter_company_name` VARCHAR(250),
	IN `parameter_is_principle` CHAR(1),
	IN `parameter_notes` MEDIUMTEXT,
	IN `parameter_employs_it` CHAR(1),
	IN `parameter_site_url` VARCHAR(250)















)
    SQL SECURITY INVOKER
    COMMENT 'Inserts employer and records event in event log'
BEGIN
  START TRANSACTION;
    INSERT INTO employers (
        company_name,
		  is_principle,
		  notes,
		  employs_it,
		  site_url)
    VALUES (
        parameter_company_name,
		  parameter_is_principle,
		  parameter_notes,
		  parameter_employs_it,
		  parameter_site_url);
    INSERT INTO event_log (
        standard_name,
        operation,
        notes,
		  employer_id,
		  event_datetime)
	 VALUES (
        parameter_company_name,
        'insert',
        parameter_notes,
		  LAST_INSERT_ID(),
		  NOW());
  COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_insert_job_ad` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_insert_job_ad`(
	IN `parameter_title` VARCHAR(250),
	IN `parameter_ad_content` MEDIUMTEXT,
	IN `parameter_notes` MEDIUMTEXT,
	IN `parameter_posted_datetime` DATETIME,
	IN `parameter_source_url` VARCHAR(250),
	IN `parameter_source` VARCHAR(250),
	IN `parameter_by_email` INT(1),
	IN `parameter_employer_id` INT




)
    SQL SECURITY INVOKER
    COMMENT 'Insert a job ad and an event in the log'
BEGIN
  START TRANSACTION;
    INSERT INTO job_ads (
        title,
        ad_content,
		  notes,
		  posted_datetime,
		  source_url,
		  source,
		  by_email,
		  employer_id)
    VALUES (
        parameter_title,
        parameter_ad_content,
		  parameter_notes,
		  parameter_posted_datetime,
		  parameter_source_url,
		  parameter_source,
		  parameter_by_email,
		  parameter_employer_id);
    INSERT INTO event_log (
        standard_name,
        operation,
        notes,
		  job_ad_id,
		  event_datetime)
	 VALUES (
        parameter_title,
        'insert',
        parameter_notes,
		  LAST_INSERT_ID(),
		  NOW());
  COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_insert_submission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_insert_submission`(
	IN `parameter_position` VARCHAR(50),
	IN `parameter_is_rejected` VARCHAR(50),
	IN `parameter_employer_id` INT,
	IN `parameter_job_ad_id` INT,
	IN `parameter_resume_id` INT,
	IN `parameter_letter_id` INT,
	IN `parameter_notes` TEXT,
	IN `parameter_submitted_datetime` DATETIME,
	IN `parameter_contact_position` VARCHAR(250),
	IN `parameter_contact_first` VARCHAR(250),
	IN `parameter_contact_last` VARCHAR(250),
	IN `parameter_contact_initial` VARCHAR(50),
	IN `parameter_contact_prefix` VARCHAR(50),
	IN `parameter_contact_email` VARCHAR(250),
	IN `parameter_contact_phone` VARCHAR(250),
	IN `parameter_contact_site` VARCHAR(250)








)
BEGIN
	 INSERT INTO submissions (
		`position`,
		`is_rejected`,
		`employer_id`,
		`job_ad_id`,
		`resume_id`,
		`letter_id`,
		`contact_position`,
		`contact_first`,
		`contact_last`,
		`contact_initial`,
		`contact_prefix`,
		`contact_email`,
		`contact_phone`,
		`contact_site`,
		`notes`,
		`submitted_datetime`)
	VALUES (
		parameter_position,
		parameter_is_rejected,
		parameter_employer_id,
		parameter_job_ad_id,
		parameter_resume_id,
		parameter_letter_id,
		parameter_contact_position,
		parameter_contact_first,
		parameter_contact_last,
		parameter_contact_initial,
		parameter_contact_prefix,
		parameter_contact_email,
		parameter_contact_phone,
		parameter_contact_site,
		parameter_notes,
		parameter_submitted_datetime);
    INSERT INTO event_log (
		standard_name,
		operation,
		notes,
		submission_id,
		event_datetime)
	 VALUES (
		parameter_position,
		'insert',
		parameter_notes,
		LAST_INSERT_ID(),
		NOW());
	COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_update_employer` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_employer`(
	IN `parameter_id` INT,
	IN `parameter_company_name` VARCHAR(250),
	IN `parameter_is_principle` TINYINT(1),
	IN `parameter_notes` MEDIUMTEXT,
	IN `parameter_employs_it` CHAR(1),
	IN `parameter_site_url` VARCHAR(250)










)
    SQL SECURITY INVOKER
    COMMENT 'Update employer does not create an event log entry'
BEGIN
	START TRANSACTION;
	UPDATE employers 
		SET 
			company_name = parameter_company_name,
			is_principle = parameter_is_principle,
			notes = parameter_notes,
			employs_it = parameter_employs_it,
			site_url = parameter_site_url
		WHERE
			id = parameter_id;
	INSERT INTO event_log (
			standard_name,
			operation,
			notes,
			employer_id,
			event_datetime)
		VALUES (
			parameter_company_name,
			'update',
			parameter_notes,
			parameter_id,
			NOW());
	COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_update_job_ad` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_job_ad`(
	IN `parameter_id` INT,
	IN `parameter_title` VARCHAR(250),
	IN `parameter_ad_content` MEDIUMTEXT,
	IN `parameter_notes` MEDIUMTEXT,
	IN `parameter_posted_datetime` DATETIME,
	IN `parameter_source_url` VARCHAR(250),
	IN `parameter_source` VARCHAR(250),
	IN `parameter_by_email` TINYINT(1),
	IN `parameter_employer_id` INT





)
    SQL SECURITY INVOKER
    COMMENT 'Update a job ad'
BEGIN
	START TRANSACTION;
		UPDATE job_ads 
			SET 
				title = parameter_title,
				ad_content = parameter_ad_content,
				notes = parameter_notes,
				posted_datetime = parameter_posted_datetime,
				source_url = parameter_source_url,
				source = parameter_source,
				by_email = parameter_by_email,
				employer_id = parameter_employer_id
			WHERE
				id = parameter_id;
		INSERT INTO event_log (
				standard_name,
				operation,
				notes,
				job_ad_id,
				event_datetime)
			VALUES (
				parameter_title,
				'update',
				parameter_notes,
				parameter_id,
				NOW());
	COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_update_letter` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_letter`(
	IN `parameter_id` INT,
	IN `parameter_description` VARCHAR(250),
	IN `parameter_notes` TEXT

)
BEGIN
	START TRANSACTION;
		UPDATE letters 
			SET
				description = parameter_description,
				notes = parameter_notes
			WHERE
				id = parameter_id;	
		INSERT INTO event_log (
			standard_name,
			operation,
			notes,
			resume_id,
			event_datetime)
		VALUES (
			parameter_description,
			'update',
			parameter_notes,
			parameter_id,
			NOW());
	COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_update_resume` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_resume`(
	IN `parameter_id` INT,
	IN `parameter_purpose` VARCHAR(250),
	IN `parameter_notes` TEXT



)
    COMMENT 'Only updates resume meta data , not the associated file'
BEGIN
	START TRANSACTION;
		UPDATE resumes 
			SET
				purpose = parameter_purpose,
				notes = parameter_notes
			WHERE
				id = parameter_id;	
		INSERT INTO event_log (
			standard_name,
			operation,
			notes,
			resume_id,
			event_datetime)
		VALUES (
			parameter_purpose,
			'update',
			parameter_notes,
			parameter_id,
			NOW());
	COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_update_submission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_submission`(
	IN `parameter_id` INT,
	IN `parameter_position` VARCHAR(50),
	IN `parameter_is_rejected` CHAR(1),
	IN `parameter_employer_id` INT,
	IN `parameter_job_ad_id` INT,
	IN `parameter_resume_id` INT,
	IN `parameter_letter_id` INT,
	IN `parameter_notes` TEXT,
	IN `parameter_submitted_datetime` DATETIME

,
	IN `parameter_contact_position` VARCHAR(250),
	IN `parameter_contact_first` VARCHAR(250),
	IN `parameter_contact_last` VARCHAR(250),
	IN `parameter_contact_initial` VARCHAR(50),
	IN `parameter_contact_prefix` VARCHAR(50),
	IN `parameter_contact_email` VARCHAR(250),
	IN `parameter_contact_phone` VARCHAR(250),
	IN `parameter_contact_site` VARCHAR(250)






)
BEGIN
START TRANSACTION;
	UPDATE submissions
		SET 
			`position` = parameter_position,
			`is_rejected` = parameter_is_rejected,
			`job_ad_id` = parameter_job_ad_id,
			`resume_id` = parameter_resume_id,
			`letter_id` = parameter_letter_id,
			`employer_id` = parameter_employer_id,
			`contact_position` = parameter_contact_position,
			`contact_first` = parameter_contact_first,
			`contact_last` = parameter_contact_last,
			`contact_initial` = parameter_contact_initial,
			`contact_prefix` = parameter_contact_prefix,
			`contact_email` = parameter_contact_email,
			`contact_phone` = parameter_contact_phone,
			`contact_site` = parameter_contact_site,
			`notes` = parameter_notes,
			`submitted_datetime` = parameter_submitted_datetime
		WHERE
			id = parameter_id;
		INSERT INTO event_log (
			standard_name,
			operation,
			notes,
			submission_id,
			event_datetime)
		VALUES (
			parameter_position,
			'update',
			parameter_notes,
			parameter_id,
			NOW());
COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_upload_letter` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_upload_letter`(
	IN `parameter_file_content` MEDIUMBLOB,
	IN `parameter_filename` VARCHAR(250),
	IN `parameter_original_basename` VARCHAR(250),
	IN `parameter_extension` VARCHAR(250),
	IN `parameter_description` VARCHAR(250),
	IN `parameter_content_type` VARCHAR(250),
	IN `parameter_size` INT,
	IN `parameter_last_modified` DATETIME,
	IN `parameter_notes` TEXT
















)
    COMMENT 'Cover letter etc.'
BEGIN
	DECLARE v_files_id INT DEFAULT 0;
  START TRANSACTION;
    INSERT INTO files (
        file_content)
    VALUES (
        parameter_file_content);
    SET v_files_id = LAST_INSERT_ID();
    INSERT INTO letters (
        filename,
        original_basename,
        extension,
        description,
        content_type,
        size,
        last_modified,
        notes,
		  files_id)
	 VALUES (
        parameter_filename,
        parameter_original_basename,
        parameter_extension,
        parameter_description,
        parameter_content_type,
        parameter_size,
        parameter_last_modified,
        parameter_notes,
		  v_files_id);
	 UPDATE files
	 	SET letter_id = LAST_INSERT_ID()
	 	WHERE id = v_files_id;
	 UPDATE letters
	     SET breadcrumb = LAST_INSERT_ID()
	     WHERE id = LAST_INSERT_ID();
    INSERT INTO event_log (
        standard_name,
        operation,
        notes,
		  letter_id,
		  event_datetime)
	 VALUES (
        parameter_description,
        'insert',
        parameter_notes,
		  LAST_INSERT_ID(),
		  NOW());
  COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `proc_upload_resume` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_upload_resume`(
	IN `parameter_file_content` MEDIUMBLOB,
	IN `parameter_filename` VARCHAR(250),
	IN `parameter_original_basename` VARCHAR(250),
	IN `parameter_extension` VARCHAR(50)







,
	IN `parameter_purpose` VARCHAR(250)





,
	IN `parameter_content_type` VARCHAR(250),
	IN `parameter_size` INT,
	IN `parameter_last_modified` DATETIME,
	IN `parameter_notes` TEXT










)
    MODIFIES SQL DATA
    COMMENT 'Inserts resume metadata into separate resumes table and content into a table that contains a blob. This is because searching a table that contains a blob column is slow.'
BEGIN
	DECLARE v_files_id INT DEFAULT 0;
  START TRANSACTION;
    INSERT INTO files (
        file_content)
    VALUES (
        parameter_file_content);
    SET v_files_id = LAST_INSERT_ID();
    INSERT INTO resumes (
        filename,
        original_basename,
        extension,
        purpose,
        content_type,
        size,
		  last_modified,
        notes,
		  files_id)
	 VALUES (
        parameter_filename,
        parameter_original_basename,
        parameter_extension,
        parameter_purpose,
        parameter_content_type,
        parameter_size,
        parameter_last_modified,
        parameter_notes,
		  v_files_id);
	 UPDATE files
	 	SET resume_id = LAST_INSERT_ID()
	 	WHERE id = v_files_id;
	 UPDATE resumes
	     SET breadcrumb = LAST_INSERT_ID()
	     WHERE id = LAST_INSERT_ID();
    INSERT INTO event_log (
        standard_name,
        operation,
        notes,
		  resume_id,
		  event_datetime)
	 VALUES (
        parameter_purpose,
        'insert',
        parameter_filename,
		  LAST_INSERT_ID(),
		  NOW());
  COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `truncate_all` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `truncate_all`()
BEGIN
	SET FOREIGN_KEY_CHECKS=0;
	TRUNCATE TABLE employers ;
	TRUNCATE TABLE event_log ;
	TRUNCATE TABLE files ;
	TRUNCATE TABLE job_ads ;
	TRUNCATE TABLE resumes ;
	TRUNCATE TABLE letters;
	TRUNCATE TABLE submissions ;
	TRUNCATE TABLE job_boards ;
	-- we leave versions alone
	SET FOREIGN_KEY_CHECKS=1;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-03-14 20:12:57
