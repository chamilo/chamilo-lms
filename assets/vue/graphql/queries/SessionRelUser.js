import gql from "graphql-tag"

let nodeAttrs = `node {
  session {
    _id
    title
    category {
      _id
      id
      title
    }
    displayStartDate
    displayEndDate
    users(user: $user) {
      edges {
        node {
          user {
            id
          }
          relationType
        }
      }
    }
    courses {
      edges {
        node {
          course {
            _id
            title
            illustrationUrl
          }
        }
      }
    }
    sessionRelCourseRelUsers(user: $user) {
      edges {
        node {
          course {
            _id
            title
            illustrationUrl
          }
        }
      }
    }
  }
}`

// The extension SessionRelUserExtension.php will be loaded.
export const GET_SESSION_REL_USER_CURRENT = gql`
    # Query to fetch current sessions with pagination set to 1000 items per page to avoid implementing pagination on the session page.
    query getCurrentSessions($user: String!) {
         sessionRelUsers(
            user: $user
            first: 1000 # Pagination hard-coded to 1000 items per page.
        ) {
            edges {
                ${nodeAttrs}
            }
        }
    }
`

export const GET_SESSION_REL_USER_UPCOMMING = gql`
    # Query to fetch sessions with date filters applied, pagination set to 1000 items per page.
    query getUpcommingSessions($user: String!) {
        sessionRelUsers(
            user: $user
            first: 1000  # Pagination hard-coded to 1000 items per page.
        ) {
            edges {
                ${nodeAttrs}
            }
        }
    }
`

export const GET_SESSION_REL_USER_PAST = gql`
    # Query to fetch sessions with date filters applied, pagination set to 1000 items per page.
    query getPastSessions($user: String!) {
        sessionRelUsers(
            user: $user
            first: 1000  # Pagination hard-coded to 1000 items per page.
        ) {
            edges {
                ${nodeAttrs}
            }
        }
    }
`
