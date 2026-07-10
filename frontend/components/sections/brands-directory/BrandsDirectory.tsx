import Link from "next/link";
import styles from "./BrandsDirectory.module.css";

type BrandEntry = {
  name: string;
  slug: string;
  logoUrl?: string | null;
};

type BrandGroup = {
  letter: string;
  items: BrandEntry[];
};

function pluralizeBrands(count: number): string {
  const mod10 = count % 10;
  const mod100 = count % 100;

  if (mod10 === 1 && mod100 !== 11) {
    return "бренд";
  }

  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
    return "бренда";
  }

  return "брендов";
}

function getBrandLetter(name: string): string {
  const letter = name.trim().charAt(0).toUpperCase();
  return /^[A-ZА-ЯЁ]$/u.test(letter) ? letter : "#";
}

export function BrandsDirectory({ brands }: { brands: BrandEntry[] }) {
  const groups = brands.reduce<BrandGroup[]>((acc, brand) => {
    const letter = getBrandLetter(brand.name);
    const existing = acc.find((group) => group.letter === letter);

    if (existing) {
      existing.items.push(brand);
      return acc;
    }

    acc.push({ letter, items: [brand] });
    return acc;
  }, []);

  const sortedGroups = groups
    .map((group) => ({
      ...group,
      items: [...group.items].sort((a, b) => a.name.localeCompare(b.name, "ru")),
    }))
    .sort((a, b) => a.letter.localeCompare(b.letter, "ru"));

  return (
    <section className={styles.section}>
      <div className={styles.hero}>
        <div>
          <h1 className={styles.title}>Бренды</h1>
        </div>
        <p className={styles.count}>{brands.length} {pluralizeBrands(brands.length)}</p>
      </div>

      <nav className={styles.letters} aria-label="Навигация по буквам">
        {sortedGroups.map((group) => (
          <a key={group.letter} href={`#letter-${group.letter}`} className={styles.letterLink}>
            {group.letter}
          </a>
        ))}
      </nav>

      <div className={styles.groups}>
        {sortedGroups.map((group) => (
          <section key={group.letter} id={`letter-${group.letter}`} className={styles.group}>
            <div className={styles.groupHead}>
              <h2 className={styles.groupTitle}>{group.letter}</h2>
            </div>
            <div className={styles.grid}>
              {group.items.map((brand) => (
                <Link key={brand.slug} href={`/brands/${brand.slug}`} className={styles.card}>
                  <div className={styles.cardBody}>
                    <span className={styles.cardName}>{brand.name}</span>
                    <span className={styles.cardLink}>Смотреть товары</span>
                  </div>
                </Link>
              ))}
            </div>
          </section>
        ))}
      </div>
    </section>
  );
}
