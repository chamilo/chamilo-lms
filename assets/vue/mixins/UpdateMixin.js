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
    console.log('mixin update created');
    // Changed
    let id = this.$route.params.id;
    if (isEmpty(id)) {
      id = this.$route.query.id;
    }
    console.log(id);
    if (!isEmpty(id)) {
      // Ajax call
      this.retrieve(decodeURIComponent(id));
      console.log(this.item);
    }
    // default
    //this.retrieve(decodeURIComponent(this.$route.params.id));
  },
  beforeDestroy() {
    this.reset();
  },
  computed: {
    retrieved() {
      // call from list
      console.log('update mixin retrieved');

      let id = this.$route.params.id;
      console.log('first');
      console.log(id);
      if (isEmpty(id)) {
        console.log('second');
        id = this.$route.query.id;
        console.log(id);
      }

      if (!isEmpty(id)) {
        let item = this.find(decodeURIComponent(id));

        if (isEmpty(item)) {
          this.retrieve(decodeURIComponent(id));
        }

        return item;
      }


      //return this.find(decodeURIComponent(this.$route.params.id));
    }
  },
  methods: {
    del() {
      console.log('mixin del');
      //let item = this.retrieved;

      console.log(this.item);

      this.deleteItem(this.item).then(() => {
        console.log('deleteItem resykt');
        let folderParams = this.$route.query;

        delete folderParams['id'];
        delete folderParams['getFile'];

        //this.showMessage(`${this.item['@id']} deleted.`);
        this.$router
          .push({
            name: `${this.$options.servicePrefix}List`,
            query: folderParams
          })
          .catch(() => {});
      });
      console.log('end mixin del()');
    },
    formatDateTime,
    reset() {
      this.$refs.updateForm.v$.$reset();
      this.updateReset();
      this.delReset();
      this.createReset();
    },
    onSendForm() {
      console.log('onSendForm');
      const updateForm = this.$refs.updateForm;
      updateForm.v$.$touch();
      if (!updateForm.v$.$invalid) {
        this.update(updateForm.v$.item.$model);
        this.item = { ...this.retrieved };
      }
    },

    resetForm() {
      console.log('resetForm');
      this.$refs.updateForm.v$.$reset();
      this.item = { ...this.retrieved };
    }
  },
  watch: {
    deleted(deleted) {
      console.log('deleted');
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
      console.log('error');
      message && this.showError(message);
    },

    deleteError(message) {
      console.log('deleteError');
      message && this.showError(message);
    },

    updated(val) {
      console.log('updated');
      this.showMessage(`${val['@id']} updated.`);
    },

    retrieved(val) {
      console.log('retrieved(val)');
      if (!isEmpty(val)) {
        this.item = {...val};
      }
    }
  }
};
