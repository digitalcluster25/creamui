export type CategoryTag = {
  id: string;
  label: string;
  href: string;
};

export type CategoryCardData = {
  id: string;
  imageSrc: string;
  imageAlt: string;
  href: string;
  subtitle: string;
  title: string;
  tags: CategoryTag[];
};

export type CategoriesData = {
  sectionTitle: string;
  items: CategoryCardData[];
};
