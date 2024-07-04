<template>
  <PrimeToolbar>
    <template #start>
      <BaseButton
        v-if="handleBack"
        :label="$t('Back')"
        type="primary"
        icon="back"
        @click="backAction"
      />

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

      <BaseButton
        v-if="handleSubmit"
        :is-loading="isLoading"
        :label="$t('Submit')"
        type="success"
        icon="save"
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
        @click="onHandleDelete"
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
    </template>
  </PrimeToolbar>
</template>

<script>
import PrimeToolbar from "primevue/toolbar"
import PrimeButton from "primevue/button"
import BaseButton from "./basecomponents/BaseButton.vue"

export default {
  name: "Toolbar",
  components: {
    BaseButton,
    PrimeToolbar,
    PrimeButton,
  },
  props: {
    filters: {
      type: Object,
    },
    handleFilter: {
      type: Function,
      required: false,
    },
    handleList: {
      type: Function,
      required: false,
    },
    handleEdit: {
      type: Function,
      required: false,
    },
    handleBack: {
      type: Function,
      required: false,
    },
    handleSubmit: {
      type: Function,
      required: false,
    },
    handleReset: {
      type: Function,
      required: false,
    },
    handleDelete: {
      type: Function,
      required: false,
    },
    handleAdd: {
      type: Function,
      required: false,
    },
    handleSend: {
      type: Function,
      required: false,
    },
    handleAddDocument: {
      type: Function,
      required: false,
    },
    onSendFilter: {
      type: Function,
      required: false,
    },
    resetFilter: {
      type: Function,
      required: false,
    },
    handleUploadDocument: {
      type: Function,
      required: false,
    },
    title: {
      type: String,
      required: false,
    },
    isLoading: {
      type: Boolean,
      required: false,
      default: () => false,
    },
  },
  methods: {
    backAction() {
      if (this.handleBack) {
        this.handleBack()
      }
    },
    listItem() {
      if (this.handleList) {
        this.handleList()
      }
    },
    addItem() {
      if (this.handleAdd) {
        this.handleAdd()
      }
    },
    addDocument() {
      if (this.addDocument) {
        this.handleAddDocument()
      }
    },
    uploadDocument() {
      if (this.uploadDocument) {
        this.handleUploadDocument()
      }
    },
    editItem() {
      if (this.handleEdit) {
        this.handleEdit()
      }
    },
    sendItem() {
      if (this.handleSend) {
        this.handleSend()
      }
    },
    submitItem() {
      if (this.handleSubmit) {
        this.handleSubmit()
      }
    },
    onHandleDelete() {
      this.$confirm.require({
        header: "Confirmation",
        message: "Are you sure you want to delete this item?",
        accept: () => {
          this.handleDelete()
        },
      })
    },
    resetItem() {
      if (this.handleReset) {
        this.handleReset()
      }
    },
  },
}
</script>
