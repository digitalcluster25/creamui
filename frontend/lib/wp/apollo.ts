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
// Данные каталога редактируются в WordPress. По умолчанию всегда запрашиваем
// свежую версию, чтобы фото и свойства товара появлялись на сайте сразу после
// сохранения в админке. Для редких, явно заданных случаев можно включить ISR.
export function getClient(options: ClientOptions = {}) {
  const fetchOptions = options.revalidate !== undefined && !options.noStore
    ? { next: { revalidate: options.revalidate } }
    : { cache: "no-store" as RequestCache };

  return new ApolloClient({
    link: new HttpLink({
      uri: GRAPHQL_URL,
      fetchOptions,
    }),
    cache: new InMemoryCache(),
    defaultOptions: { query: { fetchPolicy: "network-only" } },
  });
}
