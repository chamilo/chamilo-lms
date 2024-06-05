export default {
  path: '/resources/terms-conditions',
  meta: { requiresAuth: true },
  name: 'TermsConditions',
  component: () => import('../views/terms/TermsLayout.vue'),
  children: [
    {
      name: 'TermsConditionsList',
      path: '',
      component: () => import('../views/terms/TermsList.vue')
    },
    {
      name: 'TermsConditionsEdit',
      path: 'edit',
      component: () => import('../views/terms/TermsEdit.vue')
    },
    {
      name: 'TermsConditionsView',
      path: 'view',
      component: () => import('../views/terms/Terms.vue')
    }
  ]
}
