<section class="bg-white rounded-2xl shadow-sm border p-6 mb-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="text-sm font-semibold uppercase text-primary">{{ plugin_title }}</div>
            <h1 class="text-2xl font-bold">{{ users_coordinates_map }}</h1>
            <p class="text-gray-500">{{ users_coordinates_map_help }}</p>
        </div>
        <a class="btn btn--secondary" href="{{ admin_url }}">
            <em class="mdi mdi-arrow-left"></em> {{ 'Back'|get_lang }}
        </a>
    </div>
</section>

{% if warnings is not empty %}
    <section class="bg-yellow-50 border border-yellow-200 text-yellow-900 rounded-2xl p-4 mb-6">
        <ul class="list-disc ml-5">
            {% for warning in warnings %}
                <li>{{ warning|raw }}</li>
            {% endfor %}
        </ul>
    </section>
{% endif %}

<section class="bg-white rounded-2xl shadow-sm border p-6">
    {% if api_ready %}
        <div id="google-maps-users-map" class="w-full rounded-xl border" style="height: 520px;"></div>
        <div id="google-maps-empty-state" class="hidden text-center text-gray-500 py-10">
            {{ no_user_coordinates_found }}
        </div>
    {% else %}
        <div class="text-center text-gray-500 py-10">
            {{ configure_google_maps_first }}
        </div>
    {% endif %}
</section>

{% if api_ready %}
<script>
document.addEventListener("DOMContentLoaded", function () {
    const extraFields = {{ extra_field_values_formatted|json_encode|raw }};
    const mapElement = document.getElementById("google-maps-users-map");
    const emptyElement = document.getElementById("google-maps-empty-state");

    if (!mapElement || typeof google === "undefined" || !google.maps) {
        return;
    }

    const hasCoordinates = extraFields.some(function (group) {
        return Array.isArray(group) && group.length > 0;
    });

    if (!hasCoordinates) {
        mapElement.classList.add("hidden");

        if (emptyElement) {
            emptyElement.classList.remove("hidden");
        }

        return;
    }

    const center = new google.maps.LatLng(20, 0);
    const bounds = new google.maps.LatLngBounds();
    const map = new google.maps.Map(mapElement, {
        zoom: 2,
        center: center,
        mapTypeControl: true,
        mapTypeControlOptions: {
            style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
        },
        mapTypeId: google.maps.MapTypeId.ROADMAP
    });

    const iconUrls = [
        "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
        "https://maps.google.com/mapfiles/ms/icons/blue-dot.png",
        "https://maps.google.com/mapfiles/ms/icons/green-dot.png",
        "https://maps.google.com/mapfiles/ms/icons/yellow-dot.png",
        "https://maps.google.com/mapfiles/ms/icons/purple-dot.png"
    ];

    const infoWindow = new google.maps.InfoWindow();
    const geocoder = new google.maps.Geocoder();

    extraFields.forEach(function (group, groupIndex) {
        if (!Array.isArray(group) || groupIndex >= iconUrls.length) {
            return;
        }

        group.forEach(function (item) {
            addMarker(item.address, item.user_complete_name, iconUrls[groupIndex]);
        });
    });

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function addMarker(address, userCompleteName, iconUrl) {
        if (!address) {
            return;
        }

        geocoder.geocode({ address: address }, function (results, status) {
            if (status === google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
                window.setTimeout(function () {
                    addMarker(address, userCompleteName, iconUrl);
                }, 500);

                return;
            }

            if (status !== google.maps.GeocoderStatus.OK || !results || !results[0]) {
                return;
            }

            const marker = new google.maps.Marker({
                map: map,
                position: results[0].geometry.location,
                icon: iconUrl
            });

            const formattedAddress = results[0].formatted_address || address;
            const infoContent = "<strong>" + escapeHtml(userCompleteName) + "</strong><br>" + escapeHtml(formattedAddress);

            marker.addListener("click", function () {
                infoWindow.setContent(infoContent);
                infoWindow.open(map, marker);
            });

            bounds.extend(marker.position);
            map.fitBounds(bounds);
        });
    }
});
</script>
{% endif %}
