import NotificationMixin from "./NotificationMixin"
import { formatDateTime } from "../utils/dates"

export default {
  mixins: [NotificationMixin],
  methods: {
    formatDateTime,
    onCreated(item) {
      this.showMessage(this.$i18n.t("{0} created", [item.resourceNode.title]))
    },
    onUploadForm() {
      console.log("onUploadForm")
      const createForm = this.$refs.createForm
      for (let i = 0; i < createForm.files.length; i++) {
        let file = createForm.files[i]
        this.create(file)
      }
    },
  },
  watch: {
    created(created) {
      if (!created) {
        return
      }

      this.onCreated(created)
    },

    error(message) {
      message && this.showError(message)
    },
  },
}
