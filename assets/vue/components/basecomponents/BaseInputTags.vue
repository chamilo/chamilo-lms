<template>
  <div class="border border-gray-300 p-1 rounded-md flex flex-wrap items-center">
    <div class="flex flex-wrap items-center p-1 flex-grow min-h-[38px] outline-none border-none">
      <div v-for="(tag, index) in tags" :key="index" class="bg-blue-500 text-white mr-1 mb-1 px-2.5 py-1 rounded-full flex items-center text-sm">
        {{ tag }}
        <span class="ml-2 cursor-pointer font-bold" @click.stop="removeTag(index)">&times;</span>
      </div>
      <input
        ref="tagInput"
        v-model="newTag"
        @keyup="checkInputKey"
        @keydown.delete="deleteLastTag"
        placeholder="Add a tag"
        class="flex-grow outline-none border-none p-0 m-0 text-sm"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const tags = ref([])
const newTag = ref('')
const tagInput = ref(null)

function focusInput() {
  tagInput.value.focus()
}

function addTag() {
  if (newTag.value.trim() && !tags.value.includes(newTag.value.trim())) {
    tags.value.push(newTag.value.trim())
    newTag.value = ''
  }
}

function removeTag(index) {
  tags.value.splice(index, 1)
}

function deleteLastTag(event) {
  if (newTag.value === '' && event.key === 'Backspace' && tags.value.length > 0) {
    tags.value.pop()
  }
}

function checkInputKey(event) {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault()
    addTag()
  }
}

onMounted(() => {
  focusInput()
})
</script>
