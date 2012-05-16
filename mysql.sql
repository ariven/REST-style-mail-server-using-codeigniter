-- --------------------------------------------------------
--
-- Table structure for table `domains`
--


CREATE TABLE IF NOT EXISTS `domains` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `domain` varchar(100) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `host` varchar(200) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `access_key` varchar(40) DEFAULT NULL,
  `smtp_auth` tinyint(1) DEFAULT NULL,
  `smtp_secure` varchar(45) DEFAULT NULL COMMENT 'tls|none',
  `charset` varchar(100) NOT NULL DEFAULT 'utf-8',
  `mails_sent` bigint(20) DEFAULT '0' COMMENT 'how many emails have been sent through this provider, used for load balancing providers',
  `provider` varchar(100) DEFAULT NULL,
  `send_type` varchar(45) DEFAULT 'smtp' COMMENT 'smtp|mail|sendmail',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;



--
-- Table structure for table `emails`
--

CREATE TABLE IF NOT EXISTS `emails` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `from_name` varchar(100) DEFAULT NULL,
  `from_email` varchar(100) DEFAULT NULL,
  `to_name` varchar(100) DEFAULT NULL,
  `to_email` varchar(100) DEFAULT NULL,
  `domain` varchar(100) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `body` text,
  `bcc` text COMMENT 'comma separated list',
  `cc` text COMMENT 'comma separated list',
  `sent` tinyint(1) DEFAULT NULL,
  `when_sent` datetime DEFAULT NULL,
  `when_posted` datetime DEFAULT NULL,
  `attach_list` text COMMENT 'pipe | separated list',
  `error_sending` tinyint(1) DEFAULT '0',
  `error_time` datetime DEFAULT NULL,
  `error_message` text NOT NULL COMMENT 'last error message',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Table structure for table `lgs`
--

CREATE TABLE IF NOT EXISTS `lgs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `what` text,
  `when` datetime DEFAULT NULL,
  `who` varchar(100) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--
-- Table structure for table `send_logs`
--

CREATE TABLE IF NOT EXISTS `send_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email_id` bigint(20) DEFAULT NULL,
  `domain_id` bigint(20) NOT NULL,
  `message` text,
  `sent` tinyint(1) DEFAULT NULL,
  `when_sent` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fkey_logs_email` (`email_id`),
  KEY `fkey_logs_domain` (`domain_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `send_logs`
--
ALTER TABLE `send_logs`
  ADD CONSTRAINT `fkey_logs_domain` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fkey_logs_email` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
