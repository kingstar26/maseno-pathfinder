# Backend analysis and normalized database design (review draft)

## Scope and evidence

This is a requirements-derived design, not an implementation or SQL schema. It is based solely on the frontend in `src/` as inspected on 23 July 2026. The frontend has API calls only for authentication (`/auth/login`, `/auth/register`, and `/auth/change-password`); most resource screens are deliberately disabled/inert. Consequently:

- **Confirmed** means a field, status, validation, or action is implemented or typed in the frontend.
- **Indicated** means it is named in a visible screen, table, dashboard, or module specification, but no input or API contract exists yet.
- Items marked **review decision** must be confirmed before backend implementation; they are not assumed functionality.

No SQL is included.

## Actors and access boundaries

| Actor | Confirmed capabilities |
| --- | --- |
| Graduate | Self-register, sign in, maintain own profile/settings, change password, save/submit a tracer survey, request recommendation letters, view announcements/events/notifications/reports. |
| Management | View graduate records and outcomes; monitor surveys; process recommendation requests; create, edit, publish and delete announcements; view/export reports; maintain own profile/settings. Graduate data is explicitly read-only in this workspace. |
| Administrator | Manage users and faculty/department/programme master data; create, edit, publish and delete announcements; view/export institution-wide reports; maintain own profile/settings. |

The UI names no granular permission model. Store role assignments separately so a later authorization decision does not require changing `users`.

## Normalized entity design

All operational entities should have a stable surrogate identifier and appropriate created/updated audit timestamps. Use foreign keys described below; do not duplicate names such as programme, faculty, employer, or recipient in parent records.

### Identity, access, and staff/graduate profiles

| Entity | Required data / relationships | Evidence |
| --- | --- | --- |
| `users` | email (unique, canonicalized); password hash (never a plaintext password); first name; last name; avatar file reference nullable; last login timestamp nullable; active/account status. A user can have one or more roles through `user_roles`. | Confirmed core `User`, auth, profile, admin user list. |
| `roles` | role code: `graduate`, `management`, `administrator`; display name. | Confirmed role union. |
| `user_roles` | user FK + role FK, unique pair. | Normalized representation of confirmed roles. |
| `graduate_profiles` | one-to-one user FK; middle name, gender, national ID, birth date, phone, county, sub-county, postal address, physical address; admission number, registration number; programme FK; year admitted, graduation year, graduation status, degree awarded, classification, campus; current employment status. | Confirmed profile field labels; programme/year also occur at registration. |
| `staff_profiles` | one-to-one user FK; staff number; designation; office; department FK nullable; faculty FK nullable; official email; office phone; joined date; employment status; supervisor user FK nullable. Applies to management users. | Confirmed management profile fields. |
| `administrator_profiles` | one-to-one user FK; employee number; administrator level; office; official email; department FK nullable; date assigned; system access level. | Confirmed administrator profile fields. |
| `password_change_events` | user FK; changed-at timestamp. Do not store the old/new password or hash history unless explicitly required. | Password last-changed/security activity is displayed as unavailable; password change itself is confirmed. |

`users.email` is the account email. The current UI also exposes profile/offical email fields; whether those may differ from account email is a **review decision**. If they may differ, retain them in the corresponding profile, with clear verification and uniqueness rules.

### University master data and graduate academics

| Entity | Required data / relationships | Evidence |
| --- | --- | --- |
| `faculties` | name, code (unique). | Admin master-data table; profile faculty. |
| `departments` | faculty FK, name, code; unique within a faculty. | Admin table columns and profile department. |
| `programmes` | department FK, name, code, level; unique code. | Admin table, registration selector, profile programme. |
| `graduate_qualifications` | graduate-profile FK; programme FK; admission/registration numbers; admission year; graduation year; graduation status; degree awarded; classification; campus. | Academic-profile fields. |

`graduate_profiles` may keep the explicitly displayed current programme/year for fast display, but the normalized source of academic history should be `graduate_qualifications`. Whether graduates can have multiple Maseno qualifications is not specified; this entity supports the displayed data without forcing an unsupported one-record limitation.

### Career, business, and professional development

| Entity | Required data / relationships | Evidence |
| --- | --- | --- |
| `employment_records` | graduate-profile FK; employer; position/job title; industry; work location; start date; end date nullable; current flag; salary range nullable. | Typed `EmploymentRecord` plus employment/profile field labels. |
| `businesses` | business name; industry; registration number; location; description; verification/status. | Business module fields. |
| `graduate_businesses` | graduate-profile FK + business FK; ownership relationship (the UI says own or co-own); unique pair. | Required to normalize co-ownership. Ownership percentage/role is a **review decision**. |
| `business_documents` | business FK; file reference; document type nullable; uploaded timestamp; verification/status nullable. | Business documents field. |
| `further_study_records` | graduate-profile FK; institution; course; level; country; completion status; start date; end date nullable. | Further-studies module fields. |
| `skills` | skill name (unique/canonicalized). | Skills field. |
| `graduate_skills` | graduate-profile FK + skill FK, unique pair. Proficiency/evidence are **review decisions**. | Normalized many-to-many requirement. |
| `certifications` | graduate-profile FK; certification name; issuer nullable; issued date nullable; expiry date nullable; evidence file reference nullable. | Certifications field; all attributes beyond name are **review decisions**. |
| `files` | storage key/URI, original filename, MIME type, byte size, checksum nullable, uploaded-by user FK, uploaded timestamp. | Profile photo, CV upload, business documents, recommendation downloads require managed file references. |

Career progression, analytics, statistics, charts, and timelines are derived views/queries over these records and should not be stored as independent operational entities unless a later report-snapshot requirement is approved.

### Surveys

| Entity | Required data / relationships | Evidence |
| --- | --- | --- |
| `surveys` | name/title; status; created/available timestamps nullable. | A survey is displayed and management table includes Survey/Status. Multiple survey versions are not otherwise specified. |
| `survey_sections` | survey FK; title; display order; unique order per survey. Initial titles: Personal Information, Employment Information, Further Studies, Business / Entrepreneurship, Skills and Competencies, University Experience, Feedback and Suggestions. | Confirmed seven UI sections. |
| `survey_questions` | section FK; prompt; response type; required flag; display order. | **Review decision:** the UI currently provides one free-text response per section, not individual questions. Do not create questions unless survey authoring/question-level responses are approved. |
| `survey_responses` | survey FK; graduate-profile FK; status (`in_progress` / `submitted`); completion percentage; last saved timestamp nullable; submitted timestamp nullable; unique survey + graduate. | Typed `SurveyResponse`, save/submit UI, management table. |
| `survey_section_responses` | survey response FK; section FK; response text; saved timestamp; completion status; unique response + section. | Confirmed one text area saved per section. |
| `survey_question_answers` | response FK + question FK; typed answer payload. | **Only if** question-level design is approved. |

The frontend requires all sections to be complete before submission. It provides no field-level survey validation; survey content, response types, and versioning are therefore unresolved.

### Recommendation letters

| Entity | Required data / relationships | Evidence |
| --- | --- | --- |
| `recommendation_requests` | graduate-profile FK; purpose; recipient; additional comments nullable; requested timestamp; status. Allowed displayed statuses: `pending`, `approved`, `rejected`, `issued`. | Typed `RecommendationLetter`; request dialog; management queue/actions. |
| `recommendation_letters` | recommendation-request FK (unique); generated/issued timestamp nullable; letter file FK nullable; QR verification token/value nullable and unique. | Typed `issued_at`/`qr_code`, generated-date/download/QR fields. |
| `recommendation_request_status_history` | request FK; status; changed-by user FK nullable; changed-at timestamp; note/rejection reason nullable. | Request tracking and management approve/reject/generate actions; reason is a **review decision**. |

The initial request must have purpose and recipient. It begins `pending`. Approval/rejection, generation, and issuance should be recorded as state transitions; a download is permitted only once a letter file exists.

### Communications, events, notifications, and preferences

| Entity | Required data / relationships | Evidence |
| --- | --- | --- |
| `announcements` | title; body/content (**review decision:** UI only displays title); category nullable; status (draft/published); published timestamp nullable; created/updated/deleted audit fields; created-by user FK. | Graduate feed and management/admin CRUD/publish table. |
| `announcement_audiences` | announcement FK; target role FK; unique pair. | Announcements have an Audience column; role-targeting is the only audience detail evidenced. |
| `announcement_reads` | announcement FK + user FK; read timestamp; unique pair. | Read-status filtering is indicated. |
| `events` | title; starts-at; location; ends-at nullable; description nullable. | Typed `EventItem`; upcoming/past event calendar. |
| `event_registrations` | event FK + graduate-profile FK; registration status/date; unique event + graduate. | Event registration is indicated; status/date are review decisions. |
| `notifications` | recipient user FK; title; body; category nullable; created timestamp; source type/id nullable; read timestamp nullable. | Typed `NotificationItem`, categories/unread/mark-as-read. |
| `user_notification_preferences` | user FK (unique); email notifications; browser/push notifications; survey reminders. | Confirmed settings toggles. |
| `user_privacy_preferences` | user FK (unique); profile visibility; activity visibility. | Confirmed settings toggles. |
| `user_display_preferences` | user FK (unique); theme (`light`/`dark`); compact-mode flag. | Confirmed device-oriented settings; whether to persist server-side is a review decision. |

Announcements and events should not automatically imply notification creation: delivery-trigger rules are not defined by the frontend and require a review decision.

### Reports and auditability

| Entity | Required data / relationships | Evidence |
| --- | --- | --- |
| `report_exports` | requested-by user FK; report category; period/filter definition; format (`pdf`/`excel`); created timestamp; file FK nullable; status nullable. | Report tables and PDF/Excel export controls. |
| `activity_events` | actor user FK nullable; subject entity type/id; action; occurred timestamp; metadata nullable. | Activity history, security activity, audit-oriented management functions. Exact audit scope is a review decision. |

Report counts and charts (graduates, employment, survey completion, recommendation status, faculty, graduation trend, user roles/active status) should be generated from source records. Persist only an export/job when a downloadable artifact is required.

## Relationship map

```text
users --< user_roles >-- roles
users -- 0..1 graduate_profiles --< graduate_qualifications >-- programmes >-- departments >-- faculties
users -- 0..1 staff_profiles / administrator_profiles
graduate_profiles --< employment_records, further_study_records, certifications
graduate_profiles --< graduate_businesses >-- businesses --< business_documents
graduate_profiles --< graduate_skills >-- skills
graduate_profiles --< survey_responses --< survey_section_responses >-- survey_sections >-- surveys
graduate_profiles --< recommendation_requests -- 0..1 recommendation_letters
recommendation_requests --< recommendation_request_status_history
announcements --< announcement_audiences >-- roles
announcements --< announcement_reads >-- users
events --< event_registrations >-- graduate_profiles
users --< notifications, report_exports, activity_events
```

## Workflows and state rules

1. **Registration and authentication:** a graduate submits first/last name, programme, graduation year, Gmail email, password, and password confirmation. On success, create the user, graduate role/profile/academic link, and authenticated session/token. Login updates last-login time. The only account role created through public registration is graduate.
2. **Profile maintenance:** the authenticated user edits the role-specific profile and optional photo. Save is explicit; the UI must not persist partial profile edits. The graduate profile exposes an employment summary, but employment history remains its own collection.
3. **Password change:** authenticated user supplies current password and a valid confirmed replacement. Verify current secret server-side, replace password hash, and record changed time; revoke/retain existing sessions is not specified.
4. **Tracer survey:** create/load one response per graduate and survey; save a section as draft; calculate progress from completed sections; permit submission only at 100%; retain submitted time and show response history. Editing/resubmission after submission is not specified.
5. **Recommendation request:** graduate creates a pending request with required purpose/recipient and optional comments. Management views it and transitions it through approval/rejection/generation/issuance. Issuance associates a generated document and QR verification value.
6. **Announcements:** management/administrator create, edit, delete, and publish announcements addressed to role audiences. Graduates filter/search and record read state. Publishing rules, deletion retention, and cross-role visibility are not specified.
7. **Master data:** administrators manage faculties, departments, programmes, and users. Referential-integrity behavior when a master record has graduates/programmes is a review decision.
8. **Reporting:** management sees graduate/employment/survey/recommendation aggregates; administrators additionally see user aggregates. Both can export PDF/Excel. Filters/date ranges and exact report definitions are not provided.

## Confirmed validation and integrity rules

| Area | Backend rule implied by UI |
| --- | --- |
| Account email | Required at register/login; UI restricts it to a Gmail-form address. Treat this domain restriction as a business-policy review item before enforcing it permanently; canonicalize case/whitespace and enforce uniqueness. |
| Registration names | Required first and last name. Profile uses letters, spaces, apostrophes, and hyphens only. |
| Programme/year | Both required on registration. Programme must reference an existing programme. Graduation year must be an integer from 1990 through current calendar year + 1. |
| Password at registration/profile | At least 8 characters, uppercase, lowercase, digit, special character, confirmation matches. Profile password change requires the current password and strength of at least four of those five checks (which necessarily includes length plus three others); use the stricter registration rule unless policy says otherwise. |
| Graduate profile | First/last name valid; national ID exactly 8 digits; phone valid only as `07########`, `01########`, `+2547########`, or `+2541########`; email syntactically valid. UI labels those five profile fields required for every role, although national ID/phone are not otherwise collected at registration. |
| Avatar | PNG/JPEG only, maximum 2 MiB. Store a file reference, not the base64 browser preview. |
| Survey | All seven sections must be complete before submission; one response per graduate/survey. |
| Recommendation | Purpose and recipient are required/nonblank; comments optional; initial status pending. |
| Relational integrity | Department belongs to faculty; programme belongs to department; profile/recommendation/survey/employment records belong to their graduate; uniqueness of join pairs listed above. |

## Decisions required before implementation

1. Is a Gmail-only account policy intentional, and may profile/official email differ from login email?
2. Which profile fields are mandatory by role? The UI currently validates national ID and phone for management and administrator profiles too.
3. Are a graduate's faculty/department derived exclusively through programme, or can they differ historically? Are multiple Maseno qualifications supported?
4. What controlled values apply to gender, graduation/employment/business/account statuses, programme level, role/access level, categories, and report periods?
5. What are the full survey questions, response types, requiredness, scoring/completion rules, versions, and whether submitted responses can be amended?
6. What fields/content, audience types, publishing schedule, and retention rules apply to announcements and events?
7. What recommendation-letter templates, rejection reasons, signer/approver identity, QR verification behavior, and document retention are required?
8. Which reports are persisted exports versus on-demand queries, and what filter/date fields are required?
9. Are notification preferences and UI appearance server-wide preferences or deliberately device-local only?
10. What authorization matrix, audit-retention policy, account lifecycle (verification, deactivate/reactivate), and file-storage/retention policy are required?

## Frontend implementation gaps to preserve in planning

- No database/API contract exists for collections; `useBackendCollection` is intentionally disabled and returns no records.
- User/master-data/announcement/report controls are visual placeholders and supply no create/edit payload definitions.
- The profile and settings screens presently persist much data in browser local storage; their fields still inform requirements but are not current backend persistence behavior.
- The frontend's own domain types omit several screen-indicated entities (business, further study, programme master data, announcements), so this document treats those as indicated requirements rather than existing API contracts.
