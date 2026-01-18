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
        <label class="font-semibold w-40"> {{ $t("Options") }}: </label>
        <BaseCheckbox
          id="indexDocumentContent"
          v-model="item.indexDocumentContent"
          :label="$t('Index document content?')"
          name="indexDocumentContent"
        />
      </div>

      <!-- Specific search fields -->
      <div
        v-if="searchEnabled && searchFields.length > 0"
        class="flex flex-col gap-2 mt-3"
      >
        <div
          v-for="field in searchFields"
          :key="field.id"
          class="flex flex-row items-center gap-3"
        >
          <label
            class="font-semibold w-40"
            :for="`doc_search_field_${field.code}`"
          >
            {{ field.title }}:
          </label>

          <input
            :id="`doc_search_field_${field.code}`"
            :name="`searchFieldValues[${field.code}]`"
            v-model="item.searchFieldValues[field.code]"
            type="text"
            class="w-full border border-gray-300 rounded px-3 py-2"
            :placeholder="field.title"
            autocomplete="off"
          />
        </div>
      </div>
    </BaseAdvancedSettingsButton>

    <slot></slot>

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
import { ENTRYPOINT } from "../../config/entrypoint"

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
    values: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    initialValues: { type: Object, default: () => ({}) },
    searchEnabled: { type: Boolean, default: false },
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
      contentFile: this.initialValues ? this.initialValues.contentFile : "",
      showAdvancedSettings: false,

      // [{ id, code, title }]
      searchFields: [],
      // Guard to avoid reloading values multiple times
      searchValuesLoaded: false,
    }
  },
  computed: {
    item() {
      return this.initialValues && Object.keys(this.initialValues).length > 0 ? this.initialValues : this.values
    },
    violations() {
      return this.errors || {}
    },
  },
  async created() {
    // Ensure containers exist
    if (!this.item.searchFieldValues || typeof this.item.searchFieldValues !== "object") {
      this.item.searchFieldValues = {}
    }

    // Default checkbox value (safe)
    if (undefined === this.item.indexDocumentContent) {
      this.item.indexDocumentContent = true
    }

    if (!this.searchEnabled) {
      return
    }

    await this.loadSearchEngineFields()
    await this.loadSearchEngineFieldValuesForEdit()
  },
  methods: {
    normalizeCode(code) {
      return String(code || "")
        .trim()
        .toLowerCase()
    },
    extractIdFromIri(iri) {
      if (!iri || "string" !== typeof iri) return null
      const parts = iri.split("/")
      const last = parts[parts.length - 1]
      const n = Number(last)
      return Number.isFinite(n) ? n : null
    },
    getResourceNodeId() {
      // Try the most common shapes
      const rn = this.item?.resourceNode

      if (!rn) return null
      if ("number" === typeof rn) return rn
      if ("string" === typeof rn) return this.extractIdFromIri(rn)

      // object
      if (rn.id) return Number(rn.id)
      if (rn["@id"]) return this.extractIdFromIri(rn["@id"])

      return null
    },
    async loadSearchEngineFields() {
      try {
        const response = await fetch(ENTRYPOINT + "search_engine_fields", {
          credentials: "same-origin",
        })

        if (!response.ok) {
          console.error("[Search] Failed to load search engine fields:", response.status)
          return
        }

        const json = await response.json()
        const rawFields = Array.isArray(json) ? json : json["hydra:member"] || []

        if (!Array.isArray(rawFields)) {
          console.error("[Search] Unexpected search engine fields payload:", json)
          return
        }

        this.searchFields = rawFields
          .map((f) => ({
            id: f.id,
            code: this.normalizeCode(f.code),
            title: f.title,
          }))
          .filter((f) => f.code)

        // Init missing keys + migrate numeric keys (old style) once
        for (const field of this.searchFields) {
          const code = field.code
          if (!code) continue

          // Migration: numeric key -> code key (only if present)
          if (this.item.searchFieldValues[field.id] && !this.item.searchFieldValues[code]) {
            this.item.searchFieldValues[code] = this.item.searchFieldValues[field.id]
            delete this.item.searchFieldValues[field.id]
          }

          if (undefined === this.item.searchFieldValues[code]) {
            this.item.searchFieldValues[code] = ""
          }
        }
      } catch (e) {
        console.error("[Search] Failed to fetch search engine fields:", e)
      }
    },

    async fetchFieldValues(resourceNodeId) {
      // Preferred: filter by resourceNode IRI (requires ApiPlatform SearchFilter)
      const iri = `/api/resource_nodes/${resourceNodeId}`

      const tryUrls = [
        `${ENTRYPOINT}search_engine_field_values?resourceNode=${encodeURIComponent(iri)}&pagination=false`,
        `${ENTRYPOINT}search_engine_field_values?resourceNodeId=${encodeURIComponent(resourceNodeId)}&pagination=false`,
      ]

      for (const url of tryUrls) {
        try {
          const response = await fetch(url, { credentials: "same-origin" })
          if (!response.ok) {
            console.warn("[Search] Field values request failed:", response.status, url)
            continue
          }

          const json = await response.json()
          const items = Array.isArray(json) ? json : json["hydra:member"] || []
          if (!Array.isArray(items)) {
            console.warn("[Search] Unexpected field values payload:", json)
            continue
          }

          return items
        } catch (e) {
          console.warn("[Search] Field values request error:", e)
        }
      }

      return []
    },

    async loadSearchEngineFieldValuesForEdit() {
      if (this.searchValuesLoaded) return

      const resourceNodeId = this.getResourceNodeId()
      if (!resourceNodeId) {
        // Create mode has no resourceNode yet
        return
      }

      const items = await this.fetchFieldValues(resourceNodeId)
      if (!items.length) {
        // Not fatal: it just means "no values found" or API filter not enabled
        return
      }

      // Build map fieldId -> code using loaded searchFields
      const fieldIdToCode = new Map(this.searchFields.map((f) => [Number(f.id), f.code]))

      for (const v of items) {
        // v.field can be an IRI or an object; handle both
        let fieldId = null

        if (v.field) {
          if ("string" === typeof v.field) {
            fieldId = this.extractIdFromIri(v.field)
          } else if (v.field["@id"]) {
            fieldId = this.extractIdFromIri(v.field["@id"])
          } else if (v.field.id) {
            fieldId = Number(v.field.id)
          }
        }

        // Some APIs might expose field_id directly
        if (!fieldId && v.field_id) {
          fieldId = Number(v.field_id)
        }

        if (!fieldId) continue

        const code = fieldIdToCode.get(Number(fieldId))
        if (!code) continue

        // Fill form with stored values
        this.item.searchFieldValues[code] = String(v.value ?? "")
      }

      this.searchValuesLoaded = true
      console.log("[Search] Loaded search field values for resourceNodeId=", resourceNodeId)
    },

    // Existing methods kept intact
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
          callback(data.url)
        }
      })

      tinymce.activeEditor.windowManager.openUrl(
        { url, title: "file manager" },
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
      title: { required },
      contentFile: {},
      parentResourceNodeId: {},
      resourceNode: {},
    },
  },
  emits: ["submit"],
}
</script>
