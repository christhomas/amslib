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
 * 	notes:
 * 		-	I found this here: http://jquery-howto.blogspot.com/2009/09/get-url-parameters-values-with-jquery.html
 * 		-	I need to read through and make this a component so I can reuse it in this script AND the google analytics script
 * 		
 * 		$.extend({
 * 		  getUrlVars: function(){
 * 		    var vars = [], hash;
 *		    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
 *		    for(var i = 0; i < hashes.length; i++)
 *		    {
 *		      hash = hashes[i].split('=');
 *		      vars.push(hash[0]);
 *		      vars[hash[0]] = hash[1];
 *		    }
 *		    return vars;
 *		  },
 *		  getUrlVar: function(name){
 *		    return $.getUrlVars()[name];
 *		  }
 *		});
 *		
 */
function getShareThis(callback)
{
	//	Copied from how scriptaculous does it's "query string" thing
	var js = /Amslib_ShareThis\.js(\?.*)?$/;
	$("head script[src]").each(function(){
		var src = this.src.match(js);
		
		if(src) callback(this.src.match(/\?.*publisher=([a-zA-Z0-9\-]*)/));
	});
}

var switchTo5x=true;

$(document).ready(function(){
	getShareThis(function(params){
		$.getScript("http://w.sharethis.com/button/buttons.js",function(){
			stLight.options({publisher:params[1]});
		});
	});
});