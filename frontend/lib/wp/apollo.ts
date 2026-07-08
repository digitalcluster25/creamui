// GraphQL client — Apollo для Next.js App Router (SSR/RSC)
import { ApolloClient, InMemoryCache, HttpLink } from "@apollo/client";

const GRAPHQL_URL =
  process.env.NEXT_PUBLIC_WP_GRAPHQL_URL || "https://wpsandbox.spaces.community/graphql";

// Серверный клиент — создаём заново на каждый запрос (без singleton),
// чтобы избежать утечки кэша между пользователями в RSC.
// next: { revalidate: 3600 } — Next.js Data Cache кэширует ответы GraphQL
// на час, повторные хиты отдаются из кэша без обращения к WP.
export function getClient() {
  return new ApolloClient({
    link: new HttpLink({
      uri: GRAPHQL_URL,
      fetchOptions: { next: { revalidate: 3600 } },
    }),
    cache: new InMemoryCache(),
    defaultOptions: { query: { fetchPolicy: "network-only" } },
  });
}
