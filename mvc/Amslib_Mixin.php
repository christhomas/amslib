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

	public function addMixin($object,$filter=array())
	{
		if(!is_array($filter)) $filter = array();

		if(is_object($object) || class_exists($object)){
			$filter = array_merge(
				$filter,
				get_class_methods("Amslib_Mixin"),
				array("__construct","getInstance")
			);

			$mixin	=	method_exists($object,"getMixin") ? $object->getMixin() : array();
			$list	=	array_merge(get_class_methods($object),$mixin);

			foreach($list as $m){
				//	Block some requested methods and then some obvious methods from being added to the mixin
				if(!empty($filter) && in_array($m,$filter)) continue;

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