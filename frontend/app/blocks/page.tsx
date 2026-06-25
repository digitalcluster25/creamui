export default function BlocksPage() {
  return (
    <main className="app-shell">
      <header className="app-header">
        <div>
          <h1 className="app-title">Блоки</h1>
          <p className="app-subtitle">Переиспользуемые UI-блоки — карточки, баннеры, составные элементы.</p>
        </div>
        <nav className="app-nav" aria-label="Навигация по frontend">
          <a href="/dev">Frontend</a>
          <a href="/primitives">Примитивы</a>
          <a href="/blocks" aria-current="page">Блоки</a>
          <a href="/sections">Секции</a>
        </nav>
      </header>
    </main>
  );
}
