import axios from "axios";
import type {
  GraduateDashboardData,
  GraduateProfile,
  EmploymentRecord,
  EmploymentStatus,
  EventItem,
  Survey,
  SurveyResponse,
  RecommendationRequest,
  ManagementRecommendationRequest,
  ManagementOverviewReport,
  ManagementEmploymentReport,
  ManagementGraduateReport,
  ManagementSurveyReport,
  ManagementEventReport,
  ManagementRecommendationReport,
  ManagementSurvey,
} from "@/types";

const getBaseUrl = (): string => import.meta.env.VITE_API_BASE_URL || "/maseno-pathfinder/backend";

export const api = axios.create({
  baseURL: getBaseUrl(),
  withCredentials: false,
  timeout: 20000,
});

api.interceptors.request.use((config) => {
  if (typeof window !== "undefined") {
    const token = window.localStorage.getItem("mugts.token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }
  return config;
});

api.interceptors.response.use(
  (r) => r,
  (err) => {
    if (err?.response?.status === 401 && typeof window !== "undefined") {
      window.localStorage.removeItem("mugts.token");
      window.localStorage.removeItem("mugts.user");
    }
    return Promise.reject(err);
  },
);

export const isApiConfigured = () => true;

export function apiErrorMessage(error: unknown, fallback = "The request could not be completed. Please try again later."): string {
  if (!axios.isAxiosError(error)) return fallback;
  const response = error.response;
  if (!response) return "Unable to connect to the server. Check your internet connection and try again.";
  const body = response.data as Partial<ApiResponse<unknown>> | undefined;
  const message = typeof body?.message === "string" ? body.message : "";
  if (message) return message;
  if (response.status === 401) return "Your session has expired. Please sign in again.";
  if (response.status === 403) return "Permission denied. You do not have permission to perform this action.";
  if (response.status === 404) return "The requested resource was not found. Refresh the page and try again.";
  if (response.status >= 500) return "The server could not process this request. Please try again later or contact the system administrator.";
  return fallback;
}
export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  errors?: Record<string, string> | string[];
}

export type PublicProgramme = { programme_id: number; programme_name: string; programme_code: string | null; department_name: string; faculty_name: string; education_level_name: string };

export async function getPublicProgrammes(): Promise<PublicProgramme[]> {
  const res = await api.get<ApiResponse<PublicProgramme[]>>("/programmes");
  return res.data.data;
}

export type AcademicMetadata = {
  programmes: { programme_id: number; programme_name: string; programme_code: string | null; department_id: number; department_name: string; faculty_id: number; faculty_name: string; education_level_id: number; education_level_name: string }[];
  departments: { department_id: number; faculty_id: number; name: string; code: string | null }[];
  faculties: { faculty_id: number; name: string; code: string }[];
  graduation_statuses: { graduation_status_id: number; name: string; code: string }[];
};

export async function getAcademicMetadata(): Promise<AcademicMetadata> {
  const res = await api.get<ApiResponse<AcademicMetadata>>("/academics/meta");
  return res.data.data;
}
// 1. Dashboard API
export async function getGraduateDashboard(): Promise<GraduateDashboardData> {
  const res = await api.get<ApiResponse<GraduateDashboardData>>("/graduate/dashboard");
  return res.data.data;
}

export type ManagementDashboardData = {
  management_profile_id: number;
  total_graduates: number;
  employment: { total_records: number; employed_graduates: number; by_status: Array<{ name: string; count: number }> };
  surveys: { total_surveys: number; total_invitations: number; completed_responses: number; completion_rate: number };
  recommendations: { total_requests: number; pending_requests: number; approved_requests: number; rejected_requests: number };
  upcoming_events_count: number;
};

export async function getManagementDashboard(): Promise<ManagementDashboardData> {
  const res = await api.get<ApiResponse<ManagementDashboardData>>("/management/dashboard");
  return res.data.data;
}

// Management Announcements APIs
export type ManagementAnnouncement = {
  announcement_id: number;
  created_by: number;
  title: string;
  message: string;
  announcement_type: string | null;
  target_role?: string;
  is_active: number;
  published_at?: string | null;
  created_at: string;
  updated_at: string;
  recipients_count?: number;
};

export type GraduateAnnouncement = {
  announcement_id: number;
  created_by: number;
  title: string;
  content: string;
  message: string;
  announcement_type: string;
  target_role: string;
  published_at?: string | null;
  expires_at?: string | null;
  is_active: number;
  status: string;
  is_new: boolean;
  viewed_at?: string | null;
  created_at: string;
  updated_at: string;
};

export async function getManagementAnnouncements(): Promise<ManagementAnnouncement[]> {
  const res = await api.get<ApiResponse<ManagementAnnouncement[]>>("/management/announcements");
  return res.data.data;
}

export async function getGraduateAnnouncements(): Promise<GraduateAnnouncement[]> {
  const res = await api.get<ApiResponse<GraduateAnnouncement[]>>("/graduate/announcements");
  return res.data.data;
}

export async function markGraduateAnnouncementViewed(
  id: number,
): Promise<{ announcement_id: number; is_new: false; viewed_at: string | null }> {
  const res = await api.put<ApiResponse<{ announcement_id: number; is_new: false; viewed_at: string | null }>>(
    `/graduate/announcements/${id}/view`,
  );
  return res.data.data;
}

export async function createManagementAnnouncement(payload: { title: string; message: string; target_role?: string }): Promise<ManagementAnnouncement> {
  const res = await api.post<ApiResponse<ManagementAnnouncement>>("/management/announcements", payload);
  return res.data.data;
}

export async function publishManagementAnnouncement(id: number): Promise<ManagementAnnouncement> {
  const res = await api.put<ApiResponse<ManagementAnnouncement>>(`/management/announcements/${id}/publish`);
  return res.data.data;
}
// 2. Profile APIs
export async function getGraduateProfile(): Promise<GraduateProfile> {
  const res = await api.get<ApiResponse<GraduateProfile>>("/graduate/profile");
  return res.data.data;
}

export async function updateGraduateProfile(payload: Partial<GraduateProfile>): Promise<GraduateProfile> {
  const res = await api.put<ApiResponse<GraduateProfile>>("/graduate/profile", payload);
  return res.data.data;
}

export type UploadedProfileFile = GraduateProfile & { file?: { file_id: number; storage_key: string; original_filename: string; mime_type: string; byte_size: number; created_at: string } };

export async function uploadProfileAvatar(file: File): Promise<UploadedProfileFile> {
  const formData = new FormData();
  formData.append("avatar", file);
  const res = await api.post<ApiResponse<UploadedProfileFile>>("/graduate/profile/avatar", formData);
  return res.data.data;
}

export async function uploadProfileCv(file: File): Promise<UploadedProfileFile> {
  const formData = new FormData();
  formData.append("cv", file);
  const res = await api.post<ApiResponse<UploadedProfileFile>>("/graduate/profile/cv", formData);
  return res.data.data;
}


export async function deleteProfileCv(): Promise<void> {
  await api.delete("/graduate/profile/cv");
}

export function resolveAssetUrl(value?: string | null): string | null {
  if (!value) return null;
  if (value.startsWith("data:") || value.startsWith("blob:") || value.startsWith("http://") || value.startsWith("https://")) {
    return value;
  }
  const base = getBaseUrl().replace(/\/$/, "");
  if (value.startsWith("/")) {
    return `${base}${value}`;
  }
  return `${base}/${value}`;
}

export function uploadedFileUrl(storageKey: string): string {
  return resolveAssetUrl(`/uploads/${storageKey}`) ?? "";
}
// 3. Employment APIs
export async function getGraduateEmployment(): Promise<EmploymentRecord[]> {
  const res = await api.get<ApiResponse<EmploymentRecord[]>>("/graduate/employment");
  return res.data.data;
}

export async function getEmploymentStatuses(): Promise<EmploymentStatus[]> {
  const res = await api.get<ApiResponse<EmploymentStatus[]>>("/employment-statuses");
  return res.data.data;
}

export async function createGraduateEmployment(payload: {
  employment_status_id: number;
  company_name?: string;
  job_title?: string;
  industry?: string;
  employment_type?: string;
  start_date?: string;
  end_date?: string;
  current_job?: boolean | number;
  monthly_income?: number;
}): Promise<EmploymentRecord> {
  const res = await api.post<ApiResponse<EmploymentRecord>>("/graduate/employment", payload);
  return res.data.data;
}

export async function updateGraduateEmployment(
  id: number,
  payload: Partial<EmploymentRecord>,
): Promise<EmploymentRecord> {
  const res = await api.put<ApiResponse<EmploymentRecord>>(`/graduate/employment/${id}`, payload);
  return res.data.data;
}

export async function deleteGraduateEmployment(id: number): Promise<void> {
  await api.delete(`/graduate/employment/${id}`);
}

// 4. Events APIs
export async function getGraduateEvents(): Promise<EventItem[]> {
  const res = await api.get<ApiResponse<EventItem[]>>("/graduate/events");
  return res.data.data;
}

export async function registerForEvent(eventId: number): Promise<void> {
  await api.post(`/graduate/events/${eventId}/register`);
}

export async function cancelEventRegistration(eventId: number): Promise<void> {
  await api.delete(`/graduate/events/${eventId}/register`);
}

// 5. Surveys APIs
export async function getGraduateSurveys(): Promise<Survey[]> {
  const res = await api.get<ApiResponse<Survey[]>>("/graduate/surveys");
  return res.data.data;
}

export async function getSurveyDetails(surveyId: number): Promise<Survey> {
  const res = await api.get<ApiResponse<Survey>>(`/graduate/surveys/${surveyId}`);
  return res.data.data;
}

export async function submitSurveyAnswers(
  surveyId: number,
  answers: Array<{ question_id: number; answer_text?: string; option_id?: number }>,
): Promise<void> {
  await api.post(`/graduate/surveys/${surveyId}/responses`, { answers });
}

export async function getSurveyResponse(surveyId: number): Promise<SurveyResponse> {
  const res = await api.get<ApiResponse<SurveyResponse>>(`/graduate/surveys/${surveyId}/responses`);
  return res.data.data;
}

export async function getCompletedSurveys(): Promise<Survey[]> {
  const res = await api.get<ApiResponse<Survey[]>>("/graduate/surveys/completed");
  return res.data.data;
}

export async function getManagementSurveys(): Promise<ManagementSurvey[]> {
  const res = await api.get<ApiResponse<ManagementSurvey[]>>("/management/surveys");
  return res.data.data;
}

// 6. Recommendation Letters APIs
export async function getGraduateRecommendations(): Promise<RecommendationRequest[]> {
  const res = await api.get<ApiResponse<RecommendationRequest[]>>("/graduate/recommendations");
  return res.data.data;
}

export async function createRecommendationRequest(purpose: string): Promise<RecommendationRequest> {
  const res = await api.post<ApiResponse<RecommendationRequest>>("/graduate/recommendations", { purpose });
  return res.data.data;
}

export async function downloadRecommendationLetter(requestId: number, filename = "recommendation_letter.pdf"): Promise<void> {
  const response = await api.get(`/graduate/recommendations/${requestId}/download`, {
    responseType: "blob",
  });
  const url = window.URL.createObjectURL(new Blob([response.data], { type: (response.headers["content-type"] as string) || "application/pdf" }));
  const link = document.createElement("a");
  link.href = url;
  link.setAttribute("download", filename);
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
}

export async function getManagementRecommendations(): Promise<ManagementRecommendationRequest[]> {
  const res = await api.get<ApiResponse<ManagementRecommendationRequest[]>>("/management/recommendations");
  return res.data.data;
}

export async function approveManagementRecommendation(requestId: number): Promise<ManagementRecommendationRequest> {
  const res = await api.put<ApiResponse<ManagementRecommendationRequest>>(`/management/recommendations/${requestId}/approve`);
  return res.data.data;
}

export async function rejectManagementRecommendation(requestId: number): Promise<ManagementRecommendationRequest> {
  const res = await api.put<ApiResponse<ManagementRecommendationRequest>>(`/management/recommendations/${requestId}/reject`);
  return res.data.data;
}

export async function uploadManagementRecommendationLetter(requestId: number, file: File): Promise<ManagementRecommendationRequest> {
  const formData = new FormData();
  formData.append("letter", file);
  const res = await api.post<ApiResponse<ManagementRecommendationRequest>>(`/management/recommendations/${requestId}/upload-letter`, formData);
  return res.data.data;
}

// 7. Management Reports & Analytics APIs
export async function getManagementReportsOverview(): Promise<ManagementOverviewReport> {
  const res = await api.get<ApiResponse<ManagementOverviewReport>>("/management/reports/overview");
  return res.data.data;
}

export async function getManagementEmploymentReport(): Promise<ManagementEmploymentReport> {
  const res = await api.get<ApiResponse<ManagementEmploymentReport>>("/management/reports/employment");
  return res.data.data;
}

export async function getManagementGraduateReport(): Promise<ManagementGraduateReport> {
  const res = await api.get<ApiResponse<ManagementGraduateReport>>("/management/reports/graduates");
  return res.data.data;
}

export async function getManagementSurveyReport(): Promise<ManagementSurveyReport> {
  const res = await api.get<ApiResponse<ManagementSurveyReport>>("/management/reports/surveys");
  return res.data.data;
}

export async function getManagementEventReport(): Promise<ManagementEventReport> {
  const res = await api.get<ApiResponse<ManagementEventReport>>("/management/reports/events");
  return res.data.data;
}

export async function getManagementRecommendationReport(): Promise<ManagementRecommendationReport> {
  const res = await api.get<ApiResponse<ManagementRecommendationReport>>("/management/reports/recommendations");
  return res.data.data;
}

export async function exportManagementReport(
  type: "employment" | "graduates" | "surveys" | "events",
): Promise<void> {
  const response = await api.get(`/management/reports/export?type=${type}`, {
    responseType: "blob",
  });
  const url = window.URL.createObjectURL(new Blob([response.data], { type: "text/csv;charset=utf-8;" }));
  const link = document.createElement("a");
  link.href = url;
  const timestamp = new Date().toISOString().replace(/[:.]/g, "-");
  link.setAttribute("download", `maseno_${type}_report_${timestamp}.csv`);
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
}


export type AdministratorDashboardData = { total_users: number; total_graduates: number; total_management_users: number; total_administrators: number; active_accounts: number; inactive_accounts: number; suspended_accounts: number; total_faculties: number; total_departments: number; total_programmes: number; recent_system_activity: Array<{ type: string; label: string; created_at: string }> };
export type AdministratorUser = { user_id: number; name: string; email: string; role_id: number; role: string; role_name: string; account_status_id: number; account_status: string; account_status_name: string; created_at: string; last_login_at?: string | null };
export type AdminRole = { role_id: number; code: string; name: string };
export type AdminStatus = { account_status_id: number; code: string; name: string; is_active: number };
export type Faculty = { faculty_id: number; name: string; code: string; dean_email?: string | null; department_count?: number };
export type Department = { department_id: number; faculty_id: number; name: string; code?: string | null; faculty_name?: string; programme_count?: number };
export type Programme = { programme_id: number; department_id: number; education_level_id: number; name: string; code?: string | null; department_name?: string; faculty_name?: string; education_level_name?: string };
export type SystemSettings = { system_name: string; email_configuration: string; registration_enabled: string };

export async function getAdministratorDashboard(): Promise<AdministratorDashboardData> { const res = await api.get<ApiResponse<AdministratorDashboardData>>('/administrator/dashboard'); return res.data.data; }
export async function getAdministratorUsers(): Promise<AdministratorUser[]> { const res = await api.get<ApiResponse<AdministratorUser[]>>('/administrator/users'); return res.data.data; }
export async function updateUserStatus(id: number, account_status_id: number): Promise<void> { await api.put(`/administrator/users/${id}/status`, { account_status_id }); }
export async function updateUserRole(id: number, role_id: number): Promise<void> { await api.put(`/administrator/users/${id}/role`, { role_id }); }
export async function getRoles(): Promise<AdminRole[]> { const res = await api.get<ApiResponse<AdminRole[]>>('/administrator/roles'); return res.data.data; }
export async function getStatuses(): Promise<AdminStatus[]> { const res = await api.get<ApiResponse<AdminStatus[]>>('/administrator/statuses'); return res.data.data; }
export async function getFaculties(): Promise<Faculty[]> { const res = await api.get<ApiResponse<Faculty[]>>('/administrator/faculties'); return res.data.data; }
export async function createFaculty(payload: Pick<Faculty, 'name' | 'code' | 'dean_email'>): Promise<void> { await api.post('/administrator/faculties', payload); }
export async function updateFaculty(id: number, payload: Pick<Faculty, 'name' | 'code' | 'dean_email'>): Promise<void> { await api.put(`/administrator/faculties/${id}`, payload); }
export async function deleteFaculty(id: number): Promise<void> { await api.delete(`/administrator/faculties/${id}`); }
export async function getDepartments(): Promise<Department[]> { const res = await api.get<ApiResponse<Department[]>>('/administrator/departments'); return res.data.data; }
export async function createDepartment(payload: Pick<Department, 'faculty_id' | 'name' | 'code'>): Promise<void> { await api.post('/administrator/departments', payload); }
export async function updateDepartment(id: number, payload: Pick<Department, 'faculty_id' | 'name' | 'code'>): Promise<void> { await api.put(`/administrator/departments/${id}`, payload); }
export async function deleteDepartment(id: number): Promise<void> { await api.delete(`/administrator/departments/${id}`); }
export async function getProgrammes(): Promise<Programme[]> { const res = await api.get<ApiResponse<Programme[]>>('/administrator/programmes'); return res.data.data; }
export async function createProgramme(payload: { department_id: number; education_level_id: number; programme_name: string; programme_code?: string | null }): Promise<void> { await api.post('/administrator/programmes', payload); }
export async function updateProgramme(id: number, payload: { department_id: number; education_level_id: number; programme_name: string; programme_code?: string | null }): Promise<void> { await api.put(`/administrator/programmes/${id}`, payload); }
export async function deleteProgramme(id: number): Promise<void> { await api.delete(`/administrator/programmes/${id}`); }
export async function getSystemSettings(): Promise<SystemSettings> { const res = await api.get<ApiResponse<SystemSettings>>('/administrator/settings'); return res.data.data; }
export async function updateSystemSettings(payload: Partial<SystemSettings>): Promise<SystemSettings> { const res = await api.put<ApiResponse<SystemSettings>>('/administrator/settings', payload); return res.data.data; }

export async function getUserPreferences(): Promise<Record<string, string>> {
  const res = await api.get<ApiResponse<Record<string, string>>>('/user/preferences');
  return res.data.data;
}

export async function updateUserPreferences(payload: Record<string, any>): Promise<Record<string, string>> {
  const res = await api.put<ApiResponse<Record<string, string>>>('/user/preferences', payload);
  return res.data.data;
}

// Graduate notifications
export async function getGraduateNotifications(): Promise<NotificationItem[]> {
  const res = await api.get<ApiResponse<NotificationItem[]>>('/graduate/notifications');
  return res.data.data;
}

export async function getGraduateNotificationsUnreadCount(): Promise<number> {
  const res = await api.get<ApiResponse<{ unread_count: number }>>('/graduate/notifications/unread-count');
  const data = res.data.data as { unread_count?: number } | number | undefined;
  if (!data) return 0;
  if (typeof data === 'number') return data;
  return (data.unread_count as number) || 0;
}

export async function markGraduateNotificationRead(notificationId: number): Promise<NotificationItem> {
  const res = await api.put<ApiResponse<NotificationItem>>(`/graduate/notifications/${notificationId}/read`);
  return res.data.data;
}

export async function markAllGraduateNotificationsRead(): Promise<{ updated_count: number }> {
  const res = await api.put<ApiResponse<{ updated_count: number }>>('/graduate/notifications/read-all');
  return res.data.data;
}

export async function deleteGraduateNotification(notificationId: number): Promise<{ deleted_count: number }> {
  const res = await api.delete<ApiResponse<{ deleted_count: number }>>(`/graduate/notifications/${notificationId}`);
  return res.data.data;
}

export async function deleteGraduateNotificationsBulk(ids: number[]): Promise<{ deleted_count: number }> {
  // axios supports sending a request body with DELETE using the `data` option
  const res = await api.delete<ApiResponse<{ deleted_count: number }>>('/graduate/notifications', { data: { ids } });
  return res.data.data;
}
