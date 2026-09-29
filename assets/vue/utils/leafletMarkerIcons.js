// Webpack renames Leaflet's default marker images, breaking its built-in
// relative-path lookup; point it at the bundled URLs instead (well-known
// Leaflet + bundler fix, see https://github.com/Leaflet/Leaflet/issues/4968).
// Side-effect only import -- has no exports, just patches the shared
// L.Icon.Default singleton once per page load.
import L from "leaflet"
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png"
import markerIcon from "leaflet/dist/images/marker-icon.png"
import markerShadow from "leaflet/dist/images/marker-shadow.png"

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
})
