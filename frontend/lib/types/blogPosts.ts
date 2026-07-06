export type BlogPost = {
  id: number;
  image: string;
  title: string;
  href: string;
  readTime: string;
  author: string;
  excerpt: string;
  tags: string[];
  date?: string;
};

export type BlogPostsData = {
  title: string;
  allHref: string;
  posts: BlogPost[];
};
