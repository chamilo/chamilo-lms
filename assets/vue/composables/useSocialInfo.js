import { ref, readonly, onMounted } from "vue";
import { useStore } from "vuex";
import { useRoute } from "vue-router";
import axios from "axios";

export function useSocialInfo() {
  const store = useStore();
  const route = useRoute();

  const user = ref({});
  const isCurrentUser = ref(true);
  const groupInfo = ref({});
  const isGroup = ref(false);

  const isLoading = ref(true);

  const loadGroup = async (groupId) => {
    isLoading.value = true;
    if (groupId) {
      try {
        const response = await axios.get(`/api/usergroup/${groupId}`);
        const groupData = response.data;
        const extractedId = groupData['@id'].split('/').pop();

        groupInfo.value = {
          ...groupData,
          id: extractedId
        };

        isGroup.value = true;
      } catch (error) {
        console.error("Error loading group:", error);
        groupInfo.value = {};
        isGroup.value = false;
      }
      isLoading.value = false;
    } else {
      isGroup.value = false;
      groupInfo.value = {};
    }
  };

  const loadUser = async () => {
    try {
      if (route.query.id) {
        user.value = await store.dispatch("user/load", '/api/users/' + route.query.id)
        isCurrentUser.value = false
      } else {
        user.value = store.getters["security/getUser"]
        isCurrentUser.value = true
      }
    } catch (e) {
      user.value = {}
      isCurrentUser.value = true
    }
  };

  onMounted(async () => {
    try {
      //if (!route.params.group_id) {
        await loadUser();
      //}
      if (route.params.group_id) {
        await loadGroup(route.params.group_id);
      }
    } finally {
      isLoading.value = false;
    }
  });

  return {
    user: readonly(user),
    isCurrentUser: readonly(isCurrentUser),
    groupInfo: readonly(groupInfo),
    isGroup: readonly(isGroup),
    loadGroup,
    loadUser,
    isLoading,
  };
}
