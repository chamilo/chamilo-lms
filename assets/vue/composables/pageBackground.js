import { nextTick, onMounted, ref, watch } from "vue"
import { useRoute } from "vue-router"
import { useVisualTheme } from "./theme"

const pageBackgrounds = {
  "page-administration-platform": "images/bg-cityscape.png",
  "page-administration-session": "images/bg-fieldscape.png",
  "page-my-courses": "images/bg-landscape.png",
  "page-sessions": "images/bg-landscape.png",
  "page-social": "images/bg-seascape.png",
}

export function usePageBackground() {
  const { getThemeAssetUrl } = useVisualTheme()
  const route = useRoute()
  const appMainRef = ref(null)

  function applyBackground() {
    const el = appMainRef.value

    if (!el) {
      return
    }

    const matchedClass = Object.keys(pageBackgrounds).find((cls) => document.body.classList.contains(cls))

    if (matchedClass) {
      el.style.backgroundImage = `url('${getThemeAssetUrl(pageBackgrounds[matchedClass])}')`
      el.style.backgroundRepeat = "repeat-x"
      el.style.backgroundPosition = "bottom center"
      el.style.backgroundSize = "auto 180px"
      el.style.paddingBottom = "200px"
    } else {
      el.style.cssText = ""
    }
  }

  watch(
    () => route.path,
    () => nextTick(applyBackground),
  )

  onMounted(applyBackground)

  return { appMainRef }
}
