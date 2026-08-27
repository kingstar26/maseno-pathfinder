// Shared domain types — mirror the MUGTS MySQL schema.

export type UserRole = "graduate" | "administrator" | "management";

export interface User {
  id: string;
  first_name: string;
  last_name: string;
  email: string;
  role: UserRole;
  avatar_url?: string | null;
  last_login_at?: string | null;
  programme?: string | null;
  graduation_year?: number | null;
}

export interface GraduateProfile {
  user_id?: number;
  graduate_profile_id?: number | null;
  first_name: string;
  middle_name?: string | null;
  last_name: string;
  email: string;
  gender?: string | null;
  national_id?: string | null;
  date_of_birth?: string | null;
  phone_number?: string | null;
  county?: string | null;
  sub_county?: string | null;
  postal_address?: string | null;
  physical_address?: string | null;
  avatar_file_id?: number | null;
  cv_file_id?: number | null;
  avatar_url?: string | null;
  cv_file_url?: string | null;
  password_changed_at?: string | null;
  cv_information?: {
    cv_file_id?: number | null;
    file?: {
      file_id: number;
      original_filename: string;
      mime_type: string;
      byte_size: number;
      created_at: string;
      storage_key: string;
    } | null;
  };
  profile_completion_percentage?: number;
  profile_completion?: number;
  admission_number?: string | null;
  registration_number?: string | null;
  year_of_admission?: number | null;
  year_of_graduation?: number | null;
  programme?: string | null;
  department?: string | null;
  faculty?: string | null;
  degree_awarded?: string | null;
}

export interface EmploymentStatus {
  employment_status_id: number;
  code: string;
  name: string;
  is_active: number;
}

export interface EmploymentRecord {
  employment_record_id: number;
  id?: string;
  graduate_profile_id?: number;
  graduate_id?: string;
  employment_status_id?: number;
  employment_status_name?: string | null;
  employment_status?: string | null;
  company_name: string;
  employer?: string;
  job_title: string;
  position?: string;
  industry?: string | null;
  employment_type?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  current_job?: number | boolean;
  is_current?: boolean;
  monthly_income?: number | string | null;
  created_at?: string;
  updated_at?: string;
}

export interface EventItem {
  event_id: number;
  id?: string;
  title: string;
  description?: string | null;
  event_type?: string | null;
  venue?: string | null;
  location?: string;
  start_datetime: string;
  starts_at?: string;
  start_date?: string;
  end_datetime?: string | null;
  ends_at?: string | null;
  end_date?: string | null;
  registration_deadline?: string | null;
  max_attendees?: number | null;
  capacity?: number | null;
  registered_count?: number;
  available_slots?: number | null;
  is_registered?: boolean;
  registration_status?: string | null;
}

export interface SurveyQuestionOption {
  survey_question_option_id: number;
  option_id?: number;
  label: string;
  value: string;
  display_order: number;
}

export interface SurveyQuestion {
  survey_question_id: number;
  question_id?: number;
  prompt: string;
  response_type: string;
  is_required: number | boolean;
  display_order: number;
  options?: SurveyQuestionOption[];
}

export interface SurveySection {
  survey_section_id: number;
  section_id?: number;
  title: string;
  display_order: number;
  is_required: number | boolean;
  questions: SurveyQuestion[];
}

export interface Survey {
  survey_id: number;
  id?: string;
  name: string;
  title?: string;
  description?: string | null;
  is_active: number;
  available_from?: string | null;
  available_until?: string | null;
  invitation_status?: string;
  completion_status?: "completed" | "pending";
  is_completed?: boolean;
  sections?: SurveySection[];
}

export interface SurveyResponse {
  survey_response_id: number;
  submitted_at?: string | null;
  answers: Array<{
    question_id: number;
    option_id?: number | null;
    answer_text?: string | null;
  }>;
}

export interface ManagementSurveySubmissionAnswer {
  question_id: number;
  prompt: string;
  option_id: number | null;
  option_label: string | null;
  answer_text: string | null;
  answer_number: string | null;
}

export interface ManagementSurveySubmission {
  survey_response_id: number;
  survey_id: number;
  graduate_profile_id: number;
  survey_title: string;
  first_name: string;
  last_name: string;
  graduate_email: string;
  admission_number: string | null;
  status_name: string | null;
  submitted_at: string | null;
  completed_at: string | null;
  answers: ManagementSurveySubmissionAnswer[];
}

export interface ManagementSurvey extends Survey {
  completed_submissions: ManagementSurveySubmission[];
  completed_submissions_count: number;
}

export interface RecommendationLetterFile {
  recommendation_letter_id: number;
  recommendation_request_id: number;
  issued_by: number;
  file_id: number | null;
  letter_title: string | null;
  issued_date: string;
  original_filename: string | null;
  mime_type: string | null;
  byte_size: number | null;
  download_url?: string;
}

export interface RecommendationRequest {
  request_id: number;
  recommendation_request_id?: number;
  id?: string;
  graduate_profile_id?: number;
  purpose: string;
  status: string;
  request_status?: string;
  request_date?: string;
  requested_at?: string;
  approved_date?: string | null;
  processed_at?: string | null;
  letter_file?: RecommendationLetterFile | null;
  letters?: RecommendationLetterFile[];
  created_at?: string;
  updated_at?: string;
}

export interface ManagementRecommendationRequest extends RecommendationRequest {
  graduate_user_id?: number;
  graduate_name?: string;
  graduate_email?: string;
  graduate_phone?: string | null;
  admission_number?: string | null;
  registration_number?: string | null;
  programme?: string | null;
}


export interface GraduateDashboardData {
  profile: {
    first_name: string;
    last_name: string;
    email: string;
    avatar_url?: string | null;
    profile_completion?: number;
    profile_completion_percentage?: number;
  };
  employment: {
    employment_status: string | null;
    company_name: string | null;
    job_title: string | null;
    industry: string | null;
    employment_type: string | null;
    current_job: number | null;
  };
  recommendations: {
    total_recommendation_requests?: number;
    total_requests?: number;
    pending_requests?: number;
    pending_requests_count?: number;
    approved_requests?: number;
    approved_requests_count?: number;
    latest_request_status?: string | null;
  };
  surveys: {
    total_assigned?: number;
    total_assigned_surveys?: number;
    completed_surveys?: number;
    pending_surveys?: number;
  };
  events: Array<{
    event_id: number;
    title: string;
    venue?: string | null;
    start_datetime: string;
    registration_status: string;
  }>;
}

export interface NotificationItem {
  id: string;
  notification_id?: number;
  title: string;
  body?: string;
  message?: string;
  created_at: string;
  read?: boolean;
  is_read?: number;
}

// Management Analytics & Report Interfaces

export interface ManagementOverviewReport {
  total_graduates: number;
  graduates?: number;
  total_registered_users?: number;
  total_employment_records?: number;
  currently_employed_graduates?: number;
  employed_graduates?: number;
  employed_count?: number;
  self_employed_graduates?: number;
  self_employed_count?: number;
  further_studies_graduates?: number;
  further_studies_count?: number;
  unemployed_graduates?: number;
  unemployed_count?: number;
  employment_rate: number;
  total_businesses?: number;
  businesses?: number;
  total_surveys?: number;
  surveys?: number;
  completed_surveys?: number;
  survey_completion_percentage?: number;
  survey_completion_rate: number;
  total_recommendation_requests?: number;
  approved_recommendations?: number;
  pending_recommendations?: number;
  rejected_recommendations?: number;
  recommendation_statistics?: {
    total: number;
    approved: number;
    pending?: number;
    rejected?: number;
  };
  recommendations?: {
    total: number;
    approved: number;
    pending?: number;
    rejected?: number;
  };
  total_events?: number;
  events?: number;
  total_event_registrations?: number;
  event_statistics?: {
    total_events?: number;
    published_events?: number;
    upcoming_events?: number;
    total_registrations?: number;
  };
}

export interface EmploymentStatusBreakdownItem {
  employment_status_id?: number;
  status: string;
  code?: string;
  count: number;
  percentage?: number;
}

export interface FacultyEmploymentItem {
  faculty_id?: number;
  faculty: string;
  graduates: number;
  employed: number;
  rate: number;
}

export interface ProgrammeEmploymentItem {
  programme_id?: number;
  programme: string;
  faculty?: string;
  graduates: number;
  employed: number;
  rate: number;
}

export interface ManagementEmploymentReport {
  total_employment_records?: number;
  employed_count?: number;
  currently_employed_graduates?: number;
  unemployed_count?: number;
  unemployed_graduates?: number;
  self_employed_count?: number;
  self_employed_graduates?: number;
  further_studies_count?: number;
  further_studies_graduates?: number;
  employment_percentage: number;
  employment_rate?: number;
  status_breakdown: EmploymentStatusBreakdownItem[];
  employment_by_faculty: FacultyEmploymentItem[];
  faculty_statistics?: FacultyEmploymentItem[];
  employment_by_programme: ProgrammeEmploymentItem[];
  programme_statistics?: ProgrammeEmploymentItem[];
}

export interface GraduateDistributionItem {
  faculty_id?: number;
  programme_id?: number;
  faculty?: string;
  programme?: string;
  year?: number;
  graduation_year?: number;
  county?: string;
  count: number;
  percentage?: number;
}

export interface ManagementGraduateRecord {
  graduate_profile_id: number;
  user_id: number;
  first_name: string;
  last_name: string;
  admission_number: string | null;
  faculty: string | null;
  programme: string | null;
  year_of_graduation: number | null;
  employment_status: string | null;
  recommendation_status: string | null;
}

export interface ManagementGraduateReport {
  graduates?: ManagementGraduateRecord[];
  graduates_by_faculty: GraduateDistributionItem[];
  by_faculty?: GraduateDistributionItem[];
  faculty_distribution?: GraduateDistributionItem[];

  graduates_by_programme: GraduateDistributionItem[];
  by_programme?: GraduateDistributionItem[];
  programme_distribution?: GraduateDistributionItem[];

  graduates_by_year: GraduateDistributionItem[];
  by_graduation_year?: GraduateDistributionItem[];
  by_year?: GraduateDistributionItem[];
  graduation_year_distribution?: GraduateDistributionItem[];

  graduates_by_county: GraduateDistributionItem[];
  by_county?: GraduateDistributionItem[];
  county_distribution?: GraduateDistributionItem[];
}

export interface SurveyQuestionOptionBreakdown {
  survey_question_option_id: number;
  option_id?: number;
  label: string;
  value: string;
  count: number;
  percentage: number;
}

export interface SurveyQuestionAnalytics {
  survey_question_id: number;
  question_id?: number;
  prompt: string;
  response_type: string;
  section_title?: string;
  total_responses: number;
  option_breakdown?: SurveyQuestionOptionBreakdown[];
}

export interface SurveyAnalyticsItem {
  survey_id: number;
  title: string;
  name?: string;
  is_active?: number;
  available_from?: string | null;
  available_until?: string | null;
  total_invited: number;
  total_completed: number;
  completion_rate: number;
  completion_percentage?: number;
  questions?: SurveyQuestionAnalytics[];
}

export interface ManagementSurveyReport {
  total_surveys: number;
  available_surveys?: number;
  total_invitations: number;
  invitations_sent?: number;
  completed_responses: number;
  total_completed?: number;
  completion_rate: number;
  completion_percentage?: number;
  question_statistics?: SurveyQuestionAnalytics[];
  surveys?: SurveyAnalyticsItem[];
}

export interface EventAnalyticsItem {
  event_id: number;
  title: string;
  event_type?: string | null;
  venue?: string | null;
  start_datetime: string;
  end_datetime?: string | null;
  max_attendees?: number | null;
  is_active?: number;
  registration_count?: number;
  registrations_count?: number;
  attended_count?: number;
  attendance_rate?: number;
}

export interface ManagementEventReport {
  total_events: number;
  published_events: number;
  upcoming_events: number;
  registration_statistics: number;
  registration_counts?: number;
  total_registrations?: number;
  attendance_statistics: {
    total_registrations?: number;
    total_attended?: number;
    overall_attendance_rate?: number;
  } | number;
  total_attended?: number;
  overall_attendance_rate?: number;
  events?: EventAnalyticsItem[];
}

export interface ManagementRecommendationReport {
  total_requests: number;
  total?: number;
  pending_requests: number;
  pending?: number;
  approved_requests: number;
  approved?: number;
  rejected_requests: number;
  rejected?: number;
  average_processing_time: string | number;
  average_processing_time_hours?: number;
}
