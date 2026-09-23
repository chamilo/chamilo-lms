<template>
  <div class="flex w-full flex-col gap-6">
    <SectionHeader
      :show-student-view-button="false"
      :title="t('AI Toolbox')"
    >
      <BaseButton
        :label="t('Back')"
        icon="back"
        only-icon
        type="black"
        @click="cancel"
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

    <div
      v-else-if="!configuration.enabled"
      class="rounded-lg border border-warning/30 bg-warning/10 p-4 text-warning-dark"
    >
      {{ t("AI Toolbox") }}: {{ t("Disabled") }}
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
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 text-primary">
              <BaseIcon icon="robot" />
            </div>
            <div>
              <h2 class="text-lg font-semibold text-gray-90">{{ t("Create an interactive learning app") }}</h2>
              <p class="mt-1 text-sm text-gray-50">
                {{ t("Describe the activity. Chamilo will generate a versioned SCORM application with progress and score tracking.") }}
              </p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-2 rounded-full border border-gray-25 bg-gray-15 px-3 py-1">
              <strong>1</strong> {{ t("Idea") }}
            </span>
            <span class="mdi mdi-arrow-right rtl:rotate-180 text-gray-40" aria-hidden="true" />
            <span class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-primary">
              <strong>2</strong> {{ t("Generate") }}
            </span>
            <span class="mdi mdi-arrow-right rtl:rotate-180 text-gray-40" aria-hidden="true" />
            <span class="inline-flex items-center gap-2 rounded-full border border-gray-25 bg-gray-15 px-3 py-1">
              <strong>3</strong> {{ t("Preview") }}
            </span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
        <div class="flex flex-col gap-5">
          <section class="rounded-2xl border border-gray-25 bg-white p-5">
            <h2 class="mb-5 text-lg font-semibold text-gray-90">{{ t("Application") }}</h2>
            <div class="flex flex-col gap-5">
              <BaseInputText
                id="toolbox-title"
                v-model="form.title"
                name="toolbox_title"
                :error-text="t('Required field')"
                :form-submitted="formSubmitted"
                :is-invalid="titleInvalid"
                :label="t('Title')"
                maxlength="255"
                required
              />

              <BaseTextArea
                id="toolbox-description"
                v-model="form.description"
                name="toolbox_description"
                :label="t('Description')"
                :rows="3"
              />

              <BaseTextArea
                id="toolbox-prompt"
                v-model="form.prompt"
                name="toolbox_prompt"
                :error-text="t('Required field')"
                :form-submitted="formSubmitted"
                :is-invalid="promptInvalid"
                :label="t('What should the application do?')"
                :rows="10"
                required
              />

              <BaseSelect
                v-if="configuration.providers.length > 1"
                id="toolbox-provider"
                v-model="form.provider"
                name="toolbox_provider"
                :is-invalid="providerInvalid"
                :label="t('AI provider')"
                :message-text="providerInvalid ? t('Required field') : null"
                :options="configuration.providers"
                option-label="label"
                option-value="value"
              />
            </div>
          </section>

          <div class="flex flex-wrap justify-end gap-3">
            <BaseButton
              :disabled="generating"
              :label="t('Back')"
              icon="back"
              type="black"
              @click="cancel"
            />
            <BaseButton
              id="generate-toolbox-app"
              name="generate_toolbox_app"
              :disabled="generating"
              icon="robot"
              :is-loading="generating"
              is-submit
              :label="generating ? t('Please wait, this could take a while...') : t('Generate')"
              type="primary"
            />
          </div>
        </div>

        <aside class="h-fit rounded-2xl border border-gray-25 bg-white p-5 lg:sticky lg:top-4">
          <h2 class="mb-4 text-lg font-semibold text-gray-90">{{ t("How it works") }}</h2>
          <div class="flex flex-col gap-4 text-sm text-gray-60">
            <div class="flex gap-3">
              <BaseIcon class="mt-0.5 shrink-0 text-primary" icon="robot" />
              <div>
                <strong class="block text-gray-90">{{ t("AI generation") }}</strong>
                <span>{{ t("The app is generated from your instructions and stored as version 1.") }}</span>
              </div>
            </div>
            <div class="flex gap-3">
              <BaseIcon class="mt-0.5 shrink-0 text-primary" icon="play-box-outline" />
              <div>
                <strong class="block text-gray-90">SCORM 1.2</strong>
                <span>{{ t("Learner progress, score and resume data are reported to Chamilo.") }}</span>
              </div>
            </div>
            <div class="flex gap-3">
              <BaseIcon class="mt-0.5 shrink-0 text-primary" icon="restore" />
              <div>
                <strong class="block text-gray-90">{{ t("Versions") }}</strong>
                <span>{{ t("Future improvements create new versions without deleting previous ones.") }}</span>
              </div>
            </div>
          </div>

          <div class="mt-5 rounded-xl border border-gray-25 bg-gray-15 p-4 text-xs leading-5 text-gray-60">
            <strong class="block text-gray-90">{{ t("Tip") }}</strong>
            {{ t("Include the learning objective, rules, levels, scoring and what should happen when the learner finishes.") }}
          </div>
        </aside>
      </div>
    </form>
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
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
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
const formSubmitted = ref(false)
const configuration = reactive({ enabled: false, canCreate: false, providers: [] })
const form = reactive({ title: "", description: "", prompt: "", provider: "" })

const context = computed(() => ({
  cid: course.value?.id ?? Number(route.query.cid ?? 0),
  sid: session.value?.id ?? Number(route.query.sid ?? 0),
  gid: Number(route.query.gid ?? 0),
}))

const titleInvalid = computed(() => formSubmitted.value && form.title.trim() === "")
const promptInvalid = computed(() => formSubmitted.value && form.prompt.trim() === "")
const providerInvalid = computed(
  () => formSubmitted.value && configuration.providers.length > 1 && form.provider.trim() === "",
)

onMounted(loadConfiguration)

async function loadConfiguration() {
  loading.value = true
  loadError.value = false
  try {
    const data = await toolboxService.getItems(context.value)
    configuration.enabled = data?.enabled === true
    configuration.canCreate = data?.canCreate === true
    configuration.providers = Array.isArray(data?.providers) ? data.providers : []
    if (configuration.providers.length === 1) {
      form.provider = configuration.providers[0].value
    }
  } catch (error) {
    loadError.value = true
    showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

async function generate() {
  formSubmitted.value = true
  if (titleInvalid.value || promptInvalid.value || providerInvalid.value) return
  if (!configuration.canCreate) {
    showErrorNotification(t("You are not allowed to create content in this context."))
    return
  }

  generating.value = true
  try {
    const item = await toolboxService.createItem(context.value, {
      title: form.title.trim(),
      description: form.description.trim(),
      prompt: form.prompt.trim(),
      provider: form.provider || null,
    })
    showSuccessNotification(t("The application was generated successfully."))
    await router.push({
      name: "ToolboxDetail",
      params: { node: route.params.node, itemId: item.id },
      query: route.query,
    })
  } catch (error) {
    showErrorNotification(error)
  } finally {
    generating.value = false
  }
}

function cancel() {
  router.push({ name: "ToolboxList", params: { node: route.params.node }, query: route.query })
}
</script>
