import NotificationMixin from './NotificationMixin';
import { formatDateTime } from '../utils/dates';
import isEmpty from 'lodash/isEmpty';
import {MESSAGE_TYPE_INBOX} from "../components/message/constants";

export default {
  mixins: [NotificationMixin],
  methods: {
    formatDateTime,
    onCreated(item) {
      let message;
      if (item['resourceNode']) {
        message = this.$i18n && this.$i18n.t
          ? this.$t('{resource} created', {'resource': item['resourceNode'].title})
          : `${item['resourceNode'].title} created`;
      } else {
        message = this.$i18n && this.$i18n.t
          ? this.$t('{resource} created', {'resource': item.title})
          : `${item.title} created`;
      }

      this.showMessage(message);
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
      if (isEmpty(createForm.v$.item.$model.receiversTo)) {
        this.showMessage('Select a user', 'warning');
      }

      if (!createForm.v$.$invalid) {
        let users = [];
        createForm.v$.item.$model.receiversTo.forEach(user => {
          // Send to inbox
          users.push({receiver: user['@id'], receiverType: 1});
        });

        if (createForm.v$.item.$model.receiversCc) {
          createForm.v$.item.$model.receiversCc.forEach(user => {
            // Send to inbox
            users.push({receiver: user['@id'], receiverType: 2});
          });
        }

        createForm.v$.item.$model.sender = '/api/users/' + this.currentUser.id;
        createForm.v$.item.$model.receivers = users;
        createForm.v$.item.$model.msgType = MESSAGE_TYPE_INBOX;
        this.create(createForm.v$.item.$model);
      }
    },
    onReplyMessageForm() {
      const createForm = this.$refs.createForm;
      createForm.v$.$touch();

      if (!createForm.v$.$invalid) {
        let users = [];

        // Send to original sender.
        users.push({receiver: createForm.v$.item.$model.originalSender['@id'], receiverType: 1});

        // Check Ccs
        if (createForm.v$.item.$model.receiversCc) {
          createForm.v$.item.$model.receiversCc.forEach(user => {
            // Send to inbox
            users.push({receiver: user.receiver['@id'], receiverType: 2});
          });
        }

        createForm.v$.item.$model.sender = '/api/users/' + this.currentUser.id;
        createForm.v$.item.$model.receiversTo = null;
        createForm.v$.item.$model.receiversCc = null;
        createForm.v$.item.$model.receivers = users;
        createForm.v$.item.$model.msgType = MESSAGE_TYPE_INBOX;
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
      console.log('CreateMixin.js::created');
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
