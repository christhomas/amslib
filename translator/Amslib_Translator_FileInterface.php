<?php

class Amslib_Translator_FileInterface extends Amslib_Translator_BaseAccessLayer
{
	//	FIXME: THERE CURRENTLY IS NO LANGUAGE SELECTION, JUST LOAD WHAT YOU ARE GIVEN
	var $__language;

	//	Length of a normal index in the dictionary
	var $__indexNormalLen;

	//	Length of the missing index in the dictionary
	var $__indexMissingLen;

	//	The dictionary of information about the loaded databases
	var $__dict;

	//	The database of translations
	var $__tr;

	//	The number of bytes in a normal dictionary key
	var $__nKeyLength;

	//	The number of bytes in a missing dictionary key
	var $__mKeyLength;

	//	Number of bytes offset into the database file where the dictionary starts
	var $__dictionaryOffset;

	//	Whether or not to write the database back to the file when closing
	var $__writeOnClose;

	//	Counts the number of failures to open a file (shouldnt this be per dictionary)
	var $__openFailures;

	var $__syncMode;

	var $__SYNC;

	var $__ASYNC;

	var $__accessLayer;

	function Amslib_Translator_FileInterface($database=NULL,$immediateClose=false)
	{
		parent::Amslib_Translator_BaseAccessLayer();

		$this->__SYNC	=	true;
		$this->__ASYNC	=	false;

		$this->__missingKey	=	"MISSING";
		$this->__nKeyLength	=	(int)strlen(iconv("UTF-8","UTF-16","A"));
		$this->__mKeyLength	=	(int)strlen(iconv("UTF-8","UTF-16",$this->__missingKey));

		$this->__dictionaryOffset = 4;

		//	Structure of a directory entry is:
		//		2 byte UTF-16 character (for storing the character for the directory entry)
		//		4 byte integer offset where in the file the directory for that character starts
		//		4 byte integer length which defines the total length of the directory entry
		$this->__indexLen = $this->__nKeyLength + 4 + 4;

		//	This is a special directory entry to store missing strings, it is the last directory entry
		$this->__indexMissingLen = $this->__mKeyLength + 4 + 4;

		//	Setup a missing entry database
		$this->__tr = array();
		$this->__tr[$this->__missingKey] = array();

		if($database) $this->open($database,$immediateClose);

		if($immediateClose) $this->close();
	}

	function __createNewIndex($file,$handle)
	{
		$d = array(
			"file"			=>	$file,
			"handle"		=>	$handle,
			"mode"			=>	"readonly",
			"database"		=>	array(),
			"open"			=>	true,
			"read"			=>	false,
			//	FIXME: This writeOnClose parameter isnt used anymore??!?!??!
			"write"			=>	$this->__writeOnClose,
			"writeFailed"	=>	false
		);

		return $d;
	}

	//	TODO: THIS METHOD JUST TO CALL READDATABASE ??
	function open($database,$readAll=false)
	{
		return $this->__readDatabase($database,false,$readAll);
	}

	function close()
	{
		if(is_array($this->__dict) && !empty($this->__dict)){
			foreach($this->__dict as $index=>$d){
				//	if write on close is enabled, write the database to disk
				if($d["write"] == true) $this->__writeDatabase($this->__dict[$index]);

				fclose($d["handle"]);

				$this->__dict[$index]["open"] = false;
			}
		}
	}

	function sync()
	{
		$this->__syncMode = $this->__SYNC;
	}

	function async()
	{
		$this->__syncMode = $this->__ASYNC;
	}

	function listAll($language)
	{
		$translations = array();

		foreach($this->__tr as $item){
			$translations = array_merge($translations,$item);
		}

		return $translations;
	}

	function t($input,$language=NULL)
	{
		if(is_array($this->__dict) == false) return $input;

		$key = substr($input,0,1);

		//	If we need to load on demand, we need to update readBlock to read from all the loaded catalogues
		if(!isset($this->__tr[$key][$input])){
			$this->__readMultipleBlock($key);
		}

		if(isset($this->__tr[$key][$input])){
			return $this->__tr[$key][$input];
		}else{
			$this->__tr[$this->__missingKey][] = $input;
			return $input;
		}
	}

	//	TODO: Replace writeImmediately with the new SYNC/ASYNC mode (if SYNC, write immediately)
	function l($input,$translation,$database=NULL)
	{
		//	Select the entire array of indexes, or single out a particular index
		$indexes = ($database == NULL) ? $this->__dict : array($database => $this->__dict[$database]);

		if($indexes && !empty($indexes)){
			foreach($indexes as $ik=>$ignore){
				$this->__updateTranslation($input,$translation,$this->__dict[$ik]);
			}
		}else die("FATAL ERROR(learn): Index array was for some reason empty, or invalid");
	}

	function f($input,$database=NULL)
	{
		//	TODO: Function to forget a string from the database
		$indexes = ($database == NULL) ? $this->__dict : array($database => $this->__dict[$database]);

		if($indexes && !empty($indexes)){
			$key = substr($input,0,1);

			foreach($indexes as $ik=>$ignore){
				if(isset($this->__dict[$ik]["database"][$key]["translations"][$input])){
					unset($this->__dict[$ik]["database"][$key]["translations"][$input]);
				}

				if(isset($this->__dict[$ik]["translations"][$key]["translations"][$input])){
					unset($this->__dict[$ik]["translations"][$key]["translations"][$input]);
				}

				if($this->__syncMode == $this->__SYNC) $this->__writeDatabase($this->__dict[$ik]);
			}

			if(isset($this->__tr[$key][$input])) unset($this->__tr[$key][$input]);

		}else print("FATAL ERROR(forget): Index array was for some reason empty, or invalid");
	}

	function getMissing()
	{
		return $this->__tr[$this->__missingKey];
	}

	function __updateTranslation($input,$translation,&$index)
	{
		$key = substr($input,0,1);
		$index["write"] = true;

		//	Update database part
		$copyOnChange = $this->__updateDatabase($key,$input,$translation);

		//	Update Index part
		$this->__updateIndex($index,$key,$input,$translation,$copyOnChange);

		if($this->__syncMode == $this->__SYNC) $this->__writeDatabase($index);
	}

	function __updateDatabase($key,$input,$translation)
	{
		if(!isset($this->__tr[$key])) $this->__tr[$key] = array();

		$copyOnChange = ($this->__tr[$key][$input] === $translation) ? false : true;

		$this->__tr[$key][$input] = $translation;

		//	Return whether or not the value has been updated or not
		return $copyOnChange;
	}

	function __updateIndex(&$index,$key,$input,$translation,$copyOnChange)
	{
		if(!isset($index["database"][$key])){
			$index["database"][$key] = array("start"=>0,"length"=>0,"read"=>false,"translations"=>array(),"indexes"=>array());
		}

		if($input){
			if($copyOnChange && strlen($translation)){
				$index["database"][$key]["translations"][$input] = $translation;
			}else{
				$index["database"][$key]["indexes"][] = $input;
			}
		}
	}

	function updateKey($old,$new,$deleteOld=true)
	{
		/*	TODO: REWRITE THIS CODE
		$oldKey = substr($old,0,1);
		$newKey = substr($new,0,1);

		$oldString = $this->__tr[$oldKey][$old];
		if($deleteOld) unset($this->__tr[$oldKey][$old]);

		if(!isset($this->__tr[$newKey])) $this->__tr[$newKey] = array();
		$this->__tr[$newKey][$new] = $oldString;

		if(count($this->__tr[$oldKey]) == 0) unset($this->__tr[$oldKey]);
	*/
	}

	function __getFileLength($handle)
	{
		$length = 0;

		//	Return location
		$reloc = @ftell($handle);

		//	Obtain length
		@fseek($handle,0,SEEK_END);
		$length = @ftell($handle);

		//	Reposition to same location as before
		@fseek($handle,$reloc,SEEK_SET);

		return $length;
	}

	function __readDatabase($database,$force=false,$readAll=false)
	{
		if(!isset($this->__dict[$database]) || $force){
			if(file_exists($database)){
				$handle = fopen($database,"r+b",true);

				if($handle){
					//	Check the length of the file is not empty
					$length = $this->__getFileLength($handle);

					$this->__dict[$database] = $this->__createNewIndex($database,$handle);
					$dict = &$this->__dict[$database];

					fseek($dict["handle"],0,SEEK_SET);

					//	The file was not 0 bytes long, so lets read 4 bytes for the length of the dictionary
					if($length){
						$length = unpack("V",fread($dict["handle"],4));
						$length = $length[1];
					}

					//	The length of the dictionary must be greater than 0
					if($length > 0){
						$dictionary = fread($dict["handle"],$length);
						$this->__readDictionary($dict,$dictionary);
					}

					if($readAll){
						foreach($dict["database"] as $key=>$ignore){
							$this->__readSingleBlock($key,$dict);
						}
					}
					return $database;
				}else return false;
			}else{
				//	Fail 5 times before giving up
				if($this->__openFailures++ == 5) die("failed to open 5 times<br/>");

				//	FIXME: Potential security hole, provide an empty filename and it'll create it automatically

				//	Could not open database, attempt to create it and try again
				$fh = @fopen($database,"w+b");
				if($fh){
					fwrite($fh,pack("V",0));
					fclose($fh);
					//	Set the default access mode for the file to 775
					chmod($database,0775);

					return $this->__readDatabase($database,$force);
				}else{
					//	TODO: We should be able to say what happens when it fails to create, do we want an error, nothing? what?
					//die("FATAL ERROR: Failed to create the language database, please check the permissions of the directory");
					return false;
				}
			}
		}
	}

	function __writeDatabase(&$dict)
	{
		if(is_array($dict) && is_array($dict["database"]) && $dict["handle"])
		{
			foreach($dict["database"] as $key=>$ignore) $this->__readSingleBlock($key,$dict);

			//	Force a missing key to be present in all databases
			if(!isset($dict["database"][$this->__missingKey])){
				$dict["database"][$this->__missingKey] = array("start"=>0,"length"=>0,"read"=>false,"translations"=>array(),"indexes"=>array());
			}

			//	Reopens the file but with write capability this time
			fclose($dict["handle"]);
			$dict["handle"] = fopen($dict["file"],"w");

			//	-1 because we don't want to count the MISSING key (it has a diff byte count)
			$numEntries = count($dict["database"])-1;
			$numBytes = $numEntries * $this->__indexLen + $this->__indexMissingLen;
			$offset = $numBytes+$this->__dictionaryOffset;

			fseek($dict["handle"],$offset,SEEK_SET);

			$failureCount = 0;
			foreach($dict["database"] as $key=>$ignore){
				//	FIXME: I am almost 100% sure that this "failure" code will break everything and not work properly
				if($this->__writeNormalBlock($key,$dict["handle"],$dict["database"][$key]) == true){
					$dict["database"][$key]["writeFailed"] = true;
				}else{
					$dict["database"][$key]["writeFailed"] = false;
					$failureCount++;
				}
			}
			//	TODO: WHAT HAPPENS WHEN ALL FAIL TO WRITE ALL THE INDEXES, BUT THERE ARE MISSING ITEMS???
			//	FIXME: This is almost certainly broken
			if($failureCount == count($dict["database"])) return false;

			//	Write the missing keys
			$this->__writeMissingBlock($this->__missingKey,$dict["handle"],$dict["database"][$this->__missingKey]);

			$this->__writeDictionary($dict,$numBytes);

			return true;
		}

		return false;
	}

	function __readDictionary(&$dict,$dictionary)
	{
		$numBytes = strlen($dictionary);
		$mod = $numBytes % $this->__indexLen;

		if($numBytes % $this->__indexLen == 0){
			$offset = 0;

			while($offset < ($numBytes-$this->__indexMissingLen)){
				$this->__readIndex($dict,$dictionary,$this->__nKeyLength,$offset);
			}

			$this->__readIndex($dict,$dictionary,$this->__mKeyLength,$offset);
		}else{
			print("Dictionary was not the correct length, there are extra bytes where there is supposed to be zero<br/>");
			print("The remaining bytes: $mod<br/>");
		}
	}

	function __readIndex(&$index,$directory,$keyLength,&$offset)
	{
		$key = substr($directory,$offset,$keyLength);
		$key = iconv("UTF-16","UTF-8",$key);

		if(!isset($index["database"][$key])){
			$index["database"][$key] = array("start"=>0,"length"=>0,"read"=>false,"translations"=>array(),"indexes"=>array());
		}

		$offset+=$keyLength;

		$value = unpack("V",substr($directory,$offset,4));
		$index["database"][$key]["start"] = $value[1];
		$offset+=4;

		$value = unpack("V",substr($directory,$offset,4));
		$index["database"][$key]["length"] = $value[1];
		$offset+=4;
	}

	function __writeDictionary(&$index,$numBytes)
	{
		fseek($index["handle"],0,SEEK_SET);
		fwrite($index["handle"],pack("V",$numBytes));

		foreach($index["database"] as $key => $data){
			if($index["writeFailed"] == true) continue;

			$utf16 = iconv("UTF-8","UTF-16",$key);
			if(strlen($utf16) === $this->__nKeyLength){
				$this->__writeIndex($utf16,$index["handle"],$index["database"][$key]["start"],$index["database"][$key]["length"]);
			}
		}

		$utf16 = iconv("UTF-8","UTF-16",$this->__missingKey);
		$this->__writeIndex($utf16,$index["handle"],$index["database"][$this->__missingKey]["start"],$index["database"][$this->__missingKey]["length"]);
	}

	function __writeIndex($key,$handle,$start,$length)
	{
		fwrite($handle,$key,strlen($key));
		fwrite($handle,pack("V",$start));
		fwrite($handle,pack("V",$length));
	}

	function __readSingleBlock($key,&$dict)
	{
		$dictKey = NULL;
		if(isset($dict["database"][$key])) $dictKey = &$dict["database"][$key];

		//	If the database is closed, OR the key being requested was already read, return false, nothing to do
		if($dict["open"] == false || $dictKey["read"] == true) return false;

		if($dictKey != NULL && isset($dictKey["start"])){
 			if($dictKey["length"] > 0 && $dict["open"]){

				fseek($dict["handle"],$dictKey["start"],SEEK_SET);
				$strings = fread($dict["handle"],$dictKey["length"]);

				if($strings){
					//print("strings = <pre>$strings</pre>");

					$newStrings = unserialize($strings);
					foreach($newStrings as $input=>$translation){
						//	Temporarily disable whatever syncMode was enabled (we do NOT want a write occuring here)
						$m = $this->__syncMode;
						$this->__syncMode = false;
						$this->__updateTranslation($input,$translation,$dict);
						$this->__syncMode = $m;
					}

					$dictKey["read"] = true;

					return true;
				}
			}
		}

		return false;
	}

	function __readMultipleBlock($key)
	{
		foreach($this->__dict as $dk=>$ignore){
			$this->__readSingleBlock($key,$this->__dict[$dk]);
		}
	}

	function __writeNormalBlock($key,$handle,&$dict)
	{
		$strings = array();

		foreach($dict["indexes"] as $input){
			$strings[$input] = $this->__tr[$key][$input];
		}

		foreach($dict["translations"] as $input=>$translation){
			$strings[$input] = $translation;
		}

		return $this->__writeBlock($strings,$handle,$dict);
	}

	//	TODO: This method, JUST for calling __writeBlock with a different name ?
	function __writeMissingBlock($key,$handle,&$dict)
	{
		return $this->__writeBlock($this->__tr[$key],$handle,$dict);
	}

	function __writeBlock($strings,$handle,&$dict)
	{
		//	If there are no strings, don't do anything and instruct the caller to skip as well
		if(count($strings) == 0) return false;

		//	Serialize the array and write the start+length values
		$strings = serialize($strings);

		$dict["start"] = ftell($handle);
		$dict["length"] = strlen($strings);

		//	Write the strings to the file
		$bytes = fwrite($handle,$strings,$dict["length"]);

		//	Return false if the number of bytes does not match the length of data it should have written
		//	Reset the file handle before you return, so the result of writing a bad block is that it's "reset"
		if($bytes != $dict["length"]){
			fseek($handle,$dict["start"],SEEK_SET);
			return false;
		}

		return true;
	}
}