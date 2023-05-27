<?php namespace de\rogoss\php\core;

use Exception;

class SaltSet
{
    public static final function install($sInstallFile="./conf.saltset.php") {
        $aSaltSet = [
            "0" => self::_genRandSalt(),
            "1" => self::_genRandSalt(),
            "2" => self::_genRandSalt(),
            "3" => self::_genRandSalt(),
            "4" => self::_genRandSalt(),
            "5" => self::_genRandSalt(),
            "6" => self::_genRandSalt(),
            "7" => self::_genRandSalt(),
            "8" => self::_genRandSalt(),
            "9" => self::_genRandSalt(),
            "a" => self::_genRandSalt(),
            "b" => self::_genRandSalt(),
            "c" => self::_genRandSalt(),
            "d" => self::_genRandSalt(),
            "e" => self::_genRandSalt(),
            "f" => self::_genRandSalt()
        ];

        file_put_contents($sInstallFile, "<?php\n\$__SaltSet=" . var_export($aSaltSet, true) . ";");

        if(!file_exists($sInstallFile)) 
            throw new SaltSetException("could not create install file '$sInstallFile'", SaltSetException::NO_INSTALLFILE);
    }

    public static final function load($sInstallFile="./conf.saltset.php") {
        if(!is_file($sInstallFile)) self::install($sInstallFile);

        include $sInstallFile; // imports
        /** @var string[] $__SaltSet */

        return new self($__SaltSet);
    }

    private $aSaltSet = [];

    public function hash($mContent) {
        $sRawHash = hash("sha256", $mContent);
        return hash("sha256", $mContent . base64_decode($this->aSaltSet[strtolower(substr($sRawHash, 0, 1))]));
    }

    private function __construct($aSet) {
        $this->aSaltSet = $aSet;
    }


    private static function _genRandSalt() {
        $sOutput = "";

        for($a = 0, $iSize = rand(16, 32); $a < $iSize; $a++) {
            $sChr = chr(rand(1, 255));
            $sOutput .= $sChr;
        }

        return base64_encode($sOutput);
    }
}

class SaltSetException extends Exception {
    const NO_INSTALLFILE = 1;
}
