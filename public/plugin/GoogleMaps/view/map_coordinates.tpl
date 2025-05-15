<div id="map" style="width:100%; height:400px;">

</div>
<script>
    $(document).ready(function() {
        initMap();
    });

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

        var extraFields = {{ extra_field_values_formatted|json_encode }};

        for (var i = 0; i < extraFields.length; i++) {

            var index = i + 1;

            for (var y = 0; y < extraFields[i].length; y++) {

                var address = extraFields[i][y]['address'];
                var userCompleteName = extraFields[i][y]['user_complete_name'];

                addMaker(address, map, bounds, userCompleteName, index);
            }
        }
    }

    function addMaker(address, map, bounds, userCompleteName, index) {

        if (index > 5) {
            return;
        }

        var infoWindow = new google.maps.InfoWindow();

        var geocoder = geocoder = new google.maps.Geocoder();

        var formattedAddress = '';

        geocoder.geocode({ 'address': address }, function (results, status) {

            if (status === google.maps.GeocoderStatus.OK) {

                if (results) {
                    formattedAddress = results[0].formatted_address;
                } else {
                    formattedAddress = '{{ 'Unknown' | get_lang }}';
                }

                var marker = new google.maps.Marker({
                    map: map,
                    position: results[0].geometry.location,
                    label: userCompleteName
                });

                switch (index) {
                    case 1:
                        marker.setIcon('//maps.google.com/mapfiles/ms/icons/red-dot.png');
                        break;
                    case 2:
                        marker.setIcon('//maps.google.com/mapfiles/ms/icons/blue-dot.png');
                        break;
                    case 3:
                        marker.setIcon('//maps.google.com/mapfiles/ms/icons/green-dot.png');
                        break;
                    case 4:
                        marker.setIcon('//maps.google.com/mapfiles/ms/icons/yellow-dot.png');
                        break;
                    case 5:
                        marker.setIcon('//maps.google.com/mapfiles/ms/icons/purple-dot.png');
                        break;
                }

                var infoWinContent = "<b>" + userCompleteName + "</b> - " + formattedAddress;

                marker.addListener('click', function() {
                    infoWindow.setContent(infoWinContent);
                    infoWindow.open(map, marker);
                });

                bounds.extend(marker.position);
                map.fitBounds(bounds);

            } else if (status === google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
                setTimeout(function() {
                    addMaker(address, map, bounds, userCompleteName, index);
                }, 350);
            }
        });
    }
</script>