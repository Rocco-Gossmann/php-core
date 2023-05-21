<?php namespace de\roccogossmann\php\core;


class OSSL {

	public static function createNewKeys(&$sPrivateKey, &$sPublicKey) 
	{
		$sConfFile = "/etc/ssl/openssl.cnf";

		if(!is_file($sConfFile) and !empty(getenv("OPENSSL_CONF"))) 
			$sConfFile = getenv("OPENSSL_CONF");

		if(empty($sConfFile) or !is_file($sConfFile)) 
			throw new OSSLException("missing config file. Please define the 'OPENSSL_CONF' Environment variable to define, what file to use", OSSLException::MISSING_CONFIG);

		$aConf = [
			"private_key_bits" => 2048,
			"private_key_type" => OPENSSL_KEYTYPE_RSA,
		];

		if(is_file($sConfFile))
			$aConfig['config'] = $sConfFile;

		$sPKey = openssl_pkey_new($aConf);		
		if(!$sPKey) echo openssl_error_string();
		else {

			$sPrKey =""; 
			if(!openssl_pkey_export($sPKey, $sPrKey, null, $aConf))
				 echo "pkey_export error ", openssl_error_string();
			else {
				var_dump($sPrKey);
				$sPuKey = (openssl_pkey_get_details($sPKey)??[])['key']??false;
				if(empty($sPuKey))
					echo "pkey_export error ", openssl_error_string();
				else  {
					var_dump($sPuKey);
					$sData = "Hello World\nHowAre you 😁";

					$sEnc = "";
					$sDec = "";

					openssl_private_encrypt($sData, $sEnc, $sPrKey);
					openssl_public_decrypt($sEnc, $sDec, $sPuKey);


					if($sDec == $sData) {
						$sPublicKey = $sPuKey;
						$sPrivateKey = $sPrKey;
					}
				}
			} 
		}					
	}

	public static function decrypter($sPubKey) 
	{
		$oInst = new static();
		$oInst->sPubKey = $sPubKey;
		return $oInst;	
	}

	public static function encrypter($sPrivKey) 
	{
		$oInst = new static();
		$oInst->sPrivKey = $sPrivKey;
		return $oInst;	
	}

	public static function basicCrypter($sPassPhrease, $iv="") {
		$i = new static();
		$i->bBasic = true;
		$i->sPassPhrease = $sPassPhrease;
		$i->sIV = $iv;

		while(strlen($i->sIV) < 16)
			$i->sIV .= $sPassPhrease;	
	
		$i->sIV = substr($i->sIV, 0, 16);
		return $i;
	}

	private $sPubKey = "";
	private $sPrivKey = "";

	private $bJSON = false;
	private $bBasic = false;
	private $sPassPhrease = "";
	private $sIV = "";

	private function __construct() { }

		/** Any data will be converted to JSON on encryption and parsed as JSON on decryption */
	public function json() {
		$this->bJSON = true;
		return $this;
	}

	public function encrypt($sData, $bRaw=false) 
	{
		if($this->bJSON) $sData = json_encode($sData);


		if($this->bBasic) {
			$iOpt = $bRaw ? OPENSSL_RAW_DATA : 0;
			$sEnc = openssl_encrypt($sData, 'aes-128-cbc',  $this->sPassPhrease, $iOpt, $this->sIV);
			$bRaw = true;
		}
		else {

			if(empty($this->sPrivKey)) 
				throw new OSSLException("no private key", OSSLException::WRONG_MODE);

			$sEnc = "";	
			if(!openssl_private_encrypt($sData, $sEnc, $this->sPrivKey)) {
				$sErr = openssl_error_string();
				throw new OSSLException($sErr);
			}

		}
		return $bRaw ? $sEnc : base64_encode($sEnc);
	}

	public function decrypt($sData, $bRaw=false) 
	{
		if($this->bBasic) {
			$iOpt = $bRaw ? OPENSSL_RAW_DATA : 0;
			$sDec = openssl_decrypt($sData, 'aes-128-cbc',  $this->sPassPhrease, $iOpt, $this->sIV);
		}
		else {
			$sRaw = $bRaw ? $sData : base64_decode($sData);
			if(empty($this->sPubKey)) 
				throw new OSSLException("no public key", OSSLException::WRONG_MODE);

			$sDec = "";	
			if(!openssl_public_decrypt($sRaw, $sDec, $this->sPubKey))
				throw new OSSLException(openssl_error_string());
		}

		if($this->bJSON) $sDec = json_decode($sDec, true);

		return $sDec;
	}
}

class OSSLException extends \Exception {
	const OPENSSL_ERROR = 0;
	const WRONG_MODE = 1;
	const MISSING_CONFIG = 2;
}
