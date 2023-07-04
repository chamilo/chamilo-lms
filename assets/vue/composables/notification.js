import {useToast} from "primevue/usetoast";

// This is the migration from assets/vue/mixins/NotificationMixin.js to composables
// some components still use UploadMixin with options API, this should be use
// when migrating from options API to composition API
export function useNotification() {
  const toast = useToast();

  const showSuccessNotification = (message) => {
    showMessage(message, 'success')
  }

  const showInfoNotification = (error) => {
    showMessage(error, 'info');
  }

  const showWarningNotification = (message) => {
    showMessage(message, 'warning')
  }

  const showErrorNotification = (error) => {
    let message = error;

    if (error.response) {
      if (error.response.data) {
        message = error.response.data['hydra:description'];
      }
    } else if (error.message) {
      message = error.message;
    }

    showMessage(message, 'error');
  }

  const showMessage = (message, severity) => {
    toast.add({
      severity: severity,
      detail: message,
      life: 3500,
    });
  }

  return {
    showSuccessNotification,
    showInfoNotification,
    showWarningNotification,
    showErrorNotification,
  }
}
