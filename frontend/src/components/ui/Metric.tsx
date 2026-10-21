import { moneyText } from "../../lib/format";

type Tone = "canvas" | "peach" | "yellow" | "teal" | "blush";
const tones: Record<Tone, string> = {
  canvas: "bg-canvas",
  peach: "bg-peach",
  yellow: "bg-yellow",
  teal: "bg-teal",
  blush: "bg-blush",
};

export function Metric({
  label,
  value,
  note,
  tone = "canvas",
}: {
  label: string;
  value: number;
  note?: string;
  tone?: Tone;
}) {
  return (
    <div
      className={`rounded-metric border-[1.5px] border-ink p-5 md:p-6 ${tones[tone]}`}
    >
      <dt className="text-[13px] font-bold sm:text-sm">{label}</dt>
      <dd className="mt-2 font-serif text-metric-sm leading-tight font-black tracking-[-0.04em] tabular-nums md:text-metric-md lg:text-metric-lg">
        {moneyText(value)}
      </dd>
      {note && (
        <dd className="mt-2 text-xs leading-relaxed text-ink">{note}</dd>
      )}
    </div>
  );
}
