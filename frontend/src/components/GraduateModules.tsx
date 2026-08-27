import { useEffect, useState } from "react";
import toast from "react-hot-toast";
import {
  FileSignature,
  Send,
  Save,
  ClipboardCheck,
  Download,
  Eye,
  ChevronRight,
  Loader2,
  Briefcase,
  CalendarDays,
  Plus,
  Trash2,
  Edit2,
  CheckCircle2,
  XCircle,
} from "lucide-react";
import { PageHeader, EmptyState } from "@/components/PageHeader";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { Progress } from "@/components/ui/progress";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import {
  getGraduateRecommendations,
  createRecommendationRequest,
  downloadRecommendationLetter,
  getGraduateSurveys,
  getSurveyDetails,
  getSurveyResponse,
  submitSurveyAnswers,
  getGraduateEmployment,
  getEmploymentStatuses,
  createGraduateEmployment,
  updateGraduateEmployment,
  deleteGraduateEmployment,
  getGraduateEvents,
  registerForEvent,
  cancelEventRegistration,
} from "@/lib/api";
import type {
  RecommendationRequest,
  Survey,
  EmploymentRecord,
  EmploymentStatus,
  EventItem,
  SurveyResponse,
} from "@/types";

// ============================================================================
// 1. RECOMMENDATION LETTERS PAGE
// ============================================================================
export function RecommendationLettersPage() {
  const [requests, setRequests] = useState<RecommendationRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [downloadingId, setDownloadingId] = useState<number | null>(null);
  const [open, setOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [form, setForm] = useState({ purpose: "", organisation: "", position: "", deadline: "", instructions: "" });
  const today = new Date();
  const minimumDeadline = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;

  const loadRequests = () => {
    setLoading(true);
    getGraduateRecommendations()
      .then((data) => {
        setRequests(data);
        setLoading(false);
      })
      .catch((err) => {
        toast.error(err?.response?.data?.message || err?.message || "Failed to load recommendation requests.");
        setLoading(false);
      });
  };

  useEffect(() => {
    loadRequests();
  }, []);

  const submit = async () => {
    if (!form.purpose.trim()) {
      toast.error("Please provide the purpose of your recommendation letter.");
      return;
    }
    if (form.deadline && form.deadline < minimumDeadline) {
      toast.error("Deadline date cannot be earlier than today.");
      return;
    }
    const purpose = [
      `Purpose: ${form.purpose.trim()}`,
      form.organisation.trim() && `Organisation/institution: ${form.organisation.trim()}`,
      form.position.trim() && `Position/programme: ${form.position.trim()}`,
      form.deadline && `Deadline: ${form.deadline}`,
      form.instructions.trim() && `Instructions: ${form.instructions.trim()}`,
    ].filter(Boolean).join(" | ");
    setSubmitting(true);
    try {
      await createRecommendationRequest(purpose);
      toast.success("Recommendation request submitted successfully.");
      setForm({ purpose: "", organisation: "", position: "", deadline: "", instructions: "" });
      setOpen(false);
      loadRequests();
    } catch (err: any) {
      toast.error(err?.response?.data?.message || err?.message || "Failed to submit request.");
    } finally {
      setSubmitting(false);
    }
  };

  const download = async (req: RecommendationRequest) => {
    const reqId = req.request_id || req.recommendation_request_id;
    if (!reqId) return;
    setDownloadingId(reqId);
    try {
      await downloadRecommendationLetter(reqId, `Recommendation_Letter_${reqId}.pdf`);
      toast.success("Letter downloaded successfully.");
    } catch (err: any) {
      toast.error(err?.response?.data?.message || err?.message || "Failed to download letter.");
    } finally {
      setDownloadingId(null);
    }
  };

  return (
    <>
      <PageHeader
        title="Recommendation Letters"
        subtitle="Request and follow up on official Maseno University recommendation letters."
        actions={
          <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
              <Button>
                <FileSignature className="h-4 w-4 mr-2" />
                Request Recommendation Letter
              </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle>Request a recommendation letter</DialogTitle>
                <DialogDescription>
                  Provide the details required to prepare your official request.
                </DialogDescription>
              </DialogHeader>
              <div className="grid gap-4 py-2">
                <div className="grid gap-2">
                  <Label htmlFor="purpose">
                    Purpose <span className="text-destructive">*</span>
                  </Label>
                  <Input
                    id="purpose"
                    value={form.purpose}
                    onChange={(e) => setForm({ ...form, purpose: e.target.value })}
                    placeholder="e.g. Postgraduate admission or employment application"
                    maxLength={100}
                  />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="organisation">Organisation or institution</Label>
                  <Input id="organisation" value={form.organisation} onChange={(e) => setForm({ ...form, organisation: e.target.value })} placeholder="e.g. University or employer name" maxLength={80} />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="position">Position or programme applied for</Label>
                  <Input id="position" value={form.position} onChange={(e) => setForm({ ...form, position: e.target.value })} placeholder="e.g. Master of Science or Graduate Trainee" maxLength={80} />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="deadline">Deadline date</Label>
                  <Input id="deadline" type="date" min={minimumDeadline} value={form.deadline} onChange={(e) => setForm({ ...form, deadline: e.target.value })} />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="instructions">Additional instructions</Label>
                  <Textarea id="instructions" value={form.instructions} onChange={(e) => setForm({ ...form, instructions: e.target.value })} placeholder="Optional details for the issuing office" maxLength={120} />
                  <p className="text-xs text-muted-foreground">These details are included with the supported purpose field for management review.</p>
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setOpen(false)}>
                  Cancel
                </Button>
                <Button onClick={submit} disabled={submitting}>
                  {submitting ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Send className="h-4 w-4 mr-2" />}
                  Submit request
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        }
      />
      <section className="rounded-2xl border border-border bg-card shadow-[var(--shadow-card)]">
        <div className="border-b border-border p-5">
          <h2 className="font-semibold">Request history</h2>
          <p className="mt-1 text-sm text-muted-foreground">Track the status and availability of your requests.</p>
        </div>
        {loading ? (
          <div className="flex items-center justify-center p-8">
            <Loader2 className="h-6 w-6 animate-spin text-primary mr-2" />
            <span className="text-sm text-muted-foreground">Loading requests…</span>
          </div>
        ) : requests.length === 0 ? (
          <div className="p-5">
            <EmptyState feature="No recommendation requests" description="No records available. Submit a request to begin." />
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[720px] text-left text-sm">
              <thead className="bg-muted/40 text-muted-foreground">
                <tr>
                  {["Request ID", "Request date", "Purpose", "Status", "Approved date", "Action"].map((h) => (
                    <th className="px-5 py-3 font-medium" key={h}>
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {requests.map((r) => {
                  const reqId = r.request_id || r.recommendation_request_id || 0;
                  const reqStatus = r.status || r.request_status || "Pending";
                  const isApproved = reqStatus.toLowerCase() === "approved";

                  return (
                    <tr className="border-t border-border" key={reqId}>
                      <td className="px-5 py-4 font-mono text-xs">#{reqId}</td>
                      <td className="px-5 py-4">{r.request_date || r.requested_at || "N/A"}</td>
                      <td className="px-5 py-4 font-medium">{r.purpose}</td>
                      <td className="px-5 py-4">
                        <span
                          className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                            isApproved
                              ? "bg-success/15 text-success"
                              : reqStatus.toLowerCase() === "rejected"
                                ? "bg-destructive/15 text-destructive"
                                : "bg-warning/15 text-warning-foreground"
                          }`}
                        >
                          {reqStatus}
                        </span>
                      </td>
                      <td className="px-5 py-4 text-muted-foreground">{r.approved_date || r.processed_at || "—"}</td>
                      <td className="px-5 py-4">
                        {isApproved ? (
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => download(r)}
                            disabled={downloadingId === reqId}
                          >
                            {downloadingId === reqId ? (
                              <Loader2 className="h-3.5 w-3.5 animate-spin mr-1" />
                            ) : (
                              <Download className="h-3.5 w-3.5 mr-1" />
                            )}
                            Download Letter
                          </Button>
                        ) : (
                          <span className="text-xs text-muted-foreground">Available once approved</span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </>
  );
}

// ============================================================================
// 2. TRACER SURVEY PAGE
// ============================================================================
export function TracerSurveyPage() {
  const [surveys, setSurveys] = useState<Survey[]>([]);
  const [activeSurvey, setActiveSurvey] = useState<Survey | null>(null);
  const [loading, setLoading] = useState(true);
  const [answers, setAnswers] = useState<Record<number, { answer_text?: string; option_id?: number }>>({});
  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [reviewOpen, setReviewOpen] = useState(false);

  useEffect(() => {
    let mounted = true;
    getGraduateSurveys()
      .then((data) => {
        if (!mounted) return;
        setSurveys(data);
        if (data.length > 0) {
          const targetId = data[0].survey_id;
          getSurveyDetails(targetId)
            .then(async (details) => {
              if (mounted) {
                setActiveSurvey(details);
                setSubmitted(Boolean(details.is_completed));
                try {
                  const response = await getSurveyResponse(targetId);
                  if (mounted) setAnswers(surveyResponseAnswers(response));
                } catch (err: any) {
                  if (err?.response?.status !== 404) throw err;
                }
                setLoading(false);
              }
            })
            .catch(() => {
              if (mounted) setLoading(false);
            });
        } else {
          setLoading(false);
        }
      })
      .catch(() => {
        if (mounted) setLoading(false);
      });

    return () => {
      mounted = false;
    };
  }, []);

  const totalQuestions = activeSurvey?.sections?.reduce((sum, s) => sum + (s.questions?.length || 0), 0) || 0;
  const answeredQuestions = Object.keys(answers).length;
  const completionPct = totalQuestions > 0 ? Math.round((answeredQuestions / totalQuestions) * 100) : 0;

  const submit = async () => {
    if (!activeSurvey) return;
    if (submitted) {
      toast.error("You have already submitted responses for this survey.");
      return;
    }
    if (totalQuestions > 0 && completionPct < 100) {
      toast.error("Please complete all survey questions before submitting.");
      return;
    }
    setSubmitting(true);
    try {
      const payloadAnswers = Object.entries(answers).map(([qId, ans]) => ({
        question_id: Number(qId),
        answer_text: ans.answer_text,
        option_id: ans.option_id,
      }));
      await submitSurveyAnswers(activeSurvey.survey_id, payloadAnswers);
      setSubmitted(true);
      setReviewOpen(false);
      toast.success("Survey submitted successfully.");
    } catch (err: any) {
      toast.error(err?.response?.data?.message || err?.message || "Failed to submit survey.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <>
      <PageHeader
        title="Tracer Survey"
        subtitle="Share your graduate outcomes to help Maseno University improve its programmes."
        actions={
          <Button variant="outline" onClick={() => setReviewOpen(true)}>
            <Eye className="mr-2 h-4 w-4" /> Review Answers
          </Button>
        }
      />

      {submitted && (
        <div className="mb-5 rounded-2xl border border-success/30 bg-success/10 p-5 shadow-[var(--shadow-card)]">
          <div className="flex items-center gap-2 font-semibold text-success text-base">
            <CheckCircle2 className="h-5 w-5" />
            Survey Submitted
          </div>
          <p className="mt-1 text-sm text-muted-foreground">
            Your responses have been successfully submitted and are now read-only.
          </p>
        </div>
      )}

      <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
        <section className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h2 className="font-semibold">{activeSurvey?.name || activeSurvey?.title || "Graduate Tracer Survey"}</h2>
              <p className="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground">
                Your responses help the University understand graduate transitions, employment, further study, and the impact of your academic experience.
              </p>
            </div>
            <span
              className={`rounded-full px-3 py-1 text-xs font-semibold ${
                submitted ? "bg-success/15 text-success" : "bg-primary/10 text-primary"
              }`}
            >
              {submitted ? "Submitted" : "In progress"}
            </span>
          </div>
          <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Overview label="Completion" value={`${completionPct}%`} />
            <Overview label="Survey status" value={submitted ? "Complete" : "Not submitted"} />
            <Overview label="Questions answered" value={`${answeredQuestions} / ${totalQuestions}`} />
            <Overview label="Submission" value={submitted ? "Submitted" : "Pending"} />
          </div>
          <div className="mt-5">
            <Progress value={completionPct} />
            <p className="mt-2 text-xs text-muted-foreground">
              {submitted ? "Responses are locked and read-only." : "Complete each section to prepare your survey for submission."}
            </p>
          </div>
        </section>
        <section className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
          <h2 className="font-semibold">Actions</h2>
          <div className="mt-4 grid gap-2">
            {!submitted && (
              <Button variant="outline" onClick={() => toast.success("Survey progress saved.")}>
                <Save className="mr-2 h-4 w-4" /> Save Progress
              </Button>
            )}
            <Button onClick={() => setReviewOpen(true)} variant={submitted ? "outline" : "default"}>
              <Eye className="mr-2 h-4 w-4" /> Review Answers
            </Button>
            <Button onClick={submit} disabled={submitting || submitted}>
              {submitting ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <ClipboardCheck className="h-4 w-4 mr-2" />}
              {submitted ? "Submitted" : "Submit Survey"}
            </Button>
          </div>
        </section>
      </div>

      <section className="mt-5 rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
        <h2 className="font-semibold">Survey form</h2>
        <p className="mt-1 text-sm text-muted-foreground">
          {submitted ? "Survey questions remain expandable for your review." : "Complete each expandable section at your own pace."}
        </p>

        {loading ? (
          <div className="flex items-center justify-center p-8">
            <Loader2 className="h-6 w-6 animate-spin text-primary mr-2" />
            <span className="text-sm text-muted-foreground">Loading survey questions…</span>
          </div>
        ) : !activeSurvey || !activeSurvey.sections || activeSurvey.sections.length === 0 ? (
          <div className="mt-4">
            <EmptyState feature="No active survey available" description="Check back later for newly published tracer surveys." />
          </div>
        ) : (
          <Accordion type="single" collapsible className="mt-4">
            {activeSurvey.sections.map((section, index) => (
              <AccordionItem key={section.section_id ?? section.survey_section_id ?? index} value={`section-${index}`}>
                <AccordionTrigger>
                  <span className="flex items-center gap-3">
                    <span className="grid h-7 w-7 place-items-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                      {index + 1}
                    </span>
                    {section.title}
                  </span>
                </AccordionTrigger>
                <AccordionContent>
                  <div className="grid gap-4 rounded-xl bg-muted/25 p-4">
                    {section.questions && section.questions.length > 0 ? (
                      section.questions.map((q) => {
                        const questionId = q.question_id ?? q.survey_question_id;
                        return (
                          <div key={questionId} className="grid gap-2 border-b border-border/50 pb-4 last:border-b-0">
                            <Label className="font-medium text-sm">
                              {q.prompt} {q.is_required && !submitted && <span className="text-destructive">*</span>}
                            </Label>
                            {q.options && q.options.length > 0 ? (
                              <Select
                                disabled={submitted}
                                value={answers[questionId]?.option_id ? String(answers[questionId].option_id) : ""}
                                onValueChange={(val) =>
                                  !submitted &&
                                  setAnswers((prev) => ({
                                    ...prev,
                                    [questionId]: { option_id: Number(val) },
                                  }))
                                }
                              >
                                <SelectTrigger className="mt-1">
                                  <SelectValue placeholder="Select an option" />
                                </SelectTrigger>
                                <SelectContent>
                                  {q.options.map((opt) => {
                                    const optionId = opt.option_id ?? opt.survey_question_option_id;
                                    return <SelectItem key={optionId} value={String(optionId)}>{opt.label}</SelectItem>;
                                  })}
                                </SelectContent>
                              </Select>
                            ) : (
                              <Textarea
                                disabled={submitted}
                                placeholder="Enter your answer"
                                value={answers[questionId]?.answer_text || ""}
                                onChange={(e) =>
                                  !submitted &&
                                  setAnswers((prev) => ({
                                    ...prev,
                                    [questionId]: { answer_text: e.target.value },
                                  }))
                                }
                              />
                            )}
                          </div>
                        );
                      })
                    ) : (
                      <p className="text-sm text-muted-foreground">No questions in this section.</p>
                    )}
                  </div>
                </AccordionContent>
              </AccordionItem>
            ))}
          </Accordion>
        )}
      </section>

      {/* Review Answers Modal */}
      <Dialog open={reviewOpen} onOpenChange={setReviewOpen}>
        <DialogContent className="max-w-3xl max-h-[85vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Review Survey Answers</DialogTitle>
            <DialogDescription>
              {submitted
                ? "Your submitted survey responses are displayed below in read-only mode."
                : "Review your selected answers before final submission."}
            </DialogDescription>
          </DialogHeader>

          {submitted && (
            <div className="rounded-xl border border-success/30 bg-success/10 p-3 text-sm text-success font-medium flex items-center gap-2">
              <CheckCircle2 className="h-4 w-4" />
              Responses are read-only and cannot be modified.
            </div>
          )}

          <div className="space-y-6 py-2">
            {activeSurvey?.sections?.map((sec, sIdx) => (
              <div key={sec.section_id ?? sIdx} className="space-y-3 rounded-xl border border-border bg-muted/20 p-4">
                <h3 className="font-semibold text-sm text-primary flex items-center gap-2">
                  <span className="grid h-5 w-5 place-items-center rounded-full bg-primary text-[10px] text-primary-foreground">
                    {sIdx + 1}
                  </span>
                  {sec.title}
                </h3>
                <div className="space-y-3">
                  {sec.questions?.map((q, qIdx) => {
                    const qId = q.question_id ?? q.survey_question_id;
                    const ans = answers[qId];
                    const selectedOpt = q.options?.find((o) => (o.option_id ?? o.survey_question_option_id) === ans?.option_id);
                    const displayText = selectedOpt ? selectedOpt.label : ans?.answer_text || "Not answered";

                    return (
                      <div key={qId} className="rounded-lg border border-border bg-card p-3">
                        <p className="text-xs font-medium text-muted-foreground">{qIdx + 1}. {q.prompt}</p>
                        <p className={`mt-1 text-sm font-semibold ${ans ? "text-foreground" : "text-muted-foreground italic"}`}>
                          {displayText}
                        </p>
                      </div>
                    );
                  })}
                </div>
              </div>
            ))}
          </div>

          <DialogFooter className="gap-2 sm:gap-0">
            <Button variant="outline" onClick={() => setReviewOpen(false)}>
              Close
            </Button>
            {!submitted && (
              <Button onClick={submit} disabled={submitting}>
                {submitting ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <ClipboardCheck className="h-4 w-4 mr-2" />}
                Confirm & Submit Survey
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}

// ============================================================================
// 3. EMPLOYMENT PAGE
// ============================================================================
const getNormalizedStatusCode = (
  statusId: number,
  statusesList: EmploymentStatus[]
): "EMPLOYED" | "SELF_EMPLOYED" | "FURTHER_STUDIES" | "UNEMPLOYED" => {
  const status = statusesList.find((s) => s.employment_status_id === statusId);
  if (!status) return "EMPLOYED";
  const code = (status.code || "").toUpperCase();
  const name = (status.name || "").toLowerCase();

  if (code === "SELF_EMPLOYED" || name.includes("self")) return "SELF_EMPLOYED";
  if (code === "FURTHER_STUDIES" || name.includes("further") || name.includes("stud")) return "FURTHER_STUDIES";
  if (code === "UNEMPLOYED" || name.includes("unemploy")) return "UNEMPLOYED";
  return "EMPLOYED";
};

export function EmploymentPage() {
  const [records, setRecords] = useState<EmploymentRecord[]>([]);
  const [statuses, setStatuses] = useState<EmploymentStatus[]>([]);
  const [loading, setLoading] = useState(true);
  const [open, setOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [editingRecord, setEditingRecord] = useState<EmploymentRecord | null>(null);

  const [form, setForm] = useState({
    employment_status_id: 0,
    company_name: "",
    job_title: "",
    industry: "",
    employment_type: "Full-Time",
    work_location: "",
    start_date: "",
    end_date: "",
    monthly_income: "",
    current_job: true,
  });

  const currentStatusCode = getNormalizedStatusCode(form.employment_status_id, statuses);

  const loadData = () => {
    setLoading(true);
    Promise.all([getGraduateEmployment(), getEmploymentStatuses()])
      .then(([empRes, statRes]) => {
        setRecords(empRes);
        setStatuses(statRes);
        if (statRes.length > 0 && form.employment_status_id === 0) {
          setForm((f) => ({ ...f, employment_status_id: statRes[0].employment_status_id }));
        }
        setLoading(false);
      })
      .catch((err) => {
        toast.error(err?.response?.data?.message || err?.message || "Failed to load employment details.");
        setLoading(false);
      });
  };

  useEffect(() => {
    loadData();
  }, []);

  const openAddForm = () => {
    setEditingRecord(null);
    const defaultStatusId = statuses[0]?.employment_status_id ?? 1;
    const defaultStatusCode = getNormalizedStatusCode(defaultStatusId, statuses);
    setForm({
      employment_status_id: defaultStatusId,
      company_name: "",
      job_title: "",
      industry: "",
      employment_type: defaultStatusCode === "UNEMPLOYED" ? "Yes" : "Full-Time",
      work_location: "",
      start_date: "",
      end_date: "",
      monthly_income: "",
      current_job: true,
    });
    setOpen(true);
  };

  const openEditForm = (record: EmploymentRecord) => {
    setEditingRecord(record);

    let empType = record.employment_type || "";
    let location = "";
    if (empType.includes(" - ")) {
      const parts = empType.split(" - ");
      empType = parts[0];
      location = parts.slice(1).join(" - ");
    }

    setForm({
      employment_status_id: record.employment_status_id ?? (statuses[0]?.employment_status_id ?? 1),
      company_name: record.company_name || "",
      job_title: record.job_title || "",
      industry: record.industry || "",
      employment_type: empType,
      work_location: location,
      start_date: record.start_date ? record.start_date.split("T")[0] : "",
      end_date: record.end_date ? record.end_date.split("T")[0] : "",
      monthly_income: record.monthly_income ? String(record.monthly_income) : "",
      current_job: Boolean(record.current_job),
    });
    setOpen(true);
  };

  const saveRecord = async () => {
    if (currentStatusCode === "EMPLOYED") {
      if (!form.company_name.trim() || !form.job_title.trim() || !form.start_date) {
        toast.error("Please enter the company name, job title, and employment start date.");
        return;
      }
    } else if (currentStatusCode === "SELF_EMPLOYED") {
      if (!form.company_name.trim() || !form.job_title.trim() || !form.start_date) {
        toast.error("Please enter the business name, your role, and business start date.");
        return;
      }
    } else if (currentStatusCode === "FURTHER_STUDIES") {
      if (!form.company_name.trim() || !form.job_title.trim() || !form.start_date) {
        toast.error("Please enter the institution name, programme/course, and study start date.");
        return;
      }
    }

    setSaving(true);
    try {
      let payload: any = {
        employment_status_id: form.employment_status_id || (statuses[0]?.employment_status_id ?? 1),
      };

      if (currentStatusCode === "EMPLOYED") {
        let finalType = form.employment_type || "Full-Time";
        if (form.work_location.trim()) {
          finalType = `${finalType} - ${form.work_location.trim()}`;
        }
        payload = {
          ...payload,
          company_name: form.company_name.trim(),
          job_title: form.job_title.trim(),
          industry: form.industry.trim() || undefined,
          employment_type: finalType,
          start_date: form.start_date || undefined,
          monthly_income: form.monthly_income ? Number(form.monthly_income) : undefined,
          current_job: form.current_job ? 1 : 0,
        };
      } else if (currentStatusCode === "SELF_EMPLOYED") {
        payload = {
          ...payload,
          company_name: form.company_name.trim(),
          job_title: form.job_title.trim(),
          industry: form.industry.trim() || undefined,
          employment_type: form.employment_type.trim() || undefined,
          start_date: form.start_date || undefined,
          monthly_income: form.monthly_income ? Number(form.monthly_income) : undefined,
          current_job: 1,
        };
      } else if (currentStatusCode === "FURTHER_STUDIES") {
        payload = {
          ...payload,
          company_name: form.company_name.trim(),
          job_title: form.job_title.trim(),
          industry: form.industry.trim() || undefined,
          employment_type: form.employment_type.trim() || undefined,
          start_date: form.start_date || undefined,
          end_date: form.end_date || undefined,
          current_job: 1,
        };
      } else if (currentStatusCode === "UNEMPLOYED") {
        const isLooking = (form.employment_type || "Yes") === "Yes";
        payload = {
          ...payload,
          employment_type: form.employment_type || "Yes",
          job_title: isLooking ? (form.job_title.trim() || undefined) : undefined,
          industry: isLooking ? (form.industry.trim() || undefined) : undefined,
          company_name: isLooking ? (form.company_name.trim() || undefined) : undefined,
          current_job: 0,
        };
      }

      if (editingRecord) {
        await updateGraduateEmployment(editingRecord.employment_record_id, payload);
        toast.success("Employment profile updated successfully.");
      } else {
        await createGraduateEmployment(payload);
        toast.success("Employment record added successfully.");
      }
      setOpen(false);
      loadData();
    } catch (err: any) {
      toast.error(err?.response?.data?.message || err?.message || "Failed to save record.");
    } finally {
      setSaving(false);
    }
  };

  const removeRecord = async (id: number) => {
    try {
      await deleteGraduateEmployment(id);
      toast.success("Employment record deleted.");
      loadData();
    } catch (err: any) {
      toast.error(err?.response?.data?.message || err?.message || "Failed to delete record.");
    }
  };

  const hasRecord = records.length > 0;

  return (
    <>
      <PageHeader
        title="Employment"
        subtitle="Keep your employment history and career progression current."
        actions={
          !hasRecord ? (
            <Button onClick={openAddForm}>
              <Plus className="h-4 w-4 mr-2" /> Add Employment Profile
            </Button>
          ) : (
            <Button variant="outline" onClick={() => openEditForm(records[0])}>
              <Edit2 className="h-4 w-4 mr-2" /> Edit Employment
            </Button>
          )
        }
      />

      {hasRecord && (
        <div className="mb-5 rounded-2xl border border-primary/20 bg-primary/5 p-5 shadow-[var(--shadow-card)]">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div>
              <h3 className="font-semibold text-foreground text-base">Employment Profile</h3>
              <p className="mt-1 text-sm text-muted-foreground flex items-center gap-1.5">
                <CheckCircle2 className="h-4 w-4 text-success shrink-0" />
                You already have an employment profile. Use Edit Employment to update your employment information.
              </p>
            </div>
            <Button onClick={() => openEditForm(records[0])}>
              <Edit2 className="h-4 w-4 mr-2" /> Edit Employment
            </Button>
          </div>
        </div>
      )}

      {/* Employment Form Modal */}
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>
              {currentStatusCode === "UNEMPLOYED"
                ? editingRecord
                  ? "Edit Career Preferences"
                  : "Career Preferences"
                : editingRecord
                ? "Edit Employment Profile"
                : "Add Employment Profile"}
            </DialogTitle>
            <DialogDescription>
              {currentStatusCode === "EMPLOYED" && "Enter your current employment details."}
              {currentStatusCode === "SELF_EMPLOYED" && "Enter your business information."}
              {currentStatusCode === "FURTHER_STUDIES" && "Enter your education progression details."}
              {currentStatusCode === "UNEMPLOYED" &&
                "Tell us about your current employment status and career interests. This information helps the university understand the type of opportunities you are seeking."}
            </DialogDescription>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            {/* Employment Status Select - ALWAYS VISIBLE */}
            <div className="grid gap-2">
              <Label>Employment Status <span className="text-destructive">*</span></Label>
              <Select
                value={String(form.employment_status_id)}
                onValueChange={(v) => {
                  const nextId = Number(v);
                  const nextStatusCode = getNormalizedStatusCode(nextId, statuses);
                  setForm((prev) => ({
                    ...prev,
                    employment_status_id: nextId,
                    employment_type:
                      nextStatusCode === "EMPLOYED"
                        ? "Full-Time"
                        : nextStatusCode === "UNEMPLOYED"
                        ? "Yes"
                        : nextStatusCode === "FURTHER_STUDIES"
                        ? "Full-Time"
                        : "",
                  }));
                }}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select status" />
                </SelectTrigger>
                <SelectContent>
                  {statuses.map((s) => (
                    <SelectItem key={s.employment_status_id} value={String(s.employment_status_id)}>
                      {s.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* ==================== 1. EMPLOYED FORM ==================== */}
            {currentStatusCode === "EMPLOYED" && (
              <>
                <div className="grid gap-2">
                  <Label>Company Name <span className="text-destructive">*</span></Label>
                  <Input
                    value={form.company_name}
                    onChange={(e) => setForm({ ...form, company_name: e.target.value })}
                    placeholder="e.g. Safaricom PLC"
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Job Title <span className="text-destructive">*</span></Label>
                  <Input
                    value={form.job_title}
                    onChange={(e) => setForm({ ...form, job_title: e.target.value })}
                    placeholder="e.g. Software Engineer"
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Industry</Label>
                  <Input
                    value={form.industry}
                    onChange={(e) => setForm({ ...form, industry: e.target.value })}
                    placeholder="e.g. Telecommunications"
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Employment Type</Label>
                  <Select
                    value={form.employment_type || "Full-Time"}
                    onValueChange={(v) => setForm({ ...form, employment_type: v })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Full-Time">Full-Time</SelectItem>
                      <SelectItem value="Part-Time">Part-Time</SelectItem>
                      <SelectItem value="Contract">Contract</SelectItem>
                      <SelectItem value="Internship">Internship</SelectItem>
                      <SelectItem value="Temporary">Temporary</SelectItem>
                      <SelectItem value="Other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="grid gap-2">
                  <Label>Work Location</Label>
                  <Input
                    value={form.work_location}
                    onChange={(e) => setForm({ ...form, work_location: e.target.value })}
                    placeholder="e.g. Nairobi, Kenya"
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Employment Start Date <span className="text-destructive">*</span></Label>
                  <Input
                    type="date"
                    value={form.start_date}
                    onChange={(e) => setForm({ ...form, start_date: e.target.value })}
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Monthly Income (optional)</Label>
                  <Input
                    type="number"
                    value={form.monthly_income}
                    onChange={(e) => setForm({ ...form, monthly_income: e.target.value })}
                    placeholder="e.g. 75000"
                  />
                </div>
              </>
            )}

            {/* ==================== 2. SELF EMPLOYED FORM ==================== */}
            {currentStatusCode === "SELF_EMPLOYED" && (
              <>
                <div className="grid gap-2">
                  <Label>Business Name <span className="text-destructive">*</span></Label>
                  <Input
                    value={form.company_name}
                    onChange={(e) => setForm({ ...form, company_name: e.target.value })}
                    placeholder="e.g. Apex Tech Solutions"
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Your Role <span className="text-destructive">*</span></Label>
                  <Input
                    value={form.job_title}
                    onChange={(e) => setForm({ ...form, job_title: e.target.value })}
                    placeholder="e.g. Founder & Managing Director"
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Business Sector</Label>
                  <Input
                    value={form.industry}
                    onChange={(e) => setForm({ ...form, industry: e.target.value })}
                    placeholder="e.g. Information Technology"
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Business Location</Label>
                  <Input
                    value={form.employment_type}
                    onChange={(e) => setForm({ ...form, employment_type: e.target.value })}
                    placeholder="e.g. Kisumu, Kenya"
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Business Start Date <span className="text-destructive">*</span></Label>
                  <Input
                    type="date"
                    value={form.start_date}
                    onChange={(e) => setForm({ ...form, start_date: e.target.value })}
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Monthly Income (optional)</Label>
                  <Input
                    type="number"
                    value={form.monthly_income}
                    onChange={(e) => setForm({ ...form, monthly_income: e.target.value })}
                    placeholder="e.g. 100000"
                  />
                </div>
              </>
            )}

            {/* ==================== 3. FURTHER STUDIES FORM ==================== */}
            {currentStatusCode === "FURTHER_STUDIES" && (
              <>
                <div className="grid gap-2">
                  <Label>Institution Name <span className="text-destructive">*</span></Label>
                  <Input
                    value={form.company_name}
                    onChange={(e) => setForm({ ...form, company_name: e.target.value })}
                    placeholder="e.g. Maseno University"
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Programme / Course <span className="text-destructive">*</span></Label>
                  <Input
                    value={form.job_title}
                    onChange={(e) => setForm({ ...form, job_title: e.target.value })}
                    placeholder="e.g. Master of Science in Computer Science"
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Field of Study</Label>
                  <Input
                    value={form.industry}
                    onChange={(e) => setForm({ ...form, industry: e.target.value })}
                    placeholder="e.g. Information Technology"
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Study Mode</Label>
                  <Select
                    value={form.employment_type || "Full-Time"}
                    onValueChange={(v) => setForm({ ...form, employment_type: v })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select mode" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Full-Time">Full-Time</SelectItem>
                      <SelectItem value="Part-Time">Part-Time</SelectItem>
                      <SelectItem value="Distance Learning">Distance Learning</SelectItem>
                      <SelectItem value="Online">Online</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="grid gap-2">
                  <Label>Study Start Date <span className="text-destructive">*</span></Label>
                  <Input
                    type="date"
                    value={form.start_date}
                    onChange={(e) => setForm({ ...form, start_date: e.target.value })}
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Expected Graduation Date</Label>
                  <Input
                    type="date"
                    value={form.end_date}
                    onChange={(e) => setForm({ ...form, end_date: e.target.value })}
                  />
                </div>
              </>
            )}

            {/* ==================== 4. UNEMPLOYED FORM ==================== */}
            {currentStatusCode === "UNEMPLOYED" && (
              <>
                <div className="grid gap-2">
                  <Label>Are you currently looking for employment? <span className="text-destructive">*</span></Label>
                  <Select
                    value={form.employment_type || "Yes"}
                    onValueChange={(v) => setForm({ ...form, employment_type: v })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select option" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Yes">Yes</SelectItem>
                      <SelectItem value="No">No</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                {(form.employment_type || "Yes") === "Yes" && (
                  <>
                    <div className="grid gap-2">
                      <Label>Preferred Industry</Label>
                      <Input
                        value={form.industry}
                        onChange={(e) => setForm({ ...form, industry: e.target.value })}
                        placeholder="e.g. Information Technology, Finance, Education"
                      />
                    </div>
                    <div className="grid gap-2">
                      <Label>Preferred Job Role</Label>
                      <Input
                        value={form.job_title}
                        onChange={(e) => setForm({ ...form, job_title: e.target.value })}
                        placeholder="e.g. Software Engineer, Data Analyst"
                      />
                    </div>
                    <div className="grid gap-2">
                      <Label>Additional Comments</Label>
                      <Input
                        value={form.company_name}
                        onChange={(e) => setForm({ ...form, company_name: e.target.value })}
                        placeholder="e.g. Seeking graduate trainee or entry-level positions."
                      />
                    </div>
                  </>
                )}
              </>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
            <Button onClick={saveRecord} disabled={saving}>
              {saving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : null}
              {currentStatusCode === "UNEMPLOYED"
                ? editingRecord
                  ? "Update Preferences"
                  : "Save Preferences"
                : editingRecord
                ? "Update Profile"
                : "Save Record"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <section className="rounded-2xl border border-border bg-card shadow-[var(--shadow-card)]">
        <div className="border-b border-border p-5">
          <h2 className="font-semibold">Employment History</h2>
          <p className="mt-1 text-sm text-muted-foreground">Your recorded career positions.</p>
        </div>
        {loading ? (
          <div className="flex items-center justify-center p-8">
            <Loader2 className="h-6 w-6 animate-spin text-primary mr-2" />
            <span className="text-sm text-muted-foreground">Loading history…</span>
          </div>
        ) : records.length === 0 ? (
          <div className="p-5">
            <EmptyState feature="No employment records" description="Click Add Employment Profile to set up your employment details." />
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[640px] text-left text-sm">
              <thead className="bg-muted/40 text-muted-foreground">
                <tr>
                  <th className="px-5 py-3 font-medium">Job Title</th>
                  <th className="px-5 py-3 font-medium">Company</th>
                  <th className="px-5 py-3 font-medium">Industry</th>
                  <th className="px-5 py-3 font-medium">Status</th>
                  <th className="px-5 py-3 font-medium">Action</th>
                </tr>
              </thead>
              <tbody>
                {records.map((r) => (
                  <tr key={r.employment_record_id} className="border-t border-border">
                    <td className="px-5 py-4 font-medium">{r.job_title}</td>
                    <td className="px-5 py-4">{r.company_name}</td>
                    <td className="px-5 py-4 text-muted-foreground">{r.industry || "—"}</td>
                    <td className="px-5 py-4">
                      <span className="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">
                        {r.employment_status_name || r.employment_status || "Active"}
                      </span>
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-2">
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => openEditForm(r)}
                        >
                          <Edit2 className="h-3.5 w-3.5 mr-1" /> Edit
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          className="text-destructive hover:bg-destructive/10"
                          onClick={() => removeRecord(r.employment_record_id)}
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </>
  );
}

// ============================================================================
// 4. EVENTS PAGE
// ============================================================================
export function EventsPage() {
  const [events, setEvents] = useState<EventItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [actionId, setActionId] = useState<number | null>(null);
  const [selectedEvent, setSelectedEvent] = useState<EventItem | null>(null);

  const loadEvents = () => {
    setLoading(true);
    getGraduateEvents()
      .then((res) => {
        setEvents(res);
        setLoading(false);
      })
      .catch((err) => {
        toast.error(err?.response?.data?.message || err?.message || "Failed to load events.");
        setLoading(false);
      });
  };

  useEffect(() => {
    loadEvents();
  }, []);

  const register = async (eventId: number) => {
    setActionId(eventId);
    try {
      await registerForEvent(eventId);
      toast.success("Successfully registered for event.");
      loadEvents();
      if (selectedEvent && (selectedEvent.event_id === eventId || Number(selectedEvent.id) === eventId)) {
        setSelectedEvent((prev) => (prev ? { ...prev, is_registered: true, registration_status: "Registered" } : null));
      }
    } catch (err: any) {
      toast.error(err?.response?.data?.message || err?.message || "Registration failed.");
    } finally {
      setActionId(null);
    }
  };

  const cancelReg = async (eventId: number) => {
    setActionId(eventId);
    try {
      await cancelEventRegistration(eventId);
      toast.success("Registration canceled.");
      loadEvents();
      if (selectedEvent && (selectedEvent.event_id === eventId || Number(selectedEvent.id) === eventId)) {
        setSelectedEvent((prev) => (prev ? { ...prev, is_registered: false, registration_status: "Open" } : null));
      }
    } catch (err: any) {
      toast.error(err?.response?.data?.message || err?.message || "Failed to cancel registration.");
    } finally {
      setActionId(null);
    }
  };

  return (
    <>
      <PageHeader title="Events" subtitle="Upcoming alumni and university events." />
      <section className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
        <h2 className="font-semibold">Upcoming Events</h2>
        <p className="mt-1 text-sm text-muted-foreground mb-4">View and register for Maseno Pathfinder events.</p>
        {loading ? (
          <div className="flex items-center justify-center p-8">
            <Loader2 className="h-6 w-6 animate-spin text-primary mr-2" />
            <span className="text-sm text-muted-foreground">Loading upcoming events…</span>
          </div>
        ) : events.length === 0 ? (
          <EmptyState feature="No upcoming events" description="Check back soon for new university events." />
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {events.map((e) => {
              const eventId = e.event_id || Number(e.id);
              const isReg = e.is_registered || e.registration_status === "Registered";

              return (
                <div key={eventId} className="flex flex-col justify-between rounded-xl border border-border bg-muted/20 p-4 transition-all hover:border-primary/40">
                  <div>
                    <div className="flex items-center gap-2 text-primary">
                      <CalendarDays className="h-5 w-5 shrink-0" />
                      <h3 className="font-semibold text-foreground line-clamp-1">{e.title}</h3>
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                      Venue: {e.venue || e.location || "Main Campus"}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      Date: {new Date(e.start_datetime || e.starts_at || e.start_date || "").toLocaleString()}
                    </p>
                    {e.description && <p className="mt-2 text-xs leading-relaxed text-muted-foreground line-clamp-2">{e.description}</p>}
                  </div>
                  <div className="mt-4 pt-3 border-t border-border flex items-center justify-between gap-2">
                    <Button
                      size="sm"
                      variant="outline"
                      className="border-primary/30 text-primary hover:bg-primary/10"
                      onClick={() => setSelectedEvent(e)}
                    >
                      <Eye className="h-3.5 w-3.5 mr-1" /> View Details
                    </Button>
                    {isReg ? (
                      <span className="inline-flex items-center text-xs font-semibold text-success">
                        <CheckCircle2 className="h-4 w-4 mr-1" /> Registered
                      </span>
                    ) : (
                      <Button size="sm" onClick={() => register(eventId)} disabled={actionId === eventId}>
                        {actionId === eventId ? <Loader2 className="h-3.5 w-3.5 animate-spin mr-1" /> : null}
                        Register
                      </Button>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </section>

      {/* Event Details Modal */}
      <Dialog open={Boolean(selectedEvent)} onOpenChange={(open) => !open && setSelectedEvent(null)}>
        <DialogContent className="max-w-xl max-h-[85vh] overflow-y-auto">
          {selectedEvent && (() => {
            const eventId = selectedEvent.event_id || Number(selectedEvent.id);
            const isReg = selectedEvent.is_registered || selectedEvent.registration_status === "Registered";
            const startDate = selectedEvent.start_datetime || selectedEvent.starts_at || selectedEvent.start_date;
            const endDate = selectedEvent.end_datetime || selectedEvent.ends_at || selectedEvent.end_date;
            const deadline = selectedEvent.registration_deadline;

            return (
              <>
                <DialogHeader>
                  <div className="flex items-center gap-2 text-primary">
                    <CalendarDays className="h-5 w-5" />
                    <DialogTitle className="text-xl">{selectedEvent.title}</DialogTitle>
                  </div>
                  <DialogDescription>Detailed information for this event.</DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-3">
                  <div className="rounded-xl bg-muted/30 p-4 space-y-2.5 border border-border">
                    <div className="flex justify-between items-center border-b border-border/50 pb-2">
                      <span className="text-xs text-muted-foreground font-medium">Event Type</span>
                      <span className="text-xs font-semibold rounded-full bg-primary/10 text-primary px-2.5 py-0.5">
                        {selectedEvent.event_type || "General"}
                      </span>
                    </div>
                    <div className="flex justify-between items-center border-b border-border/50 pb-2">
                      <span className="text-xs text-muted-foreground font-medium">Venue</span>
                      <span className="text-sm font-semibold">{selectedEvent.venue || selectedEvent.location || "Main Campus"}</span>
                    </div>
                    <div className="flex justify-between items-center border-b border-border/50 pb-2">
                      <span className="text-xs text-muted-foreground font-medium">Start Date & Time</span>
                      <span className="text-xs font-semibold">{startDate ? new Date(startDate).toLocaleString() : "TBA"}</span>
                    </div>
                    <div className="flex justify-between items-center border-b border-border/50 pb-2">
                      <span className="text-xs text-muted-foreground font-medium">End Date & Time</span>
                      <span className="text-xs font-semibold">{endDate ? new Date(endDate).toLocaleString() : "TBA"}</span>
                    </div>
                    <div className="flex justify-between items-center border-b border-border/50 pb-2">
                      <span className="text-xs text-muted-foreground font-medium">Registration Deadline</span>
                      <span className="text-xs font-semibold">{deadline ? new Date(deadline).toLocaleString() : "No deadline"}</span>
                    </div>
                    <div className="flex justify-between items-center border-b border-border/50 pb-2">
                      <span className="text-xs text-muted-foreground font-medium">Maximum Capacity</span>
                      <span className="text-xs font-semibold">{selectedEvent.max_attendees ?? selectedEvent.capacity ?? "Unlimited"}</span>
                    </div>
                    <div className="flex justify-between items-center border-b border-border/50 pb-2">
                      <span className="text-xs text-muted-foreground font-medium">Remaining Slots</span>
                      <span className="text-xs font-semibold text-primary">
                        {selectedEvent.available_slots !== undefined && selectedEvent.available_slots !== null
                          ? selectedEvent.available_slots
                          : "Available"}
                      </span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-xs text-muted-foreground font-medium">Registration Status</span>
                      <span className={`text-xs font-semibold px-2.5 py-0.5 rounded-full ${isReg ? "bg-success/15 text-success" : "bg-primary/10 text-primary"}`}>
                        {isReg ? "Registered" : "Open"}
                      </span>
                    </div>
                  </div>

                  <div>
                    <h4 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Description</h4>
                    <p className="mt-1.5 text-sm leading-relaxed text-foreground bg-card p-3 rounded-lg border border-border">
                      {selectedEvent.description || "No detailed description provided for this event."}
                    </p>
                  </div>
                </div>

                <DialogFooter className="gap-2 sm:gap-0">
                  <Button variant="outline" onClick={() => setSelectedEvent(null)}>
                    Close
                  </Button>
                  {isReg ? (
                    <Button
                      variant="outline"
                      className="text-destructive border-destructive/30 hover:bg-destructive/10"
                      onClick={() => cancelReg(eventId)}
                      disabled={actionId === eventId}
                    >
                      {actionId === eventId ? <Loader2 className="h-4 w-4 animate-spin mr-1" /> : null}
                      Cancel Registration
                    </Button>
                  ) : (
                    <Button onClick={() => register(eventId)} disabled={actionId === eventId}>
                      {actionId === eventId ? <Loader2 className="h-4 w-4 animate-spin mr-1" /> : null}
                      Register for Event
                    </Button>
                  )}
                </DialogFooter>
              </>
            );
          })()}
        </DialogContent>
      </Dialog>
    </>
  );
}

function surveyResponseAnswers(response: SurveyResponse): Record<number, { answer_text?: string; option_id?: number }> {
  return Object.fromEntries(response.answers.map((answer) => [
    answer.question_id,
    {
      ...(answer.answer_text ? { answer_text: answer.answer_text } : {}),
      ...(answer.option_id ? { option_id: answer.option_id } : {}),
    },
  ]));
}

function Overview({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-xl border border-border bg-muted/20 p-3">
      <p className="text-xs font-medium text-muted-foreground">{label}</p>
      <p className="mt-1 text-sm font-semibold">{value}</p>
    </div>
  );
}
