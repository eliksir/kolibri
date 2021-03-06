<?php
/**
 * This class represents an e-mail message. When a complete e-mail message object has been
 * constructed and it should be sent, pass the object to the send() method of a
 * MailService instance.
 */
class Email {
	public $from;
	public $fromName;
	public $sender;
	public $senderName;
	public $subject;
	public $recipients;
	public $replyTo;
	public $cc;
	public $bcc;
	public $body	= '';
	public $altBody	= '';
	public $attachments = null;

	/**
	 * Initialize.
	 */
	public function __construct ($from = null, $fromName = null,
			$sender = null, $senderName = null) {
		$this->from       = $from;
		$this->fromName   = $fromName;
		$this->sender     = $sender;
		$this->senderName = $senderName;
	}

	/**
	 * Clears the list of recipients for the message. Ie. for sending the same e-mail with
	 * slight modifications to separate recipients.
	 */
	public function clearRecipients () {
		$this->recipients = array();
	}

	/**
	 * Adds a recipient of the message.
	 */
	public function addRecipient ($email, $name = null) {
		$this->recipients[] = array($email, $name);
	}

	/**
	 * Adds a reply-to address to the message.
	 */
	public function addReplyTo ($email, $name = null) {
		$this->replyTo[] = array($email, $name);
	}

	/**
	 * Adds a carbon-copy address to the message.
	 */
	public function addCc ($email, $name = null) {
		$this->cc[] = array($email, $name);
	}

	/**
	 * Adds a blind carbon-copy address to the message.
	 */
	public function addBcc ($email, $name = null) {
		$this->bcc[] = array($email, $name);
	}

	/**
	 * Sets the body of the message. $body can either be the actual body content, or a path to
	 * a file with the content if $isFile is TRUE.
	 *
	 * @param string $body Body content or path to file.
	 * @param bool $isFile TRUE if $body is a file path, FALSE if not (default).
	 */
	public function setBody ($body, $isFile = false) {
		if ($isFile) {
			$this->body = file_get_contents($body);
		}
		else {
			$this->body = $body;
		}
	}

	/**
	 * Sets the alternate body of the message. $body can either be the actual body content, or
	 * a path to a file with the content if $isFile is TRUE.
	 *
	 * Setting an alternate body implicetly sets the content type of the message to text/html.
	 *
	 * @param string $body Alternate body content or path to file.
	 * @param bool $isFile TRUE if $body is a file path, FALSE if not (default).
	 */
	public function setAltBody ($body, $isFile = false) {
		if ($isFile) {
			$this->altBody = file_get_contents($body);
		}
		else {
			$this->altBody = $body;
		}
	}

	/**
	 * Sets body text as plain text if no HTML is supplied, otherwise sets HTML as body text
	 * and the plain text as alternative body text.
	 *
	 * This method, if used, should be used instead of setBody() and setAltBody().
	 *
	 * @param string $plain The plain content to set.
	 * @param string $html  The HTML content to set, if relevant.
	 */
	public function setContent ($plain, $html = null) {
		$plain = trim($plain);

		if (!empty($html)) {
			$this->setBody(trim($html));
			$this->setAltBody($plain);
		}
		else {
			$this->setBody($plain);
		}
	}

	/**
	 * Performs string translation of the body text using the replacement pairs supplied.
	 *
	 * @param mixed $replacePairs Replacement pairs as an array or object.
	 */
	public function strtrBody ($replacePairs) {
		if (is_object($replacePairs)) {
			foreach (get_object_vars($replacePairs) as $key => $value) {
				$newPairs["%$key"] = $value;
			}
			$replacePairs = $newPairs;
		}

		$this->body = strtr($this->body, $replacePairs);
		$this->altBody = strtr($this->altBody, $replacePairs);
	}
	
	/**
	 * Adds an attachment to this email.
	 *
	 * @param string $file File name.
	 * @param string $name Name to give the file for the email.
	 */
	public function addAttachment ($file, $name = '') {
		if (file_exists($file)) {
			$this->attachments[] = array('file' => $file, 'name' => $name);
		}
	}
}
?>
