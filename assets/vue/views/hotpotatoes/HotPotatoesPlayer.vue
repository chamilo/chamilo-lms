<template>
  <section class="space-y-5">
    <SectionHeader :title="activity?.title || t('Hot Potatoes')">
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="secondary"
        :route="backRoute"
      />
    </SectionHeader>
    <p v-if="errorMessage" role="alert" class="text-red-700">{{ errorMessage }}</p>
    <template v-else-if="activity">
      <div v-if="!fixedEntrypoint" class="flex flex-wrap items-center gap-3">
        <label for="hotpotatoes-entry" class="text-sm font-semibold">{{ t('Activity') }}</label>
        <select
          id="hotpotatoes-entry"
          v-model="entrypoint"
          name="entrypoint"
          class="rounded border border-gray-200 px-3 py-2"
        >
          <option v-for="entry in activity.entrypoints" :key="entry" :value="entry">{{ entry }}</option>
        </select>
      </div>
      <p class="text-xs text-gray-60">
        This activity runs in an isolated browser frame. Launches and manual completions are tracked; scores are not verified or submitted to Gradebook.
      </p>
      <p v-if="attemptError" role="alert" class="text-sm text-red-700">{{ attemptError }}</p>
      <div class="flex items-center gap-3">
        <BaseButton
          :disabled="savingCompletion || completed || !attemptId"
          :is-loading="savingCompletion"
          :label="completed ? t('Completed') : t('Mark as completed')"
          icon="check"
          type="success"
          @click="markComplete"
        />
        <span v-if="completed" role="status" class="text-sm text-green-700">
          {{ t('Completed') }} · {{ t('No score') }}
        </span>
      </div>
      <iframe
        v-if="activityUrl"
        :key="activityUrl"
        :src="activityUrl"
        @load="startAttempt"
        title="Hot Potatoes activity"
        sandbox="allow-scripts"
        referrerpolicy="no-referrer"
        class="w-full rounded-lg border border-gray-200 bg-white"
        style="height: 75vh; min-height: 520px"
      />
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import baseService from "../../services/baseService"

const { t } = useI18n()
const route = useRoute()
const activity = ref(null)
const entrypoint = ref("")
const errorMessage = ref("")
const savingCompletion = ref(false)
const completed = ref(false)
const attemptId = ref(null)
const attemptError = ref("")
const startingEntry = ref("")
const startedEntry = ref("")
const isLearningPathActivity = computed(() =>
  String(route.query.origin || "") === "learnpath" &&
  Number(route.query.lp_id || 0) > 0 && Number(route.query.item_id || 0) > 0,
)
const fixedEntrypoint = computed(() =>
  isLearningPathActivity.value ? String(route.query.entrypoint || "") : "",
)
const backRoute = computed(() =>
  "learnpath" === String(route.query.origin || "") && Number(route.query.lp_id || 0) > 0
    ? {
        name: "LpRuntime",
        params: { node: route.query.lp_node || route.params.node, lpId: route.query.lp_id },
        query: route.query,
      }
    : { name: "HotPotatoesList", params: { node: route.params.node }, query: route.query },
)
const context = computed(() => ({
  cid: Number(route.query.cid || 0),
  ...(Number(route.query.sid || 0) ? { sid: Number(route.query.sid) } : {}),
  ...(Number(route.query.gid || 0) ? { gid: Number(route.query.gid) } : {}),
}))
const activityUrl = computed(() => {
  if (!activity.value || !entrypoint.value) return ""
  const encodedPath = entrypoint.value.split("/").map(encodeURIComponent).join("/")
  const cid = context.value.cid
  const sid = context.value.sid || 0
  const gid = context.value.gid || 0
  return `/api/hotpotatoes/archives/${cid}/${sid}/${gid}/${Number(activity.value.id)}/content/${encodedPath}?cid=${cid}&sid=${sid}&gid=${gid}`
})

async function startAttempt() {
  if (!activity.value || !entrypoint.value) return
  const selected = entrypoint.value
  if ((startedEntry.value === selected && attemptId.value) || startingEntry.value === selected) return
  startingEntry.value = selected
  attemptError.value = ""
  try {
    const result = await baseService.post(
      `/api/hotpotatoes/archives/${Number(activity.value.id)}/attempts`,
      { entrypoint: selected },
      {},
      { params: context.value },
    )
    if (entrypoint.value === selected) {
      attemptId.value = Number(result.id) || null
      startedEntry.value = selected
    }
  } catch {
    if (entrypoint.value === selected) {
      attemptError.value = "Unable to record the activity launch."
    }
  } finally {
    if (startingEntry.value === selected) startingEntry.value = ""
  }
}

async function markComplete() {
  if (!activity.value || savingCompletion.value || !attemptId.value) return
  savingCompletion.value = true
  attemptError.value = ""
  try {
    if (isLearningPathActivity.value) {
      const result = await baseService.post(
        `/api/hotpotatoes/archives/${Number(activity.value.id)}/complete`,
        { lpId: Number(route.query.lp_id), itemId: Number(route.query.item_id) },
        {},
        { params: context.value },
      )
      if (result.completed !== true) throw new Error("Learning path progress was not updated.")
    }
    await baseService.post(
      `/api/hotpotatoes/archives/${Number(activity.value.id)}/attempts/${attemptId.value}/complete`,
      {},
      {},
      { params: context.value },
    )
    completed.value = true
  } catch {
    attemptError.value = "Unable to record completion. If this is a learning path, progress may already be saved."
  } finally {
    savingCompletion.value = false
  }
}

watch(entrypoint, () => {
  attemptId.value = null
  completed.value = false
  attemptError.value = ""
  startedEntry.value = ""
})

onMounted(async () => {
  try {
    const data = await baseService.get("/api/hotpotatoes/archives", context.value)
    activity.value = (data.items || []).find((item) => Number(item.id) === Number(route.params.documentId)) || null
    if (!activity.value) {
      errorMessage.value = t("Activity not found")
      return
    }
    if (fixedEntrypoint.value) {
      if (!activity.value.entrypoints.includes(fixedEntrypoint.value)) {
        errorMessage.value = t("Activity not found")
        return
      }
      entrypoint.value = fixedEntrypoint.value
      return
    }
    entrypoint.value = activity.value.entrypoints.includes("index.htm")
      ? "index.htm"
      : activity.value.entrypoints.includes("index.html")
        ? "index.html"
        : activity.value.entrypoints[0] || ""
  } catch {
    errorMessage.value = t("Unable to load activities")
  }
})
</script>
