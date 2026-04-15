<?php

namespace rogoss\core;

require_once __DIR__ . "/vendor/PHPMailer/src/Exception.php";
require_once __DIR__ . "/vendor/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/vendor/PHPMailer/src/SMTP.php";
require_once __DIR__ . "/tSingleton.php";

use PHPMailer\PHPMailer\PHPMailer;

class Mailer extends PHPMailer {
	use tSingleton;
	use tDebug;

	private $offlineMode = false;
	private $mailerFile = null;

	private function __construct() {

		parent::__construct(true);

		$this->isSMTP();                                      //Send using SMTP
		$this->Host       = $this->_getEnvVar("MAILER_HOST"); //Set the SMTP server to send through
		$this->Port       = $this->_getEnvVar("MAILER_PORT"); //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

		if (!empty(getenv("MAILER_USER"))) {
			$this->SMTPAuth   = true;                             //Enable SMTP authentication
			$this->Username   = $this->_getEnvVar("MAILER_USER"); //SMTP username
			$this->Password   = $this->_getEnvVar("MAILER_PASS"); //SMTP password
		}

		if (!empty(Utils::unpackEnv("MAILER_SECURE")))
			$this->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  //Enable implicit TLS encryption

		if (!empty($tmp = Utils::unpackEnv("MAILER_SENDER_MAIL")))
			$this->setFrom($tmp, $tmp);

		$this->toErrorLog([
			"host" => $this->Host,
			"Port" => $this->Port,
			"SmtpAuth" => $this->SMTPAuth,
			"SmtpSecure" => $this->SMTPSecure
		]);

	}

	public function from(string $sEmail, string $sName = "") {

		$this->setFrom($sEmail, $sName);
		return $this;
	}
	public function to(string $sEmail, string $sName = "") {

		$this->addAddress($sEmail, $sName);
		return $this;
	}
	public function bcc(string $sEmail, string $sName = "") {

		$this->addBCC($sEmail, $sName);
		return $this;
	}
	public function cc(string $sEmail, string $sName = "") {

		$this->addCC($sEmail, $sName);
		return $this;
	}

	public function subject(string $subject) {

		$this->Subject = $subject;
		return $this;
	}

	public function html(string $content) {

		$this->isHTML(true);
		$this->Body = $content;
		if (empty($this->AltBody))
			$this->AltBody = $content;

		return $this;
	}

	public function send() {

		if ($this->offlineMode) {

			$vars = array_intersect_key(get_object_vars($this), array_flip([
				'Priority',
				'CharSet',
				'ContentType',
				'Encoding',
				'From',
				'FromName',
				'Sender',
				'Subject',
				'Body',
				'AltBody',
				'to',
				'cc',
				'bcc',
				'ReplyTo',
				'RecipientsQueue',
				'attachment',
			]));

			file_put_contents($this->mailerFile, var_export($vars, true), FILE_APPEND);
			return;
		}

		parent::send();
	}

	public function text(string $content) {

		$this->AltBody = $content;
		if (empty($this->Body)) $this->Body = "<pre>" . $content . "</pre>";;
		return $this;
	}

	private function _getEnvVar($envVarName) {

		$envVarValue = Utils::unpackEnv($envVarName);
		if (empty($envVarValue)) {

			if (!$this->offlineMode) {

				$this->mailerFile = "./mailer_offline.log";
				touch($this->mailerFile);
				$this->mailerFile = realpath("./mailer_offline.log");

				error_log("rgoss\core\Mailer in offlinemode. Missing evnvar '$envVarName'. Mails are sende to '{$this->mailerFile}'", E_USER_WARNING);

				if (!$this->mailerFile)
					trigger_error("fallback for mailer offline mode failed. could not create {$this->mailerFile}", E_USER_ERROR);
			}

			$this->offlineMode = true;
		}

		return $envVarValue;
	}
}
