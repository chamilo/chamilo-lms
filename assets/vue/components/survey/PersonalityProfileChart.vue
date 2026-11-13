<template>
  <section class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
    <h2 class="mb-2 text-lg font-semibold text-gray-90">{{ title }}</h2>
    <p class="mb-5 text-sm text-gray-600">
      {{ t("Preference scores are descriptive, not grades or a clinical assessment.") }}
    </p>
    <div class="grid gap-5 lg:grid-cols-2">
      <div class="h-72"><canvas ref="barCanvas" aria-label="Preference bar chart" role="img" /></div>
      <div class="h-72"><canvas ref="radarCanvas" aria-label="Preference radar chart" role="img" /></div>
    </div>
    <div class="mt-5 flex flex-wrap gap-2 text-sm">
      <span
        v-for="category in profile"
        :key="category.id"
        class="rounded-lg border border-gray-20 px-3 py-1"
      >
        {{ category.label }}: <strong>{{ category.score }}</strong>
      </span>
    </div>
  </section>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import Chart from "chart.js/auto"

const props = defineProps({
  title: { type: String, required: true },
  profile: { type: Array, default: () => [] },
})
const { t } = useI18n()
const barCanvas = ref(null)
const radarCanvas = ref(null)
let barChart = null
let radarChart = null

function renderCharts() {
  barChart?.destroy()
  radarChart?.destroy()
  barChart = null
  radarChart = null
  if (!barCanvas.value || !radarCanvas.value || !props.profile.length) return

  const labels = props.profile.map((item) => item.label)
  const values = props.profile.map((item) => Number(item.score || 0))
  const dataset = {
    label: t("Preference score"),
    data: values,
    backgroundColor: "rgba(39, 126, 149, 0.55)",
    borderColor: "rgb(29, 102, 127)",
    borderWidth: 2,
  }
  const makeOptions = () => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    plugins: { legend: { display: false } },
  })

  barChart = new Chart(barCanvas.value, {
    type: "bar",
    data: { labels, datasets: [{ ...dataset }] },
    options: {
      ...makeOptions(),
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
    },
  })
  radarChart = new Chart(radarCanvas.value, {
    type: "radar",
    data: { labels, datasets: [{ ...dataset }] },
    options: {
      ...makeOptions(),
      scales: {
        r: {
          beginAtZero: true,
          grid: { circular: true },
          ticks: { precision: 0 },
        },
      },
    },
  })
}

watch(() => props.profile, () => nextTick(renderCharts), { deep: true })
onMounted(() => nextTick(renderCharts))
onBeforeUnmount(() => {
  barChart?.destroy()
  radarChart?.destroy()
})
</script>
