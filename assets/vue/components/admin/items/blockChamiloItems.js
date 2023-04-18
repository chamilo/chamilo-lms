import { useI18n } from "vue-i18n";

export const useBlockChamiloItems = () => {
  const { t } = useI18n();

  return [
    {
      className: "item-software-homepage",
      url: "https://www.chamilo.org/",
      label: t("Chamilo homepage"),
    },
    {
      className: "item-forum",
      url: "https://www.chamilo.org/forum",
      label: t("Chamilo forum"),
    },
    {
      className: "item-installation-guide",
      url: "../../documentation/installation_guide.html",
      label: t("Installation guide"),
    },
    {
      className: "item-changelog",
      url: "../../documentation/changelog.html",
      label: t("Changes in last version"),
    },
    {
      className: "item-credits",
      url: "../../documentation/credits.html",
      label: t("Contributors list"),
    },
    {
      className: "item-security",
      url: "../../documentation/security.html",
      label: t("Security guide"),
    },
    {
      className: "item-optimization",
      url: "../../documentation/optimization.html",
      label: t("Optimization guide"),
    },
    {
      className: "item-extensions",
      url: "https://www.chamilo.org/extensions",
      label: t("Chamilo extensions"),
    },
    {
      className: "item-providers",
      url: "https://www.chamilo.org/en/providers",
      label: t("Chamilo official services providers"),
    },
  ];
};
