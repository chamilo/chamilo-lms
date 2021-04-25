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
        sortBy: 'resourceNode.title',
        descending: false,
        page: 1, // page to be displayed
        rowsPerPage: 10, // maximum displayed rows
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
        itemsPerPage: 10
      },
      //filters: {}
    };
  },
  watch: {
    $route() {
      console.log('watch listmixin');
      // react to route changes...
      this.resetList = true;

      this.onRequest({
        pagination: this.pagination,
      });
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
      this.pagination.page = this.nextPage;
      if (isEmpty(this.pagination.page)) {
        this.pagination.page = 1;
      }
      this.pagination.rowsNumber = this.totalItems;
      this.nextPage = null;
      //this.options.totalItems = this.totalItems;
    }
  },
  methods: {
    onRequest(props) {
      console.log('onRequest');
      console.log(props);
      const { page, rowsPerPage: itemsPerPage, sortBy, descending } = props.pagination;
      const filter = props.filter;

      this.nextPage = page;
      if (isEmpty(this.nextPage)) {
        this.nextPage = 1;
      }

      let params = {};
      if (itemsPerPage > 0) {
        params = { ...params, itemsPerPage, page };
      }

      if (sortBy) {
        params[`order[${sortBy}]`] = descending ? "desc" : "asc";
      }

      if (this.$route.params.node) {
        params[`resourceNode.parent`] = this.$route.params.node;
      }

      console.log(params);
      this.resetList = true;
      this.getPage(params).then(() => {
        //this.options.sortBy = sortBy;
        //this.options.sortDesc = sortDesc;
        //this.options.itemsPerPage = itemsPerPage;
        //this.options.totalItems = totalItems;
        this.pagination.sortBy = sortBy;
        this.pagination.descending = descending;
        this.pagination.rowsPerPage = itemsPerPage;
      });

      /*this.getPage({ params }).then(() => {
        this.pagination.sortBy = sortBy;
        this.pagination.descending = descending;
        this.pagination.rowsPerPage = itemsPerPage;
        //this.filters = { ...this.filter };
      });*/
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

    onSendFilter() {
      this.resetList = true;
      this.pagination.page = 1;
      this.onRequest({pagination: this.pagination})
    },

    resetFilter() {
      this.filters = {};
      this.pagination.page = 1;
      this.onRequest({pagination: this.pagination})
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
      console.log(item);
      this.pagination.page = 1;
      this.deleteItem(item).then(() =>
          this.onRequest({pagination: this.pagination})
      );
    },
    formatDateTime
  }
};
