<template>
  <ButtonToolbar>
    <BaseButton
      :disabled="isLoading"
      :label="t('Add friend')"
      icon="user-add"
      type="black"
      @click="goToAdd"
    />

    <BaseButton
      :disabled="isLoading"
      :label="t('Refresh')"
      icon="refresh"
      type="black"
      @click="reloadHandler"
    />

    <BaseButton
      :disabled="isLoading || !selectedItems.length"
      :label="t('Delete friends')"
      icon="delete-multiple-user"
      type="black"
      @click="confirmDeleteMultiple"
    />
  </ButtonToolbar>

  <div v-if="friendRequests.length">
    <div
      v-t="'Requests'"
      class="text-h4 mb-2"
    />

    <div
      v-for="(request, i) in friendRequests"
      :key="i"
      class="flex flex-row gap-2 items-center"
    >
      <BaseUserAvatar :image-url="request.user.illustrationUrl + '?w=80&h=80&fit=crop'" />

      {{ request.user.username }}

      <BaseButton
        icon="user-add"
        only-icon
        type="black"
        @click="addFriend(request)"
      />
    </div>
  </div>

  <div
    v-for="(request, i) in waitingRequests"
    :key="i"
  >
    <BaseUserAvatar :image-url="request.friend.illustrationUrl + '?w=80&h=80&fit=crop'" />

    {{ request.friend.username }}

    <BaseTag
      :label="t('Waiting')"
      type="info"
    />
  </div>

  <div class="flex flex-row pt-2">
    <div class="w-full">
      <div class="text-h4 q-mb-md">Friends</div>

      <!--      :loading="isLoading"-->
      <DataTable
        v-model:filters="friendFilter"
        v-model:selection="selectedItems"
        :lazy="true"
        :paginator="false"
        :rowsPerPageOptions="[5, 10, 20, 50]"
        :totalRecords="totalItems"
        :value="items"
        class="p-datatable-sm"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
        dataKey="@id"
        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        responsiveLayout="scroll"
        sortBy="sendDate"
        sortOrder="asc"
        @page="onPage($event)"
        @sort="sortingChanged($event)"
      >
        <Column
          :exportable="false"
          selectionMode="multiple"
          style="width: 3rem"
        ></Column>

        <Column
          :header="$t('User')"
          :sortable="false"
          field="sender"
        >
          <template #body="slotProps">
            <div v-if="slotProps.data.user['@id'] === user['@id']">
              <BaseUserAvatar :image-url="slotProps.data.friend.illustrationUrl + '?w=80&h=80&fit=crop'" />
              {{ slotProps.data.friend.username }}
            </div>
            <div v-else>
              <BaseUserAvatar :image-url="slotProps.data.user.illustrationUrl + '?w=80&h=80&fit=crop'" />
              {{ slotProps.data.user.username }}
            </div>
          </template>
        </Column>

        <Column
          :header="$t('Sent date')"
          :sortable="true"
          field="createdAt"
        >
          <template #body="slotProps">
            {{ $filters.relativeDatetime(slotProps.data.createdAt) }}
          </template>
        </Column>

        <Column :exportable="false">
          <template #body="slotProps">
            <!--          class="flex flex-row gap-2"-->
            <!--            <v-icon   v-if="slotProps.data.relationType == 3" icon="mdi-check" />-->
            <v-btn
              icon
              tile
              @click="confirmDeleteItem(slotProps.data)"
            >
              <v-icon icon="mdi-delete" />
            </v-btn>
          </template>
        </Column>
      </DataTable>
    </div>
  </div>

  <Dialog
    v-model:visible="itemDialog"
    :header="$t('New folder')"
    :modal="true"
    :style="{ width: '450px' }"
    class="p-fluid"
  >
    <BaseInputText
      id="title"
      v-model.trim="item.title"
      :label="t('Name')"
    />

    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        label="Cancel"
        @click="hideDialog"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
        label="Save"
        @click="saveItem"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="deleteItemDialog"
    :modal="true"
    :style="{ width: '450px' }"
    header="Confirm"
  >
    <div class="confirmation-content">
      <i
        class="pi pi-exclamation-triangle p-mr-3"
        style="font-size: 2rem"
      />
      <span v-if="item"
        >Are you sure you want to delete <b>{{ item.title }}</b
        >?</span
      >
    </div>
    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        label="No"
        @click="deleteItemDialog = false"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
        label="Yes"
        @click="deleteItemButton(item)"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="deleteMultipleDialog"
    :modal="true"
    :style="{ width: '450px' }"
    header="Confirm"
  >
    <div class="confirmation-content">
      <i
        class="pi pi-exclamation-triangle p-mr-3"
        style="font-size: 2rem"
      />
      <span v-if="item">Are you sure you want to delete the selected items?</span>
    </div>
    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        label="No"
        @click="deleteMultipleDialog = false"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
        label="Yes"
        @click="deleteMultipleItems"
      />
    </template>
  </Dialog>
</template>

<script>
import { mapActions, mapGetters, useStore } from "vuex"
import { mapFields } from "vuex-map-fields"
import ListMixin from "../../mixins/ListMixin"
import { ref } from "vue"
import axios from "axios"
import { RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility"
import ButtonToolbar from "../../components/basecomponents/ButtonToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import BaseTag from "../../components/basecomponents/BaseTag.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"

export default {
  name: "UserRelUserList",
  servicePrefix: "userreluser",
  components: {
    BaseInputText,
    BaseTag,
    BaseUserAvatar,
    BaseButton,
    ButtonToolbar,
  },
  mixins: [ListMixin],
  setup() {
    const store = useStore()
    const router = useRouter()
    const { t } = useI18n()
    const user = store.getters["security/getUser"]
    const isLoadingSelect = ref(false)
    const deleteItemDialog = ref(false)
    const item = ref({})
    const friendRequests = ref([])
    const waitingRequests = ref([])

    const friendRequestFilter = {
      friend: user.id,
      relationType: 10, // friend request
    }

    const waitingFilter = {
      user: user.id,
      relationType: 10,
    }

    const friendFilter = {
      user: user.id,
      relationType: 3, // friend status
    }

    const friendBackFilter = {
      friend: user.id,
      relationType: 3, // friend status
    }

    function addFriend(friend) {
      // Change from request to friend
      axios
        .put(friend["@id"], {
          relationType: 3,
        })
        .then((response) => {
          console.log(response)
          reloadHandler()
        })
        .catch(function (error) {
          console.log(error)
        })
    }

    function reloadHandler() {
      store.dispatch("userreluser/resetList")

      Promise.all([
        store.dispatch("userreluser/fetchAll", friendFilter),
        store.dispatch("userreluser/fetchAll", friendBackFilter),
      ])
      store.dispatch("userreluser/findAll", friendRequestFilter).then((response) => {
        friendRequests.value = response
      })
      store.dispatch("userreluser/findAll", waitingFilter).then((response) => {
        waitingRequests.value = response
      })
    }

    function deleteItemButton(item) {
      store.dispatch("userreluser/del", item)
      deleteItemDialog.value = false
      reloadHandler()
    }

    reloadHandler()

    const columns = ref([
      { label: t("User"), field: "friend.username", name: "friend", sortable: true },
      { label: t("Sent"), field: "createdAt", name: "createdAt", sortable: true },
      { label: t("Actions"), name: "action", sortable: false },
    ])
    const pageOptions = ref([10, 20, 50, t("All")])
    const selected = ref([])
    const selectedItems = ref([])
    const itemDialog = ref(false)
    const deleteMultipleDialog = ref(false)
    const submitted = ref(false)

    const goToAdd = () => {
      router.push({ name: "UserRelUserAdd" })
    }

    return {
      t,
      columns,
      pageOptions,
      selected,
      selectedItems,
      itemDialog,
      deleteMultipleDialog,
      submitted,
      goToAdd,
      addFriend,
      reloadHandler,
      deleteItemButton,
      item,
      friendRequests,
      waitingRequests,
      friendFilter,
      deleteItemDialog,
      user,
    }
  },
  computed: {
    // From crud.js list function
    ...mapGetters("resourcenode", {
      resourceNode: "getResourceNode",
    }),
    ...mapGetters({
      isAuthenticated: "security/isAuthenticated",
      isAdmin: "security/isAdmin",
      currentUser: "security/getUser",
    }),

    ...mapGetters("userreluser", {
      items: "list",
    }),

    //...getters

    // From ListMixin
    ...mapFields("userreluser", {
      deletedItem: "deleted",
      error: "error",
      isLoading: "isLoading",
      resetList: "resetList",
      totalItems: "totalItems",
      view: "view",
    }),
  },
  methods: {
    // prime
    onPage(event) {
      this.options.itemsPerPage = event.rows
      this.options.page = event.page + 1
      this.options.sortBy = event.sortField
      this.options.sortDesc = event.sortOrder === -1
      this.filters = {
        user: this.currentUser.id,
        relationType: 3,
      }
      this.onUpdateOptions(this.options)
    },
    sortingChanged(event) {
      console.log("sortingChanged")
      console.log(event)
      this.options.sortBy = event.sortField
      this.options.sortDesc = event.sortOrder === -1

      this.onUpdateOptions(this.options)
      // ctx.sortBy   ==> Field key for sorting by (or null for no sorting)
      // ctx.sortDesc ==> true if sorting descending, false otherwise
    },
    hideDialog() {
      this.itemDialog = false
      this.submitted = false
    },
    saveItem() {
      this.submitted = true

      if (this.item.title.trim()) {
        if (this.item.id) {
        } else {
          //this.products.push(this.product);
          this.item.filetype = "folder"
          this.item.parentResourceNodeId = this.$route.params.node
          this.item.resourceLinkList = JSON.stringify([
            {
              gid: this.$route.query.gid,
              sid: this.$route.query.sid,
              cid: this.$route.query.cid,
              visibility: RESOURCE_LINK_PUBLISHED, // visible by default
            },
          ])

          this.create(this.item)
          this.showMessage("Saved")
        }

        this.itemDialog = false
        this.item = {}
      }
    },
    editItem(item) {
      this.item = { ...item }
      this.itemDialog = true
    },
    confirmDeleteItem(item) {
      this.item = item
      this.deleteItemDialog = true
    },
    confirmDeleteMultiple() {
      this.deleteMultipleDialog = true
    },
    deleteMultipleItems() {
      this.deleteMultipleAction(this.selectedItems)
      this.onRequest({
        pagination: this.pagination,
      })
      this.deleteMultipleDialog = false
      this.selectedItems = null
      //this.onUpdateOptions(this.options);
    },
    onRowSelected(items) {
      this.selected = items
    },
    selectAllRows() {
      this.$refs.selectableTable.selectAllRows()
    },
    clearSelected() {
      this.$refs.selectableTable.clearSelected()
    },
    async deleteSelected() {
      this.deleteMultipleAction(this.selected)
      this.onRequest({
        pagination: this.pagination,
      })
    },
    //...actions,
    // From ListMixin
    ...mapActions("userreluser", {
      getPage: "fetchAll",
      create: "create",
      update: "update",
      deleteItem: "del",
      deleteMultipleAction: "delMultiple",
    }),
    ...mapActions("resourcenode", {
      findResourceNode: "findResourceNode",
    }),
  },
}
</script>
