<template>
  <div class="field">
    <FloatLabel
      variant="on"
      :class="{
        'input-has-content': hasContent,
        'input-has-focus': isFocused,
      }"
    >
      <TinyEditor
        :id="editorId"
        v-model="modelValue"
        :init="editorConfig"
        :required="required"
      />
      <label
        v-if="title"
        :for="editorId"
      >
        {{ title }}
      </label>
    </FloatLabel>
    <small
      v-if="helpText"
      v-text="helpText"
    />
  </div>
</template>

<script setup>
import { computed, ref, onBeforeUnmount } from "vue"
import TinyEditor from "../../components/Editor"
import { useRoute, useRouter } from "vue-router"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import { useSecurityStore } from "../../store/securityStore"
import FloatLabel from "primevue/floatlabel"
import { useLocale } from "../../composables/locale"

const modelValue = defineModel({ type: String, required: true })

/* Reactive UI state */
const isFocused = ref(false)

/* Props */
const props = defineProps({
  editorId: { type: String, required: true },
  required: { type: Boolean, default: false },
  title: { type: String, default: "" },
  editorConfig: { type: Object, default: () => ({}) },
  helpText: { type: String, default: "" },
  // If true: use Chamilo file manager; if false: use system file picker.
  useFileManager: { type: Boolean, default: false },
  // When true, includes TinyMCE "fullpage" plugin/button.
  fullPage: { type: Boolean, default: true },
})

/* Derived UI flags */
const hasContent = computed(() => String(modelValue?.value ?? "").trim().length > 0)

/* Routing / stores */
const router = useRouter()
const route = useRoute()
const parentResourceNodeId = ref(0)

const securityStore = useSecurityStore()
const cidReqStore = useCidReqStore()
const { course } = storeToRefs(cidReqStore)

/**
 * Determine the best resource node to browse.
 * Prefer course node when available (Documents use case), fallback to user node.
 */
function resolveParentNodeId() {
  const courseNodeId = Number(course.value?.resourceNode?.id || 0)
  const userNodeId = Number(securityStore.user?.resourceNode?.id || 0)

  // Route override (kept): if URL has a node param, respect it
  const routeNode = route?.params?.node ? Number(route.params.node) : 0

  return routeNode || courseNodeId || userNodeId || 0
}
parentResourceNodeId.value = resolveParentNodeId()

/* Language resolution */
const supportedLanguages = {
  ar: "ar.js",
  de: "de.js",
  en: "en.js",
  es: "es.js",
  fr_FR: "fr_FR.js",
  it: "it.js",
  nl: "nl.js",
  pt_PT: "pt_PT.js",
  ru: "ru.js",
  zh_CN: "zh_CN.js",
}
const { appLocale } = useLocale()

function getLanguageConfig(locale) {
  const defaultLang = "en"
  const url = "/libs/editor/langs/"
  const iso = String(locale || "").split("_")[0] || defaultLang
  let file = supportedLanguages[iso]
  let lang = iso

  if (!file) {
    const regional = Object.entries(supportedLanguages).find(([key]) => key.startsWith(iso))
    if (regional) {
      file = regional[1]
      lang = regional[0]
    } else {
      file = `${defaultLang}.js`
      lang = defaultLang
    }
  }
  return { language: lang, language_url: `${url}${file}` }
}
const languageConfig = getLanguageConfig(appLocale.value)

/* Pull base from global config file (tiny-settings.js) */
const base = (typeof window !== "undefined" ? window.CHAMILO_TINYMCE_BASE_CONFIG : {}) || {}

/* Compose default editor config: use base and add Chamilo-specific bits */
const defaultEditorConfig = {
  ...base,
  skin: false,
  branding: false,
  relative_urls: false,
  height: base.height ?? 500,
  toolbar_mode: base.toolbar_mode ?? "sliding",
  autosave_ask_before_unload: true,
  content_css: Array.isArray(base.content_css)
    ? [...base.content_css, "/build/css/editor_content.css"]
    : ["/build/css/editor_content.css"],
  language: languageConfig.language,
  language_url: languageConfig.language_url,
  // Keep a wrapper class inside content for consistent styling in Chamilo
  content_style: (base.content_style ?? "") + " .tiny-content { font-family: Arial, Helvetica, sans-serif; }",
  body_class: "tiny-content",
}

/* Add fullPage when requested (merge with base plugins/toolbar) */
if (props.fullPage) {
  const basePlugins = String(base.plugins || "")
    .split(/\s+/)
    .filter(Boolean)
  const mergedPlugins = Array.from(new Set([...basePlugins, "fullpage"]))
  defaultEditorConfig.plugins = mergedPlugins.join(" ")
  defaultEditorConfig.toolbar = (base.toolbar ? base.toolbar + " | " : "") + "fullpage"
}

/**
 * Decide whether we should use the Chamilo file manager.
 * - If the caller explicitly sets useFileManager=true, always use it.
 * - If not set, we still try to use the file manager when a node id exists,
 *   because picking existing media is a common use case.
 * - If we cannot build a manager URL, we fallback to the native picker.
 */
const effectiveUseFileManager = computed(() => {
  if (props.useFileManager === true) return true
  return Number(parentResourceNodeId.value || 0) > 0
})

/* Final config: merge base+local via builder to preserve both setup() handlers */
const editorConfig = computed(() => {
  const builder = typeof window !== "undefined" ? window.buildTinyMceConfig : null

  // Respect a custom file_picker_callback if the caller provided one.
  const callerHasPicker =
    props.editorConfig?.file_picker_callback && typeof props.editorConfig.file_picker_callback === "function"

  const local = {
    ...defaultEditorConfig,
    ...props.editorConfig,

    // Ensure TinyMCE will call the picker for these types.
    file_picker_types: props.editorConfig?.file_picker_types || "file image media",

    ...(callerHasPicker
      ? {}
      : {
          file_picker_callback: filePickerCallback,
        }),

    setup(editor) {
      editor.on("focus", () => {
        isFocused.value = true
      })
      editor.on("blur", () => {
        isFocused.value = false
      })
      editor.on("GetContent", (e) => {
        const html = String(e?.content ?? "")
        if (!html.trim()) return

        const hasWrapper = /^\s*<div[^>]+class=["'][^"']*\btiny-content\b[^"']*["'][^>]*>/i.test(html)
        if (!hasWrapper) {
          e.content = `<div class="tiny-content">${html}</div>`
        }
      })
      // Preserve caller's setup if provided
      if (props.editorConfig?.setup && typeof props.editorConfig.setup === "function") {
        props.editorConfig.setup(editor)
      }
    },
  }
  return builder ? builder(local) : local
})

/* ---------- Picker helpers ---------- */

let activeMessageHandler = null

function removeActiveMessageHandler() {
  if (activeMessageHandler) {
    window.removeEventListener("message", activeMessageHandler)
    activeMessageHandler = null
  }
}

/**
 * Native fallback picker.
 */
function openNativePicker(callback, meta) {
  const input = document.createElement("input")
  input.type = "file"

  // TinyMCE meta.filetype: "image" | "media" | "file"
  if (meta?.filetype === "image") input.accept = "image/*"
  else if (meta?.filetype === "media") input.accept = "video/*,audio/*"
  else input.accept = "*/*"

  input.onchange = () => {
    const file = input.files?.[0]
    if (!file) return

    const reader = new FileReader()
    reader.onload = () => callback(reader.result)
    reader.readAsDataURL(file)
  }

  input.click()
}

function createCbId() {
  try {
    return crypto.randomUUID()
  } catch {
    return `cb_${Date.now()}_${Math.random().toString(16).slice(2)}`
  }
}

function registerTinyPickerCallback(cbId, cb) {
  // Keep registry in English for easier debugging
  window.__chamiloTinyPickerCallbacks = window.__chamiloTinyPickerCallbacks || {}
  window.__chamiloTinyPickerCallbacks[cbId] = cb
}

function unregisterTinyPickerCallback(cbId) {
  if (window.__chamiloTinyPickerCallbacks && window.__chamiloTinyPickerCallbacks[cbId]) {
    delete window.__chamiloTinyPickerCallbacks[cbId]
  }
}

function appendParams(rawUrl, params) {
  const u = new URL(rawUrl, window.location.origin)
  Object.entries(params).forEach(([k, v]) => u.searchParams.set(k, String(v)))
  return u.toString()
}

/**
 * Build a URL for the Chamilo manager.
 * Prefer Documents HTML editor picker route; fallback to FileManagerList.
 */
function buildManagerUrl(meta) {
  // TinyMCE meta.filetype: "image" | "media" | "file"
  const ft = String(meta?.filetype || "file").toLowerCase()

  let type = "files"
  if (ft === "image") type = "images"
  else if (ft === "media") type = "media"

  // 1) Preferred: Documents HTML editor picker route (if exists)
  try {
    if (typeof router.hasRoute === "function" && router.hasRoute("DocumentForHtmlEditor")) {
      const nodeIdFromRoute =
        Number(route?.params?.node || 0) || Number(route?.params?.id || 0) || Number(parentResourceNodeId.value || 0)

      const resolved = router.resolve({
        name: "DocumentForHtmlEditor",
        params: { id: nodeIdFromRoute },
        query: { ...route.query },
      })

      const sep = resolved.href.includes("?") ? "&" : "?"
      return `${resolved.href}${sep}type=${encodeURIComponent(type)}&picker=tinymce`
    }
  } catch {
    // Not fatal, fallback below
  }

  // 2) Fallback: FileManagerList
  try {
    const hasCourse = Boolean(course.value?.id)
    const resolved = router.resolve({
      name: "FileManagerList",
      params: { node: Number(parentResourceNodeId.value || 0) },
      query: hasCourse
        ? { cid: course.value.id, sid: 0, gid: 0, type, picker: "tinymce" }
        : { loadNode: 1, type, picker: "tinymce" },
    })
    return resolved.href
  } catch {
    // Ignore
  }

  return ""
}

/**
 * File picker callback for TinyMCE.
 * Uses Chamilo manager when possible; otherwise falls back to native picker.
 */
async function filePickerCallback(callback, _value, meta) {
  if (!effectiveUseFileManager.value) {
    openNativePicker(callback, meta)
    return
  }

  const baseUrl = buildManagerUrl(meta)
  if (!baseUrl) {
    openNativePicker(callback, meta)
    return
  }

  // Clean previous listeners (avoid duplicate callbacks).
  removeActiveMessageHandler()

  // Register a direct callback (most reliable way to fill plugin inputs).
  const cbId = createCbId()
  registerTinyPickerCallback(cbId, (pickedUrl) => {
    try {
      callback(pickedUrl)
    } finally {
      unregisterTinyPickerCallback(cbId)
    }
  })

  const url = appendParams(baseUrl, { cbId })

  // Bridge for postMessage (fallback compatibility).
  const expectedOrigin = window.location.origin
  activeMessageHandler = (event) => {
    try {
      if (!event || event.origin !== expectedOrigin) return
      const data = event.data

      if (data?.mceAction === "fileSelected" && data?.content?.url) {
        callback(data.content.url)
        unregisterTinyPickerCallback(cbId)
        removeActiveMessageHandler()
        return
      }

      if (data?.url) {
        callback(data.url)
        unregisterTinyPickerCallback(cbId)
        removeActiveMessageHandler()
      }
    } catch {
      // Ignore
    }
  }
  window.addEventListener("message", activeMessageHandler)

  try {
    window.tinymce?.activeEditor?.windowManager.openUrl({
      url,
      title: "File Manager",
      onMessage: (api, message) => {
        const picked = message?.content?.url || message?.url || message?.data?.url

        if (picked) {
          callback(picked)
          unregisterTinyPickerCallback(cbId)
          removeActiveMessageHandler()
          api.close()
        }
      },
      onClose: () => {
        unregisterTinyPickerCallback(cbId)
        removeActiveMessageHandler()
      },
    })
  } catch {
    unregisterTinyPickerCallback(cbId)
    removeActiveMessageHandler()
    openNativePicker(callback, meta)
  }
}

onBeforeUnmount(() => {
  removeActiveMessageHandler()
})
</script>
<style scoped>
.input-has-content label,
.input-has-focus label {
  opacity: 0;
  visibility: hidden;
  transform: translateY(-1rem);
  transition:
    opacity 0.2s,
    visibility 0.2s,
    transform 0.2s;
}
</style>
