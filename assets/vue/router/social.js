export default {
  path: '/social',
  meta: { requiresAuth: true },
  name: 'Social',
  component: () => import('../views/social/SocialLayout.vue'),
  children: [
    {
      name: 'SocialWall',
      path: ':filterType?',
      component: () => import('../views/social/SocialWall.vue')
    },
    {
      name: 'SocialSearch',
      path: 'search',
      component: () => import('../views/social/SocialSearch.vue')
    }
  ]
}
