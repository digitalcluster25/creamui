"use client";

import { useState } from "react";
import type { ContactFormData } from "@/lib/types/contactForm";
import styles from "./ContactForm.module.css";

type Status = "idle" | "sending" | "success" | "error";

export function ContactForm({ data }: { data: ContactFormData }) {
  const [objectType, setObjectType] = useState("");
  const [area, setArea] = useState("");
  const [phone, setPhone] = useState("");
  const [status, setStatus] = useState<Status>("idle");
  const [errorMsg, setErrorMsg] = useState("");

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!phone.trim()) {
      setStatus("error");
      setErrorMsg("Укажите телефон");
      return;
    }

    setStatus("sending");
    setErrorMsg("");

    try {
      const res = await fetch("/api/lead", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          objectType,
          area,
          phone,
          page: typeof window !== "undefined" ? window.location.href : undefined,
        }),
      });
      const result = await res.json();
      if (!res.ok) {
        throw new Error(result?.error || "Не удалось отправить заявку");
      }
      setStatus("success");
      setObjectType("");
      setArea("");
      setPhone("");
    } catch (err) {
      setStatus("error");
      setErrorMsg(err instanceof Error ? err.message : "Не удалось отправить заявку");
    }
  }

  return (
    <div className={styles.card}>
      <div className={styles.inner}>
        <div className={styles.copy}>
          <h2 className={styles.title} dangerouslySetInnerHTML={{ __html: data.title }} />
          <p className={styles.lead}>{data.lead}</p>
        </div>
        <div className={styles.formWidget}>
          {status === "success" ? (
            <p className={styles.lead} role="status">
              Спасибо! Заявка отправлена, мы свяжемся с вами.
            </p>
          ) : (
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
                <button type="submit" className={styles.submitBtn} disabled={status === "sending"}>
                  {status === "sending" ? "Отправка…" : data.submitLabel}
                </button>
              </div>
              {status === "error" && (
                <p role="alert" style={{ color: "#b3261e", fontSize: 14, marginTop: 8 }}>
                  {errorMsg}
                </p>
              )}
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
