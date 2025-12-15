<template>
  <form>
    <!-- Title -->
    <BaseInputTextWithVuelidate
      id="item_title"
      v-model.trim="item.title"
      :label="$t('Title')"
      :vuelidate-property="v$.item.title"
    />

    <!-- Content editor -->
    <BaseTinyEditor
      v-if="
        (item.resourceNode && item.resourceNode.firstResourceFile && item.resourceNode.firstResourceFile.text) ||
        item.newDocument
      "
      v-model="item.contentFile"
      :title="t('Content')"
      editor-id="item_content"
      required
    />

    <!-- Advanced options: search / indexing -->
    <BaseAdvancedSettingsButton
      v-if="searchEnabled"
      v-model="showAdvancedSettings"
    >
      <div class="flex flex-row mb-2">
        <label class="font-semibold w-40">
          {{ $t('Options') }}:
        </label>
        <BaseCheckbox
          id="indexDocumentContent"
          v-model="item.indexDocumentContent"
          :label="$t('Index document content?')"
          name="indexDocumentContent"
        />
      </div>
    </BaseAdvancedSettingsButton>

    <!-- For extra content -->
    <slot></slot>

    <!-- Submit -->
    <BaseButton
      type="primary"
      icon="save"
      :label="$t('Save')"
      @click="$emit('submit')"
    />
  </form>
</template>

<script>
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import { ref } from "vue"
import { usePlatformConfig } from "../../store/platformConfig"
import BaseInputTextWithVuelidate from "../basecomponents/BaseInputTextWithVuelidate.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"

export default {
  name: "DocumentsForm",
  components: {
    BaseButton,
    BaseTinyEditor,
    BaseInputTextWithVuelidate,
    BaseAdvancedSettingsButton,
    BaseCheckbox,
  },
  props: {
    values: {
      type: Object,
      required: true,
    },
    errors: {
      type: Object,
      default: () => ({}),
    },
    initialValues: {
      type: Object,
      default: () => ({}),
    },
    // Indicates if full-text search is enabled at platform level
    searchEnabled: {
      type: Boolean,
      default: false,
    },
  },
  setup() {
    const platformConfigStore = usePlatformConfig()
    const extraPlugins = ref("")
    const { t } = useI18n()

    if ("true" === platformConfigStore.getSetting("editor.translate_html")) {
      extraPlugins.value = "translatehtml"
    }

    return { v$: useVuelidate(), extraPlugins, t }
  },
  data() {
    return {
      title: null,
      contentFile: this.initialValues ? this.initialValues.contentFile : "",
      parentResourceNodeId: null,
      resourceNode: null,
      showAdvancedSettings: false,
    }
  },
  computed: {
    item() {
      // Prefer initialValues when present (edit mode), otherwise use values (create mode)
      return this.initialValues && Object.keys(this.initialValues).length > 0
        ? this.initialValues
        : this.values
    },
    titleErrors() {
      const errors = []

      if (this.v$.item.title.required) {
        return this.$t("Required field")
      }

      return errors
    },
    violations() {
      return this.errors || {}
    },
  },
  watch: {
    contentFile(newContent) {
      if (window.tinymce && tinymce.get("item_content")) {
        tinymce.get("item_content").setContent(newContent)
      }
    },
  },
  methods: {
    browser(callback, value, meta) {
      let nodeId = this.$route.params["node"]
      let folderParams = this.$route.query
      let url = this.$router.resolve({
        name: "DocumentForHtmlEditor",
        params: { id: nodeId },
        query: folderParams,
      })
      url = url.fullPath

      if (meta.filetype === "image") {
        url = url + "&type=images"
      } else {
        url = url + "&type=files"
      }

      window.addEventListener("message", function (event) {
        const data = event.data
        if (data.url) {
          const finalUrl = data.url
          callback(finalUrl)
        }
      })

      tinymce.activeEditor.windowManager.openUrl(
        {
          url: url,
          title: "file manager",
        },
        {
          oninsert: function (file, fm) {
            let url = fm.convAbsUrl(file.url)
            const info = file.name + " (" + fm.formatSize(file.size) + ")"

            if (meta.filetype === "file") {
              callback(url, { text: info, title: info })
            }

            if (meta.filetype === "image") {
              callback(url, { alt: info })
            }

            if (meta.filetype === "media") {
              callback(url)
            }
          },
        },
      )
      return false
    },
    updateContent(content) {
      this.contentFile = content
    },
  },
  validations: {
    item: {
      title: {
        required,
      },
      contentFile: {
        // required,
      },
      parentResourceNodeId: {},
      resourceNode: {},
    },
  },
  emits: ["submit"],
}
</script>
