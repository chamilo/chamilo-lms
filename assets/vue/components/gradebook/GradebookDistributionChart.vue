<template>
  <article class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
    <h2 class="mb-4 text-center font-semibold text-gray-90">{{ title }}</h2>
    <div class="h-64">
      <canvas ref="canvas" />
    </div>
  </article>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import {
  BarController,
  BarElement,
  CategoryScale,
  Chart,
  Legend,
  LinearScale,
  LineController,
  LineElement,
  PointElement,
  Tooltip,
} from "chart.js"
import { useTheme } from "../../composables/theme"

Chart.register(
  BarController,
  BarElement,
  CategoryScale,
  Legend,
  LinearScale,
  LineController,
  LineElement,
  PointElement,
  Tooltip,
)

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  distribution: {
    type: Array,
    required: true,
  },
  average: {
    type: Object,
    default: null,
  },
})

const { t } = useI18n()
const { getColorTheme } = useTheme()
const canvas = ref(null)
let chart = null

/**
 * Reads one platform theme color as a string chart.js understands. The bars
 * follow the theme instead of a fixed palette; the average point takes the
 * tertiary color, which contrasts with the bars in every theme and carries no
 * meaning of its own, unlike the danger red.
 *
 * @param {string} variableName A theme CSS variable, e.g. "--color-primary-base".
 * @returns {string} The color as a hexadecimal string.
 */
function themeColor(variableName) {
  return getColorTheme(variableName).value.to("srgb").toString({ format: "hex" })
}

/**
 * Builds the datasets: one bar per score range, plus a single point on the
 * range that holds the average when the caller provides one.
 *
 * @returns {Object} A chart.js data object.
 */
function buildData() {
  const labels = props.distribution.map((bucket) => bucket.label)
  const datasets = [
    {
      type: "bar",
      label: t("Learners"),
      data: props.distribution.map((bucket) => bucket.count),
      backgroundColor: themeColor("--color-primary-base"),
      borderRadius: 4,
    },
  ]

  const averageIndex = props.average
    ? props.distribution.findIndex((bucket) => bucket.label === props.average.label)
    : -1

  if (averageIndex >= 0) {
    const averageColor = themeColor("--color-tertiary-base")

    datasets.push({
      type: "line",
      label: `${t("Average score")} (${props.average.percentage}%)`,
      data: props.distribution.map((bucket, index) => (index === averageIndex ? bucket.count : null)),
      borderColor: averageColor,
      backgroundColor: averageColor,
      pointRadius: 6,
      pointHoverRadius: 8,
      showLine: false,
    })
  }

  return { labels, datasets }
}

/**
 * Draws the chart, or feeds the existing one with the current data.
 *
 * @returns {void}
 */
function render() {
  if (!canvas.value) {
    return
  }

  const data = buildData()

  if (chart) {
    chart.data = data
    chart.options.plugins.legend.display = data.datasets.length > 1
    chart.update()

    return
  }

  chart = new Chart(canvas.value, {
    type: "bar",
    data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          // Headroom, so the average point on top of the tallest bar is not
          // clipped by the edge of the chart area.
          grace: "10%",
          ticks: { precision: 0 },
          title: { display: true, text: t("Learners") },
        },
      },
      plugins: {
        // A lone "Learners" entry says nothing; the average point needs naming.
        legend: { display: data.datasets.length > 1 },
      },
    },
  })
}

watch(() => [props.distribution, props.average], render, { deep: true })

onMounted(render)

onBeforeUnmount(() => {
  chart?.destroy()
  chart = null
})
</script>
