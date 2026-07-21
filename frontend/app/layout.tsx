import type { Metadata } from "next";
import { CurrencyProvider } from "@/components/providers/CurrencyProvider";

import "./globals.css";

export const metadata: Metadata = {
  title: "CreamUI Frontend",
  description: "Headless frontend previews for CreamUI blocks.",
  metadataBase: new URL(process.env.NEXT_PUBLIC_SITE_URL || "https://hws.shopping"),
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="ru">
      <body>
        <CurrencyProvider>{children}</CurrencyProvider>
      </body>
    </html>
  );
}
