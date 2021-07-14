import NotificationMixin from './NotificationMixin';
import { formatDateTime } from '../utils/dates';
import isEmpty from 'lodash/isEmpty';
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

      // @todo this should be built in in the VueMultiselect component.
      if (isEmpty(createForm.v$.item.$model.receivers)) {
        this.showMessage('Select a user', 'warning');
      }

      if (!createForm.v$.$invalid) {
        let users = [];
        createForm.v$.item.$model.receivers.forEach(user => {
          // Send to inbox
          users.push({receiver: user['@id']});
        });

        createForm.v$.item.$model.sender = '/api/users/' + this.currentUser.id;
        createForm.v$.item.$model.receivers = users;
        createForm.v$.item.$model.msgType = 1;
        this.create(createForm.v$.item.$model);
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
