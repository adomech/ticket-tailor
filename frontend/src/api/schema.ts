import { z } from "zod";

const money = z.number().int().safe();
const date = z.iso.date();
const totals = z.object({
  gross: money,
  refunds: money,
  tailorFees: money,
  stripeFees: money,
  feeCredits: money,
  net: money,
});

export const reportSchema = z.object({
  reportingDate: date,
  start: date,
  days: z.union([z.literal(1), z.literal(7), z.literal(30)]),
  currency: z.literal("GBP"),
  earnings: totals,
  lifetime: totals,
  balances: z.object({
    bank: money,
    transit: money,
    sent: money,
    remaining: money,
    available: money,
    later: money,
  }),
  availability: z.array(
    z.object({ date, amount: money, available: z.boolean() }),
  ),
  payouts: z.array(
    z.object({
      id: z.string(),
      amount: money,
      sent: date,
      arrival: date,
      state: z.enum(["arrived", "in_transit"]),
    }),
  ),
  transactions: z.array(
    z.object({
      id: z.string(),
      kind: z.enum(["charge", "refund"]),
      created: z.iso.datetime(),
      amount: money,
      net: money,
      tailorFee: money,
      stripeFee: money,
      available: date,
      reference: z.string(),
    }),
  ),
});

export type Report = z.infer<typeof reportSchema>;
