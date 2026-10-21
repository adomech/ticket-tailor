import type { Report } from "../api/schema";
import { dateText } from "../lib/format";
import { emptyClass, headingClass, Metric } from "./ui";

export function Earnings({ report: r }: { report: Report }) {
  return (
    <section className="mt-9" aria-labelledby="earnings-heading">
      <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h2 className={headingClass} id="earnings-heading">
          Earnings during this period
        </h2>
        <span className="text-[13px] text-muted">
          {dateText(r.start)} – {dateText(r.reportingDate)} (UTC)
        </span>
      </div>
      <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
        <Metric
          label="Net earned"
          value={r.earnings.net}
          tone="teal"
          note="Sales less fees and refunds"
        />
        <Metric label="Gross sales" value={r.earnings.gross} tone="canvas" />
        <Metric
          label="Refunds issued"
          value={r.earnings.refunds}
          tone="peach"
        />
        <Metric
          label="Ticket Tailor fees"
          value={r.earnings.tailorFees}
          note="After proportional fee credits"
        />
        <Metric
          label="Stripe fees"
          value={r.earnings.stripeFees}
          note="Original processing fees are retained"
        />
        <Metric
          label="Ticket Tailor fee credits"
          value={r.earnings.feeCredits}
          note="Already included in the fees shown"
        />
      </dl>
      <p className="mt-3 text-sm leading-relaxed text-muted">
        Refunds reduce earnings on the day they are issued. Ticket Tailor
        returns its fee proportionally; Stripe retains its original fee.
      </p>
      {r.transactions.length === 0 && (
        <p className={`${emptyClass} mt-4`}>
          No sales or refunds in this period.
        </p>
      )}
    </section>
  );
}
