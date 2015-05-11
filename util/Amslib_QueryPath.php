<?php
//	we have to do this here because QueryPath has no constructor we can call, so it can't be autoloaded
//	this class basically "fixes" that by wrapping it all up in a way that can be autoloaded
//	it's such a hack :P

//	NOTE: actually it does have a constructor, but it's also got a namespaced class library, which is
//			something I'm unsure about, I'm not sure how to integrate this with the Amslib class loader
//	NOTE: or perhaps I don't....But I would like that Amslib_QueryPath inherits from QueryPath and provides
//			a seamless interface for QueryPath instead of wrapping it up like this, I think it would be better
require_once(dirname(__FILE__)."/QueryPath/qp.php");

class Amslib_QueryPath
{
	static protected $qp;

	static public function qp($document=NULL, $selector=NULL, $options=array())
	{
		self::$qp = false;

		//	NOTE: we do this output buffer trick to contain any output it might make, but strlen the output afterwards
		//			if there was a problem, surely it'll be non-zero if it outputs anything, but we prevent this
		//			from breaking the output of the system, so we contain the problem, although we don't skip it
		//			or even control it, because I haven't found a way to safely do that without breaking other stuff
		ob_start();
		self::$qp = QueryPath::with($document, $selector, $options);
		$warnings = ob_get_clean();

		//	Something went wrong, but didn't trigger an exception
		if(strlen($warnings)){
			Amslib_Debug::log("QueryPath did not produce clean output when processing document, this is not normal",$warnings);
		}

		return self::$qp;
	}

	static public function htmlqp($document=NULL,$selector=NULL,$options=array())
	{
		//	I copied and modified the default options from the htmlqp method to provide a custom version for Amslib
		//	NOTE: Hmm....I'm not 100% sure this will work in all circumstances....
		$document = iconv("ISO8859-1","UTF-8",$document);

		//	NOTE: this actually broke somethings, it would convert < into &lt; and that would mean HTML stopped working :(
		//if(is_string($document)) $document = htmlspecialchars($document,ENT_COMPAT,"UTF-8");

		try{
			//	NOTE: see output buffer trick comment in qp()
			ob_start();
			$html = QueryPath::withHTML($document, $selector, $options);
			$warnings = ob_get_clean();

			if(strlen($warnings)){
				Amslib_Debug::log("FAILED TO OBTAIN CLEAN OUTPUT WHEN PROCESSING HTML: error = ",$warnings);
			}
		}catch(Exception $e){
			//	I dunno what to do here
		}

		return $html;
	}

	/**
	 * 	method:	toArray
	 *
	 * 	todo: write documentation
	 */
	static public function toArray($node,$recursive=true)
	{
		if(!$node || $node->count() == 0) return false;

		try{
			$data			=	array();
			$data["attr"]	=	$node->attr();
			$data["tag"]	=	$node->tag();
			$childNodes		=	$node->branch()->children();

			//	recurse to decode the child or merely store the child to process later
			foreach($childNodes as $c){
				$data["child"][] = $recursive
					? self::toArray($c,$recursive)
					: array("tag"=>$c->tag(),"child"=>$c);
			}

			//	If the node doesn't have children, obtain the text and store as it's value
			if(count($childNodes) == 0) $data["value"] = $node->text();
		}catch(Exception $e){
			Amslib_Debug::log("QueryPath Exception",$e->getMessage);
		}

		return $data;
	}

	static public function execCallback($key,$callback,$object=NULL)
	{
		if(!self::$qp || !is_callable($callback)){
			print("FAILED CALLBACK = ".Amslib_Debug::pdump(true,$callback));
			return;
		}

		try{
			$results = self::$qp->branch()->find($key);
		}catch(Exception $e){
			Amslib_Debug::log("QueryPath Exception",$e->getMessage);
		}

		foreach($results as $r){
			$r = Amslib_QueryPath::toArray($r);

			call_user_func($callback,$r["tag"],$r,$object);
		}
	}

	static public function get()
	{
		return self::$qp;
	}
}