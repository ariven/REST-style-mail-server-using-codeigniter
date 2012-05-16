<?php
	/**
	 * User: patrick
	 * Date: 5/16/12
	 * Time: 11:01 AM
	 */
	class Notify
	{
		protected $ci;

		function __construct()
		{
			$this->ci = & get_instance();
			$this->config->load('notify');
		}

		/**
		 * send an administrative message to the admin
		 *
		 * @param $msg
		 */
		function admin($msg)
		{
			$message = $this->load->view(
				$this->config->item('mail_master_html_template'),
				array('content' => $msg),
				TRUE);

			$response = $this->send(
				$this->config->item('mail_admin_email'),
				$this->config->item('mail_admin_name'),
				'Message from ' . $this->config->item('mail_site_name'),
				$message
			);
			return $response;
		}

		function cs ($msg)
		{
			$message = $this->load->view(
				$this->config->item('mail_master_html_template'),
				array('content' => $msg),
				TRUE);

			$response = $this->send(
				$this->config->item('mail_cs_email'),
				$this->config->item('mail_cs_name'),
				'Message from ' . $this->config->item('mail_site_name'),
				$message
			);
			return $response;
		}
		/**
		 * immediately send an error message to the admin
		 *
		 * @param $msg
		 */
		function error($msg)
		{
			$message = $this->load->view(
				$this->config->item('mail_master_html_template'),
				array('content' => $msg),
				TRUE);

			$response = $this->send(
				$this->config->item('mail_admin_email'),
				$this->config->item('mail_admin_name'),
				'There was an error on ' . $this->config->item('mail_site_name'),
				$message,
				TRUE
			);
			return $response;
		}

		/**
		 * @param $customer_email customer email address
		 * @param $customer_name  customer name
		 * @param $subject        subject of the email
		 * @param $msg            body of the email
		 * @param bool $urgent    is it important to send NOW?
		 */
		function customer($customer_email, $customer_name, $subject, $msg, $urgent = FALSE, $bcc = '')
		{
			$message = $this->load->view(
				$this->config->item('mail_master_html_template'),
				array('content' => $msg),
				TRUE);

			$response = $this->send(
				$customer_email,
				$customer_name,
				$subject,
				$message,
				$urgent
			);
			return $response;
		}


		/**
		 * Send an email to someone.
		 *
		 * @param $to_email    who to send to
		 * @param $to_name     their name
		 * @param $subject     subject of the email
		 * @param $msg         message body
		 * @param bool $urgent do we send NOW or schedule for later?
		 */
		function send($to_email, $to_name, $subject, $msg, $urgent = FALSE, $bcc = '')
		{
			$this->load->library('rest');
			$this->rest->initialize(array('server' => $this->config->item('mail_server')));

			$data = array(
				'domain'          => $this->config->item('mail_domain_name'),
				'to_name'         => $to_name,
				'to_email'        => $to_email,
				'subject'         => $subject,
				'body'            => $msg,
				'from_name'       => $this->config->item('mail_from_name'),
				'from_email'      => $this->config->item('mail_from_email'),
				'domain_key'      => $this->config->item('mail_domain_key'),
			);
			if ($urgent)
			{
				$data['send_now'] = 'send';
			}
			if (strlen($bcc) > 0)
			{
				$data['bcc'] = $bcc;
			}
			$response = $this->rest->put('new', $data);
			return $response;
		}

		/**
		 * __get
		 *
		 * Allows library to access CI's loaded classes using the same
		 * syntax as controllers.
		 *
		 * @param    string
		 * @access private
		 */
		function __get($key)
		{
			return $this->ci->$key;
		}

	}