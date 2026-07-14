import { Header } from "@/components/sections/header";
import { Footer } from "@/components/sections/footer";
import { ContactsOverview } from "@/components/sections/contacts-overview/ContactsOverview";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { getFooterData } from "@/lib/wp/footer";
import { contactsPageData } from "@/lib/data/contactsPage";
import { getHeaderData } from "@/lib/wp/header";
import styles from "../page.module.css";

export const dynamic = "force-dynamic";

export default async function ContactsPage() {
  const footerData = await getFooterData();
  const headerData = await getHeaderData();

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <Breadcrumbs items={[{ label: "Главная", href: "/" }, { label: "Контакты" }]} />
      <div className={styles.section}>
        <ContactsOverview data={contactsPageData} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}
