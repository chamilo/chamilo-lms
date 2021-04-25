import NotificationMixin from './NotificationMixin';
import { formatDateTime } from '../utils/dates';
import isEmpty from "lodash/isEmpty";

export default {
  mixins: [NotificationMixin],
  data() {
    return {
      item: {},
      options: {
        sortBy: [],
        page: 1,
        itemsPerPage: 15
      },
    };
  },
  created() {
    // Changed
    let id = this.$route.params.id;
    if (isEmpty(id)) {
      id = this.$route.query.id;
    }
    this.retrieve(decodeURIComponent(id));
    // default
    //this.retrieve(decodeURIComponent(this.$route.params.id));
  },
  beforeDestroy() {
    this.reset();
  },
  computed: {
    retrieved() {
      let id = this.$route.params.id;
      if (isEmpty(id)) {
        id = this.$route.query.id;
      }
      let item = this.find(decodeURIComponent(id));

      return item;
      //return this.find(decodeURIComponent(this.$route.params.id));
    }
  },
  methods: {
    del() {
      this.deleteItem(this.retrieved).then(() => {
        let folderParams = this.$route.query;
        this.showMessage(`${this.item['@id']} deleted.`);
        this.$router
          .push({
            name: `${this.$options.servicePrefix}List`,
            query: folderParams
          })
          .catch(() => {});
      });
    },
    formatDateTime,
    reset() {
      this.$refs.updateForm.$v.$reset();
      this.updateReset();
      this.delReset();
      this.createReset();
    },

    onSendForm() {
      const updateForm = this.$refs.updateForm;
      updateForm.v$.$touch();
      console.log('onSendForm');
      if (!updateForm.v$.$invalid) {
        this.update(updateForm.v$.item.$model);
      }
    },

    resetForm() {
      this.$refs.updateForm.v$.$reset();
      this.item = { ...this.retrieved };
    }
  },
  watch: {
    deleted(deleted) {
      if (!deleted) {
        return;
      }

      let folderParams = this.$route.query;
      this.$router
        .push({
          name: `${this.$options.servicePrefix}List`,
          query: folderParams
        })
        .catch(() => {});
    },

    error(message) {
      message && this.showError(message);
    },

    deleteError(message) {
      message && this.showError(message);
    },

    updated(val) {
      this.showMessage(`${val['@id']} updated.`);
    },

    retrieved(val) {
      this.item = { ...val };
    }
  }
};
