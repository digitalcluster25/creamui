import type { Metadata } from "next";
import { Noto_Sans } from "next/font/google";
import { CurrencyProvider } from "@/components/providers/CurrencyProvider";

import "./globals.css";

const notoSans = Noto_Sans({
  subsets: ["latin", "cyrillic"],
  variable: "--font-noto-sans",
});

export const metadata: Metadata = {
  title: "CreamUI Frontend",
  description: "Headless frontend previews for CreamUI blocks.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="ru" className={notoSans.variable}>
      <body>
        <CurrencyProvider>{children}</CurrencyProvider>
      </body>
    </html>
  );
}
