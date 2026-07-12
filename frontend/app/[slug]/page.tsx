import { notFound } from "next/navigation";
import { Header } from "@/components/sections/header";
import { Footer } from "@/components/sections/footer";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { getHeaderData } from "@/lib/wp/header";
import { getFooterData } from "@/lib/wp/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_WP_PAGE } from "@/lib/wp/queries";
import styles from "./page.module.css";

export const revalidate = 3600;

type Params = { slug: string };

export default async function WPPage({ params }: { params: Promise<Params> }) {
  const { slug } = await params;

  const client = getClient();
  const { data } = await client.query<{
    page: { title: string; content: string; slug: string } | null;
  }>({
    query: GET_WP_PAGE,
    variables: { slug },
    errorPolicy: "all",
  });

  if (!data?.page) notFound();

  const [headerData, footerData] = await Promise.all([
    getHeaderData(),
    getFooterData(),
  ]);

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <Breadcrumbs
        items={[{ label: "Главная", href: "/" }, { label: data.page.title }]}
      />
      <div className={styles.section}>
        <h1 className={styles.title}>{data.page.title}</h1>
        <div
          className={styles.content}
          dangerouslySetInnerHTML={{ __html: data.page.content }}
        />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}
