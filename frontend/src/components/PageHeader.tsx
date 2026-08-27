import type { ReactNode } from "react";
import { ArrowLeft } from "lucide-react";

export function PageHeader({
  title,
  subtitle,
  actions,
}: {
  title: string;
  subtitle?: string;
  actions?: ReactNode;
}) {
  return (
    <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div className="min-w-0">
        <button
          type="button"
          onClick={() => window.history.back()}
          className="mb-3 inline-flex items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-muted-foreground transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/25"
        >
          <ArrowLeft className="h-3.5 w-3.5" />
          Back
        </button>
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">{title}</h1>
        {subtitle && <p className="mt-1 text-sm text-muted-foreground">{subtitle}</p>}
      </div>
      {actions && <div className="flex w-full flex-wrap gap-2 sm:w-auto">{actions}</div>}
    </div>
  );
}

export function EmptyState({
  feature,
  description = "No records found.",
}: {
  feature: string;
  description?: string;
}) {
  return (
    <div className="rounded-2xl border border-dashed border-border bg-card p-10 text-center shadow-[var(--shadow-card)]">
      <h2 className="text-lg font-semibold">{feature}</h2>
      <p className="mt-2 text-sm text-muted-foreground">
        {description}
      </p>
    </div>
  );
}
