<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"

import Skeleton from "primevue/skeleton"

import BaseButton from "../basecomponents/BaseButton.vue"
import { useSkillWheel } from "../../composables/skill/skillWheel"

const emit = defineEmits(["ready", "skill-detail"])
const { t } = useI18n()

const graphContainer = ref(null)
const wheelStage = ref(null)
const wheelSize = ref(720)
let resizeObserver = null

const wheelStyle = computed(() => ({
  width: `${wheelSize.value}px`,
  height: `${wheelSize.value}px`,
  maxWidth: "100%",
  maxHeight: "100%",
}))

const { wheelContainer, isLoading, breadcrumbs, visibleSkillCount, loadSkills, showRoot, showNode, showSkill } =
  useSkillWheel({
    rootLabel: t("Skills wheel"),
    skillsLabel: t("Skills"),
    onSkillDetail: (detail) => emit("skill-detail", detail),
  })

function updateWheelSize() {
  const stage = wheelStage.value
  if (!stage) {
    return
  }

  const bounds = stage.getBoundingClientRect()
  const horizontalPadding = 12
  const verticalPadding = 10
  const availableWidth = Math.max(0, bounds.width - horizontalPadding)
  const availableHeight = Math.max(0, bounds.height - verticalPadding)
  const nextSize = Math.floor(Math.min(availableWidth, availableHeight) * 0.98)

  if (nextSize > 0) {
    wheelSize.value = nextSize
  }
}

function fitGraphToViewport() {
  const graph = graphContainer.value
  if (!graph) {
    return
  }

  const bounds = graph.getBoundingClientRect()
  const bottomGap = 4
  const availableHeight = Math.max(320, Math.floor(window.innerHeight - bounds.top - bottomGap))

  graph.style.height = `${availableHeight}px`
  window.requestAnimationFrame(updateWheelSize)
}

defineExpose({
  fitGraphToViewport,
  showRoot,
  showNode,
  showSkill,
})

onMounted(async () => {
  await nextTick()
  fitGraphToViewport()

  if (window.ResizeObserver) {
    resizeObserver = new ResizeObserver(updateWheelSize)
    if (wheelStage.value) {
      resizeObserver.observe(wheelStage.value)
    }
  }

  window.addEventListener("resize", fitGraphToViewport)

  await loadSkills()
  await nextTick()
  fitGraphToViewport()
  emit("ready")
})

onBeforeUnmount(() => {
  resizeObserver?.disconnect()
  window.removeEventListener("resize", fitGraphToViewport)
})
</script>

<template>
  <div
    ref="graphContainer"
    data-testid="skill-wheel-graph"
    class="relative h-[calc(100dvh-10rem)] overflow-hidden rounded-2xl border border-gray-20 bg-white shadow-lg"
  >
    <div class="pointer-events-none absolute inset-0 bg-white" />
    <div class="pointer-events-none absolute inset-x-16 top-10 h-40 rounded-full bg-blue-20 opacity-35 blur-3xl" />
    <div class="pointer-events-none absolute inset-x-1/4 bottom-6 h-24 rounded-full bg-blue-20 opacity-20 blur-3xl" />

    <div class="absolute left-4 top-4 z-20 max-w-[72%] md:left-6 md:top-6">
      <div class="rounded-xl border border-white/70 bg-white/92 px-2 py-1.5 shadow-sm backdrop-blur md:px-3 md:py-2">
        <div class="flex min-w-0 items-center gap-1.5">
          <div class="flex min-w-0 items-center gap-1 overflow-hidden">
            <template
              v-for="(crumb, index) in breadcrumbs"
              :key="`${crumb.id ?? 'root'}-${index}`"
            >
              <span
                v-if="index > 0"
                class="shrink-0 text-xs text-gray-40"
              >
                /
              </span>
              <BaseButton
                v-if="index < breadcrumbs.length - 1"
                :label="crumb.label"
                size="small"
                type="plain"
                class="max-w-44 shrink min-w-0"
                @click="showNode(crumb.id)"
              />
              <span
                v-else
                class="max-w-56 truncate px-1 text-sm font-semibold text-gray-90 md:max-w-[28rem]"
                :title="crumb.label"
              >
                {{ crumb.label }}
              </span>
            </template>
          </div>
          <span
            class="shrink-0 rounded-full border border-blue-20 bg-blue-10 px-2 py-0.5 text-[10px] font-bold text-blue-80"
          >
            3D
          </span>
        </div>
      </div>
    </div>

    <div
      v-if="visibleSkillCount"
      class="pointer-events-none absolute right-4 top-4 z-20 rounded-full border border-white/70 bg-white/85 px-3 py-1.5 text-xs font-semibold text-gray-70 shadow-sm backdrop-blur md:right-6 md:top-6"
    >
      {{ visibleSkillCount }} {{ t("Skills") }}
    </div>

    <div
      ref="wheelStage"
      class="relative z-10 flex h-full w-full items-center justify-center p-1"
    >
      <div
        class="relative flex shrink-0 items-center justify-center"
        :style="wheelStyle"
      >
        <div
          ref="wheelContainer"
          data-testid="skill-wheel-canvas"
          data-render-mode="3d"
          class="absolute inset-0 select-none touch-none"
        />

        <Skeleton
          v-if="isLoading"
          class="absolute inset-0 h-full w-full"
          shape="circle"
          size="100%"
        />
      </div>
    </div>
  </div>
</template>
