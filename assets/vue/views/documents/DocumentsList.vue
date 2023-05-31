<template>
  <ButtonToolbar v-if="isAuthenticated && isCurrentTeacher">
    <BaseButton
      v-if="showBackButtonIfNotRootFolder"
      :label="t('Back')"
      type="black"
      class="mr-2 mb-2"
      icon="back"
      @click="back"
    />
    <BaseButton
      :label="t('New document')"
      type="black"
      class="mr-2 mb-2"
      icon="file-add"
      @click="goToNewDocument"
    />
    <BaseButton
      :disabled="true"
      :label="t('New drawing')"
      class="mr-2 mb-2"
      type="black"
      icon="drawing"
    />
    <BaseButton
      :disabled="true"
      :label="t('Record audio')"
      class="mr-2 mb-2"
      type="black"
      icon="record-add"
    />
    <BaseButton
      :label="t('Upload')"
      type="black"
      class="mr-2 mb-2"
      icon="file-upload"
      @click="goToUploadFile"
    />
    <BaseButton
      :label="t('New folder')"
      type="black"
      class="mr-2 mb-2"
      icon="folder-plus"
      @click="openNew"
    />
    <BaseButton
      :disabled="true"
      :label="t('New cloud file')"
      class="mr-2 mb-2"
      type="black"
      icon="file-cloud-add"
    />
    <BaseButton
      :disabled="!hasImageInDocumentEntries"
      :label="t('Slideshow')"
      class="mr-2 mb-2"
      type="black"
      icon="view-gallery"
      @click="showSlideShowWithFirstImage"
    />
    <BaseButton
      :label="t('Usage')"
      type="black"
      class="mr-2 mb-2"
      icon="usage"
      @click="showUsageDialog"
    />
    <BaseButton
      :disabled="true"
      :label="t('Download all')"
      type="black"
      class="mr-2 mb-2"
      icon="download"
    />
  </ButtonToolbar>

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
    class="mb-5"
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
        <DocumentEntry
          v-if="slotProps.data"
          :data="slotProps.data"
        />
      </template>
    </Column>

    <Column
      :header="t('Size')"
      :sortable="true"
      field="resourceNode.resourceFile.size"
    >
      <template #body="slotProps">
        {{
          slotProps.data.resourceNode.resourceFile ? $filters.prettyBytes(slotProps.data.resourceNode.resourceFile.size) :
            ''
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
          <BaseButton
            type="black"
            icon="information"
            size="small"
            @click="btnShowInformationOnClick(slotProps.data)"
          />

          <BaseButton
            v-if="isAuthenticated && isCurrentTeacher"
            type="black"
            :icon="RESOURCE_LINK_PUBLISHED === slotProps.data.resourceLinkListFromEntity[0].visibility ? 'eye-on' : (RESOURCE_LINK_DRAFT === slotProps.data.resourceLinkListFromEntity[0].visibility ? 'eye-off' : '')"
            size="small"
            @click="btnChangeVisibilityOnClick(slotProps.data)"
          />

          <BaseButton
            v-if="isAuthenticated && isCurrentTeacher"
            type="black"
            icon="edit"
            size="small"
            @click="btnEditOnClick(slotProps.data)"
          />

          <BaseButton
            v-if="isAuthenticated && isCurrentTeacher"
            type="danger"
            icon="delete"
            size="small"
            @click="confirmDeleteItem(slotProps.data)"
          />
        </div>
      </template>
    </Column>
  </DataTable>

  <ButtonToolbar
    v-if="isAuthenticated && isCurrentTeacher"
    show-top-border
  >
    <BaseButton
      :label="t('Select all')"
      class="mr-2 mb-2"
      type="black"
      icon="select-all"
      @click="selectAll"
    />
    <BaseButton
      :label="t('Unselect all')"
      class="mr-2 mb-2"
      type="black"
      icon="unselect-all"
      @click="unselectAll"
    />
    <BaseButton
      :disabled="!selectedItems || !selectedItems.length"
      :label="t('Delete selected')"
      class="mr-2 mb-2"
      type="danger"
      icon="delete"
      @click="showDeleteMultipleDialog"
    />
  </ButtonToolbar>

  <BaseDialogConfirmCancel
    v-model:is-visible="isNewFolderDialogVisible"
    :title="t('New folder')"
    :confirm-label="t('Save')"
    :cancel-label="t('Cancel')"
    @confirm-clicked="createNewFolder"
    @cancel-clicked="hideNewFolderDialog"
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
  </BaseDialogConfirmCancel>

  <BaseDialogConfirmCancel
    v-model:is-visible="isDeleteItemDialogVisible"
    :title="t('Confirm')"
    @confirm-clicked="deleteSingleItem"
    @cancel-clicked="isDeleteItemDialogVisible = false"
  >
    <div class="confirmation-content">
      <BaseIcon icon="alert" size="big" class="mr-2" />
      <span v-if="item">{{ t('Are you sure you want to delete') }} <b>{{ item.title }}</b>?</span>
    </div>
  </BaseDialogConfirmCancel>

  <BaseDialogConfirmCancel
    v-model:is-visible="isDeleteMultipleDialogVisible"
    :title="t('Confirm')"
    @confirm-clicked="deleteMultipleItems"
    @cancel-clicked="isDeleteMultipleDialogVisible = false"
  >
    <div class="confirmation-content">
      <BaseIcon icon="alert" size="big" class="mr-2" />
      <span v-if="item">{{ t('Are you sure you want to delete the selected items?') }}</span>
    </div>
  </BaseDialogConfirmCancel>

  <BaseDialog
    v-model:is-visible="isFileUsageDialogVisible"
    :title="t('Space available')"
  >
    <p>This feature is in development, this is a mockup with placeholder data!</p>
    <BaseChart
      :data="usageData"
    />
  </BaseDialog>
</template>

<script setup>
import {useStore} from 'vuex'
import {RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED} from '../../components/resource_links/visibility'
import {isEmpty} from 'lodash'
import {useRoute, useRouter} from 'vue-router'
import {useI18n} from 'vue-i18n'
import {computed, onMounted, ref, watch} from 'vue'
import {useCidReq} from '../../composables/cidReq'
import {useDatatableList} from '../../composables/datatableList'
import {useRelativeDatetime} from '../../composables/formatDate'
import axios from 'axios'
import {useToast} from 'primevue/usetoast';
import DocumentEntry from "../../components/documents/DocumentEntry.vue";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import ButtonToolbar from "../../components/basecomponents/ButtonToolbar.vue";
import BaseIcon from "../../components/basecomponents/BaseIcon.vue";
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue";
import {useFileUtils} from "../../composables/fileUtils";
import BaseDialog from "../../components/basecomponents/BaseDialog.vue";
import BaseChart from "../../components/basecomponents/BaseChart.vue";

const store = useStore()
const route = useRoute()
const router = useRouter()

const {t} = useI18n()

const {filters, options, onUpdateOptions, deleteItem} = useDatatableList('Documents')

const toast = useToast();

const {cid, sid, gid} = useCidReq()

const {isImage} = useFileUtils()

store.dispatch('course/findCourse', {id: `/api/courses/${cid}`})

if (sid) {
  store.dispatch('session/findSession', {id: `/api/sessions/${sid}`})
}

const item = ref({})
const usageData = ref({})

const isNewFolderDialogVisible = ref(false)
const isDeleteItemDialogVisible = ref(false)
const isDeleteMultipleDialogVisible = ref(false)
const isFileUsageDialogVisible = ref(false)

const submitted = ref(false)

filters.value.loadNode = 1

const selectedItems = ref([])

const isAuthenticated = computed(() => store.getters['security/isAuthenticated'])
const isCurrentTeacher = computed(() => store.getters['security/isCurrentTeacher'])

const items = computed(() => store.getters['documents/getRecents'])
const isLoading = computed(() => store.getters['documents/isLoading'])

const totalItems = computed(() => store.getters['documents/getTotalItems'])

const resourceNode = computed(() => store.getters['resourcenode/getResourceNode'])

const hasImageInDocumentEntries = computed(() => {
  return items.value.find(i => isImage(i)) !== undefined
})

onMounted(() => {
  filters.value.loadNode = 1

  // Set resource node.
  let nodeId = route.params.node

  if (isEmpty(nodeId)) {
    nodeId = route.query.node
  }

  store.dispatch('resourcenode/findResourceNode', {id: `/api/resource_nodes/${nodeId}`});

  onUpdateOptions(options.value)
});

watch(
  () => route.params,
  () => {
    const nodeId = route.params.node

    const finderParams = {id: `/api/resource_nodes/${nodeId}`, cid, sid, gid};

    store.dispatch('resourcenode/findResourceNode', finderParams);

    if ('DocumentsList' === route.name) {
      onUpdateOptions(options.value);
    }
  }
);

const showBackButtonIfNotRootFolder = computed(() => {
  if (!resourceNode.value) {
    return false;
  }
  return resourceNode.value.resourceType.name !== 'courses';
})

function back() {
  if (!resourceNode.value) {
    return;
  }
  let parent = resourceNode.value.parent;
  if (parent) {
    let queryParams = {cid, sid, gid}
    router.push({name: 'DocumentsList', params: {node: parent.id}, query: queryParams});
  }
}

function openNew() {
  item.value = {}
  submitted.value = false
  isNewFolderDialogVisible.value = true
}

function hideNewFolderDialog() {
  isNewFolderDialogVisible.value = false
  submitted.value = false
}

function createNewFolder() {
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
          toast.add({
            severity: 'success',
            detail: t('Saved'),
            life: 3500,
          })

          onUpdateOptions(options.value)
        })
    }
    isNewFolderDialogVisible.value = false
    item.value = {}
  }
}

function selectAll() {
  selectedItems.value = items.value;
}
function showDeleteMultipleDialog() {
  isDeleteMultipleDialogVisible.value = true
}

function confirmDeleteItem (itemToDelete) {
  item.value = itemToDelete
  isDeleteItemDialogVisible.value = true
}

function deleteMultipleItems () {
  store.dispatch('documents/delMultiple', selectedItems.value)
    .then(() => {
      isDeleteMultipleDialogVisible.value = false
      unselectAll()
    })

  onUpdateOptions(options.value)
//this.$toast.add({severity:'success', summary: 'Successful', detail: 'Products Deleted', life: 3000});*/
}

function unselectAll () {
  selectedItems.value = [];
}

function deleteSingleItem() {
  deleteItem(item)

  item.value = {}

  isDeleteItemDialogVisible.value = false
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
      params: {id: item['@id']},
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
      params: {id: item['@id']},
      query: folderParams
    });
  }
}

function showSlideShowWithFirstImage() {
  let item = items.value.find(i => isImage(i))
  if (item === undefined) { return }
  // Right now Vue prime datatable does not offer a method to click on a row in a table
  // https://primevue.org/datatable/#api.datatable.methods
  // so we click on the dom element that has the href on the item
  document.querySelector(`a[href='${item.contentUrl}']`).click()
  // start slideshow trusting the button to play is present
  document.querySelector('button[class="fancybox-button fancybox-button--play"]').click()
}

function showUsageDialog() {
  // TODO retrieve usage data from server
  usageData.value = {
    datasets: [{
      data: [83, 14, 5],
      backgroundColor: [
        'rgba(255, 99, 132, 0.7)',
        'rgba(54, 162, 235, 0.7)',
        'rgba(255, 206, 86, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(255, 159, 64, 0.7)'
      ],
    }],
    labels: ['Course', 'Teacher', 'Available space'],
  }
  isFileUsageDialogVisible.value = true
}
</script>
