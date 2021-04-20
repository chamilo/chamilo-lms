<template>
  <q-toolbar class="q-my-md">
    <slot name="left" />

    <q-space />

    <q-btn
      v-if="handleList"
      :loading="isLoading"
      color="primary"
      @click="listItem"
      unelevated
    >
      {{ $t('List') }}
    </q-btn>
    <q-btn
      v-if="handleEdit"
      :loading="isLoading"
      color="primary"
      @click="editItem"
      unelevated
    >
      {{ $t('Edit') }}
    </q-btn>

    <q-btn
      v-if="handleSubmit"
      :loading="isLoading"
      color="primary"
      @click="submitItem"
      unelevated
    >
      <font-awesome-icon icon="save" />
      {{ $t('Submit') }}
    </q-btn>
    <!--      <v-btn-->
    <!--        v-if="handleReset"-->
    <!--        color="primary"-->
    <!--        class="ml-sm-2"-->
    <!--        @click="resetItem"-->
    <!--      >-->
    <!--        {{ $t('Reset') }}-->
    <!--      </v-btn>-->
    <q-btn
      v-if="handleDelete"
      color="red"
      unelevated
      class="ml-sm-2"
      @click="confirmDelete = true"
    >
      {{ $t('Delete') }}
    </q-btn>

    <q-btn
      v-if="handleAdd"
      color="primary"
      rounded
      @click="addItem"
    >
      <font-awesome-icon icon="folder-plus" /> New folder
    </q-btn>

    <q-btn
      v-if="handleAddDocument"
      color="primary"
      rounded
      @click="addDocument"
    >
      <font-awesome-icon icon="file-alt" /> New document
    </q-btn>

    <q-btn
      v-if="handleUploadDocument"
      color="primary"
      rounded
      @click="uploadDocument"
    >
      <font-awesome-icon icon="cloud-upload-alt" /> File upload
    </q-btn>

<!--    <DataFilter-->
<!--      v-if="filters"-->
<!--      :handle-filter="onSendFilter"-->
<!--      :handle-reset="resetFilter"-->
<!--    >-->
<!--      <DocumentsFilterForm-->
<!--        ref="filterForm"-->
<!--        slot="filter"-->
<!--        :values="filters"-->
<!--      />-->
<!--    </DataFilter>-->

    <ConfirmDelete
      v-if="handleDelete"
      :visible="confirmDelete"
      :handle-delete="handleDelete"
      @close="confirmDelete = false"
    />
  </q-toolbar>
</template>

<script>
import ConfirmDelete from './ConfirmDelete.vue';
import DocumentsFilterForm from './documents/Filter.vue';
import DataFilter from './DataFilter.vue';

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
