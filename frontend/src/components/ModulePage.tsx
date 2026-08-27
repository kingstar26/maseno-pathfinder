import type { LucideIcon } from "lucide-react";
import { FilePlus2, Inbox } from "lucide-react";
import { PageHeader } from "./PageHeader";

type Section = { title: string; description: string; fields?: string[] };

export function ModulePage({
  title,
  subtitle,
  sections,
}: {
  title: string;
  subtitle: string;
  sections: Section[];
}) {
  return (
    <>
      <PageHeader title={title} subtitle={subtitle} />
      <div className="grid gap-4 xl:grid-cols-2">
        {sections.map((section) => (
          <section
            key={section.title}
            className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)] transition-shadow hover:shadow-[var(--shadow-card-hover)]"
          >
            <div className="flex items-start gap-3">
              <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                <FilePlus2 className="h-5 w-5" />
              </span>
              <div>
                <h2 className="font-semibold">{section.title}</h2>
                <p className="mt-1 text-sm text-muted-foreground">{section.description}</p>
              </div>
            </div>
            {section.fields?.length ? (
              <div className="mt-4 flex flex-wrap gap-2">
                {section.fields.map((field) => (
                  <span
                    key={field}
                    className="rounded-md border border-border bg-muted/45 px-2.5 py-1 text-xs text-muted-foreground"
                  >
                    {field}
                  </span>
                ))}
              </div>
            ) : null}
            <div className="mt-5 flex items-center gap-2 rounded-xl border border-dashed border-border px-4 py-5 text-sm text-muted-foreground">
              <Inbox className="h-4 w-4 shrink-0" /> No records found.
            </div>
          </section>
        ))}
      </div>
    </>
  );
}

export type { Section };
