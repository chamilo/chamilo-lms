import {mapFields} from 'vuex-map-fields';
import Component from "../components/Snackbar.vue";

export default {
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
            const content = {
                // Your component or JSX template
                component: Component,

                // Props are just regular props, but these won't be reactive
                props: {
                    message: message
                },

                // Listeners will listen to and execute on event emission
                listeners: {
                    //click: () => console.log("Clicked!"),
                    //myEvent: myEventHandler
                }
            };

            if ('danger' === type) {
                type = 'error';
            }

            this.$toast(content, {
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
            });

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
