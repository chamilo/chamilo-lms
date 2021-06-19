import './styles/quasar.sass'
import '@quasar/extras/material-icons/material-icons.css'

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
  ]
}