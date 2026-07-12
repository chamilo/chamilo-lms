<template>
  <div class="flex w-full flex-col gap-6">
    <SectionHeader :title="t('AI generator')" />

    <div
      v-if="loading"
      class="p-6 text-center"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="loadError"
      class="rounded-lg border border-danger/30 bg-danger/10 p-4 text-danger"
    >
      {{ t("An error occurred") }}
    </div>

    <div
      v-else-if="!configuration.enabled"
      class="rounded-lg border border-warning/30 bg-warning/10 p-4 text-warning-dark"
    >
      {{ t("AI learning path generator") }}: {{ t("Disabled") }}
    </div>

    <div
      v-else-if="configuration.providers.length === 0"
      class="rounded-lg border border-warning/30 bg-warning/10 p-4 text-warning-dark"
    >
      {{ t("No AI text providers configured.") }}
    </div>

    <form
      v-else
      class="mx-auto flex w-full max-w-6xl flex-col gap-5"
      @submit.prevent="generate"
    >
      <div class="rounded-2xl border border-gray-25 bg-white p-5">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div class="flex items-center gap-4">
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 text-primary"
            >
              <BaseIcon icon="robot" />
            </div>

            <div class="flex flex-col gap-1">
              <h2 class="text-lg font-semibold text-gray-90">
                {{ t("AI learning path generator") }}
              </h2>
              <span class="text-sm text-gray-50">
                {{ t("Language") }}: {{ configuration.language }}
              </span>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <span
              class="inline-flex items-center gap-2 rounded-full border border-gray-25 bg-gray-15 px-3 py-1 text-xs"
            >
              <span class="font-semibold">1</span>
              {{ t("Content") }}
            </span>
            <span
              class="mdi mdi-arrow-right text-gray-40"
              aria-hidden="true"
            />
            <span
              class="inline-flex items-center gap-2 rounded-full border border-gray-25 bg-gray-15 px-3 py-1 text-xs"
            >
              <span class="font-semibold">2</span>
              {{ t("Generate") }}
            </span>
            <span
              class="mdi mdi-arrow-right text-gray-40"
              aria-hidden="true"
            />
            <span
              class="inline-flex items-center gap-2 rounded-full border border-gray-25 bg-gray-15 px-3 py-1 text-xs"
            >
              <span class="font-semibold">3</span>
              {{ t("Learning path") }}
            </span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
        <div class="flex flex-col gap-5">
          <section class="rounded-2xl border border-gray-25 bg-white p-5">
            <h2 class="mb-5 text-lg font-semibold text-gray-90">
              {{ t("General") }}
            </h2>

            <div class="flex flex-col gap-5">
              <BaseInputText
                id="lp-ai-topic"
                v-model="form.topic"
                name="lp_name"
                :error-text="t('Required field')"
                :form-submitted="formSubmitted"
                :is-invalid="topicInvalid"
                :label="t('Topic')"
                maxlength="255"
                required
              />

              <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <BaseInputNumber
                  id="lp-ai-items"
                  v-model="form.items"
                  :error-text="t('Please enter a valid number')"
                  :is-invalid="itemsInvalid"
                  :label="t('Number of items')"
                  :min="1"
                />

                <BaseInputNumber
                  id="lp-ai-words"
                  v-model="form.words"
                  :error-text="t('Please enter a valid word count')"
                  :is-invalid="wordsInvalid"
                  :label="t('Words count per page')"
                  :min="1"
                />
              </div>
            </div>
          </section>

          <section class="rounded-2xl border border-gray-25 bg-white p-5">
            <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
              <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                  <BaseCheckbox
                    id="lp-ai-add-tests"
                    v-model="form.addTests"
                    name="add_lp_quiz"
                    :label="t('Add test after each page')"
                  />

                  <BaseInputNumber
                    v-if="form.addTests"
                    id="lp-ai-questions"
                    v-model="form.questions"
                    :error-text="questionsError"
                    :is-invalid="questionsInvalid"
                    :label="t('Number of questions')"
                    :max="5"
                    :min="1"
                  />
                </div>

                <BaseSelect
                  v-if="configuration.providers.length > 1"
                  id="lp-ai-provider"
                  v-model="form.provider"
                  name="ai_provider"
                  :is-invalid="providerInvalid"
                  :label="t('AI provider')"
                  :message-text="providerInvalid ? t('Required field') : null"
                  :options="configuration.providers"
                  option-label="label"
                  option-value="value"
                />
              </div>
            </BaseAdvancedSettingsButton>
          </section>

          <div class="flex flex-wrap items-center justify-end gap-3">
            <BaseButton
              :disabled="isGenerating"
              :label="t('Back')"
              icon="back"
              type="black"
              @click="goBack"
            />

            <BaseButton
              id="create-lp-ai"
              name="create_lp_button"
              :disabled="isGenerating"
              icon="robot"
              :is-loading="isGenerating"
              is-submit
              :label="isGenerating ? t('Please wait, this could take a while...') : t('Generate')"
              type="primary"
            />
          </div>
        </div>

        <aside class="h-fit rounded-2xl border border-gray-25 bg-white p-5 lg:sticky lg:top-4">
          <h2 class="mb-5 text-lg font-semibold text-gray-90">
            {{ t("Details") }}
          </h2>

          <dl class="flex flex-col gap-4 text-sm">
            <div class="flex items-start justify-between gap-4 border-b border-gray-20 pb-3">
              <dt class="text-gray-50">{{ t("Content") }}</dt>
              <dd class="font-semibold text-gray-90">{{ form.items }}</dd>
            </div>

            <div class="flex items-start justify-between gap-4 border-b border-gray-20 pb-3">
              <dt class="text-gray-50">{{ t("Words count per page") }}</dt>
              <dd class="font-semibold text-gray-90">{{ form.words }}</dd>
            </div>

            <div class="flex items-start justify-between gap-4 border-b border-gray-20 pb-3">
              <dt class="text-gray-50">{{ t("Exercise") }}</dt>
              <dd class="font-semibold text-gray-90">
                {{ form.addTests ? t("Yes") : t("No") }}
              </dd>
            </div>

            <div
              v-if="form.addTests"
              class="flex items-start justify-between gap-4 border-b border-gray-20 pb-3"
            >
              <dt class="text-gray-50">{{ t("Questions") }}</dt>
              <dd class="font-semibold text-gray-90">{{ form.questions }}</dd>
            </div>

            <div class="flex items-start justify-between gap-4 border-b border-gray-20 pb-3">
              <dt class="text-gray-50">{{ t("AI provider") }}</dt>
              <dd class="max-w-48 text-right font-semibold text-gray-90">
                {{ selectedProviderLabel }}
              </dd>
            </div>

            <div class="flex items-start justify-between gap-4">
              <dt class="text-gray-50">{{ t("Language") }}</dt>
              <dd class="font-semibold text-gray-90">
                {{ configuration.language }}
              </dd>
            </div>
          </dl>
        </aside>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import lpService from "../../services/lpService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { showErrorNotification } = useNotification()

const loading = ref(true)
const loadError = ref(false)
const isGenerating = ref(false)
const formSubmitted = ref(false)
const showAdvancedSettings = ref(false)

const configuration = reactive({
  enabled: false,
  language: "en",
  providers: [],
  csrfToken: "",
})

const form = reactive({
  topic: "",
  items: 3,
  words: 180,
  addTests: false,
  questions: 2,
  provider: "",
})

const contextParams = computed(() => ({
  cid: Number(route.query.cid ?? 0),
  sid: Number(route.query.sid ?? 0),
  gid: Number(route.query.gid ?? 0),
  node: Number(route.params.node ?? 0),
  isStudentView: "false",
}))

const selectedProviderLabel = computed(() => {
  const selected = configuration.providers.find((provider) => provider.value === form.provider)

  return selected?.label || form.provider || "-"
})

const topicInvalid = computed(() => formSubmitted.value && form.topic.trim() === "")
const itemsInvalid = computed(() => formSubmitted.value && (!Number.isInteger(form.items) || form.items <= 0))
const wordsInvalid = computed(() => formSubmitted.value && (!Number.isInteger(form.words) || form.words <= 0))
const questionsInvalid = computed(
  () =>
    formSubmitted.value &&
    form.addTests &&
    (!Number.isInteger(form.questions) || form.questions <= 0 || form.questions > 5),
)
const providerInvalid = computed(
  () => formSubmitted.value && configuration.providers.length > 1 && form.provider === "",
)
const questionsError = computed(() =>
  t("Number of questions limited to a maximum of %d").replace("%d", "5"),
)

onMounted(loadConfiguration)

async function loadConfiguration() {
  loading.value = true
  loadError.value = false

  try {
    const data = await lpService.getAiGeneratorConfiguration(contextParams.value)

    configuration.enabled = Boolean(data?.enabled)
    configuration.language = String(data?.language || "en")
    configuration.providers = Array.isArray(data?.providers) ? data.providers : []
    configuration.csrfToken = String(data?.csrfToken || "")

    if (configuration.providers.length > 0) {
      form.provider = String(configuration.providers[0].value || "")
    }
  } catch (error) {
    loadError.value = true
    showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

function validate() {
  formSubmitted.value = true

  return !(
    topicInvalid.value ||
    itemsInvalid.value ||
    wordsInvalid.value ||
    questionsInvalid.value ||
    providerInvalid.value
  )
}

async function generate() {
  if (!validate() || isGenerating.value) {
    return
  }

  isGenerating.value = true

  try {
    const generated = await lpService.generateAiLearningPath(contextParams.value, {
      lp_name: form.topic.trim(),
      nro_items: form.items,
      words_count: form.words,
      language: configuration.language,
      add_tests: form.addTests,
      nro_questions: form.questions,
      ai_provider: form.provider,
    })

    if (!generated?.success || !generated?.data) {
      throw new Error(generated?.text || t("No results found"))
    }

    const saved = await lpService.saveAiLearningPath(contextParams.value, {
      lpData: generated.data,
      csrfToken: configuration.csrfToken,
    })

    const lpId = Number(saved?.id ?? 0)
    if (lpId <= 0) {
      throw new Error(t("Invalid server response"))
    }

    await router.push({
      name: "LpBuilder",
      params: {
        node: Number(route.params.node),
        lpId,
      },
      query: {
        ...route.query,
        isStudentView: "false",
      },
    })
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isGenerating.value = false
  }
}

function goBack() {
  router.push({
    name: "LpList",
    params: {
      node: Number(route.params.node),
    },
    query: route.query,
  })
}
</script>
