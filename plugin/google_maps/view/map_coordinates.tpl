<div id="map" style="width:100%; height:400px;">

</div>
<script>
    $(document).ready(function() {
        initMap();
    });

    function initMap() {
        var center = new google.maps.LatLng(-3.480523, 7.866211);

        var bounds = new google.maps.LatLngBounds();
        var infoWindow = new google.maps.InfoWindow();

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

            var location = new google.maps.LatLng(lat, lng);

            {% set userInfo = field.itemId | user_info %}

            var marker = new google.maps.Marker({
                map: map,
                position: location,
                label: "{{ userInfo.complete_name }}"
            });

            bounds.extend(marker.position);
            map.fitBounds(bounds);

        {% endfor %}

    }
</script>