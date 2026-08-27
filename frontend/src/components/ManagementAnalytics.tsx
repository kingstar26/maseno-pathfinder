import { useEffect, useState } from "react";
import {
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  Tooltip,
  Legend,
  ResponsiveContainer,
  CartesianGrid,
} from "recharts";
import {
  Users,
  Briefcase,
  ClipboardList,
  FileSignature,
  Calendar,
  Download,
  RefreshCw,
  Award,
  TrendingUp,
  MapPin,
  CheckCircle2,
  Clock,
  XCircle,
  FileSpreadsheet,
  Building2,
  GraduationCap,
} from "lucide-react";
import toast from "react-hot-toast";

import { PageHeader } from "@/components/PageHeader";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import {
  getManagementReportsOverview,
  getManagementEmploymentReport,
  getManagementGraduateReport,
  getManagementSurveyReport,
  getManagementEventReport,
  getManagementRecommendationReport,
  exportManagementReport,
} from "@/lib/api";
import type {
  ManagementOverviewReport,
  ManagementEmploymentReport,
  ManagementGraduateReport,
  ManagementSurveyReport,
  ManagementEventReport,
  ManagementRecommendationReport,
} from "@/types";

const COLORS = ["#2563eb", "#10b981", "#f59e0b", "#ef4444", "#8b5cf6", "#06b6d4", "#ec4899", "#d4af37"];

export function ManagementAnalytics() {
  const [loading, setLoading] = useState(true);
  const [exportingType, setExportingType] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState("overview");

  const [overview, setOverview] = useState<ManagementOverviewReport | null>(null);
  const [employment, setEmployment] = useState<ManagementEmploymentReport | null>(null);
  const [graduates, setGraduates] = useState<ManagementGraduateReport | null>(null);
  const [surveys, setSurveys] = useState<ManagementSurveyReport | null>(null);
  const [events, setEvents] = useState<ManagementEventReport | null>(null);
  const [recommendations, setRecommendations] = useState<ManagementRecommendationReport | null>(null);

  const fetchAllReports = async () => {
    setLoading(true);
    try {
      const [
        overviewData,
        employmentData,
        graduateData,
        surveyData,
        eventData,
        recommendationData,
      ] = await Promise.all([
        getManagementReportsOverview(),
        getManagementEmploymentReport(),
        getManagementGraduateReport(),
        getManagementSurveyReport(),
        getManagementEventReport(),
        getManagementRecommendationReport(),
      ]);

      setOverview(overviewData);
      setEmployment(employmentData);
      setGraduates(graduateData);
      setSurveys(surveyData);
      setEvents(eventData);
      setRecommendations(recommendationData);
    } catch (err: any) {
      console.error("Failed to load management reports:", err);
      toast.error(err?.response?.data?.message || "Failed to load management analytics data.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAllReports();
  }, []);

  const handleExport = async (type: "employment" | "graduates" | "surveys" | "events") => {
    setExportingType(type);
    const toastId = toast.loading(`Generating ${type} CSV report...`);
    try {
      await exportManagementReport(type);
      toast.success(`${type.charAt(0).toUpperCase() + type.slice(1)} report downloaded successfully!`, { id: toastId });
    } catch (err: any) {
      console.error(`Export ${type} error:`, err);
      toast.error(err?.response?.data?.message || `Failed to export ${type} report.`, { id: toastId });
    } finally {
      setExportingType(null);
    }
  };

  if (loading) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Management Analytics & Reports"
          subtitle="Institutional graduate tracer statistics, employment analytics, and downloadable reports."
        />
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
              <Skeleton className="h-4 w-28" />
              <Skeleton className="mt-4 h-8 w-20" />
            </div>
          ))}
        </div>
        <div className="grid gap-6 xl:grid-cols-2">
          <div className="h-80 rounded-2xl border border-border bg-card p-6">
            <Skeleton className="h-6 w-40 mb-4" />
            <Skeleton className="h-60 w-full" />
          </div>
          <div className="h-80 rounded-2xl border border-border bg-card p-6">
            <Skeleton className="h-6 w-40 mb-4" />
            <Skeleton className="h-60 w-full" />
          </div>
        </div>
      </div>
    );
  }

  // Fallbacks for data structures
  const totalGraduatesCount = overview?.total_graduates ?? overview?.graduates ?? 0;
  const employmentRate = overview?.employment_rate ?? employment?.employment_percentage ?? 0;
  const surveyCompletionRate = overview?.survey_completion_rate ?? overview?.survey_completion_percentage ?? surveys?.completion_rate ?? 0;
  const totalRecRequests = overview?.total_recommendation_requests ?? recommendations?.total_requests ?? recommendations?.total ?? 0;
  const upcomingEventsCount = events?.upcoming_events ?? 0;

  // Employment data charts & filters
  const statusBreakdown = employment?.status_breakdown || [];
  const activeStatusBreakdown = statusBreakdown.filter((item) => item.count > 0);
  const displayStatusBreakdown = activeStatusBreakdown.length > 0 ? activeStatusBreakdown : statusBreakdown;

  const facultyEmployment = employment?.employment_by_faculty || employment?.faculty_statistics || [];
  const activeFacultyEmployment = facultyEmployment.filter((f) => f.graduates > 0);
  const displayFacultyEmployment = activeFacultyEmployment.length > 0 ? activeFacultyEmployment : facultyEmployment.slice(0, 8);

  const programmeEmployment = employment?.employment_by_programme || employment?.programme_statistics || [];
  const activeProgrammeEmployment = programmeEmployment.filter((p) => p.graduates > 0);
  const displayProgrammeEmployment = activeProgrammeEmployment.length > 0 ? activeProgrammeEmployment.slice(0, 10) : programmeEmployment.slice(0, 10);

  // Graduate distribution data charts & filters
  const facultyDistribution = graduates?.graduates_by_faculty || graduates?.by_faculty || graduates?.faculty_distribution || [];
  const activeFacultyDistribution = facultyDistribution.filter((f) => f.count > 0);
  const displayFacultyDistribution = activeFacultyDistribution.length > 0 ? activeFacultyDistribution : facultyDistribution.slice(0, 8);

  const programmeDistribution = graduates?.graduates_by_programme || graduates?.by_programme || graduates?.programme_distribution || [];
  const yearDistribution = graduates?.graduates_by_year || graduates?.by_graduation_year || graduates?.by_year || graduates?.graduation_year_distribution || [];
  const countyDistribution = graduates?.graduates_by_county || graduates?.by_county || graduates?.county_distribution || [];

  return (
    <div className="space-y-6 pb-12">
      {/* Header with Export Buttons & Refresh */}
      <PageHeader
        title="Management Analytics & Reports"
        subtitle="Institutional tracer statistics, employment outcomes, survey responses, and export options."
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <button
              onClick={() => fetchAllReports()}
              className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-3.5 py-2 text-xs font-semibold text-foreground transition hover:bg-muted"
            >
              <RefreshCw className="h-3.5 w-3.5" />
              Refresh
            </button>
            <button
              onClick={() => handleExport("employment")}
              disabled={exportingType !== null}
              className="inline-flex items-center gap-1.5 rounded-lg bg-[var(--gold)] px-3.5 py-2 text-xs font-semibold text-[oklch(0.22_0_0)] shadow-sm hover:opacity-90 disabled:opacity-50"
            >
              <Download className="h-3.5 w-3.5" />
              Export Employment CSV
            </button>
          </div>
        }
      />

      {/* CSV Export Action Bar */}
      <div className="rounded-2xl border border-border bg-card p-4 shadow-[var(--shadow-card)]">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="grid h-10 w-10 place-items-center rounded-xl bg-primary/10 text-primary">
              <FileSpreadsheet className="h-5 w-5" />
            </div>
            <div>
              <h3 className="text-sm font-semibold">Institutional CSV Reports Export</h3>
              <p className="text-xs text-muted-foreground">Download live native CSV reports directly from the database.</p>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <button
              onClick={() => handleExport("employment")}
              disabled={exportingType !== null}
              className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-muted/50 px-3 py-1.5 text-xs font-medium hover:border-primary hover:text-primary transition"
            >
              <Download className="h-3.5 w-3.5 text-blue-600" />
              Employment Report
            </button>
            <button
              onClick={() => handleExport("graduates")}
              disabled={exportingType !== null}
              className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-muted/50 px-3 py-1.5 text-xs font-medium hover:border-primary hover:text-primary transition"
            >
              <Download className="h-3.5 w-3.5 text-emerald-600" />
              Graduate Report
            </button>
            <button
              onClick={() => handleExport("surveys")}
              disabled={exportingType !== null}
              className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-muted/50 px-3 py-1.5 text-xs font-medium hover:border-primary hover:text-primary transition"
            >
              <Download className="h-3.5 w-3.5 text-amber-600" />
              Survey Report
            </button>
            <button
              onClick={() => handleExport("events")}
              disabled={exportingType !== null}
              className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-muted/50 px-3 py-1.5 text-xs font-medium hover:border-primary hover:text-primary transition"
            >
              <Download className="h-3.5 w-3.5 text-purple-600" />
              Event Report
            </button>
          </div>
        </div>
      </div>

      {/* Tabs Navigation */}
      <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
        <TabsList className="flex h-auto w-full flex-wrap gap-1 bg-muted/50 p-1.5 rounded-xl border border-border">
          <TabsTrigger value="overview" className="rounded-lg text-xs font-medium py-2 px-3">
            Overview
          </TabsTrigger>
          <TabsTrigger value="employment" className="rounded-lg text-xs font-medium py-2 px-3">
            Employment Analytics
          </TabsTrigger>
          <TabsTrigger value="graduates" className="rounded-lg text-xs font-medium py-2 px-3">
            Graduate Distribution
          </TabsTrigger>
          <TabsTrigger value="surveys" className="rounded-lg text-xs font-medium py-2 px-3">
            Surveys Analytics
          </TabsTrigger>
          <TabsTrigger value="events" className="rounded-lg text-xs font-medium py-2 px-3">
            Events Analytics
          </TabsTrigger>
          <TabsTrigger value="recommendations" className="rounded-lg text-xs font-medium py-2 px-3">
            Recommendations
          </TabsTrigger>
        </TabsList>

        {/* SECTION A: OVERVIEW */}
        <TabsContent value="overview" className="mt-6 space-y-6">
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <SummaryCard
              title="Total Graduates"
              value={totalGraduatesCount.toLocaleString()}
              subtitle="Registered tracer graduates"
              icon={Users}
              color="text-blue-600 bg-blue-50 dark:bg-blue-950/40"
            />
            <SummaryCard
              title="Employment Rate"
              value={`${employmentRate}%`}
              subtitle="Employed or self-employed"
              icon={TrendingUp}
              color="text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"
            />
            <SummaryCard
              title="Survey Completion Rate"
              value={`${surveyCompletionRate}%`}
              subtitle="Tracer survey responses"
              icon={ClipboardList}
              color="text-amber-600 bg-amber-50 dark:bg-amber-950/40"
            />
            <SummaryCard
              title="Recommendation Requests"
              value={totalRecRequests.toLocaleString()}
              subtitle={`${recommendations?.approved ?? recommendations?.approved_requests ?? 0} Approved`}
              icon={FileSignature}
              color="text-purple-600 bg-purple-50 dark:bg-purple-950/40"
            />
            <SummaryCard
              title="Upcoming Events"
              value={upcomingEventsCount.toLocaleString()}
              subtitle={`${events?.total_events ?? 0} Total events`}
              icon={Calendar}
              color="text-cyan-600 bg-cyan-50 dark:bg-cyan-950/40"
            />
          </div>

          <div className="grid gap-6 xl:grid-cols-2">
            {/* Employment Status Distribution Chart */}
            <div className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
              <div className="mb-4 flex items-center justify-between">
                <div>
                  <h3 className="font-semibold text-base">Employment Status Distribution</h3>
                  <p className="text-xs text-muted-foreground">Breakdown of graduate current work statuses</p>
                </div>
              </div>
              {displayStatusBreakdown.length > 0 ? (
                <div className="h-72 w-full">
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie
                        data={displayStatusBreakdown}
                        dataKey="count"
                        nameKey="status"
                        cx="50%"
                        cy="50%"
                        outerRadius={90}
                        innerRadius={45}
                        label={({ name, percent, payload }: any) =>
                          `${payload?.status || name}: ${payload?.percentage ?? Math.round((percent || 0) * 100)}%`
                        }
                      >
                        {displayStatusBreakdown.map((_, index) => (
                          <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                        ))}
                      </Pie>
                      <Tooltip formatter={(val: number) => [`${val} graduates`, "Count"]} />
                      <Legend />
                    </PieChart>
                  </ResponsiveContainer>
                </div>
              ) : (
                <div className="flex h-64 flex-col items-center justify-center rounded-xl border border-dashed border-border text-center">
                  <p className="text-sm font-medium">No employment status records found.</p>
                </div>
              )}
            </div>

            {/* Employment by Faculty Chart */}
            <div className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
              <div className="mb-4 flex items-center justify-between">
                <div>
                  <h3 className="font-semibold text-base">Employment Rate by Faculty</h3>
                  <p className="text-xs text-muted-foreground">Comparative employment percentages across schools</p>
                </div>
              </div>
              {displayFacultyEmployment.length > 0 ? (
                <div className="h-72 w-full">
                  <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={displayFacultyEmployment} margin={{ top: 10, right: 10, left: -20, bottom: 40 }}>
                      <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                      <XAxis
                        dataKey="faculty"
                        angle={-25}
                        textAnchor="end"
                        interval={0}
                        tick={{ fontSize: 10 }}
                        tickFormatter={(val: string) => (val.length > 25 ? `${val.substring(0, 22)}...` : val)}
                      />
                      <YAxis domain={[0, 100]} tick={{ fontSize: 11 }} />
                      <Tooltip formatter={(val: number) => [`${val}%`, "Employment Rate"]} />
                      <Bar dataKey="rate" fill="#2563eb" radius={[4, 4, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              ) : (
                <div className="flex h-64 flex-col items-center justify-center rounded-xl border border-dashed border-border text-center">
                  <p className="text-sm font-medium">No faculty employment records found.</p>
                </div>
              )}
            </div>
          </div>
        </TabsContent>

        {/* SECTION B: EMPLOYMENT ANALYTICS */}
        <TabsContent value="employment" className="mt-6 space-y-6">
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <SummaryCard
              title="Employment Rate"
              value={`${employmentRate}%`}
              subtitle="Target: 80%+"
              icon={TrendingUp}
              color="text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"
            />
            <SummaryCard
              title="Employed"
              value={(employment?.employed_count ?? employment?.currently_employed_graduates ?? overview?.currently_employed_graduates ?? 0).toLocaleString()}
              subtitle="Formal sector employment"
              icon={Briefcase}
              color="text-blue-600 bg-blue-50 dark:bg-blue-950/40"
            />
            <SummaryCard
              title="Self-Employed"
              value={(employment?.self_employed_count ?? employment?.self_employed_graduates ?? overview?.self_employed_graduates ?? 0).toLocaleString()}
              subtitle="Business founders & freelancers"
              icon={Building2}
              color="text-purple-600 bg-purple-50 dark:bg-purple-950/40"
            />
            <SummaryCard
              title="Further Studies"
              value={(employment?.further_studies_count ?? employment?.further_studies_graduates ?? overview?.further_studies_graduates ?? 0).toLocaleString()}
              subtitle="Postgraduate & continuing ed"
              icon={GraduationCap}
              color="text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40"
            />
            <SummaryCard
              title="Unemployed"
              value={(employment?.unemployed_count ?? employment?.unemployed_graduates ?? overview?.unemployed_graduates ?? 0).toLocaleString()}
              subtitle="Actively seeking opportunities"
              icon={Users}
              color="text-amber-600 bg-amber-50 dark:bg-amber-950/40"
            />
          </div>

          <div className="grid gap-6 xl:grid-cols-2">
            {/* Employment Status Distribution */}
            <div className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
              <h3 className="font-semibold text-base mb-1">Employment Status Breakdown</h3>
              <p className="text-xs text-muted-foreground mb-4">Count and percentages per employment code</p>
              <div className="space-y-3">
                {statusBreakdown.map((item, idx) => (
                  <div key={item.status} className="space-y-1">
                    <div className="flex justify-between text-xs font-medium">
                      <span>{item.status}</span>
                      <span>
                        {item.count} ({item.percentage ?? 0}%)
                      </span>
                    </div>
                    <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                      <div
                        className="h-full rounded-full transition-all"
                        style={{
                          width: `${item.percentage ?? 0}%`,
                          backgroundColor: COLORS[idx % COLORS.length],
                        }}
                      />
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Employment by Programme */}
            <div className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
              <h3 className="font-semibold text-base mb-1">Employment Rate by Programme</h3>
              <p className="text-xs text-muted-foreground mb-4">Degree programmes sorted by employment rate</p>
              {displayProgrammeEmployment.length > 0 ? (
                <div className="h-72 w-full">
                  <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={displayProgrammeEmployment} margin={{ top: 10, right: 10, left: -20, bottom: 40 }}>
                      <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                      <XAxis
                        dataKey="programme"
                        angle={-30}
                        textAnchor="end"
                        interval={0}
                        tick={{ fontSize: 9 }}
                        tickFormatter={(val: string) => (val.length > 25 ? `${val.substring(0, 22)}...` : val)}
                      />
                      <YAxis domain={[0, 100]} tick={{ fontSize: 11 }} />
                      <Tooltip formatter={(val: number) => [`${val}%`, "Employment Rate"]} />
                      <Bar dataKey="rate" fill="#10b981" radius={[4, 4, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              ) : (
                <div className="flex h-64 flex-col items-center justify-center rounded-xl border border-dashed border-border text-center">
                  <p className="text-sm font-medium">No programme statistics available.</p>
                </div>
              )}
            </div>
          </div>
        </TabsContent>

        {/* SECTION C: GRADUATE DISTRIBUTION */}
        <TabsContent value="graduates" className="mt-6 space-y-6">
          <div className="grid gap-6 xl:grid-cols-2">
            {/* Graduates by Faculty */}
            <div className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
              <h3 className="font-semibold text-base mb-1">Graduates by Faculty</h3>
              <p className="text-xs text-muted-foreground mb-4">Total graduate headcount across university schools</p>
              {displayFacultyDistribution.length > 0 ? (
                <div className="h-72 w-full">
                  <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={displayFacultyDistribution} margin={{ top: 10, right: 10, left: -20, bottom: 40 }}>
                      <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                      <XAxis
                        dataKey="faculty"
                        angle={-25}
                        textAnchor="end"
                        interval={0}
                        tick={{ fontSize: 10 }}
                        tickFormatter={(val: string) => (val.length > 25 ? `${val.substring(0, 22)}...` : val)}
                      />
                      <YAxis tick={{ fontSize: 11 }} />
                      <Tooltip formatter={(val: number) => [`${val} graduates`, "Headcount"]} />
                      <Bar dataKey="count" fill="#8b5cf6" radius={[4, 4, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              ) : (
                <div className="flex h-64 flex-col items-center justify-center rounded-xl border border-dashed border-border text-center">
                  <p className="text-sm font-medium">No faculty distribution records.</p>
                </div>
              )}
            </div>

            {/* Graduates by Graduation Year */}
            <div className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
              <h3 className="font-semibold text-base mb-1">Graduates by Graduation Year</h3>
              <p className="text-xs text-muted-foreground mb-4">Yearly cohort volume trends</p>
              {yearDistribution.length > 0 ? (
                <div className="h-72 w-full">
                  <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={yearDistribution}>
                      <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                      <XAxis dataKey="year" tick={{ fontSize: 11 }} />
                      <YAxis tick={{ fontSize: 11 }} />
                      <Tooltip formatter={(val: number) => [`${val} graduates`, "Graduates"]} />
                      <Bar dataKey="count" fill="#06b6d4" radius={[4, 4, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              ) : (
                <div className="flex h-64 flex-col items-center justify-center rounded-xl border border-dashed border-border text-center">
                  <p className="text-sm font-medium">No graduation year records.</p>
                </div>
              )}
            </div>
          </div>

          {/* County Geographic Distribution */}
          <div className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
            <div className="flex items-center gap-2 mb-1">
              <MapPin className="h-5 w-5 text-red-500" />
              <h3 className="font-semibold text-base">Graduates by Geographic County</h3>
            </div>
            <p className="text-xs text-muted-foreground mb-4">Location distribution of tracer graduates across Kenya counties</p>
            {countyDistribution.length > 0 ? (
              <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                {countyDistribution.map((cItem) => (
                  <div key={cItem.county} className="flex items-center justify-between rounded-xl border border-border bg-muted/30 px-3.5 py-2.5">
                    <span className="text-xs font-semibold">{cItem.county || "Unspecified"}</span>
                    <span className="rounded-md bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary">
                      {cItem.count}
                    </span>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">No county distribution data available.</p>
            )}
          </div>
        </TabsContent>

        {/* SECTION D: SURVEY ANALYTICS */}
        <TabsContent value="surveys" className="mt-6 space-y-6">
          <div className="grid gap-4 sm:grid-cols-4">
            <SummaryCard
              title="Surveys Created"
              value={(surveys?.total_surveys ?? surveys?.available_surveys ?? 0).toLocaleString()}
              subtitle="Tracer instruments"
              icon={ClipboardList}
              color="text-blue-600 bg-blue-50 dark:bg-blue-950/40"
            />
            <SummaryCard
              title="Invitations Sent"
              value={(surveys?.total_invitations ?? surveys?.invitations_sent ?? 0).toLocaleString()}
              subtitle="Sent to graduates"
              icon={Users}
              color="text-purple-600 bg-purple-50 dark:bg-purple-950/40"
            />
            <SummaryCard
              title="Completed Responses"
              value={(surveys?.completed_responses ?? surveys?.total_completed ?? 0).toLocaleString()}
              subtitle="Submitted survey responses"
              icon={CheckCircle2}
              color="text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"
            />
            <SummaryCard
              title="Completion Percentage"
              value={`${surveys?.completion_percentage ?? surveys?.completion_rate ?? 0}%`}
              subtitle="Response rate"
              icon={TrendingUp}
              color="text-amber-600 bg-amber-50 dark:bg-amber-950/40"
            />
          </div>

          {/* Survey List & Question Breakdown */}
          <div className="space-y-6">
            {(surveys?.surveys || []).map((sItem) => (
              <div key={sItem.survey_id} className="rounded-2xl border border-border bg-card p-6 shadow-[var(--shadow-card)] space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-4 border-b border-border pb-4">
                  <div>
                    <h3 className="text-lg font-semibold">{sItem.title || sItem.name}</h3>
                    <p className="text-xs text-muted-foreground mt-0.5">
                      Invited: {sItem.total_invited} | Completed: {sItem.total_completed} | Completion Rate: {sItem.completion_rate}%
                    </p>
                  </div>
                  <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-600 dark:bg-emerald-950/40">
                    {sItem.completion_rate}% Completed
                  </span>
                </div>

                {/* Questions Breakdown */}
                {sItem.questions && sItem.questions.length > 0 ? (
                  <div className="space-y-4">
                    <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Question Response Breakdowns</h4>
                    <div className="grid gap-4 md:grid-cols-2">
                      {sItem.questions.map((q) => (
                        <div key={q.survey_question_id} className="rounded-xl border border-border bg-muted/20 p-4 space-y-2">
                          {q.section_title && (
                            <span className="inline-block rounded-md bg-primary/10 px-2 py-0.5 text-[10px] font-semibold text-primary">
                              {q.section_title}
                            </span>
                          )}
                          <p className="text-xs font-semibold text-foreground">{q.prompt}</p>
                          <p className="text-[11px] text-muted-foreground">Total Responses: {q.total_responses}</p>

                          {q.option_breakdown && q.option_breakdown.length > 0 ? (
                            <div className="space-y-2 mt-2">
                              {q.option_breakdown.map((opt) => (
                                <div key={opt.survey_question_option_id} className="text-xs space-y-1">
                                  <div className="flex justify-between font-medium text-muted-foreground">
                                    <span>{opt.label}</span>
                                    <span>{opt.count} ({opt.percentage}%)</span>
                                  </div>
                                  <div className="h-1.5 w-full rounded-full bg-muted overflow-hidden">
                                    <div
                                      className="h-full rounded-full bg-primary"
                                      style={{ width: `${opt.percentage}%` }}
                                    />
                                  </div>
                                </div>
                              ))}
                            </div>
                          ) : (
                            <p className="text-[11px] italic text-muted-foreground">Text / Open response question</p>
                          )}
                        </div>
                      ))}
                    </div>
                  </div>
                ) : (
                  <p className="text-xs text-muted-foreground italic">No detailed question responses recorded yet.</p>
                )}
              </div>
            ))}
          </div>
        </TabsContent>

        {/* SECTION E: EVENT ANALYTICS */}
        <TabsContent value="events" className="mt-6 space-y-6">
          <div className="grid gap-4 sm:grid-cols-4">
            <SummaryCard
              title="Total Events"
              value={(events?.total_events ?? 0).toLocaleString()}
              subtitle="All institutional events"
              icon={Calendar}
              color="text-blue-600 bg-blue-50 dark:bg-blue-950/40"
            />
            <SummaryCard
              title="Published Events"
              value={(events?.published_events ?? 0).toLocaleString()}
              subtitle="Visible to graduates"
              icon={CheckCircle2}
              color="text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"
            />
            <SummaryCard
              title="Total Registrations"
              value={(
                typeof events?.registration_statistics === "number"
                  ? events.registration_statistics
                  : events?.registration_counts ?? events?.total_registrations ?? 0
              ).toLocaleString()}
              subtitle="Graduate registrations"
              icon={Users}
              color="text-purple-600 bg-purple-50 dark:bg-purple-950/40"
            />
            <SummaryCard
              title="Attendance Rate"
              value={`${
                typeof events?.attendance_statistics === "object"
                  ? events.attendance_statistics.overall_attendance_rate ?? 0
                  : events?.overall_attendance_rate ?? 0
              }%`}
              subtitle="Attended vs Registered"
              icon={Award}
              color="text-amber-600 bg-amber-50 dark:bg-amber-950/40"
            />
          </div>

          {/* Events List Table */}
          <div className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
            <h3 className="font-semibold text-base mb-4">Event Performance Breakdown</h3>
            {(events?.events || []).length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="border-b border-border bg-muted/40 font-semibold text-muted-foreground uppercase tracking-wider">
                    <tr>
                      <th className="px-4 py-3">Event Title</th>
                      <th className="px-4 py-3">Venue</th>
                      <th className="px-4 py-3">Start Date</th>
                      <th className="px-4 py-3 text-center">Registrations</th>
                      <th className="px-4 py-3 text-center">Attended</th>
                      <th className="px-4 py-3 text-right">Attendance Rate</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {events?.events?.map((ev) => (
                      <tr key={ev.event_id} className="hover:bg-muted/20">
                        <td className="px-4 py-3 font-medium text-foreground">{ev.title}</td>
                        <td className="px-4 py-3 text-muted-foreground">{ev.venue || "TBA"}</td>
                        <td className="px-4 py-3 text-muted-foreground">{new Date(ev.start_datetime).toLocaleDateString()}</td>
                        <td className="px-4 py-3 text-center font-semibold">{ev.registrations_count ?? ev.registration_count ?? 0}</td>
                        <td className="px-4 py-3 text-center font-semibold text-emerald-600">{ev.attended_count ?? 0}</td>
                        <td className="px-4 py-3 text-right font-bold text-primary">{ev.attendance_rate ?? 0}%</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <p className="text-xs text-muted-foreground">No event performance data available.</p>
            )}
          </div>
        </TabsContent>

        {/* SECTION F: RECOMMENDATION ANALYTICS */}
        <TabsContent value="recommendations" className="mt-6 space-y-6">
          <div className="grid gap-4 sm:grid-cols-5">
            <SummaryCard
              title="Total Requests"
              value={(recommendations?.total_requests ?? recommendations?.total ?? 0).toLocaleString()}
              subtitle="Submitted requests"
              icon={FileSignature}
              color="text-blue-600 bg-blue-50 dark:bg-blue-950/40"
            />
            <SummaryCard
              title="Pending"
              value={(recommendations?.pending_requests ?? recommendations?.pending ?? 0).toLocaleString()}
              subtitle="Awaiting review"
              icon={Clock}
              color="text-amber-600 bg-amber-50 dark:bg-amber-950/40"
            />
            <SummaryCard
              title="Approved"
              value={(recommendations?.approved_requests ?? recommendations?.approved ?? 0).toLocaleString()}
              subtitle="Issued recommendation letters"
              icon={CheckCircle2}
              color="text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40"
            />
            <SummaryCard
              title="Rejected"
              value={(recommendations?.rejected_requests ?? recommendations?.rejected ?? 0).toLocaleString()}
              subtitle="Declined requests"
              icon={XCircle}
              color="text-red-600 bg-red-50 dark:bg-red-950/40"
            />
            <SummaryCard
              title="Avg Processing Time"
              value={String(recommendations?.average_processing_time ?? "N/A")}
              subtitle="Request to resolution"
              icon={TrendingUp}
              color="text-purple-600 bg-purple-50 dark:bg-purple-950/40"
            />
          </div>

          <div className="rounded-2xl border border-border bg-card p-6 shadow-[var(--shadow-card)]">
            <h3 className="font-semibold text-base mb-2">Recommendation Request Status Overview</h3>
            <p className="text-xs text-muted-foreground mb-6">Efficiency metrics for university recommendation processing</p>

            <div className="grid gap-4 sm:grid-cols-3">
              <div className="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Approved & Issued</span>
                  <CheckCircle2 className="h-5 w-5 text-emerald-600" />
                </div>
                <p className="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">
                  {recommendations?.approved_requests ?? recommendations?.approved ?? 0}
                </p>
                <p className="mt-1 text-xs text-emerald-600/80">Letters generated & dispatched</p>
              </div>

              <div className="rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-semibold text-amber-700 dark:text-amber-300">Pending Review</span>
                  <Clock className="h-5 w-5 text-amber-600" />
                </div>
                <p className="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-300">
                  {recommendations?.pending_requests ?? recommendations?.pending ?? 0}
                </p>
                <p className="mt-1 text-xs text-amber-600/80">In management processing queue</p>
              </div>

              <div className="rounded-xl border border-purple-200 bg-purple-50/50 p-4 dark:border-purple-900/50 dark:bg-purple-950/20">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-semibold text-purple-700 dark:text-purple-300">Turnaround Speed</span>
                  <TrendingUp className="h-5 w-5 text-purple-600" />
                </div>
                <p className="mt-2 text-2xl font-bold text-purple-700 dark:text-purple-300">
                  {recommendations?.average_processing_time || "0 hours"}
                </p>
                <p className="mt-1 text-xs text-purple-600/80">Average resolution duration</p>
              </div>
            </div>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}

function SummaryCard({
  title,
  value,
  subtitle,
  icon: Icon,
  color,
}: {
  title: string;
  value: string | number;
  subtitle: string;
  icon: React.ComponentType<{ className?: string }>;
  color: string;
}) {
  return (
    <div className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)] transition hover:shadow-md">
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="text-xs font-medium text-muted-foreground">{title}</p>
          <p className="mt-2 text-2xl font-bold tracking-tight text-foreground">{value}</p>
        </div>
        <div className={`grid h-11 w-11 shrink-0 place-items-center rounded-xl ${color}`}>
          <Icon className="h-5 w-5" />
        </div>
      </div>
      <p className="mt-3 text-xs text-muted-foreground">{subtitle}</p>
    </div>
  );
}
