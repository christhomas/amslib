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
 * 	class:	Amslib_Token
 *
 *	file:	Amslib_Token.php
 *
 * 	This class exists to store commonly used tokens and replace them, it's a quick and easy object
 * 	to allow you to represent in strings values which can be changed, or values which should not be stored
 * 	with static text.
 *
 * 	The idea is that you can store replacements, such as an a common string which is used many times but
 *  with an easily replacable piece of the string, which should be kept safe, outside of the
 * 	string, such as the configuration data, which should be more generic.
 *
 ******************************************************************************/
class Amslib_Token
{
    protected $format;

    protected $identifier;

    protected $input;

    protected $output;

    /**
     * 	array: $list
     */
    protected $list;

    public function __construct($format="(:_X_)",$identifier="_X_")
    {
        $this->list = array();

        $this->setFormat($format,$identifier);
    }

    public function setFormat($format,$identifier)
    {
        $this->format       = $format;
        $this->identifier   = $identifier;
        $this->input        = $this->getToken('(\w+)', true);
        $this->output       = $this->getToken('\\1');
    }

    public function getToken($token,$escape=false)
    {
        $format = $escape ? preg_quote($this->format) : $this->format;

        return str_replace($this->identifier,$token,$format);
    }

    /**
     * 	method: getList
     *
     * 	Return the list of keys that the tokeniser has replacements for
     *
     * 	returns:
     * 		-	An array of keys which are tokens that can be replaced
     *
     * 	notes:
     * 		-	Does not include values, since that could leak secure information
     */
    public function getList($values=false)
    {
        return $values ? $this->list : array_keys($this->list);
    }

    /**
     * 	method: add
     *
     * 	Add an array of tokens in one step to the storage
     *
     * 	params:
     * 		$list - The array of parameters to add
     * 		$overwrite - Whether you should overwrite the parameters if they exist or not
     */
    public function add($list,$overwrite=true)
    {
        $list = Amslib_Array::valid($list);

        foreach($list as $key=>$value){
            $this->set($key,$value,$overwrite);
        }
    }

    /**
     * 	method: set
     *
     * 	Set a single token and it's replacement into the storage
     *
     * 	params:
     * 		$token - The name of the token
     * 		$replacement - The string value to replace the token with
     * 		$overwrite - Whether or not to overwrite existing tokens
     *      $allowEmpty - Whether to allow an empty replacement string
     *
     * 	notes:
     * 		-	The default is to overwrite the tokens
     * 		-	The replacement value SHOULD be a string, most likely you're replacing tokens in text
     * 		-	If the replacement value is not a string, I don't know what will happen in your specific situation
     */
    public function set($token,$replacement,$overwrite=true,$allowEmpty=false)
    {
        if(!$token || !is_string($token) || !strlen($token)){
            Amslib_Debug::log("token = ",$token);
            return false;
        }

        if(!$allowEmpty && !$replacement || !is_scalar($replacement) || !strlen($replacement)){
            Amslib_Debug::log("token = ",$token,"replacement = ",$replacement,"stack_trace");
            return false;
        }

        if($overwrite == false && isset($this->list[$token])){
            return false;
        }

        return $this->list[$this->getToken($token)] = $replacement;
    }

    /**
     * 	method: get
     *
     * 	Pass a string and replace all the found tokens with their replacements
     *
     * 	params:
     * 		$string - The string to search and replace in
     *
     * 	returns:
     * 		-	A string, with all of the tokens replaces
     */
    public function get($string)
    {
        $count = 0;

        $list = $this->list;

        do {
            $string = preg_replace_callback(
                //  input regexp
                "/".$this->input."/isU",
                //  replacement function to execute for each match $m = matches
                function($m) use ($list) {
                    return $list[$m[0]];
                },
                //  input string
                $string,
                //  replace everything!!!11
                -1,
                //  store the count of all the replacements
                $count
            );
        }while($count);

        return $string;
    }
}