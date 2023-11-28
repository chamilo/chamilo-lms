import { mapFields } from 'vuex-map-fields';

export default {
  methods: {
    showError(error) {
      this.showMessage(error, 'error'); // Use 'error' for PrimeVue
    },
    showMessage(message, type = 'success') {
      // Convert message type to PrimeVue's severity
      let severity = type;
      if (type === 'danger') {
        severity = 'error'; // PrimeVue uses 'error' instead of 'danger'
      }

      // Use PrimeVue's ToastService
      this.$toast.add({
        severity: severity,
        summary: message,
        detail: '',
        life: 5000,  // Message duration in milliseconds
        closable: true,  // Whether the message can be closed manually
      });
    }
  },
  computed: {
    ...mapFields('notifications', ['color', 'show', 'subText', 'text', 'timeout'])
  },
};
