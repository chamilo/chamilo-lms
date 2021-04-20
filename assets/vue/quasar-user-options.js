import './styles/quasar.sass'
import '@quasar/extras/material-icons/material-icons.css'
import '@quasar/extras/fontawesome-v5/fontawesome-v5.css'

import { Notify } from 'quasar'

export default {
  config: {
    notify: {
    }
  },
  plugins: [
    Notify
  ],
  extras: [
    'material-icons',
    'fontawesome-v5',
  ]
}