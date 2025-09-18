<template>
  <div class="field">
    <FloatLabel
      variant="on"
      :class="{
        'input-has-content': hasContent,
        'input-has-focus': isFocused
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
      >{{ title }}</label>
    </FloatLabel>
    <small
      v-if="helpText"
      v-text="helpText"
    />
  </div>
</template>

<script setup>
import { computed, ref } from "vue"
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

/* Determine parent node: prefer user's node; allow route override */
parentResourceNodeId.value = securityStore.user?.resourceNode?.id ?? 0
if (route.params.node) {
  parentResourceNodeId.value = Number(route.params.node)
}

/* Language resolution */
const supportedLanguages = {
  ar: "ar.js", de: "de.js", en: "en.js", es: "es.js", fr_FR: "fr_FR.js",
  it: "it.js", nl: "nl.js", pt_PT: "pt_PT.js", ru: "ru.js", zh_CN: "zh_CN.js",
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
  const basePlugins = String(base.plugins || "").split(/\s+/).filter(Boolean)
  const mergedPlugins = Array.from(new Set([...basePlugins, "fullpage"]))
  defaultEditorConfig.plugins = mergedPlugins.join(" ")
  defaultEditorConfig.toolbar = (base.toolbar ? base.toolbar + " | " : "") + "fullpage"
}

/* Final config: merge base+local via builder to preserve both setup() handlers */
const editorConfig = computed(() => {
  const builder = typeof window !== "undefined" ? window.buildTinyMceConfig : null
  const local = {
    ...defaultEditorConfig,
    ...props.editorConfig,
    file_picker_callback: filePickerCallback,
    setup(editor) {
      editor.on("focus", () => { isFocused.value = true })
      editor.on("blur", () => { isFocused.value = false })
      editor.on("GetContent", (e) => {
        if (!e.content.includes("tiny-content")) {
          e.content = `<div class="tiny-content">${e.content}</div>`
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

/* File picker: Chamilo file manager (when enabled) or system picker fallback */
async function filePickerCallback(callback, _value, meta) {
  // If system picker requested, use a lightweight native file input for images/files
  if (!props.useFileManager) {
    const input = document.createElement("input")
    input.type = "file"
    input.accept = meta.filetype === "image" ? "image/*" : "*/*"
    input.onchange = () => {
      const file = input.files?.[0]
      if (!file) return
      const reader = new FileReader()
      reader.onload = () => callback(reader.result)
      reader.readAsDataURL(file)
    }
    input.click()
    return
  }

  // Chamilo inner file manager
  let url = getUrlForTinyEditor()
  url += meta.filetype === "image" ? "&type=images" : "&type=files"

  // Bridge for postMessage from file manager
  const onMessage = (event) => {
    const data = event.data
    if (data?.url) {
      callback(data.url)
      window.removeEventListener("message", onMessage)
    }
  }
  window.addEventListener("message", onMessage)

  // Open file manager in TinyMCE window
  window.tinymce?.activeEditor?.windowManager.openUrl({
    url,
    title: "File Manager",
    onMessage: (api, message) => {
      if (message?.mceAction === "fileSelected") {
        callback(message.content.url)
        api.close()
      }
    },
  })
}

/* Build file manager URL (course-aware) */
function getUrlForTinyEditor() {
  if (!course.value) {
    return router.resolve({
      name: "FileManagerList",
      params: { node: parentResourceNodeId.value },
    }).href
  }
  return router.resolve({
    name: "FileManagerList",
    params: { node: parentResourceNodeId.value },
    query: { cid: course.value.id, sid: 0, gid: 0, filetype: "file" },
  }).href
}
</script>
<style scoped>
.input-has-content label,
.input-has-focus label {
  opacity: 0;
  visibility: hidden;
  transform: translateY(-1rem);
  transition: opacity 0.2s, visibility 0.2s, transform 0.2s;
}
</style>
