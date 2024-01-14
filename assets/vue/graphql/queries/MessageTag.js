import gql from "graphql-tag";

export const GET_USER_MESSAGE_TAGS = gql`
  query getUserMessageTags($user: String!) {
    messageTags(user: $user) {
      edges {
        node {
          id
          tag
          color
        }
      }
    }
  }
`;
