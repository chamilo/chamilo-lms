<template>
  <div
    v-if="isEnabled"
    ref="rootRef"
    class="relative inline-flex items-center"
  >
    <button
      type="button"
      class="item-button group relative"
      :aria-expanded="isMenuOpen"
      :aria-label="t('Font resize accessibility feature')"
      :title="t('Font resize accessibility feature')"
      aria-haspopup="menu"
      @click.stop="toggleMenu"
    >
      <span
        class="item-button__icon mdi mdi-format-size text-[1.6rem] leading-none transition-transform duration-200 group-hover:scale-110"
        aria-hidden="true"
      />
      <span
        v-if="0 !== fontSizeStep"
        class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold leading-none text-white"
        aria-hidden="true"
      >
        {{ currentStepLabel }}
      </span>
    </button>

    <div
      v-if="isMenuOpen"
      class="absolute right-0 top-full z-50 mt-2 w-44 rounded-2xl border border-gray-200 bg-white p-2 shadow-lg"
      role="menu"
      @click.stop
    >
      <div class="mb-2 flex items-center gap-2 px-2 text-sm font-semibold text-gray-700">
        <span
          class="mdi mdi-format-size text-lg text-primary"
          aria-hidden="true"
        />
        <span v-text="t('Font resize accessibility feature')" />
      </div>

      <div class="grid grid-cols-3 gap-1">
        <button
          type="button"
          class="rounded-xl px-2 py-2 text-sm font-bold transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
          :class="{ 'cursor-not-allowed opacity-40': decreaseDisabled }"
          :disabled="decreaseDisabled"
          :aria-label="t('Decrease the font size')"
          :title="t('Decrease the font size')"
          role="menuitem"
          @click="decreaseFontSize"
        >
          A−
        </button>

        <button
          type="button"
          class="rounded-xl px-2 py-2 text-base font-bold transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
          :class="{ 'bg-primary text-white hover:bg-primary': 0 === fontSizeStep }"
          :aria-label="t('Reset the font size')"
          :title="t('Reset the font size')"
          role="menuitem"
          @click="resetFontSize"
        >
          A
        </button>

        <button
          type="button"
          class="rounded-xl px-2 py-2 text-lg font-bold transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
          :class="{ 'cursor-not-allowed opacity-40': increaseDisabled }"
          :disabled="increaseDisabled"
          :aria-label="t('Increase the font size')"
          :title="t('Increase the font size')"
          role="menuitem"
          @click="increaseFontSize"
        >
          A+
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()

const storageKey = "chamiloFontResizeStep"
const minStep = -2
const maxStep = 4
const stepPercent = 10

const rootRef = ref(null)
const fontSizeStep = ref(0)
const isMenuOpen = ref(false)

const settingValue = computed(() => platformConfigStore.getSetting("display.accessibility_font_resize"))

const isEnabled = computed(() => {
  return true === settingValue.value || "true" === settingValue.value
})

const decreaseDisabled = computed(() => fontSizeStep.value <= minStep)
const increaseDisabled = computed(() => fontSizeStep.value >= maxStep)

const currentStepLabel = computed(() => {
  if (fontSizeStep.value > 0) {
    return `+${fontSizeStep.value}`
  }

  return String(fontSizeStep.value)
})

watch(
  settingValue,
  (value) => {
    if (null === value) {
      return
    }

    if (true === value || "true" === value) {
      applyFontSize()
      return
    }

    resetDocumentFontSize()
  },
  { immediate: true },
)

watch(fontSizeStep, () => {
  if (!isEnabled.value) {
    return
  }

  applyFontSize()
})

onMounted(() => {
  const savedStep = Number.parseInt(window.localStorage.getItem(storageKey) || "0", 10)

  if (!Number.isNaN(savedStep)) {
    fontSizeStep.value = clampStep(savedStep)
  }

  if (isEnabled.value) {
    applyFontSize()
  }

  document.addEventListener("click", closeOnOutsideClick)
  document.addEventListener("keydown", closeOnEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener("click", closeOnOutsideClick)
  document.removeEventListener("keydown", closeOnEscape)
})

function toggleMenu() {
  isMenuOpen.value = !isMenuOpen.value
}

function closeOnOutsideClick(event) {
  if (!isMenuOpen.value || !rootRef.value) {
    return
  }

  if (event.target instanceof Node && rootRef.value.contains(event.target)) {
    return
  }

  isMenuOpen.value = false
}

function closeOnEscape(event) {
  if ("Escape" !== event.key) {
    return
  }

  isMenuOpen.value = false
}

function decreaseFontSize() {
  fontSizeStep.value = clampStep(fontSizeStep.value - 1)
}

function increaseFontSize() {
  fontSizeStep.value = clampStep(fontSizeStep.value + 1)
}

function resetFontSize() {
  fontSizeStep.value = 0
}

function applyFontSize() {
  const percentage = 100 + fontSizeStep.value * stepPercent

  if (0 === fontSizeStep.value) {
    resetDocumentFontSize()
    return
  }

  document.documentElement.style.fontSize = `${percentage}%`
  window.localStorage.setItem(storageKey, String(fontSizeStep.value))
}

function resetDocumentFontSize() {
  document.documentElement.style.fontSize = ""
  window.localStorage.setItem(storageKey, "0")
}

function clampStep(step) {
  if (step < minStep) {
    return minStep
  }

  if (step > maxStep) {
    return maxStep
  }

  return step
}
</script>
