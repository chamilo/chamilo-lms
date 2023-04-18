import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { useStore } from "vuex";

export const useBlockSessionsItems = () => {
  const store = useStore();

  const { t } = useI18n();

  const isAdmin = computed(() => store.getters["security/isAdmin"]);
  const isSessionAdmin = computed(() =>
    store.getters["security/hasRole"]("ROLE_SESSION_MANAGER")
  );

  let blockItems = [
    {
      className: "item-session-list",
      url: "/main/session/session_list.php",
      label: t("Training sessions list"),
    },
    {
      className: "item-session-add",
      url: "/main/session/session_add.php",
      label: t("Add a training session"),
    },
    {
      className: "item-session-category",
      url: "/main/session/session_category_list.php",
      label: t("Sessions categories list"),
    },
    {
      className: "item-session-import",
      url: "/main/session/session_import.php",
      label: t("Import sessions list"),
    },
    {
      className: "item-session-import-hr",
      url: "/main/session/session_import_drh.php",
      label: t("Import list of HR directors into sessions"),
    },
  ];

  if (false) {
    // isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap']) > 0
    blockItems.push({
      className: "item-session-subscription-ldap-import",
      url: "/main/admin/ldap_import_students_to_session.php",
      label: t("Import LDAP users into a session"),
    });
  }

  blockItems.push({
    className: "item-session-export",
    url: "/main/session/session_export.php",
    label: t("Export sessions list"),
  });
  blockItems.push({
    className: "item-session-course-copy",
    url: "/main/coursecopy/copy_course_session.php",
    label: t("Copy from course in session to another session"),
  });

  if (isAdmin.value || isSessionAdmin.value) {
    //  && true === api_get_configuration_value('allow_session_admin_read_careers')
    // option only visible in development mode. Enable through code if required
    if (true) {
      // is_dir(api_get_path(SYS_TEST_PATH) + 'datafiller/')
      blockItems.push({
        className: "item-session-user-move-stats",
        url: "/main/admin/user_move_stats.php",
        label: t("Move users results from/to a session"),
      });
    }

    blockItems.push({
      className: "item-session-user-move",
      url: "/main/coursecopy/move_users_from_course_to_session.php",
      label: t("Move users results from base course to a session"),
    });

    blockItems.push({
      className: "item-career-dashboard",
      url: "/main/admin/career_dashboard.php",
      label: t("Careers and promotions"),
    });

    blockItems.push({
      className: "item-session-field",
      url: "/main/admin/extra_fields.php?type=session",
      label: t("Manage session fields"),
    });

    blockItems.push({
      className: "item-resource-sequence",
      url: "/main/admin/resource_sequence.php",
      label: t("Resources sequencing"),
    });
  }

  return blockItems;
};
