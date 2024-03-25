<template>
  <div>
    <h1>Editor Demo</h1>
    <BaseTinyEditor
      editor-id="demoEditor"
      v-model="editorContent"
      :mode="editorMode"
      :use-file-manager="useFileManager"
      title="Demo Editor"
      help-text="Edit your content here in demo mode."
    />
  </div>
</template>

<script setup>
import { ref } from "vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import { TINYEDITOR_MODE_PERSONAL_FILES } from "../../components/basecomponents/TinyEditorOptions"

const editorContent = ref("")
// Here you decide the mode, 'personal_files' or 'documents'
const editorMode = ref(TINYEDITOR_MODE_PERSONAL_FILES)
// Decide if you want to use the file manager or not
const useFileManager = ref(true)
</script>
