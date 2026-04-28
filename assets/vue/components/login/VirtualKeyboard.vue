<template>
  <section
    class="rounded-lg border border-gray-25 bg-white p-4 shadow-sm"
    :aria-label="t('Virtual keyboard')"
  >
    <div class="mb-3 flex items-center justify-between gap-2">
      <span class="text-sm font-semibold text-gray-90">
        {{ t("Virtual keyboard") }}
      </span>

      <button
        type="button"
        class="rounded border border-gray-25 px-3 py-1.5 text-sm text-gray-90 hover:bg-gray-15"
        @click="toggleUppercase"
        @mousedown.prevent
      >
        {{ isUppercase ? "ABC" : "abc" }}
      </button>
    </div>

    <div class="flex flex-col gap-2">
      <div
        v-for="(row, rowIndex) in visibleRows"
        :key="rowIndex"
        class="flex justify-center gap-2"
      >
        <button
          v-for="key in row"
          :key="key.value"
          type="button"
          class="min-h-10 min-w-10 rounded border border-gray-25 bg-gray-10 px-3 py-2 text-center text-sm font-medium text-gray-90 hover:bg-gray-15 focus:outline-none focus:ring-2 focus:ring-primary"
          :aria-label="key.label"
          @click="pressKey(key.value)"
          @mousedown.prevent
        >
          {{ key.label }}
        </button>
      </div>

      <div class="flex justify-center gap-2">
        <button
          type="button"
          class="min-h-10 rounded border border-gray-25 bg-gray-10 px-4 py-2 text-sm font-medium text-gray-90 hover:bg-gray-15 focus:outline-none focus:ring-2 focus:ring-primary"
          :aria-label="t('Backspace')"
          @click="pressKey('backspace')"
          @mousedown.prevent
        >
          ⌫
        </button>

        <button
          type="button"
          class="min-h-10 min-w-32 rounded border border-gray-25 bg-gray-10 px-4 py-2 text-sm font-medium text-gray-90 hover:bg-gray-15 focus:outline-none focus:ring-2 focus:ring-primary"
          :aria-label="t('Space')"
          @click="pressKey('space')"
          @mousedown.prevent
        >
          {{ t("Space") }}
        </button>

        <button
          type="button"
          class="min-h-10 rounded border border-gray-25 bg-gray-10 px-4 py-2 text-sm font-medium text-gray-90 hover:bg-gray-15 focus:outline-none focus:ring-2 focus:ring-primary"
          :aria-label="t('Clear')"
          @click="pressKey('clear')"
          @mousedown.prevent
        >
          {{ t("Clear") }}
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"

const emit = defineEmits(["key-press"])

const { t } = useI18n()

const isUppercase = ref(false)

const rows = [
  ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"],
  ["q", "w", "e", "r", "t", "y", "u", "i", "o", "p"],
  ["a", "s", "d", "f", "g", "h", "j", "k", "l"],
  ["z", "x", "c", "v", "b", "n", "m"],
  ["@", ".", "-", "_"],
]

const visibleRows = computed(() => {
  return rows.map((row) => {
    return row.map((key) => {
      const value = shouldApplyUppercase(key) && isUppercase.value ? key.toUpperCase() : key

      return {
        value,
        label: value,
      }
    })
  })
})

function shouldApplyUppercase(key) {
  return /^[a-z]$/.test(key)
}

function toggleUppercase() {
  isUppercase.value = !isUppercase.value
}

function pressKey(key) {
  emit("key-press", key)
}
</script>
