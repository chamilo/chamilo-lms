import { useI18n } from "vue-i18n";
import { usePlatformConfig } from "../../../store/platformConfig";

export const useBlockSettingsItems = () => {
  const { t } = useI18n();
  const platformConfigStore = usePlatformConfig();

  const blockItems = [];

  blockItems.push({
    className: "item-clean-cache",
    url: "/main/admin/archive_cleanup.php",
    label: t("Cleanup of cache and temporary files"),
  });
  blockItems.push({
    className: "item-special-export",
    url: "/main/admin/special_exports.php",
    label: t("Special exports"),
  });
  blockItems.push({
    className: "item-system-status",
    url: "/main/admin/system_status.php",
    label: t("System status"),
  });

  if (true) {
    // is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')
    blockItems.push({
      className: "item-data-filler",
      url: "/main/admin/filler.php",
      label: t("Data filler"),
    });
  }

  if (true) {
    // is_dir(api_get_path(SYS_TEST_PATH))
    blockItems.push({
      className: "item-email-tester",
      url: "/main/admin/email_tester.php",
      label: t("E-mail tester"),
    });
  }

  blockItems.push({
    className: "item-ticket-system",
    url: "/main/ticket/tickets.php",
    label: t("Tickets"),
  });

  if ("true" === platformConfigStore.getSetting("session.allow_session_status")) {
    blockItems.push({
      url: "/main/session/cron_status.php",
      label: t("Update session status"),
    });
  }

  return blockItems;
};
