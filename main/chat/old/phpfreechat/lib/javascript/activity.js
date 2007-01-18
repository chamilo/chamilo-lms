var DetectActivity = Class.create();
DetectActivity.prototype = {
  initialize: function(subject)
  {
    this.onunactivate = function() {};
    this.onactivate   = function() {};
    this.subject = subject;
    this.isactive = true;
    Event.observe(subject, 'mousemove', this._OnFocus.bindAsEventListener(this), false);
    Event.observe(subject, 'mouseout', this._OnBlur.bindAsEventListener(this), false);
  },
  _OnFocus: function(e)
  {
    this.isactive = true;
    if (this.onactivate) this.onactivate();
  },
  _OnBlur: function(e)
  {
    this.isactive = false;
    if (this.onunactivate) this.onunactivate();
  },
  isActive: function()
  {
    return this.isactive;
  }
}



/*
// Unused code, by usefull for further auto idle features

  _launchTimeout: function(myself)
  {
var oldisactive =  this.isactive;
    if (this.oldposx == this.posx &&
        this.oldposy == this.posy)
      this.isactive = false;
    else
      this.isactive = true;
this.oldposx = this.posx;
this.oldposy = this.posy;
if (oldisactive != this.isactive) alert("switch");
    setTimeout(function() { myself._launchTimeout(myself); }, 1000);
  },

  _OnMouseMove: function(e)
  {
        var posx = 0;
	var posy = 0;
        if (!e) var e = window.event;
	if (e.pageX || e.pageY)
 	{
		posx = e.pageX;
		posy = e.pageY;
	}
	else if (e.clientX || e.clientY) 	
        {
		posx = e.clientX + document.body.scrollLeft
			+ document.documentElement.scrollLeft;
		posy = e.clientY + document.body.scrollTop
			+ document.documentElement.scrollTop;
	}
        this.posx = posx;
        this.posy = posy;
  },
*/
