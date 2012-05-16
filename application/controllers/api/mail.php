<?php defined('BASEPATH') OR exit('No direct script access allowed');

	/**
	 * Example
	 *
	 * This is an example of a few basic user interaction methods you could use
	 * all done with a hardcoded array.
	 *
	 * @package        CodeIgniter
	 * @subpackage     Rest Server
	 * @category       Controller
	 * @author         Phil Sturgeon
	 * @link           http://philsturgeon.co.uk/code/
	 */

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
	require APPPATH . '/libraries/REST_Controller.php';

	class Mail extends REST_Controller
	{

		protected $methods = array(
			'mail_put' => array('level' => 10,
								'limit' => 10),
		);


		/**
		 * Inserts a new mail into the database
		 */
		function new_put()
		{
			$this->load->model('email_model');
			$this->load->model('domain');
			$this->load->model('lg');

			$data['domain']     = $this->put('domain');
			$data['domain_key'] = $this->put('domain_key');
			$data['to_name']    = $this->put('to_name');

			$data['to_email'] = trim($this->put('to_email')) != '' ? $this->put('to_email') : '';
			$data['subject']     = $this->put('subject');
			$data['body']        = $this->put('body');
			$data['bcc']         = $this->put('bcc');
			$data['cc']          = $this->put('cc');
			$data['attach_list'] = $this->put('attach_list');
			$data['from_name']   = $this->put('from_name');
			$data['from_email']  = $this->put('from_email');
			$data['send_now']    = $this->put('send_now');

			// we need some fields or we cant process.

			if (empty($data['domain']))
			{
				$this->response(array('status'  => 'failed',
									  'message' => 'Need domain'));
				return;
			}
			if (empty($data['to_email']))
			{
				$this->response(array('status'  => 'failed',
									  'message' => 'Need destination email address'));
				return;
			}
			if (empty($data['domain_key']))
			{
				$this->response(array('status'  => 'failed',
									  'message' => 'Need domain key'));
				return;
			}
			if (empty($data['subject']))
			{
				$this->response(array('status'  => 'failed',
									  'message' => 'Need subject text'));
				return;
			}
			if (empty($data['body']))
			{
				$this->response(array('status'  => 'failed',
									  'message' => 'Need email body'));
				return;
			}

			$domain = $this->domain->order_by('mails_sent', 'ASC')->get_by('domain', $data['domain']);
			if ($domain)
			{
				$dom = $domain;

				if ($dom->access_key <> $data['domain_key'])
				{
					$response['status']  = 'failed';
					$response['message'] = 'invalid domain key';
				}
				else
				{
					$mail_id = $this->email_model->insert(array(
						'from_name'     => $data['from_name'],
						'from_email'    => $data['from_email'],
						'to_name'       => $data['to_name'],
						'to_email'      => $data['to_email'],
						'domain'        => $data['domain'],
						'subject'       => $data['subject'],
						'body'          => $data['body'],
						'bcc'           => $data['bcc'],
						'cc'            => $data['cc'],
						'sent'          => 0,
						'when_sent'     => date('Y-m-d H:i:s'),
						'when_posted'   => date('Y-m-d H:i:s'),
						'attach_list'   => $data['attach_list'],
						'error_sending' => 0,
						'error_time'    => date('Y-m-d H:i:s'),
					));
					if ($mail_id)
					{
						$this->lg->info(sprintf('Created email #%d for %s', $mail_id, $data['domain']));
						$response['status']  = 'success';
						$response['message'] = 'Message saved';
						if ($data['send_now'] == 'send')
						{
							$mail_response = $this->send_now($mail_id);
							if (!$mail_response['error'])
							{
								$response['status']  = 'success';
								$response['message'] = 'Message saved and sent. ';
							}
							else
							{
								$response['status']  = 'success';
								$response['message'] = 'Message saved, failed during immediate send. ' . $mail_response['message'];
							}
						}
					}
				}
			}
			else
			{
				$response['status']  = 'failed';
				$response['message'] = 'Invalid Domain';
			}

			$this->response($response, 200);
		}

		/**
		 * Forces a mail to go out now.
		 *
		 * @param $id id of the mail to send
		 */
		private function send_now($id)
		{
			$this->load->library('logrus_mail');
			return $this->logrus_mail->send_now($id);
		}
	}

