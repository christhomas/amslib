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
 * file: dni.php
 * Title: DNI Validator 
 * Version: 2.0
 * Project: amslib
 * 
 * Notes:
 * 	-	This is actually a validator for DNI codes, so shouldnt this be dni-validator.php and not dni.php
 *
 * Contributors:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

/**
 * class:	DNI
 * 
 * Validates a NIF/CIF/NIE using the related algorithms
 */
class DNI{
	/**
	 * function: validate
	 * 
	 * A global method which identifies which DNI has been passed and calls a subfunction to deal 
	 * with the specifics
	 * 
	 * parameters:
	 * 	dni	-	The DNI to check
	 * 
	 * returns:
	 * 0	-	pattern was not valid
	 *	-1	-	invalid NIF
	 *	1	-	valid NIF
	 *	-2	-	invalid CIF
	 *	2	-	valid CIF
	 *	-3	-	invalid NIE
	 *	3	-	valid NIE
	 *
	 * notes:
	 * 	-	In the future this file will be moved to dni-validator.php and when this happens, this method will
	 * 		change it's name to execute instead
	 * 
	 * 	-	It should also be possible to create an instance of this object as well, instead of statically calling validate
	 * 			PROBABLY bad, since this entire class is built to function statically
	 */
	function validate($dni)
	{
		$dni = strtoupper($dni);
		
		if(ereg("^[JABCDEFGHI]{1}[0-9]{7}[A-Z0-9]{1}$",$dni))	return DNI::__validateCIF($dni);
		if(ereg("^[TX]{1}[A-Z0-9]{8}[A-Z]?$",$dni))				return DNI::__validateNIE($dni);
		if(ereg("^[0-9]{8}[A-Z]{1}$",$dni))						return DNI::__validateNIF($dni);
			
		return 0;
	}
	
	/**
	 * function: __validateCIF
	 * 
	 * Validate a CIF DNI code
	 * 
	 * parameters:
	 * 	dni	-	The CIF DNI to check
	 * 
	 * returns:
	 * 	-2	-	Invalid CIF
	 * 	2	-	Valid CIF
	 * 
	 * operations:
	 * 	-	grab the last value, this is the checksum value
	 * 	-	sum all the odd numbers together
	 * 	-	double each even number and if above 9, add both digits together like the luhn algoritum [15 -> 6 (1+5)] and add the result to the sum
	 * 	-	obtain the modulus of the resulting sum as the control (obtain the modulus of the that as well (in case of sum = 10)
	 * 	-	If the control equals the last value, or the letter at that index, then return positively, otherwise, failure
	 */
	function __validateCIF($dni)
	{
		$lastLetter = array("J", "A", "B", "C", "D", "E", "F", "G", "H", "I");
	
		$dni = substr($dni,1);
		
		$last = substr($dni,strlen($dni)-1);	
		$sum = 0;
		
		//	Sum up all the even numbers
		for($pos=1;$pos<7;$pos+=2){
			$sum += (int)(substr($dni,$pos,1));
		}
		
		//	Sum up all the odd numbers (but differently)
		//	This uses the Luhn Algorithm: 
		//		Any value greater than 10, comprises two numbers (etc: 15 is [1, 5] )
		//		Add both together, this is the value to sum
		for($pos=0;$pos<8;$pos+=2){
			$val = 2*(int)(substr($dni,$pos,1));
			$val = str_pad($val,2,"0",STR_PAD_LEFT);
			$sum += (int)$val[0]+(int)$val[1];
		}
			
		//	Obtain the modulus of 10 and subtract it from 10, if the sum was 10, control is 0 (second modulus)
		$control = (10 - ($sum % 10)) % 10;
		
		return (($last == $control) || ($last == $lastLetter[$control])) ? 2 : -2;
	}
	
	/**
	 * function: __validateNIE
	 * 
	 * Validate a NIE DNI code
	 * 
	 * parameters:
	 * 	dni	-	The NIE DNI code to check
	 * 
	 * returns:
	 * 	-3	-	Invalid NIE
	 * 	3	-	Valid NIE
	 * 
	 * notes:
	 * 	-	This is a proxy method for validateNIF, which is identical in calculation, except we need to remove the
	 * 		X from the front of the NIE and then check the remaining string (which is a valid NIE, or should be)
	 */
	function __validateNIE($dni)
	{
		$firstCharacter = substr($dni,0,1);
		if($firstCharacter == "X"){
			$dni = substr($dni,1);
			return (DNI::__validateNIF($dni)*3);
		}
		
		return -3;
	}
	
	/**
	 * function: __validateNIF
	 * 
	 * Validate a NIF DNI code
	 * 
	 * parameters:
	 * 	dni	-	The NIF DNI code to check
	 * 
	 * returns:
	 * 	-1	-	Invalid NIE
	 * 	1	-	Valid NIE
	 * 
	 * operations:
	 * 	-	Check the end character is a letter
	 * 	-	obtain the numerical part and modulus against 23
	 * 	-	The result, will be an array index into a list of validation characters
	 * 	-	If the end letter from the dni was the same letter as the validation character then the DNI is valid
	 */
	function __validateNIF($dni)
	{
		$endLetter = substr($dni,strlen($dni)-1); 
		if(!is_numeric($endLetter)){
			$source = "TRWAGMYFPDXBNJZSQVHLCKET";
			
			$dni = substr($dni,0,-1);
			$sum = $dni%23;
	
			$calcLetter = substr($source,$sum,1);
	
			return ($endLetter == $calcLetter) ? 1 : -1;
		}
	
		return -1;
	}
}

?>