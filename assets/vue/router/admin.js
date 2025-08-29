export default {
  path: "/admin",
  name: "admin",
  meta: { requiresAuth: true, showBreadcrumb: false },
  component: () => import("../components/admin/AdminLayout.vue"),
  children: [
    {
      path: "",
      name: "AdminIndex",
      meta: { requiresAdmin: true, requiresSessionAdmin: true, showBreadcrumb: false },
      component: () => import("../views/admin/AdminIndex.vue"),
    },
    {
      name: "AdminConfigurationColors",
      path: "configuration/colors",
      meta: { requiresAdmin: true, requiresSessionAdmin: true, showBreadcrumb: true },
      component: () => import("../views/admin/AdminConfigureColors.vue"),
    },
    {
      path: "gdpr/third-parties",
      name: "ThirdPartyManager",
      meta: { requiresAdmin: true, showBreadcrumb: true },
      component: () => import("../views/admin/ThirdPartyManager.vue"),
    },
    {
      path: "gdpr/data-exchanges/:thirdPartyId?",
      name: "DataExchangeManager",
      meta: { requiresAdmin: true, showBreadcrumb: true },
      component: () => import("../views/admin/DataExchangeManager.vue"),
    },
    {
      name: "TermsConditionsList",
      path: "terms-conditions",
      meta: { requiresAdmin: true, showBreadcrumb: true, },
      component: () => import("../views/terms/TermsList.vue"),
    },
    {
      name: "TermsConditionsEdit",
      path: "terms-conditions/edit",
      meta: { requiresAdmin: true, showBreadcrumb: true, },
      component: () => import("../views/terms/TermsEdit.vue"),
    },
    {
      name: "TermsConditionsView",
      path: "terms-conditions/view",
      meta: { requiresAdmin: true, showBreadcrumb: true, },
      component: () => import("../views/terms/Terms.vue"),
    },
  ],
}
