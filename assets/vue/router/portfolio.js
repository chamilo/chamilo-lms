function listRoute(mode) {
  return {
    name: mode === "course" ? "PortfolioCourseList" : "PortfolioPersonalList",
    path: "",
    meta: { requiresAuth: true, breadcrumb: "Portfolio", portfolioMode: mode },
    component: () => import("../views/portfolio/PortfolioListView.vue"),
  }
}

function children(mode) {
  const prefix = mode === "course" ? "PortfolioCourse" : "PortfolioPersonal"

  return [
    listRoute(mode),
    {
      name: `${prefix}Add`,
      path: "add",
      meta: { requiresAuth: true, breadcrumb: "Add", portfolioMode: mode },
      component: () => import("../views/portfolio/PortfolioFormView.vue"),
    },
    {
      name: `${prefix}Edit`,
      path: "edit/:id(\\d+)",
      meta: { requiresAuth: true, breadcrumb: "Edit", portfolioMode: mode },
      component: () => import("../views/portfolio/PortfolioFormView.vue"),
    },
    {
      name: `${prefix}Item`,
      path: "item/:id(\\d+)",
      meta: { requiresAuth: true, breadcrumb: "Portfolio", portfolioMode: mode },
      component: () => import("../views/portfolio/PortfolioItemView.vue"),
    },
    {
      name: `${prefix}Details`,
      path: "details",
      meta: { requiresAuth: true, breadcrumb: "Details", portfolioMode: mode },
      component: () => import("../views/portfolio/PortfolioDetailsView.vue"),
    },
    {
      name: `${prefix}Categories`,
      path: "categories",
      meta: { requiresAuth: true, breadcrumb: "Categories", portfolioMode: mode, portfolioManagement: "categories" },
      component: () => import("../views/portfolio/PortfolioManagementView.vue"),
    },
    {
      name: `${prefix}Tags`,
      path: "tags",
      meta: { requiresAuth: true, breadcrumb: "Tags", portfolioMode: mode, portfolioManagement: "tags" },
      component: () => import("../views/portfolio/PortfolioManagementView.vue"),
    },
  ]
}

export default [
  {
    path: "/resources/portfolio/:node(\\d+)/",
    name: "PortfolioCourse",
    component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
    redirect: { name: "PortfolioCourseList" },
    meta: {
      requiresAuth: true,
      requiresCourseContext: true,
      showBreadcrumb: true,
      tool: "portfolio",
      breadcrumb: "Portfolio",
      portfolioMode: "course",
    },
    children: children("course"),
  },
  {
    path: "/social/portfolio",
    name: "PortfolioPersonal",
    component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
    redirect: { name: "PortfolioPersonalList" },
    meta: {
      requiresAuth: true,
      showBreadcrumb: true,
      breadcrumb: "Portfolio",
      portfolioMode: "personal",
    },
    children: children("personal"),
  },
]
