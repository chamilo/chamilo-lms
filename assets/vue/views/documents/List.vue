<template>
  <Toolbar v-if="isAuthenticated && isCurrentTeacher">
    <template #start>
      <Button
        :label="t('New folder')"
        class="p-button-plain p-button-outlined"
        icon="mdi mdi-folder-plus"
        @click="openNew"
      />
      <Button
        :label="t('New document')"
        class="p-button-plain p-button-outlined"
        icon="mdi mdi-file-plus"
        @click="goToNewDocument"
      />
      <Button
        :label="t('Upload')"
        class="p-button-plain p-button-outlined"
        icon="mdi mdi-file-upload"
        @click="goToUploadFile"
      />
      <!--
      <Button label="{{ $t('Download') }}" class="btn btn--primary" @click="downloadDocumentHandler()" :disabled="!selectedItems || !selectedItems.length">
        <v-icon icon="mdi-file-download"/>
        {{ $t('Download') }}
      </Button>
      -->
      <Button
        :disabled="!selectedItems || !selectedItems.length"
        :label="t('Delete selected')"
        class="p-button-danger p-button-outlined"
        icon="mdi mdi-delete"
        @click="confirmDeleteMultiple"
      />
    </template>
  </Toolbar>

  <DataTable
    v-model:filters="filters"
    v-model:selection="selectedItems"
    :global-filter-fields="['resourceNode.title', 'resourceNode.updatedAt']"
    :lazy="true"
    :loading="isLoading"
    :paginator="true"
    :rows="options.itemsPerPage"
    :rows-per-page-options="[5, 10, 20, 50]"
    :total-records="totalItems"
    :value="items"
    current-page-report-template="Showing {first} to {last} of {totalRecords}"
    data-key="iid"
    filter-display="menu"
    paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
    responsive-layout="scroll"
    striped-rows
    @page="onPage($event)"
    @sort="sortingChanged($event)"
  >
    <Column
      v-if="isCurrentTeacher"
      :exportable="false"
      selection-mode="multiple"
    />

    <Column
      :header="t('Title')"
      :sortable="true"
      field="resourceNode.title"
    >
      <template #body="slotProps">
        <div v-if="slotProps.data && slotProps.data.resourceNode && slotProps.data.resourceNode.resourceFile">
          <ResourceFileLink :resource="slotProps.data" />
        </div>
        <div v-else>
          <Button
            v-if="slotProps.data"
            :label="slotProps.data.resourceNode.title"
            class="p-button-text p-button-plain"
            icon="mdi mdi-folder"
            @click="btnFolderOnClick(slotProps.data)"
          />
        </div>
      </template>
    </Column>

    <Column
      :header="t('Size')"
      :sortable="true"
      field="resourceNode.resourceFile.size"
    >
      <template #body="slotProps">
        {{
          slotProps.data.resourceNode.resourceFile ? $filters.prettyBytes(slotProps.data.resourceNode.resourceFile.size) : ''
        }}
      </template>
    </Column>

    <Column
      :header="t('Modified')"
      :sortable="true"
      field="resourceNode.updatedAt"
    >
      <template #body="slotProps">
        {{ useRelativeDatetime(slotProps.data.resourceNode.updatedAt) }}
      </template>
    </Column>

    <Column
      :exportable="false"
    >
      <template #body="slotProps">
        <div class="flex flex-row justify-end gap-2">
          <Button
            class="p-button-icon-only p-button-plain p-button-outlined p-button-sm"
            icon="mdi mdi-information"
            @click="btnShowInformationOnClick(slotProps.data)"
          />

          <Button
            v-if="isAuthenticated && isCurrentTeacher"
            :icon="RESOURCE_LINK_PUBLISHED === slotProps.data.resourceLinkListFromEntity[0].visibility ? 'mdi mdi-eye' : (RESOURCE_LINK_DRAFT === slotProps.data.resourceLinkListFromEntity[0].visibility ? 'mdi mdi-eye-off' : '')"
            class="p-button-icon-only p-button-plain p-button-outlined p-button-sm"
            @click="btnChangeVisibilityOnClick(slotProps.data)"
          />

          <Button
            v-if="isAuthenticated && isCurrentTeacher"
            class="p-button-icon-only p-button-plain p-button-outlined p-button-sm"
            icon="mdi mdi-pencil"
            @click="btnEditOnClick(slotProps.data)"
          />

          <Button
            v-if="isAuthenticated && isCurrentTeacher"
            class="p-button-icon-only p-button-danger p-button-outlined p-button-sm"
            icon="mdi mdi-delete"
            @click="confirmDeleteItem(slotProps.data)"
          />
        </div>
      </template>
    </Column>
  </DataTable>

  <Dialog
    v-model:visible="itemDialog"
    :header="$t('New folder')"
    :modal="true"
    :style="{width: '450px'}"
    class="p-fluid"
  >
    <div class="p-float-label">
      <InputText
        id="title"
        v-model.trim="item.title"
        :class="{ 'p-invalid': submitted && !item.title }"
        autocomplete="off"
        autofocus
        name="name"
        required="true"
      />
      <label
        v-t="'Name'"
        for="name"
      />
    </div>
    <small
      v-if="submitted && !item.title"
      v-t="'Title is required'"
      class="p-error"
    />

    <template #footer>
      <Button
        class="p-button-outlined p-button-plain"
        icon="pi pi-times"
        label="Cancel"
        @click="hideDialog"
      />
      <Button
        class="p-button-secondary"
        icon="pi pi-check"
        label="Save"
        @click="saveItem"
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
      <span v-if="item">Are you sure you want to delete <b>{{ item.title }}</b>?</span>
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
      <span v-if="item">{{ $t('Are you sure you want to delete the selected items?') }}</span>
    </div>
    <template #footer>
      <Button
        class="p-button-outlined p-button-plain"
        icon="pi pi-times"
        label="No"
        @click="deleteMultipleDialog = false"
      />
      <Button
        class="p-button-secondary"
        icon="pi pi-check"
        label="Yes"
        @click="deleteMultipleItems"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { useStore } from 'vuex'
import ResourceFileLink from '../../components/documents/ResourceFileLink.vue'
import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from '../../components/resource_links/visibility'
import { isEmpty } from 'lodash'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { computed, inject, onMounted, ref, watch } from 'vue'
import { useCidReq } from '../../composables/cidReq'
import { useDatatableList } from '../../composables/datatableList'
import { useRelativeDatetime } from '../../composables/formatDate'
import axios from 'axios'

const store = useStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const { filters, options, onUpdateOptions, deleteItem } = useDatatableList('Documents')

const flashMessageList = inject('flashMessageList')

const { cid, sid, gid } = useCidReq()

store.dispatch('course/findCourse', { id: `/api/courses/${cid}` })

if (sid) {
  store.dispatch('session/findSession', { id: `/api/sessions/${sid}` })
}

const item = ref({})

const itemDialog = ref(false)
const deleteItemDialog = ref(false)
const deleteMultipleDialog = ref(false)

const submitted = ref(false)

filters.value.loadNode = 1

const selectedItems = ref([])

const isAuthenticated = computed(() => store.getters['security/isAuthenticated'])
const isCurrentTeacher = computed(() => store.getters['security/isCurrentTeacher'])

const items = computed(() => store.getters['documents/getRecents'])
const isLoading = computed(() => store.getters['documents/isLoading'])

const totalItems = computed(() => store.getters['documents/getTotalItems'])

onMounted(() => {
  filters.value.loadNode = 1

  // Set resource node.
  let nodeId = route.params.node

  if (isEmpty(nodeId)) {
    nodeId = route.query.node
  }

  store.dispatch('resourcenode/findResourceNode', { id: `/api/resource_nodes/${nodeId}` });

  onUpdateOptions(options.value)
});

watch(
  () => route.params,
  () => {
    const nodeId = route.params.node

    const finderParams = { id: `/api/resource_nodes/${nodeId}`, cid, sid, gid };

    store.dispatch('resourcenode/findResourceNode', finderParams);

    if ('DocumentsList' === route.name) {
      onUpdateOptions(options.value);
    }
  }
);

function openNew () {
  item.value = {}
  submitted.value = false
  itemDialog.value = true
}

function hideDialog () {
  itemDialog.value = false
  submitted.value = false
}

function saveItem () {
  submitted.value = true

  if (item.value.title?.trim()) {
    if (!item.value.id) {
      item.value.filetype = 'folder'
      item.value.parentResourceNodeId = route.params.node
      item.value.resourceLinkList = JSON.stringify([{
        gid,
        sid,
        cid,
        visibility: RESOURCE_LINK_PUBLISHED, // visible by default
      }])

      store.dispatch('documents/createWithFormData', item.value)
          .then(() => {
            flashMessageList.value.push({
              severity: 'success',
              detail: t('Saved')
            })

            onUpdateOptions(options.value)
          })
    }
    itemDialog.value = false
    item.value = {}
  }
}

function confirmDeleteMultiple () {
  deleteMultipleDialog.value = true
}

function confirmDeleteItem (itemToDelete) {
  item.value = itemToDelete
  deleteItemDialog.value = true
}

function deleteMultipleItems () {
  store.dispatch('documents/delMultiple', selectedItems.value)
      .then(() => {
        deleteMultipleDialog.value = false
        selectedItems.value = []
      })

  onUpdateOptions(options.value)
//this.$toast.add({severity:'success', summary: 'Successful', detail: 'Products Deleted', life: 3000});*/
}

function btnCofirmSingleDeleteOnClick () {
  deleteItem(item)

  item.value = {}

  deleteItemDialog.value = false
}

function onPage (event) {
  options.value = {
    itemsPerPage: event.rows,
    page: event.page + 1,
    sortBy: event.sortField,
    sortDesc: event.sortOrder === -1
  }

  onUpdateOptions(options.value)
}

function sortingChanged (event) {
  options.value.sortBy = event.sortField
  options.value.sortDesc = event.sortOrder === -1

  onUpdateOptions(options.value)
}

function goToNewDocument () {
  router.push({
    name: 'DocumentsCreateFile',
    query: route.query,
  })
}

function goToUploadFile () {
  router.push({
    name: 'DocumentsUploadFile',
    query: route.query
  })
}

function btnFolderOnClick (item) {
  const folderParams = route.query;
  const resourceId = item.resourceNode.id;

  if (!resourceId) {
    return;
  }

  filters.value['resourceNode.parent'] = resourceId;

  router.push({
    name: 'DocumentsList',
    params: { node: resourceId },
    query: folderParams,
  });
}

function btnShowInformationOnClick (item) {
  const folderParams = route.query;

  if (item) {
    folderParams.id = item['@id'];
  }

  router.push({
    name: 'DocumentsShow',
    params: folderParams,
    query: folderParams
  });
}

function btnChangeVisibilityOnClick (item) {
  const folderParams = route.query;

  folderParams.id = item['@id'];

  axios
    .put(item['@id'] + '/toggle_visibility')
    .then(response => {
      item.resourceLinkListFromEntity = response.data.resourceLinkListFromEntity;
    })
  ;
}

function btnEditOnClick (item) {
  const folderParams = route.query;

  folderParams.id = item['@id'];

  if ('folder' === item.filetype || isEmpty(item.filetype)) {
    router.push({
      name: 'DocumentsUpdate',
      params: { id: item['@id'] },
      query: folderParams,
    });

    return;
  }

  if ('file' === item.filetype) {
    folderParams.getFile = true;

    if (item.resourceNode.resourceFile
      && item.resourceNode.resourceFile.mimeType
      && 'text/html' === item.resourceNode.resourceFile.mimeType
    ) {
      //folderParams.getFile = true;
    }

    router.push({
      name: 'DocumentsUpdateFile',
      params: { id: item['@id'] },
      query: folderParams
    });
  }
}
</script>
