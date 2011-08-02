<?php
/*******************************************************************************
 * Copyright (c) {04/02/2008} {Christopher Thomas}
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
 * File: Amslib_Validator3.php
 * Title: Antimatter Form Validator
 * Version: 3.0
 * Project: amslib
 *
 * Contributors:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

/**
 * class: Amslib_Validator3
 *
 * Validates a form posted from the browser to see whether the data conforms to the expected types
 *
 * future improvements
 * 	-	Add a url validator method perhaps?
 * 	-	move the required parameter into the options structure
 * 	-	Allow a date to validate between two unix timestamps defining the start and end period (useful for birthdates, restriction of dates based on product availability etc)
 */
class Amslib_Validator3
{
	/**
	 * array:	$__types
	 *
	 * Contains all the registered types that are allowed to validate, in order to add a new validator
	 * you must register it's type in the constructor
	 */
	protected $__types;

	/**
	 * array:	$__error
	 *
	 * Contains an array of strings which represent data elements which failed to validate
	 */
	protected $__error;

	/**
	 * array:	$__items
	 *
	 * Contains an array of items to validate from the source data array, the source
	 * might contain 100 elements, but you only want to validate 20, this is how we know which
	 * to validate and which to ignore
	 */
	protected $__items;

	/**
	 * array:	$__source
	 *
	 * The source array of data to validate according to the rules attached to the items
	 */
	protected $__source;

	/**
	 * array:	$__validData
	 *
	 * The data from the source array which was validated correctly, this is wanted usually to refill the form with information
	 * after a failed validation has occured, to stop the user from having to retype all the form again
	 */
	protected $__validData;

	/**
	 * boolean:	$__hasExecuted
	 *
	 * A toggle to know whether the validator has run or not, sometimes this is wanted to know whether or not to bother obtaining
	 * information about the status of validation, because it's simple to check whether any validation information retrieved would
	 * be correct or unnecessary
	 */
	protected $__hasExecuted;

	/**
	 * boolean: $__hasRequiredRules
	 *
	 * This flag indicates whether there are some rules which are required to pass validation in order to return success from
	 * the execute method, this is to fix an issue where if the data source was empty, the default action was to return true
	 * but if there are required rules, then empty data source means that actually, the source failed to validate, so should
	 * return false
	 */
	protected $__hasRequiredRules;
	
	//	NOTE: we ignore the name, it's not used here
	protected function __require_one($name,$value,$required,$options)
	{
		unset($options["__vobject"]);
		
		$dest	=	array_pop($options);
		$valid	=	$this->getValid();
		$keys	=	array_keys($valid);
		
		foreach($options as $o){
			//	Make sure the key exists in the array
			if(in_array($o,$keys)){
				//	If the key is a string, makes sure it's not empty
				if(is_string($valid[$o]) && strlen($valid[$o]) == 0) continue;
				
				$this->setValid($dest,$valid[$o]);

				return true;
			}
		}
		
		if(!$required) return true;
		
		$this->setError($name,"REQUIRE_ONE_FAILED");

		return false;
	}
	
	protected function __array($name,$value,$required,$options)
	{
		if(!isset($options["type"])) return "ARRAY_REQUIRE_TYPE_PARAM";
		
		if(!is_array($value)) return "ARRAY_INVALID";
		
		$arrayValidator = new self($value);
		foreach($value as $k=>$v) $arrayValidator->add($k,$options["type"],$required,$options);
		
		$data = array(
			"success"	=>	$arrayValidator->execute(),
			"valid"		=>	$arrayValidator->getValid(),
			"errors"	=>	$arrayValidator->getErrors()
		);
		
		$this->setValid($name,$data);
		return true;
	}
	
	protected function __isbn($name,$value,$required,$options)
	{
		//	strip out some characters we know might be present, but have to be removed
		$options["original_value"] = $value;
		$value = str_replace(array("isbn","-"," ",".",","),"",strtolower($value));
		
		if(is_string($value)){
			if(strlen($value) == 10)	return $this->__isbn10($name,$value,$required,$options);
			if(strlen($value) == 13)	return $this->__isbn13($name,$value,$required,$options);
			
			if($required) return "ISBN_INVALID";
		}
		
		if($required) return "ISBN_NOT_STRING";
		
		return true;
	}
	
	protected function __isbn10($name,$value,$required,$options)
	{
		//	Disclaimer, I took this code from the isbn wikipedia article
    	$check = 0;
    	for ($i = 0; $i < 9; $i++) $check += (10 - $i) * substr($value, $i, 1);
    	$t = substr($value, 9, 1); // tenth digit (aka checksum or check digit)
    	$check += ($t == 'x' || $t == 'X') ? 10 : $t;
    	
    	if($check % 11 == 0) $this->setValid($name,$options["original_value"]);
    	else if($required) return "ISBN_10_INVALID";
    	
    	return true;
	}
	
	protected function __isbn13($name,$value,$required,$options)
	{
		//	Disclaimer, I took this code from the isbn wikipedia article
		$check = 0;
    	for ($i = 0; $i < 13; $i+=2) $check += substr($value, $i, 1);
    	for ($i = 1; $i < 12; $i+=2) $check += 3 * substr($value, $i, 1);
    	
    	if($check % 10 == 0) $this->setValid($name,$options["original_value"]);
    	else if($required) return "ISBN_13_INVALID";
    	
    	return true;
	}
	
	/**
	 * method:	__text
	 *
	 * Validate a text field using the information passed to it
	 *
	 * parameters:
	 * 	name		-	The name of the field
	 * 	value		-	The value of the field
	 * 	required	-	Boolean true or false whether the field is mandatory or not
	 * 	options		-	Validation restrictions: minlength,maxlength
	 *
	 * returns:
	 * 	If failed with the string length is zero and required being enabled, return "TEXT_LENGTH_ZERO"
	 * 	If failed with string length being below minimum length, return "TEXT_LENGTH_IS_BELOW_MINIMUM"
	 * 	If failed with string length being above maximum length, return "TEXT_LENGTH_IS_ABOVE_MAXIMUM"
	 * 	If successful, will return true and assign the valid data into the $__validData array for retrieval later
	 *
	 * operations:
	 * 	-	Find the length of the string
	 * 	-	If the length of the string is zero, but required, return VALIDATOR_TEXT_LENGTH_ZERO
	 * 	-	If the length of the string is below the minimum set, return VALIDATOR_TEXT_LENGTH_IS_BELOW_MINIMUM
	 * 	-	If the length of the string is above the maximum set, return VALIDATOR_TEXT_LENGTH_IS_ABOVE_MAXIMUM
	 * 	-	else the validation passed, move to setting the validData
	 * 	-	if successfully validated Set the validData item for this method
	 *
	 * notes:
	 * 	-	we should create a shared method for doing simple calculations, like "checkLength" to see whether the value passes this test or not
	 */
	protected function __text($name,$value,$required,$options)
	{
		$len = strlen(trim($value));
		
		$error = false;

		if($len == 0 && !isset($options["permit-empty"])){
			$error = "TEXT_LENGTH_ZERO";
		}
		
		if(isset($options["minlength"]) && $len < $options["minlength"]){
			$error = "TEXT_LENGTH_IS_BELOW_MINIMUM";
		}
		
		if(isset($options["maxlength"]) && $len > $options["maxlength"]){
			$error = "TEXT_LENGTH_IS_ABOVE_MAXIMUM";
		}
		
		if(isset($options["limit-input"]) && !in_array($value,$options["limit-input"])){
			$error = "TEXT_CANNOT_MATCH_AGAINST_LIMIT";
		}
		
		//	NOTE: maybe "invalid" should change to "exclude-input" to be closer to the syntax of "limit-input" ?
		if(isset($options["invalid"]) && in_array($value,$options["invalid"])){
			$error = "TEXT_INVALID_INPUT";
		}

		if($required == true && $error !== false) return $error;
		
		if($error === false) $this->setValid($name,$value);

		return true;
	}

	/**
	 * method:	__alpha
	 *
	 * Validate a text field contains only alphabetical (A-Za-z) characters
	 *
	 * parameters:
	 * 	name		-	The name of the field
	 * 	value		-	The value of the field
	 * 	required	-	Boolean true or false whether the field is mandatory or not
	 * 	options		-	Validation restrictions: minlength,maxlength
	 *
	 * returns:
	 * 	If failed, will return a text string representing the error (this can be used to represent a language translation key)
	 * 	If successful, will return true and assign the valid data into the $__validData array for retrieval later
	 *
	 * operations:
	 * 	-	Otherwise, validate as a alphabetical string only
	 * 	-	If success, validation passed, set the validData to store the data and return true
	 */
	protected function __alpha($name,$value,$required,$options)
	{
		$status = $this->__text($name,$value,$required,$options);

		//	Text validation failed, drop out here
		if($status !== true) return $status;

		if(!ctype_alpha($value)) return "TEXT_NOT_ALPHABETICAL";

		$this->setValid($name,$value);

		return true;
	}

	/**
	 * method:	__alpha_relaxed
	 *
	 * Validate a text field contains alphabetical (A-Za-z) characters and some other normal human text characters
	 *
	 * parameters:
	 * 	name		-	The name of the field
	 * 	value		-	The value of the field
	 * 	required	-	Boolean true or false whether the field is mandatory or not
	 * 	options		-	Validation restrictions: minlength,maxlength
	 *
	 * returns:
	 * 	If failed, will return a text string representing the error (this can be used to represent a language translation key)
	 * 	If successful, will return true and assign the valid data into the $__validData array for retrieval later
	 */
	protected function __alpha_relaxed($name,$value,$required,$options)
	{
		$status = $this->__text($name,$value,$required,$options);

		//	Text validation failed, drop out here
		if($status !== true) return $status;

		$regexp = preg_match("/[\p{N}\p{P}\p{S}]/i",$value);

		if($regexp != 0) return "TEXT_NOT_ALPHABETICAL";

		$this->setValid($name,$value);

		return true;
	}

	protected function __password($name,$value,$required,$options)
	{
		//	TODO: This seems unnecessarily complex
		if(isset($options["p1"]) && isset($options["p2"])){
			$f1 = $options["p1"];
			$f2 = $options["p2"];

			if((!isset($this->__source[$f1]) || !isset($this->__source[$f2]))){
				if($required) return "PASSWORD_FIELDS_MISSING";

				return true;
			}

			$p1 = $this->__source[$f1];
			$p2 = $this->__source[$f2];

			if($p1 != $p2){
				//	strings not identical

				//	password is not required, so just return true or "NO_MATCH" if required is true
				if(!$required) return true;

				$this->setError($f1,$p1,"PASSWORDS_NO_MATCH");
				$this->setError($f2,$p2,"PASSWORDS_NO_MATCH");

				return "PASSWORDS_NO_MATCH";
			}else{
				//	strings are identical

				//	Required is true and strings are both empty
				if($required && !strlen($p1) && !strlen($p2)){
					$this->setError($f1,$p1,"PASSWORDS_NO_MATCH");
					$this->setError($f2,$p2,"PASSWORDS_NO_MATCH");

					return "PASSWORDS_EMPTY";
				}

				$this->setValid($name,$p1);
				return true;
			}
		}else{
			$status = $this->__text($name,$value,$required,$options);

			if($status === true){
				$this->setValid($name,$value);
				return true;
			}
			
			if($required == false) return true;
			
			return $status;
		}

		return false;
	}

	/**
	 * method:	__dni
	 *
	 * Validate a Spanish DNI number.  DNI is not the 100% correct technical name for this, but it's hard to give a name
	 * to it since some people call it DNI or others NIF/CIF, it was decided to call it DNI because it made the most sense,
	 * however imperfect.  A DNI number is a identification number for either a spanish national (NIF) a spanish company (CIF)
	 * or a foreigner (NIE)
	 *
	 * parameters:
	 * 	name		-	The name of the field
	 * 	$code		-	The value of the field
	 * 	required	-	Boolean true or false whether the field is mandatory or not
	 * 	options		-	Validation restrictions
	 *
	 * returns:
	 *	If code does not match any DNI like profile, will return "DNI_INVALID"
	 * 	If code does match, look for __nif, __cif or __nie for the return information, it completely delegates everything
	 *
	 * returns:
	 * 	Will set the valid value, or return VALIDATOR_DNI_INVALID for a invalid code
	 */
	protected function __dni($name,$code,$required,$options)
	{
		$code = strtoupper($code);
		
		if(ereg("^[JABCDEFGHI]{1}[0-9]{7}[A-Z0-9]{1}$",$code)){
			return $this->__cif($name,$code,$required,$options);
		}
		
		//	FIXME:	this regexp looks wrong, can you have letters in the 
		//			middle of a nie? I thought it was identical	to NIF??
		if(ereg("^[TX]{1}[A-Z0-9]{8}[A-Z]?$",$code)){
			return $this->__nie($name,$code,$required,$options);
		}
		
		if(ereg("^[0-9]{8}[A-Z]{1}$",$code)){
			return $this->__nif($name,$code,$required,$options);
		}

		return "DNI_INVALID";
	}
	
	/**
	 * function: __nif
	 * 
	 * Validate a Spanish NIF identication code
	 * 
	 * parameters:
	 * 	$code	-	The NIF DNI code to check
	 * 
	 * returns:
	 * 	If failed because the end character did not match the correct calculated one, return "NIF_INVALID
	 * 	If failed because the last letter was not a alpha character, return "NIF_ENDCHAR_NOT_ALPHA"
	 * 	If successful, will set the valid data array and return true
	 * 
	 * operations:
	 * 	-	Check the end character is a letter
	 * 	-	obtain the numerical part and modulus against 23
	 * 	-	The result, will be an array index into a list of validation characters
	 * 	-	If the end letter from the dni was the same letter as the validation character then the DNI is valid
	 */
	protected function __nif($name,$code,$required,$options)
	{
		$endLetter = substr($code,strlen($code)-1); 
		if(!is_numeric($endLetter)){
			$source = "TRWAGMYFPDXBNJZSQVHLCKET";
			
			$code	=	substr($code,0,-1);
			$sum	=	$code%23;
	
			$calcLetter = substr($source,$sum,1);
	
			if($endLetter != $calcLetter) return "NIF_INVALID";
			
			$this->setValid($name,$code);
			
			return true;
		}
		
		if(!$required) return true;
	
		return "NIF_ENDCHAR_NOT_ALPHA";
	}
	
	
	/**
	 * function: __cif
	 * 
	 * Validate a Spanish CIF identification code
	 * 
	 * parameters:
	 * 	$code	-	The CIF to check
	 * 
	 * returns:
	 *	If failed, will return a string "CIF_INVALID"
	 * 	If successful, will set the valid data array and return true
	 * 
	 * operations:
	 * 	-	grab the last value, this is the checksum value
	 * 	-	sum all the odd numbers together
	 * 	-	double each even number and if above 9, add both digits together like the luhn algoritum [15 -> 6 (1+5)] and add the result to the sum
	 * 	-	obtain the modulus of the resulting sum as the control (obtain the modulus of the that as well (in case of sum = 10)
	 * 	-	If the control equals the last value, or the letter at that index, then return positively, otherwise, failure
	 */
	protected function __cif($name,$code,$required,$options)
	{
		$lastLetter = array("J", "A", "B", "C", "D", "E", "F", "G", "H", "I");
	
		$numeric = substr($code,1);
		
		$last = substr($numeric,strlen($numeric)-1);	
		$sum = 0;
		
		//	Sum up all the even numbers
		for($pos=1;$pos<7;$pos+=2){
			$sum += (int)(substr($numeric,$pos,1));
		}
		
		//	Sum up all the odd numbers (but differently)
		//	This uses the Luhn Algorithm: 
		//		Any value greater than 10, comprises two numbers (etc: 15 is [1, 5] )
		//		Add both together, this is the value to sum
		for($pos=0;$pos<8;$pos+=2){
			$val = 2*(int)(substr($numeric,$pos,1));
			$val = str_pad($val,2,"0",STR_PAD_LEFT);
			$sum += (int)$val[0]+(int)$val[1];
		}
			
		//	Obtain the modulus of 10 and subtract it from 10, if the sum was 10, control is 0 (second modulus)
		$control = (10 - ($sum % 10)) % 10;
		
		if(($last == $control) || ($last == $lastLetter[$control])){
			$this->setValid($name,$code);

			return true;
		}
		
		if(!$required) return true;
		
		return "CIF_INVALID";
	}
	
	/**
	 * function: __nie
	 * 
	 * Validate a Spanish NIE identification code
	 * 
	 * parameters:
	 * 	$code	-	The NIE DNI code to check
	 * 
	 * returns:
	 * 	If failed, will return a string "NIE_INVALID"
	 * 	If successful, will set the valid data array and return true 
	 * 
	 * notes:
	 * 	-	This is a proxy method for validateNIF, which is identical in calculation, except we need to remove the
	 * 		X from the front of the NIE and then check the remaining string (which is a valid NIE, or should be)
	 */
	protected function __nie($name,$code,$required,$options)
	{
		$firstCharacter = substr($code,0,1);
		if($firstCharacter == "X"){
			$nif = substr($code,1);
			
			if($this->__nif($name,$nif,$required,$options)){
				$this->setValid($name,$code);
				
				return true;
			}
		}
		
		if(!$required) return true;
		
		return "NIE_INVALID";
	}
	
	/**
	 * method:	__boolean
	 *
	 * Validate a boolean value that is correctly representing a boolean value (and not a string such as "1998")
	 *
	 * parameters:
	 * 	name		-	The name of the field
	 * 	value		-	The value of the field
	 * 	required	-	Boolean true or false whether the field is mandatory or not
	 * 	options		-	Validation restrictions
	 *
	 * returns:
	 * 	If failed, will return a string "BOOLEAN_INVALID"
	 * 	If successful, will set the validData array and return true
	 *
	 * operations:
	 * 	-	if string, lower case it
	 * 	-	if numeric, cast to bool
	 * 	-	test value against some known values that normally mean "true", return true or false, depends on whether it successfully matches or not
	 * 	-	if value was not required to succeed validation, just return true
	 * 	-	if value was boolean then if the test was not delegated assign the value to the validData array and return true
	 * 	-	if value was not boolean, return VALIDATOR_BOOLEAN_INVALID
	 *
	 * carlos seez:
	 * -	Please find your boolean validation method replaced by something else that has little resemblance to the previous description, but that actually works, even if it's a bit eager to flag something as false.
	 * 		chris reply: thanks for fixing it! I didnt realise it would do that
	 * 		chris seez: I changed it again, it seems that the version was returning invalid when it found boolean false :)
	 */
	protected function __boolean($name,$value,$required,$options)
	{
		if(is_string($value)){
			$valid = array("1","0","true","false","on","off","yes","no");

			$value = strtolower($value);

			if(!in_array($value,$valid)) return "BOOLEAN_INVALID";

			$bool = $value === "1" || $value === "true" || $value === "on" || $value === "yes";
		}else{
			if($value != 0 && $value != 1) return "BOOLEAN_INVALID";

			$bool = (bool)$value;
		}

		$this->setValid($name,$bool);

		if(!$required) return true;

		return true;
	}

	/**
	 * method:	__number
	 *
	 * Validate a numerical value
	 *
	 * parameters:
	 * 	name		-	The name of the field
	 * 	value		-	The value of the field
	 * 	required	-	Boolean true or false, whether the field is mandatory or not
	 * 	options		-	Validation restrictions: minvalue, maxvalue
	 *
	 * returns:
	 * 	If failed to validate because value is NULL, will return string "NUMBER_IS_NULL"
	 * 	If failed to validate because value is NaN (like "abcdef"), will return string "NUMBER_IS_NAN"
	 * 	If failed to validate because value is below minimum value will return string "NUMBER_IS_BELOW_MINIMUM"
	 * 	If failed to validate because value is above maximum value will return string "NUMBER_IS_ABOVE_MAXIMUM"
	 * 	If successful, will set the validData array and return true
	 *
	 * operations:
	 * 	-	use is_numeric to test whether value is a number or not
	 * 	-	if required is true, but is_numeric returns false, error occurred
	 * 	-	If value is NaN, return NAN error
	 *	-	If value is null, return NULL error
	 * 	-	If value is above or below value restrictions, return a "below" or "above" error
	 * 	-	else set the validData array and return true
	 *
	 */
	protected function __number($name,$value,$required,$options)
	{
		$error = false;
		
		if($error == false && $value == NULL && !isset($options["ignorenull"])){
			$error = "NUMBER_IS_NULL";
		}
		
		if($error == false && !is_numeric($value)){
			$error = "NUMBER_NAN";
		}
		
		if($error == false && isset($options["minvalue"]) && $value < $options["minvalue"]){
			$error = "NUMBER_IS_BELOW_MINIMUM";
		}
		
		if($error == false && isset($options["maxvalue"]) && $value > $options["maxvalue"]){
			$error = "NUMBER_IS_ABOVE_MAXIMUM";
		}
		
		if($error == false && isset($options["limit-input"]) && !in_array($value,$options["limit-input"])){
			$error = "NUMBER_CANNOT_MATCH_AGAINST_LIMIT";
		}
		
		//	If there was an error
		if($error !== false)
		{
			//	If you require the number to be valid, return the error
			//	If you don't require a valid number, just return true
			return ($required) ? $error : true;
		}
		
		$this->setValid($name,$value);
		
		return true;
	}

	/**
	 * method:	__email
	 *
	 * Validate a string against an email template, if the value matches the pattern, will validate successfully
	 *
	 * parameters:
	 * 	name		-	The name of the field
	 * 	value		-	The value of the field
	 * 	required	-	Boolean true or false, whether the field is mandatory or not
	 * 	options		-	Validation restrictions
	 *
	 * returns:
	 * 	If failed because string was empty, will return "EMAIL_EMPTY"
	 * 	If failed because pattern did not match, will return "EMAIL_INVALID"
	 * 	If successful, will set validData and return true
	 *
	 * operations:
	 * 	-	Test email string length, if zero and required is true, return "EMAIL_EMPTY"
	 * 	-	Trim the resulting non-empty string from whitespace before and after
	 * 	-	Match the string against the pattern which describes a valid email (perhaps not ALL possible valid emails, but 99% at least)
	 * 	-	If matches, set the validData and return true
	 * 	-	If failed, but required is NOT true, return true anyway
	 * 	-	If required, but failed all tests, return "EMAIL_INVALID"
	 */
	protected function __email($name,$value,$required,$options)
	{
		$error	=	false;
		$value	=	trim($value);

		if(strlen($value) == 0 && $required == true) $error = "EMAIL_EMPTY";
		
		if(isset($options["invalid"]) && in_array($value,$options["invalid"])) $error = "EMAIL_INVALID_INPUT";

		//	LOL REGEXP
		$pattern = "^[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+(\.[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,})$";
		if(!eregi($pattern,$value)) $error = "EMAIL_INVALID";
		
		if($required == true && $error !== false) return $error;
		
		if($error === false) $this->setValid($name,$value);
		
		return true;
	}

	/**
	 * method:	__domain
	 *
	 * Validate a domain name by performing a DNS lookup on the given information
	 *
	 * parameters:
	 * 	name		-	The name of the field
	 * 	value		-	The value of the field (domain name)
	 * 	required	-	Boolean true or false, whether the field is mandatory or not
	 * 	options		-	Validation restrictions
	 *
	 * returns:
	 * 	If domain name was an empty string, will return "DOMAIN_EMPTY"
	 * 	If domain was invalid (or no response) will return "DOMAIN_INVALID"
	 * 	If successful, will set validData and return true
	 *
	 * notes:
	 * 	-	It is possible that this method will fail with VALID domains, because they do not respond in time
	 * 	-	This method required a DNS lookup, which might be expensive if performed lots of times.
	 */
	protected function __domain($name,$value,$required,$options)
	{
		$value = trim($value);

		if(strlen($value) == 0 && $required == true) return "DOMAIN_EMPTY";

		$record = dns_get_record($value);

		if($record){
			$this->setValid($name,$value);
			return true;
		}

		if($required == false) return true;

		return "DOMAIN_INVALID";
	}

	/**
	 * method:	__phone
	 *
	 * Validate a phone number
	 *
	 * parameters:
	 * 	name		-	The name of the field
	 * 	value		-	The value of the field
	 * 	required	-	Boolean true or false, whether the field is mandatory or not
	 * 	options		-	Validation restrictions: minlength
	 *
	 * returns:
	 * 	If failed because string was empty, but required, return "PHONE_EMPTY"
	 * 	If failed because string was less than minimum length, return "PHONE_LENGTH_INVALID"
	 * 	If failed because it did not match pattern required, return "PHONE_INVALID"
	 *
	 * operations:
	 * 	-	Test email string length, if zero and required is true, return "PHONE_EMPTY"
	 * 	-	remove all typical non-numeric characters from phone numbers (like brackets, +, -, " " and periods and commas)
	 * 	-	if the length of the string is less than the minimum, return "PHONE_LENGTH_INVALID"
	 * 	-	Remove all numerical characters with nothing (removing all numerical characters means we can see how many NON numerical characters there are)
	 * 	-	trim the result
	 * 	-	If the string length is NOT zero, it means that alphabetical characters are found, like abcddef, etc, this is invalid, we should only have numerical characters in the string
	 * 	-	if the string length was zero, we contained a minimum length string, that only contained numerical characters, this is valid, set the validData and return true
	 */
	protected function __phone($name,$value,$required,$options)
	{
		if(strlen($value) == 0 && $required == true) return "PHONE_EMPTY";

		$temp = str_replace("(","",$value);
		$temp = str_replace(")","",$temp);
		$temp = str_replace("+","",$temp);
		$temp = str_replace("-","",$temp);
		$temp = str_replace(" ","",$temp);
		$temp = str_replace(".","",$temp);
		$temp = str_replace(",","",$temp);

		if(isset($options["minlength"])){
			if(strlen($temp) < $options["minlength"]) return "PHONE_LENGTH_INVALID";
		}

		$temp = preg_replace("/\d/","",$temp);
		$temp = trim($temp);

		if(strlen($temp)) return "PHONE_INVALID"; 

		$this->setValid($name,$value);
		return true;
	}

	/**
	 * method:	__date
	 *
	 * Very simple method to convert a data to a UNIX timestamp using strtotime
	 *
	 * parameters:
	 * 	name		-	The name of the field
	 * 	value		-	The value of the field
	 * 	required	-	Boolean true or false, whether the field is mandatory or not
	 * 	options		-	Validation restrictions
	 *
	 * returns:
	 * 	Boolean true is required AND success were both true
	 * 	Boolean true is required was false
	 * 	Boolean false if one of those conditions is not supported
	 */
	protected function __date($name,$value,$required,$options)
	{
		$success = strtotime($value);

		if($required && $success || !$required){
			$this->setValid($name,$value);
			return true;
		}

		return false;
	}

	/**
	 * method:	__file
	 *
	 * Validates the presence of a file in the uploaded data
	 * (this only works in PHP by extracting the info from the $_FILES superglobal)
	 *
	 * parameters:
	 * 	name		-	The name of the field
	 * 	value		-	The value of the field
	 * 	required	-	Boolean true or false, whether the field is mandatory or not
	 * 	options		-	Validation restrictions
	 *
	 * returns:
	 * 	If failed because file was too large for the PHP configuration, return "FILE_EXCEED_INI_SIZE"
	 * 	If failed because file exceeded the HTML designated form size, return "FILE_EXCEED_FORM_SIZE"
	 * 	If failed because file was only partially uploaded, return "FILE_PARTIAL_FILE"
	 * 	If failed because file was missing or could not be located, return "FILE_MISSING_FILE"
	 * 	If failed because there was no tmp directory to upload (perhaps it was invalid) return "FILE_NO_TMP_DIRECTORY"
	 * 	If failed because tmp directory cannot be written to, return "FILE_CANNOT_WRITE"
	 * 	If failed because file extension was banned (disallowed, such as .exe), return "FILE_BANNED_EXTENSION"
	 * 	If failed because file could not be found in the $_FILES array, return "FILE_NOT_FOUND"
	 *
	 * operations:
	 * 	-	Test the value was NULL or not (NULL means file didnt upload, or not uploaded)
	 * 	-	If the file was NOT NULL, test further
	 * 	-	Test the file exists on disk
	 * 	-	If the file exists, set the validData and return true
	 * 	-	If the file doesnt exist, but the file is not required, just return true anyway
	 * 	-	If the file was required, but not found, interrogate the error to find the correct error message to return
	 * 	-	if none of those errors matched the situation, then look to see whether the value was required
	 * 	-	If an error could not be found, but the file cannot be found either BUT the file is not required,
	 * 		just put a REQUEST_FILE_NOT_FOUND message and return true (validated ok, but couldnt find anything
	 * 		when it was expected, it's not an error, it's just a warning)
	 * 	-	If none of these match, then return "FILE_NOT_FOUND"
	 */
	protected function __file($name,$value,$required,$options)
	{
		$value = Amslib::filesParam($name);

		if($value !== NULL){
			if(strlen($value["tmp_name"]) && is_file($value["tmp_name"])){
				$this->setValid($name,$value);
				return true;
			}
		}

		if($required == false) return true;
		
		//	Return some alternative errors to FILE_NOT_FOUND
		if($value["error"] == UPLOAD_ERR_INI_SIZE)		return "FILE_EXCEED_INI_SIZE";
		if($value["error"] == UPLOAD_ERR_FORM_SIZE)		return "FILE_EXCEED_FORM_SIZE";
		if($value["error"] == UPLOAD_ERR_PARTIAL)		return "FILE_PARTIAL_FILE";
		if($value["error"] == UPLOAD_ERR_NO_FILE)		return "FILE_MISSING_FILE";
		if($value["error"] == UPLOAD_ERR_NO_TMP_DIR)	return "FILE_NO_TMP_DIRECTORY";
		if($value["error"] == UPLOAD_ERR_CANT_WRITE)	return "FILE_CANNOT_WRITE";
		if($value["error"] == UPLOAD_ERR_EXTENSION)		return "FILE_BANNED_EXTENSION";

		//	Unknown error, just comment it here so I can't lose the info: "FILE_REQUEST_FILE_NOT_FOUND"
		return "FILE_NOT_FOUND";
	}
	
	protected function __file_exists($name,$value,$required,$options)
	{
		if($options["absolute"] == true) $value = Amslib_Filesystem::absolute($value);
		
		if(is_file($value)){
			$this->setValid("{$options["key"]}$name",$value);
			return true;
		}
		
		if($required == false) return true;
		
		return "FILE_EXISTS_FAILED";
	}
	
	/**
	 * For description: read the member variables related to this method, they explain all
	 */
	protected function __setRequiredRules($state)
	{
		$this->__hasRequiredRules = $state;
	}

	protected function __checkRequiredRules()
	{
		$areRequiredRules = false;
		foreach($this->__items as $item){
			if($item["required"] == true) $areRequiredRules = true;
		}

		$this->__hasRequiredRules = $areRequiredRules;
	}

	/**
	 * method:	__construct
	 *
	 * Constructs an object that is responsible for validating the information passed through the the parameter $source
	 *
	 * parameters:
	 * 	$source	-	The source data that will  be validated
	 */
	public function __construct($source)
	{
		$this->__items				=	array();
		$this->__error				=	array();
		$this->__source				=	$source;
		$this->__hasExecuted		=	false;

		$this->__setRequiredRules(false);

		$this->register("text",				array($this,"__text"));
		$this->register("alpha",			array($this,"__alpha"));
		$this->register("alpha_relaxed",	array($this,"__alpha_relaxed"));
		$this->register("password",			array($this,"__password"));
		$this->register("boolean",			array($this,"__boolean"));
		$this->register("number",			array($this,"__number"));
		$this->register("email",			array($this,"__email"));
		$this->register("domain",			array($this,"__domain"));
		$this->register("phone",			array($this,"__phone"));
		$this->register("date",				array($this,"__date"));
		$this->register("file",				array($this,"__file"));
		$this->register("file_exists",		array($this,"__file_exists"));
		$this->register("array",			array($this,"__array"));
		$this->register("isbn",				array($this,"__isbn"));

		//	Methods to validate spanish national identification document (dni)
		$this->register("dni",				array($this,"__dni"));
		$this->register("cif",				array($this,"__cif"));
		$this->register("nif",				array($this,"__nif"));
		$this->register("nie",				array($this,"__nie"));
		
		//	Register some popular alternative spellings which keep cropping up to make life easier
		$this->register("string",			array($this,"__text"));
		$this->register("numeric",			array($this,"__number"));
		$this->register("alpha-relaxed",	array($this,"__alpha_relaxed"));

		//	Custom validation methods which do things we all want, but don't 
		//	conform to obvious rules like "number", "text", "date", etc
		$this->register("require_one",		array($this,"__require_one"));
	}
	
	public function setValid($name,$value)
	{
		$this->__validData[$name] = $value;
	}

	/**
	 * method:	add
	 *
	 * add a validation to happen on a particular field
	 *
	 * parameters:
	 * 	name		-	The name of the field to validate
	 * 	type		-	The type of the field, methodology to use when validating
	 * 	required	-	Boolean true or false, whether the field is mandatory or not
	 * 	options		-	Other validation restrictions
	 *
	 * notes:
	 * 	-	An idea to expand on the flexibility here is to allow a field to be validated multiple times by different types
	 * 		would allow you to layer validations instead of forcing the construction of customised validators which just
	 * 		do two or three simple validation types together, this would require changing the structures a little bit because
	 * 		right now, the field name is used as the key to the type of validator, we'd have to change that so each key can have
	 * 		multiple types assigned to it
	 * -	I think to allow an item to be validated multiple times, we could just move to a system where
	 * 		we don't use the $name parameter as the key to the __items array, we simply add a new __items[] element
	 * 		and then loop through them identically, we could move the $name parameter into the array assigned
	 * 		as the same "name" array index and it should work identically
	 * -	I think we need to stop assigning validData items to a blank string by default, because if an item
	 * 		doesn't validate, it shouldn't appear in the array, but having a string here is beneficial because
	 * 		it means you can just print the element and it'll print a blank string if it failed to validate.
	 * 		this SOUNDS a good idea, until you start to think of situations where knowing it failed to validate
	 * 		by merely looking for it and seeing it's not present is actually useful too.
	 * 		The problem here seems to be a lack of direction, we haven't decided yet which is the best course of action
	 */
	public function add($name,$type,$required=false,$options=array())
	{
		$required					=	isset($options["required"]) ? $options["required"] : $required;
		$options["__vobject"]		=	$this;
		$this->__items[$name]		=	array("name"=>$name,"type"=>$type,"required"=>$required,"options"=>$options);
		$this->__validData[$name]	=	"";

		if($required === true) $this->__setRequiredRules(true);
	}

	/**
	 * method: remove
	 *
	 * Remove a type from validation, perhaps it's part of a post-filter on the type settings for the validator
	 *
	 * parameters:
	 * 	name	-	The name of the type to remove from validation
	 */
	public function remove($name)
	{
		unset($this->__items[$name],$this->__validData[$name]);

		$this->__checkRequiredRules();
	}

	/**
	 * method: register
	 *
	 * Register a validation type, this allows the importation of external validator methods to act like
	 * native ones
	 *
	 * parameters:
	 * 	$name		-	The name of the validation method to implment (you can override built in types here)
	 * 	$callback	-	The name of the callback, can be array of two items if callback is from a class (see: call_user_func)
	 */
	public function register($name,$callback)
	{
		$this->__types[$name] = $callback;
	}

	/**
	 * method:	execute
	 *
	 * Execute the validator according to the rules setup
	 *
	 * returns:
	 * 	If there are no items in the source array, return true or false, depending on whether there are required rules or not
	 * 	Return whether the result from calling Amslib_Validator3::getStatus (true or false, true for no errors)
	 *
	 * operations:
	 * 	-	Reset the hasExecuted flag to false
	 * 	-	Count the number of items in the source array, if zero, return true (hasExecuted will remain false)
	 * 	-	For each item in the rules array (see notes) validate it
	 * 	-	Set the hasExecuted flag to true
	 * 	-	obtain the name of the callback to execute
	 * 	-	Find the required flag and the minlength from the validator structure
	 * 	-	if the validator type is "file" obtain the file from the $_FILES superglobal and assign it as the value
	 * 	-	Otherwise, obtain the value from the source array
	 * 	-	Call the callback and obtain the success code
	 * 	-	If failed, then set data into the error array
	 * 	-	Return the status of the validator (true = no errors)
	 *
	 * notes:
	 * 	-	Rename Amslib_Validator3::__items to Amslib_Validator3::__rules or something more descriptive
	 */
	public function execute()
	{
		$this->__hasExecuted = false;

		if(count($this->__source) == 0) return !$this->__hasRequiredRules;

		foreach($this->__items as $name=>$rule){
			$this->__hasExecuted = true;

			$value = (isset($this->__source[$name])) ? $this->__source[$name] : NULL;

			if(is_string($value)) $value = trim($value);

			$status = call_user_func(
							$this->__types[$rule["type"]],
							$name,
							$value,
							$rule["required"],
							$rule["options"]
						);

			if($status !== true){
				$this->setError($name,$value,$status);
			}
		}

		return $this->getStatus();
	}

	/**
	 * method:	setError
	 *
	 * Register an error against the field being validated
	 *
	 */
	public function setError($name, $value, $error)
	{
		$this->__error[$name] = array("value"=>$value,"code"=>"VALIDATOR_{$error}");
	}

	/**
	 * method:	getErrors
	 *
	 * Get the array of errors which occurred when performing the validation on the data
	 *
	 * returns:
	 * 	An array of data which represents all the errors from the validator
	 */
	public function getErrors($field=NULL)
	{
		return ($field && isset($this->__error[$field])) 
					? $this->__error[$field] 
					: $this->__error;
	}

	/**
	 * method:	getSuccess
	 *
	 * Tests the success status of a single field
	 *
	 * parameters:
	 * 	field	-	The name of the field to test
	 *
	 * returns:
	 * 	Boolean true or false, depending on whether the field was found in the error array or not
	 */
	public function getSuccess($field)
	{
		return isset($this->__error[$field]) ? false : true;
	}

	/**
	 * method:	getValid
	 *
	 * Obtains the valid data array, which is an associative array of data that was deemed valid when performing
	 * all the tests, it's usually used when refilling the form with information to prevent the user from having
	 * to refill it manually by themselves
	 *
	 * parameters:
	 * 	mergeSource	-	A source array that could contain a base level of data which to override with data from the validator
	 *
	 * returns:
	 * 	An array of valid data obtained when performing the validation, will merge if requested with another array by passing it as a paramater
	 */
	public function getValid($mergeSource=NULL)
	{
		$validData = is_array($this->__validData) ? $this->__validData : array();
		return ($mergeSource) ? array_merge($mergeSource, $validData) : $validData;
	}
	
	/**
	 * method:	getStatus
	 *
	 * Returns whether the validator has found any errors or not
	 *
	 * returns:
	 * 	Boolean true or false, depending on whether the validator failed any fields or not
	 */
	public function getStatus()
	{
		return (count($this->__error)) ? false : true;
	}

	/**
	 * method:	itemCount
	 *
	 * Return the count of items that has been setup for testing, this is useful to know
	 * whether rules have been associated with the validator or  not
	 *
	 * returns:
	 * 	The number of items that are being validated
	 */
	public function itemCount()
	{
		return count($this->__items);
	}

	/**
	 * method:	hasExecuted
	 *
	 * Whether or not the validation has executed or not (useful to know as a shortcut to
	 * see whether the data contained on the state of the validation is valid or not)
	 *
	 * returns:
	 * 	Boolean true or false, depending on whether the validation has executed or not
	 */
	public function hasExecuted()
	{
		return $this->__hasExecuted;
	}
	
	/** DEPRECATED METHOD: use getErrors instead */
	public function getErrorsByFieldName(){ return $this->getErrors(); }
	/**	DEPRECATED METHOD: use getValid instead */
	public function getValidData($mergeSource=NULL){ return $this->getValid($mergeSource); }
}
?>