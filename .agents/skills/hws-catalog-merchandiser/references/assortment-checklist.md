# HWS Merchandising Checklist

Use this checklist when auditing or preparing product content.

## Product Matrix

- Manufacturer source URL is present.
- Source SKU/article is present and comes from the manufacturer page or manufacturer source dataset.
- WooCommerce SKU is not used as the article source unless it has been verified against the manufacturer article.
- Any mismatch between WooCommerce SKU and manufacturer article is marked for review.
- HWS normalized name is present.
- Brand and series are present.
- Product type decision is explicit: simple, variable, accessory, spare part, or service item.
- Target HWS category is explicit.
- Missing data is marked, not guessed.

## Pricing

- Source price and currency are captured.
- Date checked is recorded.
- Missing price is marked as `price_on_request` or equivalent.
- RUB-to-USD conversion uses the current official CBR rate when implementing canonical HWS price.

## Attributes And Filters

- Brand, series, equipment type, power, volume, voltage, material/facing, use case, and fuel/power source are checked.
- Filter values are normalized, not copied as messy text.
- Manufacturer-specific attributes are captured when relevant.

## Options

- Every variation has a real source SKU or source-backed option.
- Every variation SKU/article comes from the manufacturer source, not only from WooCommerce.
- No impossible generated combinations exist.
- Variation image and price match the selected option when available.
- Accessories are not hidden inside variation attributes unless the source sells them as a bundle option.

## Images

- Cover is exact product or exact parent model.
- Variation images match visible option changes.
- Gallery image order is useful for buyers.
- Diagrams/manual covers are not used as cover images.

## Final Verification

- WooCommerce product matches the matrix.
- Frontend/API can display category, title, price, image, options, and filters.
- Duplicates are removed/archived/redirected only according to the active workflow.
