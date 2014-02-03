/**
 * jQuery bxSlider v3.0
 * http://bxslider.com
 *
 * Copyright 2011, Steven Wanderski
 * http://bxcreative.com
 *
 * Free to use and abuse under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 * 
 */


(function($){
	
	$.fn.bxSlider = function(options){		
				
		var defaults = {
			mode: 'horizontal',									// 'horizontal', 'vertical', 'fade'
			infiniteLoop: true,									// true, false - display first slide after last
			hideControlOnEnd: false,						// true, false - if true, will hide 'next' control on last slide and 'prev' control on first
			controls: true,											// true, false - previous and next controls
			speed: 500,													// integer - in ms, duration of time slide transitions will occupy
			easing: 'swing',                    // used with jquery.easing.1.3.js - see http://gsgd.co.uk/sandbox/jquery/easing/ for available options
			pager: false,												// true / false - display a pager
			pagerSelector: null,								// jQuery selector - element to contain the pager. ex: '#pager'
			pagerType: 'full',									// 'full', 'short' - if 'full' pager displays 1,2,3... if 'short' pager displays 1 / 4
			pagerLocation: 'bottom',						// 'bottom', 'top' - location of pager
			pagerShortSeparator: '/',						// string - ex: 'of' pager would display 1 of 4
			pagerActiveClass: 'pager-active',		// string - classname attached to the active pager link
			nextText: 'next',										// string - text displayed for 'next' control
			nextImage: '',											// string - filepath of image used for 'next' control. ex: 'images/next.jpg'
			nextSelector: null,									// jQuery selector - element to contain the next control. ex: '#next'
			prevText: 'prev',										// string - text displayed for 'previous' control
			prevImage: '',											// string - filepath of image used for 'previous' control. ex: 'images/prev.jpg'
			prevSelector: null,									// jQuery selector - element to contain the previous control. ex: '#next'
			captions: false,										// true, false - display image captions (reads the image 'title' tag)
			captionsSelector: null,							// jQuery selector - element to contain the captions. ex: '#captions'
			auto: false,												// true, false - make slideshow change automatically
			autoDirection: 'next',							// 'next', 'prev' - direction in which auto show will traverse
			autoControls: false,								// true, false - show 'start' and 'stop' controls for auto show
			autoControlsSelector: null,					// jQuery selector - element to contain the auto controls. ex: '#auto-controls'
			autoStart: true,										// true, false - if false show will wait for 'start' control to activate
			autoHover: false,										// true, false - if true show will pause on mouseover
			autoDelay: 0,                       // integer - in ms, the amount of time before starting the auto show
			pause: 3000,												// integer - in ms, the duration between each slide transition
			startText: 'start',									// string - text displayed for 'start' control
			startImage: '',											// string - filepath of image used for 'start' control. ex: 'images/start.jpg'
			stopText: 'stop',										// string - text displayed for 'stop' control
			stopImage: '',											// string - filepath of image used for 'stop' control. ex: 'images/stop.jpg'
			ticker: false,											// true, false - continuous motion ticker mode (think news ticker)
																					// note: autoControls, autoControlsSelector, and autoHover apply to ticker!
			tickerSpeed: 5000,								  // float - use value between 1 and 5000 to determine ticker speed - the smaller the value the faster the ticker speed
			tickerDirection: 'next',						// 'next', 'prev' - direction in which ticker show will traverse
			tickerHover: false,                 // true, false - if true ticker will pause on mouseover
			wrapperClass: 'bx-wrapper',					// string - classname attached to the slider wraper
			startingSlide: 0, 									// integer - show will start on specified slide. note: slides are zero based!
			displaySlideQty: 1,									// integer - number of slides to display at once
			moveSlideQty: 1,										// integer - number of slides to move at once
			randomStart: false,									// true, false - if true show will start on a random slide
			onBeforeSlide: function(){},				// function(currentSlideNumber, totalSlideQty, currentSlideHtmlObject) - advanced use only! see the tutorial here: http://bxslider.com/custom-pager
			onAfterSlide: function(){},					// function(currentSlideNumber, totalSlideQty, currentSlideHtmlObject) - advanced use only! see the tutorial here: http://bxslider.com/custom-pager
			onLastSlide: function(){},					// function(currentSlideNumber, totalSlideQty, currentSlideHtmlObject) - advanced use only! see the tutorial here: http://bxslider.com/custom-pager
			onFirstSlide: function(){},					// function(currentSlideNumber, totalSlideQty, currentSlideHtmlObject) - advanced use only! see the tutorial here: http://bxslider.com/custom-pager
			onNextSlide: function(){},					// function(currentSlideNumber, totalSlideQty, currentSlideHtmlObject) - advanced use only! see the tutorial here: http://bxslider.com/custom-pager
			onPrevSlide: function(){},					// function(currentSlideNumber, totalSlideQty, currentSlideHtmlObject) - advanced use only! see the tutorial here: http://bxslider.com/custom-pager
			buildPager: null										// function(slideIndex, slideHtmlObject){ return string; } - advanced use only! see the tutorial here: http://bxslider.com/custom-pager
		}
		
		var options = $.extend(defaults, options);
		
		// cache the base element
		var base = this;
		// initialize (and localize) all variables
		var $parent = '';
		var $origElement = '';
		var $children = '';
		var $outerWrapper = '';
		var $firstChild = '';
		var childrenWidth = '';
		var childrenOuterWidth = '';
		var wrapperWidth = '';
		var wrapperHeight = '';
		var $pager = '';	
		var interval = '';
		var $autoControls = '';
		var $stopHtml = '';
		var $startContent = '';
		var $stopContent = '';
		var autoPlaying = true;
		var loaded = false;
		var childrenMaxWidth = 0;
		var childrenMaxHeight = 0;
		var currentSlide = 0;	
		var origLeft = 0;
		var origTop = 0;
		var origShowWidth = 0;
		var origShowHeight = 0;
		var tickerLeft = 0;
		var tickerTop = 0;
		var isWorking = false;
    
		var firstSlide = 0;
		var lastSlide = $children.length - 1;
		
						
		// PUBLIC FUNCTIONS
						
		/**
		 * Go to specified slide
		 */		
		this.goToSlide = function(number, stopAuto){
			if(!isWorking){
				isWorking = true;
				// set current slide to argument
				currentSlide = number;
				options.onBeforeSlide(currentSlide, $children.length, $children.eq(currentSlide));
				// check if stopAuto argument is supplied
				if(typeof(stopAuto) == 'undefined'){
					var stopAuto = true;
				}
				if(stopAuto){
					// if show is auto playing, stop it
					if(options.auto){
						base.stopShow(true);
					}
				}			
				slide = number;
				// check for first slide callback
				if(slide == firstSlide){
					options.onFirstSlide(currentSlide, $children.length, $children.eq(currentSlide));
				}
				// check for last slide callback
				if(slide == lastSlide){
					options.onLastSlide(currentSlide, $children.length, $children.eq(currentSlide));
				}
				// horizontal
				if(options.mode == 'horizontal'){
					$parent.animate({'left': '-'+getSlidePosition(slide, 'left')+'px'}, options.speed, options.easing, function(){
						isWorking = false;
						// perform the callback function
						options.onAfterSlide(currentSlide, $children.length, $children.eq(currentSlide));
					});
				// vertical
				}else if(options.mode == 'vertical'){
					$parent.animate({'top': '-'+getSlidePosition(slide, 'top')+'px'}, options.speed, options.easing, function(){
						isWorking = false;
						// perform the callback function
						options.onAfterSlide(currentSlide, $children.length, $children.eq(currentSlide));
					});			
				// fade	
				}else if(options.mode == 'fade'){
					setChildrenFade();
				}
				// check to remove controls on last/first slide
				checkEndControls();
				// accomodate multi slides
				if(options.moveSlideQty > 1){
					number = Math.floor(number / options.moveSlideQty);
				}
				// make the current slide active
				makeSlideActive(number);
				// display the caption
				showCaptions();
			}
		}
		
		/**
		 * Go to next slide
		 */		
		this.goToNextSlide = function(stopAuto){
			// check if stopAuto argument is supplied
			if(typeof(stopAuto) == 'undefined'){
				var stopAuto = true;
			}
			if(stopAuto){
				// if show is auto playing, stop it
				if(options.auto){
					base.stopShow(true);
				}
			}			
			// makes slideshow finite
			if(!options.infiniteLoop){
				if(!isWorking){
					var slideLoop = false;
					// make current slide the old value plus moveSlideQty
					currentSlide = (currentSlide + (options.moveSlideQty));
					// if current slide has looped on itself
					if(currentSlide <= lastSlide){
						checkEndControls();
						// next slide callback
						options.onNextSlide(currentSlide, $children.length, $children.eq(currentSlide));
						// move to appropriate slide
						base.goToSlide(currentSlide);						
					}else{
						currentSlide -= options.moveSlideQty;
					}
				} // end if(!isWorking)		
			}else{ 
				if(!isWorking){
					isWorking = true;					
					var slideLoop = false;
					// make current slide the old value plus moveSlideQty
					currentSlide = (currentSlide + options.moveSlideQty);
					// if current slide has looped on itself
					if(currentSlide > lastSlide){
						currentSlide = currentSlide % $children.length;
						slideLoop = true;
					}
					// next slide callback
					options.onNextSlide(currentSlide, $children.length, $children.eq(currentSlide));
					// slide before callback
					options.onBeforeSlide(currentSlide, $children.length, $children.eq(currentSlide));
					if(options.mode == 'horizontal'){						
						// get the new 'left' property for $parent
						var parentLeft = (options.moveSlideQty * childrenOuterWidth);
						// animate to the new 'left'
						$parent.animate({'left': '-='+parentLeft+'px'}, options.speed, options.easing, function(){
							isWorking = false;
							// if its time to loop, reset the $parent
							if(slideLoop){
								$parent.css('left', '-'+getSlidePosition(currentSlide, 'left')+'px');
							}
							// perform the callback function
							options.onAfterSlide(currentSlide, $children.length, $children.eq(currentSlide));
						});
					}else if(options.mode == 'vertical'){
						// get the new 'left' property for $parent
						var parentTop = (options.moveSlideQty * childrenMaxHeight);
						// animate to the new 'left'
						$parent.animate({'top': '-='+parentTop+'px'}, options.speed, options.easing, function(){
							isWorking = false;
							// if its time to loop, reset the $parent
							if(slideLoop){
								$parent.css('top', '-'+getSlidePosition(currentSlide, 'top')+'px');
							}
							// perform the callback function
							options.onAfterSlide(currentSlide, $children.length, $children.eq(currentSlide));
						});
					}else if(options.mode == 'fade'){
						setChildrenFade();
					}					
					// make the current slide active
					if(options.moveSlideQty > 1){
						makeSlideActive(Math.ceil(currentSlide / options.moveSlideQty));
					}else{
						makeSlideActive(currentSlide);
					}
					// display the caption
					showCaptions();
				} // end if(!isWorking)
				
			}	
		} // end function
		
		/**
		 * Go to previous slide
		 */		
		this.goToPreviousSlide = function(stopAuto){
			// check if stopAuto argument is supplied
			if(typeof(stopAuto) == 'undefined'){
				var stopAuto = true;
			}
			if(stopAuto){
				// if show is auto playing, stop it
				if(options.auto){
					base.stopShow(true);
				}
			}			
			// makes slideshow finite
			if(!options.infiniteLoop){	
				if(!isWorking){
					var slideLoop = false;
					// make current slide the old value plus moveSlideQty
					currentSlide = currentSlide - options.moveSlideQty;
					// if current slide has looped on itself
					if(currentSlide < 0){
						currentSlide = 0;
						// if specified, hide the control on the last slide
						if(options.hideControlOnEnd){
							$('.bx-prev', $outerWrapper).hide();
						}
					}
					checkEndControls();
					// next slide callback
					options.onPrevSlide(currentSlide, $children.length, $children.eq(currentSlide));
					// move to appropriate slide
					base.goToSlide(currentSlide);
				}							
			}else{
				if(!isWorking){
					isWorking = true;			
					var slideLoop = false;
					// make current slide the old value plus moveSlideQty
					currentSlide = (currentSlide - (options.moveSlideQty));
					// if current slide has looped on itself
					if(currentSlide < 0){
						negativeOffset = (currentSlide % $children.length);
						if(negativeOffset == 0){
							currentSlide = 0;
						}else{
							currentSlide = ($children.length) + negativeOffset; 
						}
						slideLoop = true;
					}
					// next slide callback
					options.onPrevSlide(currentSlide, $children.length, $children.eq(currentSlide));
					// slide before callback
					options.onBeforeSlide(currentSlide, $children.length, $children.eq(currentSlide));
					if(options.mode == 'horizontal'){
						// get the new 'left' property for $parent
						var parentLeft = (options.moveSlideQty * childrenOuterWidth);
						// animate to the new 'left'
						$parent.animate({'left': '+='+parentLeft+'px'}, options.speed, options.easing, function(){
							isWorking = false;
							// if its time to loop, reset the $parent
							if(slideLoop){
								$parent.css('left', '-'+getSlidePosition(currentSlide, 'left')+'px');
							}
							// perform the callback function
							options.onAfterSlide(currentSlide, $children.length, $children.eq(currentSlide));
						});
					}else if(options.mode == 'vertical'){
						// get the new 'left' property for $parent
						var parentTop = (options.moveSlideQty * childrenMaxHeight);
						// animate to the new 'left'
						$parent.animate({'top': '+='+parentTop+'px'}, options.speed, options.easing, function(){
							isWorking = false;
							// if its time to loop, reset the $parent
							if(slideLoop){
								$parent.css('top', '-'+getSlidePosition(currentSlide, 'top')+'px');
							}
							// perform the callback function
							options.onAfterSlide(currentSlide, $children.length, $children.eq(currentSlide));
						});
					}else if(options.mode == 'fade'){
						setChildrenFade();
					}					
					// make the current slide active
					if(options.moveSlideQty > 1){
						makeSlideActive(Math.ceil(currentSlide / options.moveSlideQty));
					}else{
						makeSlideActive(currentSlide);
					}
					// display the caption
					showCaptions();
				} // end if(!isWorking)				
			}
		} // end function
		
		/**
		 * Go to first slide
		 */		
		this.goToFirstSlide = function(stopAuto){
			// check if stopAuto argument is supplied
			if(typeof(stopAuto) == 'undefined'){
				var stopAuto = true;
			}
			base.goToSlide(firstSlide, stopAuto);
		}
		
		/**
		 * Go to last slide
		 */		
		this.goToLastSlide = function(){
			// check if stopAuto argument is supplied
			if(typeof(stopAuto) == 'undefined'){
				var stopAuto = true;
			}
			base.goToSlide(lastSlide, stopAuto);
		}
		
		/**
		 * Get the current slide
		 */		
		this.getCurrentSlide = function(){
			return currentSlide;
		}
		
		/**
		 * Get the total slide count
		 */		
		this.getSlideCount = function(){
			return $children.length;
		}
		
		/**
		 * Stop the slideshow
		 */		
		this.stopShow = function(changeText){
			clearInterval(interval);
			// check if changeText argument is supplied
			if(typeof(changeText) == 'undefined'){
				var changeText = true;
			}
			if(changeText && options.autoControls){
				$autoControls.html($startContent).removeClass('stop').addClass('start');
				autoPlaying = false;
			}
		}
		
		/**
		 * Start the slideshow
		 */		
		this.startShow = function(changeText){
			// check if changeText argument is supplied
			if(typeof(changeText) == 'undefined'){
				var changeText = true;
			}
			setAutoInterval();
			if(changeText && options.autoControls){
				$autoControls.html($stopContent).removeClass('start').addClass('stop');
				autoPlaying = true;
			}
		}
		
		/**
		 * Stops the ticker
		 */		
		this.stopTicker = function(changeText){
			$parent.stop();
			// check if changeText argument is supplied
			if(typeof(changeText) == 'undefined'){
				var changeText = true;
			}
			if(changeText && options.ticker){
				$autoControls.html($startContent).removeClass('stop').addClass('start');
				autoPlaying = false;
			}			
		}
		
		/**
		 * Starts the ticker
		 */		
		this.startTicker = function(changeText){
			if(options.mode == 'horizontal'){
				if(options.tickerDirection == 'next'){
					// get the 'left' property where the ticker stopped
					var stoppedLeft = parseInt($parent.css('left'));
					// calculate the remaining distance the show must travel until the loop
					var remainingDistance = (origShowWidth + stoppedLeft) + $children.eq(0).width();			
				}else if(options.tickerDirection == 'prev'){
					// get the 'left' property where the ticker stopped
					var stoppedLeft = -parseInt($parent.css('left'));
					// calculate the remaining distance the show must travel until the loop
					var remainingDistance = (stoppedLeft) - $children.eq(0).width();
				}
				// calculate the speed ratio to seamlessly finish the loop
				var finishingSpeed = (remainingDistance * options.tickerSpeed) / origShowWidth;
				// call the show
				moveTheShow(tickerLeft, remainingDistance, finishingSpeed);					
			}else if(options.mode == 'vertical'){
				if(options.tickerDirection == 'next'){
					// get the 'top' property where the ticker stopped
					var stoppedTop = parseInt($parent.css('top'));
					// calculate the remaining distance the show must travel until the loop
					var remainingDistance = (origShowHeight + stoppedTop) + $children.eq(0).height();			
				}else if(options.tickerDirection == 'prev'){
					// get the 'left' property where the ticker stopped
					var stoppedTop = -parseInt($parent.css('top'));
					// calculate the remaining distance the show must travel until the loop
					var remainingDistance = (stoppedTop) - $children.eq(0).height();
				}
				// calculate the speed ratio to seamlessly finish the loop
				var finishingSpeed = (remainingDistance * options.tickerSpeed) / origShowHeight;
				// call the show
				moveTheShow(tickerTop, remainingDistance, finishingSpeed);
				// check if changeText argument is supplied
				if(typeof(changeText) == 'undefined'){
					var changeText = true;
				}
				if(changeText && options.ticker){
					$autoControls.html($stopContent).removeClass('start').addClass('stop');
					autoPlaying = true;
				}						
			}
		}
				
		/**
		 * Initialize a new slideshow
		 */		
		this.initShow = function(){
			
			// reinitialize all variables
			// base = this;
			$parent = $(this);
			$origElement = $parent.clone();
			$children = $parent.children();
			$outerWrapper = '';
			$firstChild = $parent.children(':first');
			childrenWidth = $firstChild.width();
			childrenMaxWidth = 0;
			childrenOuterWidth = $firstChild.outerWidth();
			childrenMaxHeight = 0;
			wrapperWidth = getWrapperWidth();
			wrapperHeight = getWrapperHeight();
			isWorking = false;
			$pager = '';	
			currentSlide = 0;	
			origLeft = 0;
			origTop = 0;
			interval = '';
			$autoControls = '';
			$stopHtml = '';
			$startContent = '';
			$stopContent = '';
			autoPlaying = true;
			loaded = false;
			origShowWidth = 0;
			origShowHeight = 0;
			tickerLeft = 0;
			tickerTop = 0;
      
			firstSlide = 0;
			lastSlide = $children.length - 1;
						
			// get the largest child's height and width
			$children.each(function(index) {
			  if($(this).outerHeight() > childrenMaxHeight){
					childrenMaxHeight = $(this).outerHeight();
				}
				if($(this).outerWidth() > childrenMaxWidth){
					childrenMaxWidth = $(this).outerWidth();
				}
			});

			// get random slide number
			if(options.randomStart){
				var randomNumber = Math.floor(Math.random() * $children.length);
				currentSlide = randomNumber;
				origLeft = childrenOuterWidth * (options.moveSlideQty + randomNumber);
				origTop = childrenMaxHeight * (options.moveSlideQty + randomNumber);
			// start show at specific slide
			}else{
				currentSlide = options.startingSlide;
				origLeft = childrenOuterWidth * (options.moveSlideQty + options.startingSlide);
				origTop = childrenMaxHeight * (options.moveSlideQty + options.startingSlide);
			}
						
			// set initial css
			initCss();
			
			// check to show pager
			if(options.pager && !options.ticker){
				if(options.pagerType == 'full'){
					showPager('full');
				}else if(options.pagerType == 'short'){
					showPager('short');
				}
			}
						
			// check to show controls
			if(options.controls && !options.ticker){
				setControlsVars();
			}
						
			// check if auto
			if(options.auto || options.ticker){
				// check if auto controls are displayed
				if(options.autoControls){
					setAutoControlsVars();
				}
				// check if show should auto start
				if(options.autoStart){
					// check if autostart should delay
					setTimeout(function(){
						base.startShow(true);
					}, options.autoDelay);
				}else{
					base.stopShow(true);
				}
				// check if show should pause on hover
				if(options.autoHover && !options.ticker){
					setAutoHover();
				}
			}						
			// make the starting slide active
			if(options.moveSlideQty > 1){
				makeSlideActive(Math.ceil(currentSlide / options.moveSlideQty));
			}else{			
				makeSlideActive(currentSlide);			
			}
			// check for finite show and if controls should be hidden
			checkEndControls();
			// show captions
			if(options.captions){
				showCaptions();
			}
			// perform the callback function
			options.onAfterSlide(currentSlide, $children.length, $children.eq(currentSlide));
		}
		
		/**
		 * Destroy the current slideshow
		 */		
		this.destroyShow = function(){			
			// stop the auto show
			clearInterval(interval);
			// remove any controls / pagers that have been appended
			$('.bx-next, .bx-prev, .bx-pager, .bx-auto', $outerWrapper).remove();
			// unwrap all bx-wrappers
			$parent.unwrap().unwrap().removeAttr('style');
			// remove any styles that were appended
			$parent.children().removeAttr('style').not('.pager').remove();
			// remove any childrent that were appended
			$children.removeClass('pager');
			
		}
		
		/**
		 * Reload the current slideshow
		 */		
		this.reloadShow = function(){
			base.destroyShow();
			base.initShow();
		}
		
		// PRIVATE FUNCTIONS
		
		/**
		 * Creates all neccessary styling for the slideshow
		 */		
		function initCss(){
			// layout the children
			setChildrenLayout(options.startingSlide);
			// CSS for horizontal mode
			if(options.mode == 'horizontal'){
				// wrap the <ul> in div that acts as a window and make the <ul> uber wide
				$parent
				.wrap('<div class="'+options.wrapperClass+'" style="width:'+wrapperWidth+'px; position:relative;"></div>')
				.wrap('<div class="bx-window" style="position:relative; overflow:hidden; width:'+wrapperWidth+'px;"></div>')
				.css({
				  width: '999999px',
				  position: 'relative',
					left: '-'+(origLeft)+'px'
				});
				$parent.children().css({
					width: childrenWidth,
				  'float': 'left',
				  listStyle: 'none'
				});					
				$outerWrapper = $parent.parent().parent();
				$children.addClass('pager');
			// CSS for vertical mode
			}else if(options.mode == 'vertical'){
				// wrap the <ul> in div that acts as a window and make the <ul> uber tall
				$parent
				.wrap('<div class="'+options.wrapperClass+'" style="width:'+childrenMaxWidth+'px; position:relative;"></div>')
				.wrap('<div class="bx-window" style="width:'+childrenMaxWidth+'px; height:'+wrapperHeight+'px; position:relative; overflow:hidden;"></div>')
				.css({
				  height: '999999px',
				  position: 'relative',
					top: '-'+(origTop)+'px'
				});
				$parent.children().css({
				  listStyle: 'none',
					height: childrenMaxHeight
				});					
				$outerWrapper = $parent.parent().parent();
				$children.addClass('pager');
			// CSS for fade mode
			}else if(options.mode == 'fade'){
				// wrap the <ul> in div that acts as a window
				$parent
				.wrap('<div class="'+options.wrapperClass+'" style="width:'+childrenMaxWidth+'px; position:relative;"></div>')
				.wrap('<div class="bx-window" style="height:'+childrenMaxHeight+'px; width:'+childrenMaxWidth+'px; position:relative; overflow:hidden;"></div>');
				$parent.children().css({
				  listStyle: 'none',
				  position: 'absolute',
					top: 0,
					left: 0,
					zIndex: 98
				});					
				$outerWrapper = $parent.parent().parent();
				$children.not(':eq('+currentSlide+')').fadeTo(0, 0);
				$children.eq(currentSlide).css('zIndex', 99);
			}
			// if captions = true setup a div placeholder
			if(options.captions && options.captionsSelector == null){
				$outerWrapper.append('<div class="bx-captions"></div>');
			}			
		}
		
		/**
		 * Depending on mode, lays out children in the proper setup
		 */		
		function setChildrenLayout(){			
			// lays out children for horizontal or vertical modes
			if(options.mode == 'horizontal' || options.mode == 'vertical'){
								
				// get the children behind
				var $prependedChildren = getArraySample($children, 0, options.moveSlideQty, 'backward');
				
				// add each prepended child to the back of the original element
				$.each($prependedChildren, function(index) {
					$parent.prepend($(this));
				});			
				
				// total number of slides to be hidden after the window
				var totalNumberAfterWindow = ($children.length + options.moveSlideQty) - 1;
				// number of original slides hidden after the window
				var pagerExcess = $children.length - options.displaySlideQty;
				// number of slides to append to the original hidden slides
				var numberToAppend = totalNumberAfterWindow - pagerExcess;
				// get the sample of extra slides to append
				var $appendedChildren = getArraySample($children, 0, numberToAppend, 'forward');
				
				if(options.infiniteLoop){
					// add each appended child to the front of the original element
					$.each($appendedChildren, function(index) {
						$parent.append($(this));
					});
				}
			}
		}
		
		/**
		 * Sets all variables associated with the controls
		 */		
		function setControlsVars(){
			// check if text or images should be used for controls
			// check "next"
			if(options.nextImage != ''){
				nextContent = options.nextImage;
				nextType = 'image';
			}else{
				nextContent = options.nextText;
				nextType = 'text';
			}
			// check "prev"
			if(options.prevImage != ''){
				prevContent = options.prevImage;
				prevType = 'image';
			}else{
				prevContent = options.prevText;
				prevType = 'text';
			}
			// show the controls
			showControls(nextType, nextContent, prevType, prevContent);
		}			
		
		/**
		 * Puts slideshow into auto mode
		 *
		 * @param int pause number of ms the slideshow will wait between slides 
		 * @param string direction 'forward', 'backward' sets the direction of the slideshow (forward/backward)
		 * @param bool controls determines if start/stop controls will be displayed
		 */		
		function setAutoInterval(){
			if(options.auto){
				// finite loop
				if(!options.infiniteLoop){
					if(options.autoDirection == 'next'){
						interval = setInterval(function(){
							currentSlide += options.moveSlideQty;
							// if currentSlide has exceeded total number
							if(currentSlide > lastSlide){
								currentSlide = currentSlide % $children.length;
							}
							base.goToSlide(currentSlide, false);
						}, options.pause);
					}else if(options.autoDirection == 'prev'){
						interval = setInterval(function(){
							currentSlide -= options.moveSlideQty;
							// if currentSlide is smaller than zero
							if(currentSlide < 0){
								negativeOffset = (currentSlide % $children.length);
								if(negativeOffset == 0){
									currentSlide = 0;
								}else{
									currentSlide = ($children.length) + negativeOffset; 
								}
							}
							base.goToSlide(currentSlide, false);
						}, options.pause);
					}
				// infinite loop
				}else{
					if(options.autoDirection == 'next'){
						interval = setInterval(function(){
							base.goToNextSlide(false);
						}, options.pause);
					}else if(options.autoDirection == 'prev'){
						interval = setInterval(function(){
							base.goToPreviousSlide(false);
						}, options.pause);
					}
				}
			
			}else if(options.ticker){
				
				options.tickerSpeed *= 10;
												
				// get the total width of the original show
				$('.pager', $outerWrapper).each(function(index) {
				  origShowWidth += $(this).width();
					origShowHeight += $(this).height();
				});
				
				// if prev start the show from the last slide
				if(options.tickerDirection == 'prev' && options.mode == 'horizontal'){
					$parent.css('left', '-'+(origShowWidth+origLeft)+'px');
				}else if(options.tickerDirection == 'prev' && options.mode == 'vertical'){
					$parent.css('top', '-'+(origShowHeight+origTop)+'px');
				}
				
				if(options.mode == 'horizontal'){
					// get the starting left position
					tickerLeft = parseInt($parent.css('left'));
					// start the ticker
					moveTheShow(tickerLeft, origShowWidth, options.tickerSpeed);
				}else if(options.mode == 'vertical'){
					// get the starting top position
					tickerTop = parseInt($parent.css('top'));
					// start the ticker
					moveTheShow(tickerTop, origShowHeight, options.tickerSpeed);
				}												
				
				// check it tickerHover applies
				if(options.tickerHover){
					setTickerHover();
				}					
			}			
		}
		
		function moveTheShow(leftCss, distance, speed){
			// if horizontal
			if(options.mode == 'horizontal'){
				// if next
				if(options.tickerDirection == 'next'){
					$parent.animate({'left': '-='+distance+'px'}, speed, 'linear', function(){
						$parent.css('left', leftCss);
						moveTheShow(leftCss, origShowWidth, options.tickerSpeed);
					});
				// if prev
				}else if(options.tickerDirection == 'prev'){
					$parent.animate({'left': '+='+distance+'px'}, speed, 'linear', function(){
						$parent.css('left', leftCss);
						moveTheShow(leftCss, origShowWidth, options.tickerSpeed);
					});
				}
			// if vertical		
			}else if(options.mode == 'vertical'){
				// if next
				if(options.tickerDirection == 'next'){
					$parent.animate({'top': '-='+distance+'px'}, speed, 'linear', function(){
						$parent.css('top', leftCss);
						moveTheShow(leftCss, origShowHeight, options.tickerSpeed);
					});
				// if prev
				}else if(options.tickerDirection == 'prev'){
					$parent.animate({'top': '+='+distance+'px'}, speed, 'linear', function(){
						$parent.css('top', leftCss);
						moveTheShow(leftCss, origShowHeight, options.tickerSpeed);
					});
				}
			}
		}		
		
		/**
		 * Sets all variables associated with the controls
		 */		
		function setAutoControlsVars(){
			// check if text or images should be used for controls
			// check "start"
			if(options.startImage != ''){
				startContent = options.startImage;
				startType = 'image';
			}else{
				startContent = options.startText;
				startType = 'text';
			}
			// check "stop"
			if(options.stopImage != ''){
				stopContent = options.stopImage;
				stopType = 'image';
			}else{
				stopContent = options.stopText;
				stopType = 'text';
			}
			// show the controls
			showAutoControls(startType, startContent, stopType, stopContent);
		}
		
		/**
		 * Handles hover events for auto shows
		 */		
		function setAutoHover(){
			// hover over the slider window
			$outerWrapper.find('.bx-window').hover(function() {
				if(autoPlaying){
					base.stopShow(false);
				}
			}, function() {
				if(autoPlaying){
					base.startShow(false);
				}
			});
		}
		
		/**
		 * Handles hover events for ticker mode
		 */		
		function setTickerHover(){
			// on hover stop the animation
			$parent.hover(function() {
				if(autoPlaying){
					base.stopTicker(false);
				}
			}, function() {
				if(autoPlaying){
					base.startTicker(false);
				}
			});
		}		
		
		/**
		 * Handles fade animation
		 */		
		function setChildrenFade(){
			// fade out any other child besides the current
			$children.not(':eq('+currentSlide+')').fadeTo(options.speed, 0).css('zIndex', 98);
			// fade in the current slide
			$children.eq(currentSlide).css('zIndex', 99).fadeTo(options.speed, 1, function(){
				isWorking = false;
				// ie fade fix
				if(jQuery.browser.msie){
					$children.eq(currentSlide).get(0).style.removeAttribute('filter');
				}
				// perform the callback function
				options.onAfterSlide(currentSlide, $children.length, $children.eq(currentSlide));
			});
		};
				
		/**
		 * Makes slide active
		 */		
		function makeSlideActive(number){
			if(options.pagerType == 'full' && options.pager){
				// remove all active classes
				$('a', $pager).removeClass(options.pagerActiveClass);
				// assign active class to appropriate slide
				$('a', $pager).eq(number).addClass(options.pagerActiveClass);
			}else if(options.pagerType == 'short' && options.pager){
				$('.bx-pager-current', $pager).html(currentSlide+1);
			}
		}
				
		/**
		 * Displays next/prev controls
		 *
		 * @param string nextType 'image', 'text'
		 * @param string nextContent if type='image', specify a filepath to the image. if type='text', specify text.
		 * @param string prevType 'image', 'text'
		 * @param string prevContent if type='image', specify a filepath to the image. if type='text', specify text.
		 */		
		function showControls(nextType, nextContent, prevType, prevContent){
			// create pager html elements
			var $nextHtml = $('<a href="" class="bx-next"></a>');
			var $prevHtml = $('<a href="" class="bx-prev"></a>');
			// check if next is 'text' or 'image'
			if(nextType == 'text'){
				$nextHtml.html(nextContent);
			}else{
				$nextHtml.html('<img src="'+nextContent+'" />');
			}
			// check if prev is 'text' or 'image'
			if(prevType == 'text'){
				$prevHtml.html(prevContent);
			}else{
				$prevHtml.html('<img src="'+prevContent+'" />');
			}
			// check if user supplied a selector to populate next control
			if(options.prevSelector){
				$(options.prevSelector).append($prevHtml);
			}else{
				$outerWrapper.append($prevHtml);
			}
			// check if user supplied a selector to populate next control
			if(options.nextSelector){
				$(options.nextSelector).append($nextHtml);
			}else{
				$outerWrapper.append($nextHtml);
			}
			// click next control
			$nextHtml.click(function() {
				base.goToNextSlide();
				return false;
			});
			// click prev control
			$prevHtml.click(function() {
				base.goToPreviousSlide();
				return false;
			});
		}
		
		/**
		 * Displays the pager
		 *
		 * @param string type 'full', 'short'
		 */		
		function showPager(type){
			// sets up logic for finite multi slide shows
			var pagerQty = $children.length;
			// if we are moving more than one at a time and we have a finite loop
			if(options.moveSlideQty > 1){
				// if slides create an odd number of pages
				if($children.length % options.moveSlideQty != 0){
					// pagerQty = $children.length / options.moveSlideQty + 1;
					pagerQty = Math.ceil($children.length / options.moveSlideQty);
				// if slides create an even number of pages
				}else{
					pagerQty = $children.length / options.moveSlideQty;
				}
			}
			var pagerString = '';
			// check if custom build function was supplied
			if(options.buildPager){
				for(var i=0; i<pagerQty; i++){
					pagerString += options.buildPager(i, $children.eq(i * options.moveSlideQty));
				}
				
			// if not, use default pager
			}else if(type == 'full'){
				// build the full pager
				for(var i=1; i<=pagerQty; i++){
					pagerString += '<a href="" class="pager-link pager-'+i+'">'+i+'</a>';
				}
			}else if(type == 'short') {
				// build the short pager
				pagerString = '<span class="bx-pager-current">'+(options.startingSlide+1)+'</span> '+options.pagerShortSeparator+' <span class="bx-pager-total">'+$children.length+'<span>';
			}	
			// check if user supplied a pager selector
			if(options.pagerSelector){
				$(options.pagerSelector).append(pagerString);
				$pager = $(options.pagerSelector);
			}else{
				var $pagerContainer = $('<div class="bx-pager"></div>');
				$pagerContainer.append(pagerString);
				// attach the pager to the DOM
				if(options.pagerLocation == 'top'){
					$outerWrapper.prepend($pagerContainer);
				}else if(options.pagerLocation == 'bottom'){
					$outerWrapper.append($pagerContainer);
				}
				// cache the pager element
				$pager = $('.bx-pager', $outerWrapper);
			}
			$pager.children().click(function() {
				// only if pager is full mode
				if(options.pagerType == 'full'){
					// get the index from the link
					var slideIndex = $pager.children().index(this);
					// accomodate moving more than one slide
					if(options.moveSlideQty > 1){
						slideIndex *= options.moveSlideQty;
					}
					base.goToSlide(slideIndex);
				}
				return false;
			});
		}
				
		/**
		 * Displays captions
		 */		
		function showCaptions(){
			// get the title from each image
		  var caption = $('img', $children.eq(currentSlide)).attr('title');
			// if the caption exists
			if(caption != ''){
				// if user supplied a selector
				if(options.captionsSelector){
					$(options.captionsSelector).html(caption);
				}else{
					$('.bx-captions', $outerWrapper).html(caption);
				}
			}else{
				// if user supplied a selector
				if(options.captionsSelector){
					$(options.captionsSelector).html('&nbsp;');
				}else{
					$('.bx-captions', $outerWrapper).html('&nbsp;');
				}				
			}
		}
		
		/**
		 * Displays start/stop controls for auto and ticker mode
		 *
		 * @param string type 'image', 'text'
		 * @param string next [optional] if type='image', specify a filepath to the image. if type='text', specify text.
		 * @param string prev [optional] if type='image', specify a filepath to the image. if type='text', specify text.
		 */
		function showAutoControls(startType, startContent, stopType, stopContent){
			// create pager html elements
			$autoControls = $('<a href="" class="bx-start"></a>');
			// check if start is 'text' or 'image'
			if(startType == 'text'){
				$startContent = startContent;
			}else{
				$startContent = '<img src="'+startContent+'" />';
			}
			// check if stop is 'text' or 'image'
			if(stopType == 'text'){
				$stopContent = stopContent;
			}else{
				$stopContent = '<img src="'+stopContent+'" />';
			}
			// check if user supplied a selector to populate next control
			if(options.autoControlsSelector){
				$(options.autoControlsSelector).append($autoControls);
			}else{
				$outerWrapper.append('<div class="bx-auto"></div>');
				$('.bx-auto', $outerWrapper).html($autoControls);
			}
						
			// click start control
			$autoControls.click(function() {
				if(options.ticker){
					if($(this).hasClass('stop')){
						base.stopTicker();
					}else if($(this).hasClass('start')){
						base.startTicker();
					}
				}else{
					if($(this).hasClass('stop')){
						base.stopShow(true);
					}else if($(this).hasClass('start')){
						base.startShow(true);
					}
				}
				return false;
			});
			
		}
		
		/**
		 * Checks if show is in finite mode, and if slide is either first or last, then hides the respective control
		 */		
		function checkEndControls(){
			if(!options.infiniteLoop && options.hideControlOnEnd){
				// check previous
				if(currentSlide == firstSlide){
					$('.bx-prev', $outerWrapper).hide();				
				}else{
					$('.bx-prev', $outerWrapper).show();
				}
				// check next
				if(currentSlide == lastSlide){
					$('.bx-next', $outerWrapper).hide();
				}else{
					$('.bx-next', $outerWrapper).show();
				}
			}
		}
		
		/**
		 * Returns the left offset of the slide from the parent container
		 */		
		function getSlidePosition(number, side){			
			if(side == 'left'){
				var position = $('.pager', $outerWrapper).eq(number).position().left;
			}else if(side == 'top'){
				var position = $('.pager', $outerWrapper).eq(number).position().top;
			}
			return position;
		}
		
		/**
		 * Returns the width of the wrapper
		 */		
		function getWrapperWidth(){
			var wrapperWidth = $firstChild.outerWidth() * options.displaySlideQty;
			return wrapperWidth;
		}
		
		/**
		 * Returns the height of the wrapper
		 */		
		function getWrapperHeight(){
			// if displaying multiple slides, multiple wrapper width by number of slides to display
			var wrapperHeight = $firstChild.outerHeight() * options.displaySlideQty;
			return wrapperHeight;
		}
		
		/**
		 * Returns a sample of an arry and loops back on itself if the end of the array is reached
		 *
		 * @param array array original array the sample is derived from
		 * @param int start array index sample will start
		 * @param int length number of items in the sample
		 * @param string direction 'forward', 'backward' direction the loop should travel in the array
		 */		
		function getArraySample(array, start, length, direction){
			// initialize empty array
			var sample = [];
			// clone the length argument
			var loopLength = length;
			// determines when the empty array should start being populated
			var startPopulatingArray = false;
			// reverse the array if direction = 'backward'
			if(direction == 'backward'){
				array = $.makeArray(array);
				array.reverse();
			}
			// loop through original array until the length argument is met
			while(loopLength > 0){				
				// loop through original array
				$.each(array, function(index, val) {
					// check if length has been met
					if(loopLength > 0){
						// don't do anything unless first index has been reached
					  if(!startPopulatingArray){
							// start populating empty array
							if(index == start){
								startPopulatingArray = true;
								// add element to array
								sample.push($(this).clone());
								// decrease the length clone variable
								loopLength--;
							}
						}else{
							// add element to array
							sample.push($(this).clone());
							// decrease the length clone variable
							loopLength--;
						}
					// if length has been met, break loose
					}else{
						return false;
					}			
				});				
			}
			return sample;
		}
												
		this.each(function(){
			// make sure the element has children
			if($(this).children().length > 0){
				base.initShow();
			}
		});
				
		return this;						
	}
	
	jQuery.fx.prototype.cur = function(){
		if ( this.elem[this.prop] != null && (!this.elem.style || this.elem.style[this.prop] == null) ) {
			return this.elem[ this.prop ];
		}

		var r = parseFloat( jQuery.css( this.elem, this.prop ) );
		// return r && r > -10000 ? r : 0;
		return r;
	}

		
})(jQuery);

