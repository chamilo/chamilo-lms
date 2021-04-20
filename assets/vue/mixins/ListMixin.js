import isEmpty from 'lodash/isEmpty';
import isString from 'lodash/isString';
import isBoolean from 'lodash/isBoolean';
import { formatDateTime } from '../utils/dates';
import NotificationMixin from './NotificationMixin';

export default {
  mixins: [NotificationMixin],
  created() {
    //console.log('created');
  },
  data() {
    return {
      pagination: {
        sortBy: null,
        descending: false,
        page: 1, // page to be displayed
        rowsPerPage: 3, // maximum displayed rows
        rowsNumber: 10, // max number of rows
      },
      nextPage: null,
      filters: {},
      filtration: {},
      expandedFilter: false,
      options: {
        //sortBy: [], vuetify
        //sortDesc: [], , vuetify
        page: 1,
        itemsPerPage: 20
      },
      //filters: {}
    };
  },
  watch: {
    $route() {
      console.log('watch listmixin');
      // react to route changes...
      this.resetList = true;
      this.onUpdateOptions(this.options);
      let nodeId = this.$route.params['node'];
      if (!isEmpty(nodeId)) {
        this.findResourceNode('/api/resource_nodes/'+ nodeId);
      }
    },

    deletedItem(item) {
      this.showMessage(this.$i18n.t('{resource} deleted', {'resource': item['resourceNode'].title}));
      // this.showMessage(`${item['@id']} deleted.`);
    },

    error(message) {
      message && this.showError(message);
    },

    items() {
      this.options.totalItems = this.totalItems;
    }
  },
  methods: {
    onRequest(props, init) {
      const { pagination: { page, rowsPerPage: itemsPerPage, sortBy, descending }} = props;
      this.nextPage = page;
      let params = {
        ...this.filtration,
      };
      if (itemsPerPage > 0) {
        params = { ...params, itemsPerPage, page };
      }
      if (sortBy) {
        params[`order[${sortBy}]`] = descending ? "DESC" : "ASC";
      }
      this.getPage({ params }).then(() => {
        this.pagination.sortBy = sortBy;
        this.pagination.descending = descending;
        this.pagination.rowsPerPage = itemsPerPage;
        if (!init) {
          this.filters = { ...this.filtration };
        }
      });
    },
    fetchNewItems({ page, itemsPerPage, sortBy, sortDesc, totalItems } = {}) {
      let params = {
        ...this.filters
      };

      if (itemsPerPage > 0) {
        params = { ...params, itemsPerPage, page };
      }

      if (this.$route.params.node) {
        params[`resourceNode.parent`] = this.$route.params.node;
      }

      if (isString(sortBy) && isBoolean(sortDesc)) {
        //params[`order[${sortBy[0]}]`] = sortDesc[0] ? 'desc' : 'asc'
        params[`order[${sortBy}]`] = sortDesc ? 'desc' : 'asc'
      }

      //this.resetList = true;

      //this.getPage(params).then(() => {
      this.options.sortBy = sortBy;
      this.options.sortDesc = sortDesc;
      this.options.itemsPerPage = itemsPerPage;
      this.options.totalItems = totalItems;
      //});
    },
    onUpdateOptions({ page, itemsPerPage, sortBy, sortDesc, totalItems, getPage } = {}) {
      let params = {
        ...this.filters
      };

      if (itemsPerPage > 0) {
        params = { ...params, itemsPerPage, page };
      }

      if (this.$route.params.node) {
        params[`resourceNode.parent`] = this.$route.params.node;
      }

      if (isString(sortBy) && isBoolean(sortDesc)) {
        //params[`order[${sortBy[0]}]`] = sortDesc[0] ? 'desc' : 'asc'
        params[`order[${sortBy}]`] = sortDesc ? 'desc' : 'asc'
      }

      this.resetList = true;
      console.log('onUpdateOptions');
      console.log(params);
      if (getPage) {
        getPage(params).then(() => {
          this.options.sortBy = sortBy;
          this.options.sortDesc = sortDesc;
          this.options.itemsPerPage = itemsPerPage;
          this.options.totalItems = totalItems;
        });
      }
      //console.log('end');
    },

    onSendFilter() {
      this.resetList = true;
      this.onUpdateOptions(this.options);
    },

    resetFilter() {
      this.filters = {};
      this.onUpdateOptions(this.options);
    },

    addHandler() {
      let folderParams = this.$route.query;
      this.$router.push({name: `${this.$options.servicePrefix}Create`, query: folderParams});
    },

    addDocumentHandler() {
      let folderParams = this.$route.query;
      this.$router.push({ name: `${this.$options.servicePrefix}CreateFile` , query: folderParams});
    },

    uploadDocumentHandler() {
      let folderParams = this.$route.query;
      this.$router.push({ name: `${this.$options.servicePrefix}UploadFile` , query: folderParams});
    },

    showHandler(item) {
      let folderParams = this.$route.query;
      folderParams['id'] = item['@id'];

      this.$router.push({
        name: `${this.$options.servicePrefix}Show`,
        //params: { id: item['@id'] },
        query: folderParams
      });
    },

    handleClick(item) {
      let folderParams = this.$route.query;
      this.resetList = true;
      this.$route.params.node = item['resourceNode']['id'];

      this.$router.push({
        name: `${this.$options.servicePrefix}List`,
        params: {node: item['resourceNode']['id']},
        query: folderParams,
      });

      /*this.$router.push({
        name: `${this.$options.servicePrefix}List`,
        params: {node: item['resourceNode']['id']}
      });*/
      /*console.log(item['resourceNode']['id']);
      this.$route.params.node = item['resourceNode']['id'];
      this.onUpdateOptions(this.options);*/
    },
    editHandler(item) {
      let folderParams = this.$route.query;
      folderParams['id'] = item['@id'];

      if ('folder' === item.filetype) {
        this.$router.push({
          name: `${this.$options.servicePrefix}Update`,
          params: { id: item['@id'] },
          query: folderParams
        });
      }

      if ('file' === item.filetype) {
        folderParams['getFile'] = false;

        if (item.resourceNode.resourceFile &&
            item.resourceNode.resourceFile.mimeType &&
            'text/html' === item.resourceNode.resourceFile.mimeType) {
          folderParams['getFile'] = true;
        }

        this.$router.push({
          name: `${this.$options.servicePrefix}UpdateFile`,
          params: { id: item['@id'] },
          query: folderParams
        });
      }
    },
    deleteHandler(item) {
      this.deleteItem(item).then(() => this.onUpdateOptions(this.options));
    },
    formatDateTime
  }
};
