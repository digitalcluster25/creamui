const BLOCK_BREAK_RE = /<(br\s*\/?|\/p|\/div|\/li|\/ul|\/ol|\/h[1-6])>/gi;

function decodeEntities(text: string): string {
  return text
    .replace(/&#8212;|&mdash;/g, "—")
    .replace(/&#8211;|&ndash;/g, "–")
    .replace(/&#8217;/g, "’")
    .replace(/&#171;/g, "«")
    .replace(/&#187;/g, "»")
    .replace(/&#038;|&amp;/g, "&")
    .replace(/&nbsp;/g, " ")
    .replace(/&quot;/g, "\"")
    .replace(/&#39;/g, "'")
    .replace(/&hellip;/g, "…");
}

export function htmlToPlainText(html?: string): string {
  if (!html) return "";

  return decodeEntities(html)
    .replace(BLOCK_BREAK_RE, "\n")
    .replace(/<[^>]*>/g, "")
    .replace(/[ \t]+\n/g, "\n")
    .replace(/\n[ \t]+/g, "\n")
    .replace(/\n{3,}/g, "\n\n")
    .trim();
}
