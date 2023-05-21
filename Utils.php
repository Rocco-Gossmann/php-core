<?php namespace de\roccogossmann\php\core;

use Closure;

class Utils {

    public static function flattenArray(array $aMultimensionalArray, Closure $sKeyGenerator=null, string $_sPrefix="", array &$_output=[]) {

        if(!is_callable($sKeyGenerator)) $sKeyGenerator = fn($sKey, $sPrefix) => empty($sPrefix) ? $sKey : $sPrefix . "." . $sKey;

        foreach($aMultimensionalArray as $sKey => $mValue) {
            $sPrefix = $sKeyGenerator($sKey, $_sPrefix);
            if(is_array($mValue) && !empty($mValue)) self::flattenArray($mValue, $sKeyGenerator, $sPrefix, $_output);
            else $_output[$sPrefix] = $mValue;
        }

        return $_output;
    }

	public static function generateHumanReadableToken($iNumberOfChars) {
		$aArr = [
			'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'j', 'k', 'm', 'n', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
			'1', '2', '3', '4', '5', '6', '7', '8', '9', '0'
		];

		shuffle($aArr);
		$sRet = "";
		$iLimit = count($aArr)-1;
		for($a = 0; $a < $iNumberOfChars; $a++) {
			$sRet .= $aArr[rand(0, $iLimit)];
		}

		return $sRet;
	}


}
