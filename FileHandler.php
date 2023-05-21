<?php namespace de\roccogossmann\php\core;


class FileHandler {

	public static function copy($sSrcFile, $sDestFile) {
		$sTmpFile = $sDestFile . "." . time() . rand(100, 999) . ".tmp";
		if(!copy($sSrcFile, $sTmpFile))
			throw new FileHandlerException("failed to copy $sSrcFile to $sDestFile (via temp file $sTmpFile)", FileHandlerException::COPY_FAILED );

		if(file_exists($sDestFile))
			unlink($sDestFile);

		rename($sTmpFile, $sDestFile);
	}

	public static function link($sSrcFile, $sDestFile) {
		if(file_exists($sDestFile))
			throw new FileHandlerException("$sDestFile exists", FileHandlerException::LINK_FAILED);

		symlink($sSrcFile, $sDestFile);

		if(!file_exists($sDestFile))
			throw new FileHandlerException("could not create link $sSrcFile => $sDestFile", FileHandlerException::LINK_FAILED);
	}

	public static function write($sFileName, $sContent) {
		$sTmpFile = tempnam(sys_get_temp_dir(), "");
		file_put_contents($sTmpFile, $sContent);
		FileHandler::copy($sTmpFile, $sFileName);
	}

	public static function recursiveCopy($sSrcDir, $sDestDir, $aIgnoreFiles=[]) {
        return self::_recursiveTransfere(fn($src, $dst) => self::copy($src, $dst), $sSrcDir, $sDestDir, $aIgnoreFiles);
	}

	public static function recursiveSymlink($sSrcDir, $sDestDir, $aIgnoreFiles=[]) {
        return self::_recursiveTransfere(fn($src, $dst) => self::link($src, $dst), $sSrcDir, $sDestDir, $aIgnoreFiles);
	}

	public static function deleteDir($sDir) {
		if(empty($sDir)) return false;
		$aDirs = [ realpath($sDir) ];
		$aStage2Dirs = [];

		while( count($aDirs) > 0 ) {
			$sDir  = array_shift($aDirs);
			if(empty($sDir) or !file_exists($sDir)) continue;

			array_push($aStage2Dirs, $sDir);

			$aFiles = new \DirectoryIterator($sDir);

			foreach($aFiles as $oF) { if($oF->isDot()) continue;
				$sFileName = $oF->getFilename();
				$sFullName = $sDir . "/" . $sFileName;

				if($oF->isDir()) array_push($aDirs, $sFullName);
				else 			 unlink($sFullName);	
			}
		}

		while( count($aStage2Dirs) ) {
			$sDir = array_pop($aStage2Dirs);
			rmdir($sDir);
		}
	}

	private static function _recursiveTransfere($fncCallback, $sSrcDir, $sDestDir, $aIgnoreFiles=[]) {

		if(    empty($sSrcDir) 
			or empty($sDestDir) 
			or !is_string($sSrcDir) 
			or !is_string($sDestDir)
		) return false;

		$aDirs = [ realpath($sSrcDir) ];
		$aDests = [ realpath($sDestDir) ];

		if(!is_array($aIgnoreFiles)) $aIgnoreFiles = [];

        foreach($aIgnoreFiles as &$sFile) {
            if(!is_string($sFile)) continue;
            if(!file_exists($sFile = realpath($sSrcDir . "/" . $sFile))) $sFile = false;
        }

		$aIgnoreFiles = array_flip(array_filter($aIgnoreFiles, "is_string"));

		$iUMask = umask(0);

		try {
			while( count($aDirs) > 0 ) {
				if(count($aDirs) != count($aDests)) break;
				$sDir  = array_shift($aDirs);
				$sDest = array_shift($aDests);
				if(empty($sDir) or empty($sDest)) continue;

				if(!file_exists($sDir)) continue;
				if(!file_exists($sDest)) mkdir($sDest, 0755, true);

				$aFiles = new \DirectoryIterator($sDir);

				foreach($aFiles as $oF) { if($oF->isDot()) continue;
					$sFileName = $oF->getFilename();
					$sFullName = realpath($sDir . "/" . $sFileName);
					$sDestName = $sDest . "/" . $sFileName;

					if(isset($aIgnoreFiles[$sFullName])) continue;
					if($oF->isDir()) {
						array_push($aDirs, $sFullName);
						array_push($aDests, $sDestName);
					}
					else {
						$fncCallback($sFullName, $sDestName);	
					}
				}
			}
		}
		finally {
			umask($iUMask);
		}
	}
}

class FileHandlerException extends \Exception {
	const FILE_NOT_IN_PATH = 1;
	const COPY_FAILED = 2;
	const LINK_FAILED = 3;
}
