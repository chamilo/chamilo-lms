<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue'

const props = defineProps({
  dropdownId: { type: [String, Number], required: true }
})

const emit = defineEmits(['open', 'close'])

// Used to track if the dropdown is open
const isOpen = ref(false)

function toggleMenu(event) {

  // Close all the dropdowns
  closeAllOtherDropdowns(event.target)
  
  isOpen.value = !isOpen.value
  
  if (isOpen.value) {
    emit('open', props.dropdownId)
  } else {
    emit('close')
  }
}

function closeMenu() {
  if (isOpen.value) {
    isOpen.value = false
    emit('close')
  }
}

function closeAllOtherDropdowns(clickedElement) {
  const allDropdowns = document.querySelectorAll('.dropdown-menu')

  allDropdowns.forEach(dropdown => {
    if (!dropdown.contains(clickedElement)) {
      dropdown.dispatchEvent(new CustomEvent('close-dropdown'))
    }
  })
}

function handleCloseDropdown() {
  closeMenu()
}

function handleClickOutside(e) {
  if (isOpen.value && !e.target.closest('.dropdown-menu')) {
    closeMenu()
  }
}

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
  const dropdownElement = document.querySelector(`[data-dropdown-id="${props.dropdownId}"]`)
  if (dropdownElement) {
    dropdownElement.addEventListener('close-dropdown', handleCloseDropdown)
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside)
  const dropdownElement = document.querySelector(`[data-dropdown-id="${props.dropdownId}"]`)
  if (dropdownElement) {
    dropdownElement.removeEventListener('close-dropdown', handleCloseDropdown)
  }
})
</script>

<template>
  <div 
    class="dropdown-menu absolute right-0" 
    style="position:relative;"
    :data-dropdown-id="dropdownId"
  >
    <span @click="toggleMenu">
      <slot name="button">
        
      </slot>
    </span>
    <div v-if="isOpen" class="menu-content absolute right-0 mt-2 top-full ">
      <slot name="menu" />
    </div>
  </div>
</template>