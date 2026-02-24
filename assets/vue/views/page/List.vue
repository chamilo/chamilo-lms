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
        <div class="text-right space-x-2">
          <Button
            v-if="securityStore.isAuthenticated"
            class="p-button-icon-only p-button-plain p-button-outlined p-button-sm"
            icon="mdi mdi-pencil"
            @click="goToEditItem(slotProps.data)"
          />
          <Button
            v-if="securityStore.isAuthenticated"
            class="p-button-icon-only p-button-danger p-button-outlined p-button-sm"
            icon="mdi mdi-delete"
            @click="confirmDeleteItem(slotProps.data)"
          />
          <Button
            v-if="slotProps.data.enabled && slotProps.data.slug"
            class="p-button-icon-only p-button-plain p-button-outlined p-button-sm"
            icon="mdi mdi-link-variant"
            :title="t('Show public link')"
            @click="showPublicLinkDialog(slotProps.data)"
          />
        </div>
      </template>
    </Column>
  </BaseTable>

  <Dialog
    v-model:visible="itemDialog"
    :header="t('New folder')"
    :modal="true"
    :style="{ width: '450px' }"
    class="p-fluid"
  >
    <div class="field">
      <div class="p-float-label">
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
      </div>
      <small
        v-if="submitted && !item.title"
        v-text="t('Title is required')"
        class="p-error"
      />
    </div>

    <template #footer>
      <Button
        v-if="securityStore.isAuthenticated"
        class="p-button-icon-only p-button-plain p-button-outlined p-button-sm"
        icon="mdi mdi-pencil"
        @click="goToEditItem(slotProps.data)"
      />

      <Button
        v-if="securityStore.isAuthenticated"
        class="p-button-icon-only p-button-danger p-button-outlined p-button-sm"
        icon="mdi mdi-delete"
        @click="confirmDeleteItem(slotProps.data)"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="deleteItemDialog"
    :modal="true"
    :style="{ width: '450px' }"
    header="Confirm"
  >
    <div class="confirmation-content">
      <i
        class="pi pi-exclamation-triangle p-mr-3"
        style="font-size: 2rem"
      />
      <span v-if="item"
        >{{ t('Are you sure you want to delete {0}?', [item.title]) }}</span
      >
    </div>
    <template #footer>
      <Button
        :label="t('No')"
        class="p-button-outlined p-button-plain"
        icon="pi pi-times"
        @click="deleteItemDialog = false"
      />
      <Button
        :label="t('Yes')"
        class="p-button-secondary"
        icon="pi pi-check"
        @click="btnCofirmSingleDeleteOnClick"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="deleteMultipleDialog"
    :modal="true"
    :style="{ width: '450px' }"
    header="Confirm"
  >
    <div class="confirmation-content">
      <i
        class="pi pi-exclamation-triangle p-mr-3"
        style="font-size: 2rem"
      />
      <span
        v-if="item"
        v-text="t('Are you sure you want to delete the selected items?')"
      />
    </div>
    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        label="No"
        @click="deleteMultipleDialog = false"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
        label="Yes"
        @click="deleteMultipleItems"
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
</template>

<script setup>
import { useStore } from "vuex"
import { useDatatableList } from "../../composables/datatableList"
import { computed, inject, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"
import { useSecurityStore } from "../../store/securityStore"
import { useRouter } from "vue-router"
import { useLocale } from "../../composables/locale"
import BaseTable from "../../components/basecomponents/BaseTable.vue"

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

const toast = useToast()

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
const deleteItemDialog = ref(false)
const deleteMultipleDialog = ref(false)
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

const deleteMultipleItems = () => {
  console.log("deleteMultipleItems".selectedItems.value)

  store.dispatch("page/delMultiple", selectedItems.value).then(() => {
    deleteMultipleDialog.value = false
    selectedItems.value = []

    toast.add({
      severity: "success",
      detail: t("Pages deleted"),
      life: 3500,
    })
  })

  onUpdateOptions(options.value)
}
const confirmDeleteItem = (itemToDelete) => {
  item.value = itemToDelete
  deleteItemDialog.value = true
}

const btnCofirmSingleDeleteOnClick = () => {
  deleteItem(item)

  item.value = {}

  deleteItemDialog.value = false
}

const publicLinkDialogVisible = ref(false)
const publicLink = ref("")

const showPublicLinkDialog = (item) => {
  if (!item.slug) return
  publicLink.value = `${window.location.origin}/pages/${item.slug}`
  publicLinkDialogVisible.value = true
}
</script>
