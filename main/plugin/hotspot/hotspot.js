// in je FORM-tag voor de hotspots:  onSubmit="return validateFlashVar('MINIMUM_AANTAL_CLICKS');

var flashVar = 1;

var lcId = new Date().getTime();
//var flashProxy = new FlashProxy(lcId, "JavaScriptFlashGateway.swf");

function validateFlashVar(counter, lang_1, lang_2)
{
	return true;
	//alert(counter);
	//alert(flashVar);
	
	if(counter != flashVar)
	{
		alert(lang_1 + counter + lang_2);
		
		return false;
	}
	else
	{
		return true;
	}
}

function updateFlashVar()
{
	//alert('updateFlashVar: ' + flashVar);
	flashVar++;
}

/*
 * Save the results of the positioning of a point by the user on top of
 * the answer area.
 * @param   int     Question ID
 * @param   int     ID of the area (square or ellipse)
 * @param   int     Whether the answer is correct or not
 * @param   int     X-coordinate (horizontal) of the answer point
 * @param   int     Y-coordinate (vertical) of the answer point 
 */
function saveHotspot(question_id, hotspot_id, answer, hotspot_x, hotspot_y)
{
	newHotspot = document.createElement("input");
	newHotspot.type = "hidden";
	newHotspot.name = "hotspot["+question_id+"]["+hotspot_id+"]";
	newHotspot.value = hotspot_x + ";" + hotspot_y;	
	document.frm_exercise.appendChild(newHotspot);
	
	newHotspot = document.createElement("input");
	newHotspot.type = "hidden";
	newHotspot.name = "choice["+question_id+"]["+hotspot_id+"]";
	newHotspot.value = answer;	
	document.frm_exercise.appendChild(newHotspot);
}
/*
 * Save the results of the positioning of a point by the user on top of
 * the answer area ( for polygons and delineation )
 * @param   int     Question ID
 * @param   int     ID of the area (square or ellipse)
 * @param   int     Whether the answer is correct or not
 * @param   array   Coordinates (horizontal+vertical) of the answer points, in an array
 */
function saveDelineationUserAnswer(question_id, hotspot_id, answer, coordinates)
{
	newHotspot = document.createElement("input");
	newHotspot.type = "hidden";
	newHotspot.name = "hotspot["+question_id+"]["+hotspot_id+"]";
	newHotspot.value = coordinates;
	document.frm_exercise.appendChild(newHotspot);
	
	newHotspot = document.createElement("input");
	newHotspot.type = "hidden";
	newHotspot.name = "choice["+question_id+"]["+hotspot_id+"]";
	newHotspot.value = answer;	
	document.frm_exercise.appendChild(newHotspot);
}

function saveShapeHotspot(question_id, hotspot_id, type, x, y, w, h)
{
	document.frm_exercise["hotspot_coordinates["+hotspot_id+"]"].value = x + ";" + y + "|" + w + "|" + h;
	document.frm_exercise["hotspot_type["+hotspot_id+"]"].value = type;
}

function savePolyHotspot(question_id, hotspot_id, coordinates)
{
	document.frm_exercise["hotspot_coordinates["+hotspot_id+"]"].value = coordinates;
	document.frm_exercise["hotspot_type["+hotspot_id+"]"].value = "poly";
}

function saveDelineationHotspot(question_id, hotspot_id, coordinates)
{
	document.frm_exercise["hotspot_coordinates["+hotspot_id+"]"].value = coordinates;
	document.frm_exercise["hotspot_type["+hotspot_id+"]"].value = "delineation";
}
function jsdebug(debug_string)
{
	alert(debug_string);
}