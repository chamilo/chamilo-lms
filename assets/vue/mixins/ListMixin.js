import isEmpty from 'lodash/isEmpty';
import isString from 'lodash/isString';
import isBoolean from 'lodash/isBoolean';
import toInteger from 'lodash/toInteger';

import { formatDateTime } from '../utils/dates';
import NotificationMixin from './NotificationMixin';

export default {
  mixins: [NotificationMixin],
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
      let nodeId = this.$route.params['node'];
      if (!isEmpty(nodeId)) {
        let cid = toInteger(this.$route.query.cid);
        let sid = toInteger(this.$route.query.sid);
        let gid = toInteger(this.$route.query.gid);
        let id = '/api/resource_nodes/'+ nodeId;
        const params = {id, cid, sid, gid};

        console.log(params);

        this.findResourceNode(params);
      }
      /*this.onRequest({
        pagination: this.pagination,
      });*/

      this.onUpdateOptions(this.options);
    },

    deletedItem(item) {
      console.log('deletedItem');
      this.showMessage(this.$i18n.t('{resource} deleted', {'resource': item['resourceNode'].title}));
      this.onUpdateOptions(this.options);
    },

    error(message) {
      console.log('error');
      message && this.showError(message);
    },

    items() {
      //this.pagination.rowsNumber = this.totalItems;
      //this.onUpdateOptions(this.options);

      /*
      console.log('items');
      this.pagination.page = this.nextPage;
      if (isEmpty(this.pagination.page)) {
        this.pagination.page = 1;
      }
      console.log(this.pagination.page );
      this.pagination.rowsNumber = this.totalItems;
      this.nextPage = null;*/
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
    },
    onUpdateOptions({ page, itemsPerPage, sortBy, sortDesc, totalItems } = {}) {
      console.log('ListMixin.js: onUpdateOptions');
      let params = {
        ...this.filters
      }

      if (itemsPerPage > 0) {
        params = { ...params, itemsPerPage, page };
      }

      if (this.$route.params.node) {
        params[`resourceNode.parent`] = this.$route.params.node;
      }

      // prime
      if (!isEmpty(sortBy)) {
        params[`order[${sortBy}]`] = sortDesc ? 'desc' : 'asc'
      }

      let cid = toInteger(this.$route.query.cid);
      let sid = toInteger(this.$route.query.sid);
      let gid = toInteger(this.$route.query.gid);
      let type = this.$route.query.type;

      params = { ...params, cid, sid, gid, type };

      // vuetify
      /*if (!isEmpty(sortBy) && !isEmpty(sortDesc)) {
        params[`order[${sortBy[0]}]`] = sortDesc[0] ? 'desc' : 'asc'
      }*/
      console.log(params);

      this.resetList = true;
      this.getPage(params).then(() => {
        this.options.sortBy = sortBy;
        this.options.sortDesc = sortDesc;
        this.options.itemsPerPage = itemsPerPage;
        this.options.totalItems = totalItems;
      });
    },

    fetchNewItems({ page, itemsPerPage, sortBy, sortDesc, totalItems } = {}) {
      console.log('fetchNewItems');
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

      this.options.sortBy = sortBy;
      this.options.sortDesc = sortDesc;
      this.options.itemsPerPage = itemsPerPage;
      this.options.totalItems = totalItems;

    },

    onSendFilter() {
      console.log('onSendFilter');
      this.resetList = true;
      this.pagination.page = 1;
      this.onRequest({pagination: this.pagination})
    },

    resetFilter() {
      console.log('resetFilter');
      this.filters = {};
      this.pagination.page = 1;
      this.onRequest({pagination: this.pagination})
    },

    addHandler() {
      console.log('addHandler');
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
      console.log('listmixin showHandler');
      let folderParams = this.$route.query;
      console.log(folderParams, 'folderParams');
      console.log(this.$route.params, 'params');
      if (item) {
        folderParams['id'] = item['@id'];
      }

      /*let cid = toInteger(this.$route.query.cid);
      let sid = toInteger(this.$route.query.sid);
      let gid = toInteger(this.$route.query.gid);

      folderParams['cid'] = cid;
      folderParams['sid'] = sid;
      folderParams['gid'] = gid;*/

      console.log(folderParams);

      this.$router.push({
        name: `${this.$options.servicePrefix}Show`,
        //params: { id: item['@id'] },
        //params: folderParams,
        query: folderParams
      });
    },

    handleClick(item) {
      let folderParams = this.$route.query;
      this.resetList = true;
      let resourceId = item['resourceNode']['id'];
      this.$route.params.node = resourceId;
      //folderParams['node'] = resourceId;

      this.$router.push({
        name: `${this.$options.servicePrefix}List`,
        params: {node: resourceId},
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
      console.log('editHandler');
      let folderParams = this.$route.query;
      folderParams['id'] = item['@id'];

      if ('folder' === item.filetype || isEmpty(item.filetype)) {
        this.$router.push({
          name: `${this.$options.servicePrefix}Update`,
          params: { id: item['@id'] },
          query: folderParams
        });
      }

      if ('file' === item.filetype) {
        folderParams['getFile'] = true;
        if (item.resourceNode.resourceFile &&
            item.resourceNode.resourceFile.mimeType &&
            'text/html' === item.resourceNode.resourceFile.mimeType) {
          //folderParams['getFile'] = true;
        }

        this.$router.push({
          name: `${this.$options.servicePrefix}UpdateFile`,
          params: { id: item['@id'] },
          query: folderParams
        });
      }
    },
    deleteHandler(item) {
      console.log('deleteHandler');
      console.log(item);
      this.pagination.page = 1;
      this.deleteItem(item).then(() =>
          this.onRequest({pagination: this.pagination})
      );
    },
    formatDateTime
  }
};
