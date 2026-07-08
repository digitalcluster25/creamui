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

export async function GET() {
  try {
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
