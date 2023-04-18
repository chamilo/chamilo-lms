import { useI18n } from "vue-i18n";

export const useBlockSkillsItems = () => {
  const { t } = useI18n();

  return [
    {
      className: "item-skill-wheel",
      url: "/main/skills/skills_wheel.php",
      label: t("Skills wheel"),
    },
    {
      className: "item-skill-import",
      url: "/main/skills/skills_import.php",
      label: t("Skills import"),
    },
    {
      className: "item-skill-list",
      url: "/main/skills/skill_list.php",
      label: t("Manage skills"),
    },
    {
      className: "item-skill-level",
      url: "/main/skills/skill.php",
      label: t("Manage skills levels"),
    },
    {
      className: "item-skill-ranking",
      url: "/main/skills/skills_ranking.php",
      label: t("Skills ranking"),
    },
    {
      className: "item-skill-gradebook",
      url: "/main/skills/skills_gradebook.php",
      label: t("Skills and assessments"),
    },
    // {
    //   url: "/main/skills/skill_badge.php",
    //   label: t("Badges"),
    // },
  ];
};
