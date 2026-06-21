export type CaseProject = {
  id: number;
  image: string;
  title: string;
  href: string;
  location: string;
  meta: {
    area: string;
    type: string;
    tech: string;
  };
};

export type CasesData = {
  title: string;
  projects: CaseProject[];
};
