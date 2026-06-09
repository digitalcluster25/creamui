const NAV_ITEMS = [
  { href: '/buttons.html',      label: 'Buttons',         tag: 'atoms' },
  { href: '/typography.html',   label: 'Typography',      tag: 'atoms' },
  { href: '/icon-buttons.html', label: 'Icon Buttons',    tag: 'atoms' },
  { href: '/tags.html',         label: 'Tags & Elements', tag: 'atoms' },
  { href: '/forms.html',        label: 'Forms',           tag: 'atoms' },
  { href: '/colors.html',       label: 'Colors',          tag: 'atoms' },
  { href: '/graphics.html',     label: 'Graphics',        tag: 'atoms' },
  { href: '/layout.html',       label: 'Layout',          tag: 'atoms' },
  { href: '/header.html',       label: 'Header',          tag: 'blocks' },
  { href: '/footer.html',       label: 'Footer',          tag: 'blocks' },
];

const NAV_CSS = `
  .ohio-nav {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 1000;
    height: 48px;
    background: rgba(244,241,233,0.97);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(0,0,0,0.07);
    font-family: 'Urbanist', sans-serif;
    display: flex;
    align-items: center;
  }
  .ohio-nav-inner {
    width: 100%;
    height: 100%;
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    gap: 0;
  }
  .ohio-nav-logo {
    font-weight: 700;
    font-size: 0.82rem;
    color: #111013;
    text-decoration: none;
    letter-spacing: -0.02em;
    margin-right: 1.5rem;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .ohio-nav-logo:hover { color: #3e5759; }
  .ohio-nav-links {
    display: flex;
    align-items: center;
    gap: 0;
    overflow-x: auto;
    scrollbar-width: none;
  }
  .ohio-nav-links::-webkit-scrollbar { display: none; }
  .ohio-nav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0 0.6rem;
    height: 48px;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 600;
    color: rgba(40,40,40,0.4);
    transition: color .2s;
    white-space: nowrap;
    border-bottom: 2px solid transparent;
  }
  .ohio-nav-link:hover { color: #282828; }
  .ohio-nav-link.active { color: #282828; border-bottom-color: #3e5759; }
  .ohio-nav-tag {
    font-size: 0.5rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 1px 4px;
    border-radius: 3px;
    background: rgba(62,87,89,0.1);
    color: #3e5759;
  }
  .ohio-nav-tag.blocks { background: rgba(40,40,40,0.07); color: rgba(40,40,40,0.45); }
  .ohio-nav-link.active .ohio-nav-tag { background: rgba(62,87,89,0.15); }
  body { padding-top: 48px !important; padding-left: 0 !important; }
`;

function initNav() {
  const style = document.createElement('style');
  style.textContent = NAV_CSS;
  document.head.appendChild(style);

  const current = window.location.pathname;
  const nav = document.createElement('nav');
  nav.className = 'ohio-nav';

  const links = NAV_ITEMS.map(item => {
    const isActive = current === item.href;
    const tagClass = item.tag === 'blocks' ? ' blocks' : '';
    return '<a href="' + item.href + '" class="ohio-nav-link' + (isActive ? ' active' : '') + '">'
      + '<span class="ohio-nav-tag' + tagClass + '">' + item.tag + '</span>'
      + item.label + '</a>';
  }).join('');

  nav.innerHTML = '<div class="ohio-nav-inner"><a href="/" class="ohio-nav-logo">Ohio UI Kit</a><div class="ohio-nav-links">' + links + '</div></div>';
  document.body.insertBefore(nav, document.body.firstChild);
}

document.addEventListener('DOMContentLoaded', initNav);
