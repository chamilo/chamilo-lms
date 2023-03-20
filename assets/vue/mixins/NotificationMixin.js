import {mapFields} from 'vuex-map-fields';
import Snackbar from "../components/Snackbar.vue";
//import { useToast } from "vue-toastification";
// inside of a Vue file

//import { useQuasar } from 'quasar'

export default {
    setup() {
    },
    computed: {
        ...mapFields('notifications', ['color', 'show', 'subText', 'text', 'timeout'])
    },
    methods: {
        cleanState() {
            /*setTimeout(() => {
                  this.show = false;
                }, this.timeout
            );*/
        },
        showError(error) {
            this.showMessage(error, 'danger');
        },
        showMessage(message, type = 'success') {
            /*const content = {
                // Your component or JSX template
                component: Snackbar,
                // Props are just regular props, but these won't be reactive
                props: {
                    message: message
                },
                // Listeners will listen to and execute on event emission
                listeners: {
                    //click: () => console.log("Clicked!"),
                    //myEvent: myEventHandler
                }
            };*/

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
                    icon: 'error';
                    break;
                case 'warning':
                    color = 'yellow';
                    break;

            }
            if ('danger' === type) {
                type = 'error';
            }

            this.$q.notify({
                position: 'top',
                timeout: 10000,
                message: message,
                color: color,
                html: true,
                multiLine: true,
            })

            /*const toast = useToast();
            console.log('toast');
            console.log(message);
            console.log(content);

            toast(content, {
                type: type,
                position: 'top-center',
                timeout: 10000, // 10 seconds
                closeOnClick: false,
                pauseOnFocusLoss: true,
                pauseOnHover: true,
                draggable: true,
                draggablePercent: 0.6,
                showCloseButtonOnHover: false,
                hideProgressBar: true,
                closeButton: "button",
                icon: true,
                rtl: false
            });*/

            /*this.show = true;
            this.color = color;
            if (typeof message === 'string') {
              this.text = message;
              this.cleanState();

              return;
            }
            this.text = message.message;
            if (message.response) this.subText = message.response.data.message;
            this.cleanState();*/
        }
    }
};
