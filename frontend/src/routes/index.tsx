import { createFileRoute, Link } from "@tanstack/react-router";
import { GraduationCap, FileSignature, BarChart3, ShieldCheck, ArrowRight } from "lucide-react";
import { MasenoLogo } from "@/components/layout/Logo";

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: "MUGTS — Maseno University Graduate Tracer System" },
      {
        name: "description",
        content:
          "Official online platform for Maseno University alumni: complete tracer surveys, track your career, and request recommendation letters.",
      },
    ],
  }),
  component: Landing,
});

function Landing() {
  return (
    <div className="flex min-h-screen flex-col bg-background">
      <header className="sticky top-0 z-20 border-b border-white/10 bg-[var(--navy-deep)] shadow-sm">
        <div className="mx-auto flex min-h-16 w-full max-w-[1440px] flex-wrap items-center justify-between gap-2 px-4 py-3 sm:h-16 sm:flex-nowrap sm:px-6 sm:py-0 lg:px-10 xl:px-16">
          <MasenoLogo size={46} light />
          <div className="ml-auto flex shrink-0 items-center gap-1.5 sm:gap-2">
            <Link
              to="/login"
              className="rounded-lg px-3 py-2 text-sm font-medium text-white/90 hover:bg-white/10 sm:px-4"
            >
              Sign in
            </Link>
            <Link
              to="/register"
              className="rounded-lg bg-primary px-3 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-transform hover:-translate-y-0.5 hover:shadow-md sm:px-4"
            >
              Create account
            </Link>
          </div>
        </div>
      </header>

      <main className="flex flex-1 items-center">
        <section className="relative w-full overflow-hidden">
          <div className="mx-auto grid w-full max-w-[1440px] items-center gap-8 px-4 py-10 sm:px-6 sm:py-12 lg:grid-cols-2 lg:gap-12 lg:px-10 lg:py-16 xl:px-16">
            <div>
              <h1 className="mt-4 text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                <span className="text-primary">Maseno University</span>
                <br />
                Graduate Tracer System
              </h1>
              <p className="mt-4 max-w-xl text-base text-muted-foreground sm:text-lg">
                Stay connected with your alma mater. Complete tracer surveys, keep your career
                profile current, and request official recommendation letters — all in one secure
                portal.
              </p>
              <div className="mt-8 flex flex-wrap gap-3">
                <Link
                  to="/register"
                  className="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
                >
                  Get started <ArrowRight className="h-4 w-4" />
                </Link>
                <Link
                  to="/login"
                  className="inline-flex items-center rounded-xl border border-border bg-card px-6 py-3 text-sm font-semibold text-foreground hover:bg-muted"
                >
                  I have an account
                </Link>
              </div>
            </div>

            <div className="grid grid-cols-1 gap-3 min-[420px]:grid-cols-2 sm:gap-4">
              <FeatureCard
                icon={GraduationCap}
                title="Tracer Survey"
                text="Guided multi-step surveys."
                tone="primary"
              />
              <FeatureCard
                icon={FileSignature}
                title="Recommendation Letters"
                text="Officially issued with QR verification."
                tone="gold"
              />
              <FeatureCard
                icon={BarChart3}
                title="Career Analytics"
                text="Track employment and outcomes."
                tone="info"
              />
              <FeatureCard
                icon={ShieldCheck}
                title="Secure & Verified"
                text="Your data, protected."
                tone="success"
              />
            </div>
          </div>
        </section>
      </main>

      <footer className="mt-auto border-t border-border bg-card shadow-[0_-1px_0_oklch(0_0_0_/_0.02)]">
        <div className="mx-auto flex min-h-16 w-full max-w-[1440px] items-center justify-center px-4 py-4 text-center text-xs text-muted-foreground sm:px-6">
          <span>© {new Date().getFullYear()} Maseno University. All Rights Reserved.</span>
        </div>
      </footer>
    </div>
  );
}

function FeatureCard({
  icon: Icon,
  title,
  text,
  tone,
}: {
  icon: React.ComponentType<{ className?: string }>;
  title: string;
  text: string;
  tone: "primary" | "gold" | "info" | "success";
}) {
  const bg = {
    primary: "bg-primary/10 text-primary",
    gold: "bg-[var(--gold)]/20 text-[oklch(0.55_0.14_82)]",
    info: "bg-[oklch(0.65_0.11_210)]/15 text-[oklch(0.45_0.11_210)]",
    success: "bg-[oklch(0.65_0.17_148)]/15 text-[oklch(0.45_0.15_148)]",
  }[tone];
  return (
    <div className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)] transition-all hover:-translate-y-1 hover:shadow-[var(--shadow-card-hover)]">
      <div className={`grid h-10 w-10 place-items-center rounded-xl ${bg}`}>
        <Icon className="h-5 w-5" />
      </div>
      <h3 className="mt-3 font-semibold">{title}</h3>
      <p className="mt-1 text-sm text-muted-foreground">{text}</p>
    </div>
  );
}
