import { createPinia, setActivePinia } from "pinia"
import translateHtml from "./../translatehtml.js"

const pinia = createPinia()
setActivePinia(pinia)

document.addEventListener("DOMContentLoaded", function () {
  translateHtml()
})
