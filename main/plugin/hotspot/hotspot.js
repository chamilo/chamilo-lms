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

function getForm()
{
	form = document.create;
	if (form == null)
		form = document.update;
	if (form == null)
		form = document.vervangmij;

	return form;
}

function saveHotspot(question_id, hotspot_id, answer, hotspot_x, hotspot_y)
{
	form = getForm();
	newHotspot = document.createElement("input");
	newHotspot.type = "hidden";
	newHotspot.name = "hotspot["+question_id+"]["+hotspot_id+"]";
	newHotspot.value = hotspot_x + ";" + hotspot_y;	
	form.appendChild(newHotspot);
	
	newHotspot = document.createElement("input");
	newHotspot.type = "hidden";
	newHotspot.name = "choice["+question_id+"]["+hotspot_id+"]";
	newHotspot.value = answer;	
	form.appendChild(newHotspot);
}

function saveDelineationUserAnswer(question_id, hotspot_id, answer, coordinates)
{
	form = getForm();
	newHotspot = document.createElement("input");
	newHotspot.type = "hidden";
	newHotspot.name = "hotspot["+question_id+"]["+hotspot_id+"]";
	newHotspot.value = coordinates;	
	form.appendChild(newHotspot);
	
	newHotspot = document.createElement("input");
	newHotspot.type = "hidden";
	newHotspot.name = "choice["+question_id+"]["+hotspot_id+"]";
	newHotspot.value = answer;	
	form.appendChild(newHotspot);
}

function saveShapeHotspot(question_id, hotspot_id, type, x, y, w, h)
{
	form = getForm();
	control = "coordinates[" + (hotspot_id - 1) + "]";
	form[control].value = x + ";" + y + "|" + w + "|" + h;
	control = "type[" + (hotspot_id - 1) + "]";
	form[control].value = type;
}

function savePolyHotspot(question_id, hotspot_id, coordinates)
{
	form = getForm();
	control = "coordinates[" + (hotspot_id - 1) + "]";
	form[control].value = coordinates;
	control = "type[" + (hotspot_id - 1) + "]";
	form[control].value = "poly";
}

function saveDelineationHotspot(question_id, hotspot_id, coordinates)
{
	form = getForm();
	control = "coordinates[" + (hotspot_id - 1) + "]";
	form[control].value = coordinates;
	control = "type[" + (hotspot_id - 1) + "]";
	form[control].value = "delineation";
}
function jsdebug(debug_string)
{
	alert(debug_string);
}