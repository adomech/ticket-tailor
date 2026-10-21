import type { Report } from "../api/schema";
import { dateText, moneyText } from "../lib/format";
import {
  Badge,
  emptyClass,
  headingClass,
  panelBodyClass,
  panelClass,
  panelHeaderClass,
} from "./ui";

export function Transactions({ report: r }: { report: Report }) {
  return (
    <section
      className={`${panelClass} mt-6`}
      aria-labelledby="transactions-heading"
    >
      <div className={panelHeaderClass}>
        <h2 className={headingClass} id="transactions-heading">
          Transactions in this period
        </h2>
        <span className="rounded-full border border-ink bg-blush px-2.5 py-1 text-xs font-bold">
          {r.transactions.length} transactions
        </span>
      </div>
      <div className={panelBodyClass}>
        {r.transactions.length === 0 ? (
          <p className={emptyClass}>No transactions to show.</p>
        ) : (
          r.transactions.map((t) => (
            <details
              className="group border-t border-line first:border-t-0"
              key={t.id}
            >
              <summary className="flex min-h-14 cursor-pointer list-none items-center gap-3 py-4 text-sm marker:content-none before:text-xl before:font-bold before:content-['+'] group-open:before:content-['−']">
                <span className="min-w-0 break-words">
                  <span className="flex flex-wrap items-center gap-2 font-bold">
                    {t.kind === "refund" ? "Refund" : "Sale"} · {t.reference}
                    <Badge tone={t.kind === "refund" ? "peach" : "teal"}>
                      {t.kind === "refund" ? "Refund" : "Charge"}
                    </Badge>
                  </span>
                  <span className="block text-sm text-muted">
                    {dateText(t.created)} · Details for {t.id}
                  </span>
                </span>
                <strong className="ml-auto whitespace-nowrap font-serif text-lg font-black tracking-[-0.04em] tabular-nums">
                  {moneyText(t.net)}
                </strong>
              </summary>
              <dl className="mb-4 rounded-xl bg-blush px-4 py-2 [&>div]:flex [&>div]:items-baseline [&>div]:justify-between [&>div]:gap-4 [&>div]:border-b [&>div]:border-line [&>div]:py-3 [&>div]:text-sm [&>div:last-child]:border-b-0 [&_dd]:whitespace-nowrap [&_dd]:text-right [&_dd]:tabular-nums">
                <div>
                  <dt>
                    {t.kind === "refund" ? "Refund issued" : "Buyer paid"}
                  </dt>
                  <dd>{moneyText(t.amount)}</dd>
                </div>
                <div>
                  <dt>Ticket Tailor fee / credit</dt>
                  <dd>{moneyText(t.tailorFee)}</dd>
                </div>
                <div>
                  <dt>Stripe fee</dt>
                  <dd>{moneyText(t.stripeFee)}</dd>
                </div>
                <div>
                  <dt>Balance movement</dt>
                  <dd>{moneyText(t.net)}</dd>
                </div>
                <div>
                  <dt>Available on</dt>
                  <dd>{dateText(t.available)}</dd>
                </div>
              </dl>
            </details>
          ))
        )}
      </div>
    </section>
  );
}
