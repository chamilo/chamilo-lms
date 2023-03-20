//import './styles/quasar.sass'
import '@quasar/extras/material-icons/material-icons.css'

import {Dialog, Notify} from 'quasar'

export default {
  config: {
    notify: {
    }
  },
  plugins: [
    Notify,
    Dialog,
  ],
  extras: [
    'material-icons',
  ]
}