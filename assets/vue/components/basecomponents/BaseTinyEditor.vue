<template>
  <div class="base-tiny-editor">
    <label
      v-if="title"
      :for="editorId"
      >{{ title }}</label
    >
    <TinyEditor
      :id="editorId"
      :model-value="modelValue"
      :init="editorConfig"
      :required="required"
      @update:model-value="updateValue"
      @input="updateValue"
    />
    <p
      v-if="helpText"
      class="help-text"
    >
      {{ helpText }}
    </p>
  </div>
</template>

<script setup>
import { computed, ref } from "vue"
import TinyEditor from "@tinymce/tinymce-vue"
import { useRoute, useRouter } from "vue-router"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import { useStore } from "vuex"
import { TINYEDITOR_MODE_DOCUMENTS, TINYEDITOR_MODE_PERSONAL_FILES, TINYEDITOR_MODES } from "./TinyEditorOptions"

const props = defineProps({
  editorId: {
    type: String,
    required: true,
  },
  modelValue: {
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
  // change mode when useFileManager=True
  mode: {
    type: String,
    default: TINYEDITOR_MODE_PERSONAL_FILES,
    validator: (value) => TINYEDITOR_MODES.includes(value),
  },
})
const emit = defineEmits(["update:modelValue"])
const router = useRouter()
const route = useRoute()
const parentResourceNodeId = ref(0)

const store = useStore()
const user = computed(() => store.getters["security/getUser"])

// Set the parent node ID based on the user's resource node ID or route parameter
parentResourceNodeId.value = user.value.resourceNode["id"]
if (route.params.node) {
  parentResourceNodeId.value = Number(route.params.node)
}

const updateValue = (value) => {
  emit("update:modelValue", value)
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
  skin_url: "/build/libs/tinymce/skins/ui/oxide",
  content_css: "/build/libs/tinymce/skins/content/default/content.css",
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
    "fullpage",
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
  file_picker_callback: filePickerCallback,
}

const editorConfig = computed(() => ({
  ...defaultEditorConfig,
  ...props.editorConfig,
}))

async function filePickerCallback(callback, value, meta) {
  if (!props.useFileManager) {
    const input = document.createElement("input")
    input.setAttribute("type", "file")
    input.style.display = "none"
    input.onchange = inputFileHandler(callback, input)
    document.body.appendChild(input)
    input.click()
    return
  }

  let url = getUrlForTinyEditor(props.mode)
  if (meta.filetype === "image") {
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
  tinymce.activeEditor.windowManager.openUrl({
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

function inputFileHandler(callback, input) {
  return () => {
    const file = input.files[0]
    const title = file.name
    const comment = ""
    const fileType = "file"
    const resourceLinkList = []

    const formData = new FormData()
    formData.append("uploadFile", file)
    formData.append("title", title)
    formData.append("comment", comment)
    formData.append("parentResourceNodeId", parentResourceNodeId.value)
    formData.append("filetype", fileType)
    formData.append("resourceLinkList", resourceLinkList)

    try {
      let response = fetch("/file-manager/upload-image", {
        method: "POST",
        body: formData,
      })
      let data = response.json()
      if (data.location) {
        callback(data.location)
      } else {
        console.error("Failed to upload file")
      }
    } catch (error) {
      console.error("Error uploading file:", error)
    } finally {
      document.body.removeChild(input)
    }
  }
}

function getUrlForTinyEditor(mode) {
  if (props.mode === TINYEDITOR_MODE_PERSONAL_FILES) {
    return "/resources/filemanager/personal_list/" + parentResourceNodeId.value
  } else if (props.mode === TINYEDITOR_MODE_DOCUMENTS) {
    const cidReqStore = useCidReqStore()
    const { course } = storeToRefs(cidReqStore)

    let nodeId = course.value && course.value.resourceNode ? course.value.resourceNode.id : null
    if (!nodeId) {
      console.error("Resource node ID is not available.")
      return
    }

    return router.resolve({ name: "DocumentForHtmlEditor", params: { id: nodeId }, query: route.query }).href
  } else {
    console.error(`Mode "${mode}" is not valid. Check valid modes on TinyEditorOptions.js`)
  }
}
</script>
