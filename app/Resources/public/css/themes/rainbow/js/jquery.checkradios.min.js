/*
	CHECKRADIOS - jQuery plugin to allow custom styling of checkboxes
	by Chris McGuckin (https://github.com/cosmicwheels)
	
	
	License: MIT (http://opensource.org/licenses/MIT)
	
	
	Credits:
	---------------------------------------------------------------------------------
	
	icomoon (http://icomoon.io/)
	
	Please refer to the icomoon website for the license regarding their icons.
	
	---------------------------------------------------------------------------------
	
	Fontawesome (http://fortawesome.github.io/Font-Awesome/)
	
	Please refer to the fontawesome website for the license regarding their icons.
	
	---------------------------------------------------------------------------------
	
	Stephan Richter (https://github.com/strichter)
	
	Thanks to Stephan for pointing out and helping to add the triggering of events 
	to help further mimic the checkboxes and radios as well as providing other
	important bug fixes.
	
	---------------------------------------------------------------------------------
	---------------------------------------------------------------------------------
	

*/(function(e){e.fn.checkradios=function(t){var n=this,r=e.extend({checkbox:{iconClass:"icon-checkradios-checkmark"},radio:{iconClass:"icon-checkradios-circle"},onChange:function(e,t,n){}},t),i={checkbox:function(t){var n=this;if(!t.parent().hasClass("checkradios-checkbox")){var i=t.attr("class");i===undefined&&(i="");var s=t.wrap('<div class="checkradios-checkbox '+i+'"/>'),o=s.parent();s.is(":checked")?n.checkboxEnable(s):n.checkboxDisable(s);t.keypress(function(e){var t=e.keyCode;(t<1||t==13||t==32)&&o.click()});t.on({focusin:function(){o.addClass("focus")},focusout:function(){var t=e(this);setTimeout(function(){t.is(":focus")||o.removeClass("focus")},10)}});o.mousedown(function(){setTimeout(function(){t.focus()},10)});t.click(function(e){e.stopPropagation();e.preventDefault()});o.click(function(){if(s.is(":checked")){n.checkboxDisable(s);r.onChange(!1,o,s)}else{n.checkboxEnable(s);r.onChange(!0,o,s)}return!1})}},radio:function(t){var n=this;if(!t.parent().hasClass("checkradios-radio")){var i=t.attr("class");i===undefined&&(i="");var s=t.wrap('<div class="checkradios-radio '+i+'"/>'),o=s.parent();s.is(":checked")?n.radioEnable(s):n.radioDisable(s);t.on({focusin:function(){o.addClass("focus");n.radioEnable(s);var t=s.attr("name"),i=e('input[name="'+t+'"]');i.each(function(){if(e(this).is(":checked")){n.radioEnable(e(this));r.onChange(!0,e(this).parent(),e(this))}else{n.radioDisable(e(this));r.onChange(!1,e(this).parent(),e(this))}})},focusout:function(){var t=e(this);setTimeout(function(){t.is(":focus")||o.removeClass("focus")},10)}});o.click(function(){t.focus()});t.click(function(e){e.stopPropagation();e.preventDefault()})}},checkboxEnable:function(e){e.parent().removeClass(r.checkbox.iconClass);e.parent().removeClass("unchecked");e.parent().addClass(r.checkbox.iconClass);e.parent().addClass("checked");e.prop("checked",!0).trigger("change")},checkboxDisable:function(e){e.parent().removeClass(r.checkbox.iconClass);e.parent().addClass("unchecked");e.prop("checked",!1).trigger("change")},radioEnable:function(e){e.parent().removeClass(r.radio.iconClass);e.parent().removeClass("unchecked");e.parent().addClass(r.radio.iconClass);e.parent().addClass("checked");e.prop("checked",!0).trigger("change")},radioDisable:function(e){e.parent().removeClass("checked");e.parent().removeClass(r.radio.iconClass);e.parent().addClass("unchecked");e.prop("checked",!1).trigger("change")}};n.each(function(t,n){var r=e(this);r.is("input[type=checkbox]")&&i.checkbox(r);r.is("input[type=radio]")&&i.radio(r);return this})}})(jQuery);