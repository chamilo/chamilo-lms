<template>
  <div
    ref="container"
    class="exercise-runtime-html text-gray-800"
    v-html="renderedHtml"
  />
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useTranslatedHtml } from "../../../composables/useTranslatedHtml"

const { t } = useI18n()
const { displayTranslatedHtml } = useTranslatedHtml()

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
  questionId: {
    type: Number,
    required: true,
  },
  segments: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(["update:modelValue"])

const container = ref(null)

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;")
}

const renderedHtml = computed(() =>
  props.segments
    .map((segment) => {
      if (segment?.type === "text") {
        return displayTranslatedHtml(String(segment.text || ""))
      }

      const position = Math.max(1, Number(segment?.position || 1))

      if (Array.isArray(segment?.options) && segment.options.length) {
        const optionsHtml = segment.options
          .map((option) => `<option value="${escapeHtml(option)}">${escapeHtml(option)}</option>`)
          .join("")

        return `<select class="mx-1 inline-block rounded border border-gray-30 px-2 py-1 text-sm" data-fill-blank-position="${position}" name="question_${props.questionId}_blank_${position}"><option value="">-- ${escapeHtml(t("Select an option"))} --</option>${optionsHtml}</select>`
      }

      const width = Math.min(Math.max(Number(segment?.inputSize || 160), 80), 320)

      return `<input autocomplete="off" class="mx-1 inline-block rounded border border-gray-30 px-2 py-1 text-sm" data-fill-blank-position="${position}" name="question_${props.questionId}_blank_${position}" style="width: ${width}px" type="text">`
    })
    .join(""),
)

function hydrateInputs() {
  const root = container.value
  if (!(root instanceof HTMLElement)) {
    return
  }

  root.querySelectorAll("input[data-fill-blank-position], select[data-fill-blank-position]").forEach((field) => {
    const position = Math.max(1, Number(field.dataset.fillBlankPosition || 1))
    const value = String(props.modelValue?.[position] ?? "")

    if (field.value !== value) {
      field.value = value
    }

    const eventName = field instanceof HTMLSelectElement ? "onchange" : "oninput"
    field[eventName] = (event) => {
      const target = event.target
      if (!(target instanceof HTMLInputElement) && !(target instanceof HTMLSelectElement)) {
        return
      }

      emit("update:modelValue", {
        ...(props.modelValue || {}),
        [position]: target.value,
      })
    }
  })
}

watch(renderedHtml, async () => {
  await nextTick()
  hydrateInputs()
})

watch(
  () => props.modelValue,
  async () => {
    await nextTick()
    hydrateInputs()
  },
  { deep: true },
)

onMounted(hydrateInputs)
</script>
