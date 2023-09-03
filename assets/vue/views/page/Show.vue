<template>
  <div>
    <Toolbar
      v-if="item && isAdmin"
      :handle-delete="del"
      :handle-edit="editHandler"
    >
    </Toolbar>

    <div
      v-if="item"
      class="flex flex-row"
    >
      <div class="w-1/2">
        <p class="text-lg">
          {{ item["title"] }}
        </p>
        <div class="flex justify-center">
          <div class="w-4/5">
            <div v-html="item['content']" />
          </div>
        </div>
      </div>

      <span class="w-1/2">
        <q-markup-table>
          <tbody>
            <tr>
              <td>
                <strong>{{ $t("Author") }}</strong>
              </td>
              <td>
                {{ item["creator"]["username"] }}
              </td>
              <td></td>
              <td />
            </tr>
            <tr>
              <td>
                <strong>{{ $t("Locale") }}</strong>
              </td>
              <td>
                {{ item["locale"] }}
              </td>
            </tr>
            <tr>
              <td>
                <strong>{{ $t("Enabled") }}</strong>
              </td>
              <td>
                {{ item["enabled"] }}
              </td>
            </tr>
            <tr>
              <td>
                <strong>{{ $t("Category") }}</strong>
              </td>
              <td>
                {{ item["category"]["title"] }}
              </td>
            </tr>
            <tr>
              <td>
                <strong>{{ $t("Created at") }}</strong>
              </td>
              <td>
                {{ item["createdAt"] ? relativeDatetime(item["createdAt"]) : "" }}
              </td>
              <td />
            </tr>
            <tr>
              <td>
                <strong>{{ $t("Updated at") }}</strong>
              </td>
              <td>
                {{ item["updatedAt"] ? relativeDatetime(item["updatedAt"]) : "" }}
              </td>
              <td />
            </tr>
          </tbody>
        </q-markup-table>
      </span>
    </div>

    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import Loading from "../../components/Loading.vue"
import ShowMixin from "../../mixins/ShowMixin"
import Toolbar from "../../components/Toolbar.vue"
import { useFormatDate } from "../../composables/formatDate"

const servicePrefix = "Page"

const { relativeDatetime } = useFormatDate()

export default {
  name: "PageShow",
  components: {
    Loading,
    Toolbar,
  },
  data() {
    return {
      relativeDatetime,
    }
  },
  mixins: [ShowMixin],
  computed: {
    ...mapFields("page", {
      isLoading: "isLoading",
    }),
    ...mapGetters("page", ["find"]),
    ...mapGetters({
      isAuthenticated: "security/isAuthenticated",
      isAdmin: "security/isAdmin",
      isCurrentTeacher: "security/isCurrentTeacher",
    }),
  },
  methods: {
    ...mapActions("page", {
      deleteItem: "del",
      reset: "resetShow",
      retrieve: "loadWithQuery",
    }),
  },
  servicePrefix,
}
</script>