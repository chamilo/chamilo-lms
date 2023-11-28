import { mapFields } from 'vuex-map-fields';
import { useQuasar } from 'quasar';
import Snackbar from "../components/Snackbar.vue";

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
  computed: {
    ...mapFields('notifications', ['color', 'show', 'subText', 'text', 'timeout'])
  },
  // Otros métodos y propiedades si los necesitas
};
