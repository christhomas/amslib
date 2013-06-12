<?php
/*******************************************************************************
 * Copyright (c) {15/03/2008} {Christopher Thomas}
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *
 *******************************************************************************/

/**
 * 	class:	Amslib
 *
 *	group:	core
 *
 *	file:	Amslib.php
 *
 *	description:
 *		This is a core file within the Amslib framework, it's responsible for the
 *		autoloading of PHP classes and handling the including of PHP scripts with extra
 *		functionality on top of the base PHP functionality.
 *
 *		Using Amslib::autoloader() will create an autoloader which can search more intelligently
 *		your include path for matching PHP classes and load them
 *
 *		Using Amslib::requireFile() will search intelligently your path in a better way
 *		than basic the PHP functionality, because it will allow relative file name
 *		searches as PHP does not
 *
 *		There are functions for debugging, see var_dump, getStackTrace, errorLog which do
 *		various useful things, var_dump is exactly what you expect, but can format the data
 *		to be more visually understandable and return you a string so you can manipulate it further
 *		errorLog can output multiple pieces of data to the error log, obtain stack traces, etc
 *
 *		lchop, rchop, truncateString are some basic string manipulation functions which are useful
 *		for cutting up urls or blocks of html if required, which is useful when cutting html strings and
 *		fixing up the html so it still works, or doesnt break
 */
class Amslib
{
	static protected $showErrorTrigger		=	false;
	static protected $originalErrorHandler	=	false;

	//	DEPRECATED: should use findPath instead, makes more sense
	static protected function findFile($filename){ return self::findPath($filename); }

	/**
	 * 	method:	findPath
	 *
	 * 	Find a file include the include path, allowing for relative path names, which is
	 * 	something that PHP does not provide by default.
	 *
	 *	parameters:
	 *		$filename - The file to search for
	 *
	 *	returns:
	 *		Boolean false if not found, or a string containing the path the file was found with
	 *
	 *	notes:
	 *		-	The return does not include the filename requested, it only contains the
	 *			path that was used to find the file.
	 */
	static protected function findPath($filename)
	{
		$includePath = explode(PATH_SEPARATOR,ini_get("include_path"));

		foreach($includePath as $path){
			//	NOTE: Cannot use Amslib_File::reduceSlashes here, chicken/egg type problem
			$test = preg_replace('#//+#','/',"$path/$filename");
			if(@file_exists($test)) return $path;
		}

		return false;
	}

	/**
	 * 	method:	locate
	 *
	 * 	A method to "locate" the Amslib.php script within the website automatically, so it
	 * 	can be used as an anchor to automatically know where amslib is installed
	 *
	 * 	returns:
	 * 		A string containing the directory name of the Amslib.php script within the website
	 */
	static public function locate()
	{
		return dirname(__FILE__);
	}

	/**
	 * 	method:	showErrors
	 *
	 * 	A method to turn on, off PHP's error reporting and display errors.  All this does is
	 * 	convert a two liner into a one liner, but also might be enhanced in the future with
	 * 	more functionality relating to errorLog and var_dump, etc.
	 *
	 * 	parameters:
	 * 		$state - Whether or not to turn showing errors on or off.
	 */
	static public function showErrors($state=true)
	{
		error_reporting(E_ALL);

		if($state){
			ini_set("display_errors",true);
			self::$showErrorTrigger = true;
		}else{
			ini_set("display_errors",false);
		}
	}

	/**
	 * 	method:	setErrorHandler
	 *
	 *	Sets a custom error handler when a problem occurs inside the PHP code,
	 *	we make a backup of the original handler so we can restore it
	 *	if it is required to do so
	 *
	 *	parameters:
	 *		$handler - The function call to make when an error occurs
	 */
	static public function setErrorHandler($handler)
	{
		self::$originalErrorHandler = set_error_handler($handler);
	}

	/**
	 * 	method:	restoreErrorHandler
	 *
	 * 	Restores the original error handler that was set.  This will only do so
	 * 	If a handler was set before, otherwise nothing will happen since there
	 * 	is nothing to restore.
	 */
	static public function restoreErrorHandler()
	{
		if(self::$originalErrorHandler){
			set_error_handler(self::$originalErrorHandler);
		}
	}

	/**
	 * 	method:	lchop
	 *
	 * 	Chop a string to remove everything to the left of the
	 * 	search, leaving only what is on the right of the search token
	 *
	 * 	parameters:
	 * 		$str - The string o search through
	 * 		$search - The search token to find
	 * 		$removeSearch - Whether or not to remove the search token from the return string
	 *
	 * 	fixme:
	 * 		there is a bug here in the amslib power panel has a 500 webserver
	 * 		error when you return "" or false for not finding a string
	 *
	 * 	notes:
	 * 		-	I think it makes more sense now to return false, since if you
	 * 			return a string, it's like you've found a result, but thats not true
	 * 		-	I disabled the removeSearch code since it was causing a 500 webserver error
	 */
	static public function lchop($str,$search,$removeSearch=false)
	{
		$p = strlen($search) ? strpos($str,$search) : false;

		//	TODO: fix the bugs and test this next line to optionally remove the search string instead of doing it by default
		//	NOTE: I didnt want to activate this by default in case it broke things I didnt realise
		//if($removeSearch) $p+=strlen($search);

		return ($p) !== false ? substr($str,$p+strlen($search)) : $str;
	}

	/**
	 * 	method:	rchop
	 *
	 * 	Chop a string to remove everything to the right of the
	 * 	search, leaving only what is on the left of the search token
	 *
	 * 	parameters:
	 * 		$str - The string o search through
	 * 		$search - The search token to find
	 *
	 * 	fixme:
	 * 		there is a bug here in the amslib power panel has a 500 webserver
	 * 		error when you return "" or false for not finding a string
	 *
	 * 	notes:
	 * 		-	I think it makes more sense now to return false, since if you
	 *	 		return a string, it's like you've found a result, but thats not true
	 *		-	Why does this function not have a $removeSearch parameter like
	 *			lchop? seems inconsistent
	 */
	static public function rchop($str,$search)
	{
		$p = strlen($search) ? strrpos($str,$search) : false;

		return ($p) !== false ? substr($str,0,$p) : $str;
	}

	/**
	 * 	method:	trimString
	 *
	 * 	A cheaper, but far less useful version of Amslib::truncateString, does not consider html, does nothing
	 * 	except chop where it was told and append the postfix, job done.  It's quite stupid.
	 *
	 * 	parameters:
	 * 		$text - The string to trim
	 * 		$length - default 100, the length required
	 * 		$ending - default "...", the ending to append if a string is truncated
	 *
	 * 	returns:
	 * 		A truncated string, or the original string if it was not longer than required
	 */
	static public function trimString($text,$length=100,$ending="...")
	{
		$length = $length-strlen($ending);

		return (strlen($text) > $length) ? substr($text,0,$length).$ending : $text;
	}

	/**
	 * 	method:	truncateString
	 *
	 * 	A more intelligent truncate string method that will cut a string better than just substr()
	 *
	 * 	parameters:
	 * 		$text - the string to truncate
	 * 		$length - default 100, the length required
	 * 		$ending - default "...", the ending to append if a string is truncated
	 * 		$exact - default false, if true, will not cut a word in two, but look for a space in the
	 * 				truncated string and truncate to that position, so words are not cut in the midd....(<-irony)
	 * 		$considerHtml - default true, whether or not to consider HTML tags, so the code doesn't cut them
	 * 						in the middle and break the HTML structure of a text string
	 *
	 *	notes:
	 *		- I copied this code from CakePHP::truncate() which was super useful
	 *		- I just didnt want to import the CakePHP namespace, I wanted to just merge this functionality
	 */
	static public function truncateString($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true)
	{
		if ($considerHtml)
		{
			// if the plain text is shorter than the maximum length, return the whole text
			if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
				return $text;
			}

			// splits all html-tags to scanable lines
			preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
			$total_length = strlen($ending);
			$open_tags = array();
			$truncate = '';

			foreach ($lines as $line_matchings)
			{
				// if there is any html-tag in this line, handle it and add it (uncounted) to the output
				if (!empty($line_matchings[1]))
				{
					// if it's an "empty element" with or without xhtml-conform closing slash
					if (preg_match(	'/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is',$line_matchings[1]))
					{
						// do nothing
						// if tag is a closing tag
					} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
						// delete tag from $open_tags list
						$pos = array_search($tag_matchings[1], $open_tags);
						if ($pos !== false) {
							unset($open_tags[$pos]);
						}

						// if tag is an opening tag
					} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
						// add tag to the beginning of $open_tags list
						array_unshift($open_tags, strtolower($tag_matchings[1]));
					}
					// add html-tag to $truncate'd text
					$truncate .= $line_matchings[1];
				}

				// calculate the length of the plain text part of the line; handle entities as one character
				$content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));

				if ($total_length+$content_length> $length)
				{
					// the number of characters which are left
					$left = $length - $total_length;
					$entities_length = 0;

					// search for html entities
					if (preg_match_all(	'/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i',
							$line_matchings[2],
							$entities,
							PREG_OFFSET_CAPTURE))
					{
						// calculate the real length of all entities in the legal range
						foreach ($entities[0] as $entity) {
							if ($entity[1]+1-$entities_length <= $left) {
								$left--;
								$entities_length += strlen($entity[0]);
							} else {
								// no more characters left
								break;
							}
						}
					}

					$truncate .= substr($line_matchings[2], 0, $left+$entities_length);

					// maximum length is reached, so get off the loop
					break;
				} else {
					$truncate .= $line_matchings[2];
					$total_length += $content_length;
				}

				// if the maximum length is reached, get off the loop
				if($total_length>= $length) {
					break;
				}
			}
		} else {
			if (strlen($text) <= $length) {
				return $text;
			} else {
				$truncate = substr($text, 0, $length - strlen($ending));
			}
		}

		// if the words shouldn't be cut in the middle...
		if (!$exact) {
			// ...search the last occurance of a space...
			$spacepos = strrpos($truncate, ' ');
			if (isset($spacepos)) {
				// ...and cut the text in this position
				$truncate = substr($truncate, 0, $spacepos);
			}
		}

		// add the defined ending to the text
		$truncate .= $ending;
		if($considerHtml) {
			// close all unclosed html-tags
			foreach ($open_tags as $tag) {
				$truncate .= '</' . $tag . '>';
			}
		}

		return $truncate;
	}

	/**
	 *	function:	addIncludePath
	 *
	 *	Add an include path to the PHP include path
	 *
	 *	parameters:
	 *		$path	-	The path to add to the PHP include path
	 */
	static public function addIncludePath($path)
	{
		$includePath = explode(PATH_SEPARATOR,ini_get("include_path"));

		$valid = true;
		foreach($includePath as $p){
			if(strcmp($path,$p) == 0) $valid = false;
		}

		if($valid) array_unshift($includePath,$path);
		ini_set("include_path",implode(PATH_SEPARATOR,$includePath));
	}

	/**
	 * 	method:	getIncludeContents
	 *
	 *	Obtain a files contents when it was included as a string instead of just importing it into the
	 *	current output, which is useful if errors occur, it'll grab those instead of dumping them to the page
	 *	and breaking the layout
	 *
	 *	parameters:
	 *		$filename - the filename to obtain the contents of
	 *
	 *	returns:
	 *		The output from including that file, as a string
	 */
	static public function getIncludeContents($filename)
	{
		ob_start();
		Amslib::includeFile($filename);
		$contents = ob_get_clean();

		return $contents;
	}

	/**
	 * 	method:	slugify
	 *
	 * 	Converts a string into something that replaces all non-url-compatible characters with
	 * 	a "slug" this is useful for creating article names in a website, where including a " " (space)
	 * 	in the filename is going to break the url and cause problems.  Will also remove more than just space
	 * 	but all other none-alphanumeric type characters and transliterate accented characters into none-accented
	 * 	versions.  This function automatically lower cases the entire text string
	 *
	 * 	parameters:
	 * 		$text - The text to slugify
	 * 		$remove - default "" (empty, nothing), any extra regex to remove - WARNING, you could break your code by putting non-functioning regex operators here
	 * 		$replace - default "-", the character to replace all the non-matching characters with
	 *
	 * 	returns:
	 * 		A string which has been stripped of all the invalid characters, in lower case
	 *
	 * 	notes:
	 * 		-	blatently stolen code from: http://snipplr.com/view/22741/slugify-a-string-in-php/ :-) thank you!
	 * 		-	modified 01/08/2011: added ability to allow custom regex through the $remove parameter
	 * 			so you can add terms if required
	 *
	 * 	todo:
	 * 		investigate whether the remove unwanted character step should be BEFORE
	 * 		the replace step since the more it was been observed, the more that it makes sense.
	 */
	static public function slugify($text,$remove="",$replace="-")
	{
		// replace non letter or digits by -
		$text = preg_replace("~[^\\pL\d{$remove}]+~u", $replace, $text);

		// trim and transliterate the string to be baseline ASCII and lowercase it for good luck
		$text = trim($text, $replace);
		if (function_exists('iconv')) $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
		$text = strtolower($text);

		// remove unwanted characters
		$text = preg_replace("~[^-\w{$remove}]+~", '', $text);

		return $text;
	}

	/**
	 * 	method:	var_dump
	 *
	 *	Obtain a var_dump of a variable, but obtain the dump as a string to be printed or manipulated
	 *	with extra options for hiding the output from the browser
	 *
	 *	parameters:
	 *		$variable - the variable to var_dump
	 *		$preformat - whether or not to wrap up the dump in a HTML <pre> tag
	 *		$hiddenOutput - whether or not to apply a css style display none to the <pre> tag
	 *
	 * 	notes:
	 * 		-	Perhaps $preformat by default should be true ? would make more sense,
	 * 			otherwise why not just use var_dump directly??
	 * 		-	this function can cause out of memory problems if the variable is huge but
	 * 			it's not known how it is possible to test for this before attempting it
	 */
	static public function var_dump($variable,$preformat=false,$hiddenOutput=false)
	{
		ob_start();
		var_dump($variable);
		$dump = ob_get_clean();

		$hiddenOutput = $hiddenOutput ? "style='display:none'" : "";

		return ($preformat) ? "<pre $hiddenOutput>$dump</pre>" : $dump;
	}

	/**
	 * 	method:	var_dump_xml
	 *
	 *	To var dump a variable into a block of XML describing the structure of the variable being dumped
	 *
	 *	parameters:
	 *		$mixed - the variable being var_dumped
	 *
	 *	returns:
	 *		An XML document containing the variable in XML format as if it was var dumped
	 *
	 * 	notes:
	 * 		-	This code was copied from [http://www.php.net/manual/en/function.print-r.php#97901]
	 * 			by the user Levofski, thanks, it's amazing!
	 */
	static public function var_dump_xml($mixed)
	{
	    // capture the output of print_r
	    $out = print_r($mixed, true);

	    // Replace the root item with a struct
	    // MATCH : '<start>element<newline> ('
	    $root_pattern = '/[ \t]*([a-z0-9 \t_]+)\n[ \t]*\(/i';
	    $root_replace_pattern = '<struct name="root" type="\\1">';
	    $out = preg_replace($root_pattern, $root_replace_pattern, $out, 1);

	    // Replace array and object items structs
	    // MATCH : '[element] => <newline> ('
	    $struct_pattern = '/[ \t]*\[([^\]]+)\][ \t]*\=\>[ \t]*([a-z0-9 \t_]+)\n[ \t]*\(/miU';
	    $struct_replace_pattern = '<struct name="\\1" type="\\2">';
	    $out = preg_replace($struct_pattern, $struct_replace_pattern, $out);
	    // replace ')' on its own on a new line (surrounded by whitespace is ok) with '</var>
	    $out = preg_replace('/^\s*\)\s*$/m', '</struct>', $out);

	    // Replace simple key=>values with vars
	    // MATCH : '[element] => value<newline>'
	    $var_pattern = '/[ \t]*\[([^\]]+)\][ \t]*\=\>[ \t]*([a-z0-9 \t_\S]+)/i';
	    $var_replace_pattern = '<var name="\\1">\\2</var>';
	    $out = preg_replace($var_pattern, $var_replace_pattern, $out);

	    $out =  trim($out);
	    $out='<?xml version="1.0"?><data>'.$out.'</data>';

	    return $out;
	}

	/**
	 * 	method:	backtrace
	 *
	 *	a method to obtain a debug backtrace of the current PHP function stack with options to
	 *	slice a part of the array
	 *
	 *	this function uses variable arguments, it will only slice the string if it first two
	 *	parameters are integer numbers after that, all the arguments will be tested whether
	 *	they are a string and if so, they will be used to filter each array stack element to
	 *	return only the keys which match the strings, dropping all the unwanted keys from
	 *	each array stack element
	 *
	 *	parameters:
	 *		$start - The starting offset to slice from the array stack
	 *		$finish - the ending offset to slice from the array stack
	 *		vargs - any number of string variables which will be used to filter each array index to reutrn only the keys requested
	 *
	 *	notes:
	 *		-	The function is really really memory hungry, use Amslib::getStackTrace if you can
	 *		-	This is a really ancient function, it's hard to know what it's trying to do
	 */
	static public function backtrace()
	{
		$args	=	func_get_args();
		$bt		=	debug_backtrace();

		$slice	=	array($bt);
		if(count($args) && is_numeric($args[0])) $slice[] = array_shift($args);
		if(count($args) && is_numeric($args[0])) $slice[] = array_shift($args);

		if(count($slice) > 1) $bt = call_user_func_array("array_slice",$slice);

		return Amslib_Array::filterKey($bt,Amslib_Array::filterType($args,"is_string"));
	}

	/**
	 * 	method:	getStackTrace
	 *
	 * 	Obtains a PHP exceptions stack trace, with optional starting index and string output facilities
	 *
	 *	parameters:
	 *		$index		-	default NULL, the index to start the stack trace from, can be used to
	 *						drop a certain number of leading function calls to focus on the section
	 *						of the stack required.  Only will accept integer numbers.
	 *		$string		-	default false, return the stack trace as a string or an array, useful
	 *						when debugging and dumping the data to the browser, error log
	 *		$var_dump - 	Whether or not to var_dump each section of the stack trace, only used
	 *						when $string parameter is not set to true
	 *
	 *	notes:
	 *		- the $var_dump parameter is not very useful, it should be considered a candidate for deletion
	 */
	static public function getStackTrace($index=NULL,$string=false,$var_dump=NULL)
	{
		$e = new Exception();
		$t = false;

		$t = $string ? $e->getTraceAsString() : $e->getTrace();

		if($index && is_numeric($index)){
			$t = $string
				? current(array_slice(explode("\n",$t),$index,1))
				: ($var_dump ? Amslib::var_dump($t[$index]) : $t[$index]);

			if($string || $var_dump){
				$t = htmlspecialchars($t,ENT_QUOTES,"UTF-8");
			}
		}

		return $t;
	}

	/**
	 * 	method:	errorLog
	 *
	 * 	todo: write documentation
	 */
	static public function errorLog()
	{
		$args		=	func_get_args();
		$data		=	array();
		$maxlength	=	8912;
		$function	=	false;

		foreach($args as $k=>$a){
			if(is_string($a) && strpos($a,"stack_trace") === 0){
				$command = explode(",",$a);

				$stack = Amslib::getStackTrace(NULL,true);
				$stack = explode("\n",$stack);

				$c = count($command);

				if($c == 2){
					$stack = array_slice($stack,$command[1]);
				}else if($c == 3 && $command[2] > 0){
					$stack = array_slice($stack,$command[1],$command[2]);
				}

				foreach($stack as $row){
					error_log("[TRACE] ".Amslib::var_dump($row));
				}
			}else if(is_string($a) && strpos($a,"func_offset") === 0){
				$command = explode(",",array_shift($args));

				if(count($command) == 2) $function = $command[1];
			}else{
				if(is_object($a))	$a = array(get_class($a),Amslib::var_dump($a));
				if(is_array($a)) 	$a = Amslib::var_dump($a);
				if(is_bool($a))		$a = $a ? "true" : "false";
				if(is_null($a))		$a = "null";

				$a = trim(preg_replace("/\s+/"," ",$a));

				if(strlen($a) > $maxlength) $a = substr($a,0,$maxlength-50)."...[array too large to display]";

				$data[] = "arg[$k]=> $a";
			}
		}

		$stack = Amslib::getStackTrace();

		if(!is_numeric($function)) $function = 2;

		$line		=	isset($stack[$function-1])	? $stack[$function-1]	:	array("line"=>-1);
		$function	=	isset($stack[$function])	? $stack[$function] 	:	false;

		if(!$function || !isset($function["class"]) || !isset($function["type"]) || !isset($function["function"])){
			$function	=	"(ERROR, function invalid: ".Amslib::var_dump($function).")";
		}else{
			$function	=	"{$function["class"]}{$function["type"]}{$function["function"]}({$line["line"]})";
		}

		error_log("[DEBUG] $function, ".implode(", ",$data));

		return array("function"=>$function,"data"=>$data);
	}

	//	NOTE: This method has weird parameter names to make it harder to clash with extract()'d parameters from the $__p parameter
	/**
	 * 	method:	__importFile
	 *
	 * 	todo: write documentation
	 */
	static public function __importFile($__r,$__f,$__p=array(),$__b=false)
	{
		$path = "";

		if(!file_exists($__f)){
			$path = self::findFile($__f);

			if($path !== false && strlen($path)) $path = "$path/";
		}

		//	NOTE:	Cannot use Amslib_File::reduceSlashes here, chicken/egg type problem
		//	NOTE:	Actually, I think it's much more than that, you can't use anything autoloaded here
		$__f = preg_replace('#//+#','/',"{$path}$__f");

		if(is_file($__f) && file_exists($__f)){
			if(is_array($__p) && count($__p)) extract($__p, EXTR_SKIP);

			//	Optional output buffering
			if($__b) ob_start();

			$__v = $__r
				? (isset($require_once) ? require_once($__f) : require($__f))
				: (isset($include_once) ? include_once($__f) : include($__f));

			if($__b) $__v = ob_get_clean();

			//	Return the result of the require/include, or if output buffering is enabled, return whatever was captured
			return $__v;
		}

		return false;
	}

	/**
	 * 	method:	requireFile
	 *
	 * 	todo: write documentation
	 */
	static public function includeFile($file,$params=array(),$buffer=false)
	{
		return self::__importFile(false,$file,$params,$buffer);
	}

	/**
	 * 	method:	requireFile
	 *
	 * 	todo: write documentation
	 */
	static public function requireFile($file,$params=array(),$buffer=false)
	{
		return self::__importFile(true,$file,$params,$buffer);
	}

	/**
	 * 	method:	autoloader
	 *
	 * 	todo: write documentation
	 */
	static public function autoloader()
	{
		//	Only register it once.
		if(function_exists("amslib_autoload")) return;
		//	add the amslib directory and it's parent to the include path
		//	99.9999% of times, you want this to happen, so I just make it default
		self::addIncludePath(dirname(__FILE__));
		self::addIncludePath(dirname(dirname(__FILE__)));

		function amslib_autoload($c)
		{
			if($c == __CLASS__) return false;

			if(strpos($c,"Amslib_Translator")	=== 0)	$c	=	"translator/$c";
			if(strpos($c,"Amslib_Router")		=== 0) 	$c	=	"router/$c";
			if(strpos($c,"Amslib_Database")		=== 0)	$c	=	"database/$c";
			if(strpos($c,"Amslib_XML")			=== 0)	$c	=	"xml/$c";
			if(strpos($c,"Amslib_Plugin")		=== 0)	$c	=	"plugin/$c";
			if(strpos($c,"Amslib_MVC")			=== 0)	$c	=	"mvc/$c";
			if(strpos($c,"Amslib_Mixin")		=== 0)	$c	=	"mvc/$c";
			if(strpos($c,"Amslib_File")			=== 0)	$c	=	"file/$c";
			if(strpos($c,"Amslib_QueryPath")	=== 0)	$c	=	"util/$c";
			if(strpos($c,"PiwikTracker")		=== 0)	$c	=	"util/$c";
			if(strpos($c,"phpQuery")			=== 0)	$c	=	"util/$c/$c";
			if(strpos($c,"QRcode")				=== 0)	$c	=	"util/phpqrcode/phpqrcode";
			if(strpos($c,"AesCtr")				=== 0)	$c	=	"util/$c";
			if(strpos($c,"Logger")				=== 0)	$c	=	"util/apache-log4php/src/main/php/$c";

			if($c == "Facebook")						$c	=	"util/facebook-php-sdk/src/facebook";
			if($c == "FirePHP") 						$c	=	"util/FirePHPCore/$c.class";
			if($c == "HTMLPurifier")					$c	=	"util/HTMLPurifier.standalone";
			if($c == "mPDF")							$c	=	"util/mpdf-5.4/mpdf";

			$f = str_replace("//","/","$c.php");

			return Amslib::requireFile($f);
		}

		//	register a special autoloader that will include correctly all of the amslib classes
		spl_autoload_register("amslib_autoload");
	}

	/**
	 * 	method:	shutdown
	 *
	 * 	todo: write documentation
	 */
	static public function shutdown($url,$callback=NULL,$warnings=false)
	{
		//	Clean these parameters each page load, cause they are only useful on the error pages, not everywhere else
		Amslib::getSESSION("/amslib/php/fatal_error/",NULL,true);
		Amslib::getSESSION("/amslib/php/backtrace/",NULL,true);

		/**
		 * 	method:	amslib_shutdown
		 *
		 * 	todo: write documentation
		 */
		function amslib_shutdown($url,$callback,$warnings)
		{
			//	E_PARSE: you cannot catch parse errors without a prepend file.
			//	NOTE: I think this has to do with being a different apache request stage

			//	All the errors I believe to be fatal/non-recoverable/you're fucked/your code is shit
			$fatal = array(E_ERROR,E_CORE_ERROR,E_COMPILE_ERROR,E_COMPILE_WARNING,E_STRICT,E_USER_ERROR);

			if($warnings){
				$fatal+=array(E_WARNING,E_NOTICE,E_CORE_WARNING,E_USER_WARNING,E_RECOVERABLE_ERROR);
			}

			if (($e = @error_get_last()) && @is_array($e) && @in_array($e["type"],$fatal)) {
				$error = array(
					"err"	=>	array(
						"code"	=>	isset($e['type']) ? $e['type'] : 0,
						"msg"	=>	isset($e['message']) ? $e['message'] : '',
						"file"	=>	isset($e['file']) ? $e['file'] : '',
						"line"	=>	isset($e['line']) ? $e['line'] : ''),
					"get"	=>	$_GET,
					"post"	=>	$_POST,
					"vars"	=>	get_defined_vars(),
					"fcns"	=>	get_defined_functions(),
					"clss"	=>	get_declared_classes()
				);

				//	TODO: write the file to a logging directory

				$exception = new Exception();
				$exception = $exception->getTrace();

				if($url){
					$_SESSION["/amslib/php/fatal_error/"]	=	$error;
					//	NOTE: perhaps this won't work, but I'm trying to get the trace to the origin of where the error occured
					$_SESSION["/amslib/php/backtrace/"]		=	$exception;

					header("Location: $url");
				}elseif(function_exists($callback)){
					$callback($error,$exception);
				}
			}
		}

		register_shutdown_function("amslib_shutdown",$url,$callback,$warnings);
	}

	/**
	 * 	method:	findKey
	 *
	 * 	notes:
	 * 		DEPRECATED, use Amslib_Array::findKey directly
	 */
	static public function findKey($key,$source)
	{
		return Amslib_Array2::findKey($source,$key);
	}

	/**
	 * 	function:	getGET
	 *
	 * 	Obtain a parameter from the GET global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the GET global array, if not exists, the value of the parameter return
	 */
	static public function getGET($key,$default=NULL,$erase=false)
	{
		return self::arrayParam($_GET,$key,$default,$erase);
	}

	/**
	 * 	method:	hasGET
	 *
	 * 	todo: write documentation
	 */
	static public function hasGET($key)
	{
		return (isset($_GET[$key])) ? true : false;
	}

	/**
	 *	function:	setGET
	 *
	 *	Insert a parameter into the global GET array
	 *
	 *	parameters:
	 *		$key	-	The parameter to insert
	 *		$value		-	The value of the parameter being inserted
	 *
	 *	notes:
	 *		-	Sometimes this is helpful, because it can let you build certain types of code flow which arent possible otherwise
	 */
	static public function setGET($key,$value)
	{
		$_GET[$key] = $value;

		return $value;
	}

	/**
	 * 	function:	getPOST
	 *
	 * 	Obtain a parameter from the POST global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the POST global array, if not exists, the value of the parameter return
	 */
	static public function getPOST($key,$default=NULL,$erase=false)
	{
		return self::arrayParam($_POST,$key,$default,$erase);
	}

	/**
	 * 	method:	hasPOST
	 *
	 * 	todo: write documentation
	 */
	static public function hasPOST($key)
	{
		return (isset($_POST[$key])) ? true : false;
	}

	/**
	 *	function:	setPOST
	 *
	 *	Insert a parameter into the global POST array
	 *
	 *	parameters:
	 *		$key	-	The parameter to insert
	 *		$value		-	The value of the parameter being inserted
	 *
	 *	notes:
	 *		-	Sometimes this is helpful, because it can let you build certain types of code flow which arent possible otherwise
	 */
	static public function setPOST($key,$value)
	{
		$_POST[$key] = $value;

		return $value;
	}

	/**
	 * 	function:	getSESSION
	 *
	 * 	Obtain a parameter from the SESSION global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the SESSION global array, if not exists, the value of the parameter return
	 */
	static public function getSESSION($key,$default=NULL,$erase=false)
	{
		return self::arrayParam($_SESSION,$key,$default,$erase);
	}

	/**
	 * 	method:	hasSESSION
	 *
	 * 	todo: write documentation
	 */
	static public function hasSESSION($key)
	{
		return (isset($_SESSION[$key])) ? true : false;
	}

	/**
	 * 	method:	setSESSION
	 *
	 * 	todo: write documentation
	 */
	static public function setSESSION($key,$value)
	{
		$_SESSION[$key] = $value;

		return $value;
	}

	/**
	 * 	method:	getCOOKIE
	 *
	 * 	todo: write documentation
	 */
	static public function getCOOKIE($key,$default=NULL)
	{
		return self::arrayParam($_COOKIE,$key,$default);
	}

	/**
	 * 	method:	hasCOOKIE
	 *
	 * 	todo: write documentation
	 */
	static public function hasCOOKIE($key)
	{
		return (isset($_COOKIE[$key])) ? true : false;
	}

	/**
	 * 	method:	getFILES
	 *
	 * 	todo: write documentation
	 */
	static public function setCOOKIE($key,$value)
	{
		$_COOKIE[$key] = $value;

		return $value;
	}

	/**
	 * 	function:	getFILES
	 *
	 * 	Obtain a parameter from the FILES global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the FILES global array, if not exists, the value of the parameter return
	 */
	static public function getFILES($key,$default=NULL,$erase=false)
	{
		return self::arrayParam($_FILES,$key,$default,$erase);
	}

	/**
	 * 	method:	setFILES
	 *
	 * 	todo: write documentation
	 */
	static public function setFILES($key,$value)
	{
		$_FILES[$key] = $value;

		return $value;
	}

	/**
	 * 	function:	getREQUEST
	 *
	 * 	Obtain a parameter from the REQUEST global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the REQUEST global array, if not exists, the value of the parameter return
	 */
	static public function getREQUEST($key,$default=NULL,$erase=false)
	{
		return self::arrayParam($_REQUEST,$key,$default,$erase);
	}

	/**
	 * 	method:	arrayParam
	 *
	 * 	todo: write documentation
	 */
	static public function setREQUEST($key,$value)
	{
		$_REQUEST[$key] = $value;

		return $value;
	}

	/**
	 * 	method:	arrayParam
	 *
	 * 	todo: write documentation
	 */
	static public function arrayParam(&$source,$key,$default=NULL,$erase=false)
	{
		if(is_array($key)){
			$k = array_shift(array_intersect($key,array_keys($source)));
			return self::arrayParam($source,$k,$default,$erase);
		}

		if(isset($source[$key])){
			$default = $source[$key];
			if($erase) unset($source[$key]);
		}

		return $default;
	}

	//	I wonder if this is true, or it's bullshit? could ask someone to verify whether they think it's safe or not
	//	TODO: I was told to replace this with a call to crypt()
	/**
	 * 	method:	getRandomCode
	 *
	 * 	todo: write documentation
	 */
	static public function getRandomCode($input=NULL)
	{
		$salt = '!@£$%^&*()QIDNFOEWBVIEWM:"|}{>|:|()**^$%(&&£NARVOEIUMCWP*£(£%@%*(}|:<>>fka9fgbqpeg';
		$input = $input !== NULL ? self::var_dump($input,true) : "";

		return sha1($salt.$input.time().mt_rand(0,21387132987).$salt);
	}

	//	IDEAS FOR A NEW(ER) SIMPLIFIED API
	static public function getSuper($type,$key,$default=NULL,$erase=false){}
	static public function setSuper($type,$key,$value){}

	//	DEPRECATED OLDER SUPER METHODS
	static public function insertRequestParam($key,$value){	self::setREQUEST($key,$value);	}
	static public function insertFilesParam($key,$value){	self::setFILES($key,$value);	}
	static public function insertCookieParam($key,$value){	self::setCOOKIE($key,$value);	}
	static public function insertSessionParam($key,$value){	self::setSESSION($key,$value);	}
	static public function insertPostParam($key,$value){	self::setPOST($key,$value);		}
	static public function insertGetParam($key,$value){		self::setGET($key,$value);		}

	static public function postParam($key,$default=NULL,$erase=false){ return self::getPost($key,$default,$erase); }
	static public function getParam($key,$default=NULL,$erase=false){ return self::getGET($key,$default,$erase); }
	static public function requestParam($key,$default=NULL,$erase=false){ return self::getREQUEST($key,$default,$erase);	}
	static public function filesParam($key,$default=NULL,$erase=false){ return self::getFILES($key,$default,$erase);	}
	static public function cookieParam($key,$default=NULL){ return self::getCOOKIE($key,$default); }
	static public function sessionParam($key,$default=NULL,$erase=false){ return self::getSESSION($key,$default,$erase); }

	//	DEPRECATED FUNCTION
	static public function exceptionBacktrace($string=false){
		return self::getStackTrace(NULL,true);
	}

	//	DEPRECATED: doesn't work very well with non-ascii characters like ü
	static public function htmlCutString($string,$length){
		return self::truncateString($string, $length);
	}
}
