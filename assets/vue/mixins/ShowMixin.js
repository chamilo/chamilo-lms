import isEmpty from 'lodash/isEmpty';
import NotificationMixin from './NotificationMixin';
import { formatDateTime } from '../utils/dates';
import {mapActions, mapGetters} from "vuex";

export default {
  mixins: [NotificationMixin],
  created() {
    // Changed
    let id = this.$route.params.id;
    if (isEmpty(id)) {
      id = this.$route.query.id;
    }
    this.retrieve(decodeURIComponent(id));
    //this.retrieve(decodeURIComponent(this.$route.params.id));
  },
  computed: {
    item() {
      // Changed
      let id = this.$route.params.id;
      if (isEmpty(id)) {
        id = this.$route.query.id;
      }
      let item = this.find(decodeURIComponent(id));

      return item;
      //return this.find(decodeURIComponent(this.$route.params.id));
    },
  },
  methods: {
    list() {
      this.$router
          .push({ name: `${this.$options.servicePrefix}List` })
          .catch(() => {});
    },
    del() {
      this.deleteItem(this.item).then(() => {
        let folderParams = this.$route.query;
        folderParams['id'] = '';
        //this.showMessage(`${this.item['@id']} deleted.`);
        this.$router
          .push(
              {
                name: `${this.$options.servicePrefix}List`,
                query: folderParams
              }
          )
          .catch(() => {});
      });
    },
    formatDateTime,
    editHandler() {
      let folderParams = this.$route.query;
      folderParams['id'] = this.item['@id'];

      this.$router.push({
        name: `${this.$options.servicePrefix}Update`,
        params: { id: this.item['@id'] },
        query: folderParams
      });
    },
  },
  watch: {
    error(message) {
      message && this.showError(message);
    },
    deleteError(message) {
      message && this.showError(message);
    }
  },
  beforeDestroy() {
    this.reset();
  }
};
