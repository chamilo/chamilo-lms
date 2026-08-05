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
        {% if map_provider == 'openstreetmap' %}
            <div id="openstreetmap-users-map" class="w-full overflow-hidden rounded-xl border" style="height: 520px;"></div>
        {% else %}
            <div id="google-maps-users-map" class="w-full rounded-xl border" style="height: 520px;"></div>
        {% endif %}
        <div id="google-maps-empty-state" class="hidden text-center text-gray-500 py-10">
            {{ no_user_coordinates_found }}
        </div>
        <div id="map-users-list" class="mt-4 space-y-2"></div>
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
    const provider = {{ map_provider|json_encode|raw }};
    const defaultLatitude = Number({{ default_latitude|json_encode|raw }});
    const defaultLongitude = Number({{ default_longitude|json_encode|raw }});
    const defaultZoom = Number({{ default_zoom|json_encode|raw }});
    const emptyElement = document.getElementById("google-maps-empty-state");
    const listElement = document.getElementById("map-users-list");
    const markers = [];

    extraFields.forEach(function (group, groupIndex) {
        if (!Array.isArray(group)) {
            return;
        }

        group.forEach(function (item) {
            markers.push({
                groupIndex: groupIndex,
                address: item.address || "",
                label: item.label || item.address || "",
                lat: item.lat,
                lng: item.lng,
                user_complete_name: item.user_complete_name || ""
            });
        });
    });

    if (markers.length === 0) {
        const googleElement = document.getElementById("google-maps-users-map");
        const osmElement = document.getElementById("openstreetmap-users-map");

        if (googleElement) {
            googleElement.classList.add("hidden");
        }

        if (osmElement) {
            osmElement.classList.add("hidden");
        }

        if (emptyElement) {
            emptyElement.classList.remove("hidden");
        }

        return;
    }

    renderMarkerList(markers);

    if (provider === "openstreetmap") {
        renderOpenStreetMap(markers);
        return;
    }

    renderGoogleMap(markers);

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function renderMarkerList(items) {
        if (!listElement) {
            return;
        }

        listElement.innerHTML = items.map(function (item) {
            const label = escapeHtml(item.label || item.address);
            const user = escapeHtml(item.user_complete_name);
            const hasCoords = Number.isFinite(Number(item.lat)) && Number.isFinite(Number(item.lng));
            const osmUrl = hasCoords
                ? "https://www.openstreetmap.org/?mlat=" + encodeURIComponent(item.lat) + "&mlon=" + encodeURIComponent(item.lng) + "#map=15/" + encodeURIComponent(item.lat) + "/" + encodeURIComponent(item.lng)
                : "https://www.openstreetmap.org/search?query=" + encodeURIComponent(item.address || item.label || "");

            return "<div class=\"rounded-lg border border-gray-20 p-3\"><strong>" + user + "</strong><br><span>" + label + "</span><br><a target=\"_blank\" rel=\"noopener noreferrer\" href=\"" + osmUrl + "\">Open in OpenStreetMap</a></div>";
        }).join("");
    }

    function renderOpenStreetMap(items) {
        const mapElement = document.getElementById("openstreetmap-users-map");

        if (!mapElement) {
            return;
        }

        const firstWithCoordinates = items.find(function (item) {
            return Number.isFinite(Number(item.lat)) && Number.isFinite(Number(item.lng));
        });

        if (!firstWithCoordinates) {
            mapElement.classList.add("hidden");
            return;
        }

        const lat = Number(firstWithCoordinates.lat);
        const lng = Number(firstWithCoordinates.lng);
        const delta = 0.05;
        const bbox = [
            lng - delta,
            lat - delta,
            lng + delta,
            lat + delta
        ].join(",");

        const iframe = document.createElement("iframe");
        iframe.title = "OpenStreetMap";
        iframe.width = "100%";
        iframe.height = "520";
        iframe.loading = "lazy";
        iframe.referrerPolicy = "no-referrer-when-downgrade";
        iframe.className = "h-full w-full border-0";
        iframe.src = "https://www.openstreetmap.org/export/embed.html?bbox=" + encodeURIComponent(bbox) + "&layer=mapnik&marker=" + encodeURIComponent(lat + "," + lng);

        mapElement.innerHTML = "";
        mapElement.appendChild(iframe);
    }

    function renderGoogleMap(items) {
        const mapElement = document.getElementById("google-maps-users-map");

        if (!mapElement || typeof google === "undefined" || !google.maps) {
            return;
        }

        const center = new google.maps.LatLng(defaultLatitude || 20, defaultLongitude || 0);
        const bounds = new google.maps.LatLngBounds();
        const map = new google.maps.Map(mapElement, {
            zoom: defaultZoom || 2,
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

        items.forEach(function (item) {
            const iconUrl = iconUrls[item.groupIndex] || iconUrls[0];
            addMarker(item, iconUrl);
        });

        function addMarker(item, iconUrl) {
            const hasCoordinates = Number.isFinite(Number(item.lat)) && Number.isFinite(Number(item.lng));

            if (hasCoordinates) {
                createMarker(new google.maps.LatLng(Number(item.lat), Number(item.lng)), item, iconUrl);
                return;
            }

            if (!item.address) {
                return;
            }

            geocoder.geocode({ address: item.address }, function (results, status) {
                if (status === google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
                    window.setTimeout(function () {
                        addMarker(item, iconUrl);
                    }, 500);

                    return;
                }

                if (status !== google.maps.GeocoderStatus.OK || !results || !results[0]) {
                    return;
                }

                createMarker(results[0].geometry.location, item, iconUrl);
            });
        }

        function createMarker(position, item, iconUrl) {
            const marker = new google.maps.Marker({
                map: map,
                position: position,
                icon: iconUrl
            });

            const label = item.label || item.address || "";
            const infoContent = "<strong>" + escapeHtml(item.user_complete_name) + "</strong><br>" + escapeHtml(label);

            marker.addListener("click", function () {
                infoWindow.setContent(infoContent);
                infoWindow.open(map, marker);
            });

            bounds.extend(marker.position);
            map.fitBounds(bounds);
        }
    }
});
</script>
{% endif %}
