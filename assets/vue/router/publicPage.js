export default {
  path: "/p/:slug",
  name: "PublicPage",
  component: () => import("../views/page/PagePublic.vue"),
}
