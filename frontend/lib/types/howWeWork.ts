export type ProcessStep = {
  id: number;
  number: string;
  title: string;
  description: string;
};

export type HowWeWorkData = {
  title: string;
  steps: ProcessStep[];
};
