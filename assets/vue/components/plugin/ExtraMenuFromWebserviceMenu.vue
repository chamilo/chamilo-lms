<template>
  <div
    v-if="items.length"
    ref="menuRoot"
    class="relative"
  >
    <button
      type="button"
      class="item-button group text-primary hover:text-primary-hover"
      :aria-expanded="isOpen ? 'true' : 'false'"
      :aria-label="title"
      :title="title"
      @click.stop="toggle"
    >
      <span
        class="item-button__icon mdi mdi-menu-open text-[1.8rem] leading-none transition-transform duration-200 group-hover:scale-110"
        aria-hidden="true"
      />
    </button>

    <div
      v-if="isOpen"
      class="absolute left-0 top-full z-50 mt-2 w-72 overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-xl"
    >
      <div class="border-b border-gray-25 bg-support-2 px-4 py-3">
        <p class="m-0 text-body-2 font-semibold text-gray-90">
          {{ title }}
        </p>
      </div>

      <nav class="max-h-[70vh] overflow-y-auto p-2">
        <template
          v-for="item in items"
          :key="`${item.title}-${item.url}`"
        >
          <a
            :href="item.url"
            :target="item.target"
            :rel="item.target === '_blank' ? 'noopener noreferrer' : null"
            class="flex items-center gap-3 rounded-xl px-3 py-2 text-body-2 text-gray-90 transition-colors hover:bg-support-1 hover:text-primary"
            @click="close"
          >
            <span
              :class="['mdi', item.icon || 'mdi-menu-right', 'text-primary']"
              aria-hidden="true"
            />
            <span class="min-w-0 flex-1 truncate">{{ item.title }}</span>
            <span
              v-if="item.target === '_blank'"
              class="mdi mdi-open-in-new text-gray-50"
              aria-hidden="true"
            />
          </a>

          <div
            v-if="item.children?.length"
            class="ml-5 border-l border-gray-25 pl-2"
          >
            <a
              v-for="child in item.children"
              :key="`${item.title}-${child.title}-${child.url}`"
              :href="child.url"
              :target="child.target"
              :rel="child.target === '_blank' ? 'noopener noreferrer' : null"
              class="flex items-center gap-3 rounded-xl px-3 py-2 text-body-2 text-gray-50 transition-colors hover:bg-support-1 hover:text-primary"
              @click="close"
            >
              <span
                :class="['mdi', child.icon || 'mdi-menu-right', 'text-primary']"
                aria-hidden="true"
              />
              <span class="min-w-0 flex-1 truncate">{{ child.title }}</span>
              <span
                v-if="child.target === '_blank'"
                class="mdi mdi-open-in-new text-gray-50"
                aria-hidden="true"
              />
            </a>
          </div>
        </template>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue"
import baseService from "../../services/baseService"

const endpoint = "/plugin/ExtraMenuFromWebservice/menu.php"

const isOpen = ref(false)
const items = ref([])
const title = ref("Extra menu")
const menuRoot = ref(null)

function close() {
  isOpen.value = false
}

function toggle() {
  isOpen.value = !isOpen.value
}

function handleDocumentClick(event) {
  if (!menuRoot.value) {
    return
  }

  if (!menuRoot.value.contains(event.target)) {
    close()
  }
}

function normalizeItems(value) {
  if (!Array.isArray(value)) {
    return []
  }

  return value.filter((item) => {
    return item && typeof item.title === "string" && typeof item.url === "string"
  })
}

async function loadMenu() {
  try {
    const data = await baseService.get(endpoint)

    title.value = typeof data.title === "string" && data.title.trim() ? data.title : title.value
    items.value = data.enabled ? normalizeItems(data.items) : []
  } catch (error) {
    items.value = []
  }
}

onMounted(() => {
  document.addEventListener("click", handleDocumentClick)
  loadMenu()
})

onBeforeUnmount(() => {
  document.removeEventListener("click", handleDocumentClick)
})
</script>
