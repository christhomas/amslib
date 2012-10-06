<?php
class Amslib_Mixin
{
	private $mixin = array();

	public function __call($name,$args)
	{
		if(in_array($name,array_keys($this->mixin))){
			return call_user_func_array(array($this->mixin[$name],$name),$args);
		}else{
			Amslib_Keystore::add("__call[failure]",array(
				"class"		=>	get_class($this),
				"method"	=>	$name,
				"available"	=>	array_keys($this->mixin)
			));
		}

		return false;
	}

	//	TODO: implement reject+accept, now its just the bare idea
	public function addMixin($object,$reject=array(),$accept=array())
	{
		if(!is_array($reject)) $reject = array();
		if(!is_array($accept)) $accept = array();

		//	FIXME:	this has never happened, but if "object" is not an instance, then does that mean it'll
		//			all it's functions statically??? that could mean don't work like expected......
		if(is_object($object) || class_exists($object)){
			$reject = array_merge(
				$reject,
				get_class_methods("Amslib_Mixin"),
				array("__construct","getInstance")
			);

			$mixin	=	method_exists($object,"getMixin") ? $object->getMixin() : array();
			$list	=	array_merge(get_class_methods($object),$mixin);

			foreach($list as $m){
				//	Block some requested methods and then some obvious methods from being added to the mixin
				if(!empty($reject) && in_array($m,$reject)) continue;

				$this->mixin[$m] = $object;
			}
		}

		return $object;
	}

	public function getMixin()
	{
		return array_keys($this->mixin);
	}
}