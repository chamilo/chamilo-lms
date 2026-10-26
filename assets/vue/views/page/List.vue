<template>
  <BaseTable
    v-if="securityStore.isAdmin"
    v-model:filters="filters"
    v-model:selected-items="selectedItems"
    :is-loading="isLoading"
    :rows="options.itemsPerPage"
    :total-items="totalItems"
    :values="items"
    data-key="@id"
    lazy
    @page="onPage"
    @sort="sortingChanged"
  >
    <Column
      :exportable="false"
      selection-mode="multiple"
    />
    <Column
      :header="t('Title')"
      :sortable="true"
      field="title"
    >
      <template #body="slotProps">
        <a
          v-if="slotProps.data"
          class="cursor-pointer"
          @click="onShowItem(slotProps.data)"
        >
          {{ slotProps.data.title }}
        </a>
      </template>
    </Column>

    <Column
      :header="t('Language')"
      field="locale"
    >
      <template #body="{ data }">
        {{ ensureLanguageName(data?.locale) }}
      </template>
    </Column>
    <Column
      :header="t('Category')"
      field="category.title"
    />
    <Column
      :header="t('Enabled')"
      field="enabled"
    >
      <template #body="slotProps">
        <span
          v-if="slotProps.data.enabled"
          v-text="t('Yes')"
        />
        <span
          v-else
          v-text="t('No')"
        />
      </template>
    </Column>

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="text-end space-x-2 rtl:space-x-reverse">
          <BaseButton
            v-if="securityStore.isAuthenticated"
            :label="t('Edit')"
            icon="edit"
            only-icon
            size="small"
            type="secondary-text"
            @click="goToEditItem(slotProps.data)"
          />

          <BaseButton
            v-if="securityStore.isAuthenticated"
            :label="t('Delete')"
            icon="delete"
            only-icon
            name="cms-page-delete-open"
            size="small"
            type="danger-text"
            @click="confirmDeleteItem(slotProps.data)"
          />

          <BaseButton
            v-if="slotProps.data.enabled && slotProps.data.slug"
            :label="t('Show public link')"
            icon="link"
            only-icon
            size="small"
            type="tertiary-text"
            @click="showPublicLinkDialog(slotProps.data)"
          />
        </div>
      </template>
    </Column>
  </BaseTable>

  <div
    v-if="securityStore.isAdmin && selectedItems.length"
    class="mt-3 flex items-center justify-end gap-3"
  >
    <span>{{ t("Selected pages to delete:") }} {{ selectedItems.length }}</span>
    <BaseButton
      :label="t('Delete selected pages')"
      icon="delete"
      type="danger"
      name="cms-pages-bulk-delete-open"
      @click="openBulkDeleteDialog"
    />
  </div>

  <Dialog
    v-model:visible="itemDialog"
    :header="t('New folder')"
    :modal="true"
    :style="{ width: '450px' }"
    class="p-fluid"
  >
    <div class="field">
      <FloatLabel variant="on">
        <InputText
          id="title"
          v-model.trim="item.title"
          :class="{ 'p-invalid': submitted && !item.title }"
          autocomplete="off"
          autofocus
          required="true"
        />
        <label
          v-text="t('Name')"
          for="title"
        />
      </FloatLabel>
      <small
        v-if="submitted && !item.title"
        v-text="t('Title is required')"
        class="p-error"
      />
    </div>

    <template #footer>
      <BaseButton
        v-if="securityStore.isAuthenticated"
        :label="t('Edit')"
        icon="edit"
        size="small"
        type="teratiry"
        @click="goToEditItem(slotProps.data)"
      />

      <BaseButton
        v-if="securityStore.isAuthenticated"
        :label="t('Delete')"
        icon="delete"
        size="small"
        type="danger"
        @click="confirmDeleteItem(slotProps.data)"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="deletePagesDialog"
    :header="t('Delete CMS pages')"
    :modal="true"
    :closable="!isDeletingLanguages"
    :style="{ width: 'min(90vw, 650px)' }"
    @hide="onDeletePagesDialogHide"
  >
    <p class="mb-2 font-semibold">{{ t("The following page will be deleted:") }}</p>
    <p class="mb-3">{{ item.title }} ({{ ensureLanguageName(item.locale) }})</p>

    <p class="mb-3 text-sm">
      {{ t("Pages in different languages are not automatically linked. Select only related pages.") }}
    </p>

    <p class="mb-2 font-semibold">{{ t("Select additional pages from other languages") }}</p>
    <p v-if="isLoadingLanguagePages">{{ t("Loading") }}</p>
    <p
      v-else-if="languageCandidatesLoadFailed"
      class="text-sm"
    >
      {{ t("Other language pages could not be loaded. You can still delete only the current page.") }}
    </p>
    <div
      v-else-if="languageDeleteCandidates.length"
      class="max-h-80 overflow-y-auto space-y-2"
    >
      <BaseCheckbox
        v-for="candidate in languageDeleteCandidates"
        :id="`page-language-delete-${candidate.id}`"
        :key="candidate.id"
        v-model="selectedLanguagePageIds"
        :name="`page-language-delete-${candidate.id}`"
        :label="`${candidate.title} (${ensureLanguageName(candidate.locale)})`"
        :value="candidate.id"
        :disabled="isDeletingLanguages"
      />
    </div>
    <p v-else>{{ t("No other language pages were found in this category.") }}</p>

    <p class="mt-3 text-sm font-semibold">{{ t("Selected pages to delete:") }} {{ selectedLanguagePageIds.length }}</p>

    <template #footer>
      <BaseButton
        :label="t('Cancel')"
        name="cms-page-delete-cancel"
        type="secondary-text"
        :disabled="isDeletingLanguages"
        @click="deletePagesDialog = false"
      />
      <BaseButton
        :label="t('Delete')"
        icon="delete"
        name="cms-page-delete-confirm"
        type="danger"
        :disabled="!canDeleteLanguagePages || isLoadingLanguagePages || isDeletingLanguages"
        :is-loading="isDeletingLanguages"
        @click="deleteSelectedLanguagePages"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="bulkDeleteDialog"
    :header="t('Delete selected pages')"
    :modal="true"
    :closable="!isBulkDeleting"
    :style="{ width: 'min(90vw, 650px)' }"
  >
    <p class="mb-3">{{ t("The following pages will be deleted:") }}</p>
    <ul class="list-disc ps-5 mb-3 max-h-72 overflow-y-auto">
      <li
        v-for="page in bulkDeletePages"
        :key="page['@id']"
        class="mb-1"
      >
        {{ page.title }} ({{ ensureLanguageName(page.locale) }} · {{ page.category?.title || "-" }})
      </li>
    </ul>
    <p class="text-sm font-semibold">{{ t("Selected pages to delete:") }} {{ bulkDeletePages.length }}</p>
    <template #footer>
      <BaseButton
        :label="t('Cancel')"
        type="secondary-text"
        :disabled="isBulkDeleting"
        name="cms-pages-bulk-delete-cancel"
        @click="bulkDeleteDialog = false"
      />
      <BaseButton
        :label="t('Delete selected pages')"
        icon="delete"
        type="danger"
        name="cms-pages-bulk-delete-confirm"
        :disabled="isBulkDeleting"
        :is-loading="isBulkDeleting"
        @click="deleteBulkPages"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="publicLinkDialogVisible"
    :header="t('Public link')"
    :modal="true"
    :style="{ width: '500px' }"
  >
    <div class="text-center">
      <p class="text-sm mb-2">{{ t("You can share or copy this link:") }}</p>
      <InputText
        v-model="publicLink"
        readonly
        class="w-full mb-3 cursor-pointer"
        @focus="$event.target.select()"
      />
      <p class="text-xs text-gray-500 italic">{{ t("Select the link above and press Ctrl+C to copy it") }}</p>
    </div>
  </Dialog>

  <Message
    v-if="securityStore.isAdmin"
    :closable="false"
    class="mt-5"
    icon="mdi mdi-information"
    severity="info"
  >
    {{
      t(
        "Pages are HTTP-cached for 60s by default, as defined in the Page entity, so changes might not reflect immediately.",
      )
    }}
  </Message>
</template>

<script setup>
defineOptions({ name: "PageList" })

import { useStore } from "vuex"
import { useDatatableList } from "../../composables/datatableList"
import { computed, inject, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import { useSecurityStore } from "../../store/securityStore"
import { useRouter } from "vue-router"
import { useLocale } from "../../composables/locale"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import pageService from "../../services/pageService"
import FloatLabel from "primevue/floatlabel"
import Message from "primevue/message"

const router = useRouter()
const store = useStore()
const securityStore = useSecurityStore()

const { t } = useI18n()

const { filters, options, onUpdateOptions, onShowItem, goToEditItem, deleteItem } = useDatatableList("Page")
const { getLanguageName, fetchLanguageNameFromApi } = useLocale()
const langMap = reactive({})

function ensureLanguageName(iso) {
  if (!iso) return "-"
  if (!langMap[iso]) {
    langMap[iso] = getLanguageName(iso)
    fetchLanguageNameFromApi(iso)
      .then((name) => {
        if (name) langMap[iso] = name
      })
      .catch(() => {})
  }
  return langMap[iso]
}

const { showSuccessNotification, showErrorNotification } = useNotification()

const layoutMenuItems = inject("layoutMenuItems")

onMounted(() => {
  const { page, itemsPerPage } = router.currentRoute.value.query

  if (page) options.value.page = parseInt(page)
  if (itemsPerPage) options.value.itemsPerPage = parseInt(itemsPerPage)

  filters.value.loadNode = 0

  onUpdateOptions(options.value)

  if (securityStore.isAdmin) {
    layoutMenuItems.value = [
      {
        label: t("New page"),
        url: router.resolve({ name: "PageCreate" }).href,
      },
    ]
  }
})

const items = computed(() => store.state["page"].recents)
const isLoading = computed(() => store.state["page"].isLoading)
const totalItems = computed(() => store.state["page"].totalItems)

const selectedItems = ref([])
const itemDialog = ref(false)
const deletePagesDialog = ref(false)
const bulkDeleteDialog = ref(false)
const bulkDeletePages = ref([])
const isBulkDeleting = ref(false)
const item = ref({})
const submitted = ref(false)

const onPage = (event) => {
  options.value.itemsPerPage = event.rows
  options.value.page = event.page + 1
  options.value.sortBy = event.sortField
  options.value.sortDesc = event.sortOrder === -1

  onUpdateOptions(options.value)
}

const sortingChanged = (event) => {
  options.value.sortBy = event.sortField
  options.value.sortDesc = event.sortOrder === -1

  onUpdateOptions(options.value)
}

function openBulkDeleteDialog() {
  const pages = selectedItems.value.filter((page) => getPageId(page))
  if (!pages.length || pages.length > 100) {
    showErrorNotification(t("A maximum of 100 pages can be deleted at once."))
    return
  }

  bulkDeletePages.value = [...pages]
  bulkDeleteDialog.value = true
}

async function deleteBulkPages() {
  if (isBulkDeleting.value || !bulkDeletePages.value.length) return

  const pageIds = bulkDeletePages.value.map(getPageId)
  if (pageIds.length > 100 || pageIds.some((id) => !id)) return

  isBulkDeleting.value = true
  try {
    await pageService.deleteBulkPages(pageIds)
    bulkDeleteDialog.value = false
    bulkDeletePages.value = []
    selectedItems.value = []
    onUpdateOptions(options.value)
    showSuccessNotification(t("Pages deleted"))
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isBulkDeleting.value = false
  }
}

const languageDeleteCandidates = ref([])
const selectedLanguagePageIds = ref([])
const isLoadingLanguagePages = ref(false)
const languageCandidatesLoadFailed = ref(false)
const isDeletingLanguages = ref(false)
let candidateFetchToken = 0

function getPageId(page) {
  const match = String(page?.["@id"] || "").match(/\/(\d+)$/)
  return match ? Number(match[1]) : null
}

const canDeleteLanguagePages = computed(() => {
  const sourceId = getPageId(item.value)
  if (!sourceId || selectedLanguagePageIds.value.length > 100) return false
  if (!selectedLanguagePageIds.value.includes(sourceId)) return false

  const selectedOtherIds = selectedLanguagePageIds.value.filter((id) => id !== sourceId)
  return selectedOtherIds.every((id) => languageDeleteCandidates.value.some((page) => page.id === id))
})

function onDeletePagesDialogHide() {
  candidateFetchToken++
  languageDeleteCandidates.value = []
  selectedLanguagePageIds.value = []
  languageCandidatesLoadFailed.value = false
}

async function confirmDeleteItem(itemToDelete) {
  const sourceId = getPageId(itemToDelete)
  if (!sourceId || isDeletingLanguages.value) return

  const fetchToken = ++candidateFetchToken
  const category = itemToDelete?.category?.["@id"] || itemToDelete?.category
  item.value = itemToDelete
  deletePagesDialog.value = true
  selectedLanguagePageIds.value = [sourceId]
  languageDeleteCandidates.value = []
  languageCandidatesLoadFailed.value = false
  isLoadingLanguagePages.value = !!category

  if (!category) return

  try {
    const pages = await pageService.getPagesForLanguageDeletion(category)
    if (fetchToken !== candidateFetchToken || !deletePagesDialog.value) return

    languageDeleteCandidates.value = pages
      .filter((page) => getPageId(page) !== sourceId && page.locale !== itemToDelete.locale)
      .map((page) => ({ id: getPageId(page), title: page.title, locale: page.locale }))
      .filter((page) => Number.isInteger(page.id))
      .sort((a, b) => a.locale.localeCompare(b.locale) || a.title.localeCompare(b.title))
  } catch (error) {
    if (fetchToken !== candidateFetchToken) return
    languageCandidatesLoadFailed.value = true
    showErrorNotification(error)
  } finally {
    if (fetchToken === candidateFetchToken) isLoadingLanguagePages.value = false
  }
}

async function deleteSelectedLanguagePages() {
  if (!canDeleteLanguagePages.value || isLoadingLanguagePages.value || isDeletingLanguages.value) return

  const ids = [...selectedLanguagePageIds.value]
  isDeletingLanguages.value = true

  try {
    if (ids.length === 1) {
      await deleteItem(item)
    } else {
      await pageService.deleteLanguagePages(getPageId(item.value), ids)
      onUpdateOptions(options.value)
      showSuccessNotification(t("Pages deleted"))
    }
    deletePagesDialog.value = false
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isDeletingLanguages.value = false
  }
}

const publicLinkDialogVisible = ref(false)
const publicLink = ref("")

const showPublicLinkDialog = (item) => {
  if (!item.slug) return
  publicLink.value = `${window.location.origin}/pages/${item.slug}`
  publicLinkDialogVisible.value = true
}
</script>
