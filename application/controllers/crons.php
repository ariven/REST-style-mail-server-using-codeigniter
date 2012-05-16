<?php
	/**
	 * User: patrick
	 * Date: 5/16/12
	 * Time: 7:17 AM
	 */

	class Crons extends CI_Controller
	{
		function __construct()
		{
			parent::__construct();

			$this->config->load('mserv');
			$this->load->model('email_model');
			$this->load->library('logrus_mail');
			$this->load->model('lg');
		}

		/**
		 * handles scheduled sending of mail.
		 */
		public function index()
		{
			$result       = '';
			$max_time     = $this->config->item('max_processing_time');
			$now          = time();
			$time_to_stop = $now + $max_time;
			if ($this->_mail_to_send())
			{
				while ($this->_mail_to_send() and $now < $time_to_stop )
				{
					$mail = $this->_get_next_email();
					$result .= $this->logrus_mail->send($mail->id);
					echo 'sent email ', $mail->id, ' to ', $mail->to_email, '<br />';
					$now = time();
				}
			}
			else
			{
				$result = 'No mail to send';
			}
			echo $result;
		}

		function _get_next_email()
		{
			return $this->email_model->order_by('when_posted', 'ASC')->get_by('sent', 0);
		}

		/**
		 * Counts remaining mail
		 *
		 * @return int
		 */
		function _mail_to_send()
		{
			return $this->email_model->count_by('sent', 0);
		}

		/**
		 * handle stuff that needs to be done nightly.
		 */
		public function nightly()
		{
			$this->load->model('send_log');

			$this->lg->info('Deleting old log entries');

			// clean up old logs
			$this->lg->delete_by(
				'when <',
				date('Y-m-d H:i:s', strtotime(sprintf('-%d days', $this->config->item('log_keep_days'))))
			);

			$this->send_log->delete_by(
				'when_sent <',
				date('Y-m-d H:i:s', strtotime(sprintf('-%d days', $this->config->item('log_keep_days'))))
			);

			$this->email->delete_by(
				'when_sent <',
				date('Y-m-d H:i:s', strtotime(sprintf('-%d days', $this->config->item('email_keep_days'))))
			);
		}
	}