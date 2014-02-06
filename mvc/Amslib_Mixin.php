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
		}else{
			//	build an array map of objects to methods so you can output a
			//	data structure to the error log with what was searched and failed
			//	to find a match, for debugging
			$map = array();
			foreach($this->mixin as $method=>$object){
				$o = get_class($object);

				if(!isset($map[$o])) $map[$o] = array();

				$map[$o][] = $method;
			}
			//	Log the failure to find a method to call to the error log
			Amslib::errorLog("MIXIN FAILURE","stack_trace",get_class($this),$name);
			foreach($map as $o=>$l){
				Amslib::errorLog("MIXIN FAILURE DATA",$o,$name,implode(",",$l));
			}
		}

		return false;
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
			if(is_string($object)) $name = is_string($object);
			if(is_object($object)) $name = get_class($object);

			Amslib::errorLog("MIXIN FAILURE","stack_trace",$name);
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