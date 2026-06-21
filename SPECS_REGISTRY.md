# Specs Registry

Служебный reference-файл для точных параметров утвержденных блоков.
Используется локально по протоколу, не как публичная страница.

## Header / Ohio Demo19

Source:
- https://ohio.clbthemes.com/demo19/

### Composition

1. Subheader strip
2. Header shell
3. Left cluster: hamburger + branding
4. Center cluster: contained nav pill with 5 menu items + changelog badge
5. Right cluster: wishlist action + cart total + cart button + badge

### Primitives

- layout/container-wide
- layout/cluster-left-right
- navigation/subheader-strip
- navigation/nav-pill
- navigation/nav-item
- navigation/nav-chevron
- branding/logo-lockup
- buttons/icon-circle-large
- buttons/icon-circle-small
- badges/version-pill
- badges/cart-count
- commerce/cart-total
- actions/wishlist-icon-button
- icons/hamburger-lines
- icons/wishlist-line
- icons/cart-line

### Exact Metrics

Verified via `getComputedStyle` at Ohio source (`1680x928px` viewport).

- Header side padding: `1.5vw` (`25.2px` at `1680px`)
- Header computed height: `111.359px` (`12vh` at `928px` viewport height)
- Subheader height: `38px`
- Subheader container padding: `0 1.5vw` (`25.2px` left/right)
- Subheader font-size: `13.328px`
- Subheader line-height: `21.3248px`
- Subheader background: `#E7E3D9` = `rgb(231,227,217)`
- Page background: `#F4F1E9`
- Left subheader list gap: `8px`
- Right subheader list gap: `8px`
- Left cluster gap (hamburger↔logo): visually `16px`
- Hamburger button: `56x56px`, radius `50%`, bg `rgba(136,136,137,0.05)`
- Logo (branding img): `81.9375x42px`, max-height `66px`
- Nav pill (`ul.menu`) padding: `4px 5.6px`
- Nav pill radius: `14px`
- Nav pill height: `44px`
- Nav pill background: `rgba(136,136,137,0.05)`
- Visible nav pill geometry at `1680px`: `726.672x44px`
- Nav item height: `36px`
- Nav item padding: `0 12px`
- Nav item gap (text↔chevron): `4.8px`
- Nav item font-size: `16px`, font-weight: `500`
- Chevron box: `10x10px`
- Version badge: text `V3.7`, height `20px`, horizontal padding `6.448px`, radius `7.44px`, font-size `9.92px`
- Wishlist icon: `48x48px`, bg transparent, radius `50%`
- Cart total font-size: `13.328px`
- Cart total font-weight: `500`
- Cart total line-height: `21.3248px`
- Cart circle: `56x56px`, bg `rgba(136,136,137,0.05)`, radius `50%`
- Cart badge: `18x18px`, font-size `8.624px`, font-weight `600`
- Text color main: `rgba(40,40,40,0.85)`

### Source Mapping

- `.subheader` -> strip height, color, padding
- `.header-wrap` -> header height and side padding
- `.header-wrap-inner` -> horizontal shell layout
- `.header.header-3:not(.-mobile) .nav` -> centered nav positioning
- `.header:not(.-mobile).-with-contained-menu.header-3 .menu` -> nav pill background and padding
- `.header:not(.-mobile):not(.header-5) .nav.with-multi-level-indicators .menu > .nav-item > a` -> nav horizontal padding
- `.icon-button` / `.icon-button.-small` -> circle sizes
- `.branding .logo img` -> logo height limits
- `.menu li.version > .menu-link::after` -> `V3.7` badge
