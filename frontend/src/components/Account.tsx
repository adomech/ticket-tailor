import type { Report } from "../api/schema";
import { dateText, moneyText } from "../lib/format";
import {
  Badge,
  emptyClass,
  headingClass,
  Metric,
  panelBodyClass,
  panelClass,
  panelHeaderClass,
} from "./ui";

export function Account({ report: r }: { report: Report }) {
  return (
    <section className="mt-10" aria-labelledby="balance-heading">
      <h2 className={headingClass} id="balance-heading">
        Balances as of {dateText(r.reportingDate)} (UTC)
      </h2>
      <p className="mt-2 mb-5 text-muted">
        All activity up to this date, independent of the earnings period.
      </p>
      <dl className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <Metric
          label="Received by bank"
          value={r.balances.bank}
          note="Money that has arrived"
          tone="teal"
        />
        <Metric
          label="Sent, in transit"
          value={r.balances.transit}
          note="Left your balance; not yet arrived"
          tone="yellow"
        />
        <Metric
          label="Remaining balance"
          value={r.balances.remaining}
          note="Includes refund adjustments"
        />
      </dl>
      <div className="mt-5 grid gap-5 md:grid-cols-2">
        <section className={panelClass} aria-labelledby="remaining-heading">
          <div className={panelHeaderClass}>
            <h3 className={headingClass} id="remaining-heading">
              When remaining funds are available
            </h3>
            <Badge tone="teal">Available</Badge>
          </div>
          <div className={panelBodyClass}>
            <dl className="mt-1">
              <div className="flex items-baseline justify-between gap-4 border-b border-line py-3 text-sm">
                <dt>Available now</dt>
                <dd className="text-right tabular-nums">
                  {moneyText(r.balances.available)}
                </dd>
              </div>
              <div className="flex items-baseline justify-between gap-4 border-b border-line py-3 text-sm">
                <dt>Available later</dt>
                <dd className="text-right tabular-nums">
                  {moneyText(r.balances.later)}
                </dd>
              </div>
            </dl>
            {r.availability.length === 0 ? (
              <p className={`${emptyClass} mt-4`}>
                No funds remain in the balance.
              </p>
            ) : (
              <ul className="mt-4">
                {r.availability.map((group) => (
                  <li
                    className="flex items-center justify-between gap-4 border-b border-line py-4 last:border-b-0"
                    key={group.date}
                  >
                    <div>
                      <strong>{dateText(group.date)}</strong>
                      <p className="text-sm text-muted">
                        {group.amount < 0
                          ? "Refund adjustment · balance debit"
                          : group.available
                            ? "Available for payout"
                            : "Not yet available"}
                      </p>
                    </div>
                    <strong className="tabular-nums">
                      {moneyText(group.amount)}
                    </strong>
                  </li>
                ))}
              </ul>
            )}
            <p className="mt-4 text-sm leading-relaxed text-muted">
              These are availability dates, not future payout dates. A refund
              after a payout remains a separate debit.
            </p>
          </div>
        </section>
        <section
          className={panelClass}
          aria-labelledby="reconciliation-heading"
        >
          <div className={panelHeaderClass}>
            <h3 className={headingClass} id="reconciliation-heading">
              Why sales and your bank differ
            </h3>
            <Badge tone="teal">Reconciled</Badge>
          </div>
          <div className={panelBodyClass}>
            <p className="text-sm text-muted">
              All activity through {dateText(r.reportingDate)}
            </p>
            <dl className="mt-4 [&>div]:flex [&>div]:items-baseline [&>div]:justify-between [&>div]:gap-4 [&>div]:border-b [&>div]:border-line [&>div]:py-3 [&>div]:text-sm [&_dd]:whitespace-nowrap [&_dd]:text-right [&_dd]:tabular-nums">
              <div>
                <dt>Gross sales</dt>
                <dd>{moneyText(r.lifetime.gross)}</dd>
              </div>
              <div>
                <dt>Less Ticket Tailor fees (after credits)</dt>
                <dd>{moneyText(r.lifetime.tailorFees)}</dd>
              </div>
              <div>
                <dt>Less Stripe fees</dt>
                <dd>{moneyText(r.lifetime.stripeFees)}</dd>
              </div>
              <div>
                <dt>Less refunds issued</dt>
                <dd>{moneyText(r.lifetime.refunds)}</dd>
              </div>
              <div className="font-bold">
                <dt>Net earned</dt>
                <dd>{moneyText(r.lifetime.net)}</dd>
              </div>
              <div>
                <dt>Held in remaining balance</dt>
                <dd>{moneyText(r.balances.remaining)}</dd>
              </div>
              <div>
                <dt>Sent, in transit</dt>
                <dd>{moneyText(r.balances.transit)}</dd>
              </div>
              <div className="font-bold">
                <dt>Received by bank</dt>
                <dd>{moneyText(r.balances.bank)}</dd>
              </div>
              <div>
                <dt>Paid out to date (all sent)</dt>
                <dd>{moneyText(r.balances.sent)}</dd>
              </div>
            </dl>
          </div>
        </section>
      </div>
    </section>
  );
}
