export function Filters({
  date,
  days,
  onDate,
  onDays,
}: {
  date: string;
  days: string;
  onDate: (value: string) => void;
  onDays: (value: string) => void;
}) {
  return (
    <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
      <label className="flex flex-1 flex-col gap-1.5 text-xs font-bold">
        Reporting date (UTC)
        <input
          type="date"
          min="2000-01-01"
          max="9999-12-31"
          value={date}
          onChange={(event) => onDate(event.target.value)}
          className="min-h-11 min-w-0 rounded-xl border-[1.5px] border-ink bg-white px-3 py-2 text-sm text-ink"
        />
      </label>
      <label className="flex flex-1 flex-col gap-1.5 text-xs font-bold">
        Earnings period
        <select
          className="min-h-11 rounded-xl border-[1.5px] border-ink bg-white px-3 py-2 text-sm text-ink"
          value={days}
          onChange={(event) => onDays(event.target.value)}
        >
          <option value="1">Day</option>
          <option value="7">Last 7 days</option>
          <option value="30">Last 30 days</option>
        </select>
      </label>
    </div>
  );
}
