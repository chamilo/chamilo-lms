<template>
  <div
    ref="container"
    class="exercise-runtime-html text-gray-800"
    v-html="renderedHtml"
  />
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue"
import { useTranslatedHtml } from "../../../composables/useTranslatedHtml"

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

const renderedHtml = computed(() =>
  props.segments
    .map((segment) => {
      if (segment?.type === "text") {
        return displayTranslatedHtml(String(segment.text || ""))
      }

      const position = Math.max(1, Number(segment?.position || 1))
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

  root.querySelectorAll("input[data-fill-blank-position]").forEach((input) => {
    const position = Math.max(1, Number(input.dataset.fillBlankPosition || 1))
    const value = String(props.modelValue?.[position] ?? "")

    if (input.value !== value) {
      input.value = value
    }

    input.oninput = (event) => {
      const target = event.target
      if (!(target instanceof HTMLInputElement)) {
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
