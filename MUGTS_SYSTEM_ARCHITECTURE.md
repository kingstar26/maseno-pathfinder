# MUGTS System Architecture Reference

> Evidence base: repository source and `schema/maseno_graduate_tracer.sql`, inspected 29 July 2026.  This is a map of implemented code, not a target design. “Not connected” means the current React route/component makes no request to the corresponding PHP endpoint.

## 1. Runtime and entry points

| Layer | Entry point | Observed responsibility |
|---|---|---|
| React application | `frontend/src/start.ts`, `server.ts`, `router.tsx` | TanStack Start/Vite bootstrap and `QueryClient` router context. |
| Route shell | `frontend/src/routes/__root.tsx` | Global application shell; generated route tree is consumed by `router.tsx`. |
| PHP API | `backend/index.php` | Loads helpers, database, middleware and every controller; applies CORS; dispatches by method/path. |
| API routing | `backend/routes/api.php` | Single imperative REST dispatcher. |
| Database | `schema/maseno_graduate_tracer.sql` | MySQL schema for database `maseno_graduate_tracer`. |

The Axios base URL is `VITE_API_BASE_URL` when supplied, otherwise `/maseno-pathfinder/backend`. The request interceptor sends `Authorization: Bearer <localStorage mugts.token>`.

## 2. Frontend structure (`frontend/src`)

### Route and access model

File routes are TanStack Router routes. `_authenticated.tsx` restores the persisted user, redirects unauthenticated visitors to `/login`, and redirects a role away from the other role's URL prefix. It is a client-side route guard; server-side authorization is separately enforced by PHP controllers.

| File | Purpose | Connected module/API |
|---|---|---|
| `routes/index.tsx` | Landing/entry route. | Navigation only. |
| `routes/login.tsx` | Sign-in form. | `POST /auth/login` via `AuthContext`. |
| `routes/register.tsx` | Graduate registration form and programme selector. | `GET /programmes`, `POST /auth/register`. |
| `routes/_authenticated.tsx` | Authenticated layout and role-prefix redirects. | Local auth state; no `GET /auth/me` call. |
| `routes/_authenticated/dashboard.tsx` | Graduate dashboard. | `GET /graduate/dashboard`. |
| `routes/_authenticated/profile.tsx` | Graduate profile page. | `ProfileWorkspacePage`: profile APIs below. |
| `routes/_authenticated/employment.tsx` | Employment page. | `GraduateModules.EmploymentPage`: employment APIs. |
| `routes/_authenticated/business.tsx` | Entrepreneurship page. | `GraduateModules.EntrepreneurshipPage`: business APIs. |
| `routes/_authenticated/survey.tsx` | Tracer-survey page. | `GraduateModules.TracerSurveyPage`: survey APIs. |
| `routes/_authenticated/recommendations.tsx` | Recommendation page. | `GraduateModules.RecommendationLettersPage`. |
| `routes/_authenticated/events.tsx` | Events page. | `GraduateModules.EventsPage`. |
| `routes/_authenticated/notifications.tsx` | Notifications page. | `GraduateModules.NotificationsPage`. |
| `routes/_authenticated/{announcements,further-studies,reports,settings}.tsx` | Generic module routes. | `ModulePage`/workspace settings; no matching live collection API call. Settings can call password change through its form. |
| `routes/_authenticated/management/dashboard.tsx` | Management dashboard route. | Placeholder `WorkspaceDashboard`; **not connected** to `GET /management/dashboard`. |
| `routes/_authenticated/management/{graduates,surveys,recommendations,announcements}.tsx` | Management table routes. | `ResourceTablePage`, whose hook is deliberately disabled; no API requests. |
| `routes/_authenticated/management/reports.tsx` | Management analytics route. | `ManagementAnalytics`: six report APIs. |
| `routes/_authenticated/management/{profile,settings}.tsx` | Management profile/settings routes. | Profile is local-storage-backed for this role; settings is local-storage-backed. |
| `routes/_authenticated/administrator/dashboard.tsx` | Administrator dashboard. | `GET /administrator/dashboard`. |
| `routes/_authenticated/administrator/users.tsx` | User, role and status administration. | Administrator user/roles/status APIs. |
| `routes/_authenticated/administrator/academics.tsx` | Faculty, department and programme CRUD. | Administrator academic APIs. |
| `routes/_authenticated/administrator/settings.tsx` | System settings form. | `GET/PUT /administrator/settings`. |
| `routes/_authenticated/administrator/{profile,reports,announcements}.tsx` | Workspace routes. | Profile local storage; reports/announcements inert `ResourceTablePage`. |

### Components

| File/folder | Purpose | Connected module |
|---|---|---|
| `components/GraduateModules.tsx` | Exports graduate-facing recommendation, survey, employment, events and notification page components. | Direct API use for those implemented page components. |
| `components/ManagementAnalytics.tsx` | Fetches and renders overview, employment, graduate, survey, event and recommendation management report data; supports report CSV export. | `/management/reports/*`. |
| `components/AdministratorDashboard.tsx` | Administrator statistics/activity UI. | `/administrator/dashboard`. |
| `components/AdministratorUsers.tsx` | Loads users, roles and statuses; changes status/role. | `/administrator/users`, `/roles`, `/statuses`. |
| `components/AdministratorAcademics.tsx` | Tabs and forms for academic hierarchy CRUD. | `/administrator/faculties`, `/departments`, `/programmes`. |
| `components/AdministratorSettings.tsx` | Reads and saves three system-setting values. | `/administrator/settings`. |
| `components/ModulePage.tsx`, `module-specs.ts` | Static descriptions/field labels for generic graduate modules. | No backend request. |
| `components/layout/` | `DashboardLayout`, `Header`, `Sidebar`, `Logo`; navigation and shared visual layout. | Uses auth/context and route configuration. |
| `components/role-workspace/config.ts` | Typed workspace navigation and role home URLs. | No API request. |
| `components/role-workspace/ProfileWorkspacePage.tsx` | Graduate profile fetch/update/avatar upload; for management/admin merges local-storage profile data. | Graduate: `/graduate/profile`, avatar; staff: local storage only. |
| `components/role-workspace/WorkspaceForms.tsx` | Settings/profile preference forms. | Password action through AuthContext; preferences stored locally. |
| `components/role-workspace/ResourceTablePage.tsx` + `use-backend-collection.ts` | Reusable table shell. Hook is explicitly `enabled: false` and returns no records. | No endpoint connected. |
| `components/role-workspace/WorkspaceDashboard.tsx` | Skeleton/statistic placeholder dashboard. | No endpoint connected. |
| `components/ui/` | Generated shadcn/Radix-style primitive components (buttons, dialogs, forms, tables, charts, etc.). | Presentation primitives, no business API. |

### Libraries, context, types, hooks and assets

| File | Purpose | Connected module |
|---|---|---|
| `lib/api.ts` | Axios instance and typed functions for programmes, graduate profile/dashboard/employment/events/surveys/recommendations, management reports, and administrator APIs. | Main frontend API contract. |
| `lib/dashboard.ts` | Role-to-dashboard path helper. | Authenticated redirect/navigation. |
| `lib/mock-auth.ts` | Fallback mock auth implementation. | Present but currently bypassed because `isApiConfigured()` returns `true`. |
| `lib/error-capture.ts`, `error-page.ts` | Client error recording/error presentation. | UI error handling. |
| `lib/utils.ts` | Shared CSS/class utility. | UI. |
| `context/auth-context.tsx` | Login/register/change-password, token/user persistence, logout and `useAuth`. | Auth endpoints; localStorage keys `mugts.user`, `mugts.token`. |
| `context/theme-context.tsx` | Theme state/provider. | Browser/local state. |
| `types/index.ts` | TypeScript domain/API payload types. | Used by `api.ts` and UI. |
| `hooks/use-mobile.tsx` | Responsive viewport hook. | Layout. |
| `assets/maseno-crest.jpeg` | Brand asset. | Logo/layout. |

## 3. Backend structure

`index.php` is the composition root. It includes controller files rather than using classes/namespaces. `routes/api.php` calls controller functions directly and ends unknown paths with JSON 404.

| Area | Files | Existing behavior |
|---|---|---|
| Controllers | `backend/controllers/*.php` | Procedural request handlers organized by graduate, management and administrator domain. |
| Routes | `backend/routes/api.php` | Method/path matching, including numeric-ID regular expressions. |
| Middleware | `middleware/auth.php` | Validates hashed bearer-session token, active account status, expiry/revocation; updates `last_used_at`; enforces role. |
| Helpers | `auth.php`, `response.php`, `utilities.php`, `validation.php` | Bearer parsing/token hash, JSON responses, request/body/path/file utilities and validation. |
| Database | `config/db.php` | Lazily cached `mysqli` connection to local MySQL (`root`, blank password, `maseno_graduate_tracer`, UTF-8 MB4). |

### Controller inventory

All listed protected handlers call the authentication middleware and role check in their controller paths. Table lists are direct operational tables and joins used by that controller; an endpoint may additionally use lookup tables shown in the schema section.

| Controller | Purpose/endpoints handled | Required role | Principal tables used |
|---|---|---|---|
| `auth.php` | Register, login, change password, authenticated-user lookup. | Register/login public; change/me authenticated (change password permits authenticated roles). | `users`, `roles`, `account_statuses`, `graduate_profiles`, `graduate_qualifications`, `user_sessions`, `password_history`, `programmes`. |
| `dashboard.php` | Graduate dashboard aggregate. | graduate | `users`, `graduate_profiles`, `employment_records`, `recommendation_requests`, `survey_invitations`, `events`, `event_registrations`. |
| `profile.php` | Read/update graduate profile, avatar and CV uploads. | graduate | `users`, `person_profiles`, `graduate_profiles`, `graduate_qualifications`, `files`, academic tables. |
| `qualifications.php` | Graduate qualification CRUD. | graduate | `graduate_profiles`, `graduate_qualifications`, `programmes`, `graduation_statuses`, `degree_classifications`. |
| `programmes.php` | Public programme list and employment-status lookup. | Programme list public; employment statuses endpoint is routed without a PHP role check at router level—controller behavior is source authority. | `programmes`, `departments`, `faculties`, `education_levels`, `employment_statuses`. |
| `employment.php` | Graduate employment CRUD. | graduate | `graduate_profiles`, `employment_records`, `employment_statuses`. |
| `entrepreneurship.php` | Graduate business CRUD. | graduate | `graduate_profiles`, `businesses`. |
| `events.php` | Graduate event listing/detail/registration/cancellation. | Listing route is available at `/events`; graduate actions require graduate. | `events`, `event_registrations`, `graduate_profiles`, `users`. |
| `surveys.php` | Available/completed surveys, details, start/submit/read responses. | graduate for graduate paths; `/surveys/:id` route is separately exposed. | `surveys`, `survey_sections`, `survey_questions`, `survey_question_options`, `survey_invitations`, `survey_responses`, `survey_answers`, response statuses. |
| `recommendations.php` | Graduate recommendation request CRUD, details and secure file download. | graduate | `recommendation_requests`, `recommendation_letters`, `files`, `graduate_profiles`. |
| `notifications.php` | List, unread count and read-state actions. | graduate | `notifications`. |
| `management_dashboard.php` | Management dashboard aggregate. | management | `management_profiles` and outcome/activity tables. |
| `management_recommendations.php` | List/detail, approve/reject and letter upload. | management | `recommendation_requests`, `recommendation_letters`, `files`, `graduate_profiles`, `users`, `notifications`. |
| `management_surveys.php` | Survey CRUD; section/question/option creation and publish. | management | `surveys`, `survey_sections`, `survey_questions`, `survey_question_options`, `question_types`, `survey_invitations`. |
| `management_events.php` | Event CRUD, publish and attendee list; also management announcement list/create/publish/delete handlers. | management | `events`, `event_registrations`, `announcements`, `graduate_profiles`, `users`. |
| `management_reports.php` | Overview/subject reports and CSV export. | management | Graduate, employment, business, survey, event and recommendation tables. |
| `administrator_dashboard.php` | User/status/academic counts and recent activity. | administrator | `users`, `roles`, `account_statuses`, `faculties`, `departments`, `programmes`, sessions. |
| `administrator_users.php` | List/detail users, change status and role. | administrator | `users`, `roles`, `account_statuses`, profile tables. |
| `administrator_roles.php` | Role/status lists. | administrator | `roles`, `account_statuses`. |
| `administrator_academics.php` | Faculty, department and programme CRUD. | administrator | `faculties`, `departments`, `programmes`, `education_levels`. |
| `administrator_settings.php` | Reads/writes settings exposed by this controller. | administrator | Source has no separate `system_settings` table in supplied schema; inspect controller for its persistence mechanism before assuming durable database storage. |

## 4. Database architecture

### Tables

| Table | Purpose and important columns | Relationships |
|---|---|---|
| `account_statuses` | Account lifecycle lookup: `account_status_id`, `code`, `name`, `is_active`. | `users.account_status_id`. |
| `roles` | Role lookup: `role_id`, `code`, `name`. | `users.role_id`. |
| `users` | Identity/auth account: `user_id`, role/status FKs, name, email, `password_hash`, `avatar_file_id`, login timestamps. | Parent of person, role-specific profiles, sessions, notifications, files. |
| `person_profiles` | Shared personal fields: `user_id`, middle name, gender, national ID, DOB, phone. | One-to-one with `users`. |
| `graduate_profiles` | Graduate contact profile: `graduate_profile_id`, unique `user_id`, addresses, `cv_file_id`. | One-to-one user; parent of career/activity records. |
| `graduate_qualifications` | Programme/graduation record: profile/programme/status/classification FKs, admission/registration numbers, years. | Many qualifications per graduate profile. |
| `management_profiles` | Staff scope: `user_id`, optional faculty/department, designation, employee number, phone. | One-to-one management user; references academic hierarchy. |
| `administrator_profiles` | Administrator staff data: `user_id`, employee number, department, phone. | One-to-one administrator user. |
| `user_sessions` | Token sessions: `user_id`, `token_hash`, issue/expiry/revocation/usage timestamps. | Many per user; middleware queries it. |
| `password_history` | Historic password hashes and `changed_at`. | Many per user. |
| `user_preferences` | Key/value preferences by user. | Many per user. |
| `files` | Uploaded-file metadata: uploader, storage key/name, MIME, size, checksum. | Referenced by avatar/CV/letter/report records. |
| `faculties` | Academic top-level: ID, unique code/name, dean email, active flag. | Parent of departments; referenced by management profiles/announcement recipients. |
| `departments` | Faculty children: ID, `faculty_id`, name/code. | Parent of programmes; optional management scope. |
| `education_levels` | Degree-level lookup: ID, code/name/order/active. | `programmes.education_level_id`. |
| `programmes` | Academic programme: department/level FKs, code/name/active. | Used by qualifications and programme-targeted announcements. |
| `degree_classifications` | Degree result lookup. | Qualification FK. |
| `graduation_statuses` | Graduation lifecycle lookup. | Qualification FK. |
| `employment_statuses` | Employment state lookup. | Employment record FK. |
| `employment_records` | Career records: profile/status FKs, employer/job/industry/type/dates/current/income. | Many per graduate. |
| `businesses` | Entrepreneurship record: profile FK, name/type/registration/location/employees/year. | Many per graduate. |
| `events` | Management-created event: creator, title/body/type/venue/times/deadline/capacity/active. | Parent of registrations; creator is user. |
| `event_registrations` | Event/graduate association: event/profile FKs, status, registration/attendance. | Unique `(event_id, graduate_profile_id)`. |
| `surveys` | Survey definition: `survey_id`, name, active and availability window. | Parent of sections/invitations. |
| `survey_sections` | Ordered survey section: survey FK, title/order/required. | Parent of questions. |
| `survey_questions` | Question: section FK, prompt, response type, required/order. | Parent of options/answers. |
| `survey_question_options` | Select-option label/value/order. | Many per question. |
| `question_types` | Question-type lookup. | Schema lookup; question field is `response_type` text. |
| `survey_response_statuses` | Invitation response state lookup. | `survey_invitations` FK. |
| `survey_invitations` | Survey assignment: survey/profile/status FKs, sent/completed times. | One/more invitations per pairing as schema permits; parent of response. |
| `survey_responses` | Response instance: invitation FK, submitted timestamp. | Parent of answers. |
| `survey_answers` | Answer data: response/question/optional option FKs; text/number values. | Many per response. |
| `recommendation_requests` | Graduate request: profile FK, purpose/status, request/process dates, processor. | Parent of letters; processor is user. |
| `recommendation_letters` | Issued letter: request/issuer/optional file FKs, title/date. | Many per request. |
| `notifications` | Per-user notices: user FK, title/message/type/read timestamps. | Many per user. |
| `announcements` | Management-created broadcast: creator, title/message/type/publish/expiry/active. | Parent of recipients. |
| `announcement_recipients` | Audience/read association: announcement and optional graduate/faculty/programme FKs. | Many targeted recipients per announcement. |
| `reports` | Stored report metadata: creator, name/type/description, optional file, dates. | Creator/file references. |

### Authentication and role relationships

1. Registration creates a graduate-oriented account/profile path; login verifies `users.password_hash`, creates a random session token, stores only its SHA-256 hash in `user_sessions`, and returns the bearer token plus user data.
2. Axios persists the returned token and sends it per request. `require_authenticated_user()` hashes it, joins `user_sessions → users → roles → account_statuses`, requires active/non-revoked/non-expired state, then updates `last_used_at`.
3. `users.role_id` identifies `graduate`, `management`, or `administrator` (the frontend `UserRole` union). `require_role()` rejects callers not in controller-allowed role lists.
4. A graduate has one `graduate_profiles` record keyed by `user_id`; career, business, qualification, event-registration, survey-invitation and recommendation records flow from that profile.
5. Management users have optional faculty/department scope in `management_profiles`; administrator users have a distinct `administrator_profiles` record. The observed management reports are institution-wide queries in their controller; do not infer filtering from profile scope without verifying a specific query.

## 5. API map

Base path is the backend URL above. `:id` is numeric. Required role is the controller authorization intent; `Public` means no bearer credential is required by the route/handler.

### Authentication and lookup APIs

| Method | Endpoint | Controller | Purpose | Role |
|---|---|---|---|---|
| POST | `/auth/register` | auth | Register graduate. | Public |
| POST | `/auth/login` | auth | Login and issue session token. | Public |
| POST | `/auth/change-password` | auth | Change authenticated password. | Authenticated |
| GET | `/auth/me` | auth | Return current user. | Authenticated |
| GET | `/programmes` | programmes | Public programme selector list. | Public |
| GET | `/employment-statuses` | programmes | Employment-status lookup. | Exposed route |

### Graduate APIs

| Method | Endpoint | Controller | Purpose | Role |
|---|---|---|---|---|
| GET | `/graduate/dashboard` | dashboard | Graduate aggregate dashboard. | Graduate |
| GET, PUT | `/graduate/profile` | profile | Read/update profile. | Graduate |
| POST | `/graduate/profile/avatar` | profile | Multipart avatar upload. | Graduate |
| POST | `/graduate/profile/cv` | profile | Multipart CV upload. | Graduate |
| GET, POST | `/graduate/qualifications` | qualifications | List/create qualification. | Graduate |
| PUT, DELETE | `/graduate/qualifications/:id` | qualifications | Update/delete qualification. | Graduate |
| GET | `/graduate/notifications` | notifications | List notifications. | Graduate |
| GET | `/graduate/notifications/unread-count` | notifications | Unread count. | Graduate |
| PUT | `/graduate/notifications/read-all` | notifications | Mark all read. | Graduate |
| PUT | `/graduate/notifications/:id/read` | notifications | Mark one read. | Graduate |
| GET, POST | `/graduate/employment` | employment | List/create employment. | Graduate |
| PUT, DELETE | `/graduate/employment/:id` | employment | Update/delete employment. | Graduate |
| GET, POST | `/graduate/entrepreneurship` | entrepreneurship | List/create business. | Graduate |
| PUT, DELETE | `/graduate/entrepreneurship/:id` | entrepreneurship | Update/delete business. | Graduate |
| GET, POST | `/graduate/recommendations` | recommendations | List/create requests. | Graduate |
| GET, PUT, DELETE | `/graduate/recommendations/:id` | recommendations | Detail/update/delete request. | Graduate |
| GET | `/graduate/recommendations/:id/download` | recommendations | Download issued letter. | Graduate |
| GET | `/events`, `/graduate/events` | events | Upcoming events. | Public/Graduate path |
| GET | `/graduate/events/:id` | events | Event detail. | Graduate |
| POST, DELETE | `/graduate/events/:id/register` | events | Register/cancel registration. | Graduate |
| GET | `/surveys`, `/graduate/surveys` | surveys | Available surveys. | Public/Graduate path |
| GET | `/graduate/surveys/completed` | surveys | Completed surveys. | Graduate |
| GET | `/graduate/surveys/responses` | surveys | Caller’s response list. | Graduate |
| GET | `/surveys/:id`, `/graduate/surveys/:id` | surveys | Survey definition/detail. | Exposed/Graduate path |
| POST | `/graduate/surveys/:id/start` | surveys | Start response. | Graduate |
| GET, POST | `/graduate/surveys/:id/responses` | surveys | Get/submit answers. | Graduate |
| POST | `/graduate/surveys/:id/submit` | surveys | Submit response (route variable is named `responseId` in dispatcher). | Graduate |

### Management APIs

| Method | Endpoint | Controller | Purpose | Role |
|---|---|---|---|---|
| GET | `/management/dashboard` | management_dashboard | Management aggregate. | Management |
| GET | `/management/recommendations` | management_recommendations | Queue list. | Management |
| GET | `/management/recommendations/:id` | management_recommendations | Request detail. | Management |
| PUT | `/management/recommendations/:id/approve`, `/reject` | management_recommendations | Decide request. | Management |
| POST | `/management/recommendations/:id/upload-letter` | management_recommendations | Upload issued letter. | Management |
| GET, POST | `/management/surveys` | management_surveys | List/create survey. | Management |
| GET, PUT, DELETE | `/management/surveys/:id` | management_surveys | Detail/update/delete. | Management |
| PUT | `/management/surveys/:id/publish` | management_surveys | Publish survey. | Management |
| POST | `/management/surveys/:id/sections`, `/:id/questions` | management_surveys | Add section/question. | Management |
| POST | `/management/questions/:id/options` | management_surveys | Add question option. | Management |
| GET, POST | `/management/events` | management_events | List/create event. | Management |
| PUT, DELETE | `/management/events/:id` | management_events | Update/delete event. | Management |
| PUT | `/management/events/:id/publish` | management_events | Publish event. | Management |
| GET | `/management/events/:id/attendees` | management_events | Attendee list. | Management |
| GET, POST | `/management/announcements` | management_events | List/create announcements (handlers are colocated in this controller). | Management |
| PUT, DELETE | `/management/announcements/:id/publish`, `/:id` | management_events | Publish/delete announcement. | Management |
| GET | `/management/reports/{overview,employment,graduates,surveys,events,recommendations}` | management_reports | Analytics datasets. | Management |
| GET | `/management/reports/export?type=…` | management_reports | CSV export. | Management |

### Administrator APIs

| Method | Endpoint | Controller | Purpose | Role |
|---|---|---|---|---|
| GET | `/administrator/dashboard` | administrator_dashboard | Dashboard aggregate. | Administrator |
| GET | `/administrator/users`, `/administrator/users/:id` | administrator_users | List/detail users. | Administrator |
| PUT | `/administrator/users/:id/status`, `/:id/role` | administrator_users | Change status/role. | Administrator |
| GET | `/administrator/roles`, `/administrator/statuses` | administrator_roles | Role/status lookup. | Administrator |
| GET, POST | `/administrator/faculties` | administrator_academics | List/create faculty. | Administrator |
| PUT, DELETE | `/administrator/faculties/:id` | administrator_academics | Update/delete faculty. | Administrator |
| GET, POST | `/administrator/departments` | administrator_academics | List/create department. | Administrator |
| PUT, DELETE | `/administrator/departments/:id` | administrator_academics | Update/delete department. | Administrator |
| GET, POST | `/administrator/programmes` | administrator_academics | List/create programme. | Administrator |
| PUT, DELETE | `/administrator/programmes/:id` | administrator_academics | Update/delete programme. | Administrator |
| GET, PUT | `/administrator/settings` | administrator_settings | Read/update settings. | Administrator |

## 6. Frontend-to-backend connection map

| Frontend source | Requests it makes | PHP controller |
|---|---|---|
| `auth-context.tsx` | `POST /auth/login`, `/auth/register`, `/auth/change-password` | `auth.php` |
| `register.tsx` | `GET /programmes` | `programmes.php` |
| `dashboard.tsx` | `GET /graduate/dashboard` | `dashboard.php` |
| `ProfileWorkspacePage.tsx` (graduate) | `GET/PUT /graduate/profile`, `POST /graduate/profile/avatar` | `profile.php` |
| `GraduateModules.tsx` employment | `GET /graduate/employment`, `GET /employment-statuses`, `POST/PUT/DELETE /graduate/employment/:id` | `employment.php`, `programmes.php` |
| `GraduateModules.tsx` surveys | `GET /graduate/surveys`, `GET /graduate/surveys/:id`, `POST /graduate/surveys/:id/responses` | `surveys.php` |
| `GraduateModules.tsx` recommendations | `GET/POST /graduate/recommendations`, `GET /graduate/recommendations/:id/download` | `recommendations.php` |
| `GraduateModules.tsx` events | `GET /graduate/events`, register/cancel path | `events.php` |
| `GraduateModules.tsx` notifications | Graduate notification endpoints | `notifications.php` |
| `ManagementAnalytics.tsx` | Six `/management/reports/*` GETs and CSV export | `management_reports.php` |
| `AdministratorDashboard.tsx` | `GET /administrator/dashboard` | `administrator_dashboard.php` |
| `AdministratorUsers.tsx` | Users, roles, statuses; role/status PUTs | `administrator_users.php`, `administrator_roles.php` |
| `AdministratorAcademics.tsx` | Faculty/department/programme GET/POST/PUT/DELETE | `administrator_academics.php` |
| `AdministratorSettings.tsx` | `GET/PUT /administrator/settings` | `administrator_settings.php` |
| `WorkspaceDashboard.tsx`, `ResourceTablePage.tsx` | None (disabled placeholder hook). | None despite management endpoints existing. |

## 7. Existing-code development guidance

### Gaps and connection mismatches

- Announcement handlers are intentionally colocated in `management_events.php` rather than a separate announcements controller. The management announcements *screen* is still a disabled placeholder and does not invoke them.
- Management dashboard, graduates, surveys, recommendations, events and announcements screens are not wired to their PHP APIs: `WorkspaceDashboard` is static and `useBackendCollection` explicitly has `enabled: false`.
- Administrator reports and announcements are also placeholder `ResourceTablePage` routes. No administrator report/announcement PHP endpoints are registered.
- The generic graduate announcements, further-studies and reports pages describe functionality but do not call a corresponding API. The schema has no dedicated further-studies table.
- `lib/api.ts` exposes only a subset of the registered PHP routes (for example qualifications, notifications mutation APIs, management content CRUD and business endpoints are not all represented there). This is a contract coverage gap, not evidence that PHP routes are absent.
- The frontend profile screen calls avatar upload but does not expose CV upload in the observed connection map, although `uploadProfileCv()` and its PHP endpoint exist.

### Security/operational concerns to verify from existing deployment

- `config/db.php` contains local root credentials in source. Use deployment configuration controls before exposing beyond XAMPP; this is a source-observed concern, not a proposed architecture change.
- Tokens and user payloads are stored in `localStorage`; an XSS issue can expose them. The API does correctly store only token hashes server-side and checks expiry/revocation.
- Verify Apache exposes `Authorization` in the deployed XAMPP configuration; helper fallbacks are present for common Apache variants.
- Verify upload validation, MIME/size controls, and that `uploads/` cannot execute uploaded content. Upload endpoints handle avatar/CV/recommendation letters and the repository contains uploaded PDF files.
- Verify CORS origins in `apply_cors_headers()` before a non-local deployment.
- The route dispatcher has overlapping dynamic patterns; regression-test exact paths such as survey submit/start and recommendation download when changing route order.

### High-value testing targets

- Authentication: inactive/suspended accounts, expired/revoked tokens, logout/token removal, password history/change failure paths and role-prefix redirects.
- Ownership: graduate IDs in employment, businesses, qualifications, recommendations, events and survey responses must not permit cross-graduate access.
- File workflows: multipart type/size/error handling, storage keys, replaced avatar/CV behavior, and authorization on recommendation download.
- Referential/deletion behavior: academic hierarchy deletes with linked departments/programmes/qualifications; event/survey/recommendation associated records.
- Management workflow: recommendation state transitions and uploads; survey publication/completion calculations; event capacity/deadline/duplicate registration; CSV format/authorization.
- UI/API parity: add integration tests only after wiring currently inert workspace routes; present UI should not be represented as exercising those backend APIs.
