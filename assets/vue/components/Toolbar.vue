<template>
  <v-toolbar class="my-md-auto" elevation="0">
    <slot name="left"></slot>
    <v-spacer />
    <div>
      <v-btn
              v-if="handleList"
              :loading="isLoading"
              color="primary"
              @click="listItem"
      >
        {{ $t('List') }}
      </v-btn>
      <v-btn
        v-if="handleEdit"
        :loading="isLoading"
        color="primary"
        @click="editItem"
      >
        {{ $t('Edit') }}
      </v-btn>
      <v-btn
        v-if="handleSubmit"
        :loading="isLoading"
        color="primary"
        @click="submitItem"
      >
        <v-icon left>mdi-content-save</v-icon>
        {{ $t('Submit') }}
      </v-btn>
      <v-btn
        v-if="handleReset"
        color="primary"
        class="ml-sm-2"
        @click="resetItem"
      >
        {{ $t('Reset') }}
      </v-btn>
      <v-btn
        v-if="handleDelete"
        color="error"
        class="ml-sm-2"
        @click="confirmDelete = true"
      >
        {{ $t('Delete') }}
      </v-btn>

      <v-btn v-if="handleAdd"
             color="primary"
             rounded
             @click="addItem">
        <v-icon left>mdi-folder-plus-outline</v-icon> New folder
      </v-btn>

      <v-btn v-if="handleAddDocument" color="primary" rounded @click="addDocument">
        <v-icon left>mdi-cloud-upload</v-icon>New document
      </v-btn>

      <v-btn v-if="handleUploadDocument" color="primary" rounded @click="uploadDocument">
        <v-icon left>mdi-cloud-upload</v-icon>File upload
      </v-btn>
    </div>

    <ConfirmDelete
      v-if="handleDelete"
      :visible="confirmDelete"
      :handle-delete="handleDelete"
      @close="confirmDelete = false"
    />
  </v-toolbar>
</template>

<script>
import ConfirmDelete from './ConfirmDelete';

export default {
  name: 'Toolbar',
  components: {
    ConfirmDelete
  },
  data() {
    return {
      confirmDelete: false
    };
  },
  props: {
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
    handleAddDocument: {
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
