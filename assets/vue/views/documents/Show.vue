<template>
  <div>
    <Toolbar
      v-if="item && isCurrentTeacher"
      :handle-delete="del"
      :handle-edit="editHandler"
    ></Toolbar>

    <div
      v-if="item"
      class="flex flex-row"
    >
      <div class="w-1/2">
        <p class="text-lg">
          {{ item["title"] }}
        </p>

        <div
          v-if="item['resourceNode']['resourceFile']"
          class="flex justify-center"
        >
          <div class="w-4/5">
            <q-img
              v-if="item['resourceNode']['resourceFile']['image']"
              :src="item['contentUrl'] + '&w=300'"
              spinner-color="primary"
            />

            <span v-else-if="item['resourceNode']['resourceFile']['video']">
              <video controls>
                <source :src="item['contentUrl']" />
              </video>
            </span>

            <span v-if="'text/html' === item['resourceNode']['resourceFile']['mimeType']">
              <iframe
                :src="item['contentUrl']"
                border="0"
                height="100%"
                width="100%"
              />
            </span>

            <!--            <span v-else>-->
            <!--                <q-btn-->
            <!--                    class="btn btn--primary"-->
            <!--                    :to="item['downloadUrl']"-->
            <!--                >-->
            <!--                  <v-icon icon="mdi-file-download"/>-->
            <!--                  {{ $t('Download file') }}-->
            <!--                </q-btn>-->
            <!--              </span>-->
          </div>
        </div>
        <div
          v-else
          class="flex justify-center"
        >
          <v-icon icon="mdi-folder" />
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
                {{ item["resourceNode"].creator.username }}
              </td>
              <td></td>
              <td />
            </tr>
            <tr>
              <td>
                <strong>{{ $t("Comment") }}</strong>
              </td>
              <td>
                {{ item["comment"] }}
              </td>
            </tr>
            <tr>
              <td>
                <strong>{{ $t("Created at") }}</strong>
              </td>
              <td>
                {{ item["resourceNode"] ? relativeDatetime(item["resourceNode"].createdAt) : "" }}
              </td>
              <td />
            </tr>
            <tr>
              <td>
                <strong>{{ $t("Updated at") }}</strong>
              </td>
              <td>
                {{ item["resourceNode"] ? relativeDatetime(item["resourceNode"].updatedAt) : "" }}
              </td>
              <td />
            </tr>
            <tr v-if="item['resourceNode']['resourceFile']">
              <td>
                <strong>{{ $t("File") }}</strong>
              </td>
              <td>
                <div>
                  <a
                    :href="item['downloadUrl']"
                    class="btn btn--primary"
                  >
                    <v-icon icon="mdi-file-download" />
                    {{ $t("Download file") }}
                  </a>
                </div>
              </td>
              <td />
            </tr>
          </tbody>
        </q-markup-table>

        <hr />

        <ShowLinks :item="item" />
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

import ShowLinks from "../../components/resource_links/ShowLinks.vue"

import { useFormatDate } from "../../composables/formatDate"

const servicePrefix = "Documents"

export default {
  name: "DocumentsShow",
  components: {
    Loading,
    Toolbar,
    ShowLinks,
  },
  mixins: [ShowMixin],
  data() {
    const { relativeDatetime } = useFormatDate()

    return {
      relativeDatetime
    }
  },
  computed: {
    ...mapFields("documents", {
      isLoading: "isLoading",
    }),
    ...mapGetters("documents", ["find"]),
    ...mapGetters({
      isAuthenticated: "security/isAuthenticated",
      isAdmin: "security/isAdmin",
      isCurrentTeacher: "security/isCurrentTeacher",
    }),
  },
  methods: {
    ...mapActions("documents", {
      deleteItem: "del",
      reset: "resetShow",
      retrieve: "loadWithQuery",
    }),
  },
  servicePrefix,
}
</script>