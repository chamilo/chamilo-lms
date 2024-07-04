<template>
  <form>
    <BaseInputTextWithVuelidate
      id="item_title"
      v-model.trim="item.title"
      :vuelidate-property="v$.item.title"
      :label="$t('Title')"
    />

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

    <!-- For extra content-->
    <slot></slot>
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

export default {
  name: "DocumentsForm",
  components: { BaseTinyEditor, BaseInputTextWithVuelidate },
  props: {
    values: {
      type: Object,
      required: true,
    },
    errors: {
      type: Object,
      default: () => {},
    },
    initialValues: {
      type: Object,
      default: () => {},
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
    }
  },
  computed: {
    item() {
      return this.initialValues || this.values
    },
    titleErrors() {
      const errors = []

      /*if (!this.$v.item.title.$dirty) return errors;
            has(this.violations, 'title') && errors.push(this.violations.title);
            !this.$v.item.title.required && errors.push(this.$t('Field is required'));*/

      if (this.v$.item.title.required) {
        return this.$t("Field is required")
      }

      return errors
    },
    violations() {
      return this.errors || {}
    },
  },
  watch: {
    contentFile(newContent) {
      tinymce.get("item_content").setContent(newContent)
    },
  },
  methods: {
    browser(callback, value, meta) {
      //const route = useRoute();
      let nodeId = this.$route.params["node"]
      let folderParams = this.$route.query
      let url = this.$router.resolve({
        name: "DocumentForHtmlEditor",
        params: { id: nodeId },
        query: folderParams,
      })
      url = url.fullPath
      console.log(url)

      if (meta.filetype === "image") {
        url = url + "&type=images"
      } else {
        url = url + "&type=files"
      }

      console.log(url)

      window.addEventListener("message", function (event) {
        var data = event.data
        if (data.url) {
          url = data.url
          console.log(meta) // {filetype: "image", fieldname: "src"}
          callback(url)
        }
      })

      tinymce.activeEditor.windowManager.openUrl(
        {
          url: url, // use an absolute path!
          title: "file manager",
          /*width: 900,
                  height: 450,
                  resizable: 'yes'*/
        },
        {
          oninsert: function (file, fm) {
            var url, info

            // URL normalization
            url = fm.convAbsUrl(file.url)

            // Make file info
            info = file.name + " (" + fm.formatSize(file.size) + ")"

            // Provide file and text for the link dialog
            if (meta.filetype === "file") {
              callback(url, { text: info, title: info })
            }

            // Provide image and alt text for the image dialog
            if (meta.filetype === "image") {
              callback(url, { alt: info })
            }

            // Provide alternative source and posted for the media dialog
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
        //required,
      },
      parentResourceNodeId: {},
      resourceNode: {},
    },
  },
}
</script>
