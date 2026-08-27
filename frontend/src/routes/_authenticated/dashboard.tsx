import { useEffect, useState } from "react";
import { createFileRoute, Link } from "@tanstack/react-router";
import {
  Briefcase,
  ClipboardList,
  FileSignature,
  UserRound,
  ArrowRight,
  CalendarDays,
  Bell,
  Loader2,
} from "lucide-react";
import { PageHeader } from "@/components/PageHeader";
import { getGraduateDashboard } from "@/lib/api";
import type { GraduateDashboardData } from "@/types";

export const Route = createFileRoute("/_authenticated/dashboard")({ component: DashboardPage });

function DashboardPage() {
  const [data, setData] = useState<GraduateDashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;
    getGraduateDashboard()
      .then((res) => {
        if (mounted) {
          setData(res);
          setLoading(false);
        }
      })
      .catch((err) => {
        if (mounted) {
          setError(err?.response?.data?.message || err?.message || "Failed to load dashboard data.");
          setLoading(false);
        }
      });
    return () => {
      mounted = false;
    };
  }, []);

  const profileDetail = data?.profile
    ? `${data.profile.first_name} ${data.profile.last_name} (${data.profile.profile_completion ?? data.profile.profile_completion_percentage ?? 0}% completed)`
    : "No profile data available.";

  const employmentDetail = data?.employment?.company_name
    ? `${data.employment.job_title || "Employee"} at ${data.employment.company_name}`
    : data?.employment?.employment_status
      ? `Status: ${data.employment.employment_status}`
      : "No employment records found.";

  const surveyDetail = data?.surveys
    ? `${data.surveys.completed_surveys ?? 0} completed, ${data.surveys.pending_surveys ?? 0} pending`
    : "No survey status available.";

  const recDetail = data?.recommendations
    ? `${data.recommendations.total_recommendation_requests ?? data.recommendations.total_requests ?? 0} total requests (${data.recommendations.pending_requests ?? data.recommendations.pending_requests_count ?? 0} pending)`
    : "No letter requests found.";

  const eventsDetail = data?.events?.length
    ? `${data.events.length} upcoming registered event(s)`
    : "No upcoming registered events found.";

  const modules = [
    {
      title: "Graduate profile",
      detail: profileDetail,
      to: "/profile" as const,
      icon: UserRound,
    },
    {
      title: "Employment",
      detail: employmentDetail,
      to: "/employment" as const,
      icon: Briefcase,
    },
    {
      title: "Tracer survey",
      detail: surveyDetail,
      to: "/survey" as const,
      icon: ClipboardList,
    },
    {
      title: "Recommendation letters",
      detail: recDetail,
      to: "/recommendations" as const,
      icon: FileSignature,
    },
  ];

  return (
    <>
      <PageHeader
        title="Graduate dashboard"
        subtitle="Your MUGTS overview updates as university services return information."
      />
      {loading ? (
        <div className="flex min-h-40 items-center justify-center rounded-2xl border border-border bg-card p-8">
          <Loader2 className="h-6 w-6 animate-spin text-primary" />
          <span className="ml-2 text-sm text-muted-foreground">Loading dashboard overview…</span>
        </div>
      ) : error ? (
        <div className="rounded-2xl border border-destructive/20 bg-destructive/10 p-5 text-destructive">
          <p className="text-sm font-medium">{error}</p>
        </div>
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {modules.map(({ title, detail, to, icon: Icon }) => (
              <Link
                key={to}
                to={to}
                className="group rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)] transition-all hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-[var(--shadow-card-hover)]"
              >
                <div className="flex items-start justify-between gap-4">
                  <span className="grid h-11 w-11 place-items-center rounded-xl bg-primary/10 text-primary">
                    <Icon className="h-5 w-5" />
                  </span>
                  <ArrowRight className="h-4 w-4 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-primary" />
                </div>
                <h2 className="mt-5 font-semibold">{title}</h2>
                <p className="mt-1 text-sm text-muted-foreground">{detail}</p>
              </Link>
            ))}
          </div>
          <div className="mt-6 grid gap-4 lg:grid-cols-2">
            <OverviewCard
              icon={CalendarDays}
              title="Events"
              detail={eventsDetail}
              to="/events"
            />
            <OverviewCard
              icon={Bell}
              title="Notifications"
              detail="Check notifications for recent updates."
              to="/notifications"
            />
          </div>
        </>
      )}
    </>
  );
}

function OverviewCard({
  icon: Icon,
  title,
  detail,
  to,
}: {
  icon: typeof Bell;
  title: string;
  detail: string;
  to: "/events" | "/notifications";
}) {
  return (
    <Link
      to={to}
      className="flex min-h-40 flex-col justify-between rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)] transition-shadow hover:shadow-[var(--shadow-card-hover)]"
    >
      <div className="flex items-center gap-2 text-primary">
        <Icon className="h-5 w-5" />
        <h2 className="font-semibold text-foreground">{title}</h2>
      </div>
      <p className="text-sm text-muted-foreground">{detail}</p>
      <span className="inline-flex items-center gap-1 text-sm font-semibold text-primary">
        View module <ArrowRight className="h-4 w-4" />
      </span>
    </Link>
  );
}
