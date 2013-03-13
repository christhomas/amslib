$.fn.dataTableExt.oPagination.four_button = {
    "fnInit": function ( oSettings, nPaging, fnCallbackDraw )
    {
    	$this = $.fn.dataTableExt.oPagination.four_button;
    	var oLang = oSettings.oLanguage.oPaginate;
		var oClasses = oSettings.oClasses;
        
        $(nPaging).append(
			'<a  tabindex="'+oSettings.iTabIndex+'" class="'+oClasses.sPageButton+" "+oClasses.sPageFirst+'">'+oLang.sFirst+'</a>'+
			'<a  tabindex="'+oSettings.iTabIndex+'" class="'+oClasses.sPageButton+" "+oClasses.sPagePrevious+'">'+oLang.sPrevious+'</a>'+
			'<span></span>'+
			'<a tabindex="'+oSettings.iTabIndex+'" class="'+oClasses.sPageButton+" "+oClasses.sPageNext+'">'+oLang.sNext+'</a>'+
			'<a tabindex="'+oSettings.iTabIndex+'" class="'+oClasses.sPageButton+" "+oClasses.sPageLast+'">'+oLang.sLast+'</a>'
		).addClass("paging_full_numbers");
        
        var els = $('a', nPaging);
		
        var nFirst	= els[0],
			nPrev	= els[1],
			nNext	= els[2],
			nLast	= els[3];
        
        var fnClickHandler = function ( e ) {
        	oSettings.button_action = e.data.action;
        	
			fnCallbackDraw( oSettings );
		};
        
        oSettings.oApi._fnBindAction( nFirst, {action: "first"},    fnClickHandler );
		oSettings.oApi._fnBindAction( nPrev,  {action: "previous"}, fnClickHandler );
		oSettings.oApi._fnBindAction( nNext,  {action: "next"},     fnClickHandler );
		oSettings.oApi._fnBindAction( nLast,  {action: "last"},     fnClickHandler );
          
        /* Disallow text selection */
        $(nFirst).bind( 'selectstart', function () { return false; } );
        $(nPrev).bind( 'selectstart', function () { return false; } );
        $(nNext).bind( 'selectstart', function () { return false; } );
        $(nLast).bind( 'selectstart', function () { return false; } );
    },
     
    "fnUpdate": function ( oSettings, fnCallbackDraw )
    {
    	$this = $.fn.dataTableExt.oPagination.four_button;
    	
        if ( !oSettings.aanFeatures.p )
        {
            return;
        }
          
        /* Loop over each instance of the pager */
        var an = oSettings.aanFeatures.p;
        for ( var i=0, iLen=an.length ; i<iLen ; i++ )
        {
        	// dataTables_paginate fg-buttonset ui-buttonset fg-buttonset-multi ui-buttonset-multi paging_full_numbers
        	// dataTables_paginate fg-buttonset ui-buttonset fg-buttonset-multi ui-buttonset-multi paging_four_button ui-button
        	
            /*var buttons = an[i].getElementsByTagName('span');
            if ( oSettings._iDisplayStart === 0 )
            {
                buttons[0].className = "paginate_disabled_previous";
                buttons[1].className = "paginate_disabled_previous";
            }
            else
            {
                buttons[0].className = "paginate_enabled_previous";
                buttons[1].className = "paginate_enabled_previous";
            }
              
            if ( oSettings.fnDisplayEnd() == oSettings.fnRecordsDisplay() )
            {
                buttons[2].className = "paginate_disabled_next";
                buttons[3].className = "paginate_disabled_next";
            }
            else
            {
                buttons[2].className = "paginate_enabled_next";
                buttons[3].className = "paginate_enabled_next";
            }*/
        }
    }
};