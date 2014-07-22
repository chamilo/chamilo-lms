/*
 * jQuery Nivo Gallery v0.7
 * http://dev7studios.com
 *
 * Copyright 2011, Gilbert Pellegrom
 * Free to use and abuse under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 * 
 * October 2011
 */

(function($) {

    $.nivoGallery = function(element, options){

        var defaults = {
            pauseTime: 3000,
            animSpeed: 300,
            effect: 'fade',
            startPaused: false,
            directionNav: true,
            progressBar: true,
            galleryLoaded: function(){},
            beforeChange: function(index, slide, paused){},
            afterChange: function(index, slide, paused){},
            galleryEnd: function(){}
        }
        
        var global = {
            slides: [],
            currentSlide: 0,
            totalSlides: 0,
            animating: false,
            paused: false,
            timer: null,
            progressTimer: null
        }

        var plugin = this;

        plugin.settings = {}

        var $element = $(element),
             element = element;

        plugin.init = function(){
            plugin.settings = $.extend({}, defaults, options);
            
            setupGallery();
        }

        /* Private Funcs */
        var setupGallery = function(){
            global.slides = $element.find('ul li').remove();
            global.totalSlides = global.slides.length;
            
            $element.find('ul').addClass('nivoGallery-slides');
            if(plugin.settings.progressBar){
                $element.append('<div class="nivoGallery-progress"></div>');
            }
            if(plugin.settings.directionNav){
                $element.append('<div class="nivoGallery-directionNav">' +
                    '<a class="nivoGallery-prev">Prev</a> <a class="nivoGallery-next">Next</a>' +
                '</div>');
            }
            $element.append('<div class="nivoGallery-bar">' +
                '<a class="nivoGallery-play playing" title="Play / Pause"></a>' +
                '<div class="nivoGallery-count">'+ setCount() +'</div>' +
                '<div class="nivoGallery-caption">'+ setCaption() +'</div>' +
                '<a class="nivoGallery-fullscreen" title="Toggle Fullscreen"></a>' +
            '</div>').fadeIn(200);
            
            loadSlide(global.currentSlide);
        }
        
        var setCount = function(){
            return (global.currentSlide + 1) +' / '+ global.totalSlides;
        }
        
        var setCaption = function(){
            var title = $(global.slides[global.currentSlide]).attr('data-title');
            var caption = $(global.slides[global.currentSlide]).attr('data-caption');
            var output = '';
            if(title) output += '<span class="nivoGallery-captionTitle">'+ title +'</span>';
            if(caption) output += caption;
            return output;
        }
        
        var runTimeout = function(){
            clearTimeout(global.timer);
            if(plugin.settings.progressBar){ 
                clearInterval(global.progressTimer);
                $element.find('.nivoGallery-progress').width('0%');
            }
            
            if(!global.paused){
                global.timer = setTimeout(function(){ $element.trigger('nextslide'); }, plugin.settings.pauseTime);
                
                if(plugin.settings.progressBar){
                    var progressStart = new Date();
                    global.progressTimer = setInterval(function(){
                        var ellapsed = new Date() - progressStart;
                        var perc = (ellapsed / plugin.settings.pauseTime) * 100;
                        $element.find('.nivoGallery-progress').width(perc + '%');
                        if(perc > 100){
                            clearInterval(global.progressTimer);
                            $element.find('.nivoGallery-progress').width('0%');
                        }
                    }, 10);
                }
            }
        }  
                
        var loadSlide = function(idx, callbackFn){
        	if($(global.slides[idx]).data('loaded')){ 
                if(typeof callbackFn == 'function') callbackFn.call(this);
                return;
            }
        	
            if($(global.slides[idx]).find('img').length > 0 && ($(global.slides[idx]).attr('data-type') != 'html' && $(global.slides[idx]).attr('data-type') != 'video')){
                $element.removeClass('loaded');
                var img = new Image();
                $(img).load(function(){
                    $element.find('.nivoGallery-slides').append(global.slides[idx]);
                    $(global.slides[idx]).fadeIn(plugin.settings.animSpeed);
                    
                    if(idx == 0){
                        $element.trigger('galleryloaded');
                    }
                    $element.addClass('loaded');
                    $(global.slides[idx]).data('loaded', true);
                    $(global.slides[idx]).addClass('slide-'+ (idx + 1));
                    if(typeof callbackFn == 'function') callbackFn.call(this);
                })
                .attr('src', $(global.slides[idx]).find('img:first').attr('src'))
                .attr('alt', ($(global.slides[idx]).find('img:first').attr('alt') != undefined) ? $(global.slides[idx]).find('img:first').attr('alt') : '')
                .attr('title', ($(global.slides[idx]).find('img:first').attr('title') != undefined) ? $(global.slides[idx]).find('img:first').attr('title') : '');
            } else {
                $element.find('.nivoGallery-slides').append(global.slides[idx]);
                if(idx == 0){
                    $element.trigger('galleryloaded');
                }
                $element.addClass('loaded');
                $(global.slides[idx]).data('loaded', true);
                $(global.slides[idx]).addClass('slide-'+ (idx + 1));
                
                if($(global.slides[idx]).attr('data-type') == 'html') $(global.slides[idx]).wrapInner('<div class="nivoGallery-htmlwrap"></div>');
                if($(global.slides[idx]).attr('data-type') == 'video') $(global.slides[idx]).wrapInner('<div class="nivoGallery-videowrap"></div>');
                
                if(typeof callbackFn == 'function') callbackFn.call(this);
            }
        }
        
        var runTransition = function(direction){
            if(global.animating) return;
            plugin.settings.beforeChange.call(this, global.currentSlide, $(global.slides[global.currentSlide]), global.paused);
            
            if(plugin.settings.effect == 'fade'){
                var galleryEnd = false;
                global.animating = true;
                $(global.slides[global.currentSlide]).fadeOut(plugin.settings.animSpeed, function(){
                    if(direction == 'prev'){
                        global.currentSlide--;
                        if(global.currentSlide < 0){ 
                            global.currentSlide = global.totalSlides - 1;
                            galleryEnd = true;
                        }
                    } else {
                        global.currentSlide++;
                        if(global.currentSlide >= global.totalSlides){ 
                            global.currentSlide = 0;
                            galleryEnd = true;
                        }
                    }
                    loadSlide(global.currentSlide, function(){
                        $element.find('.nivoGallery-count').text(setCount());
                        $element.find('.nivoGallery-caption').html(setCaption());
                        
                        $(global.slides[global.currentSlide]).fadeIn(plugin.settings.animSpeed, function(){
                            global.animating = false;
                            runTimeout();
                            plugin.settings.afterChange.call(this, global.currentSlide, $(global.slides[global.currentSlide]), global.paused);
                            if(galleryEnd) plugin.settings.galleryEnd.call(this);
                        });
                    });
                });
            }
        }
        
        /* Public Funcs */
        plugin.play = function(){
            $element.find('.nivoGallery-play').addClass('playing');
            global.paused = false;
            runTimeout();
        }
        
        plugin.pause = function(){
            $element.find('.nivoGallery-play').removeClass('playing');
            global.paused = true;
            runTimeout();
        }
        
        plugin.nextSlide = function(){
            plugin.pause();
            runTransition('next');
        }
        
        plugin.prevSlide = function(){
            plugin.pause();
            runTransition('prev');
        }
        
        plugin.goTo = function(idx){
            if(idx == global.currentSlide || global.animating) return;
            $(global.slides[global.currentSlide]).fadeOut(plugin.settings.animSpeed);
            global.currentSlide = (idx - 1);
            if(global.currentSlide < 0) global.currentSlide = global.totalSlides - 1;
            if(global.currentSlide >= global.totalSlides - 1) global.currentSlide = global.totalSlides - 2;
                        
            plugin.pause();
            runTransition('next');
        }
        
        /* Events */
        $element.bind('galleryloaded', function(){
            $(global.slides[global.currentSlide]).fadeIn(200);
            
            if(plugin.settings.startPaused){
                plugin.pause();
            } else {
                runTimeout();
            }
            
            plugin.settings.galleryLoaded.call(this);
        });
        
        $element.find('.nivoGallery-play').live('click', function(){
            $(this).toggleClass('playing');
            global.paused = !global.paused;
            runTimeout();
            return false;
        });
        
        $element.bind('nextslide', function(){
            runTransition('next');
        });
        
        $element.find('.nivoGallery-prev').live('click', function(){
        	plugin.prevSlide();
        });
        
        $element.find('.nivoGallery-next').live('click', function(){
        	plugin.nextSlide();
        });
        
        $element.find('.nivoGallery-fullscreen').live('click', function(){
            $element.toggleClass('fullscreen');
        });
        
        $(document).keyup(function(e){
            if(e.keyCode == 27){
                $element.removeClass('fullscreen');
            }
        });

        plugin.init();

    }

    $.fn.nivoGallery = function(options){

        return this.each(function() {
            if (undefined == $(this).data('nivoGallery')){
                var plugin = new $.nivoGallery(this, options);
                $(this).data('nivoGallery', plugin);
            }
        });

    }

})(jQuery);