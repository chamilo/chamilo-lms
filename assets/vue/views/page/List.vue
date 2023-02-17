<template>
  <PrimeToolbar
    v-if="isAdmin"
  >
    <template #start>
      <Button
        :label="$t('New page')"
        class="p-button-outlined"
        icon="mdi mdi-file-plus"
        @click="addHandler()"
      />
      <!--        <Button class="btn btn--primary" @click="openNew">-->
      <!--          <v-icon icon="mdi-folder-plus"/>-->
      <!--          {{ $t('New category') }}-->
      <!--        </Button>-->

      <!--        <Button label="{{ $t('Delete selected') }}" class="btn btn--danger " @click="confirmDeleteMultiple" :disabled="!selectedItems || !selectedItems.length">-->
      <!--          <v-icon icon="mdi-delete"/>-->
      <!--          {{ $t('Delete selected') }}-->
      <!--        </Button>-->
    </template>
  </PrimeToolbar>

  <DataTable
    v-if="isAdmin"
    v-model:selection="selectedItems"
    v-model:filters="filters"
    class="p-datatable-sm"
    :value="items"
    data-key="iid"
    filter-display="menu"
    :lazy="true"
    :paginator="true"
    :rows="10"
    :total-records="totalItems"
    :loading="isLoading"
    paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
    :rows-per-page-options="[5, 10, 20, 50]"
    responsive-layout="scroll"
    current-page-report-template="Showing {first} to {last} of {totalRecords}"
    @page="onPage($event)"
    @sort="sortingChanged($event)"
  >
    <Column
      selection-mode="multiple"
      style="width: 3rem"
      :exportable="false"
    />
    <Column
      field="title"
      :header="$t('Title')"
      :sortable="true"
    >
      <template #body="slotProps">
        <a
          v-if="slotProps.data"
          class="cursor-pointer "
          @click="showHandler(slotProps.data)"
        >
          {{ slotProps.data.title }}
        </a>
      </template>
    </Column>

    <Column
      field="locale"
      :header="$t('Locale')"
    />
    <Column
      field="category.title"
      :header="$t('Category')"
    />
    <Column
      field="enabled"
      :header="$t('Enabled')"
    />

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row gap-2">
          <!--          <Button class="btn btn--primary" @click="showHandler(slotProps.data)">-->
          <!--            <v-icon icon="mdi-information"/>-->
          <!--          </Button>-->

          <Button
            v-if="isAuthenticated"
            class="p-button-primary p-button-outlined p-mr-2"
            icon="mdi mdi-pencil"
            @click="editHandler(slotProps.data)"
          />

          <Button
            v-if="isAuthenticated"
            class="p-button-danger p-button-outlined"
            icon="mdi mdi-delete"
            @click="confirmDeleteItem(slotProps.data)"
          />
        </div>
      </template>
    </Column>
  </DataTable>

  <Dialog
    v-model:visible="itemDialog"
    :style="{width: '450px'}"
    :header="$t('New folder')"
    :modal="true"
    class="p-fluid"
  >
    <div class="field">
      <div class="p-float-label">
        <InputText
          id="title"
          v-model.trim="item.title"
          :class="{'p-invalid': submitted && !item.title}"
          autocomplete="off"
          autofocus
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
    </div>

    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        label="Cancel"
        @click="hideDialog"
      />
      <Button
        label="Save"
        icon="pi pi-check"
        class="p-button-text"
        @click="saveItem"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="deleteItemDialog"
    :style="{width: '450px'}"
    header="Confirm"
    :modal="true"
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
        label="No"
        icon="pi pi-times"
        class="p-button-text"
        @click="deleteItemDialog = false"
      />
      <Button
        label="Yes"
        icon="pi pi-check"
        class="p-button-text"
        @click="deleteItemButton"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="deleteMultipleDialog"
    :style="{width: '450px'}"
    header="Confirm"
    :modal="true"
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
        label="No"
        icon="pi pi-times"
        class="p-button-text"
        @click="deleteMultipleDialog = false"
      />
      <Button
        label="Yes"
        icon="pi pi-check"
        class="p-button-text"
        @click="deleteMultipleItems"
      />
    </template>
  </Dialog>
</template>

<script>
import PrimeToolbar from 'primevue/toolbar';
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import { useDatatableList } from '../../composables/datatableList';
import { inject, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

export default {
  name: 'PageList',
  servicePrefix: 'Page',
  components: {
    PrimeToolbar,
  },
  mixins: [ListMixin],
  setup() {
    const { filters, options, onUpdateOptions } = useDatatableList('Page')

    const { t } = useI18n();

    const flashMessageList = inject('flashMessageList');

    onMounted(() => {
      onUpdateOptions(options.value);
    });

    function deletedPage() {
      flashMessageList.value.push({
        severity: 'success',
        detail: t('Deleted')
      });

      onUpdateOptions(options.value);
    }

    return {
      onUpdateOptions,
      deletedPage,
    };
  },
  data() {
    return {
      sortBy: 'title',
      sortDesc: false,
      selected: [],
      isBusy: false,
      options: [],
      selectedItems: [],
      // prime vue
      itemDialog: false,
      deleteItemDialog: false,
      deleteMultipleDialog: false,
      item: {},
      submitted: false,
    };
  },
  computed: {
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),

    ...mapGetters('page', {
      items: 'list',
    }),

    //...getters

    // From ListMixin
    ...mapFields('page', {
      deletedPage: 'deleted',
      error: 'error',
      isLoading: 'isLoading',
      resetList: 'resetList',
      totalItems: 'totalItems',
      view: 'view'
    }),
  },
  methods: {
    // prime
    onPage(event) {
      this.options.itemsPerPage = event.rows;
      this.options.page = event.page + 1;
      this.options.sortBy = event.sortField;
      this.options.sortDesc = event.sortOrder === -1;

      this.onUpdateOptions(this.options);
    },
    sortingChanged(event) {
      this.options.sortBy = event.sortField;
      this.options.sortDesc = event.sortOrder === -1;
      this.onUpdateOptions(this.options);
      // ctx.sortBy   ==> Field key for sorting by (or null for no sorting)
      // ctx.sortDesc ==> true if sorting descending, false otherwise
    },
    openNew() {
      this.item = {};
      this.submitted = false;
      this.itemDialog = true;
    },
    hideDialog() {
      this.itemDialog = false;
      this.submitted = false;
    },
    saveItem() {
      this.submitted = true;

      if (this.item.title.trim()) {
        if (this.item.id) {
        } else {
          // this.item.creator
          this.createCategory(this.item);
          this.showMessage('Saved');
        }
        this.itemDialog = false;
        this.item = {};
      }
    },
    editItem(item) {
      this.item = {...item};
      this.itemDialog = true;
    },
    confirmDeleteItem(item) {
      this.item = item;
      this.deleteItemDialog = true;
    },
    confirmDeleteMultiple() {
      this.deleteMultipleDialog = true;
    },
    deleteMultipleItems() {
      console.log('deleteMultipleItems');
      console.log(this.selectedItems);
      this.deleteMultipleAction(this.selectedItems);
      this.onRequest({
        pagination: this.pagination,
      });
      this.deleteMultipleDialog = false;
      this.selectedItems = null;
      //this.$toast.add({severity:'success', summary: 'Successful', detail: 'Products Deleted', life: 3000});*/
    },
    deleteItemButton() {
      console.log('deleteItem');
      this.deleteItem(this.item);
      //this.items = this.items.filter(val => val.iid !== this.item.iid);
      this.deleteItemDialog = false;
      this.item = {};
      this.onUpdateOptions(this.options);
    },
    onRowSelected(items) {
      this.selected = items
    },
    selectAllRows() {
      this.$refs.selectableTable.selectAllRows()
    },
    clearSelected() {
      this.$refs.selectableTable.clearSelected()
    },
    async deleteSelected() {
      console.log('deleteSelected');
      this.deleteMultipleAction(this.selected);
      this.onRequest({
        pagination: this.pagination,
      });
    },
    //...actions,
    // From ListMixin
    ...mapActions('page', {
      getPage: 'fetchAll',
      createWithFormData: 'createWithFormData',
      deleteItem: 'del',
      deleteMultipleAction: 'delMultiple'
    }),
    /*...mapActions('pagecategory', {
      getCategories: 'fetchAll',
      createCategory: 'create',
      deleteItem: 'del',
      deleteMultipleAction: 'delMultiple'
    }),*/
  }
};
</script>
