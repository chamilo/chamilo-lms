var map      = null; var geocoder = null;
function $(window).load( function ()  {
  if (GBrowserIsCompatible()) {
    map = new GMap2(document.getElementById("map")); map.setCenter(new GLatLng(-10.9755,-74.9757), 6); map.addControl(new GSmallMapControl()); map.addControl(new GMapTypeControl()); geocoder = new GClientGeocoder();
    GEvent.addListener(map, "click",
      function(marker, point) {
        if (marker) {
          null;
        } else {
          map.clearOverlays();
          var marcador = new GMarker(point);
          map.addOverlay(marcador);
          document.form_mapa.coordenadas.value = point.y+","+point.x;
          var latitude=document.getElementById("latitude"); var longitude=document.getElementById("longitude");
          while(latitude.firstChild) { latitude.removeChild(latitude.firstChild); }
          latitude.appendChild(document.createTextNode(point.y));
          while(longitude.firstChild) { longitude.removeChild(longitude.firstChild); }
          longitude.appendChild(document.createTextNode(point.x));
} }); } // Cierra LLAVE 1.  
}   // Cierra LLAVE 2. 
);
