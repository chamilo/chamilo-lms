import NotificationMixin from "./NotificationMixin"
import { formatDateTime } from "../utils/dates"

export default {
  mixins: [NotificationMixin],
  methods: {
    formatDateTime,
    onCreated(item) {
      let message
      if (item["resourceNode"]) {
        message =
          this.$i18n && this.$i18n.t
            ? this.$t("{0} created", [item["resourceNode"].title])
            : `${item["resourceNode"].title} created`
      } else {
        message = this.$i18n && this.$i18n.t ? this.$t("{0} created", [item.title]) : `${item.title} created`
      }

      this.showMessage(message)
      let folderParams = this.$route.query

      this.$router.push({
        name: `${this.$options.servicePrefix}List`,
        params: { id: item["@id"] },
        query: folderParams,
      })
    },
    onSendForm() {
      const createForm = this.$refs.createForm
      createForm.v$.$touch()
      if (!createForm.v$.$invalid) {
        this.create(createForm.v$.item.$model)
      }
    },
    onSendFormData() {
      const createForm = this.$refs.createForm
      createForm.v$.$touch()
      if (!createForm.v$.$invalid) {
        this.createWithFormData(createForm.v$.item.$model)
      }
    },
    resetForm() {
      this.$refs.createForm.$v.$reset()
      this.item = {}
    },
  },
  watch: {
    created(created) {
      console.log("CreateMixin.js::created")
      console.log(created)

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
