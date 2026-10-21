import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import App from "./App";

const payload = {
  reportingDate: "2026-06-20",
  start: "2026-05-22",
  days: 30,
  currency: "GBP",
  earnings: {
    gross: 116250,
    refunds: 10750,
    tailorFees: 3805,
    stripeFees: 2831,
    feeCredits: 320,
    net: 98864,
  },
  lifetime: {
    gross: 116250,
    refunds: 10750,
    tailorFees: 3805,
    stripeFees: 2831,
    feeCredits: 320,
    net: 98864,
  },
  balances: {
    bank: 44536,
    transit: 0,
    sent: 44536,
    remaining: 54328,
    available: 54328,
    later: 0,
  },
  availability: [{ date: "2026-06-20", amount: -4380, available: true }],
  payouts: [
    {
      id: "po_1",
      amount: 44536,
      sent: "2026-06-16",
      arrival: "2026-06-18",
      state: "arrived",
    },
  ],
  transactions: [
    {
      id: "re_1",
      kind: "refund",
      created: "2026-06-20T11:30:00Z",
      amount: 4500,
      net: -4380,
      tailorFee: -120,
      stripeFee: 0,
      available: "2026-06-20",
      reference: "ch_1",
    },
  ],
};
const respond = (body: unknown = payload, ok = true) =>
  Promise.resolve({ ok, json: async () => body } as Response);

it("shows required financial answers and transaction details", async () => {
  vi.spyOn(globalThis, "fetch").mockImplementation(() => respond());
  render(<App />);
  expect((await screen.findAllByText("£988.64")).length).toBeGreaterThan(0);
  expect(screen.getAllByText("£445.36").length).toBeGreaterThan(0);
  expect(
    screen.getByText("Refund adjustment · balance debit"),
  ).toBeInTheDocument();
  await userEvent.click(screen.getByText(/Refund · ch_1/));
  expect(screen.getByText("Balance movement")).toBeInTheDocument();
});

it("conveys payment statuses with text as well as colour", async () => {
  vi.spyOn(globalThis, "fetch").mockImplementation(() => respond());
  render(<App />);
  expect(
    (await screen.findAllByText("Received by bank")).length,
  ).toBeGreaterThan(0);
  expect(screen.getByText("Available")).toBeInTheDocument();
  expect(screen.getByText("Reconciled")).toBeInTheDocument();
  expect(screen.getByText("Refund", { selector: "span" })).toBeInTheDocument();
});

it("loads changed period and date filters", async () => {
  const fetcher = vi
    .spyOn(globalThis, "fetch")
    .mockImplementation(() => respond());
  render(<App />);
  await screen.findAllByText("£988.64");
  await userEvent.selectOptions(screen.getByLabelText("Earnings period"), "7");
  await waitFor(() =>
    expect(fetcher.mock.calls.at(-1)?.[0]).toContain("days=7"),
  );
  await userEvent.clear(screen.getByLabelText("Reporting date (UTC)"));
  await userEvent.type(
    screen.getByLabelText("Reporting date (UTC)"),
    "2026-06-19",
  );
  await waitFor(() =>
    expect(fetcher.mock.calls.at(-1)?.[0]).toContain("date=2026-06-19"),
  );
});

it("shows loading, safe error and retry", async () => {
  let resolveFirst: ((value: Response) => void) | undefined;
  const fetcher = vi
    .spyOn(globalThis, "fetch")
    .mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          resolveFirst = resolve;
        }),
    )
    .mockImplementation(() => respond());
  render(<App />);
  expect(screen.getByRole("status")).toHaveTextContent("Loading payments");
  resolveFirst?.({
    ok: false,
    json: async () => ({ message: "x", reference: "1234567890abcdef" }),
  } as Response);
  expect(await screen.findByRole("alert")).toHaveTextContent(
    "Reference: 1234567890abcdef",
  );
  await userEvent.click(screen.getByRole("button", { name: "Try again" }));
  expect((await screen.findAllByText("£988.64")).length).toBeGreaterThan(0);
  expect(fetcher).toHaveBeenCalledTimes(2);
});

it("ignores stale responses after filters change", async () => {
  let stale: ((value: Response) => void) | undefined;
  vi.spyOn(globalThis, "fetch")
    .mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          stale = resolve;
        }),
    )
    .mockImplementation(() =>
      respond({
        ...payload,
        earnings: { ...payload.earnings, net: 46366 },
        days: 7,
        start: "2026-06-14",
      }),
    );
  render(<App />);
  await userEvent.selectOptions(screen.getByLabelText("Earnings period"), "7");
  expect(await screen.findByText("£463.66")).toBeInTheDocument();
  stale?.(await respond());
  await waitFor(() => expect(screen.getByText("£463.66")).toBeInTheDocument());
});

it("shows empty period states", async () => {
  vi.spyOn(globalThis, "fetch").mockImplementation(() =>
    respond({
      ...payload,
      earnings: {
        gross: 0,
        refunds: 0,
        tailorFees: 0,
        stripeFees: 0,
        feeCredits: 0,
        net: 0,
      },
      transactions: [],
    }),
  );
  render(<App />);
  expect(
    await screen.findByText("No sales or refunds in this period."),
  ).toBeInTheDocument();
  expect(screen.getByText("No transactions to show.")).toBeInTheDocument();
});
