<?php

	if (!defined('BASEPATH'))
		exit('No direct script access allowed');

	require_once(APPPATH . 'third_party/php_mailer/class.phpmailer.php');

	class Logrus_mail
	{
		protected $obey_limits = FALSE;
		protected $ci;

		function __construct()
		{
			$this->ci = & get_instance();
			$this->config->load('mserv');

			$this->load->model('domain');
			$this->load->model('email_model');
		}

		/**
		 * @param $choice TRUE or FALSE to obey rate limits.
		 */
		function obey_rate_limit($choice)
		{
			$this->obey_limits = $choice;
		}

		/**
		 * Returns TRUE if the given domain has reached hourly rate limits
		 *
		 * @param $domain domain object from database
		 * @return bool
		 */
		function rate_limited($domain)
		{
			if ($this->obey_limits)
			{
				$this->domain->where('when_sent >', now() - 3600);
				$count       = $this->domain->count_by('domain_id', $domain->id);
				$domain_caps = $this->config->item('provider_hourly_cap');
				if ($count >= $domain_caps[$domain->provider])
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
			else
			{
				return FALSE;
			}
		}

		/**
		 * Sends a single email.  Checks global obey_limits variable to decide if it should throttle.
		 *
		 * @param $mail_id mail id from database
		 * @return array response record
		 */
		private function send_mail($mail_id)
		{

			$this->load->model('send_log');

			$email = $this->email_model->get($mail_id);
			if ($this->rate_limited($email->domain))
			{
				$response['error']   = FALSE;
				$response['message'] = 'Rate limited';
				return $response;
			}
			if (!$email)
			{
				$response['error']   = TRUE;
				$response['message'] = 'Invalid message id';
				return $response;
			}
			$domain = $this->get_domain($email->domain);
			if (!$domain)
			{
				$response['error']   = TRUE;
				$response['message'] = 'Invalid domain selected';
				return $response;
			}

			$mail = new PHPMailer(TRUE); // TRUE = throw exceptions on error so we can catch them

			$mail->Timeout = $this->config->item('single_mail_timeout');
			$mail->Port    = $domain->port;
			$mail->Host    = $domain->host;

			switch ($domain->send_type)
			{
				case 'smtp' :
					$mail->IsSMTP();
					break;
				case 'mail' :
					$mail->IsMail();
					break;
				case 'sendmail' :
					$mail->IsSendmail();
					break;
			}

			if ($this->config->item('mail_debug'))
			{
				$mail->SMTPDebug = FALSE;
			}

			$mail->Username = $domain->username;
			$mail->Password = $domain->password;

			if ($domain->smtp_auth)
			{
				$mail->SMTPAuth = $domain->smtp_auth;
			}

			if ($domain->smtp_secure)
			{
				$mail->SMTPSecure = $domain->smtp_secure;
			}

			$mail->SetFrom($email->from_email, $email->from_name);
			$mail->AddReplyTo($email->from_email, $email->from_name);
			$mail->AddAddress($email->to_email, $email->to_name);
			$mail->Subject = $email->subject;

			if ($email->bcc)
			{
				if (strpos($email->bcc, ',') === FALSE)
				{
					$mail->AddBCC($email->bcc);
				}
				else
				{
					foreach (explode(',', $email->bcc) as $bcc_entry)
					{
						$mail->AddBCC(trim($bcc_entry));
					}
				}
			}

			if ($email->cc)
			{
				if (strpos($email->cc, ',') === FALSE)
				{
					$mail->AddCC($email->cc);
				}
				else
				{
					foreach (explode(',', $email->cc) as $cc_entry)
					{
						$mail->AddCC(trim($cc_entry));
					}
				}
			}

			$file_list = array();

			if ($email->attach_list)
			{
				if (strpos($email->attach_list, '|') === FALSE)
				{
					$file        = $this->normalize_attachment($email->attach_list);
					$file_list[] = $file;

					$mail->AddAttachment($file['file']);
				}
				else
				{
					$attach_files = explode('|', $email->attach_list);

					foreach ($attach_files as $attach_entry)
					{
						$file        = $this->normalize_attachment($attach_entry);
						$file_list[] = $file;

						$mail->AddAttachment(trim($file['file']));
					}
				}
			}

			$mail->MsgHTML($email->body);
			$send_date = date('Y-m-d H:i:s');

			try
			{
				$mail->Send();
				$response['error']   = FALSE;
				$response['message'] = 'Mail Sent';

				$this->send_log->insert(array(
						'email_id'    => $mail_id,
						'domain_id'   => $domain->id,
						'when_sent'   => $send_date,
						'sent'        => 1,
						'message'     => 'success')
				);
				$this->email_model->update($mail_id, array(
					'when_sent' => $send_date,
					'sent'      => 1,
				));

				// update the mails_sent field on this domain
				$sql = "UPDATE domains SET mails_sent = mails_sent + 1 WHERE id = ?";
				$this->db->query($sql, array($domain->id));
			} catch (phpmailerException $e)
			{
				$response['error']   = TRUE;
				$response['message'] = $e->errorMessage();
				$this->email_model->update($mail_id, array('error_time'    => $send_date,
														   'error_sending' => 1,
														   'error_message' => $response['message']));
				$this->send_log->insert(array(
					'email_id'    => $mail_id,
					'domain_id'   => $domain->id,
					'when_sent'   => $send_date,
					'sent'        => 0,
					'message'     => $response['message']));
			}
			foreach ($file_list as $file)
			{
				if ($file['dir'])
				{
					unlink($file['file']);
					rmdir($file['dir']);
				}
			}
			return $response;
		}

		/**
		 * Checks to see if the attached file is a URL, if so, grabs it and saves it locally.
		 * Remember that time may be a consideration while grabbing large files.
		 *
		 * @param $file_name name of the file to normalize
		 */
		function normalize_attachment($file_name)
		{
			if (strpos($file_name, 'http') === FALSE)
			{
				return array('file' => $file_name,
							 'dir'  => FALSE);
			}
			else
			{
				$this->load->helper('string');
				$dir_name = $this->config->item('attach_dir') . random_string('unique');

				$parsed_url = parse_url($file_name);
				$sub_folder = explode('/', $parsed_url['path']);
				$max        = count($sub_folder);
				$file       = $sub_folder[$max - 1];

				$res = @mkdir($dir_name);

				if (!$res)
				{
					return FALSE;
				}
				file_put_contents($dir_name . '/' . $file, file_get_contents($file_name));
				return array('file' => $dir_name . '/' . $file,
							 'dir'  => $dir_name);
			}
		}

		/**
		 * Chooses a domain name from the database, when more than one record refers to the same database, it returns
		 * the one with the lowest "mails_sent" value
		 *
		 * @param $domain_source domain name selection
		 * @return object from database with domain particulars
		 */
		function get_domain($domain_source)
		{
			$this->db->order_by('mails_sent', 'ASC'); // select the one with the least messages sent
			$this->db->where('active', '1');
			$domain = $this->domain->get_by('domain', $domain_source);
			return $domain;
		}

		/**
		 * Send mail now. Temporarily toggles the rate limit flag
		 *
		 * @param $id
		 * @todo normalize mails_sent to keep the number from being too large?  or to force load balancing only by day?
		 */
		function send_now($id)
		{
			$save_obey = $this->obey_limits;
			$this->obey_rate_limit(FALSE);
			$email = $this->email_model->get($id);
			if ($email)
			{
				$result = $this->send_mail($id);
			}
			$this->obey_rate_limit($save_obey);
			return $result;
		}

		/**
		 * Send mail now, if not rate limited.
		 *
		 * @param $id id of mail in database
		 * @return array response from send routine
		 */
		function send($id)
		{
			$email = $this->email_model->get($id);
			if ($email)
			{
				$result = $this->send_mail($id);
			}
			return $result;
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

