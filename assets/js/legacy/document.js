import { createPinia, setActivePinia } from 'pinia'

const pinia = createPinia()
setActivePinia(pinia)

import translateHtml from './../translatehtml.js'
document.addEventListener('DOMContentLoaded', function () {
  translateHtml()
});
