<template>
  <div class="base-tiny-editor">
    <label v-if="title" :for="editorId">{{ title }}</label>
    <TinyEditor
      :id="editorId"
      :model-value="modelValue"
      :init="editorConfig"
      :required="required"
      @update:model-value="updateValue"
      @input="updateValue"
    />
    <p v-if="helpText" class="help-text">{{ helpText }}</p>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import TinyEditor from '@tinymce/tinymce-vue'
import { useRoute, useRouter } from "vue-router"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import { useStore } from "vuex"

const props = defineProps({
  editorId: String,
  modelValue: String,
  required: Boolean,
  editorConfig: Object,
  title: String,
  helpText: String,
  mode: { type: String, default: 'personal_files' },
  useFileManager: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue'])
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
  emit('update:modelValue', value)
}

const defaultEditorConfig = {
  skin_url: '/build/libs/tinymce/skins/ui/oxide',
  content_css: '/build/libs/tinymce/skins/content/default/content.css',
  branding: false,
  relative_urls: false,
  height: 280,
  toolbar_mode: 'sliding',
  autosave_ask_before_unload: true,
  plugins: [
    'advlist autolink lists link image charmap print preview anchor',
    'searchreplace visualblocks code fullscreen',
    'insertdatetime media table paste wordcount emoticons',
  ],
  toolbar: 'undo redo | bold italic underline strikethrough | ...',
  file_picker_callback: filePickerCallback
}

const editorConfig = computed(() => ({
  ...defaultEditorConfig,
  ...props.editorConfig
}))

function filePickerCallback(callback, value, meta) {
  if (!props.useFileManager) {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.style.display = 'none';

    input.onchange = () => {
      const file = input.files[0];
      const title = file.name;
      const comment = '';
      const fileType = 'file';
      const resourceLinkList = [];

      const formData = new FormData();
      formData.append('uploadFile', file);
      formData.append('title', title);
      formData.append('comment', comment);
      formData.append('parentResourceNodeId', parentResourceNodeId.value);
      formData.append('filetype', fileType);
      formData.append('resourceLinkList', resourceLinkList);

      fetch('/file-manager/upload-image', {
        method: 'POST',
        body: formData,
      })
        .then(response => response.json())
        .then(data => {
          if (data.location) {
            callback(data.location);
          } else {
            console.error('Failed to upload file');
          }
        })
        .catch(error => console.error('Error uploading file:', error))
        .finally(() => document.body.removeChild(input));
    };

    document.body.appendChild(input);
    input.click();
    return
  }

  let url;
  if (props.mode === 'personal_files') {
    url = '/resources/filemanager/personal_list/' + parentResourceNodeId.value;
  } else if (props.mode === 'documents') {
    const cidReqStore = useCidReqStore();
    const { course, session } = storeToRefs(cidReqStore);

    let nodeId = course.value && course.value.resourceNode ? course.value.resourceNode.id : null;

    if (!nodeId) {
      console.error('Resource node ID is not available.');
      return;
    }

    let folderParams = Object.entries(route.query).map(([key, value]) => `${key}=${value}`).join('&');
    url = router.resolve({ name: "DocumentForHtmlEditor", params: { id: nodeId }, query: route.query }).href;
  }

  if (meta.filetype === 'image') {
    url += "&type=images";
  } else {
    url += "&type=files";
  }

  window.addEventListener("message", function (event) {
    var data = event.data;
    if (data.url) {
      url = data.url;
      callback(url);
    }
  });

  tinymce.activeEditor.windowManager.openUrl({
    url: url,
    title: "File manager",
    onMessage: (api, message) => {
      if (message.mceAction === 'fileSelected') {
        const fileUrl = message.content;
        callback(fileUrl);
        api.close();
      }
    }
  });
}
</script>
