function getE(id)
{
	if(document.getElementById) {
		return document.getElementById(id);
	} else if(document.all) {
		return document.all[id];
	} else return;
}

function openClose(id,mode)
{
	element = getE(id);
	img = getE('img_'+id);
	
	if(element.style) {
		if(mode == 0) {
			if(element.style.display == 'block' ) {
				element.style.display = 'none';
				img.src = 'pics/plus.gif';
			} else {
				element.style.display = 'block';
				img.src = 'pics/moins.gif';
			}
		} else if(mode == 1) {
			element.style.display = 'block';
			img.src = 'pics/moins.gif';
		} else if(mode == -1) {
			element.style.display = 'none';
			img.src = 'pics/plus.gif';
		}
	}
}


