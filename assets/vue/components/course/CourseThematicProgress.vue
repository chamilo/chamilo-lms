<template>
  <div
    v-if="progress && progress.enabled"
    class="mb-4 rounded-xl border border-gray-200 bg-white px-4 py-2 shadow-sm"
  >
    <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
      <div class="flex items-center gap-3 flex-shrink-0 min-w-[200px]">
        <div class="min-w-0">
          <p class="text-xs font-semibold truncate">
            {{ progress.title }}
          </p>

          <div class="mt-1 flex items-center gap-2">
            <div class="flex-1 h-1.5 rounded-full bg-gray-50 overflow-hidden">
              <div
                class="h-1.5 rounded-full"
                style="background-color: var(--color-primary, #2563eb)"
                :style="{ width: progress.score || '0%' }"
              />
            </div>

            <span class="text-[0.7rem] text-gray-600 whitespace-nowrap">
              {{ progress.score }}
            </span>
          </div>
        </div>
      </div>

      <div class="flex-1 min-w-0">
        <div class="text-[0.7rem] text-gray-700 flex flex-wrap items-center gap-x-2 gap-y-1">
          <template
            v-for="(item, index) in progress.items"
            :key="index"
          >
            <span
              v-if="index > 0"
              class="text-gray-300"
            >
              |
            </span>

            <span class="font-semibold text-gray-600">
              {{ item.type === "current" ? t("Current topic") : t("Next topic") }}:
            </span>

            <span class="font-semibold">
              {{ item.title }}
            </span>

            <span>• {{ item.startDate }}</span>

            <span v-if="item.content">
              • {{ item.content }}
            </span>

            <span>
              • {{
                (progress.labels?.duration || t("Duration in hours")) +
                ": " +
                item.duration
              }}
            </span>
          </template>
        </div>
      </div>

      <div
        v-if="progress.detailUrl"
        class="flex-shrink-0 md:ml-auto"
      >
        <a
          :href="progress.detailUrl"
          class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border text-[0.7rem] font-semibold bg-gray-25 hover:bg-gray-50 whitespace-nowrap"
        >
          <span class="mr-1">
            {{ progress.labels?.seeDetail || t("See detail") }}
          </span>
          <i class="mdi mdi-open-in-new text-xs" />
        </a>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import courseService from "../../services/courseService"

const { t } = useI18n()

const cidReqStore = useCidReqStore()
const { course, session } = storeToRefs(cidReqStore)

const progress = ref(null)

onMounted(async () => {
  if (!course.value?.id) {
    return
  }

  try {
    const data = await courseService.loadThematicProgress(
      course.value.id,
      session.value?.id || 0
    )

    if (data && data.enabled) {
      progress.value = data
    } else {
      progress.value = null
    }
  } catch (error) {
    console.error("[CourseThematicProgress] Failed to load thematic progress", error)
    progress.value = null
  }
})
</script>
