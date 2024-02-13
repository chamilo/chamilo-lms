import { ref, readonly } from 'vue';
import { useStore } from 'vuex';
import { useRoute } from 'vue-router';

export function useSocialInfo() {
  const store = useStore();
  const route = useRoute();

  const user = ref({});
  const isCurrentUser = ref(true);
  const groupInfo = ref({});
  const isGroup = ref(false);

  const loadGroup = async (groupId) => {
    if (groupId) {
      try {
        groupInfo.value = await store.dispatch("usergroups/load", groupId);
        isGroup.value = true;
      } catch (error) {
        groupInfo.value = {};
        isGroup.value = false;
      }
    } else {
      isGroup.value = false;
      groupInfo.value = {};
    }
  };

  const loadUser = async () => {
    if (route.query.id) {
      await loadGroup(route.query.id);
      isCurrentUser.value = false;
    } else {
      user.value = store.getters["security/getUser"];
      isCurrentUser.value = true;
    }
  };

  return {
    user: readonly(user),
    isCurrentUser: readonly(isCurrentUser),
    groupInfo: readonly(groupInfo),
    isGroup: readonly(isGroup),
    loadGroup,
    loadUser
  };
}
