<?php  namespace de\rogoss\php\core;

ob_start();

class APIBase {
	const METHOD_GET = "GET";
	const METHOD_POST = "POST";
	const METHOD_PUT = "PUT";
    const METHOD_PATCH = "PATCH";
	const METHOD_DELETE = "DELETE";

	public $sBody = "";
	protected $aHeaders = [];
}

class APIResponse extends APIBase {

	protected $iStatus = 200;
	protected $aFixedHeaders = [
		"Access-Control-Allow-Headers" => "Authorization, Content-Type",
		"Access-Control-Allow-Credentials" => "true"
	];

	protected $sMethods = "OPTIONS, GET, POST, PUT, DELETE, PATCH";
	protected $sOrigin = "*";

	public function allowOrigin($sOrigin) { 
		$this->sOrigin = $sOrigin; 
		return $this; 
	}

	public function allowMethods( $aMethods ) {
		if(!is_array($aMethods)) $aMethods = [ $aMethods . "" ];

		$this->sMethods = strtoupper(implode(", ", $aMethods));
		return $this;
	}

	public function status($iStatus) { 
		$this->iStatus = (int)$iStatus; 
		return $this;
	}

	public function header($sHeader, $sContent="") {
		if(preg_match("/:/", $sHeader)) {
			$aParts = explode(":", $sHeader, 2);
			$sContent = trim($aParts[1]);
			$sHeader = trim($aParts[0]);
		}
		$this->aHeaders[strtolower($sHeader)] = $sContent;
		return $this;
	}

	public function noCache() {
		return 
		$this->header("Cache-Control", "no-cache, no-store, must-revalidate")
		     ->header("Pragma", "no-cache")
		     ->header("Expires", 0);
	}

	public function text($sBody) { return $this->raw("text/plain", $sBody); }
	public function html($sBody) { return $this->raw("application/html", $sBody); }

	public function json($mData) {
		return $this->raw(
			"application/json", 
			json_encode($mData)
		);
	}

	public function error($mMSG)  {
		$sCode = "[".time() . "-" . rand(100, 999) . "]";
		error_log("[" . date("Y-m-d H:i:s") . "]" 
			. $sCode . " =>  " 
			. (!is_string($mMSG) ? var_export($mMSG, true) : $mMSG));

		return $this
			->status(500)
			->text("Technical Error $sCode please contact the API provider")
			->send();
	}

	public function raw($sContentType, $sBody) {
		$this->aFixedHeaders["content-type"] = $sContentType;
		$this->aFixedHeaders["content-length"] = strlen($sBody);
		$this->sBody = $sBody;
		return $this;
	}

	public function send() {
		while(ob_get_level()) ob_end_clean();

		http_response_code($this->iStatus);

		// Merge Headers and FixedHeaders
		$aHeaders = array_merge($this->aHeaders, $this->aFixedHeaders);

		// Append CORS 
		$aHeaders['Access-Control-Allow-Methods'] = $this->sMethods;
		$aHeaders['Access-Control-Allow-Origin']  = $this->sOrigin;

		foreach($aHeaders as $sHeader => $sValue) {
			header("$sHeader: $sValue");
		}

		echo $this->sBody;

		exit;
	}
}


class APIServer extends APIBase {

    /** @return static */
	public static function init(): static {
		$oI = new static();
		$oI->sBody = file_get_contents("php://input");

		switch(strtolower($_SERVER['CONTENT_TYPE'] ?? "")) {

		case 'application/json':
			$oI->_request = json_decode($oI->sBody, true);
			break;

		default:
            switch(strtoupper($_SERVER['REQUEST_METHOD'])) {
                case "PUT":
                case "PATCH":
					parse_str($oI->sBody, $oI->_request);
                    break;

                case "POST":
                    $oI->_request = $_POST;
                    break;

                default:
                    $oI->_request = $_GET;
            }
		}

		// Read Headers
		foreach($_SERVER as $sKey => $mValue) {
			$arr = [];
			if(!preg_match("/^HTTP_(.*)$/", $sKey, $arr)) continue;
			$oI->aHeaders[strtolower(str_replace("_", "-", $arr[1]))] = $mValue;
		}

		foreach(["CONTENT_TYPE", "CONTENT_LENGTH" ] as $sHeader)
			if(isset($_SERVER[$sHeader]))	
				$oI->aHeaders[strtolower(str_replace("_", "-", $sHeader))] = $_SERVER[$sHeader];

		$oI->oResponse = new APIResponse();

		return $oI;
	}	

	public function cors($aOriginWhiteList=[]) {
		$sCurHost = $this->header("origin");
		$sURL = parse_url($sCurHost, PHP_URL_HOST);

		$aHosts = array_flip($aOriginWhiteList);
		
		if(isset($aHosts[$sURL]))
			$this->oResponse->allowOrigin($sCurHost);
		else
			$this->response()
				->allowOrigin($sCurHost)
				->status(403 /* Forbidden */)
				->text("Who tf are you ???")
				->send();


		error_log("current host $sCurHost");
		if(($_SERVER['REQUEST_METHOD']) == "OPTIONS") 
			$this->oResponse->status(200)->send();

		return $this;
	}

	public function forceRequestMethod($aRequestMethods=['POST', 'GET', 'DELETE', 'PUT', 'PATCH']) {
		if(!is_array($aRequestMethods)) $aRequestMethods = [ $aRequestMethods."" ];

		if(!in_array(
			$_SERVER['REQUEST_METHOD'], 
			array_map("strtoupper", $aRequestMethods)
		)) $this->response()
				->status(405 /* Method Not Allowed */)
				->text("Request method '" .strtoupper($_SERVER['REQUEST_METHOD'])."' not supported by endpoint")
				->send();

		$this->oResponse->allowMethods($aRequestMethods);

		return $this;
	}


	private $_request = [];

	/** @var APIResponse */
	private $oResponse = null;

	public function response() { return clone $this->oResponse; }

	public function param($sParam) {
		return $this->_request[$sParam] ?? null;
	}

	public function header($sHeader) {
		$sHeader = strtolower($sHeader);
		return $this->aHeaders[$sHeader] ?? null;
	}

	public function _get($sParam) {
		return $_GET[$sParam] ?? null;
	}

	public function _post($sParam) {
		return $_POST[$sParam] ?? null;
	}

	public function body(): string { return $this->sBody; }

	public function request(): array { return $this->_request; }

	private function __construct() { }
}
