<?php namespace de\roccogossmann\php\core;

require_once __DIR__ . "/vendor/PHPMailer/src/Exception.php";
require_once __DIR__ . "/vendor/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/vendor/PHPMailer/src/SMTP.php";
require_once __DIR__ . "/tSingleton.php";

use PHPMailer\PHPMailer\PHPMailer;

class Mailer extends PHPMailer {
    use tSingleton;

    private function __construct() { 
		parent::__construct(true);

		$this->isSMTP();                                      //Send using SMTP
		$this->Host       = $this->_getEnvVar("MAILER_HOST"); //Set the SMTP server to send through
    	$this->Port       = $this->_getEnvVar("MAILER_PORT"); //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

		$this->SMTPAuth   = true;                             //Enable SMTP authentication
		$this->Username   = $this->_getEnvVar("MAILER_USER"); //SMTP username
		$this->Password   = $this->_getEnvVar("MAILER_PASS"); //SMTP password

    	if(!empty(getenv("MAILER_SECURE")))
        	$this->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  //Enable implicit TLS encryption
    }

	public function from(string $sEmail, string $sName="") { $this->setFrom($sEmail, $sName); return $this; }
	public function to(string $sEmail, string $sName="")   { $this->addAddress($sEmail, $sName); return $this; }
	public function bcc(string $sEmail, string $sName="")  { $this->addBCC($sEmail, $sName); return $this; }
	public function cc(string $sEmail, string $sName="")   { $this->addCC($sEmail, $sName); return $this; }

	public function subject(string $subject)   { $this->Subject = $subject; return $this; }

	public function html(string $content) { 
		$this->isHTML(true);  
		$this->Body = $content; 
		if(empty($this->AltBody)) 
			$this->AltBody = $content;

		return $this;
	}

	public function text(string $content) {
		$this->AltBody = $content;
		if(empty($this->Body)) $this->Body = "<pre>" . $content . "</pre>";;
		return $this;
	}

	private function _getEnvVar($sVar) {
		if(empty(getenv($sVar)))
			throw new \PHPMailer\PHPMailer\Exception(
				" please define EnvironmentVariable '$sVar'. Either via .htaccess, Server-Settings or the setenv-PHP-Method"
			);

		return getenv($sVar);
	}
}
