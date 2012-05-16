<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

	class Tmail extends CI_Controller
	{

		public function index()
		{
			$this->load->library('rest');
			$this->rest->initialize(array(
				'server' => 'http://mserv.example.com/api/mail'
			));

			$data     = array(
				'domain'          => 'example.com',
				'to_name'         => 'John Doe',
				'to_email'        => 'johndoe@example.com',
				'subject'         => 'Mail service test',
				'body'            => 'This is a test.',
				'attach_list'     => 'http://www.example.com/file/filename.ext',
				'from_name'       => 'Mail Server',
				'from_email'      => 'address@example.com',
				'domain_key'      => 'bad horse',
				'send_now'        => 'send',
				'cc'			  => 'someone@example.com,other@example.com',
				'bcc'			  => 'someone@example.com,other@example.com',
			);
			$response = $this->rest->put('new', $data);
			print_r($response);

		}
	}
