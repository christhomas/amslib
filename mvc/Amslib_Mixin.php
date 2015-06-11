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
 * 	class:	Amslib_Mixin
 *
 *	group:	mvc
 *
 *	file:	Amslib_Mixin.php
 *
 *	description:
 *		todo, write description
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_Mixin
{
	private $mixin = array();

	/**
	 * 	method:	__call
	 *
	 * 	todo: write documentation
	 */
	public function __call($name,$args)
	{
		if(in_array($name,array_keys($this->mixin))){
			return call_user_func_array(array($this->mixin[$name],$name),$args);
		}
		
		$e = new Amslib_Exception("Mixin Failure: method was not found in object");
		$e->setData("this",get_class($this));
		$e->setData("method",$name);
		$e->setData("arg_types",array_map("gettype",$args));
		$e->setData("mixin_data",$this->mixin);

		throw $e;
	}

	/**
	 * 	method:	addMixin
	 *
	 * 	todo: write documentation
	 *
	 * 	notes:
	 * 		-	There is a potential !!GOTCHA!! here, if you mixin one object into another,
	 * 			then that object into another, the second mixin only searches for native
	 * 			methods in the first object, it won't see the mixed in methods from the original object
	 * 			e.g: object2::mixin(object1::mixin(object0))),
	 * 			object2 can only call native methods on object1, object0's methods are invisible
	 * 		-	TODO: implement reject+accept, now its just the bare idea
	 */
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
		}else{
			$name = $object;
			if(is_string($object)) $name = $object;
			if(is_object($object)) $name = get_class($object);
			
			$e = new Amslib_Exception("Mixin Failure: Object was not valid");
			$e->setData("object",$object);
			
			throw $e;
		}

		return $object;
	}

	/**
	 * 	method:	getMixin
	 *
	 * 	todo: write documentation
	 */
	public function getMixin()
	{
		return array_keys($this->mixin);
	}
}