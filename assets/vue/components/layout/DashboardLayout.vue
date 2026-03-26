<template>
  <Topbar />
  <Sidebar v-if="securityStore.isAuthenticated" />
  <div
    ref="appMainRef"
    :class="{ 'app-main--no-sidebar': !securityStore.isAuthenticated }"
    class="app-main"
  >
    <Breadcrumb v-if="showBreadcrumb" />
    <router-view />
    <slot />
  </div>
</template>

<script setup>
import Breadcrumb from "../../components/Breadcrumb.vue"
import Topbar from "../../components/layout/Topbar.vue"
import Sidebar from "../../components/layout/Sidebar.vue"
import { useSecurityStore } from "../../store/securityStore"
import { useVisualTheme } from "../../composables/theme"
import { ref, watch, onMounted } from "vue"
import { useRoute } from "vue-router"

defineProps({
  showBreadcrumb: {
    type: Boolean,
    default: true,
  },
})

const securityStore = useSecurityStore()
const { getThemeAssetUrl } = useVisualTheme()
const route = useRoute()
const appMainRef = ref(null)

const pageBackgrounds = {
  "page-administration-platform": "images/bg-cityscape.png",
  "page-administration-session": "images/bg-fieldscape.png",
  "page-my-courses": "images/bg-landscape.png",
  "page-sessions": "images/bg-landscape.png",
  "page-social": "images/bg-seascape.png",
}

function applyBackground() {
  const el = appMainRef.value
  if (!el) return

  const bodyClasses = document.body.classList
  let bgImage = null

  for (const [pageClass, image] of Object.entries(pageBackgrounds)) {
    if (bodyClasses.contains(pageClass)) {
      bgImage = image
      break
    }
  }

  if (bgImage) {
    el.style.backgroundImage = `url('${getThemeAssetUrl(bgImage)}')`
    el.style.backgroundRepeat = "repeat-x"
    el.style.backgroundPosition = "bottom center"
    el.style.backgroundSize = "auto 180px"
    el.style.paddingBottom = "200px"
  } else {
    el.style.backgroundImage = ""
    el.style.backgroundRepeat = ""
    el.style.backgroundPosition = ""
    el.style.backgroundSize = ""
    el.style.paddingBottom = ""
  }
}

watch(() => route.path, () => {
  setTimeout(applyBackground, 0)
})

onMounted(applyBackground)
</script>
