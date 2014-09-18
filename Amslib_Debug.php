<?php
/**
 * 		There are functions for debugging, see var_dump, getStackTrace, errorLog which do
 *		various useful things, var_dump is exactly what you expect, but can format the data
 *		to be more visually understandable and return you a string so you can manipulate it further
 *		errorLog can output multiple pieces of data to the error log, obtain stack traces, etc
 */
class Amslib_Debug
{
	static protected $showErrorTrigger		=	false;
	static protected $originalErrorHandler	=	false;

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
		//	This method sometimes causes php to run out of memory, a problem which is very hard to find
		//	these two lines, let you increment a counter and die after a certain point, it's a cheap, nasty debugging method
		//	but you'll eventually find a count where it won't die "out of memory" and the next number up, will die
		//	then you'll get a stack trace of when it happened
		//	Yes, it's dumb, but I really don't know a way to get around this problem, PHP gives no tools to do so
		//	Uncomment these lines to use and comment them back once you're done
		//static $run = 0;
		//if(++$run == 4) die(self::getStackTrace(NULL,true));

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
	 *		-	The function is really really memory hungry, use Amslib_Debug::getStackTrace if you can
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
			//	NOTE: why in array_slice do we request a length of 1? we only want one return value??
			? current(array_slice(explode("\n",$t),$index,1))
			: ($var_dump ? self::var_dump($t[$index]) : $t[$index]);

			if($string || $var_dump){
				$t = htmlspecialchars($t,ENT_QUOTES,"UTF-8");
			}
		}

		//	NOTE: remove the first entry, it will always be this function, we never want that.
		if(is_string($t)){
			$t = implode("\n",array_slice(explode("\n",$t),1));
		}else{
			array_shift($t);
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

				$stack = self::getStackTrace(NULL,true);
				$stack = explode("\n",$stack);

				$c = count($command);

				if($c == 2){
					$stack = array_slice($stack,$command[1]);
				}else if($c == 3 && $command[2] > 0){
					$stack = array_slice($stack,$command[1],$command[2]);
				}

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
				if(is_object($a))	$a = array(get_class($a),self::var_dump($a));
				if(is_array($a)) 	$a = self::var_dump($a);
				if(is_bool($a))		$a = $a ? "true" : "false";
				if(is_null($a))		$a = "null";

				$a = trim(preg_replace("/\s+/"," ",$a));

				if(strlen($a) > $maxlength) $a = substr($a,0,$maxlength-50)."...[array too large to display]";

				$data[] = "arg[$k]=> $a";
			}
		}

		$stack = self::getStackTrace();

		if(!is_numeric($function)) $function = count($stack) > 1 ? 1 : 0;

		$line		= array("line"=>-1);
		$function	= false;

		if($function == 0){
			if(isset($stack[$function])){
				$line		= $stack[$function]["line"];
				$f			= explode("/",$stack[$function]["file"]);
				$function	= array(
						"class"=>"",
						"type"=>"",
						"function"=>end($f)
				);
			}
		}else{
			if(isset($stack[$function-1])){
				$line = $stack[$function-1]["line"];
			}

			if(isset($stack[$function])){
				$function = $stack[$function];
			}
		}

		if(!$function || !isset($function["class"]) || !isset($function["type"]) || !isset($function["function"])){
			$function	=	"(ERROR, function invalid: ".self::var_dump($function).")";
			$data		=	array(self::var_dump($stack));
		}else{
			$function	=	"{$function["class"]}{$function["type"]}{$function["function"]}($line)";
			//$data[] = self::var_dump($stack);
		}

		error_log("[DEBUG] $function, ".implode(", ",$data));

		return array("function"=>$function,"data"=>$data);
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
}