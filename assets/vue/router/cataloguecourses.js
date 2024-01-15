export default {
    path: '/catalogue/courses',
    name: 'CatalogueCourses',
    meta: { requiresAdmin: true, requiresSessionAdmin: true },
    component: () => import('../views/course/CatalogueCourses.vue')
};
