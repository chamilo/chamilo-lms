import { ApolloClient, createHttpLink, InMemoryCache } from "@apollo/client/core"

const httpLink = createHttpLink({
  uri: "/api/graphql",
})

const cache = new InMemoryCache()

const apolloClient = new ApolloClient({
  link: httpLink,
  cache,
})

export default apolloClient
