import type { ReactNode } from "react";

type BadgeTone = "teal" | "yellow" | "peach";
const tones: Record<BadgeTone, string> = {
  teal: "bg-teal",
  yellow: "bg-yellow",
  peach: "bg-peach",
};

export function Badge({
  children,
  tone,
}: {
  children: ReactNode;
  tone: BadgeTone;
}) {
  return (
    <span
      className={`inline-block rounded-full border border-ink px-2.5 py-1 text-xs font-bold text-ink ${tones[tone]}`}
    >
      {children}
    </span>
  );
}
