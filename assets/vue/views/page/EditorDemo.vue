<template>
  <div>
    <h1>Editor Demo</h1>
    <BaseTinyEditor
      v-model="editorContent"
      :use-file-manager="useFileManager"
      editor-id="demoEditor"
      help-text="Edit your content here in demo mode."
      title="Demo Editor"
    />
  </div>
</template>

<script setup>
import { ref } from "vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"

const editorContent = ref("")
// Here you decide the mode, 'personal_files' or 'documents'
// Decide if you want to use the file manager or not
const useFileManager = ref(true)
</script>
