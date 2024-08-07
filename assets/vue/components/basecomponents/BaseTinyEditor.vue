<template>
  <div class="field">
    <FloatLabel>
      <TinyEditor
        :id="editorId"
        v-model="modelValue"
        :init="editorConfig"
        :required="required"
      />
      <label
        v-if="title"
        :for="editorId"
        v-text="title"
      />
    </FloatLabel>
    <small
      v-if="helpText"
      v-text="helpText"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import TinyEditor from "../../components/Editor"
import { useRoute, useRouter } from "vue-router"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import { useSecurityStore } from "../../store/securityStore"
import FloatLabel from "primevue/floatlabel"

const modelValue = defineModel({
  type: String,
  required: true,
})

const props = defineProps({
  editorId: {
    type: String,
    required: true,
  },
  required: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: "",
  },
  editorConfig: {
    type: Object,
    default: () => {},
  },
  // A helper text shown below editor
  helpText: {
    type: String,
    default: "",
  },
  // if true the Chamilo inner file manager will be shown
  // if false the system file picker will be shown
  useFileManager: {
    type: Boolean,
    default: false,
  },
  fullPage: {
    type: Boolean,
    required: false,
    default: true,
  },
})

const router = useRouter()
const route = useRoute()
const parentResourceNodeId = ref(0)

const securityStore = useSecurityStore()
const cidReqStore = useCidReqStore()

const { course } = storeToRefs(cidReqStore)

// Set the parent node ID based on the user's resource node ID or route parameter
parentResourceNodeId.value = securityStore.user.resourceNode.id
if (route.params.node) {
  parentResourceNodeId.value = Number(route.params.node)
}

const toolbarUndo = "undo redo"
const toolbarFormatText = "bold italic underline strikethrough"
const toolbarInsertMedia = "image media template link"
const toolbarFontConfig = "fontselect fontsizeselect formatselect"
const toolbarAlign = "alignleft aligncenter alignright alignjustify"
const toolbarIndent = "outdent indent"
const toolbarList = "numlist bullist"
const toolbarColor = "forecolor backcolor removeformat"
const toolbarPageBreak = "pagebreak"
const toolbarSpecialSymbols = "charmap emoticons"
const toolbarOther = "fullscreen preview save print"
const toolbarCode = "code codesample"
const toolbarTextDirection = "ltr rtl"

const defaultEditorConfig = {
  skin: false,
  content_css: ['/build/css/editor_content.css'],
  branding: false,
  relative_urls: false,
  height: 500,
  toolbar_mode: "sliding",
  autosave_ask_before_unload: true,
  plugins: [
    "advlist",
    "anchor",
    "autolink",
    "charmap",
    "code",
    "codesample",
    "directionality",
    "fullscreen",
    "emoticons",
    "image",
    "insertdatetime",
    "link",
    "lists",
    "media",
    "paste",
    "preview",
    "print",
    "pagebreak",
    "save",
    "searchreplace",
    "table",
    "template",
    "visualblocks",
    "wordcount",
  ],
  toolbar:
    toolbarUndo +
    " | " +
    toolbarFormatText +
    " | " +
    toolbarInsertMedia +
    " | " +
    toolbarFontConfig +
    " | " +
    toolbarAlign +
    " | " +
    toolbarIndent +
    " | " +
    toolbarList +
    " | " +
    toolbarColor +
    " | " +
    toolbarPageBreak +
    " | " +
    toolbarSpecialSymbols +
    " | " +
    toolbarOther +
    " | " +
    toolbarCode +
    " | " +
    toolbarTextDirection,
  content_style: ".tiny-content { font-family: Arial, sans-serif; }",
  body_class: 'tiny-content'
};

if (props.fullPage) {
  defaultEditorConfig.plugins.push("fullpage")
  defaultEditorConfig.toolbar += " | fullpage"
}

const editorConfig = computed(() => ({
  ...defaultEditorConfig,
  ...props.editorConfig,
}))

watch(modelValue, (newValue) => {
  if (newValue && !newValue.includes('tiny-content')) {
    modelValue.value = `<div class="tiny-content">${newValue}</div>`
  }
})

async function filePickerCallback(callback, value, meta) {
  let url = getUrlForTinyEditor()
  if ("image" === meta.filetype) {
    url += "&type=images"
  } else {
    url += "&type=files"
  }

  window.addEventListener("message", function (event) {
    let data = event.data
    if (data.url) {
      url = data.url
      callback(url)
    }
  })

  // tinymce is already in the global scope, set by backend and php
  window.tinymce.activeEditor.windowManager.openUrl({
    url: url,
    title: "File manager",
    onMessage: (api, message) => {
      if (message.mceAction === "fileSelected") {
        const fileUrl = message.content
        callback(fileUrl)
        api.close()
      }
    },
  })
}

function getUrlForTinyEditor() {
  if (!course.value) {
    return router.resolve({
      name: "FileManagerList",
      params: {
        node: parentResourceNodeId.value,
      },
    }).href
  }

  let nodeId = course.value.resourceNode ? course.value.resourceNode.id : null

  if (!nodeId) {
    console.error("Resource node ID is not available.")
    return
  }

  return router.resolve({
    name: "DocumentForHtmlEditor",
    params: {
      node: nodeId,
    },
    query: route.query,
  }).href
}
</script>
