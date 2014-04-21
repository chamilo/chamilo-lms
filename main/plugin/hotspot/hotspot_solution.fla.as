// javascript Proxy
import com.macromedia.javascript.JavaScriptProxy;
var JS_proxy:JavaScriptProxy = new JavaScriptProxy();

System.useCodepage = true;

// get id from querystring
myURL = this._url;
//myURL = "http://localhost/dokeosSVN/main/plugin/hotspot/hotspot_solution.swf?modifyAnswers=1&exe_id=2&from_db=3";
tmpAr = myURL.split("?");
tmpAr = tmpAr[1].split("&");
id = tmpAr[0].split("=");
id = id[1];
exe_id = tmpAr[1].split("=");
exe_id = exe_id[1];
from_db = tmpAr[2].split("=");
from_db = from_db[1];

// get URL to load lang and hotspot variables

//myURL = this._url;
myPos = myURL.lastIndexOf("/");
myURL = myURL.substring(0, myPos);
myPos = myURL.lastIndexOf("/");
myURL = myURL.substring(0, myPos);
myPos = myURL.lastIndexOf("/");
myURL = myURL.substring(0, myPos);

// movie properties
var movieWidth:Number = 380;
var movieHeight:Number = 400;

// hotspot properties
var startWidth:Number = 0;		//  \__ if these values are set, shape will be drawn right and down from the mouse;
var startHeight:Number = 0;		//	/
var pType:String = ""; 			// possible values: circle, square, poly;

// other settings
var i:Number = 1;
var isDrawing:Boolean = false;
var hotspots_c:Array = new Array('0x4271B5','0xFE8E16','0x45C7F0','0xBCD631','0xD63173','0xD7D7D7','0x90AFDD','0xAF8640','0x4F9242','0xF4EB24','0xED2024','0x3B3B3B');

// make array with all elements
var hotspots:Array = new Array();

// get data from database
this.createEmptyMovieClip("target_mc", 2000);
//_answers
loadVariables(myURL + "/exercice/hotspot_answers.as.php?modifyAnswers="+id+"&exe_id="+exe_id+"&from_db="+from_db, target_mc);
var param_interval:Number = setInterval(checkParamsLoaded, 100);

// language variables
var str_choose:String = "";
var lang_interval:Number

// counter for language (if language can't be loaded after 2 sec,
// english will be loaded
var counter:Number = 0;

// booleans for loading
var langLoaded:Boolean = false;
var varsLoaded:Boolean = false;
var imageLoaded:Boolean = false;

// image preloader
var loadListener:Object = new Object();
loadListener.onLoadComplete = function(){
	_root.imageLoaded = true;
	_root.showInterface();
}
var mcLoader:MovieClipLoader = new MovieClipLoader();
mcLoader.addListener(loadListener);
var mc:MovieClip = _root.image_mc;

/////////////////////////////////////////////////////////////////////////////////
// FUNCTIONS
/////////////////////////////////////////////////////////////////////////////////

// show hotspots, user can't click anymore
function showHotspots(num){
	for(j=1;j<=_root.hotspots.length;j++){
		_root.map_mc["hotspot"+j]._alpha = num;		
	}
}

function showUserpoints(num){
	for(j=1;j<=_root.hotspots.length;j++){
		_root["hotspot_answer"+j]._alpha = num;
		if(num==0){
			_root["hotspot_answer"+j].hotspot_mc._visible = false;
		}else{
			_root["hotspot_answer"+j].hotspot_mc._visible = true;
		}		
	}
}

function showInterface(){	
	if((_root.langLoaded == true) and (_root.varsLoaded == true) and (_root.imageLoaded == true)){
		_root.showUserpoints(100);
		_root.showHotspots(100);
		_root.loading_mc._visible = false;
	}	
}

// get language file
function getLang(){
	counter++;
	if (_root.lang_mc.done != undefined) {
		_root.show_userPoints_btn.label = _root.lang_mc.showUserPoints;
		_root.show_hotspots_btn.label = _root.lang_mc.showHotspots;
		
		// check if interface can be showed (if everything else is loaded)
		_root.langLoaded = true;
		_root.showInterface();
		
		// clear interval
		clearInterval(_root.lang_interval);		
	 }else if(counter==20){
		// clear interval
		clearInterval(_root.lang_interval);
		
		// set counter to zero
		_root.counter = 0;
		
		// set new interval (get english version)
		loadVariables(myURL + "/lang/english/hotspot.inc.php", _root.lang_mc);
		_root.lang_interval = setInterval(getLang, 100);
		
	}
}

function checkParamsLoaded() {
	 if (target_mc.done != undefined) {
		// once we know the language, get language-variables from language file
		language = target_mc["hotspot_lang"];
		_root.createEmptyMovieClip("lang_mc", 2001);
		
		//loadVariables(myURL + "/lang/" + language + "/hotspot.inc.php", _root.lang_mc);
		loadVariables(myURL + "/exercice/hotspot.inc.php", _root.lang_mc);
		
		_root.lang_interval = setInterval(getLang, 100);
		 
		// start loading external image
		_root.mcLoader.loadClip("../../courses/" + target_mc["courseCode"] + "/document/images/"  + target_mc["hotspot_image"], mc);
						
		// make needed array's: filled if hotspot exists, empty if not
		for(m = 1; m <= 12; m++){	
			// make seperate lists
			if(target_mc["hotspot_" + m]=="true"){
				// add to general list
				tmpAr = Array();
				tmpAr.push(m);
				tmpAr.push(target_mc["hotspot_" + m + "_type"]);				
				_root.hotspots.push(tmpAr);
				
				if (target_mc["hotspot_" + m + "_type"] != 'poly' &&  target_mc["hotspot_" + m + "_type"] != 'delineation'){
					// x;y|height|width
					_root["p_hotspot_" + m] = new Array;					
					$coordinates = target_mc["hotspot_" + m + "_coord"].split("|");
					$tmp_xy = $coordinates[0].split(";");
					$x = $tmp_xy[0];
					$y = $tmp_xy[1];
					$height = $coordinates[1];
					$widht = $coordinates[2];
					_root["p_hotspot_" + m].push($x);
					_root["p_hotspot_" + m].push($y);
					_root["p_hotspot_" + m].push($height);
					_root["p_hotspot_" + m].push($widht);
				}else{               
					// p1_x;p1_y|p2_x;p2_y|...
					_root["p_hotspot_" + m] = new Array();
					$coordinates = target_mc["hotspot_" + m + "_coord"].split("|");
					
					for(k=0;k<$coordinates.length;k++){
						$tmp_xy = $coordinates[k].split(";");
						tempArray = Array();
						tempArray.push($tmp_xy[0]);
						tempArray.push($tmp_xy[1]);
						_root["p_hotspot_" + m].push(tempArray);
					}
				}
			}else{
				_root["p_hotspot_" + m] = Array();
			}
		}
				
		// set border around image
		_root.map_mc._width = int(target_mc.hotspot_image_width) + 1;
		_root.map_mc._height = int(target_mc.hotspot_image_height) + 1;
		
		// draw the hotspots
		drawShapes();
		
		// check if interface can be showed (if everything else is loaded)
		_root.varsLoaded = true;
		_root.showInterface();
		
		// clear interval
		clearInterval(param_interval);
	 }
}

// draw the shapes that are given from the database
function drawShapes(){
	// draw points where user clicked
	_root.drawPoints();
	
	// draw hotspots
	for (var j:String in hotspots) {
		if(j <> ""){
			// +1 because array names starts from 1
			_root.i = int(j) + 1;	
			// -1 because array values starts from 0
			_root.pType = _root.hotspots[_root.i - 1][1];
			if(_root.pType=="poly" || _root.pType=="delineation"){
				drawPoly();
			}
			else{
				drawShape(true);
			}			
			_root.map_mc["hotspot" + _root.i]._alpha = 0;
		}
	}
}

function drawPoints(){	
	answers = _root.target_mc.p_hotspot_answers.split("|");
	
	if(answers[0]!=''){
		j = 1;
		k = 500;
			
		for(var z:String in answers){
			if(target_mc["hotspot_"+j+"_type"]=="delineation")
			{
				// trace poly
				delineation_coords = answers[j-1].split("/");
				drawDelineation(j, delineation_coords);
			}
			else
			{
			
				xy = answers[j-1].split(";");
				$x = xy[0];
				$y = xy[1];
			
				// create new hotspot
				_root.createEmptyMovieClip("hotspot_answer" + j, k);		
				
				// attach correct type of hotspot
				_root["hotspot_answer" + j].attachMovie("numbers", "hotspot_mc", _root["hotspot_answer" + j].getNextHighestDepth());
				
				_root["hotspot_answer" + j].hotspot_mc._width = 33;
				_root["hotspot_answer" + j].hotspot_mc._height = 22;
				
				_root["hotspot_answer" + j].hotspot_mc._x = int($x) + _root.map_mc._x;
				_root["hotspot_answer" + j].hotspot_mc._y = int($y) + _root.map_mc._y;
				
				_root["hotspot_answer" + j].hotspot_mc.order_txt.text = int(j);		
				_root["hotspot_answer" + j].hotspot_mc._visible = false;
				
				_root["hotspot_answer" + j]._alpha = 0;
			}
			
			j++;
			k++;
		}
	}
	
	
}

function drawShape(userDrawing){
	// create new hotspot
	_root.map_mc.createEmptyMovieClip("hotspot" + _root.i, _root.i);
	
	// attach correct type of hotspot
	_root.map_mc["hotspot" + _root.i].attachMovie(_root.pType, "hotspot_mc", _root.map_mc["hotspot" + _root.i].getNextHighestDepth());
	
	_root.map_mc["hotspot" + _root.i].hotspot_mc._visible = true;
	_root.map_mc["hotspot" + _root.i].hotspot_mc.center_mc._alpha = 60;
	_root.map_mc["hotspot" + _root.i].hotspot_mc._x = _root["p_hotspot_"+ _root.i][0];
	_root.map_mc["hotspot" + _root.i].hotspot_mc._y = _root["p_hotspot_"+ _root.i][1];
	_root.map_mc["hotspot" + _root.i].hotspot_mc._width = _root["p_hotspot_"+ _root.i][2];
	_root.map_mc["hotspot" + _root.i].hotspot_mc._height = _root["p_hotspot_"+ _root.i][3];
	
	colorchange = new Color(_root.map_mc["hotspot" + _root.i].hotspot_mc);	
	colorchange.setRGB(_root.hotspots_c[_root.i - 1]);
}
// when black lines of hotspots are deleted, draw the exact same poly with coordinates
// that are saved in the array
function drawPoly(){	
	// create empty movieclip
	_root.map_mc.createEmptyMovieClip("hotspot" + _root.i, _root.i);
	
	// begin filling the movieclip
	_root.map_mc["hotspot" + _root.i].beginFill(_root.hotspots_c[_root.i - 1], 60);
	
	// set linestyle
	_root.map_mc["hotspot" + _root.i].lineStyle(1, _root.hotspots_c[_root.i - 1], 100);
	
	// move mouse to first coordinate
	_root.map_mc["hotspot" + _root.i].moveTo(_root["p_hotspot_"+_root.i][0][0],_root["p_hotspot_"+_root.i][0][1]);
	
	// draw lines to all coordinates
	v = _root["p_hotspot_"+_root.i].length;	
	for (k=1;k<v;k++){
		_root.map_mc["hotspot" + _root.i].lineTo(_root["p_hotspot_"+_root.i][k][0],_root["p_hotspot_"+_root.i][k][1]);		
	}
	
	// attach first and last coordinates
	_root.map_mc["hotspot" + _root.i].lineTo(_root["p_hotspot_"+_root.i][0][0],_root["p_hotspot_"+_root.i][0][1]);					  
	
	// stop filling the movieclip
	_root.map_mc["hotspot" + _root.i].endFill();
}

function drawDelineation(level, coords){	
	
	// create empty movieclip
	_root.map_mc.createEmptyMovieClip("hotspot_delineation" + level, 2000+level);

	// begin filling the movieclip
	_root.map_mc["hotspot_delineation" + level].beginFill(0xFFFFFF, 60);
	
	// set linestyle
	_root.map_mc["hotspot_delineation" + level].lineStyle(1,0x000000, 100);
	
	// move mouse to first coordinate
	xy_origin = coords[0].split(";");
	_root.map_mc["hotspot_delineation" + level].moveTo(xy_origin[0],xy_origin[1]);
	
	// draw lines to all coordinates
	v = coords.length;	
	for (k=1;k<v;k++){
		xy = coords[k].split(";");
		_root.map_mc["hotspot_delineation" + level].lineTo(xy[0],xy[1]);		
	}
	
	// attach first and last coordinates
	_root.map_mc["hotspot_delineation" + level].lineTo(xy_origin[0],xy_origin[1]);					  
	
	// stop filling the movieclip
	_root.map_mc["hotspot_delineation" + level].endFill();
}

function jsdebug(debug_string){
	_root.JS_proxy.jsdebug(debug_string);
}
