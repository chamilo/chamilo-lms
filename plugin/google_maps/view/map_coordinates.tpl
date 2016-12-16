<div id="map" style="width:100%; height:400px;">

</div>
<script>
    $(document).ready(function() {
        initMap();
    });

    function addMaker(lat, lng, map, bounds, userInfo) {

        var location = new google.maps.LatLng(lat, lng);

        var infoWindow = new google.maps.InfoWindow();

        var geocoder = geocoder = new google.maps.Geocoder();

        var marker = new google.maps.Marker({
            map: map,
            position: location,
            label: userInfo.complete_name
        });
        var address = "";

        geocoder.geocode({ 'latLng': location }, function (results) {

            address = results[1].formatted_address;

            var infoWinContent = "<b>" + userInfo.complete_name + "</b> - " + address;

            marker.addListener('click', function() {
                infoWindow.setContent(infoWinContent);
                infoWindow.open(map, marker);
            });
        });

        bounds.extend(marker.position);
        map.fitBounds(bounds);
    }

    function initMap() {
        var center = new google.maps.LatLng(-3.480523, 7.866211);

        var bounds = new google.maps.LatLngBounds();

        var map = new google.maps.Map(document.getElementById("map"), {
            zoom: 2,
            center: center,
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
            },
            navigationControl: true,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        });

        {% for field in extra_field_values %}

            var latLng = '{{ field.value }}';
            latLng = latLng.split(',');

            var lat = latLng[0];
            var lng = latLng[1];

            {% set userInfo = field.itemId | user_info %}

            addMaker(lat, lng, map, bounds, {{ userInfo|json_encode }});

        {% endfor %}

    }
</script>