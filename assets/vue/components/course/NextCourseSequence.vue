<template>
  <div
    v-if="sequenceList.length"
    class="border border-gray-300 p-4 rounded-md mt-6 bg-white shadow"
  >
    <h3 class="text-lg font-semibold mb-4">
      {{ t("Dependencies") }}
    </h3>

    <div
      v-for="(item, index) in sequenceList"
      :key="index"
      class="mb-8"
    >
      <h4 class="text-base font-medium text-gray-700 mb-4">
        {{ item.name }}
      </h4>

      <div class="flex items-center justify-center flex-wrap gap-8 relative">
        <template
          v-for="(course, cid, idx) in item.dependents"
          :key="cid"
        >
          <div class="flex flex-col items-center text-center relative">
            <i class="mdi mdi-book-open-page-variant text-5xl text-blue-600"></i>

            <p class="mt-2 text-sm font-semibold">
              <template v-if="course.status && course.url">
                <a
                  :href="course.url"
                  class="text-green-700 hover:underline"
                >
                  {{ course.name }}
                </a>
              </template>
              <template v-else>
                {{ course.name }}
              </template>
            </p>

            <span
              class="mt-1 text-xs px-2 py-1 rounded font-medium"
              :class="course.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
            >
              <i
                :class="course.status ? 'mdi mdi-check' : 'mdi mdi-alert-circle'"
                class="mr-1"
              ></i>
              {{ course.status ? t("Complete") : t("Incomplete") }}
            </span>

            <div
              v-if="idx < Object.keys(item.dependents).length - 1"
              class="absolute right-[-40px] top-6 w-10 border-t-2 border-gray-300"
            ></div>
          </div>
        </template>
      </div>
    </div>

    <div
      v-if="graphUrl"
      class="mt-6 text-center"
    >
      <img
        :src="graphUrl"
        alt="Graph Dependency Tree"
        class="mx-auto max-w-full border rounded-md shadow"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useCidReqStore } from "../../store/cidReq"
import courseService from "../../services/courseService"
import { storeToRefs } from "pinia"

const { t } = useI18n()
const cidReqStore = useCidReqStore()
const { course, session } = storeToRefs(cidReqStore)

const graphUrl = ref(null)
const sequenceList = ref([])

onMounted(async () => {
  try {
    const { sequenceList: list, graph } = await courseService.getNextCourse(course.value.id, session.value?.id || 0, true)
    sequenceList.value = list || []
    graphUrl.value = graph || null
  } catch (e) {
    console.warn("No sequence data available", e)
  }
})
</script>
