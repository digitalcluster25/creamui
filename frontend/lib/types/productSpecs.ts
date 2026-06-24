export type SpecRow = {
  label: string;
  value: string;
};

export type SpecGroup = {
  title: string;
  rows: SpecRow[];
};

export type ProductSpecsData = {
  sectionTitle: string;
  groups: SpecGroup[];
};
