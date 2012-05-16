========================================
REST-style-mail-server-using-codeigniter
========================================

A REST style mail server that allows you to to schedule an email to be sent.  Can also force send now.  Allows for attached files, and multiple outboxes.


This server uses the following great pieces of code:
---------------------------------------------------
Codeigniter Restserver by Phil Sturgeon
----------------------
https://github.com/philsturgeon/codeigniter-restserver
required

Codeigniter base model by Jamie Rumbelow
----------------------
https://github.com/jamierumbelow/codeigniter-base-model
required

Codeigniter Restclient by Phil Sturgeon
----------------------
https://github.com/philsturgeon/codeigniter-restclient
Used for example, you can use any means of sending a REST put request to send a mail.

*********
PHPMailer
*********
http://phpmailer.worxware.com/index.php?pg=phpmailer
required.
Install in the third_party folder in a folder named php_mailer

SQL
---
There is a mysql.sql file with the required table structure.

********
Database
********

Domains
-------
domain: use the same name from the 'providers' array in the config file (see below).
More than one entry can use the same domain, when sending mail the server will send to the one with the lowest "mails_sent" count.  This allows for a round robin of multiple providers for a single site.

username
--------
The username for your email account

password
--------
The password for your email account

host
----
The host for your mail, such as smtp.googlemail.com or mail.example.com

port
----
The port to send to, normally 25, or 587 if sending to gmail

active
------
If 1, then this item is active and usable to send mail.

access_key
----------
Used to authenticate the sending server.  All servers with the same _domain_ need to have the same key

smtp_auth
---------
1 if the mail server requires authentication

smtp_secure
-----------
tls or none.  Depends on what your server requires.  This is the PHPMailer SMTPSecure property.  Gmail, for example, requires TLS

charset
-------
The charset to set the email to.  utf-8 is what I generally use

mails_sent
----------
How many emails have been sent using this server.  Incremented automatically.  Used to help round_robin multiple servers for the same domain

provider
--------
Provider as defined in the 'providers' option in the config file.  Used for per hour limits on sending mail

send_type
---------
smtp, mail, sendmail
What route to send the mail.  smtp use a host, sendmail uses local sendmail, and mail uses the PHP mail function.

***
Use
***
The mail server looks for a put rest connection to: mailserver.com/api/mail

The tmail controller has an example of how to use Phil Sturgeons REST client to send an email.  The options when puting an email are:

	'domain'          => 'example.com',
	'to_name'         => 'John Doe',
	'to_email'        => 'johndoe@example.com',
	'subject'         => 'Mail service test',
	'body'            => 'This is a test.',
	'attach_list'     => 'http://www.example.com/file/filename.ext|/home/example/files/filename2.ext',
	'from_name'       => 'Mail Server',
	'from_email'      => 'address@example.com',
	'domain_key'      => 'bad horse',
	'send_now'        => 'send',
	'cc'			  => 'someone@example.com,other@example.com',
	'bcc'			  => 'someone@example.com,other@example.com',

	attach)list, send_now, cc, and bcc are optional.
	
domain
------
The domain tag to identify who is sending.

to_name
-------
optional
Regular name of who the email is going to.
If you don't include this, the to_email is used.

to_email
--------
Email address to send to

subject
-------
The subject of the mail

body
----
The body of the mail.
For HTML mail, you have to have a fully html compliant body.  (html, head, body, etc).
File links need to be absolutely defined to be attached internally (i.e. inline images).
If you link to images externally, i.e. web hosted images, many mail clients will not render them until the receiver agrees to see them.

from_name
---------
The name of the sender

from_mail
---------
The email address to put on the message as who it is from, also used for reply-to

domain_key
----------
Used to authenticate and allow sender to send mail.
You can also use the API key option in the REST server, and you will have to send that as well.

send_now
--------
Instead of scheduling mail to go out, send it immediately.  The call takes longer to return when using this option.

cc and bcc
----------
Optional
comma separated lists of emails to CC or BCC the email to.

attach_list
-----------
Optional
Pipe | separated list of files to attach.  
If the file contains http, it is grabbed from the web and temporarily stored at the time of mail processing.
Files in this list need to remain available until after sending, which may not be immediate.

**********
Cron tasks
**********
you will need to configure the following two cron tasks

example.com/crons/nightly
-------------------------
Run once a day, handles expiration of old logs and emails

example.com/crons
-----------------
Run as often as you want to check for mail to go out.  I use every 10 minutes.



*************
Configuration
*************
mserv.php in config directory
-----------------------------

$config['providers']
--------------------
Set your possible providers in the $config['providers'] item as an array.
The names don't matter, this is used to enforce hourly caps on sending mail.
example:
$config['providers'] = array('gmail', 'dreamhost');

$config['provider_hourly_cap']
------------------------------
This allows you to set the maximum number of mails to send in an hour.  This is a sliding window, so it checks the last hour's worth of sent mail.

I recommend setting this to less than the allotted amount if you plan on using the "force send" option to force some emails out immediately.

example: 
$config['provider_hourly_cap']['providername'] = 99;


$config['max_processing_time']
------------------------------
Number of seconds maximum to occupy while processing email.  This check is done after each email is sent, so it might run a short amount over.

Remember that attaching files can expand the time needed to send a mail.

You should set this to be less time than the maximum processing time your PHP install is configured for, so that it stops before your server forces it to.


$config['attach_dir']
----------------------
The local directory to store temporary attached files.
The web server needs to be able to access files and directories under this path.

If an attached file has "http"  in it, it is assumed to be from the web, and it is downloaded to a temporary directory under the attach_dir directory during the send process.  After the send process, this file and temporary directory is deleted.

Files that don't have "http" in them are assumed to be local files, and are attached, and are NOT deleted from the local file system.

example:
$config['attach_dir']							= FCPATH . 'attach/';

$config['single_mail_timeout']
----------------------
The number of seconds to try to deliver a single piece of mail.  You can increase this if you have a slow server you are talking to.

$config['log_keep_days']
----------------------
The number of days to keep old logs.

$config['email_keep_days']
----------------------
The number of days to keep old, sent emails.

*****
NOTES
*****

You can remove the lg model and calls to it, as well as the lgs directory, if you prefer to use a different means to log some of the basic info.  It is a carryover from another project.

*********
Changelog
*********
**Version 1.0.0
* Initial release