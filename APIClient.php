<?php namespace de\rogoss\php\core;

class APIClient {

	const METHOD_POST    = "POST";
	const METHOD_PUT     = "PUT";
	const METHOD_DELETE  = "DELETE";
	const METHOD_GET     = "GET";

	private static $_debug = false;

	public static function debug($bMode) {
		self::$_debug = $bMode != false;
	}

	public static function log() {
		if(!self::$_debug) return;
		foreach(func_get_args() as $mArgs) {
			echo "[",__CLASS__," Debug] ", var_export($mArgs, true), "<br />\n";
		}
	}

	public static function setup($sBaseURL) {
		$oI = new static();
		$oI->sBaseURL = rtrim($sBaseURL, "/") . "/";	
		return $oI;
	}


	private $sBaseURL = "";


	/**
	 * calls a url 
	 *
	 * @param string $method - can be "post", "get" or "put", "delete"
	 * @param string $path - the path after $this->sBaseURL
	 * @param mixed  $data - the request-body / get-/post data
	 *
	 * @return FetchResult
	 */
	function fetch($method, $path, $data = false, $headers=[])
	{
        $path = ltrim($path, "/");

    	$curl = curl_init();
		$sURL = $this->sBaseURL . $path;

		switch (strtoupper($method))
		{
		case self::METHOD_PUT:
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");

		case self::METHOD_POST:
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
			if ($data) 
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			break;

		case self::METHOD_DELETE:
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");

		case self::METHOD_GET:
				if ($data and is_array($data))
					$sURL .= "?" . http_build_query($data);

				$data = "";
				break;

		default:
				throw new APIClientException("method '$method' is not know to '".__CLASS__."' please use only the '".__CLASS__."::METHOD_' constants");
		}


		curl_setopt($curl, CURLOPT_URL, $sURL);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_VERBOSE, true);

		if(is_array($headers)) {
			$aHeaders = [];
			$sFirstLine = "";

			foreach($headers as $sHeader => $sValue) {
				if(is_numeric($sHeader)) {
					$aParts = explode(":", $sValue, 2);
					if(count($aParts) == 2) {
						$aHeaders[strtolower($aParts[0])] = $aParts[1];
					}
					else $sFirstLine = $sValue;
				}
				else {
					$aHeaders[strtolower($sHeader)] = $sValue;
				}
			}

			$aHeaders = array_map(

				fn($i, $e) => "$i: $e", 

				array_keys($aHeaders), 
				array_values($aHeaders)
			);	

			if(!empty($sFirstLine))
				array_unshift($aHeaders, $sFirstLine);

			curl_setopt($curl, CURLOPT_HTTPHEADER, $aHeaders);
		}

		self::log("Call: ", strtoupper($method) . " " . $sURL . "\n\n" . implode("\n", $aHeaders) . "\n\n", $data);

		if(self::$_debug) {
			$streamVerboseHandle = fopen('php://temp', 'w+');
			curl_setopt($curl, CURLOPT_STDERR, $streamVerboseHandle);
		}
		$result = curl_exec($curl);

		if(self::$_debug) {
			rewind($streamVerboseHandle);
			$verboseLog = stream_get_contents($streamVerboseHandle);
			self::log("Verbose information:", $verboseLog);
		}


		return new FetchResult($result);
	}

	private function __construct() { }
}

class FetchResult {
	/** @var string */
	public $protokoll = "";

	/** @var int */
	public $status = 0;

	/** @var string */
	public $statusText = "";

	/** @var string */
	public $body = "";

	/** @var array */
	public $headers = [];

	public function __construct($r)
	{
		$aParts = explode("\n\n", str_replace("\r", "", $r), 2); 
		$this->body = $aParts[1];
		$sHeaders = $aParts[0];
		unset($aParts);

		$aHeaderLines = explode("\n", $sHeaders);

		$aStatusLine = explode(" ", $aHeaderLines[0], 3);
		$this->protokoll = $aStatusLine[0];
		$this->status = (int)$aStatusLine[1];
		$this->statusText = $aStatusLine[2];
		unset($aStatusLine, $aHeaderLines[0]);

		foreach($aHeaderLines as $sHeaderLine) {
			$aParts = explode(": ", $sHeaderLine);
			$this->headers[strtolower($aParts[0])] = $aParts[1];
			unset($aParts);
		}

	}
}

class APIClientException extends \Exception {
	const UNKNOWN_METHOD = 1;
}

