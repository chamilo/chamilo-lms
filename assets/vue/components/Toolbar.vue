<template>
  <v-toolbar
    class="my-md-auto"
    elevation="0"
  >
    <slot name="left" />
    <v-spacer />
    <div>
      <b-button
        v-if="handleList"
        :loading="isLoading"
        variant="primary"
        @click="listItem"
      >
        {{ $t('List') }}
      </b-button>
      <b-button
        v-if="handleEdit"
        :loading="isLoading"
        variant="primary"
        @click="editItem"
      >
        {{ $t('Edit') }}
      </b-button>
      <b-button
        v-if="handleSubmit"
        :loading="isLoading"
        variant="primary"
        @click="submitItem"
      >
        <v-icon left>
          mdi-content-save
        </v-icon>
        {{ $t('Submit') }}
      </b-button>
      <!--      <v-btn-->
      <!--        v-if="handleReset"-->
      <!--        color="primary"-->
      <!--        class="ml-sm-2"-->
      <!--        @click="resetItem"-->
      <!--      >-->
      <!--        {{ $t('Reset') }}-->
      <!--      </v-btn>-->
      <b-button
        v-if="handleDelete"
        variant="danger"
        class="ml-sm-2"
        @click="confirmDelete = true"
      >
        {{ $t('Delete') }}
      </b-button>

      <b-button
        v-if="handleAdd"
        variant="primary"
        rounded
        @click="addItem"
      >
        <v-icon left>
          mdi-folder-plus-outline
        </v-icon> New folder
      </b-button>

      <b-button
        v-if="handleAddDocument"
        variant="primary"
        rounded
        @click="addDocument"
      >
        <v-icon left>
          mdi-file-plus-outline
        </v-icon>New document
      </b-button>

      <b-button
        v-if="handleUploadDocument"
        variant="primary"
        rounded
        @click="uploadDocument"
      >
        <v-icon left>
          mdi-cloud-upload
        </v-icon>File upload
      </b-button>
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
  data() {
    return {
      confirmDelete: false
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
