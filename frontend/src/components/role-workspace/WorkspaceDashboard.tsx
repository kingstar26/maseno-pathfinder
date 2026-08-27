import { useEffect, useState } from "react";
import {
  Activity,
  BriefcaseBusiness,
  CalendarDays,
  CheckCircle2,
  ClipboardCheck,
  ClipboardList,
  Clock,
  FileCheck2,
  GraduationCap,
  Sparkles,
  TrendingUp,
  UsersRound,
  XCircle,
} from "lucide-react";
import { PageHeader } from "@/components/PageHeader";
import { Skeleton } from "@/components/ui/skeleton";
import { apiErrorMessage, getManagementDashboard, type ManagementDashboardData } from "@/lib/api";

export function WorkspaceDashboard({ role }: { role: "management" | "administrator" }) {
  if (role === "administrator") return null;
  return <ManagementDashboard />;
}

function ManagementDashboard() {
  const [data, setData] = useState<ManagementDashboardData | null>(null);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    getManagementDashboard()
      .then((result) => {
        if (active) setData(result);
      })
      .catch((err: unknown) => {
        if (active) {
          setError(
            apiErrorMessage(
              err,
              "Management dashboard loading failed because the server returned an unexpected response. Please try again later."
            )
          );
        }
      });
    return () => {
      active = false;
    };
  }, []);

  const totalEmpRecords = data?.employment.total_records || 0;
  const totalEmployed = data?.employment.employed_graduates || 0;
  const employmentRatePct =
    totalEmpRecords > 0 ? Math.round((totalEmployed / totalEmpRecords) * 100) : 0;

  const totalStatusCount =
    data?.employment.by_status.reduce((acc, curr) => acc + curr.count, 0) || 0;

  const getStatusStyle = (name: string) => {
    const lower = name.toLowerCase();
    if (lower.includes("employed") && !lower.includes("self") && !lower.includes("unemployed")) {
      return {
        badge: "bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20",
        bar: "bg-emerald-500",
        border: "hover:border-emerald-500/30",
      };
    }
    if (lower.includes("self")) {
      return {
        badge: "bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20",
        bar: "bg-sky-500",
        border: "hover:border-sky-500/30",
      };
    }
    if (lower.includes("further")) {
      return {
        badge: "bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20",
        bar: "bg-purple-500",
        border: "hover:border-purple-500/30",
      };
    }
    if (lower.includes("unemployed")) {
      return {
        badge: "bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20",
        bar: "bg-amber-500",
        border: "hover:border-amber-500/30",
      };
    }
    return {
      badge: "bg-primary/10 text-primary border-primary/20",
      bar: "bg-primary",
      border: "hover:border-primary/30",
    };
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Management dashboard"
        subtitle="Live institutional outcomes, survey, recommendation and event statistics."
      />

      {error && (
        <div className="rounded-2xl border border-destructive/30 bg-destructive/10 p-5 text-sm text-destructive shadow-sm">
          <p className="font-semibold">Dashboard Notice</p>
          <p className="mt-1">{error}</p>
        </div>
      )}

      {/* Loading Skeletons */}
      {!data && !error && (
        <div className="space-y-6">
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            {Array.from({ length: 5 }, (_, index) => (
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
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              {Array.from({ length: 4 }, (_, i) => (
                <Skeleton key={i} className="h-24 w-full rounded-xl" />
              ))}
            </div>
          </div>
        </div>
      )}

      {data && (
        <>
          {/* Top 5 Metrics Grid */}
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            {/* Card 1: Total Graduates */}
            <div className="group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl">
              <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-600" />
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Total graduates
                  </p>
                  <p className="mt-2 text-3xl font-extrabold tracking-tight text-foreground">
                    {data.total_graduates.toLocaleString()}
                  </p>
                </div>
                <div className="rounded-xl bg-blue-500/10 p-2.5 text-blue-600 dark:text-blue-400 transition-transform duration-300 group-hover:scale-110 shadow-sm">
                  <GraduationCap className="h-6 w-6" />
                </div>
              </div>
              <div className="mt-4 flex items-center gap-1.5 text-xs text-muted-foreground">
                <Sparkles className="h-3.5 w-3.5 text-blue-500" />
                <span>{totalEmpRecords} employment records</span>
              </div>
            </div>

            {/* Card 2: Employed Graduates */}
            <div className="group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-emerald-500/40 hover:shadow-xl">
              <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-600" />
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Employed graduates
                  </p>
                  <p className="mt-2 text-3xl font-extrabold tracking-tight text-foreground">
                    {data.employment.employed_graduates.toLocaleString()}
                  </p>
                </div>
                <div className="rounded-xl bg-emerald-500/10 p-2.5 text-emerald-600 dark:text-emerald-400 transition-transform duration-300 group-hover:scale-110 shadow-sm">
                  <BriefcaseBusiness className="h-6 w-6" />
                </div>
              </div>
              <div className="mt-4 flex items-center justify-between text-xs">
                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 font-medium text-emerald-600 dark:text-emerald-400">
                  {employmentRatePct}% rate
                </span>
                <span className="text-muted-foreground">Employed & Self-Employed</span>
              </div>
            </div>

            {/* Card 3: Survey Completion */}
            <div className="group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-purple-500/40 hover:shadow-xl">
              <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-purple-500 to-pink-600" />
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Survey completion
                  </p>
                  <p className="mt-2 text-3xl font-extrabold tracking-tight text-foreground">
                    {data.surveys.completion_rate}%
                  </p>
                </div>
                <div className="rounded-xl bg-purple-500/10 p-2.5 text-purple-600 dark:text-purple-400 transition-transform duration-300 group-hover:scale-110 shadow-sm">
                  <ClipboardList className="h-6 w-6" />
                </div>
              </div>
              <div className="mt-3 space-y-1">
                <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                  <div
                    className="h-full rounded-full bg-gradient-to-r from-purple-500 to-pink-500 transition-all duration-500"
                    style={{ width: `${Math.min(data.surveys.completion_rate, 100)}%` }}
                  />
                </div>
                <p className="text-xs text-muted-foreground">
                  {data.surveys.completed_responses} / {data.surveys.total_invitations || 1} completed
                </p>
              </div>
            </div>

            {/* Card 4: Recommendation Requests */}
            <div className="group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40 hover:shadow-xl">
              <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 to-orange-600" />
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Recommendations
                  </p>
                  <p className="mt-2 text-3xl font-extrabold tracking-tight text-foreground">
                    {data.recommendations.total_requests.toLocaleString()}
                  </p>
                </div>
                <div className="rounded-xl bg-amber-500/10 p-2.5 text-amber-600 dark:text-amber-400 transition-transform duration-300 group-hover:scale-110 shadow-sm">
                  <UsersRound className="h-6 w-6" />
                </div>
              </div>
              <div className="mt-4 flex items-center gap-1.5 text-xs">
                <span className="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2 py-0.5 font-medium text-amber-600 dark:text-amber-400">
                  <Clock className="h-3 w-3" />
                  {data.recommendations.pending_requests} pending
                </span>
                <span className="text-muted-foreground"> Total requests</span>
              </div>
            </div>

            {/* Card 5: Upcoming Events */}
            <div className="group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-sky-500/40 hover:shadow-xl sm:col-span-2 lg:col-span-1">
              <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-sky-500 to-blue-600" />
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Upcoming events
                  </p>
                  <p className="mt-2 text-3xl font-extrabold tracking-tight text-foreground">
                    {data.upcoming_events_count.toLocaleString()}
                  </p>
                </div>
                <div className="rounded-xl bg-sky-500/10 p-2.5 text-sky-600 dark:text-sky-400 transition-transform duration-300 group-hover:scale-110 shadow-sm">
                  <CalendarDays className="h-6 w-6" />
                </div>
              </div>
              <div className="mt-4 flex items-center gap-1.5 text-xs text-muted-foreground">
                <span className="h-2 w-2 rounded-full bg-sky-500 animate-pulse" />
                <span>Active institution schedule</span>
              </div>
            </div>
          </div>

          {/* Detailed Section 1: Employment Status Distribution */}
          <section className="rounded-2xl border border-border/80 bg-card p-6 shadow-[var(--shadow-card)]">
            <div className="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
              <div>
                <div className="flex items-center gap-2">
                  <div className="rounded-lg bg-primary/10 p-2 text-primary">
                    <TrendingUp className="h-5 w-5" />
                  </div>
                  <h2 className="text-lg font-bold tracking-tight text-foreground">
                    Employment status distribution
                  </h2>
                </div>
                <p className="mt-1 text-xs text-muted-foreground">
                  Breakdown of graduate current work statuses across registered profiles
                </p>
              </div>
              {totalStatusCount > 0 && (
                <span className="self-start rounded-full bg-muted px-3 py-1 text-xs font-semibold text-muted-foreground sm:self-center">
                  Total: {totalStatusCount} records
                </span>
              )}
            </div>

            <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              {data.employment.by_status.length ? (
                data.employment.by_status.map((item) => {
                  const pct =
                    totalStatusCount > 0
                      ? Math.round((item.count / totalStatusCount) * 100)
                      : 0;
                  const style = getStatusStyle(item.name);

                  return (
                    <div
                      key={item.name}
                      className={`group relative overflow-hidden rounded-xl border border-border/60 bg-muted/30 p-4 transition-all duration-300 hover:bg-muted/70 ${style.border}`}
                    >
                      <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-muted-foreground">
                          {item.name}
                        </span>
                        <span
                          className={`rounded-full border px-2.5 py-0.5 text-xs font-bold ${style.badge}`}
                        >
                          {pct}%
                        </span>
                      </div>
                      <p className="mt-2 text-2xl font-extrabold tracking-tight text-foreground">
                        {item.count.toLocaleString()}
                      </p>
                      <div className="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                        <div
                          className={`h-full rounded-full transition-all duration-500 ${style.bar}`}
                          style={{ width: `${Math.min(pct, 100)}%` }}
                        />
                      </div>
                    </div>
                  );
                })
              ) : (
                <div className="col-span-full rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                  No employment records have been submitted yet.
                </div>
              )}
            </div>
          </section>

          {/* Detailed Section 2: Survey & Recommendation Activity */}
          <section className="rounded-2xl border border-border/80 bg-card p-6 shadow-[var(--shadow-card)]">
            <div className="flex items-center gap-2">
              <div className="rounded-lg bg-primary/10 p-2 text-primary">
                <Activity className="h-5 w-5" />
              </div>
              <div>
                <h2 className="text-lg font-bold tracking-tight text-foreground">
                  Survey and recommendation activity
                </h2>
                <p className="text-xs text-muted-foreground">
                  Operational tracking for graduate surveys and academic recommendation processing
                </p>
              </div>
            </div>

            <div className="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
              {/* Tracer Surveys Block */}
              <div className="rounded-xl border border-border/60 bg-muted/20 p-5 transition-all duration-200 hover:border-primary/30">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <ClipboardCheck className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                    <h3 className="font-semibold text-sm">Tracer Surveys</h3>
                  </div>
                  <span className="rounded-full bg-purple-500/10 px-2.5 py-0.5 text-xs font-semibold text-purple-600 dark:text-purple-400">
                    {data.surveys.total_surveys} Active
                  </span>
                </div>
                <div className="mt-4 flex items-baseline justify-between">
                  <span className="text-3xl font-bold tracking-tight">
                    {data.surveys.completed_responses}
                  </span>
                  <span className="text-xs text-muted-foreground">
                    of {data.surveys.total_invitations} invitations
                  </span>
                </div>
                <div className="mt-3 space-y-1">
                  <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div
                      className="h-full rounded-full bg-gradient-to-r from-purple-500 to-pink-500 transition-all duration-500"
                      style={{ width: `${Math.min(data.surveys.completion_rate, 100)}%` }}
                    />
                  </div>
                  <div className="flex justify-between text-xs text-muted-foreground pt-1">
                    <span>Completion Rate</span>
                    <span className="font-semibold text-foreground">
                      {data.surveys.completion_rate}%
                    </span>
                  </div>
                </div>
              </div>

              {/* Recommendation Requests Block */}
              <div className="rounded-xl border border-border/60 bg-muted/20 p-5 transition-all duration-200 hover:border-primary/30">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <FileCheck2 className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    <h3 className="font-semibold text-sm">Recommendation Requests</h3>
                  </div>
                  <span className="rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-semibold text-blue-600 dark:text-blue-400">
                    {data.recommendations.total_requests} Total
                  </span>
                </div>
                <div className="mt-4 grid grid-cols-3 gap-2 text-center">
                  <div className="rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-2.5">
                    <CheckCircle2 className="mx-auto h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    <p className="mt-1 text-lg font-bold text-emerald-600 dark:text-emerald-400">
                      {data.recommendations.approved_requests}
                    </p>
                    <p className="text-[10px] font-medium text-emerald-700/80 dark:text-emerald-300/80">
                      Approved
                    </p>
                  </div>
                  <div className="rounded-lg border border-amber-500/20 bg-amber-500/10 p-2.5">
                    <Clock className="mx-auto h-4 w-4 text-amber-600 dark:text-amber-400" />
                    <p className="mt-1 text-lg font-bold text-amber-600 dark:text-amber-400">
                      {data.recommendations.pending_requests}
                    </p>
                    <p className="text-[10px] font-medium text-amber-700/80 dark:text-amber-300/80">
                      Pending
                    </p>
                  </div>
                  <div className="rounded-lg border border-rose-500/20 bg-rose-500/10 p-2.5">
                    <XCircle className="mx-auto h-4 w-4 text-rose-600 dark:text-rose-400" />
                    <p className="mt-1 text-lg font-bold text-rose-600 dark:text-rose-400">
                      {data.recommendations.rejected_requests}
                    </p>
                    <p className="text-[10px] font-medium text-rose-700/80 dark:text-rose-300/80">
                      Rejected
                    </p>
                  </div>
                </div>
              </div>

              {/* Event Schedule Block */}
              <div className="rounded-xl border border-border/60 bg-muted/20 p-5 transition-all duration-200 hover:border-primary/30 md:col-span-2 lg:col-span-1">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <CalendarDays className="h-5 w-5 text-sky-600 dark:text-sky-400" />
                    <h3 className="font-semibold text-sm">Events & Workshops</h3>
                  </div>
                  <span className="inline-flex items-center gap-1 rounded-full bg-sky-500/10 px-2.5 py-0.5 text-xs font-semibold text-sky-600 dark:text-sky-400">
                    <span className="h-1.5 w-1.5 rounded-full bg-sky-500 animate-pulse" />
                    Live
                  </span>
                </div>
                <div className="mt-4 flex items-baseline justify-between">
                  <span className="text-3xl font-bold tracking-tight">
                    {data.upcoming_events_count}
                  </span>
                  <span className="text-xs font-medium text-sky-600 dark:text-sky-400">
                    Upcoming scheduled
                  </span>
                </div>
                <p className="mt-3 text-xs text-muted-foreground leading-relaxed">
                  Institutional career fairs, tracer workshops, and networking events currently published for graduate participation.
                </p>
              </div>
            </div>
          </section>
        </>
      )}
    </div>
  );
}