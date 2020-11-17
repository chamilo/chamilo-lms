import isEmpty from 'lodash/isEmpty';
import { formatDateTime } from '../utils/dates';
import NotificationMixin from './NotificationMixin';

export default {
  mixins: [NotificationMixin],
  data() {
    return {
      options: {
        sortBy: [],
        page: 1,
        itemsPerPage: 15
      },
      filters: {}
    };
  },

  watch: {
    $route() {
      // react to route changes...
      this.resetList = true;
      this.onUpdateOptions(this.options);
      let nodeId = this.$route.params['node'];
      this.findResourceNode('/api/resource_nodes/'+ nodeId);
    },

    deletedItem(item) {
      this.showMessage(`${item['@id']} deleted.`);
    },

    error(message) {
      message && this.showError(message);
    },

    items() {
      this.options.totalItems = this.totalItems;
    }
  },

  methods: {
    onUpdateOptions({ page, itemsPerPage, sortBy, sortDesc, totalItems } = {}) {
      let params = {
        ...this.filters
      };
      if (itemsPerPage > 0) {
        params = { ...params, itemsPerPage, page };
      }

      if (this.$route.params.node) {
        params[`resourceNode.parent`] = this.$route.params.node;
      }

      if (!isEmpty(sortBy) && !isEmpty(sortDesc)) {
        params[`order[${sortBy[0]}]`] = sortDesc[0] ? 'desc' : 'asc'
      }

      this.resetList = true;

      this.getPage(params).then(() => {
        this.options.sortBy = sortBy;
        this.options.sortDesc = sortDesc;
        this.options.itemsPerPage = itemsPerPage;
        this.options.totalItems = totalItems;
      });
    },

    onSendFilter() {
      this.resetList = true;
      this.onUpdateOptions(this.options);
    },

    resetFilter() {
      this.filters = {};
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
