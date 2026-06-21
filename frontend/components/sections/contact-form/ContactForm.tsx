"use client";

import { useState } from "react";
import type { ContactFormData } from "@/lib/types/contactForm";
import styles from "./ContactForm.module.css";

export function ContactForm({ data }: { data: ContactFormData }) {
  const [objectType, setObjectType] = useState("");
  const [area, setArea] = useState("");
  const [phone, setPhone] = useState("");

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    // WPGraphQL mutation goes here
  }

  return (
    <div className={styles.card}>
      <div className={styles.inner}>
        <div className={styles.copy}>
          <h2 className={styles.title} dangerouslySetInnerHTML={{ __html: data.title }} />
          <p className={styles.lead}>{data.lead}</p>
        </div>
        <div className={styles.formWidget}>
          <form className={styles.form} onSubmit={handleSubmit} noValidate>
            <div className={styles.cellSelect}>
              <label className={styles.fieldLabel}>
                <span className={styles.srOnly}>Тип объекта</span>
                <select
                  className={styles.fieldControl}
                  value={objectType}
                  onChange={(e) => setObjectType(e.target.value)}
                >
                  <option value="">Тип объекта</option>
                  {data.objectTypes.map((type) => (
                    <option key={type} value={type}>
                      {type}
                    </option>
                  ))}
                </select>
              </label>
            </div>
            <div className={styles.cellArea}>
              <label className={styles.fieldLabel}>
                <span className={styles.srOnly}>Площадь</span>
                <input
                  type="text"
                  className={styles.fieldControl}
                  placeholder="Площадь, м²"
                  value={area}
                  onChange={(e) => setArea(e.target.value)}
                />
              </label>
            </div>
            <div className={styles.cellPhone}>
              <label className={styles.fieldLabel}>
                <span className={styles.srOnly}>Телефон</span>
                <input
                  type="tel"
                  className={styles.fieldControl}
                  placeholder="Ваш телефон"
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                />
              </label>
            </div>
            <div className={styles.cellSubmit}>
              <button type="submit" className={styles.submitBtn}>
                {data.submitLabel}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
