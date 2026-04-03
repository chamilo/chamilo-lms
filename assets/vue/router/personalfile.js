export default {
  path: "/resources/personal_files",
  meta: { requiresAuth: true },
  name: "personal_files",
  component: () => import("../views/personalfile/Home.vue"),
  children: [
    {
      path: ":node/",
      component: () => import("../components/personalfile/Layout.vue"),
      redirect: { name: "PersonalFileList" },
      children: [
        {
          name: "PersonalFileList",
          path: "",
          meta: { breadcrumb: "My files" },
          component: () => import("../views/personalfile/List.vue"),
        },
        {
          name: "PersonalFileUploadFile",
          path: "upload",
          meta: { breadcrumb: "Upload file" },
          component: () => import("../views/personalfile/Upload.vue"),
        },
        {
          name: "PersonalFileShared",
          path: "shared",
          meta: { breadcrumb: "Shared files" },
          component: () => import("../views/personalfile/Shared.vue"),
        },
        {
          name: "PersonalFileUpdate",
          path: "edit_file",
          meta: { breadcrumb: "Edit" },
          component: () => import("../views/personalfile/Update.vue"),
        },
        {
          name: "PersonalFileShow",
          path: "show",
          meta: { breadcrumb: "Show" },
          component: () => import("../views/personalfile/Show.vue"),
        },
      ],
    },
  ],
}
