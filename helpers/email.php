<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Email helper class for Swift 4.
 * Uses standard kohana email config format (see http://docs.kohanaphp.com/helpers/email)
 *
 * @author      Matt (matt@ninjapenguin.co.uk)
 * @url         www.ninjapenguin.co.uk
 */
class email_Core {

	// SwiftMailer instance
	protected static $mail;

	/**
	 * Creates a SwiftMailer instance.
	 *
	 * @param   string  Config array mirroring format as described in config file
	 * @return  object  Swift object
	 */
	public static function connect($config = NULL)
	{
		if ( ! class_exists('Swift', FALSE))
		{
			// Load SwiftMailer
			require Kohana::find_file('vendor', 'swift4/lib/swift_required');

			// Register the Swift ClassLoader as an autoload
			Swift::registerAutoload();
		}

		// Load default configuration
		($config === NULL) and $config = Kohana::config('email');

		switch ($config['driver'])
		{
			case 'smtp':
				// Set port
				$port = empty($config['options']['port']) ? NULL : (int) $config['options']['port'];

                //Set host
                $transport = Swift_SmtpTransport::newInstance()
                                ->setHost($config['options']['hostname']);
                          
                //Set port if defined
                if($port) $transport->setPort($port);
                
                //Set encryption if defined
                if(! empty($config['options']['encryption']))
                {
                    $enc = strtolower($config['options']['encryption']);
                    $transport->setEncryption($enc);
                }

                empty($config['options']['username']) or $transport->setUsername($config['options']['username']);
				empty($config['options']['password']) or $transport->setPassword($config['options']['password']);

				// Set the timeout to 5 seconds
                $mailer = Swift_Mailer::newInstance($transport);
			break;
			
			//Sendmail
			case 'sendmail':
		        $op = (empty($config['options'])) ? null : $config['options'];
			    $transport = Swift_SendmailTransport::newInstance($op);			
			break;
			
			//PHP mail :(
			default:
                $transport = Swift_MailTransport::newInstance();
			break;
		}

		// Create the SwiftMailer instance
		return email::$mail = $mailer;
	}

	/**
	 * Send an email message.
	 *
	 * @param   string|array  recipient email (and name), or an array of To, Cc, Bcc names
	 * @param   string|array  sender email (and name)
	 * @param   string        message subject
	 * @param   string        message body
	 * @param   boolean       send email as HTML
	 * @return  integer       number of emails sent
	 */
	public static function send($to, $from, $subject, $body, $html = TRUE)
	{
		// Connect to SwiftMailer
		(email::$mail === NULL) and email::connect();

		// Determine the message type
		$html = ($html === TRUE) ? 'text/html' : 'text/plain';

		// Create the message
        $message = Swift_Message::newInstance()        
                 ->setBody($body, $html)
                 ->setSubject($subject);
        
		if (is_string($to))
		{
			// Single recipient
			$message->setTo(array($to));
		}
		elseif (is_array($to))
		{
		    //array('to', array('address' => 'name'))
		    //array('to', 'address')
		    foreach ($to as $method => $add) {
		      
		      switch (strtolower($method)) {
		          case 'bcc':
		              $message->setBcc($add);
		              break;
    		          
    		      case 'cc':
    		          $message->setCc($add);
    		          break;
    		          
    		      //Default method is to    
    		      default:
		              $message->setTo($add);
		              break;
		      }
		      
		    }
		    
		}

		$message->setFrom($from);
        
		return email::$mail->send($message);
	}

} // End email