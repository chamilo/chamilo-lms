import { computed, nextTick, onUnmounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useCidReqStore } from "../store/cidReq"
import pluginRegionService from "../services/pluginRegionService"

/**
 * @param {string} region
 * @returns {{blocks: Ref<[]>}}
 */
export function usePluginRegion(region) {
  const route = useRoute()
  const router = useRouter()
  const cidReqStore = useCidReqStore()

  const blocks = ref([])
  const injectedElements = ref([])
  let abortController = null
  let stopRequestWatcher = null
  let isUnmounted = false

  function normalizeContextId(value) {
    if (value === null || value === undefined || value === "") {
      return undefined
    }

    return String(value)
  }

  const resolvedParams = computed(() => ({
    ...route.query,
    ...route.params,
    cid: normalizeContextId(cidReqStore.course?.id ?? route.query.cid),
    sid: normalizeContextId(cidReqStore.session?.id ?? route.query.sid),
    gid: normalizeContextId(cidReqStore.group?.id ?? route.query.gid),
    _route: route.path,
    _route_name: route.name ?? undefined,
  }))

  const requestKey = computed(() => JSON.stringify(resolvedParams.value))

  async function fetchBlocks() {
    if (abortController) {
      abortController.abort()
    }

    abortController = new AbortController()

    cleanupInjectedElements()

    blocks.value = []

    try {
      const data = await pluginRegionService.getRegion(region, {
        params: resolvedParams.value,
        signal: abortController.signal,
      })

      blocks.value = data.blocks || []

      await nextTick()

      executeInlineScripts()
      injectAssets()
    } catch (e) {
      // Ignore request cancellations triggered by AbortController
      if (e?.code !== "ERR_CANCELED" && e?.name !== "CanceledError") {
        blocks.value = []
      }
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

  function cleanupInjectedElements() {
    injectedElements.value.forEach((el) => el.remove())
    injectedElements.value = []
  }

  router.isReady().then(() => {
    if (isUnmounted) {
      return
    }

    stopRequestWatcher = watch(requestKey, fetchBlocks, { immediate: true })
  })

  onUnmounted(() => {
    isUnmounted = true

    if (stopRequestWatcher) {
      stopRequestWatcher()
    }

    if (abortController) {
      abortController.abort()
    }

    cleanupInjectedElements()
  })

  return {
    blocks,
  }
}
