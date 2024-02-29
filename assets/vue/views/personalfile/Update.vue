<template>
  <Button
    :label="$t('Back')"
    class="mb-4"
    icon="pi pi-chevron-left"
    @click="goBack"
  />
  <div v-if="!isLoading && item">
    <!--      :handle-delete="del"-->
    <Toolbar
      :handle-reset="resetForm"
      :handle-submit="onSendFormData"
    />
    <DocumentsForm
      ref="updateForm"
      :errors="violations"
      :values="item"
    >
      <EditLinks
        v-model="item"
        links-type="users"
      />
    </DocumentsForm>
    <Loading :visible="isLoading || deleteLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import DocumentsForm from "../../components/personalfile/Form.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import UpdateMixin from "../../mixins/UpdateMixin"
import EditLinks from "../../components/resource_links/EditLinks.vue"

const servicePrefix = "PersonalFile"

export default {
  name: "PersonalFileUpdate",
  servicePrefix,
  components: {
    EditLinks,
    Loading,
    Toolbar,
    DocumentsForm,
  },
  mixins: [UpdateMixin],
  computed: {
    ...mapFields("personalfile", {
      deleteLoading: "isLoading",
      isLoading: "isLoading",
      error: "error",
      updated: "updated",
      violations: "violations",
    }),
    ...mapGetters("personalfile", ["find"]),
  },
  methods: {
    goBack() {
      this.$router.go(-1)
    },
    ...mapActions("personalfile", {
      createReset: "resetCreate",
      deleteItem: "del",
      delReset: "resetDelete",
      retrieve: "load",
      update: "update",
      updateWithFormData: "updateWithFormData",
      updateReset: "resetUpdate",
    }),
  },
}
</script>
