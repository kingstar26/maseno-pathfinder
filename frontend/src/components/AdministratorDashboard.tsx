import { useEffect, useState } from "react";
import {
  Activity,
  Building,
  Building2,
  GraduationCap,
  History,
  Layers3,
  ShieldCheck,
  Sparkles,
  User,
  UserCheck,
  UsersRound,
} from "lucide-react";
import { PageHeader } from "@/components/PageHeader";
import { Skeleton } from "@/components/ui/skeleton";
import { apiErrorMessage, getAdministratorDashboard, type AdministratorDashboardData } from "@/lib/api";

export function AdministratorDashboard() {
  const [data, setData] = useState<AdministratorDashboardData | null>(null);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    getAdministratorDashboard()
      .then((result) => {
        if (active) setData(result);
      })
      .catch((err: unknown) => {
        if (active) {
          setError(
            apiErrorMessage(
              err,
              "Administrator dashboard loading failed because the server could not retrieve statistics. Please try again later."
            )
          );
        }
      });
    return () => {
      active = false;
    };
  }, []);

  const getActivityIcon = (type: string) => {
    const lower = type.toLowerCase();
    if (lower.includes("user")) return <User className="h-4 w-4 text-blue-500" />;
    if (lower.includes("faculty") || lower.includes("faculties")) return <Building2 className="h-4 w-4 text-amber-500" />;
    if (lower.includes("department")) return <Building className="h-4 w-4 text-purple-500" />;
    if (lower.includes("programme")) return <Layers3 className="h-4 w-4 text-rose-500" />;
    return <Activity className="h-4 w-4 text-primary" />;
  };

  const activePct =
    data && data.total_users > 0
      ? Math.round((data.active_accounts / data.total_users) * 100)
      : 0;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Administrator dashboard"
        subtitle="System configuration and account overview."
      />

      {error && (
        <div className="rounded-2xl border border-destructive/30 bg-destructive/10 p-5 text-sm text-destructive shadow-sm">
          <p className="font-semibold">Notice</p>
          <p className="mt-1">{error}</p>
        </div>
      )}

      {/* Loading Skeletons */}
      {!data && !error && (
        <div className="space-y-6">
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {Array.from({ length: 6 }, (_, index) => (
              <div
                key={index}
                className="rounded-2xl border border-border/80 bg-card p-5 shadow-sm space-y-3"
              >
                <div className="flex justify-between items-center">
                  <Skeleton className="h-4 w-24" />
                  <Skeleton className="h-9 w-9 rounded-xl" />
                </div>
                <Skeleton className="h-8 w-20" />
                <Skeleton className="h-3 w-32" />
              </div>
            ))}
          </div>
          <div className="rounded-2xl border border-border bg-card p-6 shadow-sm space-y-4">
            <Skeleton className="h-6 w-48" />
            <div className="space-y-3">
              {Array.from({ length: 4 }, (_, i) => (
                <Skeleton key={i} className="h-12 w-full rounded-xl" />
              ))}
            </div>
          </div>
        </div>
      )}

      {data && (
        <>
          {/* Top 6 Metric Cards Grid */}
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {/* Card 1: Total Users */}
            <div className="group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-indigo-500/40 hover:shadow-xl">
              <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 to-blue-600" />
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Total users
                  </p>
                  <p className="mt-2 text-3xl font-extrabold tracking-tight text-foreground">
                    {data.total_users.toLocaleString()}
                  </p>
                </div>
                <div className="rounded-xl bg-indigo-500/10 p-2.5 text-indigo-600 dark:text-indigo-400 transition-transform duration-300 group-hover:scale-110 shadow-sm">
                  <UsersRound className="h-6 w-6" />
                </div>
              </div>
              <div className="mt-4 flex items-center justify-between text-xs text-muted-foreground">
                <span className="inline-flex items-center gap-1 rounded-full bg-indigo-500/10 px-2 py-0.5 font-medium text-indigo-600 dark:text-indigo-400">
                  {data.total_administrators} Admin
                </span>
                <span>System accounts</span>
              </div>
            </div>

            {/* Card 2: Graduates */}
            <div className="group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-emerald-500/40 hover:shadow-xl">
              <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-600" />
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Graduates
                  </p>
                  <p className="mt-2 text-3xl font-extrabold tracking-tight text-foreground">
                    {data.total_graduates.toLocaleString()}
                  </p>
                </div>
                <div className="rounded-xl bg-emerald-500/10 p-2.5 text-emerald-600 dark:text-emerald-400 transition-transform duration-300 group-hover:scale-110 shadow-sm">
                  <GraduationCap className="h-6 w-6" />
                </div>
              </div>
              <div className="mt-4 flex items-center gap-1.5 text-xs text-muted-foreground">
                <Sparkles className="h-3.5 w-3.5 text-emerald-500" />
                <span>Registered alumni profiles</span>
              </div>
            </div>

            {/* Card 3: Management Users */}
            <div className="group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-purple-500/40 hover:shadow-xl">
              <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-purple-500 to-violet-600" />
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Management users
                  </p>
                  <p className="mt-2 text-3xl font-extrabold tracking-tight text-foreground">
                    {data.total_management_users.toLocaleString()}
                  </p>
                </div>
                <div className="rounded-xl bg-purple-500/10 p-2.5 text-purple-600 dark:text-purple-400 transition-transform duration-300 group-hover:scale-110 shadow-sm">
                  <UserCheck className="h-6 w-6" />
                </div>
              </div>
              <div className="mt-4 flex items-center gap-1.5 text-xs text-muted-foreground">
                <span className="inline-flex items-center gap-1 rounded-full bg-purple-500/10 px-2 py-0.5 font-medium text-purple-600 dark:text-purple-400">
                  Institutional Staff
                </span>
              </div>
            </div>

            {/* Card 4: Active Accounts */}
            <div className="group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-sky-500/40 hover:shadow-xl">
              <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-sky-500 to-cyan-600" />
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Active accounts
                  </p>
                  <p className="mt-2 text-3xl font-extrabold tracking-tight text-foreground">
                    {data.active_accounts.toLocaleString()}
                  </p>
                </div>
                <div className="rounded-xl bg-sky-500/10 p-2.5 text-sky-600 dark:text-sky-400 transition-transform duration-300 group-hover:scale-110 shadow-sm">
                  <ShieldCheck className="h-6 w-6" />
                </div>
              </div>
              <div className="mt-4 flex items-center justify-between text-xs">
                <span className="inline-flex items-center gap-1 rounded-full bg-sky-500/10 px-2 py-0.5 font-medium text-sky-600 dark:text-sky-400">
                  {activePct}% active
                </span>
                <span className="text-muted-foreground">Verified users</span>
              </div>
            </div>

            {/* Card 5: Faculties */}
            <div className="group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40 hover:shadow-xl">
              <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 to-orange-600" />
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Faculties
                  </p>
                  <p className="mt-2 text-3xl font-extrabold tracking-tight text-foreground">
                    {data.total_faculties.toLocaleString()}
                  </p>
                </div>
                <div className="rounded-xl bg-amber-500/10 p-2.5 text-amber-600 dark:text-amber-400 transition-transform duration-300 group-hover:scale-110 shadow-sm">
                  <Building2 className="h-6 w-6" />
                </div>
              </div>
              <div className="mt-4 flex items-center justify-between text-xs text-muted-foreground">
                <span className="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2 py-0.5 font-medium text-amber-600 dark:text-amber-400">
                  {data.total_departments} Departments
                </span>
                <span>Schools & Faculties</span>
              </div>
            </div>

            {/* Card 6: Programmes */}
            <div className="group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-rose-500/40 hover:shadow-xl">
              <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-rose-500 to-pink-600" />
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Programmes
                  </p>
                  <p className="mt-2 text-3xl font-extrabold tracking-tight text-foreground">
                    {data.total_programmes.toLocaleString()}
                  </p>
                </div>
                <div className="rounded-xl bg-rose-500/10 p-2.5 text-rose-600 dark:text-rose-400 transition-transform duration-300 group-hover:scale-110 shadow-sm">
                  <Layers3 className="h-6 w-6" />
                </div>
              </div>
              <div className="mt-4 flex items-center gap-1.5 text-xs text-muted-foreground">
                <span>Academic degree offerings</span>
              </div>
            </div>
          </div>

          {/* Recent System Activity Section */}
          <section className="rounded-2xl border border-border/80 bg-card p-6 shadow-[var(--shadow-card)]">
            <div className="flex items-center gap-2">
              <div className="rounded-lg bg-primary/10 p-2 text-primary">
                <History className="h-5 w-5" />
              </div>
              <div>
                <h2 className="text-lg font-bold tracking-tight text-foreground">
                  Recent system activity
                </h2>
                <p className="text-xs text-muted-foreground">
                  Audit trail of recent system additions and profile registrations
                </p>
              </div>
            </div>

            <div className="mt-6 space-y-3">
              {data.recent_system_activity.length ? (
                data.recent_system_activity.map((item, i) => (
                  <div
                    key={`${item.type}-${i}`}
                    className="flex items-center justify-between gap-4 rounded-xl border border-border/60 bg-muted/30 p-3.5 transition-all duration-200 hover:bg-muted/70"
                  >
                    <div className="flex items-center gap-3 min-w-0">
                      <div className="rounded-lg bg-background p-2 shadow-sm shrink-0">
                        {getActivityIcon(item.type)}
                      </div>
                      <div className="min-w-0">
                        <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                          {item.type}
                        </p>
                        <p className="text-sm font-medium text-foreground truncate mt-0.5">
                          {item.label}
                        </p>
                      </div>
                    </div>
                    <time className="shrink-0 rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
                      {new Date(item.created_at).toLocaleDateString(undefined, {
                        year: "numeric",
                        month: "short",
                        day: "numeric",
                      })}
                    </time>
                  </div>
                ))
              ) : (
                <div className="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                  No recent system activity is available.
                </div>
              )}
            </div>
          </section>
        </>
      )}
    </div>
  );
}