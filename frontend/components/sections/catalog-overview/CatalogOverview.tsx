import { Categories } from "@/components/sections/categories";
import type { CategoriesData } from "@/lib/types/categories";
import styles from "./CatalogOverview.module.css";

type Props = {
  title: string;
  lead?: string;
  categories?: CategoriesData | null;
  categoryCardVariant?: "image" | "menu";
};

export function CatalogOverview({ title, lead, categories, categoryCardVariant = "image" }: Props) {
  return (
    <section className={styles.section}>
      <div className={styles.intro}>
        <h1 className={styles.title}>{title}</h1>
      </div>
      {categories && categories.items.length > 0 && (
        <div className={styles.categories}>
          <Categories data={categories} variant="catalog" cardVariant={categoryCardVariant} />
        </div>
      )}
    </section>
  );
}
