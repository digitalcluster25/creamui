import { NextResponse, type NextRequest } from "next/server";

// Middleware runs in the Next edge runtime. Its Docker-internal GraphQL host
// is not resolvable there, so redirects use the public WordPress endpoint.
const graphQlUrl = process.env.HWS_REDIRECT_GRAPHQL_URL ?? "https://wpsandbox.spaces.community/graphql";

export async function middleware(request: NextRequest) {
  const slug = request.nextUrl.pathname.split("/").filter(Boolean).at(-1);
  if (!slug) return NextResponse.next();

  try {
    const response = await fetch(graphQlUrl, {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify({
        query: "query ProductRedirect($slug: String) { hwsProductRedirect(slug: $slug) }",
        variables: { slug },
      }),
      cache: "no-store",
    });
    const payload = await response.json() as { data?: { hwsProductRedirect?: string | null } };
    const target = payload.data?.hwsProductRedirect;
    if (target) return NextResponse.redirect(new URL(`/product/${target}/`, request.url), 301);
  } catch {
    // If WordPress is temporarily unavailable, the normal product route keeps serving.
  }

  return NextResponse.next();
}

export const config = {
  matcher: "/product/:path*",
};
