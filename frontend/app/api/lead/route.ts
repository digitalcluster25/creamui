import { NextResponse } from "next/server";

type LeadBody = {
  objectType?: string;
  area?: string;
  phone?: string;
  page?: string;
};

export async function POST(request: Request) {
  let body: LeadBody;
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ error: "Некорректный запрос" }, { status: 400 });
  }

  const phone = (body.phone ?? "").trim();
  if (!phone) {
    return NextResponse.json({ error: "Укажите телефон" }, { status: 400 });
  }

  const token = process.env.TELEGRAM_BOT_TOKEN;
  const chatId = process.env.TELEGRAM_CHAT_ID;

  if (!token || !chatId) {
    // Не путать с ошибкой пользователя — это конфигурация, чинить на сервере, не на форме.
    console.error("app/api/lead: TELEGRAM_BOT_TOKEN/TELEGRAM_CHAT_ID не заданы в .env.local");
    return NextResponse.json(
      { error: "Форма временно не настроена, попробуйте позже" },
      { status: 500 }
    );
  }

  const lines = [
    "🔔 Новая заявка с сайта",
    body.objectType ? `Тип объекта: ${body.objectType}` : null,
    body.area ? `Площадь: ${body.area}` : null,
    `Телефон: ${phone}`,
    body.page ? `Страница: ${body.page}` : null,
  ].filter(Boolean);

  try {
    const res = await fetch(`https://api.telegram.org/bot${token}/sendMessage`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ chat_id: chatId, text: lines.join("\n") }),
    });
    const data = await res.json();
    if (!data.ok) {
      console.error("Telegram API error:", data);
      return NextResponse.json({ error: "Не удалось отправить заявку" }, { status: 502 });
    }
  } catch (e) {
    console.error("Telegram fetch error:", e);
    return NextResponse.json({ error: "Не удалось отправить заявку" }, { status: 502 });
  }

  return NextResponse.json({ ok: true });
}
