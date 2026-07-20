import { revalidatePath } from "next/cache";
import { NextRequest, NextResponse } from "next/server";

export async function POST(request: NextRequest) {
  const expectedSecret = process.env.NEXT_REVALIDATE_SECRET;
  const providedSecret =
    request.headers.get("x-revalidate-secret") ??
    request.nextUrl.searchParams.get("secret");

  if (expectedSecret && providedSecret !== expectedSecret) {
    return NextResponse.json({ ok: false, error: "Unauthorized" }, { status: 401 });
  }

  revalidatePath("/");
  revalidatePath("/", "layout");
  revalidatePath("/catalog");
  revalidatePath("/catalog/[category]", "page");
  revalidatePath("/brands/[slug]", "page");
  revalidatePath("/product/[slug]", "page");

  return NextResponse.json({ ok: true, revalidated: true });
}
