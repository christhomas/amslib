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
 * 	class:	Amslib_Paypal
 *
 *	group:	Core
 *
 *	file:	Amslib_Paypal.php
 *
 *	description: todo, write description
 *
 * 	todo: write documentation
 * 	UNKNOWN PARAMETERS FROM NVP EXAMPLE
 * 
 * 	$nvpstr =
 * 		MAXAMT=$maxamt&
 * 		AMT=(string)$am"&
 * 		ITEMAMT=$itemamt&
 * 		CALLBACKTIMEOUT=4&
 * 		CALLBACK=https://d-sjn-00513807/callback.pl&
 */
class Amslib_Paypal
{
	/**
	 * $url:	URL to call when executing operation
	 */
	protected $url;
	
	/**
	 * $nvpURL: the url for paypal login
	 */
	protected $nvpURL;
	
	/**
	 * $paypalURL: the normal url to post other operation to
	 */
	protected $paypalURL;
	
	/**
	 * $authenticationMode: 3token(Username, Password and Signature)
	 */
	protected $authenticationMode = "3TOKEN";
	
	/**
	 * params: the parameters that the remote call will need to process the request
	 * 
	 * note:
	 * 	-	we keep this separate from products because we will need to update the products array dynamically
	 */
	protected $params;
	
	/**
	 * $products: The array of products that are involved in this transaction
	 */
	protected $products;
	
	/**
	 * $queryString: The final string to send to the url
	 */
	protected $queryString;
	
	protected $response;
	
	protected $debug;
	
	/**
	 * method: calculateProductTotal
	 * 
	 * Calculate the total price for the purchase, from the amounts specified in the products array
	 * 
	 * notes:
	 * 	-	we need to add the tax amount also?
	 * 	-	we need to add the shipping amount also?
	 *	-	Should we have a way to compare the calculated value against the value passed through setAmountTotal ?
	 */
	protected function calculateProductTotal()
	{
		$amount = (float)0;
		
		foreach($this->products as $num=>$p)
		{
			$amount += $p["L_AMT{$num}"]*$p["L_QTY{$num}"];
		}
		
		//	Add tax amount?
		//	Add shipping costs?
		$this->setAmountTotal($amount);
	}
	
	protected function checkRequiredFields()
	{
		switch($this->command){
			case "SetExpressCheckout":{
				if($this->get("amt") == false){
					$this->calculateProductTotal();
				}
				
				if($this->get("paymentaction") == false){
					$this->setPaymentAction("sale");	
				}
				
				if($this->get("noshipping") == false){
					$this->set("noshipping",1);
				}
			}break;
		}
	}
	
	protected function collapseParameters()
	{
		//	Collapse all the parameters set manually
		$this->queryString = http_build_query($this->params);
		
		//	Collapse the products into the same query string
		foreach($this->products as $p){
			$this->queryString .= "&".http_build_query($p);
		}
	}	
	
	protected function decodeResponse($response)
	{
		if($response == false) return false;
		
		if(is_string($response)){
			if($this->debug) print("DEBUGGING: ".var_dump($response));
			
			$parameters = explode("&",$response);
			
			$this->response = array();
			foreach($parameters as $p){
				list($key,$value) = explode("=",$p);
				
				$this->response[urldecode($key)] = urldecode($value);
			}
			return ($this->getResponse("ACK") == "Success") ? true : false;
		}
	}
		
	public function __construct()
	{
		$this->initialise();
		
		$this->setDebug(false);
	}
	
	public function setDebug($state)
	{
		$this->debug = $state;
	}
	
	public function initialise()
	{
		$this->params		=	array();
		$this->products		=	array();
	}
	
	public function setAPIDetails($username,$password,$signature)
	{
		$this->set("user",		urlencode($username));
		$this->set("pwd",		urlencode($password));
		$this->set("signature",	urlencode($signature));
	}
	
	public function enableSandbox()
	{
		$this->nvpURL		=	"https://api-3t.sandbox.paypal.com/nvp";
		$this->paypalURL	=	"https://www.sandbox.paypal.com/webscr";
	}
	
	public function enableLive()
	{
		$this->nvpURL		=	"https://api-3t.paypal.com/nvp";
		$this->paypalURL	=	"https://www.paypal.com/webscr";
	}
	
	public function setExpressCheckout($redirect=false)
	{
		if(!$redirect){
			$this->url		=	$this->nvpURL;
			$this->command	=	"SetExpressCheckout";
			$this->set("method",urlencode($this->command));
		}else{
			$this->initialise();
			$this->url	=	$this->paypalURL;
			$this->set("cmd","_express-checkout");
			$this->set("token",$this->getResponse("TOKEN"));
		}
	}
	
	public function getExpressCheckoutDetails($token=false)
	{
		$this->url		=	$this->nvpURL;
		$this->command	=	"GetExpressCheckoutDetails";
		$this->set("method",urlencode($this->command));
		$this->setToken($token);		
	}
	
	public function doExpressCheckout($token=false,$payerId=false)
	{
		$this->url		=	$this->nvpURL;
		$this->command	=	"DoExpressCheckoutPayment";
		$this->set("method",urlencode($this->command));
		$this->setToken($token);
		$this->setPayerId($payerId);
		$this->setCurrencyCode($this->getResponse("CURRENCYCODE"));
		$this->setIPAddress($_SERVER['SERVER_NAME']);
		$this->setAmountTotal($this->getResponse("AMT"));
		$this->setPaymentAction("sale");
	}
	
	public function set($name,$value)
	{
		$this->params[$name] = $value;
	}
	
	public function get($name)
	{
		return (isset($this->params[$name])) ? $this->params[$name] : false;
	}
	
	public function setIPAddress($address)
	{
		$this->set("ipaddress",urlencode($address));
	}
	
	public function setToken($token=false)
	{
		if($token == false) $token = $this->getResponse("TOKEN");
		if($token == false) return false;

		$this->set("token",urlencode($token));
		
		return true;
	}
	
	public function setPayerId($payerId=false)
	{
		if($payerId == false) $payerId = $this->getResponse("PAYERID");
		if($payerId == false) return false;
		
		$this->set("payerid",urlencode($payerId));
		
		return true;
	}
	
	public function addProduct($name,$description,$productId,$quantity,$price)
	{
		$num = count($this->products);
		
		$quantity	=	(float)$quantity;
		$price		=	(float)$price;
		
		$productData = array(
			"L_NAME{$num}"		=>	$name,
			"L_DESC{$num}"		=>	$description,
			"L_NUMBER{$num}"	=>	$productId,
			"L_QTY{$num}"		=>	$quantity,
			"L_AMT{$num}"		=>	$price,
		);
		
		$this->products[$num] = $productData;
		
		return $num;
	}
	
	public function setShippingDetails($personName,$street,$city,$state,$countryCode,$postcode)
	{
		$this->set("addroverride",		1);
		$this->set("shiptoname",		$personName);
		$this->set("shiptostreet",		$street);
		$this->set("shiptocity",		$city);
		$this->set("shiptocountrycode",	$countryCode);
		$this->set("shiptozip",			$postcode);
		
		//	This can be optional in many cases, so test for it being empty
		if(strlen($state)){
			$this->set("shiptostate",	$state);
		}
	}
	
	public function setShippingAmount($shippingAmount,$shippingDiscount)
	{
		$this->set("shippingamt",$shippingAmount);
		$this->set("shipdiscamt",$shippingDiscount);
	}
	
	public function setPhoneNumber($number)
	{
		$this->set("shiptophonenum",$number);
	}
	
	public function setEmail($email)
	{
		$this->set("email",$email);
	}
	
	/**
	 * method: setProductWeight
	 * 
	 * set the products weight so you can calculate the shipping
	 * 
	 * parameters:
	 * 	$pid	-	The item number in the product array (was returned by the method addProduct)
	 * 	$unit	-	The unit of weight you are using
	 * 	$weight	-	The weight of the product, using the units provided
	 * 
	 * notes:
	 * 	-	the item about calculating the shipping is that you provide us with enough 
	 * 		information so that this is actually possible
	 */
	public function setProductWeight($pid,$unit,$weight)
	{
		if(isset($this->products[$pid])){
			$this->products[$pid]["L_ITEMWEIGHTUNIT{$pid}"]		=	$unit;
			$this->products[$pid]["L_ITEMWEIGHTVALUE{$pid}"]	=	$weight;
		}
	}
	
	/**
	 * method: setProductShipping
	 * 
	 * Set a shipping detail against a particular product
	 * 
	 * parameters:
	 * 	$pid	-	The id of the product (as returned by addProduct)
	 * 	$label	-	The label of the shipping type to use
	 * 	$name	-	The name of the shipping type
	 * 	$amount	-	The amount the shipping will cost
	 * 
	 * notes:
	 * 	-	I am not sure how to validate these parameters to make sure they are valid, or whether
	 * 		there are invalid values to pass, so be careful how to use this method
	 */
	public function setProductShipping($pid,$label,$name,$amount)
	{
		if(isset($this->products[$pid])){
			$this->products[$pid]["L_SHIPPINGOPTIONlABEL{$pid}"]		=	$label;
			$this->products[$pid]["L_SHIPPINGOPTIONNAME{$pid}"]			=	$name;
			$this->products[$pid]["L_SHIPPINGOPTIONISDEFAULT{$pid}"]	=	true;
			$this->products[$pid]["L_SHIPPINGOPTIONAMOUNT{$pid}"]		=	$amount;
		}
	}
	
	public function setPaymentAction($action)
	{
		$allowed = array("sale","offer","authorization");
		
		if(in_array($action,$allowed)){
			$this->set("paymentaction",$action);
		}
	}
	
	public function setLocaleCode($code)
	{
		$allowed = array("AU","DE","FR","GB","IT","ES","JP","US");
		
		if(in_array($code,$allowed)){
			$this->set("locale",$code);
		}
	}
	
	public function setCurrencyCode($currencyCode)
	{
		$allowed = array(
			"AUD","CAD","CHF",
			"CZK","DKK","EUR",
			"GBP","HKD","HUF",
			"ILS","JPY","MXN",
			"NOK","NZD","PLN",
			"SEK","SGD","USD"
		);
		
		if(in_array($currencyCode,$allowed)){
			$this->set("currencycode",$currencyCode);
		}
	}
	
	public function setAPIVersion($version)
	{
		$this->set("version",urlencode($version));
	}
	
	public function setAmountTotal($amount)
	{
		if(!$amount || !is_numeric($amount)) return false;
		
		$this->set("amt",$amount);
	}
	
	public function setTaxAmount($amount)
	{
		if(!$amount || !is_numeric($amount)) return false;
		
		$this->set("taxamt",$amount);
	}
	
	public function setReturnURL($url)
	{
		$this->set("returnurl",$url);
	}
	
	public function setCancelURL($url)
	{
		$this->set("cancelurl",$url);
	}
	
	public function setInsurance($amount)
	{
		if(!$amount || !is_numeric($amount)) return false;
		
		$this->set("insuranceoptionoffered",true);
		$this->set("insuranceamt",$amount);
	}
	
	public function execute()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$this->url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1);
		
		$this->checkRequiredFields();
		$this->collapseParameters();
		
		if($this->debug) print("$this->url?$this->queryString<br/>");
		
		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch,CURLOPT_POSTFIELDS,$this->queryString);
		
		$r = curl_exec($ch);
		
		if (curl_errno($ch)) {
			//	moving to display page to display curl errors
			print("FAILURE INFO: ".curl_errno($ch)."<br/>");
			print("FAILURE INFO: ".curl_error($ch)."<br/>");
			return false;
		}

		//getting response from server
		return $this->decodeResponse($r);
	}
	
	public function getResponse($field=false)
	{
		if($field){
			return (isset($this->response[$field])) ? $this->response[$field] : false;
		}
		
		return $this->response;
	}
	
	public function getError()
	{
		$r = $this->getResponse();
		if(is_string($r)){
			print("<pre>response[string]: ".htmlentities($r)."</pre>");	
		}else{
			print("<pre>response[array]: ".print_r($r,true)."</pre>");
		}
	}
	
	public function redirect($command=false)
	{
		if($command != false) $this->command = $command;

		switch($this->command){
			case "SetExpressCheckout":{
				$this->setExpressCheckout(true);
				$this->collapseParameters();

				$this->url = "$this->url?$this->queryString";
				header("Location: $this->url");
				die("WAITING TO REDIRECT: '$this->url'");
			}
		}
	}
}