<?php
/**
 * this class abstracts the functionality available for OpenEMM as webservice
 * it does not implement all functions at the moment, also the result checking is very limited/poor
 *
 * Requirements: PHP5, SOAP module; OpenEMM 5.5.1 with webservices enabled
 *
 * @author Tobias Kluge / incratec
 * @license MIT
 * @copyright Copyright &copy; 2008-2009, Tobias Kluge, http://enarion.net/programming/php/openemm/
 * @version 1.1 from 2009-06-11
 * 
 * History
 * version 1.1 - bugfixes
 * - bugfix setSubscriberBinding (see http://www.openemm.org/forums/ftopic907.html)
 * - adapted updateSubscriber to new webservice functionality
 * 
 * version 1.0 - initial release
 */

class OpenEMM {
  private $soapClient = null;
	private $loginCredentials = array (
		'login' => null,
		'password' => null
	);
	/**
	 * instantiate instance of class
	 * @param string $wsdl_url contains the url or path of wsdl file
	 * @param string $openemm_login contains the login name for OpenEMM webservice interface
	 * @param string $openemm_password contains the login password/secret for OpenEMM webservice interface
	 * @param array $soap_parameter (optional) contains parameters that are forwarded directly to the soap class of PHP (e.g. proxy, authentification, ...)
	 */
	public function OpenEMM($wsdl_url, $openemm_login, $openemm_password, $soap_parameter = array ()) {
		$this->soapClient = new SoapClient($wsdl_url, $soap_parameter);
		$this->loginCredential['login'] = $openemm_login;
		$this->loginCredential['password'] = $openemm_password;
	}

	/**
	 * add new subscriber to OpenEMM
	 * @param array $subscriber contains the parameters to create a new subscriber (default parameters by OpenEMM: email, title, firstname, lastname, gender, datasource_id, mailtype)
	 * @param string $key_column specifies the key column used to identifiy duplicated entries (optional, default: email, use email address as unique key)
	 * @param bool $doublecheck if true, check that receipients are only one time in database, no duplicate entries (optional, default: true)
	 * @param bool $overwrite if true, overwrite existing entries (unset param values will be set to ""); (optional, default: false)
	 * @return int id of created subscriber if succeed, misc otherwise
	 */
	public function addSubscriber($subscriber, $key_column = 'email', $doublecheck = true, $overwrite = false) {
		$params = array (
			'in0' => $this->loginCredential['login'],
			'in1' => $this->loginCredential['password'],
			'in2' => $doublecheck,
			'in3' => $key_column,
			'in4' => $overwrite,
			'in5' => array_keys($subscriber),
			'in6' => array_values($subscriber)
		);
		$customerId = $this->soapClient->__soapCall('addSubscriber', $params);
		// TODO check + validate result
		return $customerId;
	}

	/**
	 * this function returns information for given subscriber_id
	 * @param int $subscriber_id id of subscriber
	 * @return array containing information about subscriber
	 */
	public function getSubscriber($subscriber_id) {
		$params = array (
			'in0' => $this->loginCredential['login'],
			'in1' => $this->loginCredential['password'],
			'in2' => $subscriber_id
		);
		$soapResult = $this->soapClient->__soapCall('getSubscriber', $params);
		// TODO check + validate result
		return $this->__generateArrayFromSOAPResult($soapResult->paramNames->x, $soapResult->paramValues->x);
	}

	/**
	 * this function deletes a subscriber with given subscriber_id
	 * @param int $subscriber_id id of subscriber
	 * @return bool true if succeed, false otherwise
	 */
	public function deleteSubscriber($subscriber_id) {
		$params = array (
			'in0' => $this->loginCredential['login'],
			'in1' => $this->loginCredential['password'],
			'in2' => $subscriber_id
		);
		$soapResult = $this->soapClient->__soapCall('deleteSubscriber', $params);
		// TODO check + validate result
		return $soapResult == 1;
	}

	/**
	 * this function searches for subscriber with given conditions
	 * @param string $key_column column in database that should be used for search
	 * @param string $value will be used for search
	 * @return int 0 if no entry found, subscriber id of first match
	 */
	public function findSubscriber($key_column, $value) {
		$params = array (
			'in0' => $this->loginCredential['login'],
			'in1' => $this->loginCredential['password'],
			'in2' => $key_column,
			'in3' => $value
		);
		$subscriberId = $this->soapClient->__soapCall('findSubscriber', $params);
		// TODO check + validate result
		return $subscriberId;
	}

	/**
	 * this function updates a given subscriber with information specified in $subscriber
	 * @param int $subscriber_id id of subscriber
	 * @param array $subscriberData containing new information for subscriber to update
	 * @return bool true if succeed, false otherwise
	 */
	public function updateSubscriber($subscriber_id, $subscriberData) {
		$params = array (
			'in0' => $this->loginCredential['login'],
			'in1' => $this->loginCredential['password'],
			'in2' => $subscriber_id,
			'in3' => array_keys($subscriberData),
			'in4' => array_values($subscriberData)
		);
		return $this->soapClient->__soapCall('updateSubscriber', $params) == true;
	}

	/**
	 * this function sets subscriber binding information for given subscriber_id and mailinglist_id
	 * @param int $subscriber_id id of subscriber
	 * @param int $mailinglist_id id of mailing list
	 * @param int $status status in mailing list (1 = active, 2 = bounced, 3 = opt-out by admin, 4 = opt-out by user); optional, default: 1 = active
	 * @param int $binding_type type in mailing list ('A' = admin subscriber, 'T' = test subscriber, 'W' = normal subscriber); optional, default: 'W' = normal subscriber
	 * @param int $remark comment for binding, e.g. "opt-in by application x"; optional, default: "" (empty string)
	 * @param int $exit_mailing_id ID of the mailing the recipent had bounced (0 if unknown); optional, default: 0 (unknown)
	 * @param int $mediatype mediatype for what the binding should be retrieved (0 = email, 1 = sms); optional, default: 0 = email
	 * @return bool true if succeed, false otherwise
	 */
	public function setSubscriberBinding($subscriber_id, $mailinglist_id, $status = 1, $binding_type = 'W', $remark = '', $exit_mailing_id = 0, $mediatype = 0) {
		$params = array (
			 'in0' => $this->loginCredential['login'],
			 'in1' => $this->loginCredential['password'],
			 'in2' => $subscriber_id,
			 'in3' => $mailinglist_id,
			 'in4' => $mediatype,
			 'in5' => $status,
			 'in6' => $binding_type,
			 'in7' => $remark,
			 'in8' => $exit_mailing_id
		); 
		$updated_subscriber_id = $this->soapClient->__soapCall('setSubscriberBinding', $params); // returns the customer_id/subscriber_id of updated/added subscriber
		return $updated_subscriber_id === $subscriber_id; // the ids have to match to indicate an correct, valid update
	}

	/**
	 * set up a new email mailing, use insertContent to set the message content and sendMailing to send out the mailing
	 * @param string $shortname internal name of mailing
	 * @param string $description internal description of mailing
	 * @param int $mailinglist_id id of mailing list (must exist in OpenEMM)
	 * @param int $mailing_typ type of mailing (0 = normal mailing, 1 = event-based mailing, 2 = rule-based mailing)
	 * @param int $template_id id of template or 0 for no template
	 * @param string $email_subject subject of email
	 * @param string $email_sender address of email sender, e.g. 'mail@domain.com' or '"Sender name"<mail@domain.com>'
	 * @param int $target_id list of ids of target group or array('0') for all email addresses; optional, default: 0 (all email addresses)
	 * @param int $email_format format of email (0 = text only, 1 = text + html (Multipart), 2 = text + html + offline (Multipart + embedded graphics)); optional, default: 1
	 * @param string $email_charset charset of email; optional, default: iso-8859-1
	 * @param string $email_linefeed automated linefeed in the text-version of the email; optional, default: "\n\r"
	 * @return int id of created mailing
	 */
	public function newEmailMailing($shortname, $description, $mailinglist_id, $mailing_type, $template_id, $email_subject, $email_sender, $target_ids = array(), $email_format = 1, $email_charset = "iso-8859-1", $email_linefeed = "1") {
		if (is_null($target_ids) || count($target_ids) == 0) {
			$target_ids = array('0');
		}
		
		$params = array (
			'in0' => $this->loginCredential['login'],
			'in1' => $this->loginCredential['password'],
			'in2' => $shortname,
			'in3' => $description,
			'in4' => $mailinglist_id,
			'in5' => $target_ids,
			'in6' => $mailing_type,
			'in7' => $template_id,
			'in8' => $email_subject,
			'in9' => $email_sender,
			'in10' => $email_charset,
			'in11' => $email_linefeed,
			'in12' => $email_format
		);

		$mailing_id = $this->soapClient->__soapCall('newEmailMailing', $params);
		return $mailing_id;
	}

	/**
	 * set up a new email mailing with reply address; use insertContent to set the message content and sendMailing to send out the mailing
	 * @param string $shortname internal name of mailing
	 * @param string $description internal description of mailing
	 * @param int $mailinglist_id id of mailing list (must exist in OpenEMM)
	 * @param int $mailing_typ type of mailing (0 = normal mailing, 1 = event-based mailing, 2 = rule-based mailing)
	 * @param int $template_id id of template or 0 for no template
	 * @param string $email_subject subject of email
	 * @param string $email_sender address of email sender, e.g. 'mail@domain.com' or '"Sender name"<mail@domain.com>'
	 * @param string $email_reply reply-to address of email, e.g. 'mail@domain.com' or '"Sender name"<mail@domain.com>'
	 * @param int $target_id id of target group or 0 for all email addresses; optional, default: 0 (all email addresses)
	 * @param int $email_format format of email (0 = text only, 1 = text + html (Multipart), 2 = text + html + offline (Multipart + embedded graphics)); optional, default: 1
	 * @param string $email_charset charset of email; optional, default: iso-8859-1
	 * @param string $email_linefeed automated linefeed in the text-version of the email; optional, default: "\n\r"
	 * @return int id of created mailing
	 */
	public function newEmailMailingWithReply($shortname, $description, $mailinglist_id, $mailing_type, $template_id, $email_subject, $email_sender, $email_reply, $target_id = 0, $email_format = 1, $email_charset = "iso-8859-1", $email_linefeed = "\n\r") {

		$params = array (
			'in0'  => $this->loginCredential['login'],
			'in1'  => $this->loginCredential['password'],
			'in2'  => $shortname,
			'in3'  => $description,
			'in4'  => $mailinglist_id,
			'in5'  => $target_id,
			'in6'  => $mailing_type,
			'in7'  => $template_id,
			'in8'  => $email_subject,
			'in9'  => $email_sender,
			'in10' => $email_reply,
			'in11' => $email_charset,
			'in12' => $email_linefeed,
			'in13' => $email_format
		);
		$mailing_id = $this->soapClient->__soapCall('newEmailMailingWithReply', $params);
		// TODO check + validate result
		return $mailing_id;
	}

	/**
	 * update email mailing with reply address; use insertContent to set the message content and sendMailing to send out the mailing
	 * @param string $shortname internal name of mailing
	 * @param string $description internal description of mailing
	 * @param int $mailinglist_id id of mailing list (must exist in OpenEMM)
	 * @param int $mailing_typ type of mailing (0 = normal mailing, 1 = event-based mailing, 2 = rule-based mailing)
	 * @param int $template_id id of template or 0 for no template
	 * @param string $email_subject subject of email, will be overwritten every time!
	 * @param string $email_sender address of email sender, e.g. 'mail@domain.com' or '"Sender name"<mail@domain.com>'
	 * @param string $email_reply reply-to address of email, e.g. 'mail@domain.com' or '"Sender name"<mail@domain.com>'
	 * @param int $target_id id of target group or 0 for all email addresses; optional, default: 0 (all email addresses)
	 * @param int $email_format format of email (0 = text only, 1 = text + html (Multipart), 2 = text + html + offline (Multipart + embedded graphics)); optional, default: 1
	 * @param string $email_charset charset of email; optional, default: iso-8859-1
	 * @param string $email_linefeed automated linefeed in the text-version of the email; optional, default: "\n\r"
	 * @return bool true if succeed, false otherwise
	 */
	public function updateEmailMailing($shortname, $description, $mailinglist_id, $mailing_type, $template_id, $email_subject, $email_sender, $email_reply, $target_id = 0, $email_format = 1, $email_charset = "iso-8859-1", $email_linefeed = "\n\r") {

		$params = array (
			'in0' => $this->loginCredential['login'],
			'in1' => $this->loginCredential['password'],
			'in2' => $shortname,
			'in3' => $description,
			'in4' => $mailinglist_id,
			'in5' => $target_id,
			'in6' => $mailing_type,
			'in7' => $template_id,
			'in8' => $email_subject,
			'in9' => $email_sender,
			'in10' => $email_reply,
			'in11' => $email_charset,
			'in12' => $email_linefeed,
			'in13' => $email_format
		);
		$soap_result = $this->soapClient->__soapCall('updateEmailMailing', $params);
		return $soap_result == true;
	}

	/**
	 * add content for an existing mailing
	 * @param int $mailing_id id of mailing
	 * @param string $block_name name of content block, depends on the template (if no template has been used, only emailText and emailHtml are available)
	 * @param string $block_content content of the block
	 * @param int $target_id id of target group of this content block
	 * @param int $priority priority of content block (smaller numbers => higher priority)
	 * @return int length of block content, or 0 (as written in Webservice documentation 1.0.3)
	 */
	public function insertContent($mailing_id, $block_name, $block_content, $target_id = 0, $priority = 10) {
		$params = array (
			'in0' => $this->loginCredential['login'],
			'in1' => $this->loginCredential['password'],
			'in2' => $mailing_id,
			'in3' => $block_name,
			'in4' => $block_content,
			'in5' => $target_id,
			'in6' => $priority
		);

		$soap_result = $this->soapClient->__soapCall('insertContent', $params);
		return $soap_result;
	}

	/**
	 * deletes content of an existing mailing
	 * @param int $content_id id of content
	 * @return bool true if succeed, false otherwise
	 */
	public function deleteContent($content_id) {
		$params = array (
			'in0' => $this->loginCredential['login'],
			'in1' => $this->loginCredential['password'],
			'in2' => $content_id
		);

		$soap_result = $this->soapClient->__soapCall('deleteContent', $params);
		return $soap_result == 1;
	}

	/**
	 * send admin, test and world-mailings
	 * @param int $mailing_id id of mailing to send
	 * @param string $send_group user group to which the email will be sent ('A' = admin only, 'T' = test- and admin subscribers, 'W' = all subscribers(can only be executed once))
	 * @param int $send_time unix timestamp of sending
	 * @param int $stepping artificially slowing down sending process, delay between sending emails; optional, default: 0
	 * @param int $blocksize artificially slowing down sending process, number of emails in one mailing-block; optional, default: 0
	 * @return bool true if succeed, false otherwise
	 */
	public function sendMailing($mailing_id, $send_group, $send_time, $stepping = 0, $blocksize = 0) {
		$params = array (
			'in0' => $this->loginCredential['login'],
			'in1' => $this->loginCredential['password'],
			'in2' => $mailing_id,
			'in3' => $send_group,
			'in4' => $send_time,
			'in5' => $stepping,
			'in6' => $blocksize
		);

		$soap_result = $this->soapClient->__soapCall('sendMailing', $params);
		return $soap_result == 1;
	}

	/**
	 * generates an array containing the correct timestamp used by OpenEMM webservice to describe date / timestamp
	 * @param string $fieldname name of variable
	 * @param int $unix_timestamp contains the timestamp in unix timeformat
	 * @param bool $with_time_information add also time information to result; optional, default: true
	 * @return array containing the date/time information in OpenEMM format
	 */
	public function generateDateArray($fieldname, $unix_timestamp, $with_time_information = true) {
		$result = array ();
		$result[$fieldname . '_DAY_DATE'] = date('d', $unix_timestamp);
		$result[$fieldname . '_MONTH_DATE'] = date('m', $unix_timestamp);
		$result[$fieldname . '_YEAR_DATE'] = date('Y', $unix_timestamp);

		if ($with_time_information === true) {
			// add time information
			$result[$fieldname . '_HOUR_DATE'] = date('G', $unix_timestamp);
			$result[$fieldname . '_MINUTE_DATE'] = date('i', $unix_timestamp);
			$result[$fieldname . '_SECOND_DATE'] = date('s', $unix_timestamp);
		}
		return $result;
	}

	/**
	 * this function calculates a unix timestamp from given date_array for given fieldname
	 * @param string $fieldname name of variable
	 * @param array containing the date/time information in OpenEMM format
	 * @return int timestamp in unix timeformat
	 */
	public function generateUnixTimestampFromDateArray($fieldname, $date_array) {
		$hour = (isset ($date_array[$fieldname . '_HOUR_DATE']) && is_numeric($date_array[$fieldname . '_HOUR_DATE'])) ? $date_array[$fieldname . '_HOUR_DATE'] : 0;
		$minute = (isset ($date_array[$fieldname . '_MINUTE_DATE']) && is_numeric($date_array[$fieldname . '_MINUTE_DATE'])) ? $date_array[$fieldname . '_MINUTE_DATE'] : 0;
		$second = (isset ($date_array[$fieldname . '_SECOND_DATE']) && is_numeric($date_array[$fieldname . '_SECOND_DATE'])) ? $date_array[$fieldname . '_SECOND_DATE'] : 0;

		return mktime($hour, $minute, $second, $date_array[$fieldname . '_MONTH_DATE'], $date_array[$fieldname . '_DAY_DATE'], $date_array[$fieldname . '_YEAR_DATE']);
	}

	/**
	 * this function returns subscriber binding information for given subscriber_id and mailinglist_id
	 * @param int $subscriber_id id of subscriber
	 * @param int $mailinglist_id id of mailing list
	 * @param int $mediatype mediatype for what the binding should be retrieved (0 = email, 1 = sms); optional, default: 0 = email
	 * @return array containing subscriber status (status, bindingType, existMailingID, remark)
	 */
	public function getSubscriberBinding($subscriber_id, $mailinglist_id, $mediatype = 0) {
		$params = array (
			'in0' => $this->loginCredential['login'],
			'in1' => $this->loginCredential['password'],
			'in2' => $subscriber_id,
			'in3' => $mailinglist_id,
			'in4' => $mediatype
		);
		$soapResult = $this->soapClient->__soapCall('getSubscriberBinding', $params);
		// TODO check + validate result
		return $this->__generateArrayFromSOAPResult($soapResult->paramNames->x, $soapResult->paramValues->x);
	}

	/**
	 * this function generates an array with names as keys and values as key_values
	 * @param array $names array with name pairs ($rownumber, $name)
	 * @param array $values array with value pairs ($rownumber, $value)
	 */
	private function __generateArrayFromSOAPResult($names, $values) {
		$result = array ();
		foreach ($names as $key => $name) {
			$result[$name] = $values[$key];
		}
		// ignore additional values in $values
		return $result;
	}
}
