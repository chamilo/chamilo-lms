import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue"

export function useTopbarTour({ platformConfigStore, route, isAnonymous }) {
  const tourBusy = ref(false)
  const tourAvailableForCurrentPage = ref(false)
  let tourRefreshTimerIds = []
  let tourBodyClassObserver = null

  function normalizeBooleanFlag(value, defaultValue = false) {
    if (typeof value === "boolean") return value
    if (typeof value === "number") return value === 1

    if (typeof value === "string") {
      const normalized = value.trim().toLowerCase()

      if (["1", "true", "yes", "on"].includes(normalized)) return true
      if (["0", "false", "no", "off"].includes(normalized)) return false
    }

    return defaultValue
  }

  const tourConfig = computed(() => {
    return platformConfigStore.plugins?.tour || {}
  })

  const showTourButton = computed(() => {
    if (isAnonymous.value) {
      return false
    }

    const enabled = normalizeBooleanFlag(tourConfig.value?.enabled, false)
    const showTour = normalizeBooleanFlag(tourConfig.value?.showTour, true)

    return enabled && showTour && tourAvailableForCurrentPage.value
  })

  function getTourConfigValue(key, fallback = "") {
    const value = tourConfig.value?.[key]

    return typeof value === "string" && value.trim() !== "" ? value : fallback
  }

  function getCurrentTourPageClass() {
    const body = document.body

    if (!body) {
      return null
    }

    const classes = Array.from(body.classList).filter((className) => className.startsWith("page-"))

    if (!classes.length) {
      return null
    }

    return `body.${classes.join(".")}`
  }

  function loadTourCssOnce(href, key) {
    if (!href) {
      return
    }

    if (document.querySelector(`link[${key}="1"]`)) {
      return
    }

    const link = document.createElement("link")
    link.rel = "stylesheet"
    link.href = href
    link.setAttribute(key, "1")
    document.head.appendChild(link)
  }

  function ensureTourScriptLoaded(src) {
    return new Promise((resolve, reject) => {
      if (window.introJs) {
        resolve()
        return
      }

      const existingScript = document.querySelector('script[data-tour-intro-js="1"]')

      if (existingScript) {
        existingScript.addEventListener("load", () => resolve(), { once: true })
        existingScript.addEventListener("error", () => reject(new Error("Unable to load intro.js")), {
          once: true,
        })
        return
      }

      const script = document.createElement("script")
      script.src = src
      script.setAttribute("data-tour-intro-js", "1")
      script.onload = () => resolve()
      script.onerror = () => reject(new Error("Unable to load intro.js"))
      document.body.appendChild(script)
    })
  }

  async function fetchTourSteps(pageClass) {
    const stepsAjax = getTourConfigValue("stepsAjax", "/plugin/Tour/ajax/steps.ajax.php")

    const response = await fetch(`${stepsAjax}?page=${encodeURIComponent(pageClass)}`, {
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
      },
    })

    if (!response.ok) {
      throw new Error(`Tour steps request failed: ${response.status}`)
    }

    const data = await response.json()

    return Array.isArray(data) ? data : []
  }

  function filterValidTourSteps(steps) {
    if (!Array.isArray(steps)) {
      return []
    }

    return steps.filter((step) => {
      if (!step || typeof step !== "object") {
        return false
      }

      if (!step.element) {
        return true
      }

      const element = document.querySelector(step.element)

      if (!element) {
        return false
      }

      const rect = element.getBoundingClientRect()

      if (rect.width <= 0 || rect.height <= 0) {
        return false
      }

      const style = window.getComputedStyle(element)

      if (style.display === "none" || style.visibility === "hidden") {
        return false
      }

      return true
    })
  }

  function clearScheduledTourRefreshes() {
    if (!tourRefreshTimerIds.length) {
      return
    }

    for (const timerId of tourRefreshTimerIds) {
      window.clearTimeout(timerId)
    }

    tourRefreshTimerIds = []
  }

  function scheduleTourAvailabilityRefresh() {
    clearScheduledTourRefreshes()
    window.dispatchEvent(new CustomEvent("tour:refresh-request"))

    const refreshDelays = [0, 120, 350, 700, 1200]

    for (const delay of refreshDelays) {
      const timerId = window.setTimeout(() => {
        void refreshTourAvailability()
      }, delay)

      tourRefreshTimerIds.push(timerId)
    }
  }

  function registerTourBodyClassObserver() {
    if (!document.body || typeof MutationObserver === "undefined") {
      return
    }

    if (tourBodyClassObserver) {
      tourBodyClassObserver.disconnect()
    }

    tourBodyClassObserver = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        if (mutation.type === "attributes" && mutation.attributeName === "class") {
          scheduleTourAvailabilityRefresh()
          return
        }
      }
    })

    tourBodyClassObserver.observe(document.body, {
      attributes: true,
      attributeFilter: ["class"],
    })
  }

  async function refreshTourAvailability() {
    if (window["ChamiloTour"] && typeof window["ChamiloTour"].hasSteps === "function") {
      tourAvailableForCurrentPage.value = !!window["ChamiloTour"].hasSteps()
      return
    }

    const pageClass = getCurrentTourPageClass()

    if (!pageClass) {
      tourAvailableForCurrentPage.value = false
      return
    }

    try {
      const steps = await fetchTourSteps(pageClass)
      const validSteps = filterValidTourSteps(steps)
      tourAvailableForCurrentPage.value = validSteps.length > 0
    } catch (e) {
      console.warn("[Topbar][Tour] Failed to refresh availability", e)
      tourAvailableForCurrentPage.value = false
    }
  }

  function handleTourAvailabilityChange(event) {
    if (event?.detail && typeof event.detail.hasSteps !== "undefined") {
      tourAvailableForCurrentPage.value = !!event.detail.hasSteps
      return
    }

    void refreshTourAvailability()
  }

  function handleTourNavigationSignal() {
    scheduleTourAvailabilityRefresh()
  }

  async function saveTourCompletion(pageClass) {
    const saveAjax = getTourConfigValue("saveAjax", "/plugin/Tour/ajax/save.ajax.php")
    const params = new URLSearchParams()
    params.append("page_class", pageClass)

    try {
      await fetch(saveAjax, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: params.toString(),
      })
    } catch (e) {
      console.warn("[Topbar][Tour] Failed to save completion state", e)
    }
  }

  async function startTourFallback() {
    const pageClass = getCurrentTourPageClass()

    if (!pageClass) {
      console.warn("[Topbar][Tour] Page class not detected")
      return false
    }

    const steps = await fetchTourSteps(pageClass)
    const validSteps = filterValidTourSteps(steps)

    if (!validSteps.length) {
      console.warn("[Topbar][Tour] No valid steps available for this page")
      return false
    }

    const introCss = getTourConfigValue("introCss", "/plugin/Tour/intro.js/introjs.min.css")
    const introThemeCss = getTourConfigValue("introThemeCss", "")
    const introJs = getTourConfigValue("introJs", "/plugin/Tour/intro.js/intro.min.js")

    loadTourCssOnce(introCss, "data-tour-intro-css")

    if (introThemeCss) {
      loadTourCssOnce(introThemeCss, "data-tour-intro-theme-css")
    }

    await ensureTourScriptLoaded(introJs)

    if (!window.introJs) {
      console.warn("[Topbar][Tour] introJs is unavailable after loading")
      return false
    }

    const intro = window.introJs()

    intro.setOptions({
      steps: validSteps,
      overlayOpacity: 0.34,
      exitOnOverlayClick: true,
      showBullets: true,
      showProgress: true,
      scrollToElement: true,
      disableInteraction: false,
      nextLabel: "Next →",
      prevLabel: "← Back",
      doneLabel: "Finish",
      skipLabel: "Skip",
    })

    intro.oncomplete(() => {
      void saveTourCompletion(pageClass)
    })

    intro.onexit(() => {
      void saveTourCompletion(pageClass)
    })

    intro.start()

    return true
  }

  async function startTourFromTopbar() {
    if (tourBusy.value) {
      return
    }

    tourBusy.value = true

    try {
      if (window["ChamiloTour"] && typeof window["ChamiloTour"].start === "function") {
        await window["ChamiloTour"].start()
        return
      }

      await startTourFallback()
    } catch (e) {
      console.warn("[Topbar][Tour] Failed to start tour", e)
    } finally {
      tourBusy.value = false
    }
  }

  onMounted(() => {
    scheduleTourAvailabilityRefresh()

    window.addEventListener("tour:availability-change", handleTourAvailabilityChange)
    window.addEventListener("popstate", handleTourNavigationSignal)
    window.addEventListener("hashchange", handleTourNavigationSignal)

    registerTourBodyClassObserver()
  })

  onBeforeUnmount(() => {
    window.removeEventListener("tour:availability-change", handleTourAvailabilityChange)
    window.removeEventListener("popstate", handleTourNavigationSignal)
    window.removeEventListener("hashchange", handleTourNavigationSignal)

    if (tourBodyClassObserver) {
      tourBodyClassObserver.disconnect()
      tourBodyClassObserver = null
    }

    clearScheduledTourRefreshes()
  })

  watch(
    () => route.fullPath,
    () => {
      scheduleTourAvailabilityRefresh()
    },
  )

  return {
    tourBusy,
    showTourButton,
    startTourFromTopbar,
  }
}
