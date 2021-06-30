import NotificationMixin from './NotificationMixin';
import { formatDateTime } from '../utils/dates';

export default {
  mixins: [NotificationMixin],
  methods: {
    formatDateTime,
    onCreated(item) {
      if (item['resourceNode']) {
        this.showMessage(this.$i18n.t('{resource} created', {'resource': item['resourceNode'].title}));
      } else {
        this.showMessage(this.$i18n.t('{resource} created', {'resource': item.title}));
      }

      let folderParams = this.$route.query;

      this.$router.push({
        name: `${this.$options.servicePrefix}List`,
        params: {id: item['@id']},
        query: folderParams
      });
    },
    onSendForm() {
      const createForm = this.$refs.createForm;
      createForm.v$.$touch();
      if (!createForm.v$.$invalid) {
        this.create(createForm.v$.item.$model);
      }
    },
    onSendFormData() {
      const createForm = this.$refs.createForm;
      createForm.v$.$touch();
      if (!createForm.v$.$invalid) {
        this.createWithFormData(createForm.v$.item.$model);
      }
    },
    onSendMessageForm() {
      const createForm = this.$refs.createForm;
      createForm.v$.$touch();

      if (!createForm.v$.$invalid) {
        createForm.v$.item.$model.receivers.forEach(user => {
          // Send to inbox
          createForm.v$.item.$model.userSender = '/api/users/' + this.currentUser.id;
          createForm.v$.item.$model.userReceiver = user['@id'];
          createForm.v$.item.$model.msgStatus = 1;
          this.create(createForm.v$.item.$model);
        });
      }
    },
    resetForm() {
      this.$refs.createForm.$v.$reset();
      this.item = {};
    }
  },
  watch: {
    created(created) {
      console.log('created');
      console.log(created);

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
