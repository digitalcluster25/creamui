const WP_MEDIA_PREFIX = "/wp-content/uploads/";

export function normalizeWpMediaUrl(url?: string | null): string | undefined {
  if (!url) return undefined;

  if (url.startsWith(WP_MEDIA_PREFIX)) return url;

  try {
    const parsed = new URL(url);
    if (parsed.pathname.startsWith(WP_MEDIA_PREFIX)) {
      return `${parsed.pathname}${parsed.search}`;
    }
  } catch {
    return url;
  }

  return url;
}
