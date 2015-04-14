<?php
/**
 * 		There are functions for debugging, see var_dump, getStackTrace, errorLog which do
 *		various useful things, var_dump is exactly what you expect, but can format the data
 *		to be more visually understandable and return you a string so you can manipulate it further
 *		errorLog can output multiple pieces of data to the error log, obtain stack traces, etc
 */
class Amslib_Debug
{
	static public $dumpLimit = 1;
	/**
	 * 	method:	dump
	 *
	 * 	Use var_dump to dump to a string variables so they can be inspected
	 *
	 * 	parameters:
	 * 		$variable	-	The variable to dump, however, this function uses
	 * 						func_get_args so you can pass a list of them if you wanted
	 *
	 * 	NOTES:
	 * 		-	INCLUDES A VERY CRUDE DEBUGGING TOOL
	 * 			If var_dump attempts to dump something that is huge, it'll run out of memory,
	 * 			causing your PHP script to fail and when this happens, you don't really get
	 * 			any information that is useful, WHICH variable was too large.
	 * 			This simple counter will let you die after a certain number of calls to this method
	 * 			YES, I TOLD YOU IT WAS CRUDE.....Alter the dumpLimit static member variable in order to adjust
	 * 			_when_ to die, meaning you can run the code and when it dies here, you know the code up to
	 * 			that point is ok then you increase or decrease the limit until your code runs out of memory
	 * 			and you know the call which was responsible for running out of memory.
	 * 			I felt a bit dirty just writing this code.....
	 */
	static public function dump($variable)
	{
		//	NOTE: uncomment these lines to run the dirty nasty dump debugger :(
		//static $run = 0;
		//if(++$run == self::$dumpLimit) die(self::getStackTrace("type","text"));

		//	Obtain the variables to dump
		$args = func_get_args();

		//	var_dump all the variables and capture everything
		ob_start();
		array_map("var_dump",$args);
		return ob_get_clean();
	}

	/**
	 *	method:	pdump
	 *
	 *	(pdump means <pre>dump</pre>)
	 *	Print the dumping of various variables with optional hidden visibility for the browser
	 *
	 *	params:
	 *		$visible	-	Boolean true or false, to print the data out in a browser visible or invisible form (<pre display:none>)
	 *		$vargs		-	A variable list of arguments, minimum 1, of everything you want to dump
	 *
	 *	returns:
	 *		A string containing the data, html or not ready to be output somewhere
	 */
	static public function pdump($visible,$vargs)
	{
		$hidden = $visible ? "" : "style='display:none'";

		//	Obtain the variables to dump
		$vargs = func_get_args();
		array_shift($vargs);

		$dump = call_user_func_array("Amslib_Debug::dump",$vargs);

		return "<pre $hidden>$dump</pre>";
	}

	/**
	 *	method:	vdump
	 *
	 *	A wrapper around pdump which means visible pre-dump
	 *
	 *	params:
	 *		$vargs		-	A variable list of arguments, minimum 1, of everything you want to dump
	 *
	 *	returns:
	 *		A string containing the data, html or not ready to be output somewhere
	 */
	static public function vdump($args)
	{
		$args = func_get_args();
		array_unshift($args,true);

		return call_user_func_array("Amslib_Debug::pdump",$args);
	}

	/**
	 *	method:	hdump
	 *
	 *	A wrapper around pdump which means hidden pre-dump
	 *
	 *	params:
	 *		$vargs		-	A variable list of arguments, minimum 1, of everything you want to dump
	 *
	 *	returns:
	 *		A string containing the data, html or not ready to be output somewhere
	 */
	static public function hdump($args)
	{
		$args = func_get_args();
		array_unshift($args,false);

		return call_user_func_array("Amslib_Debug::pdump",$args);
	}

	/**
	 * 	method:	log
	 *
	 * 	todo: write documentation
	 */
	static public function log()
	{
		$args		=	func_get_args();
		$data		=	array();
		$maxlength	=	8912;
		$function	=	false;

		foreach($args as $k=>$a){
			if(is_string($a) && strpos($a,"stack_trace") === 0){
				$parts = explode(",",$a);
				array_shift($parts); // discard the "stack_trace" part

				$args = count($parts)
					? array("type","text","limit",$parts)
					: array("type","text");

				$stack = call_user_func_array("Amslib_Debug::getStackTrace",$args);
				$stack = explode("\n",$stack);

				foreach($stack as $k=>$row){
					error_log("[TRACE:$k] $row");
				}
			}else if(is_string($a) && strpos($a,"memory_usage_human") === 0){
				$data[] = "memory_usage_human = ".self::getMemoryUsage(true,true);
			}else if(is_string($a) && strpos($a,"memory_usage") === 0){
				$data[] = "memory_usage = ".self::getMemoryUsage(true);
			}else if(is_string($a) && strpos($a,"func_offset") === 0){
				$command = explode(",",array_shift($args));

				if(count($command) == 1) $function = $command[0];
			}else{
				if(is_object($a))	$a = array(get_class($a),self::dump($a));
				if(is_array($a)) 	$a = self::dump($a);
				if(is_bool($a))		$a = $a ? "true" : "false";
				if(is_null($a))		$a = "null";

				$a = trim(preg_replace("/\s+/"," ",$a));

				if(strlen($a) > $maxlength) $a = substr($a,0,$maxlength-50)."...[array too large to display]";

				$data[] = "arg[$k]=> $a";
			}
		}

		$stack = self::getStackTrace();
		
		//	eliminate this function from the stack
		$location = array_shift(array_slice($stack,2,1));
		
		$line = isset($location["line"]) ? "@{$location["line"]}" : "";
		
		$location = Amslib_Array::hasKeys($location,array("class","type","function"))
			? $location["class"].$location["type"].$location["function"]
			: $location;
		
		$location = is_array($location) && Amslib_Array::hasKeys($location,"file","function")
			? basename($location["file"])."({$location["function"]})"
			: $location;

		error_log($log = $location.$line.": ".implode(", ",$data));

		return array("function"=>$function,"data"=>$data,"log"=>$log);
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
	 * 	method: getStackTrace
	 *
	 * 	todo: write documentation
	 */
	static public function getStackTrace($va_args=NULL)
	{
		$a = func_get_args();
		$a = Amslib_Array::toKeyValue($a);

		$e = isset($a["exception"]) ? $a["exception"] : new Exception();

		if(isset($a["type"]) && in_array($a["type"],array("text","string","html"))){
			//	A common mistake is putting "text" instead of "string" so i'll cover this up here
			if($a["type"] == "text") $a["type"] = "string";

			$t = $e->getTraceAsString();
			$t = explode("\n",$t);
			$i = $a["type"] == "string" ? "\n" : "<br/>";
		}else{
			$t = $e->getTrace();
			
			//	remove the arguments because they are largely useless
			foreach($t as $k=>$ignore){
				unset($t[$k]["args"]);
			}
		}

		if(isset($a["limit"])){
			$l = $a["limit"];

			if(is_numeric($l)){
				$l = array($l);
			}

			if(is_array($l) && count($l)){
				$p = array($t,$s=intval(array_shift($l)));

				if(count($l) && ($e=intval(array_shift($l))) > $s){
					$p[] = $e;
				}

				$t = call_user_func_array("array_slice",$p);
			}
		}

		return isset($i) ? implode($i,$t) : $t;
	}

	/**
	 * 	method: getMemoryUsage
	 *
	 * 	Return the memory usage that PHP is using, with various filters
	 *
	 * 	parameters:
	 * 		$real	-	Whether to return the real memory usage of PHP, or the value returned from emalloc
	 * 		$human	-	Whether to return the memory usage as bytes or a human readable string
	 *
	 * 	returns:
	 * 		Either the number of bytes, real or approximated, or a human readable interpretation of that number of bytes
	 */
	static public function getMemoryUsage($real=true,$human=false)
	{
		$size = memory_get_usage($real);

		if($human){
			$unit = array('b','kb','mb','gb','tb','pb');
			$size = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
		}

		return $size;
	}

	/**
	 * 	method:	enable
	 *
	 * 	A method to turn on, off PHP's error reporting and display errors.  All this does is
	 * 	convert a two liner into a one liner, but also might be enhanced in the future with
	 * 	more functionality relating to errorLog and var_dump, etc.
	 *
	 * 	parameters:
	 * 		$state - Whether or not to turn showing errors on or off.
	 */
	static public function enable($state=true)
	{
		if($state){
			ini_set("display_errors",$state);
			error_reporting(-1);
		}else{
			ini_set("display_errors",$state);
			error_reporting(intval($state));
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
	 *		$handler	-	The function call to make when an error occurs
	 *		$reset		-	Whether to override the initial handler and set the new handler regardless
	 *
	 *	returns:
	 *		The handler that was set
	 */
	static public function setErrorHandler($handler,$reset=false)
	{
		static $handler = NULL;

		if($handler === NULL || $reset){
			$handler = set_error_handler($handler);
		}

		return $handler;
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
	 *	method: formatJSON
	 *
	 *	A method to pretty format json output so you can output it somewhere
	 *
	 *	parameters:
	 *		$json	-	The json that you'd like to format
	 *
	 *	notes:
	 *		-	You can pass an array and it'll convert it to json using json_encode($json)
	 *		-	I got this code from here: http://stackoverflow.com/a/9776726/279147
	 *		-	Thanks a lot!!
	 *
	 *	returns:
	 *		A string containing the json output, or "FALSE: json invalid" when it fails
	 */
	static public function formatJSON($json)
	{
		$error = "FALSE: json invalid";

		if(is_array($json)){
			try{
				$json = json_encode($json);
			}catch(Exception $e){
				return $error;
			}
		}

		if(!is_string($json)){
			return $error;
		}

		$result = '';
		$level = 0;
		$in_quotes = false;
		$in_escape = false;
		$ends_line_level = NULL;
		$json_length = strlen( $json );

		for( $i = 0; $i < $json_length; $i++ ) {
			$char = $json[$i];
			$new_line_level = NULL;
			$post = "";
			if( $ends_line_level !== NULL ) {
				$new_line_level = $ends_line_level;
				$ends_line_level = NULL;
			}
			if ( $in_escape ) {
				$in_escape = false;
			} else if( $char === '"' ) {
				$in_quotes = !$in_quotes;
			} else if( ! $in_quotes ) {
				switch( $char ) {
					case '}': case ']':
						$level--;
						$ends_line_level = NULL;
						$new_line_level = $level;
						break;

					case '{': case '[':
						$level++;
					case ',':
						$ends_line_level = $level;
						break;

					case ':':
						$post = " ";
						break;

					case " ": case "\t": case "\n": case "\r":
						$char = "";
						$ends_line_level = $new_line_level;
						$new_line_level = NULL;
						break;
				}
			} else if ( $char === '\\' ) {
				$in_escape = true;
			}
			if( $new_line_level !== NULL ) {
				$result .= "\n".str_repeat( "\t", $new_line_level );
			}
			$result .= $char.$post;
		}

		return $result;
	}

	static private function _______DEPRECATED_METHODS_BELOW_THIS_LINE(){}

	//	DEPRECATED, BUT STILL USED EVERYWHERE
	static public function var_dump($variable,$print=false,$hidden=false)
	{
		return $print
			? self::pdump($hidden,$variable)
			: self::dump($variable);
	}

	static public function errorLog()
	{
		$args = func_get_args();

		return call_user_func_array("Amslib_Debug::log",$args);
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
	 *		-	The function is really really memory hungry, use Amslib_Debug::getStackTrace if you can
	 *		-	This is a really ancient function, it's hard to know what it's trying to do
	 	*/
	static public function backtrace()
	{
		//	Find out who is using this method so I can upgrade it's code and delete this
		Amslib_Debug::log(__METHOD__,"stack_trace");

		$args	=	func_get_args();
		$bt		=	debug_backtrace();

		$slice	=	array($bt);
		if(count($args) && is_numeric($args[0])) $slice[] = array_shift($args);
		if(count($args) && is_numeric($args[0])) $slice[] = array_shift($args);

		if(count($slice) > 1) $bt = call_user_func_array("array_slice",$slice);

		return Amslib_Array::filterKey($bt,Amslib_Array::filterType($args,"is_string"));
	}

	static public function showErrors($state=true)
	{
		Amslib_Debug::log(__METHOD__,"deprecated, use 'enable(\$state)' instead","stack_trace");
		self::enable($state);
	}
}