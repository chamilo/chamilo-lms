import {useQuasar} from "quasar";

const showMessage = function (message, type = 'success') {
    const $q = useQuasar();

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
            break
    }

    $q.notify({
        position: 'top',
        timeout: 10000,
        message: message,
        color: color,
        html: true,
        multiLine: true,
    });
};

export default showMessage;
