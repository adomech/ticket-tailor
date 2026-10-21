import { useState } from "react";
import { Account } from "./components/Account";
import { Earnings } from "./components/Earnings";
import { Filters } from "./components/Filters";
import { PayoutHistory } from "./components/PayoutHistory";
import { Transactions } from "./components/Transactions";
import { headingClass, panelClass } from "./components/ui";
import { useReport } from "./hooks/useReport";
import { DEFAULT_REPORTING_DATE } from "./lib/config";

export default function App() {
  const [date, setDate] = useState(DEFAULT_REPORTING_DATE);
  const [days, setDays] = useState("30");
  const [attempt, setAttempt] = useState(0);
  const state = useReport(date, days, attempt);

  return (
    <>
      <a
        className="absolute top-[-100px] left-3 z-10 rounded-xl bg-white p-3 font-bold text-ink focus:top-2"
        href="#main"
      >
        Skip to payments
      </a>
      <header className="bg-ink py-5 text-white">
        <div className="mx-auto flex max-w-[1180px] items-center justify-between gap-4 px-4 sm:px-6">
          <span className="font-serif text-2xl font-black tracking-[-0.04em]">
            ticket tailor
            <span className="ml-2 inline-grid size-6 place-items-center rounded-full bg-canvas text-sm text-coral">
              ✳
            </span>
          </span>
          <span className="hidden rounded-full border border-ink bg-peach px-3 py-1.5 text-xs font-bold text-ink sm:inline-block">
            Your box office / Payments
          </span>
        </div>
      </header>
      <main
        id="main"
        className="mx-auto max-w-[1180px] px-4 py-8 text-ink sm:px-6 md:py-12"
      >
        <p className="mb-3 text-[11px] font-bold tracking-[1.6px] uppercase">
          YOUR MONEY, MADE CLEAR
        </p>
        <div className="mb-6 flex flex-wrap items-end justify-between gap-6">
          <div>
            <h1 className="font-serif text-[38px] leading-tight font-black tracking-[-0.04em] sm:text-5xl lg:text-[56px]">
              Payments
            </h1>
            <p className="mt-2 text-muted">
              From ticket sold to money in your bank.
            </p>
          </div>
          <Filters date={date} days={days} onDate={setDate} onDays={setDays} />
        </div>
        <p className="rounded-xl border border-line bg-blush px-4 py-3 text-[13px] leading-relaxed">
          Historical demo · June 2026 sample activity. Change the date to
          explore earnings and balances.
        </p>
        {state.status === "loading" && (
          <div
            role="status"
            className={`${panelClass} my-6 flex items-center gap-3 p-5`}
          >
            <span
              aria-hidden="true"
              className="motion-safe:animate-status-pulse size-2.5 rounded-full bg-ink"
            />
            Loading payments…
          </div>
        )}
        {state.status === "error" && (
          <div
            role="alert"
            className="my-6 rounded-[20px] border-2 border-error bg-blush p-5 sm:p-6"
          >
            <h2 className={headingClass}>We couldn’t load your payments</h2>
            <p className="my-3">{state.message}</p>
            <button
              className="min-h-11 rounded-full border-2 border-ink bg-ink px-5 py-2 font-bold text-white hover:bg-[#3d4052]"
              onClick={() => setAttempt((value) => value + 1)}
            >
              Try again
            </button>
          </div>
        )}
        {state.status === "ready" && (
          <>
            <p className="mt-4 inline-flex rounded-full border border-ink bg-peach px-3 py-1.5 text-xs font-bold text-ink">
              Historical fixture data as at {state.report.reportingDate}
            </p>
            <p className="mt-3 text-sm text-muted">
              Reporting periods use inclusive UTC calendar days.
            </p>
            <Earnings report={state.report} />
            <Account report={state.report} />
            <PayoutHistory report={state.report} />
            <Transactions report={state.report} />
          </>
        )}
        <footer className="mt-8 border-t border-line pt-5 text-sm text-muted">
          All amounts in GBP · Calendar dates use UTC · Availability is not a
          bank-arrival forecast.
        </footer>
      </main>
    </>
  );
}
