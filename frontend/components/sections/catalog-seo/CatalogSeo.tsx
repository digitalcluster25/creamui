import styles from "./CatalogSeo.module.css";

export type CatalogSeoFaq = {
  question: string;
  answer: string;
};

export type CatalogSeoData = {
  eyebrow?: string;
  title: string;
  paragraphs: string[];
  bullets?: string[];
  faqs?: CatalogSeoFaq[];
};

type Props = {
  data: CatalogSeoData | null;
};

export function CatalogSeo({ data }: Props) {
  if (!data) return null;

  return (
    <section className={styles.section}>
      <div className={`${styles.shell}${!data.faqs?.length ? ` ${styles.shellFull}` : ""}`}>
        <div className={styles.copy}>
          {data.eyebrow && <p className={styles.eyebrow}>{data.eyebrow}</p>}
          <h2 className={styles.title}>{data.title}</h2>
          <div className={styles.text}>
            {data.paragraphs.map((paragraph) => (
              <p key={paragraph}>{paragraph}</p>
            ))}
          </div>
          {data.bullets && data.bullets.length > 0 && (
            <ul className={styles.bullets}>
              {data.bullets.map((bullet) => (
                <li key={bullet}>{bullet}</li>
              ))}
            </ul>
          )}
        </div>
        {data.faqs && data.faqs.length > 0 && (
          <div className={styles.faq}>
            <h3 className={styles.faqTitle}>Частые вопросы</h3>
            <div className={styles.faqList}>
              {data.faqs.map((faq) => (
                <details key={faq.question} className={styles.faqItem}>
                  <summary className={styles.faqQuestion}>{faq.question}</summary>
                  <p className={styles.faqAnswer}>{faq.answer}</p>
                </details>
              ))}
            </div>
          </div>
        )}
      </div>
    </section>
  );
}
