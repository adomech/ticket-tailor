import type { ReactNode } from "react";
import { panelBodyClass, panelClass, panelHeaderClass } from "./styles";

export function Panel({
  header,
  children,
  className = "",
}: {
  header: ReactNode;
  children: ReactNode;
  className?: string;
}) {
  return (
    <section className={`${panelClass} ${className}`}>
      <div className={panelHeaderClass}>{header}</div>
      <div className={panelBodyClass}>{children}</div>
    </section>
  );
}
