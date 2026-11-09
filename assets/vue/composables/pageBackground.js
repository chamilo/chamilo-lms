import { nextTick, onMounted, ref, watch } from "vue"
import { useRoute } from "vue-router"
import { useVisualTheme } from "./theme"
import { usePlatformConfig } from "../store/platformConfig"

// The slot of the theme images each page shows at its bottom
const pageBackgrounds = {
  "page-administration-platform": "cityscape",
  "page-administration-session": "fieldscape",
  "page-my-courses": "landscape",
  "page-sessions": "landscape",
  "page-social": "seascape",
}

export function usePageBackground() {
  const { getThemeAssetUrl } = useVisualTheme()
  const platformConfigStore = usePlatformConfig()
  const route = useRoute()
  const appMainRef = ref(null)

  /**
   * The image the administrator chose for the slot, none when they removed it, or the one the
   * theme ships until the platform configuration is loaded.
   */
  function getBackgroundUrl(slot) {
    const configured = platformConfigStore.pageBackgrounds?.[slot]

    return undefined === configured ? getThemeAssetUrl(`images/bg-${slot}.png`) : configured
  }

  function applyBackground() {
    const el = appMainRef.value

    if (!el) {
      return
    }

    const matchedClass = Object.keys(pageBackgrounds).find((cls) => document.body.classList.contains(cls))
    const backgroundUrl = matchedClass ? getBackgroundUrl(pageBackgrounds[matchedClass]) : null

    if (backgroundUrl) {
      el.style.backgroundImage = `url('${backgroundUrl}')`
      el.style.backgroundRepeat = "repeat-x"
      el.style.backgroundPosition = "bottom center"
      el.style.backgroundSize = "auto 180px"
      el.style.paddingBottom = "200px"
    } else {
      el.style.cssText = ""
    }
  }

  watch([() => route.path, () => platformConfigStore.pageBackgrounds], () => nextTick(applyBackground))

  onMounted(applyBackground)

  return { appMainRef }
}
