<template>
  <div class="">
    <slot name="left" />

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
      <font-awesome-icon icon="save" />
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
      <font-awesome-icon icon="folder-plus" /> New folder
    </b-button>

    <b-button
      v-if="handleAddDocument"
      variant="primary"
      rounded
      @click="addDocument"
    >
      <font-awesome-icon icon="file-alt" /> New document
    </b-button>

    <b-button
      v-if="handleUploadDocument"
      variant="primary"
      rounded
      @click="uploadDocument"
    >
      <font-awesome-icon icon="cloud-upload-alt" /> File upload
    </b-button>

    <DataFilter
      v-if="filters"
      :handle-filter="onSendFilter"
      :handle-reset="resetFilter"
    >
      <DocumentsFilterForm
        ref="filterForm"
        slot="filter"
        :values="filters"
      />
    </DataFilter>

    <ConfirmDelete
      v-if="handleDelete"
      :visible="confirmDelete"
      :handle-delete="handleDelete"
      @close="confirmDelete = false"
    />
  </div>
</template>

<script>
import ConfirmDelete from './ConfirmDelete';
import DocumentsFilterForm from './documents/Filter';
import DataFilter from './DataFilter';

export default {
  name: 'Toolbar',
  components: {
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
