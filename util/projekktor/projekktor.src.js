/*
 * this file is part of: 
 * projekktor zwei
 * http://www.projekktor.com
 *
 * Copyright 2010, 2011, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 * under GNU General Public License
 * http://www.projekktor.com/license/
 * ,------------------------------------------,      .    _  .
 * |  Naaah, love shines through !!1          |      |\_|/__/|
 * |------------------------------------------|     / / \/ \  \
 *                                           \     /__|O||O|__ \
 *                                             \  |/_ \_/\_/ _\ |
 *                                                | | (____) | ||
 *                                                \/\___/\__/  //
 *                                                (_/         ||
 *                                                 |          ||
 *                                                 |          ||\
 *                                                  \        //_/
 *                                                   \______//
 *                                                 __ || __||
 *                                                (____(____)
 */
jQuery(function($) {
// apply IE8 html5 fix - thanx to Remy Sharp - http://remysharp.com/2009/01/07/html5-enabling-script/
if ($.browser.msie) {
    (function(){
	if(!/*@cc_on!@*/0) return;
	var e = "div,audio,video,source".split(',');
	for(var i=0;i<e.length;i++){document.createElement(e[i])}}
    )();
    if(!Array.prototype.indexOf){
	Array.prototype.indexOf=function(obj,start){
	    for(var i=(start||0),j=this.length;i<j;i++){
		if(this[i]==obj){return i;}
	    }
	    return -1;
	}
    }
}
    
// container for player instances
var projekktors = [];


// this object is returned when multiple player's are requested 
function Iterator(arr) {
    this.length = arr.length;
    this.each = function(fn) {$.each(arr, fn);};
    this.size = function() {return arr.length;};	
};

// make sure projekktor works with jquery 1.3, 1.4, 1.5, 1.6:
if ($.prop==undefined || $().jquery < "1.6") {    
    $.fn.prop = function(arga, argb) { 
	return $(this).attr(arga, argb); 
    }
}	
projekktor = $p = function() {
    
    var arg = arguments[0],	
	instances = [],
	plugins = [];

    if (!arguments.length) {
	return projekktors[0] || null;
    } 
	
    // get instances
    // projekktor(idx:number);
    if (typeof arg == 'number') { 
	return projekktors[arg];	
    }
    
    // by string selection unqiue "id" or "*"
    if (typeof arg == 'string') {
	// get all instances
	if (arg == '*') {
	    return new Iterator(projekktors);	
	}

	// get instance by Jquery OBJ, 'containerId' or selector
	for (var i=0; i<projekktors.length; i++) {
	    try {if (projekktors[i].getId() == arg.id) { instances.push(projekktors[i]); continue; } } catch(e){};	    
	    try {
		for (var j=0; j<$(arg).length; j++)
		{
		    if (projekktors[i].env.playerDom.get(0)==$(arg).get(j)) { instances.push(projekktors[i]); continue; }
		}
	    } catch(e){};
	    try {if (projekktors[i].getParent() == arg) { instances.push(projekktors[i]); continue; }} catch(e){};
	    try {if (projekktors[i].getId() == arg) { instances.push(projekktors[i]); continue; }} catch(e){};
	    
	}
	if (instances.length>0)
	    return (instances.length==1) ? instances[0] : new Iterator(instances);
    }

    // build instances
    if (instances.length==0) {	
	var cfg = arguments[1] || {};
	var callback = arguments[2] || {};
	if (typeof arg == 'string') {
		var count=0, playerA;
		$.each($(arg), function() {
		    playerA = new PPlayer($(this), cfg, callback);
		    projekktors.push(playerA);
		    count++;
		});
		return (count>1) ? new Iterator(projekktors) : playerA;
	// arg is a DOM element
	} else if (arg) {
	    projekktors.push(new PPlayer(arg, cfg, callback));
	    return new Iterator(projekktors);
	}
    }

    return null;
    
    function PPlayer(srcNode, cfg, onReady) {
	
	this.config = new projekktorConfig('1.0.05');
		
	this._persCfg = [
	    'playbackQuality',
	    'enableNativePlayback',
	    'enableFlashFallback',
	    'volume'
	];	
		
	
	this.env = {
	    muted: false,
	    inFullscreen: false,
	    playerStyle: null,
	    scrollTop: null,
	    scrollLeft: null,
	    bodyOverflow: null,
	    playerDom: null,
	    mediaContainer: null,
	    agent: 'standard',
	    mouseIsOver: false,
	    loading: false,  // important
	    autoSize: false,
	    className: '',
	    onReady: onReady
	    
	};

  
	this.media = [null];
	this._plugins = [];
	this._queue = [];
	this._cuePoints = {};
	this.listeners = [];     
	this.playerModel = {};
	this._isReady = false;
	this._compTableCache = false;
	this._currentItem = null;
	this._playlistServer = '';
	this._id = '';	

		    
	/* apply incoming playlistdata  */
	this._reelUpdate = function(obj) {

	    this.env.loading = true;
	    
	    switch(typeof obj) {
		default:
		    obj = [{'file':'', 'type':'', 'errorCode': 98}];
		    break;		
		case 'object':		
		    if (obj.length==0) {
			obj = [{'file':'', 'type':'', 'errorCode': 97}];
		    }
		    break;
		case 'undefined':
		    obj = [{'file':'', 'type':'', 'errorCode': 97}];
		    break;
	    }
 
	    var ref = this, data = obj;
	
	    this.media = [];	   

	    // gather and set alternate config from reel:
	    try {
		var changes = false;
		for(var props in data.config) {
		    if (typeof data.config[props].indexOf('objectfunction')>-1) continue; // IE SUCKZ
		    this.config[props] = eval( data.config[props] );
		    changes=true;		   
		}        	
		delete(data.config);
		if (changes===true) {
		    this._debug('Updated config var: '+props+' to '+this.config[props]);
		    this._promote('configModified');
		}
	    } catch(e) {}
	

	    // add media items
	    var files = data.playlist || data;	    	    
	    for(var item in files) {
		if (typeof files[item]=='function') continue; // IE
		if (typeof files[item]==undefined) continue;
	
		if (files[item]) {
		    this._addItem(this._prepareMedia({file:files[item], config:files[item].config || {}, errorCode: files[item].errorCode}));
		}
	    }

	    this.env.loading = false;
	    this._promote('scheduled', this.getItemCount());
	    this._syncPlugins(function(){ref.setActiveItem(0);})
	};
	

	this._addItem = function(data, idx, replace) {

	    var resultIdx = 0;
	    // replace "error dummy" if any:
	    if (this.media.length===1 && this.media[0].mediaModel=='NA') {
		this._detachplayerModel();
		this.media = [];
	    }
	    
	    // inject or append:
	    if (idx===undefined || idx<0 || idx>this.media.length-1) {
		this.media.push(data);
		resultIdx = this.media.length-1;
	    } else {
		this.media.splice(idx, (replace===true) ? 1 : 0,data);
		resultIdx = idx;
	    }

	    // report schedule modifications after initial scheduling only:
	    if (this.env.loading===false) 
		this._promote('scheduleModified', this.getItemCount());
		
	    return resultIdx;
	};
	
	this._removeItem = function(idx) {
	    
	    var resultIdx = 0;
	     
	    if (this.media.length===1) {
		// keep "error dummy", nothing to do:
		if (this.media[0].mediaModel=='NA') {
		    return 0;
		} else {		
		    // replace last one with "error dummy"
		    this.media[0] = this._prepareMedia({file:''});
		    return 0;
		}
	    }
	    
	    
	    if (idx===undefined || idx<0 || idx>this.media.length-1) {
		this.media.pop();
		resultIdx = this.media.length;
	    }
	    else {
		this.media.splice(idx, 1);
		resultIdx = idx;
	    }

	    if (this.env.loading===false) 
		this._promote('scheduleModified', this.getItemCount());
		
	    return resultIdx;
	};	
	
	/* apply available data and playout models */
	this._prepareMedia = function(data) {

	    var mediaFile = '',
		extTypes = {},
		typesModels = {},
		errorCode = data.errorCode || 7,
		priority = [],
		modelSets = []
		result = {};

	    // build regex string and filter dublicate extensions and more ... 
	    var extRegEx = [];
	    for(var i in $p.mmap ) {
		
		/*
		if ($p.mmap[i].platform && $p.mmap[i].platform.toUpperCase()=='VLC') {
		    if ( !this.getVLCVersion()>0  ) continue;
		    if ( this.getConfig('enableVLC')==false ) continue;
		}
		*/
	    	
		// flash isn´t installed or disabled
		if ($p.mmap[i].platform && $p.mmap[i].platform.toUpperCase()=='FLASH') {
		    if ( !this.getFlashVersion()>0  ) continue;
		    if ( this.getConfig('enableFlashFallback')==false ) continue;
		}
		
		// type can not or should not be played back natively
		if ( $p.mmap[i].platform && $p.mmap[i].platform.toUpperCase()=='NATIVE' ) {
		    if ( !this.getCanPlayNatively($p.mmap[i].type) ) continue;
		    if ( this.getConfig('enableNativePlayback')==false ) continue;
		    if ( data.config && data.config.flashStreamType=='rtmp' ) continue;
		}
		
		// set priority level
		$p.mmap[i].level = this.config._platformPriority.indexOf($p.mmap[i].platform);
		$p.mmap[i].level = ($p.mmap[i].level<0) ? 100 : $p.mmap[i].level;
		
		extRegEx.push( '.'+$p.mmap [i].ext );

		if (!extTypes[$p.mmap[i].ext])
		    extTypes[$p.mmap[i].ext] = new Array();
		    
		extTypes[$p.mmap[i].ext].push( $p.mmap[i] );
		    
		    		
		if (!typesModels[$p.mmap[i].type])
		    typesModels[$p.mmap[i].type] = new Array();
		    
		typesModels[$p.mmap[i].type].push( $p.mmap[i] );
	    }
	    
	    extRegEx = '^.*\.('+extRegEx.join('|')+")$";

	
	    // incoming file is a string only, no array
	    if (typeof data.file=='string') {
		data.file = [{'src':data.file}];
		if (typeof data.type=='string') {
		    data.file = [{'src':data.file, 'type':data.type}];
		}
	    }
	    
	    // incoming file is ... bullshit
	    if ($.isEmptyObject(data) || data.file===false || data.file === null) {
		data.file = [{'src':null}];
	    }

	    for(var index in data.file) {
		
		// mööööp
		if (index=='config') continue;
		
		// just a filename _> go object
		if (typeof data.file[index]=='string') {
		    data.file[index] = {'src':data.file[index]};
		}
		
    
		// nothing to do, next one
		if (data.file[index].src==null) {
		    continue;
		}

 		// get file extension:
		/**
		try {
		    data.file[index].ext = data.file[index].src.match( new RegExp(extRegEx))[1];
		    data.file[index].ext = (!data.file[index].ext) ? 'NaN' : data.file[index].ext.replace('.','');
		} catch(e) { data.file[index].ext='NaN'; }   
		*/
	    
		// if type is set, get rid of the codec mess
		if ( data.file[index].type!=null && data.file[index].type!=='') {
		    try {
			var codecMatch = data.file[index].type.split(' ').join('').split(/[\;]codecs=.([a-zA-Z0-9\,]*)[\'|\"]/i);		    

			if (codecMatch[1]!==undefined) {
			    data.file[index].codec = codecMatch[1];
			    data.file[index].type = codecMatch[0];
			}
		    } catch(e){}
		    

		}
		else {
		    data.file[index].type = this._getTypeFromFileExtension( data.file[index].src );
		}

		if (typesModels[ data.file[index].type ]) 
		    modelSets =  $.merge(modelSets, typesModels[ data.file[index].type ]);
	    }

	    if (modelSets.length==0)	    
		modelSets = typesModels['none/none'];
	    else
	    // find highest priorized playback model
	    modelSets.sort(function(a, b) {
		return a.level - b.level;
	    });

	    for (var index in data.file) {
		if ( data.file[index].type==modelSets[0].type || modelSets[0].type=='none/none' ) {
		    
		    result = {
			ID: $p.utils.randomId(8),
			setup: data.file,
			type: data.file[index].type,
			ext: data.file[index].ext,
			file: (!$.isEmptyObject(data.config) && data.config.flashStreamType!='rtmp') ? $p.utils.toAbsoluteURL(data.file[index].src) : data.file[index].src,
			mediaModel: modelSets[0].model || 'NA',			
			errorCode: errorCode,
			config:  data.config || {},
			_prepared: true
			
		    }

		    break;
		}
	    }

	    return result;
	};    
	
	/* media element update listener */
	this._modelUpdateListener = function(type, value) {
	    var ref = this;

	    if (!this.playerModel.init) return;
	    if (type!='time' && type!='progress') {		
		this._debug("Received model Update: '"+type+"' ("+value+") while handling '"+this.playerModel.getFile()+"' using '"+this.playerModel.getModelName()+"'");	    	   
	    }

	    switch(type) {
		case 'state':
		    this._promote('state', value); // IMPORTANT: promote first!
		    switch (value) {
			case 'IDLE':
			    break;
			case 'AWAKENING':
			    var modelRef = this.playerModel;
			    this._syncPlugins(function() {
				if (modelRef.getState('AWAKENING'))
				    modelRef.displayItem(true);
			    });
			    break;
			case 'BUFFERING':
			case 'PLAYING':
			    break;			    
		    
			case 'ERROR':
			    this._addGUIListeners();	
			    this._promote('error', {});
			    break;				    
			
			case 'STOPPED':
			    this._promote('stopped', {});
			    break;
			
			case 'PAUSED':            
			    if (this.getConfig('disablePause')===true) {
				this.playerModel.applyCommand('play', 0);
			    }
			    break;
		    
			case 'COMPLETED':
			    // all items in PL completed:
			    if (this._currentItem+1>=this.media.length && !this.getConfig('loop')) {
				this.setFullscreen(false);
				this._promote('done', {});
			    }
			    // next one, pls:
			    this.setActiveItem('next');
			    break;
		    }	
		    break;
		
		case 'buffer':	
		    this._promote('buffer', value);
		    // update time and progress 
		    // this._promote('time');
		    break;
		
		case 'modelReady':
		    this._promote('item', ref._currentItem);
		    break;
		
		case 'displayReady':
		    this._promote('displayReady', true);
		    var modelRef = this.playerModel;
		    this._syncPlugins(function() {
			ref._promote('ready');
			ref._addGUIListeners();			
			if (!modelRef.getState('IDLE'))			   
			    modelRef.start();
		    });
		   
		    break;
		
		case 'qualityChange':
		    ref._promote('qualityChange');		    
		    this.setConfig({playbackQuality: value});
		    break;
		
		case 'FFreinit':		
		    break;
		
		case 'seek':
		    this._promote('seek', {dest:value});
		    break;
		
		case 'volume':
		    
		    this.setConfig({volume: value});             
		    this._promote('volume', value);
		    
		    if (value<=0) {
			this.env.muted = true;
			this._promote('mute', value);			
		    } else if (this.env.muted ==true) {
			this.env.muted = false;
			this._promote('unmute', value);			
		    }
		    
		    break;

		case 'time':	
		case 'resume':
		case 'progress':
		case 'fullscreen':
		case 'resize':
		case 'start':		   
		    this._promote(type, value);
		    break;

		case 'playlist':
		    this.setFile(value.file, value.type);
		    break;
		
		case 'config':
		    this.setConfig(value);
		    break;

		case 'scaled':
		    // experimental
		    if (this.env.autoSize===true) {
			this.env.playerDom.css({
			    height: value.realHeight+"px",
			    width: value.realWidth+"px"
			});
			this._promote('resize', value);	    
			this.env.autoSize = false;
			break;
		    }
		    this._promote('scaled', value);	    
		    break;
	    }
    
	};

	this._syncPlugins = function(callback) {
	    // wait for all plugins to re-initialize properly
	    var ref = this;
	    this.env.loading = true;
	    (function() {
		try{		    
		    if (ref._plugins.length>0) {
			for(var i=0; i<ref._plugins.length; i++) {
			    if (!ref._plugins[i].isReady()) {
				setTimeout(arguments.callee,50);
				return;
			    }			
			}
		    }
		    ref.env.loading = false;		   
		    ref._promote('pluginsReady', {});
		    if (ref._isReady===true) {			
			ref._enqueue(function() { try {ref._applyCuePoints();} catch(e) {} } );			
		    }		    
		    try {callback();}catch(e){}
		} catch(e) {}
	    })();	
	};
	
	this._MD = function(event) {
	    projekktor('#'+event.currentTarget.id.replace('_media', ''))._displayMousedownListener(event);
	};
	
	/* attach mouse-listeners to GUI elements */
	this._addGUIListeners = function() {
	    
	    var ref = this;
	    this._removeGUIListeners();
	    
	    if (this.getDC().get(0).addEventListener)
		this.getDC().get(0).addEventListener("mousedown", this._MD, true);
	    else
		// IE
		this.getDC().mousedown(function(event){ref._displayMousedownListener(event);});	
		
	    this.getDC()
		.mousemove(function(event){ref._displayMousemoveListener(event);})	
		.mouseenter(function(event){ref._displayMouseEnterListener(event);})
		.mouseleave(function(event){ref._displayMouseLeaveListener(event);})
		.bind('touchstart', function(){ref._MD})
		.bind('touchend', function(){ref._displayMousemoveListener(event);});


	    $(window).bind('resize.projekktor'+this.getId(), function() {ref.playerModel.applyCommand('resize');});
			
	    // keyboard interface get rid of this moz.warning
	    if (this.config.enableKeyboard===true) {
		if (!$.browser.mozilla) {		
		    $(document.documentElement).unbind('keydown.pp'+this._id);
		    $(document.documentElement).bind('keydown.pp'+this._id, function(evt){
			ref._keyListener(evt);
		    });
		} else {
		    $(document.documentElement).unbind('keypress.pp'+this._id);		    
		    $(document.documentElement).bind('keypress.pp'+this._id,function(evt){
			ref._keyListener(evt);
		    });
		}
	    }
    
	};
	
	/* remove mouse-listeners */
	this._removeGUIListeners = function() {
	    $("#"+this.getId()).unbind();
	    this.getDC().unbind();
	    
	    if (this.getDC().get(0).removeEventListener)
		this.getDC().get(0).removeEventListener("mousedown", this._MD, true);
	    else
		this.getDC().get(0).detachEvent('onmousedown', this._MD);	    
	    
  
	    $(window).unbind('resize.projekktor'+this.getId());	   
	};
	
	/* add plugin objects to the bubble-event queue */
	this._registerPlugins = function() {
	    
	    var plugins = $.merge($.merge([],this.config._plugins), this.config._addplugins);

	    // nothing to do / we don´t do this twice
	    if (this._plugins.length>0) return;
	    if (plugins.length==0) return;
	    for(var i=0; i<plugins.length; i++) {
		
		var pluginName = "projekktor"+plugins[i].charAt(0).toUpperCase() + plugins[i].slice(1);
		try {typeof eval(pluginName);} catch(e) {continue;}
		
		var pluginObj = $.extend(true, {}, new projekktorPluginInterface(), eval(pluginName).prototype);
		pluginObj.name = plugins[i].toLowerCase();
		pluginObj.pp = this;
		pluginObj.playerDom = this.env.playerDom; 	
		pluginObj._init( this.config['plugin_'+plugins[i].toLowerCase()] || {} );
		this._plugins.push( pluginObj );
	    }
	};
	
	/* removes some or all eventlisteners from registered plugins */
	this.removePlugin = function(rmvPl) {
	    
	    if (this._plugins.length==0) return;
	    
	    var pluginsToRemove = rmvPl || $.merge($.merge([],this.config._plugins), this.config._addplugins),
		pluginsRegistered = this._plugins.length;

	    for (var j=0; j<pluginsToRemove.length; j++){
		for (var k=0; k<pluginsRegistered; k++){
		    if (this._plugins[k]!=undefined) {
			if (this._plugins[k].name==pluginsToRemove[j].toLowerCase()){
			    this._plugins[k].deconstruct();
			    this._plugins.splice(k, 1);
			}
		    }
		}
	    }
	};
	
	/* promote an event to all registered plugins */
	this._promote = function(evt, value) {

	    var event = evt, pluginData={};
	    if (typeof event=='object') {
		if (!event._plugin) return;
		value.PLUGIN = event._plugin+"";
		value.EVENT = event._event+""
		event = 'pluginevent'
	    }
	    
	    
	    if (event!='time' && event!='progress' && event!='mousemove') {		
		this._debug("Event: "+event);
		// if (event=='state') console.log("Fireing :"+event+" "+this.getState());
		// else console.log("Fireing :"+event+" "+value);
	    }
	    
	    // fire on plugins
	    if (this._plugins.length>0) {
		for(var i in this._plugins) {
		    try{
		        this._plugins[i][event+"Handler"](value, this);
		    } catch(e){};
		}
	    }
	    // fire on custom listeners
	    if (this.listeners.length>0) {
		for(var i in this.listeners) {
		    try{
			if ( this.listeners[i]['event']==event || this.listeners[i]['event']=='*' ) {
			    this.listeners[i]['callback'](value, this);
			}
		    } catch(e){};
		}
	    }
	};    
	
	/* destoy, reset, break down to rebuild */ 
	this._detachplayerModel = function() {
	    this._removeGUIListeners();	
	
	    try {
		this.playerModel.destroy();
		this._promote('detach', {});

	    } catch(e) {
		// this.playerModel = new playerModel();
		// this.playerModel._init({pp:this, autoplay: false});
	    }	    
	};
	    
	
	/*******************************
	      GUI LISTENERS
	*******************************/
	this._displayMousedownListener = function(evt) {
	    if (!this.env.mouseIsOver)
		return false;
	    
	    switch (evt.which) {
		case 1:		    
		    this._promote('leftclick', evt);		    
		    break;
		
		case 2:
		    this._promote('middleclick', evt);
		    break;
		
		case 3:
		    evt.stopPropagation()
		    evt.preventDefault();		
		    $(document).bind('contextmenu', function(evt){
			$(document).unbind('contextmenu');
			return false;
		    });		
		    this._promote('rightclick', evt);
		    break;
	    }
	    return false;    
	};

	
	this._displayMousemoveListener = function(evt) {
	    if ( "TEXTAREA|INPUT".indexOf(evt.target.tagName.toUpperCase()) > -1){
		this.env.mouseIsOver = false;
	    }
	    this.env.mouseIsOver = true;
	    this._promote('mousemove', {});
	    evt.stopPropagation();
	};
	    
	
	this._displayMouseEnterListener = function(evt) {
	    this._promote('mouseenter', {});
	    this.env.mouseIsOver = true;
	    evt.stopPropagation();
	};
	
	
	this._displayMouseLeaveListener = function(evt) {
	    this._promote('mouseleave', {});
	    this.env.mouseIsOver = false;
	    evt.stopPropagation();
	};    
	    
	this._keyListener = function(evt) {
	    if (!this.env.mouseIsOver) return;
	    evt.stopPropagation();
	    evt.preventDefault();

	    this._debug('Keypress: '+evt.keyCode);
	    // escape from fullscreen
	    this._promote('key', evt);
	    switch( evt.keyCode ) {
		case 27: // esc
		    this.setFullscreen(false);
		    break;
		case 13: // enter
		    this.setFullscreen(true);
		    break; 
		case 39: // right arrow		    
		    this.setActiveItem('next');
		    break;
		case 37: // left arrow		
		    this.setActiveItem('previous');
		    break;
		case 123: // f12
		    break;
		default: // space
		    this.setPlayPause();
		    break;
	    };
	    return false;
	};
	
	/*****************************************
	    DOM Manipulations
	*****************************************/
	/* make player fill the whole window viewport */
	this._enterFullViewport = function(forcePlayer) {

	    // get relevant elements	    
	    var win = this.getIframeWindow() || $(window),
		target = this.getIframe() || this.getDC();

	    if (forcePlayer) {
		win = $(window);
		target = this.getDC();
	    }
	    
	    // remember relevant attributes
	    this.env.scrollTop = win.scrollTop();
	    this.env.scrollLeft = win.scrollLeft();
	    this.env.playerStyle = target.attr('style');
	    this.env.bodyOverflow = $(win[0].document.body).css('overflow');
	
	    this.env.iframeWidth = target.attr('width') || 0;
	    this.env.iframeHeight = target.attr('height') || 0;

	    // prepare parent window
	    win.scrollTop(0).scrollLeft(0);
	    $(win[0].document.body).css('overflow', 'hidden');
	    
	    // prepare player
	    target.css({
		position: 'fixed',
		display: 'block',
		top: 0,
		left: 0,
		width: '100%',
		height: '100%',
		zIndex: 9999,
		margin: 0,
		padding: 0
	    });
	    
	    if (!forcePlayer)
		target.addClass('fullscreen');

	    
	    return target;
	};
	
	/* reset player from "full (parent) window viewport" iframe thing */
	this._exitFullViewport = function(forcePlayer) {

	    // get relevant elements	    
	    var win = this.getIframeWindow() || $(window),
		target = this.getIframe() || this.getDC();
		
	    if (forcePlayer) {
		win = $(window);
		target = this.getDC();
	    }	    

	    // reset 
	    win.scrollTop(this.env.scrollTop).scrollLeft(this.env.scrollLef);
	    $(win[0].document.body).css('overflow', this.env.bodyOverflow);

	    if ( this.env.iframeWidth > 0 && !forcePlayer) {
		target.attr('width', this.env.iframeWidth+"px");
		target.attr('height', this.env.iframeHeight+"px");		
	    }
	    
	    target
		.attr('style', (this.env.playerStyle==undefined) ? '' : this.env.playerStyle )
		.removeClass('fullscreen');
	    
	    return (this.getIframe()) ? parent.window.document : document;
	};
	    
    
	
	
	/*******************************
	public (API) methods GETTERS
	*******************************/
	
	// compatibility <0.9.x
	this.getItemConfig = function(name, itemIdx) {
	    return this.getConfig(name, itemIdx);
	};
	
	this.getConfig = function(name, itemIdx) {

	    var idx = itemIdx || this._currentItem,
		result = undefined,
		playerCfg = (this.config['_'+name]) ? this.config['_'+name] : this.config[name];
		
	    if (playerCfg!==undefined) {
		
		// grab defaults
		result = playerCfg;		
		// get value from user config
		if ($.inArray(name, this._persCfg)>-1) {
		    if (this._cookie(name)!==null) {
			result = this._cookie(name);
		    }
		}

		// get value from item-specific config (beats them all)
		if (this.config['_'+name]==undefined) {
		    try {
			if (this.media[idx]['config'][name]!==undefined) {
			    result = this.media[idx]['config'][name];
			}
		    } catch(e){}
		}	    
		
	    } else {
		if (name.indexOf('plugin_')>-1) {
		    try {
			if (this.media[idx]['config'][name]) {
			    result = this.media[idx]['config'][name];
			}
		    } catch(e){}
		}
	    }
	    
	    return result;
	};    
	    
	this.getItemCount = function() {
	    return this.media.length;
	};
    
	this.getDC = function() {
	    return this.env.playerDom;  
	};
    
	this.getState = function(isThis) {
	    
	    var result = null;
	    try {result =  this.playerModel.getState();}
	    catch(e) {result =  'IDLE';}
	    
	    if (isThis!=null) return (result==isThis.toUpperCase());
	    return result;
	    
	};

	this.getLoadProgress = function() {	
	    try {return this.playerModel.getLoadProgress();}
	    catch(e) {return 0;}  
	};
	
	this.getKbPerSec = function() {	
	    try {return this.playerModel.getKbPerSec();}
	    catch(e) {return 0;}  
	};
	
	this.getItemId = function(idx) {
	    if (idx==undefined) return this.media[this._currentItem].ID;
	    return this.media[idx].ID;
	};
	
	this.getItemIdx = function() {
	    return this._currentItem;
	};
	
	this.getItem = function() {
	    arg = arguments[0] || 'current';
	    switch(arg) {
		case 'next':
		    return $.extend(true, [], this.media[this._currentItem+1]);	
		case 'prev':
		    return $.extend(true, [], this.media[this._currentItem-1]);
		case 'current':
		    return $.extend(true, [], this.media[this._currentItem]);
		case '*':
		    return $.extend(true, [], this.media);
		default:
		    return $.extend(true, [], this.media[arg]);
	    }
	    
	};
	
	this.getVolume = function() {
	    return (this.getConfig('fixedVolume')===true)
		? this.config.volume
		: this.getConfig('volume');
	};    
	
	this.getTrackId = function() {
	    if (this.getConfig('trackId')) {
		return this.config.trackId;
	    }
	    if (this._playlistServer!=null) {
		return "pl"+this._currentItem;
	    }
	    return null;
	};
	    
	this.getLoadPlaybackProgress = function() {
	    try {return this.playerModel.getLoadPlaybackProgress()}
	    catch(e) {return 0;}  
	};
	
	this.getDuration = function() {
	    try {return this.playerModel.getDuration();}
	    catch(e) {return 0;}  
	};
	
	this.getPosition = function() {
	    try {return this.playerModel.getPosition() || 0;}
	    catch(e) {return 0;}  
	};
    
	this.getTimeLeft = function() {
	    try {return this.playerModel.getDuration() - this.playerModel.getPosition();}
	    catch(e) {return this.media[this._currentItem].duration;}  
	};
    
	this.getInFullscreen = function() {
	    return this.getNativeFullscreenSupport().isFullScreen();
	}
	
	this.getMediaContainer = function() {
	    
	    // return "buffered" media container
	    if (this.env.mediaContainer==null) {
		this.env.mediaContainer = $('#'+this.getMediaId());
	    }
	    // if mediacontainer does not exist ...	    
	    if (this.env.mediaContainer.length==0) {
		// and there´s a "display", injectz media container
		if ( this.env.playerDom.find('.'+this.config._cssClassPrefix+'display').length>0 ) {
		    this.env.mediaContainer = $(document.createElement('div'))
			.attr({'id':this.getId()+"_media"}) // IMPORTANT IDENTIFIER
			.css({
			   // position: 'absolute',
			    overflow: 'hidden',
			    height: '100%',
			    width: '100%',
			    top: 0,
			    left: 0,
			    padding: 0,
			    margin: 0,
			    display: 'block'
			})
			.appendTo( this.env.playerDom.find('.'+this.config._cssClassPrefix+'display') );
		}
		
		// elsewise create a 1x1 pixel dummy somewhere
		else {
		    this.env.mediaContainer = $(document.createElement('div'))
			.attr({id: this.getMediaId()})
			.css({width: '1px', height: '1px'})
			.appendTo( $(document.body) );
		}
		
	    }
	    
	    // go for it
	    return this.env.mediaContainer;
	};

	this.getMediaId = function() {
	    return this.getId()+"_media";
	};      
    
	this.getMediaType = function() {
	    return this.media[this._currentItem].type;
	};
		
	this.getUsesFlash = function() {
	    return (this.playerModel.requiresFlash !==false)
	};
	
	this.getModel = function() {	   
	    try {return this.media[this._currentItem].mediaModel.toUpperCase()} catch(e) {return "NA";}
	};	
	
	this.getIframeWindow = function() {
	    try {
		var result = $(parent.window) || [];
		if (result.length==0) return false;
		return result;
	    } catch(e) { return false; }
	};
    
	this.getIframe = function() {
	    
	    try {
		var result = window.$(frameElement) || [];
		if (result.length==0) return false;
		return result;
	    } catch(e) { return false; }
	    
	};    
    
	this.getPlaylist = function() {
	    return this.getItem('*');
	};

	this.getPlaybackQuality = function() {	    
	    return this.getConfig('playbackQuality');  
	};

	/*
	this.getVLCVersion = function() {

	    try {
		return navigator.plugins['VLC Multimedia Plug-in'].version.match(/^,?(.+),?$/)[1].match(/\d+/g)[0]
	    } catch(e) {}		    
	    return '0,0,0'.match(/\d+/g)[0];	    
	}
	*/

	/* returns the version of the flash player installed for user´s browser. returns 0 on none. */
	this.getFlashVersion = function() {

	    try {
		try {
		    // avoid fp6 minor version lookup issues
		    // see: http://blog.deconcept.com/2006/01/11/getvariable-setvariable-crash-internet-explorer-flash-6/
		    var axo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash.6');
		    try { axo.AllowScriptAccess = 'always';	} 
		    catch(e) { return '6,0,0'; }				
		} catch(e) {}
		return new ActiveXObject('ShockwaveFlash.ShockwaveFlash').GetVariable('$version').replace(/\D+/g, ',').match(/^,?(.+),?$/)[1].match(/\d+/g)[0];
	    } catch(e) {
		try {
		    if(navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin){			
			return (navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]).description.replace(/\D+/g, ",").match(/^,?(.+),?$/)[1].match(/\d+/g)[0];
		    }
		} catch(e) {}		
	    }
	
	   
	    return '0,0,0'.match(/\d+/g)[0];
	};       
       
       
       	this.getCanPlay = function(type) {
	    return this._canPlay(type, true);
	}
	
	this.getCanPlayNatively = function(type) {
	    return this._canPlay(type, false);
	}
       
	this._canPlay = function(type, all) {

	    if (this._compTableCache==false)
		this._compTableCache = this._testMediaSupport();
	
	    var checkFor = [],
		checkIn = (all) ? this._compTableCache.all : this._compTableCache.media;
	    
	    switch (typeof type) {
		
		case 'undefined':
		    if (checkIn>0)
			return true;
		    
		case 'string':
		    checkFor.push(type);
		    break;
		
		case 'array':
		    checkFor = type;
		    break;
		    
	    }

	    for(var i in checkFor) {		
		if ($.inArray( checkFor[i], checkIn)>-1)
		    return true;
	    }

	    return false;
	};
	
	
	/*
	 Thanx to John Dyer: http://johndyer.name/native-fullscreen-javascript-api-plus-jquery-plugin/
	*/
	this.getNativeFullscreenSupport = function() {

	    var ref = this,
		fullScreenApi = { 
		    supportsFullScreen: 'semi',
		    isFullScreen: function() {try {return this.dest.hasClass('fullscreen');} catch(e){return false;}}, 
		    requestFullScreen: function() {ref._enterFullViewport();  ref.playerModel.applyCommand('fullscreen', true);}, 
		    cancelFullScreen: function() { ref._exitFullViewport();  ref.playerModel.applyCommand('fullscreen', false);},
		    prefix: '',
		    dest: (this.config._iframe===true) ? this.getIframe() : this.getDC(),
		    ref: this
	    },
	    browserPrefixes = 'webkit moz o ms khtml'.split(' ');
	    
	    
	    // check for native support
	    
	    // standard conform?
	    if (typeof document.cancelFullScreen != 'undefined') {
		    fullScreenApi.supportsFullScreen = true;
	    } else {

		// (double)-check for fullscreen support by vendor prefix
		for (var i = 0, il = browserPrefixes.length; i < il; i++ ) {

		    fullScreenApi.prefix = browserPrefixes[i];

		    // media element only
		    if (typeof document.createElement('video')[fullScreenApi.prefix+"EnterFullscreen"] != 'undefined') {				
			fullScreenApi.supportsFullScreen = 'media';				
		    }			    
		    
		    // player container
		    if (typeof document[fullScreenApi.prefix + 'CancelFullScreen' ] != 'undefined' ) {				
			fullScreenApi.supportsFullScreen = 'dom';
			
			// FF8+FF9 double-check
			if (fullScreenApi.prefix=='moz' && typeof document[fullScreenApi.prefix + 'FullScreenEnabled'] == 'undefined' )
			    fullScreenApi.supportsFullScreen = false;
		    }
		    
		    if (fullScreenApi.supportsFullScreen!==false && fullScreenApi.supportsFullScreen!=='semi')
			break;
			
		}
	    }

	    // forget it:
	    if (fullScreenApi.supportsFullScreen=='semi')
		return fullScreenApi;

	    // is in fullscreen check
	    fullScreenApi.isFullScreen = function() {
		var dest = (ref.getIframe()) ? parent.window.document : document;
		switch (this.prefix) {	
		    case '':
			return dest.fullScreen;
		    case 'webkit':
			return dest.webkitIsFullScreen;
		    default:
			return dest[this.prefix + 'FullScreen'];
		}
	    }
	    
	    // set initiation method and dest Obj
	    if (fullScreenApi.supportsFullScreen=='dom') {
		
		// the browser supports true fullscreen for any DOM container - this is ubercool:
		fullScreenApi.requestFullScreen = function() {
		    var target = ref._enterFullViewport(),
			apiRef = this,
			dest = (ref.getIframe()) ? parent.window.document : document;
			
		    $(dest).unbind(this.prefix + "fullscreenchange.projekktor");
		    $(dest).bind(this.prefix + "fullscreenchange.projekktor", function(evt) {
			
			$(evt.target).unbind(this.prefix + "fullscreenchange.projekktor");
			apiRef.ref.playerModel.applyCommand('fullscreen', apiRef.isFullScreen());
		
			if (!apiRef.isFullScreen()) {
			    apiRef.ref._exitFullViewport();
			    apiRef.ref.playerModel.applyCommand('fullscreen', false);
			}
		    });
		    
		    if (this.prefix === '')
			target.get(0).requestFullScreen()
		    else
			target.get(0)[this.prefix + 'RequestFullScreen']();
					    
		}
		
		// cancel fullscreen method
		fullScreenApi.cancelFullScreen = function() {
		    var target = ref._exitFullViewport();

		    $(target).unbind(this.prefix + "fullscreenchange.projekktor");
		    ref.playerModel.applyCommand('fullscreen', false);
		    
		    if (this.prefix === '')
			target.cancelFullScreen();
		    else
			target[this.prefix + 'CancelFullScreen']();
			
		}				
	    
		return fullScreenApi;
	    }
	    
	    
	    // the browser supports true fullscreen for the media element only - this is semi cool
	    fullScreenApi.requestFullScreen = function(el) {
		ref.playerModel.getMediaElement().get(0)[this.prefix+'EnterFullscreen']();
	    }		
	    fullScreenApi.dest = {};	
	
	    // cancel fullscreen method
	    fullScreenApi.cancelFullScreen = function() {}		
	    
	    return fullScreenApi;
	};
	
	this.getId = function() {
	    return this._id;
	};
	
	this.getHasGUI = function() {	    
	    try {
	        return this.playerModel.getHasGUI();
	    } catch(e) { return false;}
	};	
	
	this.getCssPrefix = function() {
	    return this.config._cssClassPrefix;  
	};
	
	this.getPlayerDimensions = function() {
	    return {width: this.config._width, height: this.config._height};
	};

	this.getMediaDimensions = function() {
	    return {width: this.config._width, height: this.config._height};
	};
	
	/* asynchronously loads external XML and JSON data from server */
	this.getFromUrl = function(url, dest, callback, customParser, dataType) {

	    var data = null, ref=this;

	    if (dest==ref && callback=='_reelUpdate') {
		this._promote('scheduleLoading', 1+this.getItemCount());
	    }

	    if (callback.substr(0,1)!='_') {
		window[callback] = function(data) {		
		    try { delete window[callback]; } catch(e) {}
		    dest[callback](data);
		};	 		
	    } else if (dataType.indexOf('jsonp')>-1) {
		this['_jsonp'+callback] = function(data) {
		    dest[callback](data);
		};		
	    }

	    if (dataType) {
		if ($.parseJSON==undefined && dataType.indexOf('json')>-1) {
		    this._raiseError("Projekktor requires at least jQuery 1.4.2 in order to handle JSON playlists.");
		    return this;
		}
		dataType = (dataType.indexOf('/')>-1) ? dataType.split('/')[1] : dataType;
	    }

	    $.ajax({
		url: url,
		complete: function( xhr, status ) {

		    if (dataType==undefined) {
			try {
			    if (xhr.getResponseHeader("Content-Type").indexOf('xml')>-1) dataType = 'xml';
			    if (xhr.getResponseHeader("Content-Type").indexOf('json')>-1) dataType = 'json';
			    if (xhr.getResponseHeader("Content-Type").indexOf('html')>-1) dataType = 'html';
			} catch(e){}
		    }

		    // on error, bypass IE xml header issue:
		    switch (dataType) {
			case 'html':
			case 'xml':
			    // Create the xml document from the responseText string.
			    if( window.DOMParser ) {
				data = new DOMParser()
				data = data.parseFromString( xhr.responseText,"text/xml" ) ;
			    }
			    else { // Internet Explorer			
				data=new ActiveXObject( "Microsoft.XMLDOM" ) ;
				data.async = "false" ;
				data.loadXML( xhr.responseText ) ;
			    }
			    break;
			
			case 'json':
			    data = xhr.responseText;
			    if (typeof data == 'string') {
				data = $.parseJSON(data);
			    }
			    break;
			case 'jsonp':		
			    break;
			default:			
			    data = xhr.responseText;
			    break;
		    }

		    try {data = customParser(data, xhr.responseText);} catch(e){}

		    if (status!='error' && dataType!='jsonp') {
			try {dest[callback](data);} catch(e){}
		    }
		},
		error: function(data) {
		 
		    // bypass jq 1.6.1 issues
		    if (dest[callback] && dataType!='jsonp'){
			dest[callback](false);		    
		    }
		},
		cache: true,
		async: !this.getIsMobileClient(),
		dataType: dataType,
		jsonpCallback: (callback.substr(0,1)!='_') ? false : "projekktor('"+this.getId()+"')._jsonp"+callback,
		jsonp: (callback.substr(0,1)!='_') ? false : 'callback'
	    });

	    return this;
	};
	
	
	/*******************************
	public (API) methods SETTERS
	*******************************/	
	this.setActiveItem = function(mixedData) {

	    var newItem = 0;
	    var lastItem = this._currentItem;

	    if (typeof mixedData=='string') {
		// prev/next shortcuts
		switch(mixedData) {
		    case 'previous':
			if (this.getConfig('disallowSkip')==true && !this.getState('COMPLETED') ) return this;
			newItem = this._currentItem-1;
			break;		
		    case 'next':
			if (this.getConfig('disallowSkip')==true && !this.getState('COMPLETED') ) return this;
			newItem = this._currentItem+1;
			break;
		    default:
		    case 'poster': {
			result = 0;
			break;
		    }
		}	
	    } else if (typeof mixedData=='number') {
		// index number given		
		newItem = parseInt(mixedData);
	    } else {
		// default
		newItem = 0;
	    }


	    // item change requested...
	    if (newItem!=this._currentItem) {
		// and denied... gnehe
		if (this.getConfig('disallowSkip')==true && !this.getState('COMPLETED') ) {
		    return this;
		}			
	    }    

	    this._detachplayerModel();
	    this.env.loading = false;

	    // do we have an autoplay situation?
	    var ap = false;

	    // regular "autoplay" on:
	    if (newItem===0 && (lastItem==null || lastItem==newItem) && (this.config._autoplay===true || 'DESTROYING|AWAKENING'.indexOf(this.getState())>-1) ) {		
		ap = true;
	    }
	    //  "continuous" playback?
	    else if (this.getItemCount()>1 && newItem!=lastItem && lastItem!=null && this.config._continuous===true && newItem<this.getItemCount()) {
		ap = true;
	    }
	    
	    // always "loop" playlist and disallow illegal indexes:
	    if (newItem >= this.getItemCount() || newItem<0) {
		ap = this.config._loop;
		newItem = 0;
	    }
	    
	    // set new item
	    this._currentItem = newItem;
	    
	    // reset player class
	    var wasFullscreen = this.getInFullscreen();
	    this.getDC().attr('class', this.env.className)
	    if (wasFullscreen) this.getDC().addClass('fullscreen');
	    
	    // create player instance
	    var newModel = this.media[this._currentItem].mediaModel.toUpperCase();	

	    // model does not exist or is faulty:
	    if ( !$p.models[newModel] ) {
		newModel='NA';
		this.media[this._currentItem].mediaModel = newModel;
		this.media[this._currentItem].errorCode = 8;		
	    } else {
		// apply item specific class(es) to player		
		if (this.getConfig('className')!==false) {
		    this.getDC().addClass(this.getConfig('className'));
		}	   		
	    }

	    // start model:
	    this.playerModel = new playerModel();
	    $.extend(this.playerModel, $p.models[newModel].prototype );


	    this._promote('syncing', 'display');

	    this.playerModel._init({
		media: $.extend(true, {}, this.media[this._currentItem]),
		model: newModel,
		pp: this,
		environment: $.extend(true, {}, this.env),
		autoplay: ap
		// persistent: (ap || this.config._continuous) && (newModel==nextUp)
	    });

	    return this;
	};
	
	this.getIsLastItem = function() {
	    return ( (this._currentItem==this.media.length-1) && this.config._loop!==true )
	};

	this.getIsFirstItem = function() {
	    return ( (this._currentItem==0) && this.config._loop!==true )
	};

	this.getIsMobileClient = function(what) {
	    var uagent = navigator.userAgent.toLowerCase();
	
	    var mobileAgents = ['android', "windows ce", 'blackberry', 'palm', 'mobile'];

	    for (var i=0; i<mobileAgents.length; i++) {
		if (uagent.indexOf(mobileAgents[i])>-1) {
		    // if (uagent.indexOf('webkit')>-1) return false;
		    return (what) ? (mobileAgents[i].toUpperCase()==what.toUpperCase()) : true;
		}
	    }
	    return false;	
	};
	
	/* queue ready */
	this.setPlay = function() {
	    this._enqueue('play', false);
	    return this;
	};
    
	/* queue ready */
	this.setPause = function() {
	    this._enqueue('pause', false);
	    return this;
	};
    
	/* queue ready */
	this.setStop = function(toZero) {
	    var ref = this;
	    
	    if (this.getState('IDLE'))
		return this;
	
	    if (toZero) {
		this._enqueue(function() {
		    ref._currentItem=0;		    
		    ref.setActiveItem(0);
		});
	    }
	    else
		this._enqueue('stop', false);
		
	    return this;
	};
	
	/* queue ready */
	this.setPlayPause = function() {
	    if (!this.getState('PLAYING')) {
		this.setPlay();
	    } else {
		this.setPause();
	    }
	    return this;	
	};

	/* queue ready */
	this.setVolume = function(vol, fadeDelay) {

	    if (this.getConfig('fixedVolume')==true) {
		return this;
	    }
	
	    var initalVolume = this.getVolume();
 
	    if (typeof vol == 'string') {
		var dir = vol.substr(0,1);
		vol = parseFloat(vol.substr(1));
		vol =  (vol>1) ? vol/100 : vol;
		if (dir=='+') {
		    vol = this.getVolume()+vol;
		} else if (dir=='-') {
		    vol = this.getVolume()-vol;
		} else {
		    vol = this.getVolume();
		}
	    }

	    if (typeof vol == 'number') {		
		vol =  (vol>1) ? 1 : vol;
		vol = (vol<0) ? 0 : vol;
	    } else {
		return this;
	    }
	    

	    if (vol>initalVolume && fadeDelay) {
		if (vol-initalVolume>0.03) {
		    for(var i=initalVolume; i<=vol; i=i+0.03) {
			this._enqueue('volume', i, fadeDelay);
		    }
		    this._enqueue('volume', vol, fadeDelay);
		    return this;
		}
	    }
	    else if (vol<initalVolume && fadeDelay) {
		if (initalVolume-vol>0.03) {
		    for(var i=initalVolume; i>=vol; i=i-0.03) {
			this._enqueue('volume', i, fadeDelay);
		    }
		    this._enqueue('volume', vol, fadeDelay);
		    return this;
		}
	    }
	    this._enqueue('volume', vol);
	    return this;
	};	
   
   	/* queue ready */
	this.setPlayhead = function(position) {    
	    if (this.getConfig('disallowSkip')==true) return this;	    
	    if (typeof position == 'string') {
		var dir = position.substr(0,1);
		position = parseFloat(position.substr(1));
		
		if (dir=='+') {
		    position = this.getPosition()+position;
		} else if (dir=='-') {
		    position = this.getPosition()-position;
		} else {
		    position = this.getPosition();
		}
	    }	    
	    if (typeof position == 'number') {
		this._enqueue('seek', position);
	    }	    
	    return this;
	};

	/* queue ready */
	this.setPlayerPoster = function(url) {	   
	    var ref = this;
	    this._enqueue(function() {ref.setConfig({poster:url},0);});
	    this._enqueue(function() {ref.playerModel.setPosterLive();});
	    return this;
	};
	
	// compatibility < 0.9.x
	this.setItemConfig = function() {
	    return this.setConfig(arguments);
	};
	

    
	this.setConfig = function() {
	    var ref = this, args = arguments;
	    this._enqueue(function() {ref._setConfig(args[0] || null, args[1] || null)});
	    return this;	    
	};
	
	this._setConfig = function() {

	    if (!arguments.length) {
		return result;
	    } 	    
	    
	    var confObj = arguments[0],
		dest = '*',
		value = false;
	    
	    if (typeof confObj != 'object') {
		return this;
	    }

	    if (arguments[1] == 'string' || arguments[1] == 'number') {
		dest = arguments[1];
	    } else {
		dest = this._currentItem;
	    }

	    for (var i in confObj) {
		if ($.inArray(i, this._persCfg)>-1) {
		    this._cookie(i, (typeof confObj[i]=='string') ? confObj[i] : eval(confObj[i]));
		}
		
		// is constant:
		if (this.config['_'+i]!=null) continue;
		
		try {value = eval(confObj[i]);}
		catch(e) {value = confObj[i];}

		if (dest == '*') {
		    $.each(this.media, function() {
			if (this.config == null) {
			    this.config = {};
			}
			this.config[i] = value;			
		    });
		    continue;
		}
		
		if (this.media[dest] == undefined) return this;
		
		if (this.media[dest]['config'] == null) {
		    this.media[dest]['config'] = {};
		}

		this.media[dest]['config'][i] = value;
	    }
	    return this;
	};
	
	this.setFullscreen = function(goFull) {

	    var nativeFullscreen = this.getNativeFullscreenSupport(),
		ref = this;
	
	    goFull = (goFull==null) ? !nativeFullscreen.isFullScreen() : goFull;
	    if (goFull==nativeFullscreen.isFullScreen()) return this;
	    
	    if (goFull) nativeFullscreen.requestFullScreen();
	    else nativeFullscreen.cancelFullScreen();
	    	    
            return this;
        };
	
	this.setResize = function() {
	    this._modelUpdateListener('resize');
	    return this;
	};
	
	this.setSize = function(data) {
	    
	    this.config._width = data.width || this.config._width;
	    this.config._height = data.height || this.config._height;
	    
	    if (this.getInFullscreen()===true)
		return;
	    
	    this.getDC().css({
		width: data.width+"px",
		height: data.height+"px"
	    });

	    this._modelUpdateListener('resize');
	}
	
	this.setLoop = function(value) {
	    this.config._loop = value || !this.config._loop;
	}
	
	this.setDebug = function(value) {
	    this.config._debug = value || 'console';
	}	
	
	this.addListener = function(evt, callback) {
	    var ref=this;
	    this._enqueue(function() {ref._addListener(evt, callback)});
	    return this;
	};
	this._addListener = function(evt, callback) {
	    var listenerObj = {event:evt, callback:callback}
	    this.listeners.push(listenerObj);
	    return this;
	};    

	
	/* removes an JS object from the bubble-event queue */
	this.removeListener = function(evt, callback) {
	    var len = this.listeners.length;
	    for (var i=0; i<len;i++) {
		if (this.listeners[i]==undefined) continue;
		if (this.listeners[i].event!=evt && evt!=='*') continue;	   
		if (this.listeners[i].callback!=callback && callback!=null) continue;
		this.listeners.splice(i,1);		
	    }
	    return this;
	};
	
	this.setItem = function() {
	    // arg0 -> item obj 
	    // arg1 -> position (int)
	    // arg2 -> replace (bool)
	    
	    var itemData = arguments[0];
	    var affectedIdx = 0;
	    
	    this._clearqueue();
	    if (this.env.loading===true) {
		// return this;
	    }

	    if (itemData==null) {
		// remove item
		affectedIdx = this._removeItem(arguments[1]);
		if (affectedIdx===this._currentItem) {
		    this.setActiveItem('previous');
		}
	    }
	    else {
		// add/set item
		affectedIdx = this._addItem( this._prepareMedia({file:itemData, config:itemData.config || {}}), arguments[1], arguments[2]);
		if (affectedIdx===this._currentItem) {		    
		    this.setActiveItem(this._currentItem);		  
		} 
	    }

	    return this;
	};
    
	this.setFile = function() {

	    var fileNameOrObject = arguments[0] || '',
		dataType = arguments[1] || this._getTypeFromFileExtension( fileNameOrObject ), 
		result = [];


	    if (this.env.loading===true) return this;


	    
	    if (typeof fileNameOrObject=='object' && $.isEmptyObject(fileNameOrObject)) {		
		return this;	    
	    }
	        
	    this._clearqueue();
	    this.env.loading = true;
	    this._detachplayerModel();
  
	    // incoming JSON
	    if (typeof fileNameOrObject=='object') {
		
		this._debug('Applying incoming JS Object', fileNameOrObject);
		this._reelUpdate(fileNameOrObject);
		
		return this;
	    
	    }	


	    result[0] = {};
	    result[0].file = {}
	    result[0].file.src = fileNameOrObject || '';
	    result[0].file.type = dataType || this._getTypeFromFileExtension( splt[0] ) ;
    
	    // incoming playlist
	    if (result[0].file.type.indexOf('/xml')>-1 || result[0].file.type.indexOf('/json') >-1) {

		this._debug('Loading external data from '+result[0].file.src+' supposed to be '+result[0].file.type );		    
		this._playlistServer = result[0].file.src;
		this.getFromUrl(result[0].file.src, this, '_reelUpdate', this.getConfig('reelParser'), result[0].file.type );
		
		return this;
	    }
	    
	    // incoming single file:	    
	    this._debug('Applying incoming single file:'+result[0].file.src, result);
	    this._reelUpdate(result);
	    
	    return this;
	};
	
	this.setPlaybackQuality = function(quality) {
	    
	    var options = ['small', 'medium', 'large', 'hd720', 'hd1080'];
	    if ($.inArray(quality, options)==-1) return this;
	    this.playerModel.applyCommand('quality', quality);
	    return this;
	
	};
    
	this.openUrl = function(cfg) {
	    cfg = cfg || {url:'http://www.projekktor.com', target:'', pause: false};	    
	    if (cfg.pause===true) {
		this.setPause();
	    }
	    window.open(cfg.url, cfg.target).focus();
	    return this;
	};
    
    
	/**
	* Removes THIS Projekktor and reconstructs original DOM
	*
	* ENQUEUED
	* 
	* @public
	* @return {Object} this
	*/
	this.selfDestruct = function() {
	    var ref = this;
	    this._enqueue(function() {ref._selfDestruct();});
	    return this;
	},    	
	this._selfDestruct = function() {
	    
	    var ref = this;
	    $(this).unbind();
	    this.removePlugin();
	    this._removeGUIListeners();
	
	    this.env.playerDom.replaceWith( $(this.env.srcNode).clone() );
	    
	    $.each(projekktors, function(idx) {
		try {
		    if (this.getId() == ref.getId() || this.getId() == ref.getId() || this.getParent() == ref.getId())  {
			projekktors.splice(idx, 1);
			return;
		    }
		} catch(e){}
	    });	    
	    return this;
	}
	
	/**
	* @public
	* @return {Object} this
	*/
	this.reset = function() {
	    var ref = this;
	    this._clearqueue();
	    this._enqueue(function() {ref._reset();});
	    return this;
	},
	
	this._reset = function() {

	    var cleanConfig = {},
		ref = this;
	    
	    $(this).unbind();
	    this.setFullscreen(false);
	    this.removePlugin();
	    this._removeGUIListeners();
	    this.env.mediaContainer = null;
	    for (var i in this.config) {
		cleanConfig[(i.substr(0,1)=='_') ? i.substr(1) : i] = this.config[i];
	    }
	  
	    if (typeof this.env.onReady==='function') {
		this._enqueue(ref.env.onReady(ref));
	    }
	    
	    this._init(this.env.playerDom, cleanConfig);	    		
	    return this;
	},
    
    
 	/********************************************************************************************
		Queue Points
	*********************************************************************************************/
		
	this.setCuePoint = function(obj) {
	    var item = obj.item || this.getItemIdx(),
		ref = this,
		cuePoint = {
		    id: obj.id || $p.utils.randomId(8),
		    group: obj.group || $p.utils.randomId(8),
		    item: item,
		    on: $p.utils.toSeconds(obj.on) || 0,
		    off: $p.utils.toSeconds(obj.off) || 86400,
		    value: obj.value || null,
		    callback: obj.callback || function(){},

		    _active: false,
		    _lastTime: 0,
		    _stateListener: function(state, player) {
			if (state=='STOPPED') {
			    if (this._active)
				this.callback(false, this, player);
			    this._active = false;			
			}
			
		    },
		    _timeListener: function(time, player) {
			var timeIdx = $p.utils.roundNumber(time, 2);

			// something to do?
			if (this._lastTime==timeIdx)
			    return;
			
			var nat = !(timeIdx <= this._lastTime+1);

			// trigger ON
			if ( (timeIdx >= this.on && timeIdx < this.off) && !this._active) {			    		    
			    this._active = true;
			    try { this.callback(true, this.value, nat, player); } catch(e) {}
			}
			// trigger OFF
			else if ( (timeIdx < this.on || timeIdx > this.off) && this._active) {
			    if (this._active==true) {				
				this._active = false;
				try { this.callback(false, this.value, nat, player); } catch(e) {}
			    }
			}
			
			this._lastTime = timeIdx;			
		    } 
		}
		
	    // create itemidx key
	    if (this._cuePoints[item]==null)
		this._cuePoints[item] = [];
		
	    this._cuePoints[item].push(cuePoint);
	    return this;   
	    
	},
	
	this.getCuePoints = function(idx) {	    
	    return $.extend({}, this._cuePoints[idx || this._currentItem], this._cuePoints['*']);
	},
    

	this.removeCuePoint = function(group, idx) {
	    
	    var cuePoints = this.getCuePoints(idx) || [];
	    if (cuePoints.length==0) return;

	    for (var j=0; j<cuePoints.length; j++){
		
		    if (cuePoints[j].group===group) {
			this.removeListener('time', cuePoints[j].timeEventHandler);	    
			this.removeListener('state', cuePoints[j].stateEventHandler);
			cuePoints.splice(j, 1);			
		    }		
	    }

	},	
	
	this._applyCuePoints = function() {
	    	    
	    var ref = this;


	    if (this._cuePoints[this._currentItem]==null && this._cuePoints['*']==null)
		return;

	    $.each( $.merge(this._cuePoints[this._currentItem] || [], this._cuePoints['*'] || []), function(key, cuePointObj) {

		cuePointObj.timeEventHandler = function(time, player) {
		    try {cuePointObj._timeListener(time, player);} catch(e){}
		},
		
		cuePointObj.stateEventHandler = function(state, player) {
		    try {cuePointObj._stateListener(state, player);} catch(e){}
		},
		
				
		ref.addListener('time', cuePointObj.timeEventHandler);	    
		ref.addListener('state', cuePointObj.stateEventHandler);
		
		ref.addListener('item', function() {
		    ref.removeListener('time', cuePointObj.timeEventHandler);	    
		    ref.removeListener('state', cuePointObj.stateEventHandler);
		});
		
	    })
    
	},
	
    
 	/********************************************************************************************
		Command Queue
	*********************************************************************************************/   
	this._enqueue = function(command, params, delay)  {
	    if (command==null) return;
	    this._queue.push({command:command, params:params, delay:delay});
	    this._processQueue();
	};
	
	this._clearqueue = function(command, params)  {
	    if (this._isReady!==true) return;
	    this._queue = [];
	};
	
	this._processQueue = function() {
	    var ref = this, modelReady = false;
	    if (this._processing===true) return;
	    if (this.env.loading===true) return;
	    this._processing = true;
	    
	    (function() {		
		try {modelReady=ref.playerModel.getIsReady();} catch(e) {}
		if (ref.env.loading!==true && modelReady) {
		    
		    try {
			var msg = ref._queue.shift();
			if (msg!=null) {
			    if (typeof msg.command=='string') {
				if (msg.delay>0)
				    setTimeout(function() {
					ref.playerModel.applyCommand(msg.command, msg.params);
				    }, msg.delay);
				else
				    ref.playerModel.applyCommand(msg.command, msg.params);
			    } else {
				msg.command(ref);
			    }
			}
		    } catch(e) {}

		    if (ref._queue.length==0){			
			if (ref._isReady===false ) {			    
			    // ref._promote('ready', ref.getItemIdx());
			    ref._isReady=true;
			}
			ref._processing = false;
			return;
		    }
		    
		    arguments.callee();
		    return;
		}
		setTimeout(arguments.callee,100);
	    })();		
	}
	    
    	
	/********************************************************************************************
		GENERAL Tools
	*********************************************************************************************/	
	this._cookie = function (key, value) {
    
	    // iphone will fail if you try to set a cookie this way:
	    if (document.cookie===undefined) return null;
	    if (document.cookie===false) return null;
	    if (key==null) return null;


	    // set cookie:
	    if (arguments.length > 1 && (value === null || typeof value !== "object")) {		
		var t = new Date();
		t.setDate(t.getDate() + this.config._cookieExpiry);

		return (document.cookie = 
		    encodeURIComponent(this.config._cookieName+"_"+key)+'='
		    +encodeURIComponent(value)
		    +'; expires=' + t.toUTCString()
		    +'; path=/'
		    // +options.domain ? '; domain=' + options.domain : '',
		    // +options.secure ? '; secure' : ''
		);
	    }

	    // get cookie data:	    
	    var result,
		returnthis = (result = new RegExp('(?:^|; )' + encodeURIComponent(this.config._cookieName+"_"+key) + '=([^;]*)').exec(document.cookie)) ? decodeURIComponent(result[1]) : null;

	    return (returnthis=='true' || returnthis=='false') ? eval(returnthis) : returnthis;
	    
	};	
	
	
	this._getTypeFromFileExtension = function(url) {
    
	    var fileExt = '', extRegEx = [], extTypes = {}, extRegEx = []	
	    
	    // build regex string and filter dublicate extensions:	    
	    for(var i in $p.mmap ) {
		extRegEx.push( '.'+$p.mmap [i].ext );
		extTypes[$p.mmap [i].ext] = $p.mmap [i];		
	    }
	    extRegEx = '^.*\.('+extRegEx.join('|')+")";		    

	    try {
		fileExt = url.match( new RegExp(extRegEx))[1];
		fileExt = (!fileExt) ? 'NaN' : fileExt.replace('.','');
	    } catch(e) { fileExt='NaN'; }	

	    return extTypes[fileExt].type;
	    
	};
	
	/* generates an array of mediatype=>playertype relations depending on browser capabilities */
	this._testMediaSupport = function() {
	    
	    var result = {all:[], media:[]};

	    for (var i=0; i < $p.mmap.length; i++ ) {         	    
				
		if ( $.inArray($p.mmap[i]['type'], result.all )>-1 )
		    continue;
		
		// we love all and us:
		if ('all|internal'.indexOf($p.mmap[i]['platform'])>-1) {
		    result.all.push($p.mmap[i]['type']);
		    continue;
		}
		
		// we hate flash:
		if ('flash'.indexOf($p.mmap[i]['platform'])>-1) {
		    if (this.getFlashVersion()<1 || !this.getConfig('enableFlashFallback')) continue;
		    result.all.push($p.mmap[i]['type']);
		    continue;
		}		

		// check if we can handle this with html5 
		if ($p.mmap[i]['fixed']!==true) {
		    if (($p.mmap[i]['type'].indexOf('video')>-1 || $p.mmap[i]['type'].indexOf('audio')>-1)) {
			try {        				
			    var testObject = document.createElement( ($p.mmap [i]['type'].indexOf('video')>-1) ? 'video' : 'audio' );
			    if (testObject.canPlayType!=false) {
				switch ( testObject.canPlayType($p.mmap[i]['type']) ) {
				    case "no":
				    case "":
					break;
					
				    // optimizm now:
				    case "maybe":
					if ($.browser.opera) {
					    if ($.browser.version.slice(0,2)<11) break;
					}
				    case "probably":
				    default:
					result.all.push($p.mmap[i]['type']);
					result.media.push($p.mmap[i]['type']);
				}
			    }    		
			} catch (e) {}
		    }
		}
	    }

	    return result;	    
	};    
		
	    
	this._debug = function(desc, data) {	
	    
	    // disabled
	    if (this.config._debug===false) return;
    
	    // debug to console
	    if (this.config._debug=='console') {
		try {
		    if (desc) console.log(desc);
		    if (data) console.log(data);
		}catch(e){}
		return;
	    }
	    
	    // debug to dom container
	    var result = '<pre><b>'+desc+"</b>\n";
	    
	    // textify objects 
		if (data && this.config.debugLevel>1) {
		switch(typeof data){
		    case 'undefined':
			break;
		    case 'object':
			var temp = '';
			// temp =this.parseMyJSON(data);
			if (temp=='') {
			    temp = '';
			    for(var i in data){
				temp += i+' : '+data[i]+"\n";
			    }
			}
			result += temp;
			break;
		    case 'string':
			result += data;
		}
		result += '</pre>';
	    }

	    try {$('#'+this.config._debug).prepend(result);}catch(e){}
	};
	
	this._raiseError = function(txt) {
	    this.env.playerDom
		.html(txt)
		.css({
		    color: '#fdfdfd',
		    backgroundColor: '#333',
		    lineHeight: this.config.height+"px",
		    textAlign: 'center',
		    display: 'block'
		    
		});
		this._promote('error');
	};
	
	/* create a projekktor config set from a given mediatag */
	this._readMediaTag = function(domNode) {
	    
	    var result = {},
		htmlTag='',
		attr=[],
		ref=this;
	    
	    if(domNode[0].tagName.toUpperCase() != 'VIDEO' && domNode[0].tagName.toUpperCase() != 'AUDIO')
		return false;
				
	    
	    // gather general config attributes:
	    // - Safari does not supply default-bools here:
	    if (!this.getConfig('ignoreAttributes')) {
		result = {
		    autoplay: (domNode.prop('autoplay')!==undefined && domNode.prop('autoplay')!==false) ? true : false,
		    controls: (domNode.prop('controls')!==undefined && domNode.prop('controls')!==false) ? true : false,
		    loop: (domNode.prop('loop')!==undefined && domNode.prop('loop')!==false) ? true : false,
		    title: (domNode.attr('title')!==undefined && domNode.attr('title')!==false) ? domNode.attr('title') : '',
		    poster: (domNode.attr('poster')!==undefined && domNode.attr('poster')!==false) ? domNode.attr('poster') : '',
		    width: (domNode.attr('width')!==undefined && domNode.attr('width')!==false) ? domNode.attr('width') : false,
		    height: (domNode.attr('height')!==undefined && domNode.attr('height')!==false) ? domNode.attr('height') : false
		};
	    }

	    // IE7+8 does not keep attributes w/o values:
	    if ($.browser.msie) {
		htmlTag = $($('<div></div>').html($(domNode).clone())).html();		
		attr = ['autoplay', 'controls', 'loop'];
		
		for (var i=0; i<attr.length; i++) {
		    if (htmlTag.indexOf(attr[i])==-1) continue;
		    result[attr[i]] = true;
		}
	    }	    

	    // avoid strange overlap-effects in FF:
	    domNode.prop('autoplay', false);	
	    
	    // get possible media sources:
	    result.playlist = [];
	    result.playlist[0] = [];
	    
	    // ... from "src" attribute:
	    if (srcNode.attr('src')) {
		result.playlist[0].push({
		    src: srcNode.attr('src'),
		    type: srcNode.attr('type') || this._getTypeFromFileExtension(srcNode.attr('src'))
		});            
	    } 	
		

	    // ... from media tag´s children
	    if ($.browser.msie && $.browser.version < 9) {		
		
		// ... within a lame browser ...
		var childNode = srcNode;
		do {
		    
		    childNode = childNode.next('source');
		    if (childNode.attr('src')) {
			result.playlist[0].push({
			    src: childNode.attr('src'),
			    type: childNode.attr('type') || this._getTypeFromFileExtension(childNode.attr('src'))
			});
		    } 			
		} while (childNode.attr('src'))
		
	    } else {
		
		// ... within a good browser ...
		srcNode.children('source').each( function(){
		    if ($(this).attr('src')) {
			result.playlist[0].push({
			    src: $(this).attr('src'),
			    type: $(this).attr('type') || ref._getTypeFromFileExtension($(this).attr('src'))
			});
		    }                        
		});
	    }
    
	    // detach source
	    try {
		domNode[0].pause()
		domNode.find('source').remove();
		domNode.prop('src', '');
		domNode[0].load();
	    } catch(e) {}


	    return result;
	};	
	
	this._applyDimensions = function() {

	    // trim dimension configs
	    if (this.config._height!==false && this.config._width!==false) {

		if (this.config._width<=this.config._minWidth && this.config._iframe!=true) {
		    this.config._width = this.config._minWidth;
		    this.env.autoSize = true;
		}	    

		if (this.config._height<=this.config._minHeight && this.config._iframe!=true) {
		    this.config._height = this.config._minHeight;
		    this.env.autoSize = true;
		}
	    }
	    
	    this.env.playerDom		
		.css({
		    overflow: 'hidden',
		    'max-width': '100%'
		});
	    
	    if (this.config._height!==false)
		this.env.playerDom.css('height', this.config._height+"px")

	    if (this.config._width!==false)
	        this.env.playerDom.css('width', this.config._width+"px")
	    
	};
	
	this._init = function(customNode, customCfg) {
	    
	    var theNode = customNode || srcNode,
		theCfg = customCfg || cfg,
		cfgBySource = this._readMediaTag(theNode);


	    // remember original node for reset and reference purposes:
	    this.env.srcNode = $.extend(true, {}, theNode);
	    
	    // remember iinitial classes
	    this.env.className = theNode.attr('class');


	    // -----------------------------------------------------------------------------
	    // - 1. GENERAL CONFIG ---------------------------------------------------------
	    // -----------------------------------------------------------------------------
	    if (cfgBySource!==false) {
		// swap videotag->playercontainer
		this.env.playerDom = $(document.createElement('div'))
		    .attr({			
			'class': theNode[0].className,
			'style': theNode.attr('style')
		    })
		theNode.replaceWith( this.env.playerDom );
	    }
	    else {
		cfgBySource = {
		    width: (theNode.attr('width')) ? theNode.attr('width') : theNode.width(),
		    height: (theNode.attr('height')) ? theNode.attr('height') : theNode.height()
		};
		this.env.playerDom = theNode;
	    }
	
	
	    // merge configs so far:
	    theCfg = $.extend(true, {}, cfgBySource, theCfg)
	    for (var i in theCfg) {
		if (this.config['_'+i]!=undefined) {
		    this.config['_'+i] = theCfg[i];
		} else {
		    this.config[i] = theCfg[i];
		}		
	    }
	    	    
	    // force autoplay false on mobile devices:
	    if  (this.getIsMobileClient()) {
		this.config._autoplay = false;
		this.config.fixedVolume = true;
	    }	   		    
		    
	    
	    // -----------------------------------------------------------------------------
	    // - 2. TRIM DEST --------------------------------------------------------------
	    // -----------------------------------------------------------------------------	    

	    // make sure we can deal with a domID here:
	    this._id = theNode[0].id || $p.utils.randomId(8);
	    this.env.playerDom.attr('id', this._id);
	    
	    	    
	    
	    // -----------------------------------------------------------------------------
	    // - 3. INIT THEME LOADER ------------------------------------------------------
	    // -----------------------------------------------------------------------------
	    if (this.config._theme) {
		switch(typeof this.config._theme) {
		    case 'string':
			// this.getFromUrl(this.parseTemplate(this.config._themeRepo, {id:this.config._theme, ver:this.config._version}), this, "_applyTheme", false, 'jsonp');
			break;
		    case 'object':
			this._applyTheme(this.config._theme)
		    
		}
	    }
	    else {		
		this._start(false);
	    }

	    return this;
	
	};
	
	
	this._start = function(data) {
  
	    var ref = this, files=[];
	    	    
	    
	    // -----------------------------------------------------------------------------
	    // - 5. FINAL STEPS ------------------------------------------------------------
	    // -----------------------------------------------------------------------------
	    this._applyDimensions();	    
	    
	    // pubish version info
	    try {$('#projekktorver').html("V"+this.config._version);} catch(e){};

	    // load and initialize plugins´
	    this._registerPlugins();		    

	    // set up iframe environment
	    if (this.config._iframe===true) {		
		// wait for parent window:
		if (this.getIframeWindow()) {
		    this.getIframeWindow().ready(function() {
			ref._enterFullViewport(true);
		    });
		} else {
		    ref._enterFullViewport(true);
		    this.config.disableFullscreen = true;
		}
	    }
	    
	    if (typeof onReady==='function') {
		this._enqueue(function() {onReady(ref);});
	    }		    

	    // playlist?
	    for (var i in this.config._playlist[0]) {			   
		// we prefer playlists - search one:
		if (this.config._playlist[0][i].type) {
		    if (this.config._playlist[0][i].type.indexOf('/json')>-1 || this.config._playlist[0][i].type.indexOf('/xml')>-1 ) {
			this.setFile(this.config._playlist[0][i].src, this.config._playlist[0][i].type); 
			return this;
		    }
		}
	    }

	    this.setFile(this.config._playlist);
	    return this;
	};
	
	this._applyTheme = function(data) {

	    var ref = this;
	    
	    // theme could not be loaded => error
	    if (data===false) {
		this._raiseError('The Projekktor theme-set specified could not be loaded.')
		return false;
	    }

	    /*
    	    // check projekktor version	    
	    if (typeof data. == 'string') {
		if (
		    (parseInt(this.config._version.split('.')[0]) || 0) < (parseInt(data.version.split('.')[0]) || 0) ||
		    (parseInt(this.config._version.split('.')[1]) || 0) < (parseInt(data.version.split('.')[1]) || 0)
		){
		    this._raiseError('You are using Projekktor V'+this.config._version+' but the applied theme requires at least V'+data.version+'.');
		    return false;
		}
	    }
	    */
	    
	    
	    // inject CSS & parse {relpath} tag (sprites)
	    if (typeof data.css == 'string') {
		$('head').append('<style type="text/css">' + $p.utils.parseTemplate(data.css, {'rp':data.baseURL}) + '</style>');
	    }

	    // apply html template
	    if (typeof data.html=='string') {
		this.env.playerDom.html( $p.utils.parseTemplate(data.html, {'p':this.config._cssClassPrefix}) );		
	    }
	
	    // apply class
	    this.env.playerDom.addClass(data.id).addClass(data.variation);

	    if (typeof data.config=='object') {
		for (var i in data.config) {
		    if (this.config['_'+i]!=undefined) {
			this.config['_'+i] = data.config[i];
		    } else {
			this.config[i] = data.config[i];
		    }		
		}
		
		// check dependencies
		if (typeof data.config.plugins == 'object' ) {
		    for (var i=0; i<data.config.plugins.length; i++) {
			try {
			    typeof eval('projekktor'+data.config.plugins[i]);
			}
			catch(e) {			
			    this._raiseError('The applied theme requires the following Projekktor plugin(s): <b>'+data.config.plugins.join(', ')+'</b>');
			    return false;
			}
		    }
		}		
	    }

	    if (data.onReady) {
		this._enqueue(function(player){eval(data.onReady);});
	    }
	    
	    return this._start();
	};
	
	
	return this._init();    
    };
    
}

$p.mmap = [];
$p.models = {};
$p.newModel = function(obj, ext) {
    

    var result = false,
	extend = ($p.models[ext] && ext!=undefined) ? $p.models[ext].prototype : {};

    if (typeof obj!='object' ) return result;
    if (!obj.modelId) return result;
    if ($p.models[obj.modelId]) return result;

    /* register new model */
    $p.models[obj.modelId] = function(){};
    $p.models[obj.modelId].prototype = $.extend({}, extend, obj);

    /* add modelname to media map object */
    for (var i=0; i< obj.iLove.length; i++) {
	obj.iLove[i].model = obj.modelId.toLowerCase();	
	$p.mmap.push( obj.iLove[i] );
    }
    return true; 
}

});
var projekktorConfig = function(ver){this._version = ver;};

/*
 * this file is part of: 
 * projekktor zwei
 * http://www.projekktor.com
 *
 * Copyright 2010, 2011, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 * under GNU General Public License
 * http://www.filenew.org/projekktor/license/
*/
projekktorConfig.prototype = {	
	
	
	/**************************************************************
	    Config options to be customized prior initialization only:
	***************************************************************/
	
	/* sets name of the cookie to store playerinformation in */
	_cookieName:			'qwprojaaekktor',
	
	/* days to keep cookie alive */
	_cookieExpiry:			356,	    
	
	/* Plugins to load on instance initialization, plugins are automatically extening the projekktorPluginInterface class.
	The order how the plugins are set here is important because they are added from z-index 0 to n one by one to the player DOM.
	As such it is usefull to add the "Display" plugin always first.
	*/
	_plugins: 			['display', 'controlbar'],
	
	/* Add one plugin or more plugins to the player. Alternative to "plugins" above. Will be merged with it. */
	_addplugins: 			[],	
	
	/* custom reel parser (data:JSObject), default function(data){return data;} */
	_reelParser:			null,
	
	/* Prefix prepended to all css-Classnames used by the player in order to avoid conflicts with existing site layouts */	
	_cssClassPrefix: 		'pp',
	
	/* set if to prefer native video before flash or vise versa */
	_platformPriority:		['native', 'flash'],
	
	/*
	Firefox requires special treatment whenever flash objects are
	resized while any parent element gets a new "overflow" value.
	You can force to skip projekktor¥s workaround by setting the following var to "true"
	*/
	_bypassFlashFFFix:		false,	
	
	/* if set to true, projekktor assumes to live within an iframe and will act accordingly (used for embedding) */
	_iframe: 			false,
	
	/* if set to true projekktor will discard native media tag attributes (loop,controls,autoplay,preload etc.pp) */	  
	_ignoreAttributes:		false,
		
	/* looping scheduled media elements -  will be overwritten by loop-attribute of a replaced <video> tag. */
	_loop: 				false,
	
	/* automatically start playback once page has loaded -  will be overwritten by autoplay-attribute of a replaced <video> tag. */
	_autoplay: 			false,
	
	/* if more than one item is scheduled, true will automatically start playback of the next item in line once current one completed */	
	_continuous:			true,

	/* An array of items to be played. Check http://www.projekktor.com/docs/playlists to learn more */
	_playlist: 			[],
	
	_theme:				false,

	/*'http://www.projekktorxl.com/themegen/api/themes/live/format/jsonp/id/%{id}/version/%{ver}',*/	
	_themeRepo:			false, 
	
	/* all error messages waiting for your translation */
	_messages: 	{
	    
	    /* flash & native: */
	    0:'An error occurred.',
	    1:'You aborted the media playback. ',
	    2:'A network error caused the media download to fail part-way. ',
	    3:'The media playback was aborted due to a corruption problem. ',
	    4:'The media (%{file}) could not be loaded because the server or network failed.',
	    5:'Sorry, your browser does not support the media format of the requested file (%{type}).',
	    6:'Your client is in lack of the Flash Plugin V%{flashver} or higher.',
	    7:'No media scheduled.',
	    8: '! Invalid media model configured !',
	    9: 'File (%{file}) not found.',
	    97:'No media scheduled.',
	    98:'Invalid or malformed playlist data!',
	    99:'Click display to proceed. ',
	    
	    /* youtube errors: */
	    500: 'This Youtube video has been removed or set to private',
	    501: 'The Youtube user owning this video disabled embedding.',
	    502: 'Invalid Youtube Video-Id specified.'
	},
	
	/* false => OFF, console=>console.log, <string>=>ID of DOMcontainer to pump text into */
	_debug: 			false,
	
	/* the width of the player - 0= use destNode¥s width */
	_width: 			0,
	
	/* guess what.... the hight of the player - 0 = use destNode¥s width */
	_height:			0,
	
	
	/* if height is <=0 use this to scale the player to a minimum height
	if minHeight is actually applied, autorescaling is enabled 
	*/
	_minHeight:			40,	    

	/* if width is <=0 use this to scale the player to a minimum width
	if minWidth is actually applied, autorescaling is enabled
	*/
	_minWidth:			40,	
	
	
	/**************************************************************
	    Config options available per playlist item:
	***************************************************************/
	
	/* unique itemID for the item currently played - dynamically generated if not provided via config */
	ID:				0,
	
	/* a title is a title is a title */	
	title:				null,
		
	/* URL to poster image -  will be overwritten by poster-attribute of the replaced media tag. */
	poster: 			false,			
	
	/* enable/disable controls -  will be overwritten by controls-attribute of the replaced <video> tag. */
	controls: 			false,
	
	/* start offset in seconds for randomly seekable media. (EXPERIMENTAL) */
	start: 				false,

	/* stop endpoint in seconds for randomly seekable media. (EXPERIMENTAL) */
	stop: 				false,			
	
	/* initial volume on player-startup, 0=muted, 1=max */
	volume: 			0.5,
	
	/* a cover which will fill the display on audio-only playback */
	cover: 				'',	    
			
	/* enable/disable the possibility to PAUSE the video once playback started. */
	disablePause:			false,
	
	/* enable/disable the possibility to skip the video by hitting NEXT or using the SCRUBBER */
	disallowSkip:			false,
	
	/* if set to TRUE users can not change the volume of the player - neither via API nor through controls */
	fixedVolume:			false,
	
	/* scaling used for images (playlist items and posters) "fill", "aspectratio" or "none" */
	imageScaling:			'aspectratio',
	
	/* scaling used for videos (flash and native, not youtube) "fill", "aspectratio" or "none" */
	videoScaling:			'aspectratio',
		
	/* path to the MP4 Flash-player fallback component */
	playerFlashMP4:			'jarisplayer.swf',
	    
	/* path to the MP3 Flash-player fallback component */
	playerFlashMP3:			'jarisplayer.swf',		
			
	/* defines the streamtype of the current item.
	    'file': 		progressive download streaming (http, default)
	    'http':		http pseudo streaming
	    'rtmp':		RTMP streaming - requires "flashRTMPServer" to be set.
	*/
	flashStreamType:		'file',
	
	/* it flashStreamType is 'rtmp' you have to provide the serverURL here. */
	flashRTMPServer:		'',	    		    
		
	/* Youtube offers two different player APIs: fLaSh and "iFrame" for HTML5 . Make your choice here:
	  For mobile devices this is forced to TRUE
	*/
	useYTIframeAPI:			true,
	
	/* enable/disable automatic flash fallback */
	enableFlashFallback:		true,
	
	/* enable/disable native players */
	enableNativePlayback:		true,	
	
	/* enable/disable fetching of keyboard events - works in "fullscreen" only */
	enableKeyboard:			true,		
	
	/* enable/disable the possibility to toggle to FULLSCREEN mode */
	enableFullscreen: 		true,
	
	/*  experimental - youtube only so far - 'small', 'medium', 'large', 'hd720', 'hd1080' */
	playbackQuality: 		'medium',
	
	/* if testcard is disabled, the player will force a filedowload in case no native- or flashplayer
	is available. oterhwise (enableTestcard=true) a testcard with an errormessage is shown in case of issues */
	enableTestcard:			true,
	
	/* if the scheduled playlist holds more than one item an "skipTestcard" is set to TRUE in case of an error
	the player will proceed to the next item without showing a testcard */
	skipTestcard:			false,		
      	
	/* sets the duration for media items without a duration (images & html pages) */
	duration:			0,
	
	/* sets the player¥s additional CSS class */
	className:			''
};

/*
 * this file is part of: 
 * projekktor zwei
 * http://www.projekktor.com
 *
 * Copyright 2010, 2011, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 * under GNU General Public License
 * http://www.filenew.org/projekktor/license/
*/
var playerModel = function(){};
jQuery(function($) {
playerModel.prototype = {   
    
    modelId: 'player',
    iLove: [],
    
    // all the player states
    _currentState: null,
    _currentBufferState: null,
    
    _ap: false, // autplay
    _volume: 0, // async
    
    _displayReady: false,

    _id: null,
    
    // experimental
    _KbPerSec: 0,    
    _bandWidthTimer: null,
    
    // flags
    _isPoster: false,
    
    requiresFlash: false,
    hasGUI: false,

    allowRandomSeek: false,
    flashVerifyMethod: 'api_get',  
    mediaElement: null,
    
    pp: {},    
        
    media: {
        duration: 0,
        position: 0,
        startOffset: 0,        
        file: false,
        poster: '',
        ended: false,
        message:'',
        error: null ,
	loadProgress:0,
	errorCode:0,
	message:'',
	type:'NA'
    },

    /*******************************
            CORE
    *******************************/       
    _init: function(params){
	this.pp = params.pp || null;
        this.media = params.media || this.media;
	this._ap = params.autoplay;
	this._id = $p.utils.randomId(8);
	this._volume = this.pp.getVolume('volume');
	this._playbackQuality = this.pp.getPlaybackQuality();
	this.init();

    },
    
    init: function(params) {
	this.ready();
    },
    
    ready: function() {
	this.sendUpdate('modelReady');
	if (this._ap) this._setState('awakening');
	else this.displayItem(false);
    },
    
    /* apply poster while sleeping or get ready for true multi media action */
    displayItem: function(showMedia) {
	
	// poster 
	if (showMedia!==true || this.getState('STOPPED') ) {
	   
	    this._setState('idle');
	    this.applyImage(this.getPoster(), this.pp.getMediaContainer().html(''));	        
	    this._isPoster = true;	    
	    this.displayReady();
	    return;
	} 

	// media
	$('#'+this.pp.getMediaId()+"_image").remove();
	
	// a very dirty hack:	
	if (this.hasGUI) {
	    this.pp.env.playerDom.children().not('.ppdisplay').addClass('inactive').removeClass('active');
	}
	
	
	// display "you need to install flash"-message
	if (this.requiresFlash!==false) {
	    if (this.requiresFlash>this.pp.getFlashVersion()) {		    
		this.setTestcard(6);
		return;
	    }
	}

	// bring up media element.
	this._displayReady = false;
	this._isPoster = false;
    
	// remove testcard (if any)
	$("#"+this.pp.getId()+'_testcard_media').remove();

	this.applyMedia(this.pp.getMediaContainer());	
    },
    
    applyMedia: function() {},
    
    sendUpdate: function(type, value) {
	this.pp._modelUpdateListener(type, value);
    },
    
    /* wait for the playback element to initialize */    
    displayReady: function(){
	this._displayReady = true;
	this.pp._modelUpdateListener('displayReady');
    },
    
    start: function() {

	var ref = this;

		    

	if (this.mediaElement==null && this.modelId!='PLAYLIST') return;
	if (this.getState('STARTING')) return;

	this._setState('STARTING');

	if (!this.getState('STOPPED') ) 
	    this.addListeners();
	
	if (this.pp.getIsMobileClient('ANDROID') && !this.getState('PLAYING')) {
	    setTimeout(function() {ref.setPlay()}, 500);
	}
	
	this.setPlay();

    },
    
        
    addListeners: function() {},
    
    removeListeners: function() {
	try {this.mediaElement.unbind('.projekktor'+this.pp.getId());} catch(e){};
    }, 
        
    detachMedia: function() {},            

    destroy: function() {
	this.removeListeners();    	
	this._setState('destroying');
	// this.setPause();    	
        this.detachMedia();
	
	/*
	if (this._persistent!==true) {
	    try {$('#'+this.mediaElement.id).empty();}catch(e){}
	    try {$('#'+this.mediaElement.id).remove();}catch(e){}
	    try {this.mediaElement.remove();}catch(e){}			        
	    this.pp.getMediaContainer().html('');
	    this.mediaElement = null;
	}
	*/
		
	this.media.loadProgress = 0;
	this.media.playProgress = 0;
	this.media.position = 0;
	this.media.duration = 0;
    },
        
    /* firefox reinit-issue-workaround-helper-thingy */
    reInit: function() {

	// no FF:
	if (this.requiresFlash===false || !($.browser.mozilla) || this.getState('ERROR') || this.pp.getConfig('bypassFlashFFFix')===true) {
	    return;
	}

	// elsewise nuke:
	this.sendUpdate('FFreinit');
        this.removeListeners();
 	this.displayItem((!this.getState('IDLE')));
    },
        
    applyCommand: function(command, value) {	
        switch(command) {
	    case 'quality':		
		this.setQuality(value);
		break;
            case 'play':
		if (this.getState('ERROR')) break;				
		if (this.getState('IDLE')) {
		    this._setState('awakening');
		    break;
		}		
                this.setPlay();
                break;
            case 'pause':
		if (this.getState('ERROR')) break;		
                this.setPause();
                break;
            case 'volume':
		if (this.getState('ERROR')) break;
                if (!this.setVolume(value)) {
		    this._volume = value;
		    this.sendUpdate('volume', value);
		}
                break;
	    case 'stop':
		this.setStop();
		break;
            case 'seek':
		if (this.getState('ERROR')) break;		
		if (this.media.loadProgress==-1 ) break;
                this.setSeek(value);
		this.sendUpdate('seek', value);
                break;
            case 'fullscreen':
		this.sendUpdate('fullscreen', value);
                this.setFullscreen(value);		
		this.reInit();    		
                break;
	    case 'resize':
		this.setResize();
		this.sendUpdate('resize', value);
		break;
        }        
    },   
         
    /*******************************
            ELEMENT SETTERS 
    *******************************/               
    setSeek: function(newpos) {},

    setPlay: function() {},
    
    setPause: function() {},
    
    setStop: function() {
	this.detachMedia();
	this._setState('stopped');
	this.displayItem(false);
    },
            
    setVolume: function(volume) {},   
    
    setFullscreen: function(inFullscreen) {},

    setResize: function() {},
    
    setPosterLive: function() {},
    
    setQuality: function() {},
    
    
    /*******************************
            ELEMENT GETTERS 
    *******************************/             
    getVolume: function() {
	if (this.mediaElement==null)
	    return this._volume;
	
        return (this.mediaElement.prop('muted')==true) ? 0 : this.mediaElement.prop('volume');        
    },

    getLoadProgress: function() {
        return this.media.loadProgress || 0;        
    },
    
    getLoadPlaybackProgress: function() {
        return this.media.playProgress || 0;        
    },

    getPosition: function() {
        return this.media.position || 0;          
    },    

    getDuration: function() {
        return this.media.duration  || 0;   
    },
    
    getPlaybackQuality: function() {
	return false;
    },
    
    getInFullscreen: function() {        
        return this.pp.getInFullscreen();
    },    
    
    getKbPerSec: function() {
	return this._KbPerSec;
    },
    
    getState: function(isThis) {	
	var result = (this._currentState==null) ? 'IDLE' : this._currentState;
	if (isThis!=null) return (result==isThis.toUpperCase());
	return result;
    },
    
    getFile: function() {
	return this.media.file || null;
    },

    getModelName: function() {
	return this.modelId || null;
    },
    
    getHasGUI: function() {
	return (this.hasGUI && !this._isPoster);
    },
    
    getIsReady: function() {
	return this._displayReady;
    },
    
    getPoster: function() {
	return this.pp.getConfig('poster');
    },
    
    getMediaElement: function() {
	return this.mediaElement || $('<video/>');
    },

    getIsLastItem: function() {
            return ( (this._currentItem==this.media.length-1) && this.config._loop!==true )
    },
    
    
    /*******************************
          ELEMENT LISTENERS
    *******************************/    
    timeListener: function(obj) {

	if (obj==undefined) return;
        	
	var current = parseFloat( (obj.position!=undefined) ? obj.position : obj.currentTime ),
	    total = parseFloat( obj.duration ),
	    progress = parseFloat( (current>0 && total>0) ? current * 100 / total : 0 );

	// bypass strange IE flash bug	
	if ( isNaN(total+current+progress) ) return;
	
    	this.media.duration = total;
    	this.media.position = current;
        this.media.playProgress = progress;
	
	this.sendUpdate('time', this.media.position);
	this.loadProgressUpdate();	
    },

    loadProgressUpdate: function() {

	try {
	    var me =  this.mediaElement[0];
	    
	    if (typeof me.buffered!=='object') return;
	    if (typeof me.buffered.length<=0) return;

	    var endRounded = Math.round(me.buffered.end( me.buffered.length-1)*100)/100,
		progress = endRounded*100 / this.media.duration;

	    if (progress==this.media.loadProgress) return;
	    this.media.loadProgress = (this.allowRandomSeek===true) ? 100 : -1;
	    this.media.loadProgress = (this.media.loadProgress<100 || this.media.loadProgress==undefined) ? progress : 100;

	    this.sendUpdate('progress', this.media.loadProgress);
	} catch(e){};
    },

    progressListener: function(evt, obj) {

	// we prefer timeranges but keep catching "progress" events
	// for historical and compatibility reasons:	
	try {
	    if (typeof this.mediaElement[0].buffered=='object') {
		if (this.mediaElement[0].buffered.length>0) {
		    this.mediaElement.unbind('progress');
		    return;
		}
	    }
	} catch(e){}
	
	if (this._bandWidthTimer==null) {
	    this._bandWidthTimer = (new Date()).getTime();
	}
	
	var current = 0, total = 0;	

	if (!isNaN(evt.loaded / evt.total)) {	    
	    current = evt.loaded;
	    total = evt.total;
	} else if (evt.originalEvent && !isNaN(evt.originalEvent.loaded / evt.originalEvent.total)) {
	    current = evt.originalEvent.loaded;
	    total = evt.originalEvent.total;	    
	} else if ( obj && !isNaN(obj.loaded / obj.total)) {
	    current = obj.loaded;
	    total = obj.total;	
	} /*else {
	    
	    try {
		this.media.loadProgress = (this.allowRandomSeek===true) ? 100 : -1;
		this.sendUpdate('progress', this.media.loadProgress);
	    } catch(e){};
	   return;
	   
	} */
	
	var loadedPercent = (current>0 && total>0) ? current * 100 / total : 0 ;

	if (Math.round(loadedPercent)>Math.round(this.media.loadProgress)) {
	    this._KbPerSec = (  (current/1024) /  ( ((new Date()).getTime() - this._bandWidthTimer) / 1000) );
	}
    
	loadedPercent = (this.media.loadProgress!==100) ? loadedPercent : 100;
	loadedPercent= (this.allowRandomSeek===true) ? 100 : loadedPercent;
	
	if (this.media.loadProgress != loadedPercent) {
	    this.media.loadProgress = loadedPercent
	    this.sendUpdate('progress', loadedPercent);
	}
	
	// Mac flash fix:
	if ( this.media.loadProgress>=100 && this.allowRandomSeek==false) {
	    this._setBufferState('full');
	    
	}	
    },
    
    qualityChangeListener: function(obj) {
	this.sendUpdate('qualityChange', obj);	
    },
    
    endedListener: function(player) {
	if (this.mediaElement===null) return;
	if (this.media.position<=0) return;
	this._setState('completed'); 
    },
    
    waitingListener: function(event) {
	this._setBufferState('empty');
    },
            
    canplayListener: function(obj) {
	this._setBufferState('full'); 
    },

    canplaythroughListener: function(obj) {
	this._setBufferState('full'); 
    },
    
    suspendListener: function(obj) {
        this._setBufferState('full'); // hmmmm...
    },   
        
    playingListener: function(obj) {
	this._setState('playing'); 
    },    
    
    startListener: function(obj) {
	this.applyCommand('volume', this.pp.getConfig('volume'));
	this.setSeek(this.media.position || 0);
	this._setState('playing'); 
    },       

    pauseListener: function(obj) {
	this._setState('paused'); 
    },

    volumeListener: function(obj) {	
        this.sendUpdate('volume', this.getVolume());
    },

    flashReadyListener: function() {
	this._displayReady = true;	
    },  

    errorListener: function(event, obj) {},
    
    metaDataListener: function(obj) {
	try {
	    this.videoWidth = obj.videoWidth;
	    this.videoHeight = obj.videoHeight;	
	} catch(e) {};
	this._scaleVideo();
    },


    setTestcard: function(code, txt) {
	
        var destContainer = this.pp.getMediaContainer(),
	    messages  = this.pp.getConfig('messages'),
	    msgTxt = (messages[code]!=undefined) ? messages[code] : messages[0];
	    
	msgTxt = (txt!=undefined && txt!='') ? txt : msgTxt;
	
        if (this.pp.getItemCount()>1) {
	    // "press next to continue"
            msgTxt += messages[99];
        }        
	if (msgTxt.length<3) {
	    msgTxt = 'ERROR';
	}
	if (code==100) {
	    msgTxt = '';
	}
	
	msgTxt = $p.utils.parseTemplate(msgTxt, $.extend(
	    {},this.media,{flashver:this.requiresFlash}));
	
        destContainer
	    .html('')
	    .css({width:'100%',height:'100%'});
	   
	    
	this.mediaElement  = $(document.createElement('div'))
	    .addClass(this.pp.getConfig('cssClassPrefix')+'testcard')
	    .attr('id', this.pp.getId()+'_testcard_media')
	    .appendTo(destContainer);
	
	// display some error message
	if (msgTxt.length>0) {
            $(document.createElement('p')).appendTo(this.mediaElement).html(msgTxt);	    
	}

	this._setState('error'); 	
    },

    applyImage: function(url, destObj) {

	var imageObj = $(document.createElement('img')).hide(),
	    ref = this;
	 
	$p.utils.blockSelection(imageObj);
	 
        // empty URL... apply placeholder
        if (url=='' || url==undefined) {
            return $(document.createElement('span')).attr({"id": this.pp.getMediaId()+"_image"}).appendTo(destObj);	    
        }

	imageObj.html('').appendTo(destObj).attr({
            "id": this.pp.getMediaId()+"_image",
            "src": url,
	    "alt": this.pp.getConfig('title') || ''
        }).css({position: 'absolute'});

	imageObj.error(function(event){
	    $(this).remove();
	});
	
	var setRealDims = function(dest) {
	    dest.realWidth = dest.prop('width');
	    dest.realHeight = dest.prop('height');	    
	    dest.width = function() {return dest.realWidth;}
	    dest.height = function() {return dest.realHeight;}
	}	
	
	/* YEAH - just one more IE trap */
	if($.browser.msie){
	    (function() {
		try{
		    if(imageObj[0].complete==true){ 
			imageObj.show();
			setRealDims(imageObj);
			$p.utils.stretch(ref.pp.getConfig('imageScaling'), imageObj, destObj.width(), destObj.height());
			return;
		    }
		    setTimeout(arguments.callee,100);	
		} catch(e) {
		    setTimeout(arguments.callee,100);    
		}
	       
	    })();	       	    
	}
	else {	    
	    imageObj.load(function(event){
		$(this).show();
		setRealDims(imageObj);
		$p.utils.stretch(ref.pp.getConfig('imageScaling'), $(this), destObj.width(), destObj.height());
	    });	    
	}
	

	    

	var onReFull = function(imgObj, destObj) {	
	    if (destObj.is(':visible')===false) {
		ref.pp.removeListener('fullscreen', arguments.callee);
	    }
	    $p.utils.stretch(ref.pp.getConfig('imageScaling'), imgObj, destObj.width(), destObj.height());	    
	}
	
	// add/remove scaling on fullscreen
	this.pp.addListener('fullscreen', function() { onReFull(imageObj, destObj);} );
	
	// add/remove scaling on window resize
	this.pp.addListener('resize', function() { onReFull(imageObj, destObj);} );
	    
	
	return imageObj;
    },

        
    /* X-Browser flash embedd mess */
    createFlash: function(domOptions, destObj){
	this.mediaElement = $p.utils.embeddFlash(destObj.html(''), domOptions);		
	this._waitforPlayer();
    },
    
    /* we have to wait for the flash components to load and initialize */
    _waitforPlayer: function() {
        if (this._displayReady==true) return;
        var ref = this;
	this._setBufferState('empty');
	(function() {
	
	    var dest = ref.mediaElement;

	    try{		
		if (dest==undefined) {
		    setTimeout(arguments.callee,100);	
		} else if (dest[ref.flashVerifyMethod]==undefined) {
		    setTimeout(arguments.callee,100);	
		} else {
		    ref._setBufferState('full');
		    ref.flashReadyListener();   	    
		}
	    } catch(e) {
		setTimeout(arguments.callee,100);    
	    }
	   
	})();
    },
    
    _setState: function(state) {
	var ref = this;
	state = state.toUpperCase();
	if (this._currentState!=state) {
	    
	    if (this._currentState=='PAUSED' && state=='PLAYING') {
		this.sendUpdate('resume', this.media);
	    }

	    if ( (this._currentState=='IDLE' || this._currentState=='STARTING') && state=='PLAYING') {
		this.sendUpdate('start', this.media);	
	    }
	    
	    if (state=='ERROR') {		
		this.setPlay = function() {
		    ref.sendUpdate('start');
		}
	    }
	    this._currentState = state.toUpperCase();
	    this.sendUpdate('state', this._currentState);
	}
    },
    
    _setBufferState: function(state) { 
	if (this._currentBufferState!=state.toUpperCase()) {	
	    this._currentBufferState = state.toUpperCase();
	    this.sendUpdate('buffer', this._currentBufferState);	    
	}
    },

    _scaleVideo: function(promote) {
        var destContainer = this.pp.getMediaContainer();
	if (this.pp.getIsMobileClient()) return;
	try {	    
	    var wid = destContainer.width(),
		hei = destContainer.height(),	
		tw = this.videoWidth,
	    	th = this.videoHeight;
		
	    if ($p.utils.stretch(this.pp.getConfig('videoScaling'), this.mediaElement, wid, hei, tw, th)) {
		this.sendUpdate('scaled', {
		    realWidth: tw,
		    realHeight: th,
		    displayWidth: wid,
		    displayHeight: hei
		});
	    }
	} catch(e) {};
	
    }    

};
});

/*
 * this file is part of: 
 * projekktor zwei
 * http://www.projekktor.com
 *
 * Copyright 2010, 2011, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 * under GNU General Public License
 * http://www.filenew.org/projekktor/license/
*/
var projekktorPluginInterface = function(){};
jQuery(function($) {
projekktorPluginInterface.prototype = {
    
    pluginReady: false,
    name: '',
    pp: {},
    config: {},
    playerDom: null,
    canvas: {
        media: null,
        projekktor: null
    },
    
    _appliedDOMObj: [],
    
    _init: function(pluginConfig) {
	this.config = $.extend(true, this.config, pluginConfig);
	this.initialize();
    },
    
    getConfig: function(idx, defaultValue) {
	
	var result = null;
	
	if (this.pp.getConfig('plugin_'+this.name)!=null) {
	    result = this.pp.getConfig('plugin_'+this.name)[idx];
	}

	if (result==null) {
	    result = this.pp.getConfig(idx);	   
	}
	
	if (result==null) {
	    result = (this.config[idx] || defaultValue);
	}

	if (typeof result == 'object')
	    result = $.extend(true, {}, result, this.config[idx]);
	    
	return result;

    },
    
    sendEvent: function(eventName, data) {
	this.pp._promote({_plugin:this.name, _event:eventName}, data);
    },

    deconstruct: function() {
	this.pluginReady = false;
	$.each(this._appliedDOMObj, function() {
	    $(this).remove(); 
	});
    },
    
    
    /**
    * applies a new dom element to the player in case it¥s not present yet
    * also transparently applies the cssclass prefix as configured
    * @private
    * @param (Object) the element
    * @return (Object) the element
    */         
    applyToPlayer: function(element, prepend) {
	var classPrefix = this.getConfig('cssClassPrefix');
	if (!element) return null;
	if ( this.playerDom.find('.'+classPrefix+element.attr('class')).length==0)
	{
	    var tmpClass = element.attr("class");
	    element.removeClass(tmpClass);
	    element.addClass(classPrefix+tmpClass);
	    
	    if (prepend===true)
		element.prependTo(this.playerDom);
	    else 
		element.appendTo(this.playerDom);
		
	    this._appliedDOMObj.push(element);
	    return element;
	}
	var tmpClass = element.attr("class");	    
	element = this.playerDom.find('.'+classPrefix+element.attr('class'));
	element.removeClass(tmpClass);
	element.addClass(classPrefix+tmpClass);
	return element;
    },
    
    getElement: function(name) {
	return this.pp.env.playerDom.find('.'+this.getConfig('cssClassPrefix')+name)
    },
    
    // triggered on plugin-instanciation 
    initialize: function() {},
    
    isReady: function() {
	return this.pluginReady;
    }
    
    /*
     triggered once playback of a new item is being initialized 
    detachHandler: function(itemData) {},
            
     triggered once display has been initialized (and before plugins are re-ionitialized 
    displayReadyHandler: function(obj) {},

     triggered once all plugins have been (re-) initialized 
    pluginsReadyHandler: function(obj) {},

     triggered on model-state changes 
    stateHandler: function() {},

     triggered on buffer state changes 
    bufferHandler: function() {}, 
 
     triggered before ansychronous playlist loading
    scheduleLoading: function(itemData) {},
    
     Fired if player configuration has been altered by AJAX results 
    configModified: function(itemData) {},     
  
     triggered once new playlist has been initialized
    scheduledHandler: function(itemCount) {}, 
    
     triggered when ever items are removed from or added to playlist 
    scheduleModifiedHandler: function(itemData) {}, 

     triggered once playback of a new item is being initialized 
    itemHandler: function(itemData) {}, 
 
     triggered every time playback starts 
    startHandler: function(obj) {},     
    
     triggered once playlist reached its end and loop=false 
    doneHandler: function(obj) {},
    
     triggered once player has been stoped / sent back to sleep 
    stopHandler: function(obj) {},    
    
     triggered once a media file has been played back completely 
    endedHandler: function(obj) {},    
   
    canplayHandler: function(obj) {},    
  
     triggered on volume change 
    volumeHandler: function(obj) {},  
    
     triggered on time updates 
    timeHandler: function(obj) {},
    
     triggered on progress updates 
    progressHandler: function(obj) {},
    
     triggered on mousemovement on player
    mousemoveHandler: function(obj) {},         
 
     triggered if mouse leaves player DOM 
    mouseleaveHandler: function(obj) {},
    
     triggered oif mouse enters player DOM 
    mouseeterHandler: function(obj) {},
    
     triggered on fullscreen toggle 
    fullscreenHandler: function(obj) {},
    
     triggered on keyboard events 
    keyHandler: function(obj) {}
    */
}
});

jQuery(function($) {	

	$p.utils = {
		
		
		/**
		* blocks text selection attempts by the user for the given obj
		* @private
		* @param (Object) Object 
		*/      
		blockSelection: function(dest) {
		    if (!dest) return;
		    // block text-selection:
		    if($.browser.mozilla){//Firefox
			dest.css("MozUserSelect","none");
		    }else if($.browser.msie){//IE
			dest.bind("selectstart",function(){return false;});
		    }else{//Opera, etc.
			dest.mousedown(function(){return false;});
		    }
		},		
		
		roundNumber: function (rnum, rlength) {
		    if (rnum<=0 || isNaN(rnum) ) return 0;
		    return Math.round(rnum*Math.pow(10,rlength))/Math.pow(10,rlength);
		},		
		
		/* generates a random string of <length> */
		randomId: function(length) {			
		    var chars = "abcdefghiklmnopqrstuvwxyz", result = '';
		    for (var i=0; i<length; i++) {
			    var rnum = Math.floor(Math.random() * chars.length);
			    result += chars.substring(rnum,rnum+1);
		    }
		    return result;
		},
		
		toAbsoluteURL: function (s) {	    
			var l = location, h, p, f, i;
			
			if (s==null || s=='') return '';
			
			if (/^\w+:/.test(s)) {
			    return s;
			}
			
			h = l.protocol + '//' + l.host;
			if (s.indexOf('/') == 0) {
			    return h + s;
			}
			
			p = l.pathname.replace(/\/[^\/]*$/, '');
			f = s.match(/\.\.\//g);
			if (f) {
			    s = s.substring(f.length * 3);
			    for (i = f.length; i--;) {
				p = p.substring(0, p.lastIndexOf('/'));
			    }
			}
			
			return h + p + '/' + s;
		},
		
		/**
		* strips / trims
		* @public
		* @param (String) Da string to get processed
		* @return (String) Da trimmed string
		*/       
		strip: function(s) {
		    return s.replace(/^\s+|\s+$/g,"");
		},

    	
		/**
		* strips / trims
		* @public
		* @param (String) Da human readable time to parse
		* @return (Integer) Absolute seconds
		*/       
		toSeconds: function(t) {
		    var s = 0.0
		    if (typeof t != 'string') return t;
		    if(t) {
		      var p = t.split(':');
		      for(i=0;i<p.length;i++)
			s = s * 60 + parseFloat(p[i].replace(',', '.'))
		    }
		    return parseFloat(s);
		},
		
		/* X-Browser flash embedd mess */
		embeddFlash: function(destObj, domOptions){
		
		    var flashVars = domOptions.FlashVars || {},
			result = '',
			htmlEmbedObj = '',
			htmlEmbed = '',
			tmpStr = '',
			dest = destObj,
			id = '';
		    
		    // add flashVars
			if (domOptions.src.indexOf('?') == -1)
			   domOptions.src += "?";
			else
			   domOptions.src += "&";		    

		    
		    
		    for(var key in flashVars) {
			if(typeof flashVars[key] != 'function') {
			    tmpStr = flashVars[key];
			    
			    /*
			    // support "{tags}" to add media properties
			    for(var i in this.media) {
				if (typeof tmpStr != 'string') continue;	    
				tmpStr = tmpStr.replace('{'+i+'}', this.media[i]);
			    }
			    */
			    domOptions.src += key+'='+encodeURIComponent(tmpStr)+'&';
			}
		    }
		    domOptions.src.replace(/&$/, '');	
		    
		    
		    // IE extrawurst
		    if ($.browser.msie) {
			id = ' id="'+domOptions.id+'" ';
		    }
			    
		    // <object> bullshit
		    htmlEmbedObj = '<object'+id+' codebase="https://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0"  name="'+domOptions.name+'" width="'+domOptions.width+'" height="'+domOptions.height+'" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">'
		    + '<param name="movie" value="'+domOptions.src+'"></param>'
		    + '<param name="allowScriptAccess" value="'+domOptions.allowScriptAccess+'"></param>'
		    + '<param name="allowFullScreen" value="'+domOptions.allowFullScreen+'"></param>'
		    + '<param name="wmode" value="'+domOptions.wmode+'"></param>';
		    
		    
		    // <embed> tag
		    htmlEmbed = '<embed ';	    
		    for(var key in domOptions) {
			if (key.toUpperCase()==='FLASHVARS') continue;
			if(typeof domOptions[key] != 'function') htmlEmbed += key+'="'+domOptions[key]+'" ';
		    }	    
		    htmlEmbed += ' pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"></embed>';	
		    
		    
		    result = htmlEmbedObj + htmlEmbed; 
		    result += '</object>';
		    
		    if ($.browser.mozilla || $.browser.webkit) {
			result = htmlEmbed;
		    }
	
		    if (dest===null)
			return result;
		    
		    // jquerx 1.4.2 IE flash <object> issue workaround:
		    // this doesn¥t work in IE: destObj.append(result);
		    dest.get(0).innerHTML = result;

		    return $('#'+domOptions.id)[0];
		},
		
    
		/**
		* replaces {}-tags with parameter¥s equialents
		* @public
		* @param (String) Da string to get processed
		* @param (Object) Object holding data to fill in
		* @return (String) Da parsed string
		*/      
		parseTemplate: function(template, data) {
	
		    if (data===undefined || data.length==0 || typeof data != 'object') return template;
	
		    for(var i in data) {
			template = template.replace(new RegExp('%{'+i+'}', 'gi'), data[i]);
		    }
		    template = template.replace(/%{(.*?)}/gi, '');
		    return template;
		},
		
		
		/**
		 * stretches target to fit into specified dimensions keeping apsect ratio
		 * @public
		 * @param (String) "fill" or "aspectratio" (default)
		 * @param (Object) the Dom-Obj to scale
		 * @param (Float) The maximum available width in px 
		 * @param (Float) The maximum available height in px
		 * @param (Float) A forced asumed with of the target object (optional)
		 * @param (Float) A forced asumed height of the target object (optional)
		 * @return (Boolean) Returns TRUE if <target> was resized in any way, otherwise FALSE
		 */          
		 stretch: function (stretchStyle, target, wid, hei, twf, thf) {
	 
	 
			if (target==null)
					return false;
			
			if (target._originalDimensions===undefined) {
				target._originalDimensions = {};
				target._originalDimensions = {width:target.width() , height:target.height()};
			}
 
			var tw = (twf!==undefined) ? twf : target._originalDimensions.width,
				th = (thf!==undefined) ? thf : target._originalDimensions.height,
				xsc = (wid/tw),
				ysc = (hei/th),
				rw = wid,
				rh = hei;
	 
			// fill area
			switch (stretchStyle) {
				case 'none':
					rw = tw;
					rh = th;
				break;
				
				case 'fill':
				if(xsc > ysc) {
					rw = tw*xsc;
					rh = th*xsc;
				} else if(xsc < ysc) {
					rw = tw*ysc;
					rh = th*ysc;	    
				}
				break;
				
				case 'aspectratio':
				default:
				// scale, keep aspect ratio
				if(xsc > ysc) {
					rw = tw*ysc;
					rh = th*ysc;
				} else if(xsc < ysc) {
					rw = tw*xsc;
					rh = th*xsc;	    
				}
				break;
			}
			wid = $p.utils.roundNumber((rw/wid)*100,0);
			hei = $p.utils.roundNumber((rh/hei)*100,0);
		
			if (wid==0 || hei==0)
				return false;
			
			target.css({
				'margin': 0,
				'padding': 0,
				'width': wid+"%",
				'height': hei+"%",
				'left': (100-wid)/2+"%",
				'top': (100-hei)/2+"%"
			});
			
		
			if (target._originalDimensions.width != target.width() || target._originalDimensions.height != target.height() ) {
				return true;
			}
			
			return false;
		
		 }
		
		
	}
});

/*
 * this file is part of: 
 * projekktor zwei
 * http://www.projekktor.com
 *
 * Copyright 2010, 2011, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 * under GNU General Public License
 * http://www.filenew.org/projekktor/license/
*/
jQuery(function($) {
$p.newModel({
    modelId: 'NA',
    iLove: [
        {ext:'NaN', type:'none/none', platform:'all'}
    ],    
    hasGUI: true,
    
    applyMedia: function(destContainer) {

        destContainer.html('');
        
        var ref = this;
        
        this.mouseClick = function(){
            ref.pp.removeListener('leftclick', arguments.callee);
            ref._setState('completed');
        };
        
        this.displayReady();
        
        if (this.pp.getConfig('skipTestcard') && this.pp.getItemCount > 1) {
            ref._setState('completed');            
            return;
        }
        
        if (this.pp.getConfig('enableTestcard') && !this.pp.getIsMobileClient()) {
            this.setTestcard( (this.media.file!==null && this.media.errorCode===7) ? 5 : this.media.errorCode);
            this.pp.addListener('leftclick', mouseClick)
        } else {
            // this.applyImage(this.media.config.poster, destContainer);
            this.applyCommand ('stop');
            window.location.href = this.media.file;            
        }
    },
    
    detachMedia: function() {
	this.pp.removeListener('leftclick', this.mouseClick)        
    }
    

});
});

/*
 * this file is part of: 
 * projekktor zwei
 * http://filenew.org/projekktor/
 *
 * Copyright 2010, 2011, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 * under GNU General Public License
 * http://www.filenew.org/projekktor/license/
*/
jQuery(function($) {
$p.newModel({
    
    modelId: 'PLAYLIST',
    
    iLove: [
        {ext:'json', type:'text/json', platform:'internal'},
        {ext:'jsonp', type:'text/jsonp', platform:'internal'},	    
        {ext:'xml', type:'text/xml', platform:'internal'},
        {ext:'json', type:'application/json', platform:'internal'},
        {ext:'jsonp', type:'application/jsonp', platform:'internal'},	    
        {ext:'xml', type:'application/xml', platform:'internal'}        
    ],
    
    applyMedia: function(destContainer) {
        this.displayReady();
    },
        
    setPlay: function() {
        this.sendUpdate('playlist', this.media);          
    }        
});
});

/*
 * this file is part of: 
 * projekktor zwei
 * http://www.projekktor.com
 *
 * Copyright 2010, 2011, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 * under GNU General Public License
 * http://www.filenew.org/projekktor/license/
 *
 * This is an ALTERNATE model to use the JWplayer as fallback instead of jarisplayer
*/
jQuery(function($) {
$p.newModel({

    modelId: 'JWVIDEO',
    iLove: [
	{ext:'flv', type:'video/x-flv', platform:'flash', fixed: true},
	{ext:'flv', type:'video/flv', platform:'flash', fixed: true},
	{ext:'mp4', type:'video/mp4', platform:'flash'},
	{ext:'mov', type:'video/quicktime', platform:'flash'},
	{ext:'m4v', type:'video/mp4', platform:'flash'}
    ],

    requiresFlash: 9,
    flashVerifyMethod: 'getConfig',
    hasGUI: true,
    
    _isMuted: false,
    _isStarted: false,
    
    applyMedia: function(destContainer) {

        var domOptions = {
            id: this.pp.getMediaId()+"_flash",
            name: this.pp.getMediaId()+"_flash",
            src: this.pp.getConfig('playerFlashMP4'),
            width: '100%',
            height: '100%',
            allowScriptAccess:"always",
	    quality:"height",
	    menu: false,
	    allowFullScreen: 'true',
            wmode: 'opaque',
	    seamlesstabbing: 'false',
	    bgcolor: '#111111',
            FlashVars: {
		file: this.media.file,
		backcolor: '000000',
		frontcolor: 'd5e0f7',
		lightcolor: 'FFFFFF',
		screencolor: '000000',
		stretching: 'fill',
		bufferlength: 5,
		autostart: "true" // makes sure that no start button is shown within the flash video comp.
	    }
        };
	
	this.createFlash(domOptions, destContainer);

    },
    

    /* custom addlistener */
    addListeners: function() {
	
	if (this.mediaElement==null) return;

	// pp events
	this.mediaElement.addControllerListener("VOLUME", "projekktor('"+this.pp.getId()+"').playerModel.volumeListener");	
	this.mediaElement.addControllerListener("MUTE", "projekktor('"+this.pp.getId()+"').playerModel._muteListener");

	// model events
	this.mediaElement.addModelListener("STATE", "projekktor('"+this.pp.getId()+"').playerModel._stateListener");
	this.mediaElement.addModelListener("TIME", "projekktor('"+this.pp.getId()+"').playerModel.timeListener");
	this.mediaElement.addModelListener("LOADED", "projekktor('"+this.pp.getId()+"').playerModel.progressListener");	
	this.mediaElement.addModelListener("ERROR", "projekktor('"+this.pp.getId()+"').playerModel.errorListener");
	this.mediaElement.addModelListener("BUFFER", "projekktor('"+this.pp.getId()+"').playerModel._bufferListener");
	
	this.setPlay();
    },
    
    _muteListener: function(value) {
	this._isMuted = false;
	try {
	    if (value.state===true) {
		this._isMuted = true;
	    }
	} catch(e){}
	this.volumeListener();
    },
    
    _stateListener: function(value) {

	switch (value.newstate) {
	    case "COMPLETED":
		this.endedListener();
		break;
	    case "PLAYING":
		if (this._isStarted===false) {
		    this._isStarted = true;
		    this.startListener();
		    break;
		}			
		this.playingListener();
		break;
	    case "PAUSED":
		this.pauseListener();
		break;
    	    case "BUFFERING":
		this.waitingListener();
		break;
	    
	}
	
    },
    
    /*get rid of listeners */
    removeListeners: function() {	
        try {
	    // pp events
	    this.mediaElement.removeppListener("VOLUME", "projekktor('"+this.pp.getId()+"').playerModel.volumeListener");	
	    this.mediaElement.removeppListener("MUTE", "projekktor('"+this.pp.getId()+"').playerModel._muteListener");
    
	    // model events
	    this.mediaElement.removeModelListener("STATE", "projekktor('"+this.pp.getId()+"').playerModel._stateListener");
	    this.mediaElement.removeModelListener("TIME", "projekktor('"+this.pp.getId()+"').playerModel.timeListener");
	    this.mediaElement.removeModelListener("LOADED", "projekktor('"+this.pp.getId()+"').playerModel.progressListener");	
	    this.mediaElement.removeModelListener("ERROR", "projekktor('"+this.pp.getId()+"').playerModel.errorListener");
	    this.mediaElement.removeModelListener("BUFFER", "projekktor('"+this.pp.getId()+"').playerModel._bufferListener");    	
        } catch(e){}; 
    },
    
    setSeek: function(newpos) {
	this.mediaElement.sendEvent("SEEK", newpos);        
    },
    
    setVolume: function(newvol) {
	if (this.mediaElement==null) 
	    this.volumeListener(newvol)
	else
	    this.mediaElement.sendEvent("VOLUME", newvol*100);
    },    
    
    setPause: function(event) {
	this.mediaElement.sendEvent("PLAY", false);
    },      
    
    setPlay: function(event) {
        this.mediaElement.sendEvent("PLAY", true);
    },
                
    getVolume: function() {
	if (this._isMuted===true)
	    return 0;
	
	if (this.mediaElement==null)
	    return this.media.volume;
	
        return this.mediaElement.getConfig().volume/100;
    },

    errorListener: function(event) {
        this.setTestcard(0, event.message+"<br/>");
    }
});

$p.newModel({    

    modelId: 'JWAUDIO',
    hasGUI: false,
    iLove: [
	{ext:'mp3', type:'audio/mp3', platform:'flash'},
	{ext:'m4a', type:'audio/mp4', platform:'flash'}
    ],
    
    applyMedia: function(destContainer) {
	
	this.imageElement = this.applyImage(this.pp.getConfig('cover') || this.pp.getConfig('poster'), destContainer);	
	
	var flashContainer = $('#'+this.pp.getId()+'_flash_container')
	if (flashContainer.length==0) {
	    flashContainer = $(document.createElement('div'))
		.css({width: '1px', height: '1px', 'border': '1px solid red'})
		.attr('id', this.pp.getId()+"_flash_container")
		.appendTo( destContainer );	    
	}
	
        var domOptions = {
            id: this.pp.getMediaId()+"_flash",
            name: this.pp.getMediaId()+"_flash",
            src: this.pp.getConfig('playerFlashMP3'),
            width: '1px',
            height: '1px',
            allowScriptAccess:"always",
	    quality:"height",
	    menu: false,
	    allowFullScreen: 'false',
            wmode: 'opaque',
	    seamlesstabbing: 'false',
	    bgcolor: '#111111',
            FlashVars: {
		file: this.media.file,
		backcolor: '000000',
		frontcolor: 'd5e0f7',
		lightcolor: 'FFFFFF',
		screencolor: '000000',
		stretching: 'fill',
		bufferlength: 5,
		autostart: "false" // makes sure that no start button is shown within the flash video comp.
	    }
        };
	
	this.createFlash(domOptions, flashContainer);   
    }
}, 'JWVIDEO');


});

/*
 * this file is part of: 
 * projekktor zwei
 * http://filenew.org/projekktor/
 *
 * Copyright 2010, 2011, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 * under GNU General Public License
 * http://www.filenew.org/projekktor/license/
*/
// http://code.google.com/apis/youtube/js_api_reference.html#Embedding
jQuery(function($) {
$p.newModel({
    
    modelId: 'YTVIDEO',
    iLove: [
	{ext:'youtube.com', type:'video/youtube', platform:'flash', fixed:true}
    ],
    
    allowRandomSeek: true,
    useIframeAPI: true,
    flashVerifyMethod: 'cueVideoById',
    
    _ffFix: false,
    _updateTimer: null,
    
    init: function(params) {
	
	var ref = this;
	
	this.useIframeAPI = this.pp.getConfig('useYTIframeAPI') || this.pp.getIsMobileClient();
	this.hasGUI = this.pp.getIsMobileClient();
	
	
	if (this.useIframeAPI!==true) {
	    this.requiresFlash = 8;
	    this.ready();
	    return;
	}
	
	var id = this.pp.getId();
	
	// load youtube API stuff if required:
	if (window.ProjekktorYoutubePlayerAPIReady!==true) {
	    $.getScript('http://www.youtube.com/player_api');
	    // we can¥t use the getscript onready callback here
	    // coz youtube does some additional ubersecret stuff
	    (function() {
		try{
		    if(window.ProjekktorYoutubePlayerAPIReady==true){
			ref.ready();
			return;
		    }
		    setTimeout(arguments.callee,50);	
		} catch(e) {
		    setTimeout(arguments.callee,50);    
		}
	       
	    })();	    
	}
	else {
	    this.ready();
	}
	
    	
	window.onYouTubePlayerAPIReady = function() {
	    window.ProjekktorYoutubePlayerAPIReady=true;
	}
    },
    
    applyMedia: function(destContainer) {
	
	this._setBufferState('empty');

	var ref = this,
	    width = (this.modelId=='YTAUDIO') ? 1 : '100%',
	    height = (this.modelId=='YTAUDIO') ? 1 : '100%';
	
	if (this.modelId=='YTAUDIO')
	    this.imageElement = this.applyImage(this.pp.getConfig('cover') || this.pp.getConfig('poster'), destContainer);

	if (this.useIframeAPI) {

	    destContainer
		.html('')
		.append(
		    $('<div/>').attr('id', this.pp.getId()+'_media_youtube' )
		    .css({
			width: '100%',
			height: '100%',
			position: 'absolute',
			top: 0,
			left: 0
		    })		    
		)
		.append(
		    $('<div/>').attr('id', this.pp.getId()+'_media_youtube_cc' )
		    .css({
			width: '100%',
			height: '100%',
			backgroundColor: ($.browser.msie && jQuery.browser.version < 9) ? '#000' : 'transparent',
			filter: 'alpha(opacity = 0.1)',
			position: 'absolute',
			top: 0,
			left: 0
		    })
		)


	    
	    this.mediaElement = new YT.Player( this.pp.getId()+'_media_youtube', {
		width: (this.pp.getIsMobileClient()) ? this.pp.config._width : width,
		height: (this.pp.getIsMobileClient()) ? this.pp.config._height : height,
		playerVars: {
		    autoplay: 0,
		    disablekb: 0,
		    version: 3,
		    start: 0,
		    controls: (this.pp.getIsMobileClient()) ? 1 : 0,
		    showinfo: 0,
		    enablejsapi: 1,
		    start: this.media.position || 0,
		    origin: window.location.href,
		    wmode: 'transparent', 
		    modestbranding: 1
		},
		videoId: this.youtubeGetId(),
		events: {
		    'onReady': function(evt) {ref.onReady(evt);}, // 'onReady'+ this.pp.getId(),
		    'onStateChange': function(evt) {ref.stateChange(evt);},
		    'onError':  function(evt) {ref.errorListener(evt);}
		}
	    });
	    
	    

	    
	} else {
	    	    	
	    var domOptions = {
		id: this.pp.getId()+"_media_youtube",
		name: this.pp.getId()+"_media_youtube",
		src: 'http://www.youtube.com/apiplayer',
		width: (this.pp.getIsMobileClient()) ? this.pp.config._width : width,
		height: (this.pp.getIsMobileClient()) ? this.pp.config._height : height,
		bgcolor: '#000000',
		allowScriptAccess:"always",
		wmode: 'transparent',
		FlashVars: {
		    enablejsapi: 1,
		    autoplay: 0,
		    version: 3,
		    modestbranding: 1,
		    showinfo: 0
		}
	    };
	    this.createFlash(domOptions, destContainer);
	}
	
    },
    
    /* OLD API - flashmovie loaded and initialized - cue youtube ID */
    flashReadyListener: function() {
	this._youtubeResizeFix();
	this.addListeners();	
	this.mediaElement.cueVideoById( this.youtubeGetId(), this.media.position || 0, this._playbackQuality );
    },
    
    
    /* OLD API - workaround for youtube video resize bug: */
    _youtubeResizeFix: function() {
	/*
	$(this.mediaElement).attr({
	    width: '99.99999%',
	    height: '99.9999%'
	});
	*/
	this.applyCommand('volume', this.pp.getConfig('volume'));
    },    
  
    /* OLD API */
    addListeners: function() {
	// if (this.useIframeAPI===true) return;
	this.mediaElement.addEventListener("onStateChange", "projekktor('"+this.pp.getId()+"').playerModel.stateChange");
	this.mediaElement.addEventListener("onError", "projekktor('"+this.pp.getId()+"').playerModel.errorListener");	
	this.mediaElement.addEventListener("onPlaybackQualityChange", "projekktor('"+this.pp.getId()+"').playerModel.qualityChangeListener");
    },
    
    setSeek: function(newpos) {
        try {
	    this.mediaElement.seekTo(newpos, true);
	    if (!this.getState('PLAYING'))
		this.timeListener({position:this.mediaElement.getCurrentTime(),duration:this.mediaElement.getDuration()});
	} catch(e){}
    },
    
    setVolume: function(newvol) {	
	try {this.mediaElement.setVolume(newvol*100);} catch(e){}
    },    
    
    setPause: function(event) {
	try {this.mediaElement.pauseVideo();} catch(e){}
    },      
    
    setPlay: function(event) {
        try {this.mediaElement.playVideo();}catch(e){}	
    },

    setQuality: function(quality) {
	try{this.mediaElement.setPlaybackQuality(quality)} catch(e) {}
    },

    getVolume: function() {
        try {return this.mediaElement.getVolume();} catch(e){};
	return 0;
    },
    
    getPoster: function() {
	return this.media['config']['poster'] || this.pp.config.poster || 'http://img.youtube.com/vi/' + this.youtubeGetId() + '/0.jpg';
    },

    getPlaybackQuality: function() {
	try {return this.mediaElement.getPlaybackQuality();}catch(e){ return false;}	
    },

    errorListener: function(code) {
	switch ( (code.data==undefined) ? code : code.data ) {
	    case 100:
		this.setTestcard(500);
		break;
	    case 101:
	    case 150:
		this.setTestcard(501);
		break;
	    case 2:
		this.setTestcard(502);
		break;
	}
    },
    
    stateChange: function(eventCode) {
	// unstarted (-1), ended (0), playing (1), paused (2), buffering (3), video cued (5).
	clearTimeout(this._updateTimer);
	if (this.mediaElement===null) return;
	switch ((eventCode.data==undefined) ? eventCode : eventCode.data) {
	    case -1:
		this.setPlay();		
		this.ffFix = true;	
		break;
	    case 0:
		this._setBufferState('full');
		this.endedListener({});
		break;
	    case 1:
		this._setBufferState('full');
		
		if ( (this.media.position || 0) > 0 && ($.browser.mozilla) && this.ffFix) {
		    this.ffFix = false;
		    this.setSeek(this.media.position);
		}
		    
		this.playingListener({});
		this.canplayListener({});
		this.updateInfo();		
		break;
	    case 2:
		this.pauseListener({});
		break;
	    case 3:
		this.waitingListener({});
		break;
	    case 5:
		if (this.useIframeAPI!==true)
		    this.onReady();		

		break;	    
	}
    },
    
    onReady: function() {
	
	this.setVolume(this.pp.getVolume());
	
	$( '#'+this.pp.getId()+'_media' )
	    .attr('ALLOWTRANSPARENCY', true)
	    .attr({scrolling:'no', frameborder: 0})
	    .css({
		'overflow': 'hidden',
		'display': 'block',
		'border': '0'
	    })
	
	this.displayReady();
	
	if (this.media.title ||  this.pp.config.title )
	    return;
	
	var ref = this;

	$.ajax({
	    url: 'http://gdata.youtube.com/feeds/api/videos/'+ this.youtubeGetId() +'?v=2&alt=jsonc',
	    complete: function( xhr, status) {
		
		try {
		    data = xhr.responseText;
		    if (typeof data == 'string') {
			data = $.parseJSON(data);
		    }
		    if (data.data.title) {
			ref.sendUpdate('config', {title: data.data.title + ' (' + data.data.uploader + ')'});
		    }
		} catch(e){};
		ref.displayReady();
	    }
	});	
    },
    
    youtubeGetId: function() {
	return encodeURIComponent( this.media.file.replace(/^[^v]+v.(.{11}).*/,"$1") );
    },
    
    updateInfo: function() {	
	var ref=this;	
	clearTimeout(this._updateTimer);
	(function() {
	    if(ref.mediaElement==null) {
		clearTimeout(ref._updateTimer);
		return;
	    }
	    try{
		if (ref.getState()!=='IDLE' && ref.getState()!=='COMPLETED') {
		    ref.timeListener({position:ref.mediaElement.getCurrentTime(),duration:ref.mediaElement.getDuration()});
		    ref.progressListener({loaded:ref.mediaElement.getVideoBytesLoaded(),total:ref.mediaElement.getVideoBytesTotal()});			
		}
	    } catch(e) {}
	    ref._updateTimer = setTimeout(arguments.callee,500);		    
	})();
    }
});

$p.newModel({
    
    modelId: 'YTAUDIO',
    iLove: [
	{ext:'youtube.com', type:'audio/youtube', platform:'flash', fixed:'maybe'}
    ]
    
}, 'YTVIDEO');
});

/*
 * this file is part of: 
 * projekktor zwei
 * http://filenew.org/projekktor/
 *
 * Copyright 2010, 2011, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 * under GNU General Public License
 * http://www.filenew.org/projekktor/license/
*/
jQuery(function($) {
$p.newModel({
    
    modelId: 'IMAGE',
    iLove: [
	{ext:'jpg', type:'image/jpeg', platform:'all'},
	{ext:'gif', type:'image/gif', platform:'all'},
	{ext:'png', type:'image/png', platform:'all'}
    ],
    
    allowRandomSeek: true,

    _position: 0,
    _duration: 0,
    
    applyMedia: function(destContainer) {
	this.mediaElement = this.applyImage(this.media.file, destContainer.html(''));
	this._duration = this.pp.getConfig('duration');
	this._position = -1;
	this.displayReady();
	this._position = -0.5;	
    },    
    
    /* start timer */
    setPlay: function() {

	var ref = this;
	
        this._setBufferState('full');
	this.progressListener(100);
	this.playingListener();	
        
	if (this._duration==0) {
	    ref._setState('completed');
	    return;
	}
	
	(function() {

	    if (ref._position>=ref._duration) {
		ref._setState('completed');
		return;
	    }
	    
	    if (!ref.getState('PLAYING'))
		return;
	    
	    ref.timeListener({duration: ref._duration, position:ref._position});
	    setTimeout(arguments.callee,500);
	    ref._position += 0.5;	    
	})();	
	
    },
    
    detachMedia: function() {
        this.mediaElement.remove();
    },
    
    setPause: function() {
	this.pauseListener();
    },   
            
    setSeek: function(newpos) {
        if (newpos<this._duration) {
	    this._position = newpos;
	}
    }
    
});

$p.newModel({
    
    modelId: 'HTML',
    iLove: [
	{ext:'html', type:'text/html', platform:'all'}
    ],
    
   applyMedia: function(destContainer) {
        var ref = this;
         
        this.mediaElement = $(document.createElement('iframe')).attr({
            "id": this.pp.getMediaId()+"_iframe",
            "name": this.pp.getMediaId()+"_iframe",
            "src": this.media.file,
            "scrolling": 'no',
            "frameborder": "0",
            'width': '100%',
            'height': '100%'
        }).css({
            'overflow': 'hidden',
            'border': '0px',
            "width": '100%',
            "height": '100%'            
        }).appendTo(destContainer.html(''));
        
        this.mediaElement.load(function(event){ref.success();});
        this.mediaElement.error(function(event){ref.remove();});
	
	this._duration = this.pp.getConfig('duration');
        
    },
    
    success: function() {   
        this.displayReady();
    },
    
    remove: function() {
        this.mediaElement.remove();        
    }    
}, 'IMAGE');
});

/*
 * this file is part of: 
 * projekktor zwei
 * http://www.projekktor.com
 *
 * Copyright 2010, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 * under GNU General Public License
 * http://www.filenew.org/projekktor/license/
*/
jQuery(function($) {
$p.newModel({
    
    modelId: 'VIDEO',
    iLove: [
	{ext:'ogv', type:'video/ogg', platform:'native'},	
	{ext:'webm',type:'video/webm', platform:'native'},
	{ext:'ogg', type:'video/ogg', platform:'native'},
	{ext:'anx', type:'video/ogg', platform:'native'},
	{ext:'mp4', type:'video/mp4', platform:'native', fixed: 'maybe'}
    ],
    
    allowRandomSeek: false,
    videoWidth: 0,
    videoHeight: 0,
    element: 'video',
      
    applyMedia: function(destContainer) {
        
	var wasPersistent = true;
	
	// ogg allows byterange header stuff:
	// wait for SEEKABLE and BUFFERED to be implemented in more browsers:
	if (this.media.type.indexOf('/ogg')>-1 || this.media.type.indexOf('/webm')>-1) {
	    this.allowRandomSeek = true;
	}
	
	// create image element
	if (this.element=='audio') {
	    this.imageElement = this.applyImage(this.pp.getConfig('cover') || this.pp.getConfig('poster'), destContainer);	
	}

	if ($('#'+this.pp.getMediaId()+"_html").length==0)
	{
	    wasPersistent = false;
	    destContainer.html('').append(
		$(document.createElement(this.element))
		.attr({
		    "id": this.pp.getMediaId()+"_html",         
		    "poster": (this.pp.getIsMobileClient('ANDROID')) ? this.getPoster() : 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAABBJREFUeNpi/v//PwNAgAEACQsDAUdpTjcAAAAASUVORK5CYII=',
		    "loop": false,
		    "autoplay": false,
		    "x-webkit-airplay": "allow"
		}).prop({
		    controls: ( (this.hasGUI && this.element=='video') || (this.pp.getIsMobileClient() && this.pp.getItemIdx() > 0) ),
		    volume: this.getVolume()
		}).css({
		    'width': ( (this.element=='video') ? '100%' : '1px' ),
		    'height': ( (this.element=='video') ? '100%' : '1px' ),
		    'position': 'absolute',
		    'top': 0,
		    'left': 0
		})
	    );
	}

	this.mediaElement = $('#'+this.pp.getMediaId()+"_html");

	// let client descide what to do - this is a f*cked up i*-hack
	if (wasPersistent)
	    this.mediaElement.find('source').remove();
	    
	for(var i in this.media.setup) {	    
	    if (this.media.setup[i].src) {
		$(document.createElement('source')).appendTo(this.mediaElement).attr({
		    "src": this.media.setup[i].src,
		    "type": this.media.setup[i].type
		})
	    }	    
	}
	
	if (wasPersistent)
	    this.mediaElement.get(0).load();
	
       this.displayReady();	
    },

    addListeners: function() {
	var ref = this;
	
	if (this.element=='video') {
	    this.mediaElement.bind('loadedmetadata.projekktor'+this.pp.getId(), function(){ ref.metaDataListener(this); });
	}
	this.mediaElement.bind('pause.projekktor'+this.pp.getId(), function(){ ref.pauseListener(this); });
	// this.mediaElement.bind('playing', function(){ref.playingListener(this); });	
	this.mediaElement.bind('play.projekktor'+this.pp.getId(), function(){ ref.playingListener(this); });	
	this.mediaElement.bind('volumechange.projekktor'+this.pp.getId(), function(){ ref.volumeListener(this); });
	this.mediaElement.bind('progress.projekktor'+this.pp.getId(), function(evt){ ref.progressListener(evt, this); });
	this.mediaElement.bind('timeupdate.projekktor'+this.pp.getId(), function(){ ref.timeListener(this); });
	this.mediaElement.bind('ended.projekktor'+this.pp.getId(), function(){ ref.endedListener(this); });
	this.mediaElement.bind('waiting.projekktor'+this.pp.getId(), function(){ ref.waitingListener(this); });
	this.mediaElement.bind('canplaythrough.projekktor'+this.pp.getId(), function(){  ref.canplayListener(this); });
	this.mediaElement.bind('canplay.projekktor'+this.pp.getId(), function(){ref.canplayListener(this); });
	this.mediaElement.bind('error.projekktor'+this.pp.getId(), function(evt){ ref.errorListener(evt, this); });
	this.mediaElement.bind('suspend.projekktor'+this.pp.getId(), function(){ ref.suspendListener(this); });

    },
    
    updatePlayerInfo: function() {},
    
    detachMedia: function() {
	try {
	    this.mediaElement.get(0).pause();	      
	} catch(e){}
    },

    setPlay: function() {
	try{this.mediaElement[0].play();} catch(e){}
	this.updatePlayerInfo();
    },
    
    setPause: function() {
        try {this.mediaElement[0].pause();} catch(e){}
    },   
            
    setVolume: function(volume) {
	this._volume = volume;
        try {
	    this.mediaElement.prop('volume', volume);
	} catch(e){
	    return false;
	}
	return volume;
    }, 
     
    setSeek: function(newpos) {
        try {this.mediaElement.prop('currentTime', newpos);} catch(e){}
    },           

    setFullscreen: function(inFullscreen) {
	if (this.element=='audio') return;
	this._scaleVideo();
    }, 

    setResize: function() {
	if (this.element=='audio') return;
	this._scaleVideo(false);
    },
    
    errorListener: function(event, obj) {
	try {
	    switch (event.target.error.code) {
		case event.target.error.MEDIA_ERR_ABORTED:
		this.setTestcard(1);
		break;
	    case event.target.error.MEDIA_ERR_NETWORK:
		this.setTestcard(2);
		break;
	    case event.target.error.MEDIA_ERR_DECODE:
		this.setTestcard(3);
		break;
	    case event.target.error.MEDIA_ERR_SRC_NOT_SUPPORTED:
		this.setTestcard(4);
		break;
	    default:
		this.setTestcard(5);
		break;
	    }
	} catch(e) {}
    }    

    
});

$p.newModel({
    
    modelId: 'AUDIO',
    
    iLove: [
	{ext:'ogg', type:'audio/ogg', platform:'native'},
	{ext:'oga', type:'audio/ogg', platform:'native'},
	{ext:'mp3', type:'audio/mp3', platform:'native'}	
    ],
    
    imageElement: {},
    
    element: 'audio',
    
    
    setPosterLive: function() {
	if (this.imageElement.parent) {
	    var dest = this.imageElement.parent(),
		ref = this;
	    
	    if (this.imageElement.attr('src') == ref.pp.getConfig('poster'))
		return;
	     
	    this.imageElement.fadeOut('fast', function() {
		$(this).remove();
		ref.imageElement = ref.applyImage(ref.pp.getConfig('poster'), dest );	
	    })
	}
    }
    
}, 'VIDEO');

});

/*
 * this file is part of: 
 * projekktor zwei
 * http://www.projekktor.com
 *
 * Copyright 2010, 2011, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 * under GNU General Public License
 * http://www.filenew.org/projekktor/license/
 *
 * This hack implements an interface model makeing the Jaris FLV player available within
 * the Projekktor environment.
 *
 * JARIS Player:
 * copyright 2010 Jefferson Gonz·lez, Jefferson Gonz·lez, http://jarisflvplayer.org
 * under GNU LESSER GENERAL PUBLIC LICENSE 3
*/
jQuery(function($) {
$p.newModel({

    modelId: 'VIDEOFLASH',
    iLove: [
	{ext:'flv', type:'video/x-flv', platform:'flash', fixed: true},
	{ext:'flv', type:'video/flv', platform:'flash', fixed: true},
	{ext:'mp4', type:'video/mp4',  platform:'flash', fixed: 'maybe'},
	{ext:'mov', type:'video/quicktime', platform:'flash'},
	{ext:'m4v', type:'video/mp4', platform:'flash', fixed: 'maybe'}
    ],
    requiresFlash: 9,
    allowRandomSeek: false,
    flashVerifyMethod: 'api_get',
    _jarisVolume: 0,
    
    applyMedia: function(destContainer) {
	
        var domOptions = {
            id: this.pp.getMediaId()+"_flash",
            name: this.pp.getMediaId()+"_flash",
            src: this.pp.getConfig('playerFlashMP4'),
            width: '100%',
            height: '100%',
            allowScriptAccess:"always",
	    allowFullScreen: 'false',
	    allowNetworking: "all",	    
            wmode: 'transparent',
	    bgcolor: '#000000',
            FlashVars: {
		source: this.media.file,
		type: "video",
		streamtype: this.pp.getConfig('flashStreamType'), 
		server: (this.pp.getConfig('flashStreamType')=='rtmp') ? this.pp.getConfig('flashRTMPServer') : '',
		autostart: "false",
		hardwarescaling: "true",
		controls: 'false',
		jsapi: 'true',
		aspectratio: this.pp.getConfig('videoScaling')
	    }
        };
	
	
	switch(this.pp.getConfig('flashStreamType')) {
	    case 'rtmp':
	    case 'http':
		this.allowRandomSeek = true;
		this.media.loadProgress = 100;
		break;
	}
	
	this.createFlash(domOptions, destContainer);      
    },
    
    /* custom addlistener */
    addListeners: function() {

	if (this.mediaElement==null) return;

	// time and progress updates
        this.mediaElement.api_addlistener("onprogress", "projekktor('"+this.pp.getId()+"').playerModel.progressListener");    
        this.mediaElement.api_addlistener("ontimeupdate", "projekktor('"+this.pp.getId()+"').playerModel.timeListener");

	// start
	if (this.getModelName().indexOf('VIDEO')>-1) {
	    this.mediaElement.api_addlistener("ondatainitialized", "projekktor('"+this.pp.getId()+"').playerModel.metaDataListener");
	}
	
	if (this.getModelName().indexOf('AUDIO')>-1) {
	     this.mediaElement.api_addlistener("onconnectionsuccess", "projekktor('"+this.pp.getId()+"').playerModel.startListener");
	}

	// play pause	
	this.mediaElement.api_addlistener("onplaypause", "projekktor('"+this.pp.getId()+"').playerModel._playpauseListener");

	// ended	
        this.mediaElement.api_addlistener("onplaybackfinished", "projekktor('"+this.pp.getId()+"').playerModel.endedListener");
        
	// volume
	this.mediaElement.api_addlistener("onmute", "projekktor('"+this.pp.getId()+"').playerModel.volumeListener");	
	this.mediaElement.api_addlistener("onvolumechange", "projekktor('"+this.pp.getId()+"').playerModel.volumeListener");
	
	// buffering
        this.mediaElement.api_addlistener("onbuffering", "projekktor('"+this.pp.getId()+"').playerModel.waitingListener");
        this.mediaElement.api_addlistener("onnotbuffering", "projekktor('"+this.pp.getId()+"').playerModel.canplayListener");

	// errors
        this.mediaElement.api_addlistener("onconnectionfailed", "projekktor('"+this.pp.getId()+"').playerModel.errorListener");

    },
    
    flashReadyListener: function() {

	this.setSeek(this.media.position || 0);
	this.displayReady();	
    },      
    
    /*get rid of listeners */
    removeListeners: function() {
        try {this.mediaElement.api_removelistener("*");} catch(e){}; 
    },

    /* wrapping non-status-based jaris behavior */
    _playpauseListener: function(obj) {	
	if (obj.isplaying) {
	    this.playingListener();
	}
	else {
	    this.pauseListener();
	}
    },
    
    metaDataListener: function(obj) {
	this.startListener(obj);
	try {
	    this.videoWidth = obj.width;
	    this.videoHeight = obj.height;
	    // the flash component scales the media for itself
	    this.sendUpdate('scaled', {width: this.videoWidth, height:this.videoHeight});
	} catch(e) {};	

    },    

    setSeek: function(newpos) {
        try {this.mediaElement.api_seek(newpos);} catch(e){};
	if (!this.getState('PLAYING'))
	    this.timeListener({position:newpos,duration:this.media.duration});
    },

    setVolume: function(newvol) {
	this._volume = newvol;
	try {this.mediaElement.api_volume(newvol);} catch(e){return false;}
	return newvol;
    },    
    
    setPause: function(event) {
	try {this.mediaElement.api_pause();} catch(e){};
    },      
    
    setPlay: function(event) {
        try {this.mediaElement.api_play();} catch(e){};
    },
                
    getVolume: function() {    
	return this._jarisVolume;
    },

    /* needs a more sophisticated error repoprting */
    errorListener: function(event) {         
        this.setTestcard(4);
    },
    
    /* "volume change event flood" workaround - fix this within jarisplayer! */ 
    volumeListener: function(obj) {
	if (this._jarisVolume!=obj.volume) {	    
	    this._jarisVolume=obj.volume;
	    this.sendUpdate('volume', obj.volume);
	}
    },

    detachMedia: function() {
        try{$(this.mediaElement).remove();} catch(e){}           
    }
});

$p.newModel({    

    modelId: 'AUDIOFLASH',
    iLove: [
	{ext:'mp3', type:'audio/mp3', platform:'flash'},
	{ext:'mp3', type:'audio/mpeg', platform:'flash'},
	{ext:'m4a', type:'audio/mp4', platform:'flash'}
    ],
    
    applyMedia: function(destContainer) {
	
	// create image element
	this.imageElement = this.applyImage(this.pp.getConfig('cover') || this.pp.getConfig('poster'), destContainer);

	var flashContainer = $('#'+this.pp.getMediaId()+'_flash_container')
	if (flashContainer.length==0) {
	    flashContainer = $(document.createElement('div'))
		.css({width: '1px', height: '1px'})
		.attr('id', this.pp.getMediaId()+"_flash_container")
		.appendTo( destContainer );	    
	}
		
	var domOptions = {
	    id: this.pp.getMediaId()+"_flash",
	    name: this.pp.getMediaId()+"_flash",
	    src: this.pp.getConfig('playerFlashMP3'),
	    width: '1px',
	    height: '1px',
	    allowScriptAccess:"always",
	    allowFullScreen: 'true',
	    allowNetworking: "all",	    
	    wmode: 'transparent',
	    bgcolor: '#000000',
	    FlashVars: {
		source: this.media.file,
		type: "audio",
		streamtype: this.pp.getConfig('flashStreamType'), 
		server: (this.pp.getConfig('flashStreamType')=='rtmp') ? this.pp.getConfig('flashRTMPServer') : '',
		autostart: "false",
		hardwarescaling: "false",
		controls: 'false',
		jsapi: 'true'
	     }
	 };
	
	this.createFlash(domOptions, flashContainer);      
    }
}, 'VIDEOFLASH');

});

/* This code causes it to crash
function(xmlDocument) {
     var result = {};
     var regMatch = new RegExp("http:[^ ,]+\.jpg");
     result['playlist'] = [];
     $(xmlDocument).find("item").each(function() {
        try {
         result['playlist'].push({
            0:{
             src: $(this).find('link').text(),            
             type: 'video/youtube'
            },
            config: {
             poster: regMatch.exec(unescape( $(this).find('description').text())),
             title: $(this).find('title').text(),
             desc: $(this).find('description').text()
            }
         });
        } catch(e){}
     });
     return result;
    }
});*/

/*
 * Projekktor II Plugin: cb
 * VERSION: 1.0
 * DESC: Adds a fully features cb element to the player
 * Copyright 2010, 2011, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 *
 * under GNU General Public License
 * http://www.projekktor.com/license/
 */
var projekktorControlbar = function(){};
jQuery(function($) {
projekktorControlbar.prototype = {
    
    _cTimer: null,
    _noCHide: false,
    _cFading: false,
    _vSliderAct: false,
    _storeVol: 0,
    _timeTags: {},
    cb: null,
    _pos: {left:0,right:0},

    controlElements: {},
    
    controlElementsConfig: {
	'cb': null,
	'playhead':{ on:null, call:null},
	'loaded':{ on:['touchstart', 'click'], call:'scrubberClk'},
	'scrubber':{ on:['touchstart', 'click'], call:'scrubberClk'},
	'scrubberdrag': { on:['mousedown'], call:'scrubberdragStartDragListener'},
	
	'play':{ on: ['touchstart', 'click'], call:'playClk'},
	'pause':{ on:['touchstart', 'click'], call:'pauseClk'},
	'stop':{ on:['touchstart', 'click'], call:'stopClk'},
	'prev':{ on:['touchstart', 'click'], call:'prevClk'},
	'next':{ on:['touchstart', 'click'], call:'nextClk'},
	'rewind':{ on:['touchstart', 'click'], call:'rewindClk'},
	'forward':{ on:['touchstart', 'click'], call:'forwardClk'},
	'fsexit':{ on:['touchstart', 'click'], call:'exitFullscreenClk'},
	'fsenter':{ on:['touchstart', 'click'], call:'enterFullscreenClk'},
	
	'vslider':{ on:['touchstart', 'click'], call:'vsliderClk'},
	'vmarker':{ on:['touchstart', 'click'], call:'vsliderClk'},
	'vknob':{ on: ['mousedown'], call:'vknobStartDragListener'},
	
	'mute':{ on:['touchstart', 'click'], call:'muteClk'},
	'unmute':{ on:['touchstart', 'click'], call:'unmuteClk'},
	'vmax':{ on:['touchstart', 'click'], call:'vmaxClk'},
	
	'open': { on:['touchstart', 'click'], call:'openCloseClk'},
	'close': { on:['touchstart', 'click'], call:'openCloseClk'},
	'loopon': { on:['touchstart', 'click'], call:'loopClk'},
	'loopoff': { on:['touchstart', 'click'], call:'loopClk'},	
	'draghandle':{ on: ['mousedown'], call:'handleStartDragListener'},	
	'controls': null,
	'title': null,
	'sec_dur':null,
	'min_dur':null,
	'hr_dur':null,
	'sec_elp':null,
	'min_elp':null,
	'hr_elp':null,
	'sec_rem':null,
	'min_rem':null,
	'hr_rem':null
    },
        
    config: {

	/* Plugin: cb - enable/disable fade away of overlayed controls*/
	disableFade: 		false,
	toggleMute:		false,	
	fadeDelay: 		2500,
	showOnStart:		false,
	
	/* Default layout */		
	controlsTemplate: 	'<ul class="left"><li><div %{play}></div><div %{pause}></div></li><li><div %{title}></div></li></ul><ul class="right"><li><div %{fsexit}></div><div %{fsenter}></div></li><li><div %{vmax}></div></li><li><div %{vslider}><div %{vmarker}></div><div %{vknob}></div></div></div></li><li><div %{mute}></div></li><li><div %{timeleft}>%{hr_elp}:%{min_elp}:%{sec_elp} | %{hr_dur}:%{min_dur}:%{sec_dur}</div></li><li><div %{next}></div></li><li><div %{prev}></div></li></ul><ul class="bottom"><li><div %{scrubber}><div %{loaded}></div><div %{playhead}></div><div %{scrubberdrag}></div></div></li></ul>'	
    },
    
    
    
    initialize: function() {
	
	var ref = this,
	    playerHtml = this.playerDom.html(),
	    useTemplate = true,
	    classPrefix = this.getConfig('cssClassPrefix');
	    
	// check if ANY control element already exists	    
	for(var i in this.controlElementsConfig) {
	    if (playerHtml.match(new RegExp(classPrefix+i, 'gi'))) {
		useTemplate = false;
		break;
	    }	    
	}

	if (useTemplate) {
	    
	    this.cb = this.applyToPlayer(
		$(document.createElement('div')).addClass('controls')
	    );
	
	    this.applyTemplate(this.cb, this.getConfig('controlsTemplate'));
	    
	} else {
	    this.cb = this.playerDom.find( "."+classPrefix+'controls' );
	}
		
	// find (inter)active elements
	
	for (var i in this.controlElementsConfig) {
	    this.controlElements[i] = $(this.playerDom).find('.'+classPrefix+i);
	    $p.utils.blockSelection(this.controlElements[i]);	    
	}

	this.addGuiListeners();
	this._storeVol = this.getConfig('volume');
	this.drawUpdateControls();
	this.hidecb(true);
	
	this.pluginReady = true;
    },

    /* parse and apply controls dom-template */
    applyTemplate: function(dest, templateString) {
	var ref = this,
	    classPrefix = this.getConfig('cssClassPrefix');
	    
	// apply template string if required:
	if (templateString) {
	    // replace tags by class derictive
	    var tagsUsed = templateString.match(/\%{[a-zA-Z_]*\}/gi);
	    if (tagsUsed!=null) {
		$.each(tagsUsed, function(key, value) {
		    var cn = value.replace(/\%{|}/gi, '');
		    if (value.match(/\_/gi) ) {
			// replace with span markup
			templateString = templateString.replace(value, '<span class="'+classPrefix+cn+'"></span>');		
		    } else {
			// replace with className
			templateString = templateString.replace(value, 'class="'+classPrefix+cn+'"');
		    }
		});
	    }
	    dest.html(templateString);
	}
    },


    itemHandler: function(data) {
	this.pluginReady = true;
	this.hidecb(true);
	// title display
	this.drawTitle();	
    },
    
    startHandler: function() {
	if (this.getConfig('showOnStart')==true)
	    this.showcb(true);
	else 
	    this.hidecb(true);
    },
    
    readyHandler: function(data) {

	clearTimeout(this._cTimer);
	
	this.cb.removeClass('fade');
	if (this.getConfig('disableFade')!=true) {
	    this.cb.addClass('fade');
	}

	this.pluginReady = true;
	// this.showcb(true);
	
    },
    

    drawUpdateControls: function() {

	var ref = this,
	    state = this.pp.getState();
	
	clearTimeout(this._cTimer);
	
	if (this.pp.getHasGUI()) return;

	// nothing to do
        if (this.getConfig('controls')==false) {
            this.hidecb(true);
	    return;
	}
	
	// prev / next button
	if (this.pp.getItemCount()<2 || this.getConfig('disallowSkip')) {
	    this._active('prev', false);
	    this._active('next', false);
	} else {
	    this._active('prev', true);
	    this._active('next', true);
	}
	
	if (this.pp.getItemIdx()<1) {
	    this._active('prev', false);
	}
	
	if (this.pp.getItemIdx()>=this.pp.getItemCount()-1) {
	    this._active('next', false);
	}

	// play / pause button
	if (this.getConfig('disablePause')) {
	    this._active('play', false);
	    this._active('pause', false);
	} else {
	    if (state==='PLAYING') this.drawPauseButton();
	    if (state==='PAUSED') this.drawPlayButton();
	    if (state==='IDLE') this.drawPlayButton();
	}

	// stop button
        this._active('stop', state!=='IDLE');
	
	
	
	// rewind & forward
	this._active('forward', state!=='IDLE');
	this._active('rewind', state!=='IDLE');


	// fullscreen button	
	if (this.pp.getInFullscreen()===true) {
	    this.drawExitFullscreenButton();
	} else {
	    this.drawEnterFullscreenButton();
	}
	
	if (!this.getConfig('enableFullscreen')) {
	    this._active('fsexit', false);
	    this._active('fsenter', false);
	}

	// loop button
	this._active('loopoff', !this.pp.config._loop);
	this._active('loopon', this.pp.config._loop);
	
	// init time display
	this.drawUpdateTimeDisplay()

	// init volume display
	this.drawUpdateVolumeDisplay(this.pp.getVolume() || this._storeVol);	
    },
        
    stateHandler: function(state) {
/*
	if (this.cuePoints==null) {
	    this.cuePoints = this.pp.getCuePoints();
	    
	    var time = this.cuePoints[0].on,
		totalWidth = $('.ppscrubber').width(),
		duration = this.pp.getDuration()
	    
	    $('.ppscrubber').append(
		$(document.createElement('div'))
		    .addClass('cuepoint')
		    .css('left', )
	    );
	    
	}
*/	
	this.drawUpdateControls();

	if ('STOPPED|DONE|IDLE|'.indexOf(state)>-1) {
	    this._noCHide = false;
	    this.hidecb(true);
	    return;
	}

	if ('STOPPED|AWAKENING|IDLE|DONE'.indexOf(state)>-1) { 
	    this.drawUpdateTimeDisplay(0,0,0);
	    this.drawUpdateProgressDisplay(0);    
	} else {
	    this.drawUpdateProgressDisplay();
	}


	
    },

    scheduleModifiedHandler: function() {
	if (this.pp.getState()==='IDLE') return;
	this.drawUpdateControls();
        this.drawUpdateTimeDisplay();
	this.drawUpdateProgressDisplay();
    },  

    volumeHandler: function(value) {	
	this.drawUpdateVolumeDisplay(value);
    },  

    progressHandler: function(obj) {
	this.drawUpdateProgressDisplay();
    },

    timeHandler: function(obj) {
        this.drawUpdateTimeDisplay();
	this.drawUpdateProgressDisplay();
    },

    fullscreenHandler: function(inFullscreen) {

	var ref=this,
	    classPrefix = this.getConfig('cssClassPrefix');
	
	clearTimeout(this._cTimer);

	this._noCHide = false;
	this._cFading = false;
	this._vSliderAct = false;	
	
	if (!this.getConfig('controls')) return;
	if (!this.getConfig('enableFullscreen')) return;

	if (inFullscreen) {
	    this.cb.addClass('fullscreen');
	    this.drawExitFullscreenButton();
	} else {
	    this.cb.removeClass('fullscreen');
	    this.drawEnterFullscreenButton();
	}

	if (this.pp.getState()=='IDLE') {
	    this.hidecb(true);
	} else {
	    this._cTimer=setTimeout(function(){ref.hidecb();},this.getConfig('fadeDelay'));
	}	
	

    },

    addGuiListeners: function() {
	var ref = this;
	
	// if (!this.getConfig('controls')) return;
	
	$.each(this.controlElementsConfig, function(key, elmCfg) {	    
	    if (!elmCfg)
		return true;
	    
	    if (elmCfg.on==null)
		return true;
	    
	    $.each(elmCfg.on, function(evtKey, eventName) {		
		if ("on"+eventName in window.document) {		   
		    ref.controlElements[ key ].bind(eventName,  function(event) {			
			ref.clickCatcher(event, elmCfg.call, ref.controlElements[ key ]);		
		    });
		    return false;
		}
	    });

	});
		
	this.cb.mouseenter(function(event){ref.controlsMouseEnterListener(event); });		
	this.cb.mouseleave(function(event){ref.controlsMouseLeaveListener(event); });	        	
    },
    
    clickCatcher: function(evt, callback, element) {
	if($.browser.msie) {evt.cancelBubble=true;} else {evt.stopPropagation();}
	if (!element.hasClass('inactive')) {
	    this[callback](evt, element);
	}
	return false;
    },
    
    /*******************************
        DOM Monipulations
    *******************************/      
    drawTitle: function() {
	
	var ref = this;
	
        this.controlElements['title'].html( this.getConfig('title', ''));
	
	/*
	var true_width = (function() {
	    var $tempobj =ref.controlElements['title'].clone().contents()
		.wrap('<div/>').parent().appendTo('body').css({
		'left': '-1000px'
	    });
	    var result = $tempobj.width();
	    $tempobj.remove();
	    return result;
	})();
		
	this.controlElements['title'].bind('mouseenter', function() {
		var shift_distance = true_width - $(this).width();
		var time_normalized = parseInt(shift_distance / 100, 10) * 1000;		
		$(this).contents().wrap('<div>').css({position: 'absolute', overflow: 'hidden', textOverflow: 'ellipsis'}).parent().animate({
		left: -shift_distance,
		right: 0
	    }, time_normalized, 'linear');
	});

	this.controlElements['title'].bind('mouseleave', function() {
	   // $(this).html( ref.getConfig('title', ''))
	});
	*/
	
    },
    
    hidecb: function(instant) {
	
	clearTimeout(this._cTimer);	

	var classPrefix = this.getConfig('cssClassPrefix'),	
	    ref = this;
	
        if (this.cb==null) return;
	this.cb.stop(true, true);

	// don¥t hide nao
	if (this._noCHide==true)
	    return;
		
	
	/*
	if (this.pp.getHasGUI() || this.getConfig('controls')==false) {
	    dest.removeClass('active').addClass('inactive').css('display', '');
	    return;
	}
	*/
	
	// nothing to do:
	if (!this.cb.is(':visible')) return;	

	// no fade, please:
	if (instant===true) {	    
	    this._cFading = false;
	    this.cb.removeClass('active').addClass('inactive').css('display', '');
	    return;
	} 
	
        // no controls at all:
	if (this.getConfig('controls')==false || this.pp.getHasGUI() || !this.cb.hasClass('fade') ) {
	    this.cb.removeClass('active').addClass('inactive');
	    return;
	}
	
	
	 this.cb.fadeOut('slow', function() {
	    $(this).removeClass('active').addClass('inactive').css('display', '');
	    ref._cFading = false;
	});

    },
    
    showcb: function(instant) {
	
        clearTimeout(this._cTimer);
	
	// hide for current playback component
	if (this.pp.getHasGUI() || this.getConfig('controls')==false) {
	    this.cb.removeClass('active').addClass('inactive').css('display', '');
	    return;
	}

        var ref = this,
	    classPrefix = this.getConfig('cssClassPrefix');
	    

	// IDLE
	if (this.cb==null) return;
	if ("IDLE|AWAKENING|ERROR".indexOf(this.pp.getState())>-1 && instant!=true) return;	

	// stop all animations
	this.cb.stop(true, true);

	// no fade class applied, just pop it up:
	if ( (!this.cb.hasClass('fade') || instant==true) ) {
	    this.cb.removeClass('inactive').addClass('active').css('display', '');
	    return;
	}
		    	
	// is visible or fade in progress, restart timer:
	if (this.cb.is(':visible') || this._cFading==true) {
            this._cTimer=setTimeout(function(){ref.hidecb();}, this.getConfig('fadeDelay'));
            return;
        };

	// begin fade:
	this._cFading = true;

	this.cb.fadeIn('slow', function(){
	    ref._cFading=false;	    
	    $(this).removeClass('inactive').addClass('active').css('display', '');
	});
    
	
    },
    
   drawUpdateTimeDisplay: function(pct, dur, pos) {


    	if (this.pp.getHasGUI())
	    return;

        try {
            var percent = (pct!=undefined) ? pct : this.pp.getLoadPlaybackProgress(),	    
		duration = (dur!=undefined) ? dur : this.pp.getDuration(),
		position = (pos!=undefined) ? pos : this.pp.getPosition();
        } catch(e) {
            var percent = pct || 0,
		duration = dur || 0,
		position = pos || 0;       
        }

	
	// update scrubber:
	this.controlElements['playhead'].css("width", percent+"%");
	
	// update knob
	// if (!this._sSliderAct)
	//    this.controlElements['sknob'].css("left", percent+"%");

	// update numeric displays
	var times = $.extend({}, this._clockDigits(duration, 'dur'), this._clockDigits(position, 'elp'), this._clockDigits(duration-position, 'rem'));	
	
	$.each(this.controlElements, function(key, dom) {
	    if (times[key]) {
		$.each(dom, function() {		  
		    $(this).html(times[key]);
		});
	    }
	})
	

    },
        
    drawUpdateProgressDisplay: function() {
	this.controlElements['loaded'].css("width", this.pp.getLoadProgress()+"%");
    },

    drawUpdateVolumeDisplay: function(volume) {

        if (this._vSliderAct==true) return;
	if (volume==undefined) return;
	clearTimeout(this._cTimer);

	var isVisible = this.cb.is(':visible'),
	    ref = this,
	    toggleMute = ( this.controlElements['mute'].hasClass('toggle') || this.controlElements['unmute'].hasClass('toggle') || this.getConfig('toggleMute') )
	    vknob = this.cb.find('.'+this.getConfig('cssClassPrefix')+'vknob'),
	    vslider = this.cb.find('.'+this.getConfig('cssClassPrefix')+'vslider');
	
	// hide volume mess in case volume is fixed
	this._active('mute', !this.getConfig('fixedVolume'));
	this._active('unmute', !this.getConfig('fixedVolume'));
	this._active('vmax', !this.getConfig('fixedVolume'));
	this._active('vknob', !this.getConfig('fixedVolume'));
	this._active('vmarker', !this.getConfig('fixedVolume'));
	this._active('vslider', !this.getConfig('fixedVolume'));
	
	
	
	// make controls visible in order to allow dom manipulations
	// remember current visibility state

	this.cb.stop(true, true).show();
	
    
	if (toggleMute) {
	    switch(parseFloat(volume)) {        
		case 0:	    
		    this._active('mute', false);
		    this._active('unmute', true);
		    this._active('vmax', true);
		    break;
		
		default:
		    this._active('mute', true);
		    this._active('unmute', false);
		    this._active('vmax', false);
		   //  vknob.css('left', volume*(vslider.width()-(vknob.width()/2))+"px");  
		    break;             
	    }
	}
	
	this.controlElements['vknob'].css('left', volume*(vslider.width()-(vknob.width()/2))+"px");  
	this.controlElements['vmarker'].css('width', volume*100+"%");
	
	this._cTimer=setTimeout(function(){ref.hidecb();},this.getConfig('fadeDelay'));
	
	// hide again - if necessary
	if (!isVisible) this.cb.hide();
    },

    drawPauseButton: function(event) {
	this._active('pause', true);
	this._active('play', false);
    },
    
    drawPlayButton: function(event) {
	this._active('pause', false);
	this._active('play', true);	
    },
    
    
    drawEnterFullscreenButton: function(event) {
	this._active('fsexit', false);
	this._active('fsenter', true);		
    },

    drawExitFullscreenButton: function(event) {
	this._active('fsexit', true);
	this._active('fsenter', false);		
    },


   /*******************************
          GUI Event LISTENERS
    *******************************/    
    playClk: function(evt) {
        this.pp.setPlay();     
    },
    
    pauseClk: function(evt) {
    	this.pp.setPause();
    },
    
    stopClk: function(evt) {
    	this.pp.setStop();
    },
        
    
    controlsMouseEnterListener: function(evt) {
        this._noCHide = true;       
    },
     
    controlsMouseLeaveListener: function(evt) {
        this._noCHide = false;
    },
     
    controlsClk: function(evt) {    
          
    },   
    
    mousemoveHandler: function(evt) {
	this.showcb();            	
    },
    
    mouseleaveHandler: function(evt) {
	if (this.pp.getIsMobileClient()) return;
	var ref = this;
	clearTimeout(this._cTimer);
	this._noCHide = false;
	this._cTimer=setTimeout(function(){ref.hidecb();},800);	
    },
    
    prevClk: function(evt) {
    	this.pp.setActiveItem('previous');
           	
    },
    
    nextClk: function(evt) {
    	this.pp.setActiveItem('next');
    },
    
    forwardClk: function(evt) {
    	this.pp.setPlayhead('+10');
    },
    
    rewindClk: function(evt) {
    	this.pp.setPlayhead('-10');
    },    
    
    muteClk: function(evt) {
	this._storeVol = (this.pp.getVolume()==0) ? this.getConfig('volume') : this.pp.getVolume();
    	this.pp.setVolume(0);	
    },
    
    unmuteClk: function(evt) {
	if (this._storeVol<=0) this._storeVol = 1;
    	this.pp.setVolume(this._storeVol);
    },
    
    vmaxClk: function(evt) {
    	this.pp.setVolume(1);
    },        
    
    enterFullscreenClk: function(evt) {
        this.pp.setFullscreen(true);
    },    
    
    exitFullscreenClk: function(evt) {
        this.pp.setFullscreen(false);
    },
    
    openCloseClk: function(evt) {
	var ref = this;
	$($(evt.currentTarget).attr('class').split(/\s+/)).each(function(key, value) {
	    if (value.indexOf('toggle')==-1) return;
	    ref.playerDom.find('.'+value.substring(6)).slideToggle('slow', function() {
		ref.pp.setResize();
	    });
	    ref.controlElements['open'].toggle();
	    ref.controlElements['close'].toggle()
	});
    },
    
    loopClk: function(evt) {
	if ( $.inArray( this.getConfig('cssClassPrefix')+'loopon', $(evt.currentTarget).attr('class').split(/\s+/))>-1 ) {
	    this.pp.setLoop(true);
	} else {
	    this.pp.setLoop(false);
	}
        this.drawUpdateControls();	
    },

    startClk: function(evt) {          
        this.pp.setPlay();
    },  
                      
    scrubberClk: function(evt) {
        var result = 0;
	if (this.getConfig('disallowSkip')==true) return;
	if (this._sSliderAct) return;

    	var sliderIdx = (this.pp.getInFullscreen()===true && this.controlElements['vslider'].length > 1) ? 1 : 0,
	    totalWidth = $(this.controlElements['scrubber'][sliderIdx]).width(),
	    loadedWidth = $(this.controlElements['loaded'][sliderIdx]).width(),
	    pageX = (evt.originalEvent.touches) ? evt.originalEvent.touches[0].pageX :  evt.originalEvent.pageX,
	    requested = pageX - $(this.controlElements['scrubber'][sliderIdx]).offset().left;

    	if ( requested<0 || requested=='NaN' || requested==undefined) {
            result = 0;
        }
        else if (loadedWidth!= undefined ) {
            if (requested > loadedWidth) requested = loadedWidth-1;
            result = ((requested * 100 / totalWidth) * this.pp.getDuration() / 100)*1.00;
        }
        this.pp.setPlayhead(result);    	  
    },       
            
    vmarkerClk: function(evt) {
	vsliderClk(evt);
    },
    
    vsliderClk: function(evt) {
	if (this._vSliderAct==true) return;
	var sliderIdx = (this.pp.getInFullscreen()===true && this.controlElements['vslider'].length > 1 ) ? 1 : 0,
	    slider = $(this.controlElements['vslider'][sliderIdx]),
	    totalWidth = slider.width(),
	    pageX = (evt.originalEvent.touches) ? evt.originalEvent.touches[0].pageX :  evt.originalEvent.pageX,
	    requested = pageX - slider.offset().left;

    	if ( requested<0 || requested=='NaN' || requested==undefined) {
            result = 0;
        } else {
	    result = (requested / totalWidth);
	}
	
	this.pp.setVolume(result);
	this._storeVol = result;
    },
    
    scrubberdragStartDragListener: function(event) {

	if (this.getConfig('disallowSkip')==true) return;
	this._sSliderAct = true;
	
        var ref = this,	    
	    sliderIdx = (this.pp.getInFullscreen()===true && this.controlElements['scrubber'].length > 1) ? 1 : 0,
	    slider = $(this.controlElements['scrubberdrag'][sliderIdx]),
	    loaded = $(this.controlElements['loaded'][sliderIdx]),
	    second = 0,
	    dx = Math.abs( parseInt(slider.offset().left) - event.clientX),
	    
	    applyValue = function(event) {
		
		var newPos = Math.abs(slider.offset().left - event.clientX);		
		newPos = (newPos > slider.width()) ? slider.width() : newPos;		
		newPos = (newPos > loaded.width()) ? loaded.width() : newPos;
		newPos = (newPos < 0) ? 0 : newPos;
		newPos = Math.abs(newPos/slider.width())*ref.pp.getDuration();
		
		// avoid strange "mouseMove"-flooding in IE7+8
		if (newPos>0 && newPos!=second) {		    
		    second = newPos;
		    ref.pp.setPlayhead(second);
		}
		
	    },
	    
	    mouseUp = function(mouseupevt) {		
		if($.browser.msie) { mouseupevt.cancelBubble=true;} else {mouseupevt.stopPropagation();}
		
		ref.playerDom.unbind('mouseup', mouseUp);
		slider.unbind('mousemove', mouseMove);
		slider.unbind('mouseup', mouseUp);		
		ref._sSliderAct = false;
    
		return false;
	    },
	    
	    mouseMove = function(dragevent) {

		clearTimeout(ref._cTimer);
		if($.browser.msie) {dragevent.cancelBubble=true;} else {dragevent.stopPropagation();}
		
		applyValue(dragevent);

		return false;
	    };

	this.playerDom.mouseup(mouseUp);	
	slider.mouseup(mouseUp);
	slider.mousemove(mouseMove);

	applyValue(event);	
	
    },    
    
    vknobStartDragListener: function(event, domObj) {
	this._vSliderAct = true;
	
        var ref = this,	    
	    sliderIdx = (this.pp.getInFullscreen()===true && this.controlElements['vslider'].length > 1) ? 1 : 0,
	    knob = $(domObj[sliderIdx]),
	    slider = $(this.controlElements['vslider'][sliderIdx]),
	    slider = $(this.controlElements['vslider'][sliderIdx]),
	    dx = Math.abs(parseInt(knob.position().left) - event.clientX),
	    volume = 0,
	    mouseUp = function(mouseupevt) {		

		ref.playerDom.unbind('mouseup', mouseUp);
		slider.unbind('mousemove', mouseMove);
		slider.unbind('mouseup', mouseUp);
		knob.unbind('mousemove', mouseMove);
		knob.unbind('mouseup', mouseUp);
		
		ref._vSliderAct = false;
    
		return false;
	    },
	    
	    mouseMove = function(dragevent) {

		clearTimeout(ref._cTimer);
	    
		var newXPos = (dragevent.clientX - dx);
		newXPos = (newXPos > slider.width()-knob.width()/2) ? slider.width()-(knob.width()/2) : newXPos;
		newXPos = (newXPos < 0) ? 0 : newXPos;
		knob.css('left', newXPos+'px');
		volume = Math.abs(newXPos/(slider.width()-(knob.width()/2)));
		ref.pp.setVolume(volume);
		ref._storeVol = volume;
		$(ref.controlElements['vmarker'][sliderIdx]).css('width', volume *100+"%");
		return false;
	    };

        // this.playerDom.mousemove(mouseMove);
	this.playerDom.mouseup(mouseUp);

	slider.mousemove(mouseMove);
	slider.mouseup(mouseUp);
	
	knob.mousemove(mouseMove);
	knob.mouseup(mouseUp);

    },
    
    handleStartDragListener: function(evt, domObj) {

        var ref = this;
        var dx = Math.abs(parseInt(this.cb.position().left) - evt.clientX);
	var dy = Math.abs(parseInt(this.cb.position().top) - evt.clientY);
		
	/*
	this._initalPosition = {
	    top: this.cb.css('top'),
	    bottom: this.cb.css('bottom'),
	    left: this.cb.css('left'),
	    right: this.cb.css('right')
	    
	};
	*/
	// this._initalPosition = $.extend({}, this.cb.attr('style'), this.cb.css());
	
        
	var mouseUp = function(event){
	    if($.browser.msie) {event.cancelBubble=true;} else {event.stopPropagation();}
            ref.playerDom.unbind('mouseup', mouseUp);
	    ref.playerDom.unbind('mouseout', mouseUp);	    	    
            ref.playerDom.unbind('mousemove', mouseMove);
            return false;
        }
	
	var mouseMove = function(event){
	    if($.browser.msie) {event.cancelBubble=true;} else {event.stopPropagation();}
            clearTimeout(ref._cTimer);
            var newXPos = (event.clientX - dx);
            newXPos = (newXPos > ref.playerDom.width()-ref.cb.width()) ? ref.playerDom.width()-ref.cb.width() : newXPos;      
            newXPos = (newXPos < 0) ? 0 : newXPos;      
            ref.cb.css('left', newXPos+'px');
            var newYPos = (event.clientY - dy);
            newYPos = (newYPos > ref.playerDom.height()-ref.cb.height()) ? ref.playerDom.height()-ref.cb.height() : newYPos;      
            newYPos = (newYPos < 0) ? 0 : newYPos;      
            ref.cb.css('top', newYPos+'px');
            return false;
        }	

        this.playerDom.mousemove(mouseMove);
	this.playerDom.mouseup(mouseUp);
	// this.playerDom.mouseout(mouseUp);
    },
        
    errorHandler: function(value) {
	this.hidecb(true);
	
    },
    
    
    /*******************************
            GENERAL HELPERS
    *******************************/
    _active: function(elmName, on) {
	if (on==true) this.controlElements[elmName].addClass('active').removeClass('inactive');
	else this.controlElements[elmName].addClass('inactive').removeClass('active');	
    },
    
    /* convert a num of seconds to a digital-clock like display string */
    _clockDigits: function(secs, postfix) {

        if (secs<0 || isNaN (secs) || secs==undefined) {secs = 0;}

	var hr = Math.floor(secs / (60 * 60));

	var divisor_for_minutes = secs % (60 * 60);
	var min = Math.floor(divisor_for_minutes / 60);
	
	var divisor_for_seconds = divisor_for_minutes % 60;
	var sec = Math.floor(divisor_for_seconds);

	var result = {}
	result['min_'+postfix] = (min<10) ?"0"+min : min;
	result['sec_'+postfix] = (sec<10) ? "0"+sec : sec;
	result['hr_'+postfix] = (hr<10) ? "0"+hr : hr;
	
	return result;
    }
}
});

/*
 * Projekktor II Plugin: Display
 * VERSION: 1.0
 * DESC: Provides a standard display for cover-art, video or html content
 * features startbutton, logo-overlay and buffering indicator
 * Copyright 2010, 2011, Sascha Kluger, Spinning Airwhale Media, http://www.spinningairwhale.com
 *
 * under GNU General Public License
 * http://www.projekktor.com/license/
 */
var projekktorDisplay = function(){};
jQuery(function($) {
projekktorDisplay.prototype = {
    
    logo: null,
    logoIsFading: false,
    
    display: null,
    
    displayClicks: 0,
    
    buffIcn: null,
    buffIcnSprite: null,
    bufferDelayTimer: null,
    
    config: {
	
	onclick:		{callback: 'setPlayPause', value: null},
	// onclick: 		{callback: 'openUrl', value: {url:'http://www.google.de', target: '_self', pause: true}},
	onclick_playing:	{callback: 'setPlayPause', value: null},
	ondblclick:		{callback: 'setFullscreen', value: null},
	
	/* time to delay buffering-icon-overlay once "waiting" event has been triggered */
	bufferIconDelay: 	1000,
	
	/* if set the indicator animation is tinkered from a cssprite - must be horizontal */
	spriteUrl:		'',
	spriteWidth:		50,
	spriteHeight:		50,
	spriteTiles:		25,
	spriteOffset:		1,
	spriteCountUp:		false,
	
	/* Plugin: Logo - URL to your logo file */
	logoImage:		'',
	
	/* Plugin: Logo - Seconds to be played back before logo fades in, 0=instantly */
	logoDelay:	0,
	
	/* position of the logo => tl=top left, tr, br, bl */
	logoPosition:	'tl',
	
	/* if set clicking the logo will open the given url */
	onlogoclick:	{callback: '', value: {url:'', target: '_blank', pause: true}}
	/* {callback: 'openUrl', value: {url:'http://www.google.de', target: '_blank', pause: true}} */
	
    },
    
    _imgDummy: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAABBJREFUeNpi/v//PwNAgAEACQsDAUdpTjcAAAAASUVORK5CYII=',
    
    
    /* triggered on plugin-instanciation */
    initialize: function() {
	var ref = this;
	
	// the default CSS for all "display" relevant layers
	var css = {
	    position: 'absolute',
	    overflow: 'hidden',
	    height: '100%',
	    width: '100%',
	    top: 0,
	    left: 0,
	    padding: 0,
	    margin: 0,
	    display: 'block'
	}
	
	// create the startbutton
	this.startButton =  this.applyToPlayer(
	    $(document.createElement('div'))
		.addClass('start')
	).addClass('inactive');

	// create buffericon
	this.buffIcn = this.applyToPlayer(
	    $(document.createElement('div'))
	    .addClass('buffering')	    
	).addClass('inactive');

	// add spritelayer to buffericon (if required)
	if (this.config.spriteUrl!='') {
	    this.buffIcnSprite = $(document.createElement('div'))
		.appendTo(this.buffIcn)
		.css({
		    width: this.config.spriteWidth,
		    height: this.config.spriteHeight,
		    marginLeft: ((this.buffIcn.width()-this.config.spriteWidth) / 2)+"px",
		    marginTop: ((this.buffIcn.height()-this.config.spriteHeight) / 2)+"px",
		    backgroundColor: 'transparent',
		    backgroundImage: 'url('+this.config.spriteUrl+')',
		    backgroundRepeat: 'no-repeat',
		    backgroundPosition: '0 0'
		})
		.addClass('inactive')
	}	
		
	// create the display container itself
	this.display = this.applyToPlayer(
	    $(document.createElement('div'))
		.addClass('display')
		.css(css)
	);

	// create a dedicated media container (if none exists)
	this.pp.getMediaContainer();

	// create logocontainer:
	this.logo = this.applyToPlayer(
	    $(document.createElement('img'))
		.addClass('logo')
		.addClass('inactive')
		.error(function() {
		    $(this)
			.attr('src', this._imgDummy)
			.addClass('inactive')
			.removeClass('active')
		})
		.attr('src', this._imgDummy)
		.css('position', 'absolute')
		.css(((this.getConfig('logoPosition').indexOf('r')>-1) ? 'right' : 'left'), '2%')
		.css(((this.getConfig('logoPosition').indexOf('t')>-1) ? 'top' : 'bottom'), '2%')
	);

	this.pluginReady = true;    
    },



    /*****************************************
        EVENT HANDLERS
    *****************************************/
    displayReadyHandler: function() {
	var ref = this;


	// the startbutton
	this.startButton.unbind();
    	this.startButton.click(function(){
	    ref.pp.setPlay();
	});

    },

    syncingHandler: function() {
	this.showBufferIcon();
	if (this.pp.getState('IDLE'))
	    this.startButton.addClass('inactive').removeClass('active');
    },
    
    readyHandler: function() {
	this.hideBufferIcon();
	if (this.pp.getState('IDLE'))
	    this.startButton.addClass('active').removeClass('inactive');
    },    
    
    bufferHandler: function(state) {

	if (!this.pp.getState('PLAYING') && !this.pp.getState('AWAKENING'))
	    return;
	if (state=='EMPTY') this.showBufferIcon();
	else this.hideBufferIcon();
    },    
    
    stateHandler: function(state) {
	// handle startbutton
	if (state==='IDLE') 
	    this.startButton.addClass('active').removeClass('inactive');
	else 
	    this.startButton.addClass('inactive').removeClass('active');	
	
	// handle buffericon
	if (state=='AWAKENING' || state=='COMPLETED' || state=='ERROR') {	    
	    this.hideBufferIcon();;
	}
	
	// handle logo
	if (state=='ERROR' || state==='STOPPED')
	    this.logo.addClass('inactive').removeClass('active');
    },
  
    stoppedHandler: function() {
	this.hideBufferIcon();
    },
  
    scheduleLoadingHandler: function() {
	 this.startButton.addClass('inactive').removeClass('active');
	 this.showBufferIcon(); 
    },
    
    scheduledHandler: function() {
	if (!this.getConfig('autoplay')) {
	    this.startButton.addClass('active').removeClass('inactive');
	}
	
	this.hideBufferIcon();
    },
    
    itemHandler: function() {

	var ref = this;
	
	// buffering icon
	this.hideBufferIcon();
	
	// logo overlay
	// hide old one
	this.logoIsFading = false;
	this.logo.stop(true, true)
	    .addClass('inactive')
	    .removeClass('active')
	    .attr('src', this._imgDummy)
	    .unbind();


	if (this.getConfig('logoImage')!=null) {
	    this.logo
		.attr('src', this.getConfig('logoImage'))
		.css({cursor: (this.getConfig('logoURL')!='') ? 'pointer' : 'normal'})
		.click(		    
		    function(){
			try { ref.pp[ref.getConfig('onlogoclick').callback](ref.getConfig('onlogoclick').value); } catch(e){}
			return false;
		    }		
		)
		.removeClass('inactive')
		.hide();
	}
	
    },    
    
    timeHandler: function() {

	// manage delayed logo fade:
	if (this.getConfig('logoImage')==false) return;
	if (this.pp.getIsMobileClient()) return;
	
	// get required player data
	var timeIndex = this.pp.getPosition(),
	    itemDuration = this.pp.getDuration(),
	    ref = this;

	// fade logo in after <this.config.logoDelay> seconds of playback
	if (!this.logo.hasClass('inactive') && !this.logoIsFading && timeIndex+this.config.logoDelay < itemDuration) {
	    if (timeIndex>this.config.logoDelay && itemDuration>(this.config.logoDelay*2)) {
		this.logoIsFading=true;
		this.logo.fadeIn('slow', function() {
		    ref.logoIsFading=false;		    
		    $(this).addClass('active').removeClass('inactive').css('display', '');
		});   
	    }
	}
	
	// fade logo out <this.config.logoDelay> seconds before end of item
	if (this.logo.hasClass('active') && !this.logoIsFading) {
	    if (timeIndex+this.config.logoDelay > itemDuration ) {
		this.logoIsFading=true;
		this.logo.fadeOut('slow', function(){
		    $(this).addClass('inactive').removeClass('active').css('display', '');
		    ref.logoIsFading=false;
		});   
	    }
	}
	
    },    
    
    /*****************************************,
        DISPLAY: Click handling
    *****************************************/          
    leftclickHandler: function(evt) {

	var ref = this;
	
	if($(evt.target).attr('id').indexOf('_media')==-1)
	    return;

	switch(this.pp.getState()) {
	    case 'ERROR':
		this.pp.setActiveItem('next');
		return;
	    case 'IDLE':
		this.pp.setPlay();
		return;
	    
	}
	
	if (this.pp.getHasGUI()==true) 	    
	    return;

	this.displayClicks++;
	
	if (this.displayClicks > 0) {
	    setTimeout(
		function(){		    
		    if(ref.displayClicks == 1) {
			if (ref.pp.getState()=='PLAYING')
			    try { ref.pp[ref.getConfig('onclick_playing').callback](ref.getConfig('onclick_playing').value); } catch(e){}
			else 
			    try { ref.pp[ref.getConfig('onclick').callback](ref.getConfig('onclick').value); } catch(e){}
		    } else if(ref.displayClicks == 2) {
			try { ref.pp[ref.getConfig('ondblclick').callback](ref.getConfig('ondblclick').value); } catch(e){}
		    }
		    ref.displayClicks = 0;
		}, 250
	    );	
	}
	return;
    },
    
    
    /*****************************************
        BUFFERICON: fader / animator
    *****************************************/       
    hideBufferIcon: function() {
	
        var ref=this;
        
	clearTimeout(this.bufferDelayTimer);   	           
	this.buffIcn.stop(true, true);	
	
	this.buffIcn.fadeOut('fast', function() {
	    $(this).addClass('inactive').removeClass('active').css('display', '');
	});
    },
        
    showBufferIcon: function(instant) {                        
	var ref=this;

        clearTimeout(this.bufferDelayTimer);

	if (this.pp.getHasGUI())
	    return;
	
	if ( (this.pp.getModel()==='YTAUDIO' || this.pp.getModel()==='YTVIDEO') && !this.pp.getState('IDLE'))
	    instant=true;

        if (instant!=true && this.getConfig('bufferIconDelay')>0) {
            this.bufferDelayTimer=setTimeout(function(){ref.showBufferIcon(true);},this.getConfig('bufferIconDelay'));	    
	    return;
        }

        this.buffIcn.stop(true, true);
	if (this.buffIcn.hasClass('active') ) return;
	
        this.buffIcn.fadeIn('fast', function() {
	    if (ref.buffIcnSprite==null) return;
	    var spriteOffset=(ref.config.spriteCountUp==true) ? 0 : (ref.config.spriteHeight + ref.config.spriteOffset)*ref.config.spriteTiles;
	    ref.buffIcnSprite.addClass('active').removeClass('inactive').css('display', '');
	    (function() {
		if (!ref.buffIcn.is(':visible')) return;
		ref.buffIcnSprite.css('backgroundPosition', '0px -'+spriteOffset+"px")
		if (ref.config.spriteCountUp==true)
		    spriteOffset += ref.config.spriteHeight + ref.config.spriteOffset;
		else
		    spriteOffset -= ref.config.spriteHeight + ref.config.spriteOffset;
		if (spriteOffset >= ref.config.spriteHeight*ref.config.spriteTiles) spriteOffset = 0;
		setTimeout(arguments.callee,60);
	    })(); 
	});
    }    
}
});
