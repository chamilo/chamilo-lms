import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { usePlatformConfig } from "../../../store/platformConfig";
import { useStore } from "vuex";

export const useBlockUsersItems = () => {
  const store = useStore();
  const platformConfigurationStore = usePlatformConfig();

  const { t } = useI18n();

  let blockItems = [];

  const isAdmin = computed(() => store.getters["security/isAdmin"]);
  const isSessionAdmin = computed(() =>
    store.getters["security/hasRole"]("ROLE_SESSION_MANAGER")
  );

  if (isAdmin.value) {
    blockItems = [
      {
        className: "item-user-list",
        url: "/main/admin/user_list.php",
        label: t("User list"),
      },
      {
        className: "item-user-add",
        url: "/main/admin/user_add.php",
        label: t("Add a user"),
      },
      {
        className: "item-user-export",
        url: "/main/admin/user_export.php",
        label: t("Export users list"),
      },
      {
        className: "item-user-import",
        url: "/main/admin/user_import.php",
        label: t("Import users listImport users list"),
      },
      {
        className: "item-user-import-update",
        url: "/main/admin/user_update_import.php",
        label: t("Edit users list"),
      },
      {
        className: "item-user-import-anonymize",
        url: "/main/admin/user_anonymize_import.php",
        label: t("Anonymise users list"),
      },
      {
        className: "item-user-ldap-list",
        url: "/main/admin/ldap_users_list",
        label: t("Import LDAP users into the platform"),
        visible: false, // isAdmin.value && isset($extAuthSource) && isset($extAuthSource['extldap']) && count($extAuthSource['extldap']) > 0
      },
      {
        className: "item-user-field",
        url: "/main/admin/extra_fields.php?type=user",
        label: t("Profiling"),
      },
      {
        className: "item-user-groups",
        url: "/main/admin/usergroups.php",
        label: t("Classes"),
      },
      {
        className: "item-user-linking-requests",
        url: "/main/admin/user_linking_requests",
        label: t("Student linking requests"),
        visible: false, // isAdmin.value && api_get_configuration_value('show_link_request_hrm_user')
      },
    ];
  } else {
    blockItems = [
      {
        class: "item-user-list",
        url: "/main/admin/user_list.php",
        label: t("User list"),
      },
      {
        class: "item-user-add",
        url: "/main/admin/user_add.php",
        label: t("Add a user"),
      },
      {
        class: "item-user-import",
        url: "/main/admin/user_import.php",
        label: t("Import users list"),
      },
      {
        class: "item-user-groups",
        url: "/main/admin/usergroups.php",
        label: t("Classes"),
      },
    ];

    if (isSessionAdmin.value) {
      if (
        "true" ===
        platformConfigurationStore.getSetting(
          "session.limit_session_admin_role"
        )
      ) {
        blockItems = blockItems.filter((item) => {
          const urls = [
            "/main/admin/user_list.php",
            "/main/admin/user_add.php",
          ];

          return urls.indexOf(item.url) >= 0;
        });
      }

      /*if (true === api_get_configuration_value('limit_session_admin_list_users')) {
                    blockUsersItems = blockUsersItems.filter((item) => {
                      const urls = [
                        '/main/admin/user_list.php',
                      ];
      
                      return urls.indexOf(item.url) >= 0;
                    });
                  }*/

      if (true) {
        // api_get_configuration_value('allow_session_admin_extra_access')
        blockItems.push({
          className: "item-user-import-update",
          url: "/main/admin/user_update_import.php",
          label: t("Edit users list"),
        });
        blockItems.push({
          className: "item-user-export",
          url: "/main/admin/user_export.php",
          label: t("Export users list"),
        });
      }
    }
  }

  return blockItems;
};
