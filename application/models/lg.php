<?php

	/**
	 * User: patrick
	 * Date: 5/14/12
	 * Time: 7:51 AM
	 */
	class lg extends MY_Model
	{

		/**
		 * Use this to get current user.
		 *
		 * @return string
		 * @todo Implement "get user"
		 *
		 */
		private function get_user()
		{
			return 'unknown';
		}

		public function msg($msg, $status)
		{
			$user = $this->get_user();
			$this->insert(
				array(
					 'what'   => $msg,
					 'when'   => date('Y-m-d H:i:s'),
					 'who'    => $user,
					 'status' => $status
				)
			);
		}

		/**
		 * logs an error
		 *
		 * @param $msg the error to log
		 */
		public function error($msg)
		{
			$this->msg($msg, 'error');
		}

		/**
		 * logs a message
		 *
		 * @param $msg the message to log
		 */
		public function info($msg)
		{
			$this->msg($msg, 'info');
		}

		/**
		 * Logs a warning message
		 *
		 * @param $msg the message to log
		 */
		public function warning($msg)
		{
			$this->msg($msg, 'warning');
		}
	}