<template>
  <PrimeToolbar>
    <template #start>
      <PrimeButton
        v-if="handleList"
        :label="$t('List')"
        :loading="isLoading"
        class="p-button-outlined"
        @click="listItem"
      />

      <PrimeButton
        v-if="handleEdit"
        :loading="isLoading"
        :title="$t('Edit')"
        class="p-button-outlined"
        icon="mdi mdi-pencil"
        @click="editItem"
      />

      <PrimeButton
        v-if="handleSubmit"
        :loading="isLoading"
        :title="$t('Submit')"
        class="p-button-outlined"
        icon="mdi mdi-content-save"
        @click="submitItem"
      />

      <PrimeButton
        v-if="handleSend"
        :loading="isLoading"
        :title="$t('Send')"
        class="p-button-outlined"
        icon="mdi mdi-send"
        @click="sendItem"
      />

      <PrimeButton
        v-if="handleDelete"
        :loading="isLoading"
        :title="$t('Delete')"
        class="p-button-outlined"
        icon="mdi mdi-delete"
        @click="confirmDeleteClick = true"
      />

      <PrimeButton
        v-if="handleAdd"
        :label="$t('New folder')"
        class="p-button-outlined"
        icon="mdi mdi-folder-plus"
        @click="addItem"
      />

      <PrimeButton
        v-if="handleAddDocument"
        :label="$t('New document')"
        class="p-button-outlined"
        icon="mdi mdi-file-plus"
        @click="addDocument"
      />

      <PrimeButton
        v-if="handleUploadDocument"
        :label="$t('File upload')"
        class="p-button-outlined"
        icon="mdi mdi-cloud-upload"
        @click="uploadDocument"
      />

      <ConfirmDelete
        v-if="handleDelete"
        :show="confirmDeleteClick"
        :handle-delete="handleDelete"
        :handle-cancel="() => confirmDeleteClick = false"
      />
    </template>
  </PrimeToolbar>
</template>

<script>
import PrimeToolbar from 'primevue/toolbar';
import PrimeButton from 'primevue/button';
import ConfirmDelete from './ConfirmDelete.vue';
import DocumentsFilterForm from './documents/Filter.vue';
import DataFilter from './DataFilter.vue';

export default {
  name: 'Toolbar',
  components: {
    PrimeToolbar,
    PrimeButton,
    ConfirmDelete,
    DocumentsFilterForm,
    DataFilter
  },
  props: {
    filters: {
      type: Object,
    },
    handleFilter: {
      type: Function,
      required: false
    },
    handleList: {
      type: Function,
      required: false
    },
    handleEdit: {
      type: Function,
      required: false
    },
    handleSubmit: {
      type: Function,
      required: false
    },
    handleReset: {
      type: Function,
      required: false
    },
    handleDelete: {
      type: Function,
      required: false
    },
    handleAdd: {
      type: Function,
      required: false
    },
    handleSend: {
      type: Function,
      required: false
    },
    handleAddDocument: {
      type: Function,
      required: false
    },
    onSendFilter: {
      type: Function,
      required: false
    },
    resetFilter: {
      type: Function,
      required: false
    },
    handleUploadDocument: {
      type: Function,
      required: false
    },
    title: {
      type: String,
      required: false
    },
    isLoading: {
      type: Boolean,
      required: false,
      default: () => false
    }
  },
  data() {
    return {
      confirmDeleteClick: false
    };
  },
  methods: {
    listItem() {
      if (this.handleList) {
        this.handleList();
      }
    },
    addItem() {
      if (this.handleAdd) {
        this.handleAdd();
      }
    },
    addDocument() {
      if (this.addDocument) {
        this.handleAddDocument();
      }
    },
    uploadDocument() {
      if (this.uploadDocument) {
        this.handleUploadDocument();
      }
    },
    editItem() {
      if (this.handleEdit) {
        this.handleEdit();
      }
    },
    sendItem() {
      if (this.handleSend) {
        this.handleSend();
      }
    },
    submitItem() {
      if (this.handleSubmit) {
        this.handleSubmit();
      }
    },
    resetItem() {
      if (this.handleReset) {
        this.handleReset();
      }
    }
  }
};
</script>
