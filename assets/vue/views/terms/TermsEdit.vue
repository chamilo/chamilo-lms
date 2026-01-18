<template>
  <div class="terms-edit-view mb-8">
    <div class="w-full px-4 sm:px-6 lg:px-8">
      <Message
        :closable="false"
        class="mt-5"
        icon="pi pi-send"
        severity="info"
      >
        {{
          t(
            "Display a Terms & Conditions statement on the registration page, require visitor to accept the T&C to register",
          )
        }}
      </Message>

      <Message
        :closable="false"
        class="mt-2"
        icon="pi pi-exclamation-triangle"
        severity="warn"
      >
        {{
          t(
            "Please remember that modifications to the terms related to GDPR require you to report those modifications to third parties to whom you have provided personal information about your users, as stated in Article 19 of GDPR.",
          )
        }}
      </Message>

      <BaseToolbar
        showTopBorder
        class="mt-4"
      >
        <div class="w-full flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
          <div class="w-full lg:max-w-xl">
            <BaseSelect
              v-model="selectedLanguage"
              :options="languages"
              class="w-full mb-0"
              id="language-dropdown"
              :label="t('Language')"
              name="language"
              option-value="id"
              option-label="name"
              :placeholder="t('Please select a language')"
            />
          </div>

          <div class="flex items-center justify-start lg:justify-end gap-2 flex-nowrap">
            <BaseButton
              class="whitespace-nowrap"
              :label="t('Load')"
              icon="search"
              type="button"
              :disabled="!selectedLanguage || isLoading"
              @click="loadTermsByLanguage"
            />
            <BaseButton
              class="whitespace-nowrap"
              :label="t('All versions')"
              icon="back"
              type="secondary"
              :disabled="isLoading"
              @click="backToList"
            />
          </div>
        </div>
      </BaseToolbar>

      <div
        v-if="termsLoaded"
        class="mt-6"
      >
        <form
          @submit.prevent="saveTerms"
          class="grid grid-cols-12 gap-6"
        >
          <!-- Sidebar -->
          <aside class="col-span-12 lg:col-span-3">
            <div class="lg:sticky lg:top-4 space-y-4">
              <div class="rounded-2xl border border-gray-25 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm text-gray-60">{{ t("Version currently loaded") }}</div>
                    <div class="text-lg font-semibold text-gray-90">
                      {{ loadedVersion ?? t("None") }}
                    </div>
                  </div>

                  <div class="text-right">
                    <div class="text-sm text-gray-60">{{ t("Completion") }}</div>
                    <div class="text-lg font-semibold text-gray-90">{{ filledCount }}/16</div>
                  </div>
                </div>

                <div
                  v-if="isLoading"
                  class="mt-3 text-sm text-gray-60"
                >
                  {{ t("Loading...") }}
                </div>
              </div>

              <div class="rounded-2xl border border-gray-25 bg-white p-4 shadow-sm">
                <div class="text-sm font-semibold text-gray-90 mb-3">
                  {{ t("Quick navigation") }}
                </div>

                <div class="space-y-2">
                  <button
                    v-for="(section, idx) in sectionsDefinition"
                    :key="section.type"
                    type="button"
                    class="w-full flex items-center justify-between gap-2 rounded-xl border border-gray-25 px-3 py-2 text-left hover:bg-gray-15"
                    @click="focusSection(idx, section.type)"
                  >
                    <span class="text-sm text-gray-90 line-clamp-1"> {{ idx + 1 }}. {{ section.title }} </span>

                    <span
                      class="text-xs px-2 py-1 rounded-full"
                      :class="isSectionFilled(section.type) ? 'bg-green-100 text-green-700' : 'bg-gray-15 text-gray-60'"
                    >
                      {{ isSectionFilled(section.type) ? t("Filled") : t("Empty") }}
                    </span>
                  </button>
                </div>
              </div>
            </div>
          </aside>

          <!-- Main content -->
          <section class="col-span-12 lg:col-span-9 space-y-4">
            <div class="rounded-2xl border border-gray-25 bg-white shadow-sm overflow-hidden">
              <div class="p-4 border-b border-gray-25">
                <div class="text-base font-semibold text-gray-90">
                  {{ t("Terms sections") }}
                </div>
                <div class="text-sm text-gray-60">
                  {{ t("Open a section to edit its content.") }}
                </div>
              </div>

              <div class="p-4">
                <Accordion
                  class="terms-accordion"
                  :activeIndex="activeIndex"
                  @tab-open="onTabOpen"
                  :pt="{
                    root: { class: 'space-y-3' },
                    tab: { class: 'rounded-2xl border border-gray-25 overflow-hidden bg-white' },
                    headerAction: {
                      class: 'px-4 py-3 hover:bg-gray-15 transition flex items-start justify-between gap-4',
                    },
                    content: { class: 'px-4 py-4 border-t border-gray-25 bg-white' },
                  }"
                >
                  <AccordionTab
                    v-for="(section, idx) in sectionsDefinition"
                    :key="section.type"
                  >
                    <template #header>
                      <div class="w-full flex items-start justify-between gap-4">
                        <div class="min-w-0 flex items-start gap-3">
                          <span
                            class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-15 text-xs font-semibold text-gray-90"
                          >
                            {{ idx + 1 }}
                          </span>

                          <div class="min-w-0 flex flex-col">
                            <span class="font-semibold text-gray-90 truncate">
                              {{ section.title }}
                            </span>
                            <span
                              v-if="section.helpText"
                              class="text-xs text-gray-60 mt-1 line-clamp-2"
                            >
                              {{ section.helpText }}
                            </span>
                          </div>
                        </div>

                        <span
                          class="text-xs px-2 py-1 rounded-full whitespace-nowrap mt-0.5"
                          :class="
                            isSectionFilled(section.type) ? 'bg-green-100 text-green-700' : 'bg-gray-15 text-gray-60'
                          "
                        >
                          {{ isSectionFilled(section.type) ? t("Filled") : t("Empty") }}
                        </span>
                      </div>
                    </template>

                    <div
                      :id="`terms-section-${section.type}`"
                      class="pt-2 terms-section"
                    >
                      <div class="terms-editor">
                        <BaseTinyEditor
                          v-if="isEditorMounted(idx)"
                          v-model="termData.sections[section.type]"
                          :help-text="section.helpText"
                          :title="section.title"
                          :editor-id="`terms_section_${section.type}`"
                        />
                      </div>

                      <div
                        v-if="!isEditorMounted(idx)"
                        class="text-sm text-gray-60"
                      >
                        {{ t("Open this section to load the editor.") }}
                      </div>
                    </div>
                  </AccordionTab>
                </Accordion>
              </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-white p-4 shadow-sm">
              <BaseTextArea
                id="changes"
                v-model="termData.changes"
                :label="t('Explain changes')"
              />
            </div>

            <Dialog
              v-model:visible="dialogVisible"
              :header="t('Preview')"
              :modal="true"
              :style="{ width: 'min(950px, 92vw)' }"
            >
              <div
                class="space-y-4"
                v-html="previewContent"
              />
            </Dialog>

            <!-- Sticky actions -->
            <div class="sticky bottom-0 z-10">
              <div
                class="rounded-2xl border border-gray-25 bg-white/90 backdrop-blur p-3 shadow-sm flex flex-wrap gap-2 justify-end"
              >
                <BaseButton
                  icon="back"
                  :label="t('Back')"
                  type="secondary"
                  :disabled="isLoading"
                  @click="backToList"
                />
                <BaseButton
                  icon="search"
                  :label="t('Preview')"
                  type="primary"
                  :disabled="isLoading"
                  @click="previewTerms"
                />
                <BaseButton
                  icon="save"
                  :label="t('Save')"
                  type="success"
                  isSubmit
                  :disabled="isLoading || isSaving || !selectedLanguage"
                />
              </div>
            </div>
          </section>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed, watch } from "vue"
import { useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import Message from "primevue/message"
import Dialog from "primevue/dialog"
import Accordion from "primevue/accordion"
import AccordionTab from "primevue/accordiontab"

import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"

import languageService from "../../services/languageService"
import legalService from "../../services/legalService"

const { t } = useI18n()
const router = useRouter()

const languages = ref([])
const selectedLanguage = ref(null)

const termsLoaded = ref(false)
const loadedVersion = ref(null)

const isLoading = ref(false)

const dialogVisible = ref(false)
const previewContent = ref("")

const activeIndex = ref(0)
const mountedEditorIndexes = ref(new Set([0]))
const isSaving = ref(false)

const buildEmptySections = () => {
  const sections = {}
  for (let i = 0; i <= 15; i++) sections[i] = ""
  return sections
}

const termData = ref({
  changes: "",
  sections: buildEmptySections(),
})

const sectionsDefinition = computed(() => [
  { type: 0, title: t("Terms and Conditions"), helpText: "" },

  { type: 1, title: t("Personal data collection"), helpText: t("Why do we collect this data?") },
  { type: 2, title: t("Personal data recording"), helpText: t("Where do we record the data? (in which system, hosted where, accessible by which company that handles the servers, etc.)") },
  { type: 3, title: t("Personal data organization"), helpText: t("How is the data structured? To what extent does the organization of the data protect your security? ... Do you have established processes that ensure that a hacker will not be able to collect all the personal data on the first attempt? Are they encrypted?") },
  { type: 4, title: t("Personal data structure"), helpText: t("How is the data structured in this software? (one tab for all the data, it has some categorization / label, ...)") },
  { type: 5, title: t("Personal data conservation"), helpText: t("How long do we save the data? Is there data that expires even though the account is still active? How much time do we keep the data after the last use of the system?") },
  {
    type: 6,
    title: t("Personal data adaptation or modification"),
    helpText: t("What changes can we make to the data? What changes can be made to the data without affecting the service?"),
  },
  { type: 7, title: t("Personal data extraction"), helpText: t("What do we extract data for (towards other internal processes) and which data is it?") },
  { type: 8, title: t("Personal data queries"), helpText: t("Who can consult the personal data? For what purpose?") },
  { type: 9, title: t("Personal data use"), helpText: t("How and for what can we use the personal data?") },
  { type: 10, title: t("Personal data communication and sharing"), helpText: t("With whom can we share them? In what opportunities? Through what processes (safe or not)?") },
  {
    type: 11,
    title: t("Personal data interconnection"),
    helpText: t("Do we have another system with which Chamilo interacts? What is the interconnection process? What are the data pieces exchanged and in what way?"),
  },
  { type: 12, title: t("Personal data limitation"), helpText: t("What are the limits that we will always respect when using personal data? How far can we go?") },
  { type: 13, title: t("Personal data deletion"), helpText: t("After how long do we erase the data? (event, last use, contract validity, etc.) What are the elimination processes?") },
  {
    type: 14,
    title: t("Personal data destruction"),
    helpText: t("What happens if the data is destroyed as a result of a technical failure? (unauthorized deletion or loss of material, for example)"),
  },
  { type: 15, title: t("Personal data profiling"), helpText: t("For what purpose do we process personal data? Do we use it to filter the access users have to certain parts of our application? (negative or positive discrimination)") },
])

const normalizeHtmlToText = (html) => {
  const raw = String(html ?? "")
  return raw
    .replace(/<[^>]*>/g, " ")
    .replace(/&nbsp;/g, " ")
    .replace(/\s+/g, " ")
    .trim()
}

const isSectionFilled = (type) => {
  const html = termData.value.sections?.[type] ?? ""
  return normalizeHtmlToText(html).length > 0
}

const filledCount = computed(() => {
  return sectionsDefinition.value.filter((s) => isSectionFilled(s.type)).length
})

const onTabOpen = (e) => {
  const idx = Number(e.index)
  if (!Number.isNaN(idx)) {
    activeIndex.value = idx
    mountedEditorIndexes.value.add(idx)
  }
}

const isEditorMounted = (idx) => mountedEditorIndexes.value.has(idx)

const focusSection = (idx, type) => {
  activeIndex.value = idx
  mountedEditorIndexes.value.add(idx)

  requestAnimationFrame(() => {
    const el = document.getElementById(`terms-section-${type}`)
    if (el) el.scrollIntoView({ behavior: "smooth", block: "start" })
  })
}

const loadTermsByLanguage = async () => {
  if (!selectedLanguage.value) return

  isLoading.value = true
  termsLoaded.value = false
  loadedVersion.value = null
  activeIndex.value = 0
  mountedEditorIndexes.value = new Set([0])

  try {
    const latestRes = await legalService.findLatestByLanguage(selectedLanguage.value)
    const latestJson = latestRes.ok ? await latestRes.json() : null
    const latestItem = latestJson?.["hydra:member"]?.length ? latestJson["hydra:member"][0] : null

    if (!latestItem?.version) {
      termData.value = { changes: "", sections: buildEmptySections() }
      loadedVersion.value = null
      return
    }

    loadedVersion.value = latestItem.version

    const res = await legalService.findByLanguageAndVersion(selectedLanguage.value, latestItem.version)
    if (!res.ok) {
      console.error("Failed to load terms by language and version.")
      termData.value = { changes: "", sections: buildEmptySections() }
      loadedVersion.value = null
      return
    }

    const data = await res.json()
    const items = data?.["hydra:member"] ?? []

    const sections = buildEmptySections()
    let changes = ""

    for (const row of items) {
      const rowType = Number(row.type)
      if (!Number.isNaN(rowType) && rowType >= 0 && rowType <= 15) {
        sections[rowType] = row.content ?? ""
      }
      if (!changes && row.changes) {
        changes = row.changes
      }
    }

    termData.value = { changes, sections }
  } catch (error) {
    console.error("Error loading terms:", error)
    termData.value = { changes: "", sections: buildEmptySections() }
    loadedVersion.value = null
  } finally {
    termsLoaded.value = true
    isLoading.value = false
  }
}
const saveTerms = async () => {
  if (!selectedLanguage.value) return
  if (isSaving.value) return

  isSaving.value = true

  const payload = {
    lang: selectedLanguage.value,
    changes: termData.value.changes ?? "",
    sections: termData.value.sections ?? buildEmptySections(),
  }

  try {
    const response = await legalService.saveOrUpdateLegal(payload)
    if (response.ok) {
      await router.push({ name: "TermsConditionsList" })
    } else {
      console.error("Failed to save legal terms.")
    }
  } catch (error) {
    console.error("Error while saving legal terms:", error)
  } finally {
    isSaving.value = false
  }
}

const previewTerms = () => {
  const parts = []
  for (const section of sectionsDefinition.value) {
    const html = termData.value.sections?.[section.type] ?? ""
    parts.push(`<h3>${section.title}</h3>`)
    if (section.helpText) {
      parts.push(`<p><em>${section.helpText}</em></p>`)
    }
    parts.push(`<div>${html}</div>`)
    parts.push("<hr/>")
  }
  previewContent.value = parts.join("")
  dialogVisible.value = true
}
function backToList() {
  router.push({ name: "TermsConditionsList" })
}

watch(selectedLanguage, () => {
  termsLoaded.value = false
  loadedVersion.value = null
  termData.value = { changes: "", sections: buildEmptySections() }
  activeIndex.value = 0
  mountedEditorIndexes.value = new Set([0])
})
onMounted(async () => {
  try {
    const response = await languageService.findAll()
    if (!response.ok) throw new Error("Failed to load languages.")
    const data = await response.json()
    languages.value = data["hydra:member"].map((lang) => ({
      name: lang.englishName,
      id: lang.id,
    }))
  } catch (error) {
    console.error("Error loading languages:", error)
  }
})
</script>
<style scoped>
/* Give each section a better scroll position (sticky action bar / header) */
.terms-section {
  scroll-margin-top: 90px;
}

/* Make TinyMCE taller */
:deep(.terms-editor .tox.tox-tinymce) {
  height: clamp(420px, 60vh, 760px) !important;
}

:deep(.terms-editor .tox .tox-edit-area),
:deep(.terms-editor .tox .tox-edit-area__iframe) {
  height: 100% !important;
}
/* Accordion: clearer separation and active state */
:deep(.terms-accordion .p-accordion-header-link) {
  background: rgb(249 250 251); /* gray-50 */
}

:deep(.terms-accordion .p-accordion-tab-active .p-accordion-header-link) {
  background: rgb(239 246 255); /* blue-50 */
}

:deep(.terms-accordion .p-accordion-header-link:hover) {
  background: rgb(243 244 246); /* gray-100 */
}

:deep(.terms-accordion .p-accordion-content) {
  background: white;
}
</style>
