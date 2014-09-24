<?php

class Amslib_String
{
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
	 * 	method: slugify2
	 *
	 * 	A new version which I think will function better than the original
	 * 	I just don't want to replace it yet without testing it a bit before and
	 * 	being more confident
	 *
	 * 	parameters:
	 * 		$string	-	The string to slugify
	 * 		$slug	-	The character to use when slugifying the parts together
	 * 		$extra	-	Extra characters you want to allow in the string which normally would be removed
	 *
	 * 	NOTE: I stole this code from => https://github.com/alixaxel/phunction/blob/master/phunction/Text.php
	 */
	public static function slugify2($string, $slug = '-', $extra = null)
	{
		$string		=	self::translit($string,$extra);
		$string		=	preg_replace('~[^0-9a-z'.preg_quote($extra, '~').']+~i',$slug, $string);
		//	This part will clean up the end of the filename, before the extension
		//	But only do it if you find more than one part because there was an extension
		$parts		=	explode(".",$string);
		if(count($parts) > 1){
			$extension	=	array_pop($parts);
			$string		=	rtrim(implode(".",$parts),$slug).".$extension";
		}

		return strtolower(trim($string, $slug));
	}

	/**
	 * 	method:	trimString
	 *
	 * 	A cheaper, but far less useful version of self::truncateString, does not consider html, does nothing
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
	 * 	method: translit
	 *
	 * 	A function which strips away the accents and other unwanted characters from
	 * 	a url, making it accent-crazy-less
	 *
	 * 	NOTE: I stole this code from => https://github.com/alixaxel/phunction/blob/master/phunction/Text.php
	 */
	static public function translit($text,$extra=null)
	{
		$text = htmlentities($text, ENT_QUOTES, 'UTF-8');
		$text = preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', $text);
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = preg_replace(array('~[^0-9a-z'.preg_quote($extra,'~').']~i', '~[ -]+~'), ' ', $text);

		return trim($text, ' -');
	}

	/**
	 * 	method:	reduceSlashes
	 *
	 * 	Reduce the consecutive slashes in a string to a single item, /=>/, //=>/, ///=>/, etc
	 *
	 * 	params:
	 * 		$string	-	The string to process
	 *
	 * 	returns:
	 * 		A string with all the slashes reduced
	 *
	 * 	notes:
	 * 		If the string is a URL beginning with http://, use Amslib_Website::reduceSlashes instead
	 */
	static public function reduceSlashes($string)
	{
		return preg_replace('#//+#','/',$string);
	}

	/**
	 * 	method:	stripComments
	 *
	 * 	Remove all comments from a string, it might not be perfect
	 *
	 * 	params:
	 * 		$string	-	The string to process
	 *
	 * 	returns:
	 * 		A string without comments
	 *
	 * 	notes:
	 * 		-	I got some of this code originally from: http://stackoverflow.com/a/1581063/279147
	 */
	static public function stripComments($string)
	{
		if(!is_string($string)){
			Amslib_Debug::log(__METHOD__,"Attempting to strip comments from something that is not a string");
			return false;
		}

		$string = preg_replace('#<!--[^\[<>].*?(?<!!)-->#s', '', $string);

		$regex = array(
				"`^([\t\s]+)`ism"=>'',
				"`\/\*(.+?)\*\/`ism"=>"",
				"`([\n\A;]+)\/\*(.+?)\*\/`ism"=>"$1",
				"`([\n\A;\s]+)//(.+?)[\n\r]`ism"=>"$1\n",
				"`(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+`ism"=>"\n"
		);
		$string = preg_replace(array_keys($regex),$regex,$string);

		return $string;
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
}