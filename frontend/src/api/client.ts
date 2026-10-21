import { z } from "zod";
import { reportSchema, type Report } from "./schema";

export async function fetchReport(
  date: string,
  days: string,
  signal: AbortSignal,
): Promise<Report> {
  const response = await fetch(
    `/api/dashboard?${new URLSearchParams({ date, days })}`,
    { signal, cache: "no-store" },
  );
  if (!response.ok) {
    const error = z
      .object({
        message: z.string(),
        reference: z.string().regex(/^[a-f0-9]{16}$/),
      })
      .safeParse(await response.json());
    throw new Error(
      error.success
        ? `Payments are unavailable. Reference: ${error.data.reference}`
        : "Payments are unavailable. Please try again.",
    );
  }
  return reportSchema.parse(await response.json());
}
