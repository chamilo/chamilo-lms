<template>
  <Dialog
    v-model:visible="visible"
    :header="t('Student Success AI Coach')"
    modal
    :draggable="false"
    :style="{ width: 'min(920px, 96vw)' }"
  >
    <div class="space-y-5">
      <Message
        severity="warn"
        :closable="false"
      >
        {{ privacyWarning }}
      </Message>

      <div class="rounded-lg border border-gray-25 bg-gray-10 p-4">
        <div class="flex items-start gap-3">
          <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
            <i class="mdi mdi-robot text-xl" />
          </span>
          <div class="min-w-0">
            <div class="font-semibold">{{ learner?.fullName || t("Learner") }}</div>
            <div class="text-sm text-gray-50">{{ courseTitle }}</div>
          </div>
        </div>
      </div>

      <div
        v-if="configurationLoading"
        class="flex min-h-32 items-center justify-center"
      >
        <ProgressSpinner />
      </div>

      <template v-else>
        <Message
          v-if="errorMessage"
          severity="error"
          :closable="false"
        >
          {{ errorMessage }}
        </Message>

        <Message
          v-if="!providers.length"
          severity="error"
          :closable="false"
        >
          {{ t("No AI text provider is configured.") }}
        </Message>

        <div
          v-if="providers.length > 1"
          class="max-w-sm"
        >
          <BaseSelect
            id="student-success-provider"
            v-model="selectedProvider"
            :label="t('AI provider')"
            :options="providerOptions"
            name="studentSuccessProvider"
          />
        </div>

        <div class="space-y-2">
          <label
            for="student-success-teacher-prompt"
            class="block text-sm font-semibold"
          >
            {{ t("Additional instructions for the AI coach") }}
          </label>
          <Textarea
            id="student-success-teacher-prompt"
            v-model="teacherPrompt"
            class="w-full"
            :disabled="isAnalyzing"
            :maxlength="6000"
            :placeholder="t('Example: This learner has difficulty maintaining attention. Suggest shorter activities and more frequent checks for understanding.')"
            rows="5"
          />
          <div class="text-right text-xs text-gray-50">{{ teacherPrompt.length }} / 6000</div>
        </div>

        <div
          v-if="isAnalyzing"
          class="rounded-lg border border-primary/20 bg-primary/5 p-5"
        >
          <div class="flex items-center gap-4">
            <ProgressSpinner class="h-9 w-9" />
            <div>
              <div class="font-semibold">{{ statusMessage }}</div>
              <div class="mt-1 text-sm text-gray-50">
                {{ t("Keep this window open while Chamilo prepares the recommendation.") }}
              </div>
            </div>
          </div>
        </div>

        <Message
          v-if="analysisCompleted && messageSent"
          severity="success"
          :closable="false"
        >
          {{ t("The recommendation was also saved in your Chamilo inbox.") }}
        </Message>

        <Message
          v-else-if="analysisCompleted && !messageSent"
          severity="warn"
          :closable="false"
        >
          {{ t("The recommendation was generated and saved for this learner, but the inbox copy could not be created.") }}
        </Message>

        <section
          v-if="result"
          class="space-y-5 rounded-xl border border-gray-25 bg-white p-5"
        >
          <div class="flex flex-wrap items-start gap-3">
            <div class="min-w-0 flex-1">
              <h3 class="text-lg font-semibold">{{ t("Student success recommendation") }}</h3>
              <p
                v-if="generatedAt"
                class="mt-1 text-xs text-gray-50"
              >
                {{ t("Generated") }}: {{ formatDateTime(generatedAt) }}
              </p>
            </div>
          </div>

          <p
            v-if="result.summary"
            class="leading-6"
          >
            {{ result.summary }}
          </p>

          <div v-if="result.priorityActions?.length">
            <h4 class="mb-2 font-semibold">{{ t("Priority actions") }}</h4>
            <ol class="list-decimal space-y-1 pl-5">
              <li
                v-for="(action, index) in result.priorityActions"
                :key="`priority-${index}`"
              >
                {{ action }}
              </li>
            </ol>
          </div>

          <div
            v-for="section in recommendationSections"
            :key="section.key"
            v-show="section.items.length"
            class="space-y-3"
          >
            <h4 class="border-b border-gray-25 pb-2 font-semibold">{{ section.label }}</h4>

            <article
              v-for="(item, index) in section.items"
              :key="`${section.key}-${index}`"
              class="rounded-lg bg-gray-10 p-4"
            >
              <p class="font-medium">{{ item.recommendation }}</p>
              <p
                v-if="item.rationale"
                class="mt-2 text-sm leading-6 text-gray-70"
              >
                {{ item.rationale }}
              </p>

              <div
                v-if="item.sources?.length"
                class="mt-3"
              >
                <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-50">
                  {{ t("Evidence") }}
                </div>
                <ul class="space-y-1 text-sm">
                  <li
                    v-for="source in item.sources"
                    :key="source.id"
                  >
                    <a
                      :href="source.url"
                      class="text-primary hover:underline"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      {{ source.title }}
                    </a>
                  </li>
                </ul>
              </div>
            </article>
          </div>
        </section>
      </template>
    </div>

    <template #footer>
      <div class="flex w-full flex-wrap justify-end gap-2">
        <BaseButton
          :label="t('Close')"
          icon="close"
          type="primary-alternative"
          :disabled="isAnalyzing"
          @click="visible = false"
        />
        <BaseButton
          :label="t(`Analyze user's learning`)"
          icon="robot"
          type="primary"
          :disabled="configurationLoading || !providers.length || isAnalyzing"
          :is-loading="isAnalyzing"
          @click="analyzeLearner"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import Dialog from "primevue/dialog"
import Message from "primevue/message"
import ProgressSpinner from "primevue/progressspinner"
import Textarea from "primevue/textarea"
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import studentSuccessService from "../../services/studentSuccessService"

const visible = defineModel("visible", {
  type: Boolean,
  default: false,
})

const props = defineProps({
  learner: {
    type: Object,
    default: null,
  },
  courseId: {
    type: Number,
    required: true,
  },
  sessionId: {
    type: Number,
    default: 0,
  },
  courseTitle: {
    type: String,
    default: "",
  },
})

const { t } = useI18n()

const configurationLoading = ref(false)
const providers = ref([])
const selectedProvider = ref("")
const courseAnalysisAvailable = ref(false)
const teacherPrompt = ref("")
const isAnalyzing = ref(false)
const statusMessage = ref("")
const errorMessage = ref("")
const result = ref(null)
const generatedAt = ref(null)
const analysisCompleted = ref(false)
const messageSent = ref(false)

const privacyWarning = computed(() =>
  t(
    "This feature will analyse this course's content and send learning behaviour information to the configured AI model. Despite not sending directly identifiable personal data to the AI model, some of the data sent might indirectly provide identifiable information about the user. If in doubt, always check with your AI Reference person before using this feature.",
  ),
)

const providerOptions = computed(() => providers.value.map((provider) => ({ label: provider, value: provider })))

const recommendationSections = computed(() => {
  const recommendations = result.value?.recommendations || {}

  return [
    {
      key: "additionalActivities",
      label: t("Additional activities"),
      items: Array.isArray(recommendations.additionalActivities) ? recommendations.additionalActivities : [],
    },
    {
      key: "rhythm",
      label: t("Rhythm"),
      items: Array.isArray(recommendations.rhythm) ? recommendations.rhythm : [],
    },
    {
      key: "learningMethodologies",
      label: t("Learning methodologies"),
      items: Array.isArray(recommendations.learningMethodologies) ? recommendations.learningMethodologies : [],
    },
    {
      key: "misc",
      label: t("Other recommendations"),
      items: Array.isArray(recommendations.misc) ? recommendations.misc : [],
    },
  ]
})

function getErrorMessage(error) {
  return (
    error?.response?.data?.error ||
    error?.response?.data?.detail ||
    error?.response?.data?.message ||
    error?.message ||
    t("An error occurred")
  )
}

async function loadConfiguration() {
  if (!props.learner?.id || !props.courseId) {
    return
  }

  configurationLoading.value = true
  errorMessage.value = ""
  analysisCompleted.value = false
  messageSent.value = false

  try {
    const configuration = await studentSuccessService.getConfiguration(
      props.courseId,
      Number(props.learner.id),
      props.sessionId,
    )

    providers.value = Array.isArray(configuration.providers) ? configuration.providers : []
    const previousProvider = String(configuration.previousAnalysis?.metadata?.provider || "")
    selectedProvider.value = providers.value.includes(previousProvider)
      ? previousProvider
      : String(configuration.defaultProvider || providers.value[0] || "")
    courseAnalysisAvailable.value = Boolean(configuration.courseAnalysisAvailable)
    result.value = configuration.previousAnalysis?.analysis || null
    generatedAt.value = configuration.previousAnalysis?.generatedAt || null
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    configurationLoading.value = false
  }
}

async function analyzeLearner() {
  if (!props.learner?.id || !props.courseId || isAnalyzing.value) {
    return
  }

  errorMessage.value = ""
  analysisCompleted.value = false
  messageSent.value = false
  isAnalyzing.value = true

  try {
    if (!courseAnalysisAvailable.value) {
      statusMessage.value = t("Analyzing course...")
      await studentSuccessService.prepareCourse(
        props.courseId,
        Number(props.learner.id),
        props.sessionId,
        selectedProvider.value,
      )
      courseAnalysisAvailable.value = true
    }

    statusMessage.value = t("Analyzing student's learning...")
    const response = await studentSuccessService.analyze(
      props.courseId,
      Number(props.learner.id),
      props.sessionId,
      selectedProvider.value,
      teacherPrompt.value,
    )

    result.value = response.analysis || null
    generatedAt.value = new Date().toISOString()
    messageSent.value = Boolean(response.messageSent)
    analysisCompleted.value = true
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    isAnalyzing.value = false
    statusMessage.value = ""
  }
}

function formatDateTime(value) {
  if (!value) {
    return ""
  }

  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? String(value) : parsed.toLocaleString()
}

watch(
  () => visible.value,
  async (isVisible) => {
    if (isVisible) {
      teacherPrompt.value = ""
      await loadConfiguration()
    }
  },
)
</script>
