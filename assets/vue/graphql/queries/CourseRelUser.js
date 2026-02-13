import gql from "graphql-tag"

export const GET_COURSE_REL_USER = gql`
  query getCourses($user: String!, $first: Int!, $after: String) {
    courseRelUsers(user: $user, first: $first, after: $after) {
      edges {
        cursor
        node {
          id
          status
          progress
          trackingProgress
          score
          bestScore
          timeSpentSeconds
          certificateAvailable
          completed
          hasNewContent
          course {
            _id
            id
            title
            illustrationUrl
            duration
            courseLanguage
            categoryTitles
            users(status: 1, first: 4) {
              edges {
                node {
                  id
                  status
                  user {
                    illustrationUrl
                    username
                    fullName
                  }
                }
              }
            }
          }
        }
      }
      pageInfo {
        endCursor
        hasNextPage
      }
    }
  }
`
