import { useEffect, useState } from "react";
import { fetchReport } from "../api/client";
import type { Report } from "../api/schema";

export type ReportState =
  | { status: "loading" }
  | { status: "error"; message: string }
  | { status: "ready"; report: Report };

export function useReport(
  date: string,
  days: string,
  attempt: number,
): ReportState {
  const [state, setState] = useState<ReportState>({ status: "loading" });
  useEffect(() => {
    const controller = new AbortController();
    let active = true;
    setState({ status: "loading" });
    fetchReport(date, days, controller.signal)
      .then((report) => {
        if (active) setState({ status: "ready", report });
      })
      .catch((error: unknown) => {
        if (!active) return;
        setState({
          status: "error",
          message:
            error instanceof Error && error.message.includes("Reference:")
              ? error.message
              : "Payments are unavailable. Please try again.",
        });
      });
    return () => {
      active = false;
      controller.abort();
    };
  }, [date, days, attempt]);
  return state;
}
