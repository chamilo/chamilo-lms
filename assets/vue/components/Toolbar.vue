<template>
  <div class="q-card">
<!--    <slot name="left" />-->
<!--    <q-space />-->
    <div class="p-4 flex flex-row gap-1 mb-2">
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
        no-caps
        class="btn btn-primary"
        :loading="isLoading"
        @click="editItem"
        unelevated
      >
        {{ $t('Edit') }}
      </q-btn>

<!--      <q-btn-->
<!--        v-if="handleSubmit"-->
<!--        no-caps-->
<!--        class="btn btn-primary"-->
<!--        :loading="isLoading"-->
<!--        @click="submitItem"-->
<!--        unelevated-->
<!--      >-->
<!--        <v-icon icon="mdi-content-save"/>-->
<!--        {{ $t('Submit') }}-->
<!--      </q-btn>-->

      <v-btn
          v-if="handleSubmit"
          :loading="isLoading"
          tile
          icon
          @click="submitItem"
      >
        <v-icon icon="mdi-content-save" />
      </v-btn>

      <v-btn
          v-if="handleSend"
          :loading="isLoading"
          tile
          icon
          @click="sendItem"
      >
        <v-icon icon="mdi-send" />
      </v-btn>

      <!--      <v-btn-->
      <!--        v-if="handleReset"-->
      <!--        color="primary"-->
      <!--        class="ml-sm-2"-->
      <!--        @click="resetItem"-->
      <!--      >-->
      <!--        {{ $t('Reset') }}-->
      <!--      </v-btn>-->
      <v-btn
          v-if="handleDelete"
          :loading="isLoading"
          tile
          icon
          @click="confirmDeleteClick = true"
      >
        <v-icon icon="mdi-delete" />
      </v-btn>
<!--      color="primary"-->
      <q-btn
        v-if="handleAdd"
        no-caps
        class="btn btn-primary"
        @click="addItem"
      >
        <v-icon icon="mdi-folder-plus"/>
        New folder
      </q-btn>

      <q-btn
        no-caps
        class="btn btn-primary"
        v-if="handleAddDocument"
        @click="addDocument"
      >
        <v-icon icon="mdi-file-plus"/>
        New document
      </q-btn>

      <q-btn
        no-caps
        class="btn btn-primary"
        v-if="handleUploadDocument"
        @click="uploadDocument"
      >
        <v-icon icon="mdi-cloud-upload"/>
        File upload
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
        :show="confirmDeleteClick"
        :handle-delete="handleDelete"
        :handle-cancel="() => (confirmDeleteClick = false)"
      />
    </div>
  </div>
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
