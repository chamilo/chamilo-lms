import { computed, nextTick, onUnmounted, ref, watch } from "vue"
import { useRoute } from "vue-router"
import { useCidReqStore } from "../store/cidReq"
import api from "../config/api"

export function usePluginRegion(region, context = ref({})) {
  const route = useRoute()
  const cidReqStore = useCidReqStore()

  const blocks = ref([])
  const injectedElements = ref([])

  const resolvedParams = computed(() => ({
    ...route.query,
    ...route.params,
    cid: cidReqStore.course?.id ?? undefined,
    sid: cidReqStore.session?.id ?? undefined,
    gid: cidReqStore.group?.id ?? undefined,
    _route: route.path,
    _route_name: route.name ?? undefined,
    ...(typeof context.value === "object" ? context.value : context),
  }))

  async function fetchBlocks() {
    cleanup()

    try {
      const { data } = await api.get(`/plugin-regions/${region}`, {
        headers: { Accept: "application/json" },
        params: resolvedParams.value,
      })

      blocks.value = data.blocks || []

      await nextTick()

      executeInlineScripts()
      injectAssets()
    } catch {
      blocks.value = []
    }
  }

  function executeInlineScripts() {
    const container = document.querySelector(`[data-region="${region}"]`)

    if (!container) {
      return
    }

    container.querySelectorAll("script").forEach((original) => {
      const script = document.createElement("script")

      for (const attr of original.attributes) {
        script.setAttribute(attr.name, attr.value)
      }

      if (original.textContent) {
        script.textContent = original.textContent
      }

      original.replaceWith(script)
      injectedElements.value.push(script)
    })
  }

  function injectAssets() {
    blocks.value.forEach((block) => {
      if (block.css) {
        block.css.forEach((href) => {
          if (document.querySelector(`link[href="${href}"]`)) {
            return
          }

          const link = document.createElement("link")
          link.rel = "stylesheet"
          link.href = href

          document.head.appendChild(link)
          injectedElements.value.push(link)
        })
      }

      if (block.js) {
        block.js.forEach((src) => {
          if (document.querySelector(`script[src="${src}"]`)) {
            return
          }

          const script = document.createElement("script")
          script.src = src
          script.async = false

          document.body.appendChild(script)
          injectedElements.value.push(script)
        })
      }
    })
  }

  function cleanup() {
    injectedElements.value.forEach((el) => el.remove())
    injectedElements.value = []
  }

  watch(resolvedParams, fetchBlocks, { immediate: true })

  onUnmounted(cleanup)

  return {
    blocks,
  }
}
