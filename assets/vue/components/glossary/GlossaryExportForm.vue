<template>
  <form @submit.prevent="submitForm" class="export-form">
    <div class="form-field">
      <label for="export-format">Export Format:</label>
      <select id="export-format" v-model="selectedFormat">
        <option value="csv">CSV</option>
        <option value="xls">Excel</option>
        <option value="pdf">PDF</option>
      </select>
    </div>
    <button type="submit" class="btn btn--primary">Export</button>
  </form>
</template>

<script setup>
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import { RESOURCE_LINK_PUBLISHED } from "../resource_links/visibility"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { ref } from "vue"

const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const selectedFormat = ref("csv")
const parentResourceNodeId = ref(Number(route.params.node))
const resourceLinkList = ref(
  JSON.stringify([
    {
      sid: route.query.sid,
      cid: route.query.cid,
      visibility: RESOURCE_LINK_PUBLISHED, // visible by default
    },
  ])
)

const submitForm = () => {
  const format = selectedFormat.value

  const formData = new FormData()
  formData.append("format", format)
  formData.append("sid", route.query.sid)
  formData.append("cid", route.query.cid)

  const endpoint = `${ENTRYPOINT}glossaries/export`
  axios
    .post(endpoint, formData, { responseType: "blob" })
    .then((response) => {
      const fileUrl = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement("a")
      link.href = fileUrl
      link.setAttribute("download", `glossary.${format}`)
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    })
    .catch((error) => {
      console.error("Error exporting glossary:", error)
    })
}
</script>

<style scoped>
.export-form {
  max-width: 400px;
  margin: 0 auto;
}

.form-field {
  margin-bottom: 10px;
}

label {
  font-weight: bold;
}

.btn--primary {
  background-color: #007bff;
  color: #ffffff;
  padding: 10px 20px;
  border: none;
  cursor: pointer;
}
</style>
