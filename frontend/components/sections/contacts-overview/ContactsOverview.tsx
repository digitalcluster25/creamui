import styles from "./ContactsOverview.module.css";
import type { ContactOffice } from "@/lib/data/contactsPage";

type ContactsOverviewData = {
  eyebrow: string;
  title: string;
  lead: string;
  officesTitle: string;
  officesLead: string;
  offices: ContactOffice[];
};

export function ContactsOverview({ data }: { data: ContactsOverviewData }) {
  return (
    <section className={styles.section}>
      <div className={styles.hero}>
        <h1 className={styles.title}>{data.title}</h1>
        <p className={styles.lead}>{data.lead}</p>
      </div>

      <div className={styles.offices}>
        <div className={styles.intro}>
          <h2 className={styles.introTitle}>{data.officesTitle}</h2>
          <p className={styles.introLead}>{data.officesLead}</p>
        </div>

        {data.offices.map((office) => (
          <article key={office.city} className={styles.officeCard}>
            <h3 className={styles.city}>{office.city}</h3>
            <div className={styles.copyBlock}>
              <p className={styles.copy}>
                {office.address.map((line) => (
                  <span key={line} className={styles.line}>
                    {line}
                  </span>
                ))}
              </p>
            </div>
            <div className={styles.copyBlock}>
              <p className={styles.label}>Позвонить:</p>
              <a href={`tel:${office.phone.replace(/\s+/g, "")}`} className={styles.phone}>
                {office.phone}
              </a>
            </div>
            <div className={styles.copyBlock}>
              <p className={styles.label}>{office.hoursTitle}</p>
              <p className={styles.copy}>
                {office.hours.map((line) => (
                  <span key={line} className={styles.line}>
                    {line}
                  </span>
                ))}
              </p>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}
