export default {
  path: "/resources/glossary/:node/",
  meta: { requiresAuth: true, showBreadcrumb: true },
  name: "glossary",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "GlossaryList" },
  children: [
    {
      name: "GlossaryList",
      path: "",
      meta: { breadcrumb: "" },
      component: () => import("../views/glossary/GlossaryList.vue"),
    },
    {
      name: "CreateTerm",
      path: "create",
      meta: { breadcrumb: "Create" },
      component: () => import("../views/glossary/GlossaryTermCreate.vue"),
    },
    {
      name: "UpdateTerm",
      path: "edit/:id",
      meta: { breadcrumb: "Edit" },
      component: () => import("../views/glossary/GlossaryTermUpdate.vue"),
    },
    {
      name: "ImportGlossary",
      path: "import",
      meta: { breadcrumb: "Import" },
      component: () => import("../views/glossary/GlossaryImport.vue"),
    },
    {
      name: "ExportGlossary",
      path: "export",
      meta: { breadcrumb: "Export" },
      component: () => import("../views/glossary/GlossaryExport.vue"),
    },
    {
      name: "GenerateGlossaryTerms",
      path: "generate",
      meta: { breadcrumb: "Generate terms" },
      component: () => import("../views/glossary/GlossaryGenerateTerms.vue"),
    },
  ],
}
