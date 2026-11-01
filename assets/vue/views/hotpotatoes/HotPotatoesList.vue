<template>
  <section class="space-y-6">
    <SectionHeader :title="t('Hot Potatoes')">
      <BaseButton
        :label="t('Back')"
        :route="{ name: 'ExerciseList', params: { node: route.params.node }, query: route.query }"
        icon="back"
        type="secondary"
      />
    </SectionHeader>

    <form
      v-if="canManage"
      class="flex flex-col gap-4 rounded-xl border border-gray-20 bg-white p-6"
      @submit.prevent="importArchive"
    >
      <h3 class="font-semibold">{{ t('Import Hot Potatoes') }}</h3>
      <p class="text-sm text-gray-60">
        Upload a Hot Potatoes ZIP or standalone HTML export. Include linked media files in a ZIP.
      </p>
      <BaseFileUpload
        id="hotpotatoes-package"
        name="package"
        accept=".zip,.htm,.html,application/zip,text/html"
        :label="t('Choose file')"
        @file-selected="selectedFile = $event"
      />
      <div>
        <BaseButton
          :disabled="!selectedFile || saving"
          :is-loading="saving"
          :is-submit="true"
          :label="t('Import')"
          icon="file-upload"
          type="success"
        />
      </div>
    </form>

    <div
      v-if="errorMessage"
      role="alert"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="successMessage"
      role="status"
      class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"
    >
      {{ successMessage }}
    </div>

    <div class="rounded-xl border border-gray-20 bg-white p-6">
      <h3 class="mb-4 font-semibold">{{ t('Hot Potatoes') }}</h3>
      <p v-if="loading">{{ t('Loading') }}...</p>
      <p v-else-if="!activities.length">{{ t('No results found') }}</p>
      <div
        v-for="activity in activities"
        :key="activity.id"
        :data-hotpotatoes-id="activity.id"
        class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-20 py-3"
      >
        <div>
          <p class="font-medium">{{ activity.title }}</p>
          <p class="text-xs text-gray-50">{{ activity.entrypoints.length }} HTML activities</p>
          <p
            v-if="canManage && activity.lpItemCount > 0"
            class="text-xs text-gray-50"
          >
            {{ activity.lpItemCount }} linked learning path items
          </p>
        </div>
        <div class="flex items-center gap-2">
          <span
            v-if="canManage"
            class="text-xs text-gray-50"
          >
            {{ activity.visible ? t('Published') : t('Draft') }}
          </span>
          <BaseButton
            :label="t('Open')"
            icon="preview"
            type="primary-text"
            only-icon
            :route="{
              name: 'HotPotatoesPlayer',
              params: { node: route.params.node, documentId: activity.id },
              query: route.query,
            }"
          />
          <BaseButton
            v-if="canManage"
            :label="t('Attempts')"
            icon="list"
            type="primary-text"
            only-icon
            :disabled="attemptsLoading"
            @click="toggleAttempts(activity)"
          />
          <BaseButton
            v-if="canManage && activity.editable"
            :label="t('Edit')"
            icon="pencil"
            type="primary-text"
            only-icon
            :disabled="updatingId === activity.id"
            @click="startEdit(activity)"
          />
          <BaseButton
            v-if="canManage && activity.editable"
            :label="activity.visible ? t('Hide') : t('Publish')"
            :icon="activity.visible ? 'eye-off' : 'eye-on'"
            type="primary-text"
            only-icon
            :disabled="updatingId === activity.id"
            @click="changeVisibility(activity)"
          />
          <BaseButton
            v-if="canManage && activity.editable"
            :label="t('Delete')"
            icon="delete"
            type="danger-text"
            only-icon
            :disabled="updatingId === activity.id || activity.lpItemCount > 0"
            @click="confirmDelete(activity)"
          />
        </div>
        <div
          v-if="canManage && attemptsFor === activity.id"
          class="w-full overflow-x-auto rounded border border-gray-20 p-3"
        >
          <p class="mb-2 text-sm text-gray-60">
            Activity attempts and teacher-assessed scores (latest 100). Scores are not automatically sent to Gradebook.
          </p>
          <p v-if="attemptsLoading" class="text-sm">{{ t('Loading') }}...</p>
          <p v-else-if="!attemptRows.length" class="text-sm">{{ t('No results found') }}</p>
          <table v-else class="w-full text-left text-sm">
            <thead>
              <tr>
                <th scope="col" class="p-2">{{ t('User') }} ID</th>
                <th scope="col" class="p-2">{{ t('Activity') }}</th>
                <th scope="col" class="p-2">{{ t('Started') }}</th>
                <th scope="col" class="p-2">{{ t('Status') }}</th>
                <th scope="col" class="p-2">{{ t('Score') }} (%)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="attempt in attemptRows" :key="attempt.id" class="border-t border-gray-20">
                <td class="p-2">{{ attempt.userId }}</td>
                <td class="p-2">{{ attempt.entrypoint }}</td>
                <td class="p-2">{{ new Date(attempt.startedAt).toLocaleString() }}</td>
                <td class="p-2">{{ attempt.status }}</td>
                <td class="p-2">
                  <div class="flex items-center gap-2">
                    <span v-if="attempt.scorePercent !== null && attempt.scorePercent !== undefined" class="min-w-8">
                      {{ attempt.scorePercent }}%
                    </span>
                    <span v-else class="text-gray-50">—</span>
                    <form
                      v-if="activity.editable && attempt.status === 'completed'"
                      class="flex items-center gap-2"
                      @submit.prevent="reviewAttempt(activity, attempt)"
                    >
                      <input
                        v-model.number="reviewDrafts[attempt.id]"
                        :aria-label="`${t('Score')} ${attempt.userId}`"
                        :name="`scorePercent-${attempt.id}`"
                        type="number"
                        min="0"
                        max="100"
                        step="1"
                        required
                        class="w-20 rounded border border-gray-30 p-1"
                      />
                      <BaseButton
                        :label="t('Save')"
                        icon="save"
                        type="primary-text"
                        only-icon
                        :is-submit="true"
                        :is-loading="reviewSavingId === attempt.id"
                        :disabled="reviewSavingId !== null"
                      />
                    </form>
                  </div>
                  <div v-if="attempt.reviewedByUserId" class="text-xs text-gray-50">
                    Reviewed by user #{{ attempt.reviewedByUserId }}
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <form
          v-if="canManage && activity.editable && editingId === activity.id"
          class="mt-3 flex w-full items-center gap-2"
          @submit.prevent="saveTitle(activity)"
        >
          <label
            :for="`hotpotatoes-title-${activity.id}`"
            class="text-sm"
          >
            {{ t('Title') }}
          </label>
          <input
            :id="`hotpotatoes-title-${activity.id}`"
            v-model.trim="editingTitle"
            name="hotpotatoesTitle"
            type="text"
            maxlength="255"
            required
            class="min-w-0 flex-1 rounded border border-gray-30 p-2"
          />
          <BaseButton
            :label="t('Save')"
            icon="save"
            type="primary"
            :is-submit="true"
            :disabled="updatingId === activity.id || !editingTitle.trim()"
          />
          <BaseButton
            :label="t('Cancel')"
            icon="close"
            type="secondary"
            :disabled="updatingId === activity.id"
            @click="editingId = null"
          />
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import baseService from "../../services/baseService"

const { t } = useI18n()
const route = useRoute()
const { showErrorNotification, showSuccessNotification } = useNotification()
const { requireConfirmation } = useConfirmation()
const activities = ref([])
const canManage = ref(false)
const selectedFile = ref(null)
const attemptsFor = ref(null)
const attemptRows = ref([])
const reviewSavingId = ref(null)
const reviewDrafts = ref({})
const attemptsLoading = ref(false)
const loading = ref(true)
const saving = ref(false)
const updatingId = ref(null)
const editingId = ref(null)
const editingTitle = ref("")
const errorMessage = ref("")
const successMessage = ref("")
const context = computed(() => ({
  cid: Number(route.query.cid || 0),
  ...(Number(route.query.sid || 0) ? { sid: Number(route.query.sid) } : {}),
  ...(Number(route.query.gid || 0) ? { gid: Number(route.query.gid) } : {}),
}))

async function loadActivities() {
  loading.value = true
  try {
    const data = await baseService.get("/api/hotpotatoes/archives", context.value)
    activities.value = Array.isArray(data.items) ? data.items : []
    canManage.value = Boolean(data.canManage)
  } catch (error) {
    errorMessage.value = t("Unable to load activities")
    showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

async function toggleAttempts(activity) {
  if (!canManage.value || attemptsLoading.value) return
  if (attemptsFor.value === activity.id) {
    attemptsFor.value = null
    attemptRows.value = []
    return
  }

  attemptsFor.value = activity.id
  attemptRows.value = []
  attemptsLoading.value = true
  try {
    const result = await baseService.get(`/api/hotpotatoes/archives/${activity.id}/attempts`, context.value)
    attemptRows.value = Array.isArray(result.items) ? result.items : []
    reviewDrafts.value = Object.fromEntries(
      attemptRows.value.map((attempt) => [attempt.id, attempt.scorePercent ?? ""]),
    )
  } catch (error) {
    attemptsFor.value = null
    showErrorNotification(error)
  } finally {
    attemptsLoading.value = false
  }
}

async function reviewAttempt(activity, attempt) {
  if (!canManage.value || !activity.editable || attempt.status !== "completed" || reviewSavingId.value !== null) return

  const score = reviewDrafts.value[attempt.id]
  if (!Number.isInteger(score) || score < 0 || score > 100) {
    showErrorNotification("The reviewed score must be a whole number from 0 to 100.")
    return
  }

  reviewSavingId.value = attempt.id
  try {
    await baseService.post(
      `/api/hotpotatoes/archives/${activity.id}/attempts/${attempt.id}/reviews`,
      { scorePercent: score },
      {},
      { params: context.value },
    )
    const result = await baseService.get(`/api/hotpotatoes/archives/${activity.id}/attempts`, context.value)
    if (attemptsFor.value === activity.id) {
      attemptRows.value = Array.isArray(result.items) ? result.items : []
      reviewDrafts.value = Object.fromEntries(
        attemptRows.value.map((item) => [item.id, item.scorePercent ?? ""]),
      )
    }
    showSuccessNotification(t("Saved"))
  } catch (error) {
    showErrorNotification(error)
  } finally {
    reviewSavingId.value = null
  }
}

async function importArchive() {
  if (!canManage.value || !selectedFile.value || saving.value) return
  saving.value = true
  errorMessage.value = ""
  successMessage.value = ""
  try {
    const form = new FormData()
    form.append("package", selectedFile.value)
    const result = await baseService.post("/api/hotpotatoes/archives/import", form, {}, { params: context.value })
    successMessage.value = `${t("Imported successfully")}: #${result.documentId}`
    selectedFile.value = null
    await loadActivities()
  } catch (error) {
    errorMessage.value = t("Import failed")
    showErrorNotification(error)
  } finally {
    saving.value = false
  }
}

function startEdit(activity) {
  editingId.value = activity.id
  editingTitle.value = activity.title
}

async function updateActivity(activity, changes) {
  if (!canManage.value || updatingId.value !== null) return

  updatingId.value = activity.id
  errorMessage.value = ""
  try {
    const updated = await baseService.patch(
      `/api/hotpotatoes/archives/${activity.id}`,
      changes,
      { params: context.value },
    )
    activity.title = updated.title
    activity.visible = updated.visible
    editingId.value = null
    showSuccessNotification(t("Updated"))
  } catch (error) {
    errorMessage.value = t("Unable to update the activity")
    showErrorNotification(error)
  } finally {
    updatingId.value = null
  }
}

async function saveTitle(activity) {
  await updateActivity(activity, { title: editingTitle.value })
}

function changeVisibility(activity) {
  requireConfirmation({
    title: activity.visible ? t("Hide") : t("Publish"),
    message: activity.visible
      ? t("Students will no longer be able to open this activity.")
      : t("Students will be able to open this activity."),
    accept: () => updateActivity(activity, { published: !activity.visible }),
  })
}

function confirmDelete(activity) {
  if (!canManage.value || !activity.editable || activity.lpItemCount > 0) return

  requireConfirmation({
    title: t("Delete"),
    message: "Remove this archive from the current course? This cannot be undone from this screen.",
    accept: () => deleteArchive(activity),
  })
}

async function deleteArchive(activity) {
  if (!canManage.value || updatingId.value !== null) return

  updatingId.value = activity.id
  errorMessage.value = ""
  successMessage.value = ""
  try {
    await baseService.delete(`/api/hotpotatoes/archives/${activity.id}`, { params: context.value })
    editingId.value = null
    activities.value = activities.value.filter((entry) => entry.id !== activity.id)
    if (attemptsFor.value === activity.id) {
      attemptsFor.value = null
      attemptRows.value = []
    }
    showSuccessNotification(t("Deleted"))
  } catch (error) {
    errorMessage.value = error?.response?.status === 409
      ? "This archive is still referenced by a learning path. Remove its learning path items first."
      : t("Unable to delete the activity")
    showErrorNotification(error)
    if (error?.response?.status === 409) await loadActivities()
  } finally {
    updatingId.value = null
  }
}

onMounted(loadActivities)
watch(() => [route.query.cid, route.query.sid, route.query.gid], loadActivities)
</script>
