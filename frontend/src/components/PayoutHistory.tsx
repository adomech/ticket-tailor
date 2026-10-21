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

export function PayoutHistory({ report }: { report: Report }) {
  return (
    <section className={`${panelClass} mt-6`} aria-labelledby="payout-heading">
      <div className={panelHeaderClass}>
        <h2 className={headingClass} id="payout-heading">
          Payout history
        </h2>
        <span className="text-[13px] text-muted">
          Up to {dateText(report.reportingDate)}
        </span>
      </div>
      <div className={panelBodyClass}>
        {report.payouts.length === 0 ? (
          <p className={emptyClass}>No payouts sent by this date.</p>
        ) : (
          report.payouts.map((payout) => (
            <div
              className="flex items-center justify-between gap-4 border-b border-line py-4 last:border-b-0"
              key={payout.id}
            >
              <div>
                <strong className="font-serif text-lg font-black tracking-[-0.04em] tabular-nums">
                  {moneyText(payout.amount)}
                </strong>
                <p className="mt-1 text-sm text-muted">
                  Sent {dateText(payout.sent)}
                </p>
              </div>
              <div className="text-right">
                <Badge tone={payout.state === "arrived" ? "teal" : "yellow"}>
                  {payout.state === "arrived"
                    ? "Received by bank"
                    : "In transit"}
                </Badge>
                <p className="mt-2 text-sm text-muted">
                  {payout.state === "arrived" ? "Arrived" : "Recorded arrival"}{" "}
                  {dateText(payout.arrival)}
                </p>
              </div>
            </div>
          ))
        )}
      </div>
    </section>
  );
}
