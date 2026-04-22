<template>
  <div
    class="field"
    data-chamilo-editor="BaseTinyEditor"
  >
    <FloatLabel variant="on">
      <div
        :class="[
          'html-editor-container',
          { 'html-editor-container--filled': hasContent, 'html-editor-container--focused': isFocused },
        ]"
      >
        <TinyEditor
          :id="editorId"
          v-model="modelValue"
          :init="editorConfig"
          :required="required"
        />
      </div>
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
import { computed, onBeforeUnmount, ref } from "vue"
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
  const routeId = route?.params?.id ? Number(route.params.id) : 0

  return routeNode || routeId || courseNodeId || userNodeId || 0
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

/* ------------------------------------------------------------------ */
/* Responsive images support (TinyMCE image dialog)                    */
/* ------------------------------------------------------------------ */

const RESPONSIVE_IMAGE_CLASS = "ch-img-responsive"
const HOOK_GUARD_KEY = "__chamiloBaseTinyEditorHooksAttached"

function normalizeImageClassListItem(item) {
  if (!item) return null

  if (typeof item === "string") {
    const v = item.trim()
    if (!v) return null
    return { title: v, value: v }
  }

  if (typeof item === "object") {
    const title = String(item.title ?? "").trim()
    const value = String(item.value ?? "").trim()

    const fallbackTitle = String(item.text ?? item.name ?? "").trim()
    const fallbackValue = String(item.class ?? "").trim()

    const finalValue = value || fallbackValue
    const finalTitle = title || fallbackTitle || finalValue

    if (!finalValue && !finalTitle) return null

    return {
      title: finalTitle || finalValue,
      value: finalValue,
    }
  }

  return null
}

function buildImageClassList(baseList) {
  const list = Array.isArray(baseList) ? baseList : []
  const normalized = []

  for (const item of list) {
    const n = normalizeImageClassListItem(item)
    if (!n) continue

    const key = `${n.value}::${n.title}`
    if (normalized.some((x) => `${x.value}::${x.title}` === key)) continue

    normalized.push(n)
  }

  if (!normalized.some((i) => String(i.value) === "")) {
    normalized.unshift({ title: "None", value: "" })
  }

  if (!normalized.some((i) => String(i.value) === RESPONSIVE_IMAGE_CLASS)) {
    normalized.push({ title: "Responsive", value: RESPONSIVE_IMAGE_CLASS })
  }

  return normalized
}

function ensureTinyContentStyles(contentStyleRaw) {
  const contentStyle = String(contentStyleRaw ?? "")

  const baseWrapperRule = " .tiny-content { font-family: Arial, Helvetica, sans-serif; }"
  const responsiveRule = ` .tiny-content img.${RESPONSIVE_IMAGE_CLASS} { max-width: 100%; height: auto; }`

  let out = contentStyle

  if (!out.includes(" .tiny-content {") && !out.includes(".tiny-content{")) {
    out += baseWrapperRule
  }
  if (!out.includes(`img.${RESPONSIVE_IMAGE_CLASS}`)) {
    out += responsiveRule
  }

  return out
}

function ensureExtendedValidElements(raw) {
  const add = "img[class|style|src|alt|title|width|height]"
  const s = String(raw ?? "").trim()
  if (!s) return add
  if (s.includes("img[")) return s
  return `${s},${add}`
}

function applyResponsiveInlineStyles(htmlRaw) {
  const html = String(htmlRaw ?? "")
  if (!html.trim()) return html
  if (!html.includes(RESPONSIVE_IMAGE_CLASS)) return html

  try {
    const doc = new DOMParser().parseFromString(`<div id="__root">${html}</div>`, "text/html")
    const root = doc.getElementById("__root")
    if (!root) return html

    const imgs = root.querySelectorAll(`img.${RESPONSIVE_IMAGE_CLASS}`)
    imgs.forEach((img) => {
      const style = String(img.getAttribute("style") || "").trim()
      const hasMaxWidth = /max-width\s*:/i.test(style)
      const hasHeightAuto = /height\s*:\s*auto/i.test(style)

      if (hasMaxWidth && hasHeightAuto) return

      let next = style
      if (next && !next.endsWith(";")) next += ";"

      if (!hasMaxWidth) next += "max-width:100%;"
      if (!hasHeightAuto) next += "height:auto;"

      img.setAttribute("style", next)
    })

    return root.innerHTML
  } catch {
    return html
  }
}

function attachChamiloHooks(editor) {
  try {
    if (editor && editor[HOOK_GUARD_KEY]) return
    if (editor) editor[HOOK_GUARD_KEY] = true
  } catch {
    // Ignore
  }

  editor.on("init", () => {
    try {
      window.__chamiloTinyEditorLoaded = true
      window.__chamiloTinyEditorAppliedConfig = {
        image_advtab: editor?.settings?.image_advtab,
        image_class_list: editor?.settings?.image_class_list,
        extended_valid_elements: editor?.settings?.extended_valid_elements,
      }
    } catch {
      // Ignore
    }
  })

  editor.on("focus", () => {
    isFocused.value = true
  })
  editor.on("blur", () => {
    isFocused.value = false
  })

  editor.on("GetContent", (e) => {
    const html = String(e?.content ?? "")
    if (!html.trim()) return

    let out = html

    const hasWrapper = /^\s*<div[^>]+class=["'][^"']*\btiny-content\b[^"']*["'][^>]*>/i.test(out)
    if (!hasWrapper) {
      out = `<div class="tiny-content">${out}</div>`
    }

    out = applyResponsiveInlineStyles(out)
    e.content = out
  })
}

/* Compose default editor config */
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
  image_advtab: true,
  image_class_list: buildImageClassList(base.image_class_list),
  extended_valid_elements: ensureExtendedValidElements(base.extended_valid_elements),
  content_style: ensureTinyContentStyles(base.content_style ?? ""),
  body_class: "tiny-content",
}

if (props.fullPage) {
  const basePlugins = String(base.plugins || "")
    .split(/\s+/)
    .filter(Boolean)
  const mergedPlugins = Array.from(new Set([...basePlugins, "fullpage"]))
  defaultEditorConfig.plugins = mergedPlugins.join(" ")
  defaultEditorConfig.toolbar = (base.toolbar ? base.toolbar + " | " : "") + "fullpage"
}

const effectiveUseFileManager = computed(() => {
  if (props.useFileManager === true) return true
  return Number(parentResourceNodeId.value || 0) > 0
})

const editorConfig = computed(() => {
  const builder = typeof window !== "undefined" ? window.buildTinyMceConfig : null

  const callerConfig = props.editorConfig || {}
  const callerHasPicker = callerConfig?.file_picker_callback && typeof callerConfig.file_picker_callback === "function"

  const callerSetup = typeof callerConfig.setup === "function" ? callerConfig.setup : null
  const appendToolbar = String(callerConfig.appendToolbar || "").trim()

  const safeCallerConfig = { ...callerConfig }
  delete safeCallerConfig.setup
  delete safeCallerConfig.appendToolbar

  const local = {
    ...defaultEditorConfig,
    ...safeCallerConfig,
    file_picker_types: safeCallerConfig.file_picker_types || "file image media",
    ...(callerHasPicker
      ? {}
      : {
          file_picker_callback: filePickerCallback,
        }),
  }

  const built = builder ? builder(local) : local

  if (appendToolbar) {
    const currentToolbar = String(built.toolbar || "").trim()
    built.toolbar = currentToolbar ? `${currentToolbar} | ${appendToolbar}` : appendToolbar
  }

  built.image_advtab = built.image_advtab ?? true
  built.image_class_list = buildImageClassList(built.image_class_list)
  built.extended_valid_elements = ensureExtendedValidElements(built.extended_valid_elements)
  built.content_style = ensureTinyContentStyles(built.content_style)

  const prevSetup = built.setup
  built.setup = (editor) => {
    attachChamiloHooks(editor)

    if (typeof prevSetup === "function") {
      prevSetup(editor)
    }

    if (typeof callerSetup === "function") {
      callerSetup(editor)
    }
  }

  return built
})

/* ---------- Picker helpers ---------- */

let activeMessageHandler = null

function removeActiveMessageHandler() {
  if (activeMessageHandler) {
    window.removeEventListener("message", activeMessageHandler)
    activeMessageHandler = null
  }
}

function openNativePicker(callback, meta) {
  const input = document.createElement("input")
  input.type = "file"

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
  window.__chamiloTinyPickerCallbacks = window.__chamiloTinyPickerCallbacks || {}
  window.__chamiloTinyPickerCallbacks[cbId] = cb
}

function unregisterTinyPickerCallback(cbId) {
  if (window.__chamiloTinyPickerCallbacks && window.__chamiloTinyPickerCallbacks[cbId]) {
    delete window.__chamiloTinyPickerCallbacks[cbId]
  }
}

function appendParams(rawUrl, params) {
  const [path, existingQuery] = rawUrl.split("?")
  const sp = new URLSearchParams(existingQuery || "")
  Object.entries(params).forEach(([k, v]) => sp.set(k, String(v)))
  const qs = sp.toString()

  return qs ? `${path}?${qs}` : path
}

function buildManagerUrl(meta) {
  const ft = String(meta?.filetype || "file").toLowerCase()

  let type = "files"
  if (ft === "image") type = "images"
  else if (ft === "media") type = "media"

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

  removeActiveMessageHandler()

  const cbId = createCbId()
  registerTinyPickerCallback(cbId, (pickedUrl) => {
    try {
      callback(pickedUrl)
    } finally {
      unregisterTinyPickerCallback(cbId)
    }
  })

  const url = appendParams(baseUrl, { cbId })

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
