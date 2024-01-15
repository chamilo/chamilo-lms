export default {
    path: '/catalogue/sessions',
    name: 'CatalogueSessions',
    meta: { requiresAdmin: true, requiresSessionAdmin: true },
    component: () => import('../views/course/CatalogueSessions.vue')
};
