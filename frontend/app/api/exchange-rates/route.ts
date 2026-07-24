import { NextResponse } from "next/server";
import { DEFAULT_CURRENCY_RATES } from "@/lib/currency/format";

export const revalidate = 3600;

type CbrValute = {
  Value: number;
  Nominal: number;
};

type CbrResponse = {
  Date?: string;
  Valute?: {
    USD?: CbrValute;
    AZN?: CbrValute;
    UZS?: CbrValute;
  };
};

function toRubPerUnit(value?: CbrValute): number | null {
  if (!value || !value.Value || !value.Nominal) {
    return null;
  }

  return value.Value / value.Nominal;
}

function getWpRatesUrl(): string | null {
  const graphqlUrl = process.env.NEXT_PUBLIC_WP_GRAPHQL_URL;
  if (!graphqlUrl) return null;

  try {
    const url = new URL(graphqlUrl);
    url.pathname = "/wp-json/hws-currency/v1/rates";
    url.search = "";
    return url.toString();
  } catch {
    return null;
  }
}

export async function GET() {
  try {
    const wpRatesUrl = getWpRatesUrl();

    if (wpRatesUrl) {
      const response = await fetch(wpRatesUrl, {
        next: { revalidate: 3600 },
      });

      if (response.ok) {
        const payload = await response.json() as {
          USD?: number;
          AZN?: number;
          UZS?: number;
          RUB?: number;
          updatedAt?: string;
        };

        if (payload.USD && payload.AZN && payload.UZS && payload.RUB) {
          return NextResponse.json(
            {
              USD: payload.USD,
              AZN: payload.AZN,
              UZS: payload.UZS,
              RUB: payload.RUB,
              updatedAt: payload.updatedAt,
            },
            {
              headers: {
                "Cache-Control": "s-maxage=3600, stale-while-revalidate=86400",
              },
            },
          );
        }
      }
    }

    const response = await fetch("https://www.cbr-xml-daily.ru/daily_json.js", {
      next: { revalidate: 3600 },
    });

    if (!response.ok) {
      throw new Error(`CBR rates request failed: ${response.status}`);
    }

    const payload = (await response.json()) as CbrResponse;
    const usdRub = toRubPerUnit(payload.Valute?.USD);
    const aznRub = toRubPerUnit(payload.Valute?.AZN);
    const uzsRub = toRubPerUnit(payload.Valute?.UZS);

    if (!usdRub || !aznRub || !uzsRub) {
      throw new Error("Incomplete CBR rates payload");
    }

    return NextResponse.json(
      {
        USD: 1,
        AZN: usdRub / aznRub,
        UZS: usdRub / uzsRub,
        RUB: usdRub,
        updatedAt: payload.Date,
      },
      {
        headers: {
          "Cache-Control": "s-maxage=3600, stale-while-revalidate=86400",
        },
      },
    );
  } catch {
    return NextResponse.json(
      DEFAULT_CURRENCY_RATES,
      {
        headers: {
          "Cache-Control": "s-maxage=300, stale-while-revalidate=3600",
        },
      },
    );
  }
}
