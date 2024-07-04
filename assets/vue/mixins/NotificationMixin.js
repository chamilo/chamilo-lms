import { mapFields } from 'vuex-map-fields';
import { useQuasar } from 'quasar';

export default {
  setup() {
    const quasar = useQuasar();

    const showError = (error) => {
      showMessage(error, 'danger');
    };

    const showMessage = (message, type = 'success') => {
      let color = 'primary';

      switch (type) {
        case 'info':
          break;
        case 'success':
          color = 'green';
          break;
        case 'error':
        case 'danger':
          color = 'red';
          break;
        case 'warning':
          color = 'yellow';
          break;
      }

      quasar.notify({
        position: 'top',
        timeout: 10000,
        message: message,
        color: color,
        html: true,
        multiLine: true,
      });
    };

    return {
      showError,
      showMessage
    };
  },
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
