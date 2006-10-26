function mousePosX(e) {
	var posx = 0;
	if (!e) var e = window.event;
	if (e.pageX) 	{
		posx = e.pageX;
	}
	else if (e.clientX) 	{
		posx = e.clientX + document.body.scrollLeft
			+ document.documentElement.scrollLeft;
	}
        return posx;
}

function mousePosY(e) {
	var posy = 0;
	if (!e) var e = window.event;
	if (e.pageY) 	{
		posy = e.pageY;
	}
	else if (e.clientY) 	{
		posy = e.clientY + document.body.scrollTop
			+ document.documentElement.scrollTop;
	}
        return posy;
}
