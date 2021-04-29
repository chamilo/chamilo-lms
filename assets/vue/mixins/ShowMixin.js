import isEmpty from 'lodash/isEmpty';
import NotificationMixin from './NotificationMixin';
import { formatDateTime } from '../utils/dates';
import {mapActions, mapGetters} from "vuex";
import toInteger from "lodash/toInteger";

export default {
  mixins: [NotificationMixin],
  created() {
    console.log('show mixin created');
    // Changed
    let id = this.$route.params.id;
    if (isEmpty(id)) {
      id = this.$route.query.id;
    }

    let cid = toInteger(this.$route.query.cid);
    let sid = toInteger(this.$route.query.sid);
    let gid = toInteger(this.$route.query.gid);
    id = decodeURIComponent(id);
    const params = {id, cid, sid, gid};

    this.retrieve(params);
    //this.retrieve(decodeURIComponent(this.$route.params.id));
  },
  computed: {
    item() {
      console.log('show mixin computed');
      // Changed
      let id = this.$route.params.id;
      if (isEmpty(id)) {
        id = this.$route.query.id;
      }

      let item = this.find(decodeURIComponent(id));

      if (isEmpty(item)) {
        console.log('error item is empty');
        let folderParams = this.$route.query;
        delete folderParams['id'];
        this.$router
            .push(
                {
                  name: `${this.$options.servicePrefix}List`,
                  query: folderParams
                }
            )
            .catch(() => {});
      }


      return item;
      //return this.find(decodeURIComponent(this.$route.params.id));
    },
  },
  methods: {
    list() {
      console.log('show mixin list');
      this.$router
          .push({ name: `${this.$options.servicePrefix}List` })
          .catch(() => {});
    },
    del() {
      console.log('show mixin del');
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
      console.log('show mixin editHandler');
      let folderParams = this.$route.query;
      if (!isEmpty(this.item)) {
        folderParams['id'] = this.item['@id'];
      }

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
