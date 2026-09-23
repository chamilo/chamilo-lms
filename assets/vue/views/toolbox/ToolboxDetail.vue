<template>
  <div class="flex w-full flex-col gap-6">
    <SectionHeader
      :show-student-view-button="Boolean(item?.canEdit)"
      :title="item?.title || t('AI Toolbox')"
    >
      <BaseButton
        :label="t('Back')"
        icon="back"
        only-icon
        type="black"
        @click="goBack"
      />
      <BaseButton
        v-if="item?.canEdit && item?.previewUrl"
        :label="t('Preview')"
        icon="preview"
        only-icon
        type="black"
        @click="openUrl(item.previewUrl)"
      />
      <BaseButton
        v-if="item?.canEdit && item?.downloadUrl"
        :label="t('Download')"
        icon="download"
        only-icon
        type="black"
        @click="openUrl(item.downloadUrl)"
      />
    </SectionHeader>

    <div
      v-if="loading"
      class="animate-pulse rounded-2xl border border-gray-25 bg-white p-8"
    >
      <div class="mb-4 h-8 w-1/3 rounded bg-gray-15" />
      <div class="h-44 rounded bg-gray-15" />
    </div>

    <div
      v-else-if="loadError"
      class="rounded-lg border border-danger/30 bg-danger/10 p-4 text-danger"
    >
      {{ t("An error occurred") }}
    </div>

    <template v-else-if="item">
      <section class="overflow-hidden rounded-2xl border border-gray-25 bg-white">
        <div class="grid grid-cols-1 gap-0 lg:grid-cols-[minmax(0,2fr)_minmax(17rem,1fr)]">
          <div class="flex flex-col gap-5 p-6">
            <div class="flex items-start gap-4">
              <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 text-primary">
                <BaseIcon icon="robot" />
              </div>
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <h2 class="text-xl font-semibold text-gray-90">{{ item.title }}</h2>
                  <button
                    v-if="item.canEdit"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold transition-opacity disabled:cursor-not-allowed disabled:opacity-60"
                    :class="item.visible ? 'bg-success/10 text-success' : 'bg-gray-15 text-gray-60'"
                    :disabled="visibilityBusy || (!item.launchUrl && !item.visible)"
                    :title="item.visible ? t('Hide') : t('Publish')"
                    :aria-label="item.visible ? t('Hide') : t('Publish')"
                    :aria-pressed="item.visible"
                    @click="toggleVisibility"
                  >
                    <BaseIcon :icon="item.visible ? 'eye-on' : 'eye-off'" />
                    {{ item.visible ? t("Published") : t("Draft") }}
                  </button>
                </div>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-60">
                  {{ item.description || t("Interactive learning application") }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
              <div class="rounded-xl border border-gray-20 bg-gray-10 p-3">
                <div class="text-xs text-gray-50">{{ t("Current version") }}</div>
                <div class="mt-1 text-lg font-semibold text-gray-90">v{{ item.currentVersion }}</div>
              </div>
              <div class="rounded-xl border border-gray-20 bg-gray-10 p-3">
                <div class="text-xs text-gray-50">{{ t("Versions") }}</div>
                <div class="mt-1 text-lg font-semibold text-gray-90">{{ item.versionCount }}</div>
              </div>
              <div class="rounded-xl border border-gray-20 bg-gray-10 p-3">
                <div class="text-xs text-gray-50">{{ t("Progress") }}</div>
                <div class="mt-1 text-lg font-semibold text-gray-90">{{ item.progress }}%</div>
              </div>
              <div class="rounded-xl border border-gray-20 bg-gray-10 p-3">
                <div class="text-xs text-gray-50">{{ t("Score") }}</div>
                <div class="mt-1 text-lg font-semibold text-gray-90">{{ formatScore(item.score) }}</div>
              </div>
            </div>
          </div>

          <div class="flex flex-col justify-center gap-3 border-t border-gray-20 bg-gray-10 p-6 lg:border-l lg:border-t-0">
            <BaseButton
              v-if="item.launchUrl"
              :label="item.canEdit ? t('Open in student view') : t('Open')"
              icon="play-box-outline"
              type="primary"
              @click="openUrl(item.launchUrl)"
            />
            <BaseButton
              v-if="item.canEdit && item.reportingUrl"
              :label="t('Reporting')"
              icon="tracking"
              type="secondary"
              @click="openUrl(item.reportingUrl)"
            />
            <p v-if="!item.launchUrl" class="text-center text-sm text-gray-50">
              {{ t("Generate the application to enable preview and publishing.") }}
            </p>
          </div>
        </div>
      </section>

      <section
        v-if="item.canGenerate"
        class="rounded-2xl border border-gray-25 bg-white p-5"
      >
        <div class="mb-5 flex items-start gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <BaseIcon icon="robot" />
          </div>
          <div>
            <h2 class="text-lg font-semibold text-gray-90">
              {{ item.launchUrl ? t("Improve with AI") : t("Generate with AI") }}
            </h2>
            <p class="mt-1 text-sm text-gray-50">
              {{ item.launchUrl ? t("Describe the change. A new version will be created and previous versions will remain available.") : t("Describe the complete interactive activity you want to generate.") }}
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
          <BaseTextArea
            id="toolbox-improvement-prompt"
            v-model="improvement.prompt"
            name="toolbox_improvement_prompt"
            :label="item.launchUrl ? t('What should be improved?') : t('What should the application do?')"
            :rows="7"
          />

          <div class="flex flex-col gap-4">
            <BaseSelect
              v-if="providers.length > 1"
              id="toolbox-improvement-provider"
              v-model="improvement.provider"
              name="toolbox_improvement_provider"
              :label="t('AI provider')"
              :options="providers"
              option-label="label"
              option-value="value"
            />
            <div class="mt-auto flex justify-end">
              <BaseButton
                :disabled="generating || !improvement.prompt.trim()"
                icon="robot"
                :is-loading="generating"
                :label="generating ? t('Please wait, this could take a while...') : t('Generate new version')"
                type="primary"
                @click="generateVersion"
              />
            </div>
          </div>
        </div>
      </section>

      <section
        v-if="item.canEdit"
        class="flex flex-col gap-4 rounded-2xl border border-gray-25 bg-white p-5"
      >
        <div class="flex flex-col gap-1">
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Version history") }}</h2>
          <p class="text-sm text-gray-50">{{ t("Preview, download or restore any generated version.") }}</p>
        </div>

        <div
          v-if="item.versions.length === 0"
          class="rounded-xl border border-dashed border-gray-30 p-5 text-center text-sm text-gray-50"
        >
          {{ t("No results found") }}
        </div>

        <article
          v-for="version in item.versions"
          :key="version.id"
          class="rounded-xl border p-4"
          :class="version.isCurrent ? 'border-primary/30 bg-primary/5' : 'border-gray-25'"
        >
          <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <strong class="text-gray-90">{{ t("Version") }} {{ version.versionNumber }}</strong>
                <span
                  v-if="version.isCurrent"
                  class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary"
                >
                  {{ t("Current") }}
                </span>
                <span v-if="version.provider" class="rounded-full bg-gray-15 px-2.5 py-1 text-xs text-gray-60">
                  {{ version.provider }}
                </span>
              </div>

              <p v-if="version.changeSummary" class="mt-2 text-sm text-gray-70">{{ version.changeSummary }}</p>
              <p v-else-if="version.description" class="mt-2 text-sm text-gray-60">{{ version.description }}</p>

              <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-50">
                <span>{{ formatDate(version.createdAt) }}</span>
                <span v-if="version.createdBy?.name">{{ version.createdBy.name }}</span>
              </div>

              <details v-if="version.prompt" class="mt-3 text-sm">
                <summary class="cursor-pointer font-semibold text-gray-70">{{ t("Prompt") }}</summary>
                <p class="mt-2 whitespace-pre-line rounded-lg bg-gray-10 p-3 text-gray-60">{{ version.prompt }}</p>
              </details>
            </div>

            <div class="flex shrink-0 items-center gap-2">
              <BaseButton
                v-if="version.previewUrl"
                :label="t('Preview')"
                icon="preview"
                only-icon
                type="black"
                @click="openUrl(version.previewUrl)"
              />
              <BaseButton
                v-if="version.reportingUrl"
                :label="t('Reporting')"
                icon="information"
                only-icon
                type="black"
                @click="openUrl(version.reportingUrl)"
              />
              <BaseButton
                v-if="version.downloadUrl"
                :label="t('Download')"
                icon="download"
                only-icon
                type="black"
                @click="openUrl(version.downloadUrl)"
              />
              <BaseButton
                v-if="!version.isCurrent && version.learningPathId"
                :disabled="restoringVersion === version.versionNumber"
                :is-loading="restoringVersion === version.versionNumber"
                :label="t('Restore')"
                icon="restore"
                only-icon
                type="primary"
                @click="restoreVersion(version.versionNumber)"
              />
            </div>
          </div>
        </article>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { storeToRefs } from "pinia"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import { useCidReqStore } from "../../store/cidReq"
import toolboxService from "../../services/toolboxService"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { course, session } = storeToRefs(useCidReqStore())
const { showSuccessNotification, showErrorNotification } = useNotification()

const loading = ref(true)
const loadError = ref(false)
const generating = ref(false)
const visibilityBusy = ref(false)
const restoringVersion = ref(0)
const item = ref(null)
const providers = ref([])
const improvement = reactive({ prompt: "", provider: "" })

const context = computed(() => ({
  cid: course.value?.id ?? Number(route.query.cid ?? 0),
  sid: session.value?.id ?? Number(route.query.sid ?? 0),
  gid: Number(route.query.gid ?? 0),
}))

onMounted(load)

async function load() {
  loading.value = true
  loadError.value = false
  try {
    const [itemData, collectionData] = await Promise.all([
      toolboxService.getItem(route.params.itemId, context.value),
      toolboxService.getItems(context.value),
    ])
    item.value = itemData
    providers.value = Array.isArray(collectionData?.providers) ? collectionData.providers : []
    if (!improvement.provider && providers.value.length === 1) {
      improvement.provider = providers.value[0].value
    }
  } catch (error) {
    loadError.value = true
    showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

async function generateVersion() {
  if (!improvement.prompt.trim()) return
  generating.value = true
  try {
    await toolboxService.generateVersion(route.params.itemId, context.value, {
      prompt: improvement.prompt.trim(),
      provider: improvement.provider || null,
    })
    improvement.prompt = ""
    showSuccessNotification(t("A new version was generated."))
    await load()
  } catch (error) {
    showErrorNotification(error)
  } finally {
    generating.value = false
  }
}

async function toggleVisibility() {
  if (!item.value) return
  visibilityBusy.value = true
  try {
    await toolboxService.setVisibility(item.value.id, context.value, !item.value.visible)
    showSuccessNotification(item.value.visible ? t("Hidden") : t("Published"))
    await load()
  } catch (error) {
    showErrorNotification(error)
  } finally {
    visibilityBusy.value = false
  }
}

async function restoreVersion(versionNumber) {
  restoringVersion.value = Number(versionNumber)
  try {
    await toolboxService.restoreVersion(item.value.id, versionNumber, context.value)
    showSuccessNotification(t("Version restored"))
    await load()
  } catch (error) {
    showErrorNotification(error)
  } finally {
    restoringVersion.value = 0
  }
}

function openUrl(url) {
  if (url) window.location.assign(url)
}

function goBack() {
  router.push({ name: "ToolboxList", params: { node: route.params.node }, query: route.query })
}

function formatScore(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "-"
  }

  const score = Number(value)
  return `${score.toFixed(Number.isInteger(score) ? 0 : 1)}%`
}

function formatDate(value) {
  if (!value) return ""
  try {
    return new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(new Date(value))
  } catch {
    return value
  }
}
</script>
