<?php

namespace rogoss\core;

use Closure;

class Utils
{

    /**
     * Turns a multidimensional array into a one dimensional one
     *
     * @param array $aMultimensionalArray the array to convert
     * @param \Closure $sKeyGenerator (optional) is called, to define, what the key of the next element should be. It is given a $key, to define the current key of the element in the current layer  and a $prefix which is all the previews layers combined
     * @param string $_sPrefix (optional) Dont use, this is meant for handling the recursion
     * @param array $_output (optional) Dont use, this is meant for handling the recursion
     *
     * @return array a one dimensional version of the input, in which the keys are combined according to $sKeyGenerator
     *
     * @example
     *    $arr = Utils::flattenArray(
     *      [ "a" => [
     *          "b" => "c",
     *          "d" => [
     *              "e" => "f",
     *              "g" => "h"
     *          ]
              ],
     *        "i" => "j"
     *      ],
     *      fn($key, prefix) => empty($prefix) ? $key  : ($prefix . "." . $key)
     *   )
     *
     *   results in:
     *   [
     *        "a.b" => "c"
     *      , "a.d.e" => "f"
     *      , "a.d.g" => "h"
     *      , "i" => "j"
     *   ]
     *
     */
    public static function flattenArray(array $aMultimensionalArray, ?Closure $sKeyGenerator = null, string $_sPrefix = "", array &$_output = [])
    {
        if (!is_callable($sKeyGenerator)) $sKeyGenerator = fn ($sKey, $sPrefix) => empty($sPrefix) ? $sKey : $sPrefix . "." . $sKey;

        foreach ($aMultimensionalArray as $sKey => $mValue) {
            $sPrefix = $sKeyGenerator($sKey, $_sPrefix);
            if (is_array($mValue) && !empty($mValue)) self::flattenArray($mValue, $sKeyGenerator, $sPrefix, $_output);
            else $_output[$sPrefix] = $mValue;
        }

        return $_output;
    }

    /**
     * Generates a random String out of only Human readable characters.
     * Good to generate codes for stuff like 2 Factor Auth or Captchas
     *
     * DO NOT USE GENERATE USER PASSWORDS WITH THIS !!!
     *
     * @param int $iNumberOfChars the number of characters, the string should have
     *
     * @return string
     */
    public static function generateHumanReadableToken($iNumberOfChars)
    {
        $aArr = [
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'j', 'k', 'm', 'n', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'
        ];

        shuffle($aArr);
        $sRet = "";
        $iLimit = count($aArr) - 1;
        for ($a = 0; $a < $iNumberOfChars; $a++) {
            $sRet .= $aArr[rand(0, $iLimit)];
        }

        return $sRet;
    }


    /**
     * Mutates a given array directly, unlike array_replace_recursive, which generates a new Array
     *
     * @param array $aTarget The array to mutate
     * @param array $aMutations the data to muate with
     *              Should a mutation key point to `null` than that value will not be mutated
     */
    public static function mutateArrayRecursive(array &$aTarget, array $aMutations)
    {
        foreach($aMutations as $mKey => $mValue) {
            if($mValue === null) continue;
            if(isset($aTarget[$mKey])) {
                if(is_array($aTarget[$mKey]) and is_array($mValue))
                    self::mutateArrayRecursive($aTarget[$mKey], $mValue);
                else $aTarget[$mKey] = $mValue;
            }
            else $aTarget[$mKey] = $mValue;
        }
    }

	/**
	 * allows for using password-store pathes in EnvVars.
	 * (By wrting the the var in the following schema  `export ENV_VAR="ps[line]:[path_in_password_store]"`)
	 * envVars that don't fall into that schema are returned as they are.
	 * That way passwords can be stored savely. Only the Server has the required GPG-Keys to decrypt the passwords.
	 *
	 * @param string $envVar - name of the Environment variable
	 *
	 * @example
	 *  `export MAILER_HOST="ps1:dev/Mailer"` => extracts the first line of the pass-stores "dev/Mailer" value as MAILER_HOST
	 *  `export MAILER_HOST="localhost" => uses "localhost" as the MAILER_HOST
	 *
	 * @return string - the value of the unpacked store or the value of the EnvVar.
	 */
	private static $_envCache = [];
	public static function unpackEnv($envVar) : string {

		if(isset(self::$_envCache[$envVar]))
			return self::$_envCache[$envVar];

		$var = trim(getenv($envVar) ?? "");
		$matches = [];

		if(preg_match("/ps(?<line>[\d]+):(?<store>.*)$/", $var, $matches))
			$var = trim(shell_exec("pass show '{$matches['store']}' | head -n{$matches['line']} | tail -n1"));

		self::$_envCache[$envVar] = $var;

		return $var;

	}
}
