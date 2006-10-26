// Image Preloader  v1.0.1
// documentation: http://www.dithered.com/javascript/image_preloader/index.html
// license: http://creativecommons.org/licenses/by/1.0/
// code by Chris Nott (chris[at]dithered[dot]com)


function preloadImages() {
	if (document.images) {
		for (var i = 0; i < preloadImages.arguments.length; i++) {
			(new Image()).src = preloadImages.arguments[i];
		}
	}
}