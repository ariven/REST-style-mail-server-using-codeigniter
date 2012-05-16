<?php

	/**
	 * User: ariven
	 * Date: 5/13/12
	 * Time: 2:50 PM
	 */

	$config['providers']  = array('gmail', 'dreamhost');

	$config['provider_hourly_cap']['gmail']			= 50;
	$config['provider_hourly_cap']['dreamhost']		= 90;

	$config['max_processing_time']					= 60 * 2; // maximum amount of time for bulk sending of mail

	$config['attach_dir']							= FCPATH . 'attach/'; // top directory where we stuff attached files if we have to retrieve them from the web
	$config['single_mail_timeout']					= 15; // number of seconds to try to deliver a single piece of mail, i.e. in case of slow mail server

	$config['log_keep_days']						= 30; // number of days to keep logs
	$config['email_keep_days']						= 30; // number of days to keep old emails

	/**
	 * These are default values for gmail for you to prefill into database entry when making one
	 */
	$config['provider']['gmail']['protocol']		= 'smtp'; // $p->Mailer   mail|sendmail|smtp
	$config['provider']['gmail']['smtp_host']		= 'smtp.googlemail.com'; // $p->Host
	$config['provider']['gmail']['smtp_port']		= 587; // $p->Port
	$config['provider']['gmail']['smtp_user']		= 'USERNAME'; // $p->Username
	$config['provider']['gmail']['smtp_pass']		= 'PASSWORD'; // $p->Password
	$config['provider']['gmail']['charset']			= 'utf-8'; // $p->CharSet
	$config['provider']['gmail']['smtp_timeout']	= 5; // $p->Timeout
