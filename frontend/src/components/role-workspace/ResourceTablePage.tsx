import { Download, FileDown, Plus, Search, SlidersHorizontal, Check, X, Upload, QrCode, FileText, Loader2, Megaphone, Eye, RotateCw } from "lucide-react";
import toast from "react-hot-toast";
import { PageHeader, EmptyState } from "@/components/PageHeader";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useBackendCollection } from "./use-backend-collection";
import { useEffect, useState } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogTrigger } from "@/components/ui/dialog";
import {
  getManagementAnnouncements,
  createManagementAnnouncement,
  publishManagementAnnouncement,
  ManagementAnnouncement,
  getManagementRecommendations,
  approveManagementRecommendation,
  rejectManagementRecommendation,
  uploadManagementRecommendationLetter,
  downloadRecommendationLetter,
  apiErrorMessage,
  ManagementRecommendationRequest,
  getManagementGraduateReport,
  getManagementSurveys,
} from "@/lib/api";
import type { ManagementGraduateRecord, ManagementSurveySubmission } from "@/types";

export function ResourceTablePage({ title, subtitle, columns, actions = [], allowCreate = false, exports = false }: { title: string; subtitle: string; columns: string[]; actions?: string[]; allowCreate?: boolean; exports?: boolean }) {
  // Special-case: Announcements have a working backend API; enable interactive behavior here.
  if (title === "Announcements") {
    const [records, setRecords] = useState<ManagementAnnouncement[]>([]);
    const [loading, setLoading] = useState(false);
    const [actionLoadingId, setActionLoadingId] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [createOpen, setCreateOpen] = useState(false);
    const [viewAnnouncement, setViewAnnouncement] = useState<ManagementAnnouncement | null>(null);
    const [newTitle, setNewTitle] = useState("");
    const [newMessage, setNewMessage] = useState("");
    const [newTarget, setNewTarget] = useState("graduate");
    const [searchQuery, setSearchQuery] = useState("");
    const [roleFilter, setRoleFilter] = useState("all");

    const fetch = async () => {
      setLoading(true);
      setError(null);
      try {
        const items = await getManagementAnnouncements();
        setRecords(items);
      } catch (err: any) {
        setError(err?.message || "Could not load announcements.");
      } finally {
        setLoading(false);
      }
    };

    useEffect(() => {
      void fetch();
    }, []);

    const submitCreate = async () => {
      if (!newTitle.trim() || !newMessage.trim()) {
        toast.error("Please enter a title and message content.");
        return;
      }
      setActionLoadingId(-1);
      try {
        await createManagementAnnouncement({ title: newTitle, message: newMessage, target_role: newTarget });
        toast.success("Announcement created successfully.");
        setCreateOpen(false);
        setNewTitle("");
        setNewMessage("");
        void fetch();
      } catch (err: any) {
        toast.error(err?.message || "Could not create announcement.");
      } finally {
        setActionLoadingId(null);
      }
    };

    const doPublish = async (id: number) => {
      setActionLoadingId(id);
      try {
        await publishManagementAnnouncement(id);
        toast.success("Announcement published successfully.");
        void fetch();
      } catch (err: any) {
        toast.error(err?.message || "Could not publish announcement.");
      } finally {
        setActionLoadingId(null);
      }
    };

    const filteredRecords = records.filter((r) => {
      const matchesSearch =
        `${r.title} ${r.message || r.content || ""}`
          .toLowerCase()
          .includes(searchQuery.toLowerCase());
      const targetRole = r.target_role || r.announcement_type || "graduate";
      const matchesRole = roleFilter === "all" || targetRole.toLowerCase() === roleFilter.toLowerCase();
      return matchesSearch && matchesRole;
    });

    const getAudienceBadge = (target: string) => {
      const lower = target.toLowerCase();
      if (lower.includes("admin")) {
        return "bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20";
      }
      if (lower.includes("management")) {
        return "bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20";
      }
      return "bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20";
    };

    return <>
      <PageHeader
        title={title}
        subtitle={subtitle}
        actions={
          <>
            {exports && (
              <Button variant="outline">
                <FileDown className="mr-2 h-4 w-4" />
                Export Excel
              </Button>
            )}
            {exports && (
              <Button variant="outline">
                <Download className="mr-2 h-4 w-4" />
                Export PDF
              </Button>
            )}
            {allowCreate && (
              <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogTrigger asChild>
                  <Button className="gap-2">
                    <Plus className="h-4 w-4" />
                    Create Announcement
                  </Button>
                </DialogTrigger>
                <DialogContent className="max-w-2xl">
                  <DialogHeader>
                    <DialogTitle>Create Announcement</DialogTitle>
                    <DialogDescription>
                      Compose and target an official institutional message for your audience.
                    </DialogDescription>
                  </DialogHeader>
                  <div className="mt-4 space-y-4">
                    <div>
                      <Label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Title
                      </Label>
                      <Input
                        className="mt-1.5"
                        placeholder="e.g., Annual Graduate Tracer Survey 2026"
                        value={newTitle}
                        onChange={(e) => setNewTitle(e.target.value)}
                      />
                    </div>
                    <div>
                      <Label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Target Audience
                      </Label>
                      <select
                        className="mt-1.5 w-full rounded-md border border-input bg-background px-3 py-2 text-sm font-medium"
                        value={newTarget}
                        onChange={(e) => setNewTarget(e.target.value)}
                      >
                        <option value="graduate">Graduates</option>
                        <option value="management">Management</option>
                        <option value="administrator">Administrators</option>
                      </select>
                    </div>
                    <div>
                      <Label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Message Content
                      </Label>
                      <textarea
                        className="mt-1.5 w-full rounded-md border border-input bg-background p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                        rows={5}
                        placeholder="Type the full announcement message here..."
                        value={newMessage}
                        onChange={(e) => setNewMessage(e.target.value)}
                      />
                    </div>
                  </div>
                  <DialogFooter className="mt-6">
                    <Button onClick={() => setCreateOpen(false)} variant="outline">
                      Cancel
                    </Button>
                    <Button
                      onClick={submitCreate}
                      disabled={actionLoadingId === -1}
                      className="gap-2"
                    >
                      {actionLoadingId === -1 && <Loader2 className="h-4 w-4 animate-spin" />}
                      Create Announcement
                    </Button>
                  </DialogFooter>
                </DialogContent>
              </Dialog>
            )}
          </>
        }
      />

      <section className="rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)]">
        {/* Filter and Search Bar */}
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div className="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
            <div className="relative flex-1 max-w-md">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                className="pl-9 bg-background/80"
                placeholder="Search announcements..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
            <div className="flex items-center gap-2">
              <SlidersHorizontal className="h-4 w-4 text-muted-foreground shrink-0 hidden sm:block" />
              <select
                aria-label="Filter target audience"
                className="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary/20"
                value={roleFilter}
                onChange={(e) => setRoleFilter(e.target.value)}
              >
                <option value="all">All Audiences</option>
                <option value="graduate">Graduates</option>
                <option value="management">Management</option>
                <option value="administrator">Administrators</option>
              </select>
            </div>
          </div>

          <div className="flex items-center justify-between gap-3 sm:justify-end">
            <span className="text-xs font-medium text-muted-foreground">
              Showing <strong className="text-foreground">{filteredRecords.length}</strong> of {records.length}
            </span>
            <Button
              variant="outline"
              size="sm"
              onClick={() => void fetch()}
              disabled={loading}
              className="gap-2"
            >
              <RotateCw className={`h-4 w-4 ${loading ? "animate-spin" : ""}`} />
              <span>Refresh</span>
            </Button>
          </div>
        </div>

        {/* Desktop Table View */}
        <div className="mt-5 hidden sm:block overflow-x-auto rounded-xl border border-border/80">
          <Table>
            <TableHeader className="bg-muted/40 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
              <TableRow>
                <TableHead className="whitespace-nowrap px-4 py-3.5">Announcement Title</TableHead>
                <TableHead className="whitespace-nowrap px-4 py-3.5">Audience</TableHead>
                <TableHead className="whitespace-nowrap px-4 py-3.5">Status</TableHead>
                <TableHead className="whitespace-nowrap px-4 py-3.5">Published Date</TableHead>
                <TableHead className="whitespace-nowrap px-4 py-3.5 text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody className="divide-y divide-border/60">
              {loading ? (
                <TableRow>
                  <TableCell colSpan={5} className="h-28 text-center text-muted-foreground">
                    <Loader2 className="mx-auto h-5 w-5 animate-spin mb-2" />
                    Loading announcements…
                  </TableCell>
                </TableRow>
              ) : error ? (
                <TableRow>
                  <TableCell colSpan={5} className="h-28 text-center text-destructive">
                    {error}
                  </TableCell>
                </TableRow>
              ) : filteredRecords.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} className="h-28 text-center text-muted-foreground">
                    No matching announcements found.
                  </TableCell>
                </TableRow>
              ) : (
                filteredRecords.map((r) => {
                  const target = r.announcement_type ?? r.target_role ?? "graduate";
                  const audienceStyle = getAudienceBadge(target);

                  return (
                    <TableRow key={r.announcement_id} className="transition-colors hover:bg-muted/40">
                      <TableCell className="px-4 py-3.5">
                        <div className="font-semibold text-foreground">{r.title}</div>
                        <p className="line-clamp-1 text-xs text-muted-foreground max-w-md mt-0.5">
                          {r.message || r.content || "No preview text"}
                        </p>
                      </TableCell>
                      <TableCell className="whitespace-nowrap px-4 py-3.5">
                        <span className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize ${audienceStyle}`}>
                          {target}
                        </span>
                      </TableCell>
                      <TableCell className="whitespace-nowrap px-4 py-3.5">
                        {r.is_active ? (
                          <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            <Check className="h-3 w-3" />
                            Published
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-semibold text-amber-600 dark:text-amber-400">
                            Draft
                          </span>
                        )}
                      </TableCell>
                      <TableCell className="whitespace-nowrap px-4 py-3.5 text-xs text-muted-foreground">
                        {r.published_at
                          ? new Date(r.published_at).toLocaleDateString(undefined, {
                              year: "numeric",
                              month: "short",
                              day: "numeric",
                            })
                          : "—"}
                      </TableCell>
                      <TableCell className="whitespace-nowrap px-4 py-3.5 text-right">
                        <div className="flex items-center justify-end gap-2">
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setViewAnnouncement(r)}
                            title="View details"
                            className="gap-1.5"
                          >
                            <Eye className="h-3.5 w-3.5" />
                            <span>View</span>
                          </Button>
                          {!r.is_active && (
                            <Button
                              size="sm"
                              disabled={actionLoadingId === r.announcement_id}
                              onClick={() => doPublish(r.announcement_id)}
                              className="gap-1.5"
                            >
                              {actionLoadingId === r.announcement_id ? (
                                <Loader2 className="h-3.5 w-3.5 animate-spin" />
                              ) : (
                                <Check className="h-3.5 w-3.5" />
                              )}
                              Publish
                            </Button>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  );
                })
              )}
            </TableBody>
          </Table>
        </div>

        {/* Mobile Card List View */}
        <div className="block sm:hidden mt-4 space-y-3">
          {loading ? (
            <div className="p-4 text-center text-muted-foreground text-sm">Loading...</div>
          ) : filteredRecords.length === 0 ? (
            <div className="p-6 text-center text-muted-foreground text-sm">
              No matching announcements found.
            </div>
          ) : (
            filteredRecords.map((r) => {
              const target = r.announcement_type ?? r.target_role ?? "graduate";
              const audienceStyle = getAudienceBadge(target);

              return (
                <div
                  key={r.announcement_id}
                  className="rounded-xl border border-border/80 bg-card p-4 space-y-2.5 shadow-sm"
                >
                  <div className="flex items-start justify-between gap-2">
                    <h3 className="font-semibold text-sm text-foreground">{r.title}</h3>
                    {r.is_active ? (
                      <span className="shrink-0 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-600">
                        Published
                      </span>
                    ) : (
                      <span className="shrink-0 rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-600">
                        Draft
                      </span>
                    )}
                  </div>
                  <p className="line-clamp-2 text-xs text-muted-foreground">
                    {r.message || r.content}
                  </p>
                  <div className="flex items-center justify-between pt-2 border-t text-xs">
                    <span className={`rounded-full border px-2 py-0.5 text-[10px] font-semibold capitalize ${audienceStyle}`}>
                      {target}
                    </span>
                    <div className="flex items-center gap-2">
                      <Button size="sm" variant="ghost" className="h-7 text-xs" onClick={() => setViewAnnouncement(r)}>
                        View
                      </Button>
                      {!r.is_active && (
                        <Button
                          size="sm"
                          className="h-7 text-xs"
                          disabled={actionLoadingId === r.announcement_id}
                          onClick={() => doPublish(r.announcement_id)}
                        >
                          Publish
                        </Button>
                      )}
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>
      </section>

      {/* View Details Dialog */}
      <Dialog open={viewAnnouncement !== null} onOpenChange={(open) => { if (!open) setViewAnnouncement(null); }}>
        <DialogContent className="max-w-xl">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Megaphone className="h-5 w-5 text-primary" />
              {viewAnnouncement?.title}
            </DialogTitle>
            <DialogDescription>
              Target Audience: <span className="font-semibold capitalize text-foreground">{viewAnnouncement?.target_role || viewAnnouncement?.announcement_type || "graduate"}</span>
            </DialogDescription>
          </DialogHeader>
          <div className="mt-3 space-y-4">
            <div className="rounded-xl border bg-muted/30 p-4 text-sm text-foreground whitespace-pre-wrap leading-relaxed">
              {viewAnnouncement?.message || viewAnnouncement?.content}
            </div>
            <div className="flex justify-between items-center text-xs text-muted-foreground pt-2">
              <span>Status: <strong className="text-foreground">{viewAnnouncement?.is_active ? "Published" : "Draft"}</strong></span>
              <span>Published: <strong className="text-foreground">{viewAnnouncement?.published_at ? new Date(viewAnnouncement.published_at).toLocaleString() : "—"}</strong></span>
            </div>
          </div>
          <DialogFooter className="mt-4">
            <Button onClick={() => setViewAnnouncement(null)}>Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>;
  }

  if (title === "Recommendation Letters") {
    const [records, setRecords] = useState<ManagementRecommendationRequest[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [actionLoadingId, setActionLoadingId] = useState<number | null>(null);
    const [uploadDialogReqId, setUploadDialogReqId] = useState<number | null>(null);
    const [uploadFile, setUploadFile] = useState<File | null>(null);
    const [qrDialogReq, setQrDialogReq] = useState<ManagementRecommendationRequest | null>(null);

    const fetch = async () => {
      setLoading(true);
      setError(null);
      try {
        const items = await getManagementRecommendations();
        setRecords(items);
      } catch (err: any) {
        setError(apiErrorMessage(err, "Could not load recommendation requests."));
      } finally {
        setLoading(false);
      }
    };

    useEffect(() => {
      void fetch();
    }, []);

    const handleApprove = async (id: number, name: string) => {
      if (!window.confirm(`Are you sure you want to approve the recommendation request for ${name}?`)) return;
      setActionLoadingId(id);
      try {
        await approveManagementRecommendation(id);
        toast.success("Recommendation request approved successfully.");
        await fetch();
      } catch (err: any) {
        toast.error(apiErrorMessage(err, "Failed to approve recommendation request."));
      } finally {
        setActionLoadingId(null);
      }
    };

    const handleReject = async (id: number, name: string) => {
      if (!window.confirm(`Are you sure you want to reject the recommendation request for ${name}?`)) return;
      setActionLoadingId(id);
      try {
        await rejectManagementRecommendation(id);
        toast.success("Recommendation request rejected successfully.");
        await fetch();
      } catch (err: any) {
        toast.error(apiErrorMessage(err, "Failed to reject recommendation request."));
      } finally {
        setActionLoadingId(null);
      }
    };

    const handleGenerate = async (id: number) => {
      setActionLoadingId(id);
      try {
        await approveManagementRecommendation(id);
        toast.success("Official recommendation letter generated successfully.");
        await fetch();
      } catch (err: any) {
        toast.error(apiErrorMessage(err, "Failed to generate recommendation letter."));
      } finally {
        setActionLoadingId(null);
      }
    };

    const handleUploadSubmit = async () => {
      if (!uploadDialogReqId || !uploadFile) {
        toast.error("Please select a valid recommendation letter file.");
        return;
      }
      setActionLoadingId(uploadDialogReqId);
      try {
        await uploadManagementRecommendationLetter(uploadDialogReqId, uploadFile);
        toast.success("Recommendation letter uploaded and request approved successfully.");
        setUploadDialogReqId(null);
        setUploadFile(null);
        await fetch();
      } catch (err: any) {
        toast.error(apiErrorMessage(err, "Failed to upload recommendation letter."));
      } finally {
        setActionLoadingId(null);
      }
    };

    const handleDownload = async (id: number) => {
      setActionLoadingId(id);
      try {
        await downloadRecommendationLetter(id, `Recommendation_Letter_${id}.pdf`);
        toast.success("Recommendation letter downloaded successfully.");
      } catch (err: any) {
        toast.error(apiErrorMessage(err, "Failed to download recommendation letter."));
      } finally {
        setActionLoadingId(null);
      }
    };

    return <>
      <PageHeader title={title} subtitle={subtitle} actions={<>{exports && <Button variant="outline"><FileDown className="mr-2 h-4 w-4" />Export Excel</Button>}{exports && <Button variant="outline"><Download className="mr-2 h-4 w-4" />Export PDF</Button>}</>} />
      <section className="rounded-2xl border border-border bg-card p-4 shadow-[var(--shadow-card)] sm:p-5">
        <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem_auto]"><div className="relative"><Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" /><Input className="pl-9" placeholder={`Search ${title.toLowerCase()}...`} aria-label={`Search ${title.toLowerCase()}`} title="Search recommendations" /></div><Select><SelectTrigger><SelectValue placeholder="All records" /></SelectTrigger><SelectContent><SelectItem value="all">All records</SelectItem></SelectContent></Select><Button variant="outline" title="Filters"><SlidersHorizontal className="mr-2 h-4 w-4" />Filters</Button></div>
        <div className="mt-5 overflow-x-auto"><Table><TableHeader className="sticky top-0 z-10 bg-muted/80 backdrop-blur"><TableRow>{columns.map((column) => <TableHead key={column} className="whitespace-nowrap">{column}</TableHead>)}</TableRow></TableHeader>
          <TableBody>
            {loading ? <TableRow><TableCell colSpan={columns.length} className="h-28 text-center text-muted-foreground">Loading records…</TableCell></TableRow> : error ? <TableRow><TableCell colSpan={columns.length} className="h-28 text-center text-muted-foreground">{error}</TableCell></TableRow> : records.length === 0 ? <TableRow><TableCell colSpan={columns.length} className="h-28 text-center text-muted-foreground">No recommendation requests found.</TableCell></TableRow> : records.map((r) => {
              const reqId = r.recommendation_request_id || r.request_id;
              const rawStatus = (r.status || r.request_status || "Pending").trim();
              const statusLower = rawStatus.toLowerCase();
              const gradName = r.graduate_name || r.graduate_email || "Graduate";
              const hasLetter = Boolean(r.letter_file || (r.letters && r.letters.length > 0));

              return (
                <TableRow key={reqId}>
                  <TableCell className="whitespace-nowrap font-medium">{gradName}</TableCell>
                  <TableCell className="max-w-xs truncate" title={r.purpose}>{r.purpose}</TableCell>
                  <TableCell className="whitespace-nowrap">{r.requested_at || r.request_date || "-"}</TableCell>
                  <TableCell className="whitespace-nowrap">
                    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                      statusLower === "approved" ? "bg-emerald-100 text-emerald-800" :
                      statusLower === "rejected" ? "bg-rose-100 text-rose-800" :
                      "bg-amber-100 text-amber-800"
                    }`}>
                      {rawStatus}
                    </span>
                  </TableCell>
                  <TableCell className="whitespace-nowrap">
                    {statusLower === "approved" && hasLetter ? (
                      <Button variant="outline" size="sm" onClick={() => setQrDialogReq(r)} title="View QR verification details">
                        <QrCode className="mr-1 h-3.5 w-3.5 text-emerald-600" />
                        Verified
                      </Button>
                    ) : statusLower === "approved" ? (
                      <span className="text-xs font-medium text-amber-600">Pending Letter</span>
                    ) : (
                      <span className="text-xs text-muted-foreground">-</span>
                    )}
                  </TableCell>
                  <TableCell className="whitespace-nowrap">
                    {statusLower === "pending" && (
                      <div className="flex items-center gap-1.5">
                        <Button size="sm" variant="default" disabled={actionLoadingId === reqId} onClick={() => handleApprove(reqId, gradName)}>
                          {actionLoadingId === reqId ? <Loader2 className="h-3.5 w-3.5 animate-spin mr-1" /> : <Check className="mr-1 h-3.5 w-3.5" />}
                          Approve
                        </Button>
                        <Button size="sm" variant="outline" className="text-destructive border-destructive/30 hover:bg-destructive/10" disabled={actionLoadingId === reqId} onClick={() => handleReject(reqId, gradName)}>
                          <X className="mr-1 h-3.5 w-3.5" />
                          Reject
                        </Button>
                      </div>
                    )}

                    {statusLower === "approved" && !hasLetter && (
                      <div className="flex items-center gap-1.5">
                        <Button size="sm" variant="outline" disabled={actionLoadingId === reqId} onClick={() => handleGenerate(reqId)}>
                          <FileText className="mr-1 h-3.5 w-3.5" />
                          Generate Letter
                        </Button>
                        <Button size="sm" variant="default" disabled={actionLoadingId === reqId} onClick={() => setUploadDialogReqId(reqId)}>
                          <Upload className="mr-1 h-3.5 w-3.5" />
                          Upload Letter
                        </Button>
                      </div>
                    )}

                    {statusLower === "approved" && hasLetter && (
                      <div className="flex items-center gap-1.5">
                        <Button size="sm" variant="outline" disabled={actionLoadingId === reqId} onClick={() => handleDownload(reqId)}>
                          <Download className="mr-1 h-3.5 w-3.5" />
                          Download Letter
                        </Button>
                        <Button size="sm" variant="ghost" onClick={() => setQrDialogReq(r)} title="View QR verification">
                          <QrCode className="mr-1 h-3.5 w-3.5" />
                          QR Verification
                        </Button>
                      </div>
                    )}

                    {statusLower === "rejected" && (
                      <span className="text-xs font-medium text-destructive">Rejected</span>
                    )}
                  </TableCell>
                </TableRow>
              );
            })}
          </TableBody></Table></div>
        <p className="mt-4 text-xs text-muted-foreground">Review recommendation requests submitted by graduates.</p>
      </section>
      {actions.length > 0 && <div className="mt-5"><EmptyState feature="Available actions" description={`${actions.join(", ")}.`} /></div>}

      <Dialog open={uploadDialogReqId !== null} onOpenChange={(open) => { if (!open) { setUploadDialogReqId(null); setUploadFile(null); } }}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Upload Recommendation Letter</DialogTitle>
            <DialogDescription>Attach an official recommendation document (PDF, DOC, DOCX) for request #{uploadDialogReqId}.</DialogDescription>
          </DialogHeader>
          <div className="grid gap-3 py-3">
            <Label htmlFor="rec-letter-file">Recommendation Letter File</Label>
            <Input id="rec-letter-file" type="file" accept=".pdf,.doc,.docx" onChange={(e) => setUploadFile(e.target.files?.[0] || null)} />
            <p className="text-xs text-muted-foreground">Supported formats: PDF, DOC, DOCX (Max 10 MB).</p>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => { setUploadDialogReqId(null); setUploadFile(null); }}>Cancel</Button>
            <Button onClick={handleUploadSubmit} disabled={!uploadFile || actionLoadingId === uploadDialogReqId}>
              {actionLoadingId === uploadDialogReqId && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Upload & Approve
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={qrDialogReq !== null} onOpenChange={(open) => { if (!open) setQrDialogReq(null); }}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <QrCode className="h-5 w-5 text-emerald-600" />
              Recommendation Verification Status
            </DialogTitle>
            <DialogDescription>Verification details for recommendation request #{qrDialogReq?.recommendation_request_id || qrDialogReq?.request_id}.</DialogDescription>
          </DialogHeader>
          {qrDialogReq && (
            <div className="space-y-3 py-2 text-sm">
              <div className="rounded-lg border bg-muted/40 p-3 space-y-1">
                <p><span className="font-semibold">Graduate:</span> {qrDialogReq.graduate_name || qrDialogReq.graduate_email}</p>
                <p><span className="font-semibold">Programme:</span> {qrDialogReq.programme || "N/A"}</p>
                <p><span className="font-semibold">Purpose:</span> {qrDialogReq.purpose}</p>
                <p><span className="font-semibold">Issued Date:</span> {qrDialogReq.letter_file?.issued_date || qrDialogReq.approved_date || "-"}</p>
                <p><span className="font-semibold">Document:</span> {qrDialogReq.letter_file?.original_filename || "Official_Recommendation_Letter.pdf"}</p>
              </div>
              <div className="flex items-center justify-center p-4 border rounded-lg bg-emerald-50 text-emerald-800 text-center font-medium">
                ✓ QR Code Verified • Official Maseno University Document
              </div>
            </div>
          )}
          <DialogFooter>
            <Button onClick={() => setQrDialogReq(null)}>Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>;
  }

  if (title === "Graduates") {
    return <ManagementGraduatesTable subtitle={subtitle} columns={columns} actions={actions} />;
  }

  if (title === "Tracer Surveys") {
    return <ManagementSurveySubmissionsTable subtitle={subtitle} columns={columns} actions={actions} />;
  }

  const { isLoading, isError, configured } = useBackendCollection<unknown[]>(title);
  return <><PageHeader title={title} subtitle={subtitle} actions={<>{exports && <Button variant="outline" disabled={!configured}><FileDown className="mr-2 h-4 w-4" />Export Excel</Button>}{exports && <Button variant="outline" disabled={!configured}><Download className="mr-2 h-4 w-4" />Export PDF</Button>}{allowCreate && <Button disabled={!configured}><Plus className="mr-2 h-4 w-4" />Create</Button>}</>} />
    <section className="rounded-2xl border border-border bg-card p-4 shadow-[var(--shadow-card)] sm:p-5"><div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem_auto]"><div className="relative"><Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" /><Input disabled={!configured} className="pl-9" placeholder={`Search ${title.toLowerCase()}...`} aria-label={`Search ${title.toLowerCase()}`} title="Search becomes available when records are available." /></div><Select disabled={!configured}><SelectTrigger><SelectValue placeholder="All records" /></SelectTrigger><SelectContent><SelectItem value="all">All records</SelectItem></SelectContent></Select><Button variant="outline" disabled={!configured} title="Filters become available when records are available."><SlidersHorizontal className="mr-2 h-4 w-4" />Filters</Button></div>
      <div className="mt-5 overflow-x-auto"><Table><TableHeader className="sticky top-0 z-10 bg-muted/80 backdrop-blur"><TableRow>{columns.map((column) => <TableHead key={column} className="whitespace-nowrap">{column}</TableHead>)}</TableRow></TableHeader><TableBody>{isLoading ? <TableRow><TableCell colSpan={columns.length} className="h-28 text-center text-muted-foreground">Loading recordsâ€¦</TableCell></TableRow> : <TableRow><TableCell colSpan={columns.length} className="h-28 text-center text-muted-foreground">{isError ? "Records could not be loaded. Please try again." : "No records available yet."}</TableCell></TableRow>}</TableBody></Table></div>
      <p className="mt-4 text-xs text-muted-foreground">{configured ? "Actions will become available when the requested records are returned." : "There are no records to display yet."}</p></section>
    {actions.length > 0 && <div className="mt-5"><EmptyState feature="Available actions" description={`${actions.join(", ")}.`} /></div>}
  </>;
}

function ManagementGraduatesTable({ subtitle, columns, actions }: { subtitle: string; columns: string[]; actions: string[] }) {
  const [records, setRecords] = useState<ManagementGraduateRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    void getManagementGraduateReport()
      .then((report) => {
        if (active) setRecords(report.graduates ?? []);
      })
      .catch((err: any) => {
        if (active) setError(apiErrorMessage(err, "Could not load graduate records."));
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, []);

  return <>
    <PageHeader title="Graduates" subtitle={subtitle} />
    <section className="rounded-2xl border border-border bg-card p-4 shadow-[var(--shadow-card)] sm:p-5">
      <div className="mt-5 overflow-x-auto"><Table><TableHeader className="sticky top-0 z-10 bg-muted/80 backdrop-blur"><TableRow>{columns.map((column) => <TableHead key={column} className="whitespace-nowrap">{column}</TableHead>)}</TableRow></TableHeader><TableBody>
        {loading ? <TableRow><TableCell colSpan={columns.length} className="h-28 text-center text-muted-foreground">Loading records…</TableCell></TableRow> : error ? <TableRow><TableCell colSpan={columns.length} className="h-28 text-center text-muted-foreground">{error}</TableCell></TableRow> : records.length === 0 ? <TableRow><TableCell colSpan={columns.length} className="h-28 text-center text-muted-foreground">No graduate records found.</TableCell></TableRow> : records.map((record) => {
          const initials = `${record.first_name[0] ?? ""}${record.last_name[0] ?? ""}`.toUpperCase();
          return <TableRow key={record.graduate_profile_id}>
            <TableCell><span className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">{initials || "G"}</span></TableCell>
            <TableCell>{record.admission_number || "—"}</TableCell>
            <TableCell className="font-medium">{`${record.first_name} ${record.last_name}`.trim()}</TableCell>
            <TableCell>{record.faculty || "—"}</TableCell>
            <TableCell>{record.programme || "—"}</TableCell>
            <TableCell>{record.year_of_graduation || "—"}</TableCell>
            <TableCell>{record.employment_status || "Not provided"}</TableCell>
            <TableCell>{record.recommendation_status || "No request"}</TableCell>
            <TableCell>—</TableCell>
          </TableRow>;
        })}
      </TableBody></Table></div>
    </section>
    {actions.length > 0 && <div className="mt-5"><EmptyState feature="Available actions" description={`${actions.join(", ")}.`} /></div>}
  </>;
}

function ManagementSurveySubmissionsTable({ subtitle, columns, actions }: { subtitle: string; columns: string[]; actions: string[] }) {
  const [records, setRecords] = useState<ManagementSurveySubmission[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedResponse, setSelectedResponse] = useState<ManagementSurveySubmission | null>(null);

  useEffect(() => {
    let active = true;

    void getManagementSurveys()
      .then((surveys) => {
        if (active) setRecords(surveys.flatMap((survey) => survey.completed_submissions ?? []));
      })
      .catch((err: any) => {
        if (active) setError(apiErrorMessage(err, "Could not load submitted survey responses."));
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, []);

  return <>
    <PageHeader title="Tracer Surveys" subtitle={subtitle} />
    <section className="rounded-2xl border border-border bg-card p-4 shadow-[var(--shadow-card)] sm:p-5">
      <div className="mt-5 overflow-x-auto"><Table><TableHeader className="sticky top-0 z-10 bg-muted/80 backdrop-blur"><TableRow>{columns.map((column) => <TableHead key={column} className="whitespace-nowrap">{column}</TableHead>)}</TableRow></TableHeader><TableBody>
        {loading ? <TableRow><TableCell colSpan={columns.length} className="h-28 text-center text-muted-foreground">Loading records…</TableCell></TableRow> : error ? <TableRow><TableCell colSpan={columns.length} className="h-28 text-center text-muted-foreground">{error}</TableCell></TableRow> : records.length === 0 ? <TableRow><TableCell colSpan={columns.length} className="h-28 text-center text-muted-foreground">No completed survey submissions found.</TableCell></TableRow> : records.map((record) => <TableRow key={record.survey_response_id}>
          <TableCell><div className="font-medium">{`${record.first_name} ${record.last_name}`.trim()}</div><div className="text-xs text-muted-foreground">{record.graduate_email}</div></TableCell>
          <TableCell>{record.survey_title}</TableCell>
          <TableCell>{record.status_name || "Submitted"}</TableCell>
          <TableCell>Completed</TableCell>
          <TableCell>{record.submitted_at || record.completed_at || "—"}</TableCell>
          <TableCell><Button size="sm" variant="outline" onClick={() => setSelectedResponse(record)}>View responses</Button></TableCell>
        </TableRow>)}
      </TableBody></Table></div>
    </section>
    {actions.length > 0 && <div className="mt-5"><EmptyState feature="Available actions" description={`${actions.join(", ")}.`} /></div>}
    <Dialog open={selectedResponse !== null} onOpenChange={(open) => { if (!open) setSelectedResponse(null); }}>
      <DialogContent className="max-h-[85vh] max-w-3xl overflow-y-auto">
        <DialogHeader><DialogTitle>Submitted survey responses</DialogTitle><DialogDescription>{selectedResponse ? `${selectedResponse.first_name} ${selectedResponse.last_name} — ${selectedResponse.survey_title}` : ""}</DialogDescription></DialogHeader>
        <div className="space-y-4">
          {selectedResponse?.answers.map((answer) => <div key={answer.question_id} className="rounded-lg border p-3"><p className="font-medium">{answer.prompt}</p><p className="mt-1 text-sm text-muted-foreground">{answer.option_label || answer.answer_text || answer.answer_number || "No response provided."}</p></div>)}
        </div>
      </DialogContent>
    </Dialog>
  </>;
}
