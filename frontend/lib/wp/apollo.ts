// GraphQL client — Apollo для Next.js App Router (SSR/RSC)
import { ApolloClient, InMemoryCache, HttpLink } from "@apollo/client";

const GRAPHQL_URL =
  process.env.NEXT_PUBLIC_WP_GRAPHQL_URL || "https://wpsandbox.spaces.community/graphql";

type ClientOptions = {
  noStore?: boolean;
  revalidate?: number;
};

// Серверный клиент — создаём заново на каждый запрос (без singleton),
// чтобы избежать утечки кэша между пользователями в RSC.
// next: { revalidate: 3600 } — Next.js Data Cache кэширует ответы GraphQL
// на час, повторные хиты отдаются из кэша без обращения к WP.
export function getClient(options: ClientOptions = {}) {
  const fetchOptions = options.noStore
    ? { cache: "no-store" as RequestCache }
    : { next: { revalidate: options.revalidate ?? 3600 } };

  return new ApolloClient({
    link: new HttpLink({
      uri: GRAPHQL_URL,
      fetchOptions,
    }),
    cache: new InMemoryCache(),
    defaultOptions: { query: { fetchPolicy: "network-only" } },
  });
}
