 /**
 * @fileOverview Leaflet Map Widget.
 */
(function() {
  /* Flow of Control for CKEditor and Widget components:
      Loading the page:
      CKEditor init()
      Widget upcast()

      Creating new widgets:
      Widget init()
      Widget data()
      Dialog select element's items()
      Dialog onShow()
      Dialog setup()
      Dialog commit()

      When editing existing widgets:
      Dialog onShow()
      Dialog setup()
      Dialog commit()
      Widget data()

      When saving page or clicking the CKEditor's Source button:
      Widget downcast()
   */

  // Dummy global method for quick workaround of asynchronous document.write()
  // issue of Google APIs with respect to CKEditor.
  // See the Google APIs URL below for the query string usage of this dummy().
  // Using this hack, the document.write(...) requirement of Google APIs
  // will be replaced by the more 'gentle' combination of
  // document.createElement(...) and document.body.appendChild(...).
  window.dummy = function(){
    // Do nothing.
  }

  // Load the needed external libraries. This is asynchronous loading,
  // that is, they will be loaded in parallel to boost performance.
  // See also CKEDITOR.scriptLoader.queue.
  CKEDITOR.scriptLoader.load('//code.jquery.com/jquery-1.11.0.min.js');
  CKEDITOR.scriptLoader.load('http://maps.googleapis.com/maps/api/js?libraries=places&sensor=false&callback=dummy');

  // Add a new CKEditor plugin. Note that widgets are subclass of plugins.
  CKEDITOR.plugins.add('leaflet', {
    // Declare dependencies.
    requires: 'widget',

    init: function(editor) {
      // Declare a new Dialog for interactive selection of
      // map parameters. It's still not bound to any widget at this moment.
      CKEDITOR.dialog.add('leaflet', this.path + 'dialogs/leaflet.js');

      // For reusability, declare a global variable pointing to the map script path
      // that will build and render the map.
      // In JavaScript, relative path must include the leading slash.
      mapParserPath = CKEDITOR.getUrl(this.path + 'scripts/mapParser.html');

      // Declare a new widget.
      editor.widgets.add('leaflet', {
        // Bind the widget to the Dialog command.
        dialog: 'leaflet',

        // Declare the elements to be upcasted back.
        // Otherwise, the widget's code will be ignored.
        // Basically, we will allow all divs with 'leaflet_div' class,
        // including their alignment classes, and all iframes with
        // 'leaflet_iframe' class, and then include
        // all their attributes.
        // Read more about the Advanced Content Filter here:
        // * http://docs.ckeditor.com/#!/guide/dev_advanced_content_filter
        // * http://docs.ckeditor.com/#!/guide/plugin_sdk_integration_with_acf
        allowedContent: 'div(!leaflet_div,align-left,align-right,align-center)[*];'
                            + 'iframe(!leaflet_iframe)[*];',

        // Declare the widget template/structure, containing the
        // important elements/attributes. This is a required property of widget.
        template:
          '<div id="" class="leaflet_div" data-lat="" data-lon="" data-width="" data-height="" ' +
          'data-zoom="" data-popup-text="" data-tile="" data-minimap="" data-alignment=""></div>',

        // This will be executed when going from the View Mode to Source Mode.
        // This is usually used as the function to convert the widget to a
        // dummy, simpler, or equivalent textual representation.
        downcast: function(element) {
          // Note that 'element' here refers to the DIV widget.
          // Get the previously saved zoom value data attribute.
          // It will be compared to the current value in the map view.
          var zoomSaved = element.attributes["data-zoom"];

          // Get the id of the div element.
          var divId = element.attributes["id"];

          // Get the numeric part of divId: leaflet_div-1399121271748.
          // We'll use that number for quick fetching of target iframe.
          var iframeId = "leaflet_iframe-" + divId.substring(12);

          // Get the zoom level's snapshot because the current user
          // might have changed it via mouse events or via the zoom bar.
          // Basically, get the zoom level of a map embedded
          // in this specific iframe and widget.
          var zoomIframe = editor.document.$.getElementById(iframeId).contentDocument.getElementById("map_container").getAttribute("data-zoom");

          // In case there are changes in zoom level.
          if (zoomIframe != zoomSaved) {
            // Update the saved zoom value in data attribute.
            element.attributes["data-zoom"] = zoomIframe;

            // Fetch the data attributes needed for
            // updating the full path of the map.
            var latitude = element.attributes["data-lat"];
            var longitude = element.attributes["data-lon"];
            var width = element.attributes["data-width"];
            var height = element.attributes["data-height"];
            var zoom = element.attributes["data-zoom"];
            var popUpText = element.attributes["data-popup-text"];
            var tile = element.attributes["data-tile"];
            var minimap = element.attributes["data-minimap"];

            // Build the updated full path to the map renderer.
            var mapParserPathFull = mapParserPath + "?lat=" + latitude + "&lon=" + longitude + "&width=" + width + "&height=" + height + "&zoom=" + zoom + "&text=" + popUpText + "&tile=" + tile + "&minimap=" + minimap;

            // Update also the iframe's 'src' attributes.
            // Updating 'data-cke-saved-src' is also required for
            // internal use of CKEditor.
            element.children[0].attributes["src"] = mapParserPathFull;
            element.children[0].attributes["data-cke-saved-src"] = mapParserPathFull;
          }

          // Return the DOM's textual representation.
          return element;
        },

        // Required property also for widgets, used when switching
        // from CKEditor's Source Mode to View Mode.
        // The reverse of downcast() method.
        upcast: function(element) {
          // If we encounter a div with a class of 'leaflet_div',
          // it means that it's a widget and we need to convert it properly
          // to its original structure.
          // Basically, it says to CKEditor which div is a valid widget.
          if (element.name == 'div' && element.hasClass('leaflet_div')) {
            return element;
          }
        },
      });

      // Add the widget button in the toolbar and bind the widget command,
      // which is also bound to the Dialog command.
      // Apparently, this is required just like their plugin counterpart.
      editor.ui.addButton('leaflet', {
        label : 'Leaflet Map',
        command : 'leaflet',
        icon : this.path + 'icons/leaflet.png',
        toolbar: "insert,1"
      });

      // Append the widget's styles when in the CKEditor edit page,
      // added for better user experience.
      // Assign or append the widget's styles depending on the existing setup.
      if (typeof editor.config.contentsCss == 'object') {
          editor.config.contentsCss.push(CKEDITOR.getUrl(this.path + 'css/contents.css'));
      }

      else {
        editor.config.contentsCss = [editor.config.contentsCss, CKEDITOR.getUrl(this.path + 'css/contents.css')];
      }
    },
  });
})();
