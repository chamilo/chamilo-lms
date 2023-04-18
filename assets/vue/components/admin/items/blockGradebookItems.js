import { useI18n } from "vue-i18n";

export const useBlockGradebookItems = () => {
  const { t } = useI18n();

  return [
    {
      className: "item-gradebook-list",
      url: "/main/admin/gradebook_list.php",
      label: t("List"),
    },
  ];
};
