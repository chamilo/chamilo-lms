import { defineStore } from "pinia"
import { usePlatformConfig } from "./platformConfig"

export const useCidReqStore = defineStore("cidReq", {
  state: () => ({
    course: null,
    session: null,
  }),

  getters: {
    userIsCoach: (state) => {
      const platformConfigStore = usePlatformConfig()

      return (userId, cId = 0, checkStudentView = true) => {
        if (checkStudentView && platformConfigStore.isStudentViewActive) {
          return false
        }

        if (!state.session || !userId) {
          return false
        }

        const sessionIsCoach = []

        if (cId) {
          const courseCoachSubscription = state.session?.sessionRelCourseRelUsers?.find(
            (srcru) => srcru.course.id === cId && srcru.user.id === userId && 2 === srcru.status,
          )

          if (courseCoachSubscription) {
            sessionIsCoach.push(courseCoachSubscription)
          }
        }

        const generalCoachSubscription = state.session?.users?.find(
          (sru) => sru.user.id === userId && 3 === sru.relationType,
        )

        if (generalCoachSubscription) {
          sessionIsCoach.push(generalCoachSubscription)
        }

        return sessionIsCoach.length > 0
      }
    },
  },

  actions: {
    resetCidReq() {
      this.course = null
      this.session = null
    },
  },
})
