import { useI18n } from "vue-i18n";
import { usePlatformConfig } from "../../../store/platformConfig";

export const useBlockPlatformItems = () => {
  const platformConfigurationStore = usePlatformConfig();
  const { t } = useI18n();

  const blockItems = [];

  blockItems.push({
    className: "item-setting-list",
    url: "/admin/settings/platform",
    label: t("Configuration settings"),
  });
  blockItems.push({
    className: "item-language-list",
    url: "/main/admin/languages.php",
    label: t("Languages"),
  });
  blockItems.push({
    className: "item-plugin-list",
    url: "/main/admin/settings.php?category=Plugins",
    label: t("Plugins"),
  });
  blockItems.push({
    className: "item-region-list",
    url: "/main/admin/settings.php?category=Regions",
    label: t("Regions"),
  });
  blockItems.push({
    className: "item-global-announcement",
    url: "/main/admin/system_announcements.php",
    label: t("Portal news"),
  });
  blockItems.push({
    className: "item-global-agenda",
    url: "/main/calendar/agenda_js.php?type=admin",
    label: t("Global agenda"),
  });

  if ("true" === platformConfigurationStore.getSetting("agenda.agenda_reminders")) {
    blockItems.push({
      className: "item-agenda-reminders",
      url: "/main/admin/import_course_agenda_reminders.php",
      label: t("Import course events"),
    });
  }

  // blockItems.push({ // Replaced by page blocks,
  //     className: 'item-homepage',
  //     url: '/main/admin/configure_homepage.php',
  //     label: t('Edit portal homepage'),
  // });

  blockItems.push({
    className: "item-pages-list",
    url: "/resources/pages",
    label: t("Pages"),
  });
  blockItems.push({
    className: "item-registration-page",
    url: "/main/admin/configure_inscription.php",
    label: t("Setting the registration page"),
  });
  blockItems.push({
    className: "item-stats",
    url: "/main/admin/statistics/index.php",
    label: t("Statistics"),
  });
  blockItems.push({
    className: "item-stats-report",
    url: "/main/my_space/company_reports.php",
    label: t("Reports"),
  });
  blockItems.push({
    className: "item-teacher-time-report",
    url: "/main/admin/teacher_time_report.php",
    label: t("Teachers time report"),
  });

  if (false) {
    // true === api_get_configuration_value('chamilo_cms')
    blockItems.push({
      className: "item-cms",
      url: "/web/app_dev.php/administration/dashboard",
      label: t("CMS"),
    });
  }

  blockItems.push({
    className: "item-field",
    url: "/main/admin/extra_field_list.php",
    label: t("Extra fields"),
  });

  if (false) {
    // true === !empty($_configuration['multiple_access_urls'] && api_is_global_platform_admin()
    blockItems.push({
      className: "item-access-url",
      url: "/main/admin/access_urls.php",
      label: t("Configure multiple access URL"),
    });
  }

  if (false) {
    // 'true' === api_get_plugin_setting('dictionary', 'enable_plugin_dictionary'),
    blockItems.push({
      className: "item-dictionary",
      url: "/main/plugin/dictionary/terms.php",
      label: t("Dictionary"),
    });
  }

  if (
    "true" ===
    platformConfigurationStore.getSetting("registration.allow_terms_condition")
  ) {
    blockItems.push({
      className: "item-terms-and-conditions",
      url: "/main/admin/legal_add.php",
      label: t("Terms and Conditions"),
    });
  }

  if (false) {
    // true === api_get_configuration_value('mail_template_system')
    blockItems.push({
      className: "item-mail-template",
      url: "/main/mail_template/list.php",
      label: t("Mail templates"),
    });
  }

  if (false) {
    // true === api_get_configuration_value('notification_event')
    blockItems.push({
      className: "item-notification-list",
      url: "/main/notification_event/list.php",
      label: t("Notifications"),
    });
  }

  if (false) {
    // 'true' === api_get_plugin_setting('justification', 'tool_enable')
    blockItems.push({
      className: "item-justification-list",
      url: "/plugin/justification/list.php",
      label: t("Justification"),
    });
  }

  blockItems.push({
    className: "",
    url: "/main/admin/lti",
    label: t("External tools"),
  });

  return blockItems;
};
