import { CategoryCard } from "@/components/blocks/category-card";
import type { CategoriesData } from "@/lib/types/categories";
import styles from "./Categories.module.css";

type Props = {
  data: CategoriesData;
};

export function Categories({ data }: Props) {
  const { sectionTitle, items } = data;

  return (
    <section>
      <div className={styles.section}>
<div className={styles.grid}>
          {items.map((item, index) => (
            <CategoryCard
              key={item.id}
              image={item.imageSrc}
              imageAlt={item.imageAlt}
              href={item.href}
              subtitle={item.subtitle}
              title={item.title}
              tags={item.tags}
              imageLoading={index < 4 ? "eager" : "lazy"}
              imageFetchPriority={index < 2 ? "high" : "auto"}
              imagePriority={index < 2}
            />
          ))}
        </div>
      </div>
    </section>
  );
}
