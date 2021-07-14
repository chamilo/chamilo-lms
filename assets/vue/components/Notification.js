import { useQuasar } from 'quasar';

/**
 * Use this when using Vue 3 composition api (setup)
 */
export default function () {
    const $q = useQuasar();

    function showNotification (message, type = 'success') {
        let color = 'primary';
        let icon = 'info';

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
        if ('danger' === type) {
            type = 'error';
        }

        $q.notify({
            position: 'top',
            timeout: 10000,
            message: message,
            color: color,
            html: true,
            multiLine: true,
        });
    }

    return {showNotification};
}
