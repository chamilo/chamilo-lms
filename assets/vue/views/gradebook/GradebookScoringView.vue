<template>
  <section class="space-y-6">
    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="infoMessage"
      class="rounded-xl border border-info/30 bg-support-1 p-4 text-sm text-support-4"
      role="status"
    >
      {{ infoMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <template v-else-if="data">
      <div class="flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
        <BaseButton
          :label="t('Back')"
          :route="backRoute"
          icon="back"
          only-icon
          size="normal"
          type="primary-text"
        />
        <BaseButton
          v-if="data.canManage && data.customEnabled"
          :disabled="isSaving"
          :is-loading="isSaving"
          :label="t('Save')"
          icon="save"
          only-icon
          size="normal"
          type="success"
          @click="save"
        />
      </div>

      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2">
          <BaseIcon
            icon="graph"
            size="normal"
          />
          <h1 class="text-xl font-semibold text-gray-90">
            {{ t("Skills ranking") }}
          </h1>
        </div>
        <p
          v-if="categorySubtitle"
          class="mt-2 text-sm text-gray-600"
        >
          {{ categorySubtitle }}
        </p>
      </div>

      <div
        v-if="!data.customEnabled"
        class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      >
        {{ t("Skills ranking") }}: {{ t("Disabled") }}
      </div>

      <div
        v-else
        class="space-y-6 rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div
          v-if="data.coloringEnabled"
          class="max-w-xs"
        >
          <BaseInputNumber
            id="gradebook-score-color-threshold"
            v-model="colorSplitPercent"
            :disabled="!data.canManage"
            :label="t('Threshold')"
            :max="100"
            :min="1"
            name="gradebook_score_color_threshold"
          />
        </div>

        <div>
          <div class="mb-3 flex items-center justify-between gap-4">
            <div>
              <h2 class="font-semibold text-gray-90">{{ t("Skills ranking") }}</h2>
              <p class="text-sm text-gray-600">{{ t("Information") }}</p>
            </div>
            <BaseButton
              v-if="data.canManage && ranges.length < 20"
              :label="t('Add')"
              icon="plus"
              only-icon
              size="small"
              type="success"
              @click="addRange"
            />
          </div>

          <div class="space-y-3">
            <div
              v-for="(range, index) in ranges"
              :key="range.key"
              class="grid gap-3 rounded-lg border border-gray-20 p-3 md:grid-cols-[160px_1fr_auto] md:items-end"
            >
              <BaseInputNumber
                :id="`gradebook-score-range-${index}`"
                v-model="range.score"
                :disabled="!data.canManage"
                :label="t('Score')"
                :max="100"
                :min="1"
                :name="`gradebook_score_range_${index}`"
              />
              <BaseInputText
                :id="`gradebook-score-label-${index}`"
                v-model="range.display"
                :disabled="!data.canManage"
                :label="t('Description')"
                :name="`gradebook_score_label_${index}`"
              />
              <BaseButton
                v-if="data.canManage && ranges.length > 1"
                :label="t('Delete')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="removeRange(index)"
              />
            </div>
          </div>
        </div>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()

const data = ref(null)
const ranges = ref([])
const colorSplitPercent = ref(50)
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const infoMessage = ref("")
let nextKey = 1

const categorySubtitle = computed(() => {
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId <= 0) {
    return ""
  }

  return String(data.value?.category?.title || "").trim()
})

const backRoute = computed(() => ({
  name: "GradebookList",
  params: { node: route.params.node },
  query: { ...route.query },
}))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
  }
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId > 0) {
    params.categoryId = categoryId
  }

  return params
}

async function loadData() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    data.value = await gradebookService.getScoringSettings(getContextParams())
    colorSplitPercent.value = Number(data.value?.colorSplitPercent || 50)
    ranges.value = (data.value?.ranges || []).map((range) => ({
      key: nextKey++,
      score: Number(range.score || 0),
      display: range.display || "",
    }))
    if (ranges.value.length === 0) {
      addRange()
    }
  } catch (error) {
    console.error("Failed to load Gradebook scoring settings:", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

function addRange() {
  if (ranges.value.length >= 20) {
    return
  }

  ranges.value.push({
    key: nextKey++,
    score: ranges.value.length === 0 ? 100 : null,
    display: "",
  })
}

function removeRange(index) {
  ranges.value.splice(index, 1)
}

async function save() {
  isSaving.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    await gradebookService.runScoringAction(
      {
        categoryId: Number(data.value?.category?.id || 0),
        colorSplitPercent: Number(colorSplitPercent.value || 0),
        ranges: ranges.value.map((range) => ({
          score: range.score,
          display: range.display || "",
        })),
        submittedCsrfToken: data.value?.csrfToken || "",
      },
      getContextParams(),
    )
    infoMessage.value = t("Update successful")
    await loadData()
  } catch (error) {
    console.error("Failed to save Gradebook scoring settings:", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    isSaving.value = false
  }
}

onMounted(loadData)
</script>
