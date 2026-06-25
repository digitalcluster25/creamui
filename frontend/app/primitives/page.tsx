export default function PrimitivesPage() {
  return (
    <main className="app-shell">
      <header className="app-header">
        <div>
          <h1 className="app-title">Примитивы</h1>
          <p className="app-subtitle">Атомарные UI-элементы — иконочные кнопки, логотипы, переключатели.</p>
        </div>
        <nav className="app-nav" aria-label="Навигация по frontend">
          <a href="/dev">Frontend</a>
          <a href="/primitives" aria-current="page">Примитивы</a>
          <a href="/blocks">Блоки</a>
          <a href="/sections">Секции</a>
        </nav>
      </header>
    </main>
  );
}
