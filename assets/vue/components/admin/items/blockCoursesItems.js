import { useI18n } from "vue-i18n";
import { usePlatformConfig } from "../../../store/platformConfig";

export const useBlockCoursesItems = () => {
  const platformConfigurationStore = usePlatformConfig();

  const { t } = useI18n();

  let blockItems = [];

  blockItems.push({
    url: "/main/admin/course_list.php",
    label: t("Course list"),
    className: "item-course-list",
  });
  blockItems.push({
    url: "/main/admin/course_add.php",
    label: t("Add course"),
    className: "item-course-add",
  });

  if (
    "true" === platformConfigurationStore.getSetting("course.course_validation")
  ) {
    blockItems.push({
      url: "/main/admin/course_request_review.php",
      label: t("Review incoming course requests"),
      className: "item-course-request",
    });
    blockItems.push({
      url: "/main/admin/course_request_accepted.php",
      label: t("Accepted course requests"),
      className: "item-course-request-accepted",
    });
    blockItems.push({
      url: "/main/admin/course_request_rejected.php",
      label: t("Rejected course requests"),
      className: "item-course-request-rejected",
    });
  }

  blockItems.push({
    url: "/main/admin/course_export.php",
    label: t("Export courses"),
    className: "item-course-export",
  });
  blockItems.push({
    url: "/main/admin/course_import.php",
    label: t("Import courses list"),
    className: "item-course-import",
  });
  blockItems.push({
    url: "/main/admin/course_category.php",
    label: t("Courses categories"),
    className: "item-course-category",
  });
  blockItems.push({
    url: "/main/admin/subscribe_user2course.php",
    label: t("Add a user to a course"),
    className: "item-course-subscription",
  });
  blockItems.push({
    url: "/main/admin/course_user_import.php",
    label: t("Import users list"),
    className: "item-course-subscription-import",
  });
  blockItems.push({
    url: "/main/admin/grade_models.php",
    label: t("Grading model"),
    className: "item-grade-model",
    visible:
      "true" ===
      platformConfigurationStore.getSetting(
        "gradebook.gradebook_enable_grade_mode"
      ),
  });
  blockItems.push({
    url: "/main/admin/ldap_import_students.php",
    label: t("Import LDAP users into a course"),
    className: "item-course-subscription-ldap",
    visible: false, // isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap']) > 0,
  });
  blockItems.push({
    url: "/main/admin/extra_fields.php?type=course",
    label: t("Manage extra fields for courses"),
    className: "item-course-field",
  });
  blockItems.push({
    url: "/main/admin/questions.php",
    label: t("Questions"),
  });

  return blockItems;
};
