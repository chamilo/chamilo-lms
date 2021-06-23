import NotificationMixin from './NotificationMixin';
import { formatDateTime } from '../utils/dates';

export default {
  mixins: [NotificationMixin],
  methods: {
    formatDateTime,
    onCreated(item) {
      this.showMessage(this.$i18n.t('{resource} created', {'resource': item['resourceNode'].title}));
      const createForm = this.$refs.createForm;
      let folderParams = this.$route.query;

      /*this.$router.push({
        name: `${this.$options.servicePrefix}List`,
        params: {id: item['@id']},
        query: folderParams
      });*/
    },
    onUploadForm() {
      console.log('onUploadForm');
      const createForm = this.$refs.createForm;
      for (let i = 0; i < createForm.files.length; i++) {
        let file = createForm.files[i];
        this.create(file);
      }
    },
  },
  watch: {
    created(created) {
      if (!created) {
        return;
      }

      this.onCreated(created);
    },

    error(message) {
      message && this.showError(message);
    }
  }
};
