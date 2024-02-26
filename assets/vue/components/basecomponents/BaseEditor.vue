<template>
  <div class="base-editor">
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
const props = defineProps({
  editorId: String,
  modelValue: String,
  required: Boolean,
  editorConfig: Object,
  title: String,
  helpText: String
})
const emit = defineEmits(['update:modelValue'])
const updateValue = (value) => {
  emit('update:modelValue', value)
  document.getElementById(props.editorId).value = value
}
const defaultEditorConfig = {
  skin_url: '/build/libs/tinymce/skins/ui/oxide',
  content_css: '/build/libs/tinymce/skins/content/default/content.css',
  branding: false,
  relative_urls: false,
  height: 280,
  toolbar_mode: 'sliding',
  file_picker_callback: browser,
  autosave_ask_before_unload: true,
  plugins: [
    'fullpage advlist autolink lists link image charmap print preview anchor',
    'searchreplace visualblocks code fullscreen',
    'insertdatetime media table paste wordcount emoticons',
  ],
  toolbar: 'undo redo | bold italic underline strikethrough | ...',
}
const editorConfig = computed(() => ({
  ...defaultEditorConfig,
  ...props.editorConfig
}))
function browser(callback, value, meta) {
}
</script>
