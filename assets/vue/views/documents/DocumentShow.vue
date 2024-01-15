<template>
  <div
    v-if="item"
    class="document-show"
  >
    <Toolbar
      v-if="item && isCurrentTeacher"
      :handle-delete="del"
      :handle-edit="editHandler"
    />

    <div class="section-header section-header--h6">
      <h6 v-text="item.title" />
    </div>

    <div
      class="document-show__section"
    >
      <div class="document-show__content-side">
        <div
          v-if="item['resourceNode']['resourceFile']"
        >
          <img
            v-if="item.resourceNode.resourceFile.image"
            :src="item.contentUrl + '&w=500'"
            :alt="item.title"
          />

          <video
            v-else-if="item['resourceNode']['resourceFile']['video']"
            controls
          >
            <source :src="item['contentUrl']" />
          </video>

          <iframe
            v-if="'text/html' === item['resourceNode']['resourceFile']['mimeType']"
            :src="item['contentUrl']"
          />
        </div>
        <div
          v-else
        >
          <BaseIcon icon="folder-generic" />
        </div>
      </div>

      <div class="document-show__details-side">
        <table>
          <tr>
            <th v-text="$t('Author')" />
            <td v-text="item.resourceNode.creator.username" />
          </tr>
          <tr
           v-if="item.comment"
          >
            <th v-text="$t('Comment')" />
            <td v-text="item.comment" />
          </tr>
          <tr>
            <th v-text="$t('Created at')" />
            <td>
              {{ item["resourceNode"] ? relativeDatetime(item["resourceNode"].createdAt) : "" }}
            </td>
          </tr>
          <tr>
            <th v-text="$t('Updated at')" />
            <td>
              {{ item["resourceNode"] ? relativeDatetime(item["resourceNode"].updatedAt) : "" }}
            </td>
          </tr>
          <tr v-if="item['resourceNode']['resourceFile']">
            <th v-text="$t('File')" />
            <td>
                <a
                  :href="item['downloadUrl']"
                  class="btn btn--primary"
                >
                  <BaseIcon icon="download" /> {{ $t("Download file") }}
                </a>
            </td>
          </tr>
        </table>

        <ShowLinks :item="item" />
      </div>
    </div>
  </div>
  <Loading :visible="isLoading" />
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import Loading from "../../components/Loading.vue"
import ShowMixin from "../../mixins/ShowMixin"
import Toolbar from "../../components/Toolbar.vue"

import ShowLinks from "../../components/resource_links/ShowLinks.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

import { useFormatDate } from "../../composables/formatDate"

const servicePrefix = "Documents"

export default {
  name: "DocumentsShow",
  components: {
    BaseIcon,
    Loading,
    Toolbar,
    ShowLinks,
  },
  mixins: [ShowMixin],
  data() {
    const { relativeDatetime } = useFormatDate()

    return {
      relativeDatetime,
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
