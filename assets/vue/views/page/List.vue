<template>
  <div v-if="isAdmin"  class="q-card">
    <div class="p-4 flex flex-row gap-1 mb-2">
      <div class="flex flex-row gap-2" >
<!--        <Button class="btn btn-primary" @click="openNew">-->
<!--          <v-icon icon="mdi-folder-plus"/>-->
<!--          {{ $t('New category') }}-->
<!--        </Button>-->
        <Button label="{{ $t('New page') }}" class="btn btn-primary" @click="addHandler()" >
          <v-icon icon="mdi-file-plus"/>
          {{ $t('New page') }}
        </Button>
<!--        <Button label="{{ $t('Delete selected') }}" class="btn btn-danger " @click="confirmDeleteMultiple" :disabled="!selectedItems || !selectedItems.length">-->
<!--          <v-icon icon="mdi-delete"/>-->
<!--          {{ $t('Delete selected') }}-->
<!--        </Button>-->
      </div>
    </div>
  </div>

  <DataTable
      v-if="isAdmin"
      class="p-datatable-sm"
      :value="items"
      v-model:selection="selectedItems"
      dataKey="iid"
      v-model:filters="filters"
      filterDisplay="menu"
      :lazy="true"
      :paginator="true"
      :rows="10"
      :totalRecords="totalItems"
      :loading="isLoading"
      @page="onPage($event)"
      @sort="sortingChanged($event)"
      paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
      :rowsPerPageOptions="[5, 10, 20, 50]"
      responsiveLayout="scroll"
      currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
  >
    <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
    <Column field="title" :header="$t('Title')" :sortable="true">
      <template #body="slotProps">
        <a
            v-if="slotProps.data"
            @click="showHandler(slotProps.data)"
            class="cursor-pointer "
        >
          {{ slotProps.data.title }}
        </a>
      </template>
    </Column>

    <Column field="locale" :header="$t('Locale')" />
    <Column field="category.title" :header="$t('Category')" />
    <Column field="enabled" :header="$t('Enabled')" />

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row gap-2">
<!--          <Button class="btn btn-primary" @click="showHandler(slotProps.data)">-->
<!--            <v-icon icon="mdi-information"/>-->
<!--          </Button>-->

          <Button v-if="isAuthenticated" class="btn btn-primary p-mr-2" @click="editHandler(slotProps.data)">
            <v-icon icon="mdi-pencil"/>
          </Button>

          <Button v-if="isAuthenticated" class="btn btn-danger" @click="confirmDeleteItem(slotProps.data)" >
            <v-icon icon="mdi-delete"/>
          </Button>
        </div>
      </template>
    </Column>
  </DataTable>

  <Dialog v-model:visible="itemDialog" :style="{width: '450px'}" :header="$t('New folder')" :modal="true" class="p-fluid">
    <div class="p-field">
      <label for="name">{{ $t('Name') }}</label>
      <InputText
          autocomplete="off"
          id="title"
          v-model.trim="item.title"
          required="true"
          autofocus
          :class="{'p-invalid': submitted && !item.title}"
      />
      <small class="p-error" v-if="submitted && !item.title">$t('Title is required')</small>
    </div>

    <template #footer>
      <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="hideDialog"/>
      <Button label="Save" icon="pi pi-check" class="p-button-text" @click="saveItem" />
    </template>
  </Dialog>

  <Dialog v-model:visible="deleteItemDialog" :style="{width: '450px'}" header="Confirm" :modal="true">
    <div class="confirmation-content">
      <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem" />
      <span v-if="item">Are you sure you want to delete <b>{{item.title}}</b>?</span>
    </div>
    <template #footer>
      <Button label="No" icon="pi pi-times" class="p-button-text" @click="deleteItemDialog = false"/>
      <Button label="Yes" icon="pi pi-check" class="p-button-text" @click="deleteItemButton" />
    </template>
  </Dialog>

  <Dialog v-model:visible="deleteMultipleDialog" :style="{width: '450px'}" header="Confirm" :modal="true">
    <div class="confirmation-content">
      <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem" />
      <span v-if="item">{{ $t('Are you sure you want to delete the selected items?') }}</span>
    </div>
    <template #footer>
      <Button label="No" icon="pi pi-times" class="p-button-text" @click="deleteMultipleDialog = false"/>
      <Button label="Yes" icon="pi pi-check" class="p-button-text" @click="deleteMultipleItems" />
    </template>
  </Dialog>
</template>

<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import {useRoute, useRouter} from "vue-router";

export default {
  name: 'PageList',
  servicePrefix: 'Page',
  mixins: [ListMixin],
  components: {
  },
  setup() {
    const store = useStore();
    const route = useRoute();
  },
  data() {
    return {
      sortBy: 'title',
      sortDesc: false,
      columns: [
        { label: this.$i18n.t('Title'), field: 'title', name: 'title', sortable: true},
        { label: this.$i18n.t('Category'), field: 'category.title', name: 'category', sortable: true},
        { label: this.$i18n.t('Locale'), field: 'locale', name: 'locale', sortable: true},
        { label: this.$i18n.t('Modified'), field: 'updatedAt', name: 'updatedAt', sortable: true},
        { label: this.$i18n.t('Actions'), name: 'action', sortable: false}
      ],
      pageOptions: [10, 20, 50, this.$i18n.t('All')],
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
  mounted() {
    this.onUpdateOptions(this.options);
  },
  deletedPage(item) {
    this.showMessage(this.$i18n.t('{resource} deleted', {'resource': item['resourceNode'].title}));
    this.onUpdateOptions(this.options);
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
