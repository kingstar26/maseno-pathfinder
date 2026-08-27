import { useEffect, useRef, useState } from "react";
import { Link } from "@tanstack/react-router";
import toast from "react-hot-toast";
import {
  BadgeCheck,
  BriefcaseBusiness,
  Eye,
  EyeOff,
  GraduationCap,
  KeyRound,
  Save,
  ShieldCheck,
  Trash2,
  Upload,
  UserCog,
  UserRound,
  UsersRound,
  X,
  FileText,
  Download,
  ExternalLink,
} from "lucide-react";
import { useAuth } from "@/context/auth-context";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { PageHeader } from "@/components/PageHeader";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Progress } from "@/components/ui/progress";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import {
  getGraduateProfile,
  updateGraduateProfile,
  uploadProfileAvatar,
  uploadProfileCv,
  deleteProfileCv,
  uploadedFileUrl,
  resolveAssetUrl,
  getAcademicMetadata,
  type AcademicMetadata,
} from "@/lib/api";

type Role = "graduate" | "management" | "administrator";
type Data = Record<string, string>;

interface CvFileRecord {
  file_id: number;
  original_filename: string;
  mime_type: string;
  byte_size: number;
  created_at: string;
  storage_key: string;
}

const names = new Set(["first_name", "middle_name", "last_name"]);
const personal = ["first_name", "middle_name", "last_name", "gender", "national_id", "date_of_birth", "phone", "email"];
const graduatePersonal = [...personal, "county", "sub_county", "postal_address", "physical_address"];
const graduateAcademic = [
  "admission_number",
  "registration_number",
  "programme",
  "department",
  "faculty",
  "year_of_admission",
  "year_of_graduation",
  "graduation_status",
  "degree_awarded",
];
const official = ["staff_number", "designation", "office", "department", "faculty", "official_email", "office_phone", "date_joined", "employment_status", "role", "supervisor"];
const admin = ["employee_number", "administrator_level", "role", "office", "official_email", "department", "date_assigned", "system_access_level"];
const labels: Record<string, string> = {
  first_name: "First name",
  middle_name: "Middle name",
  last_name: "Last name",
  gender: "Gender",
  national_id: "National ID",
  date_of_birth: "Date of birth",
  phone: "Phone number",
  email: "Email",
  county: "County",
  sub_county: "Sub county",
  postal_address: "Postal address",
  physical_address: "Physical address",
  admission_number: "Admission number",
  registration_number: "Registration number",
  programme: "Programme",
  department: "Department",
  faculty: "School / faculty",
  year_of_admission: "Year of admission",
  year_of_graduation: "Year of graduation",
  graduation_status: "Graduation status",
  degree_awarded: "Degree awarded",
  classification: "Classification",
  campus: "Campus",
  employment_status: "Employment status",
  employer: "Employer",
  job_title: "Job title",
  industry: "Industry",
  work_location: "Work location",
  employment_start_date: "Employment start date",
  staff_number: "Staff number",
  designation: "Designation",
  office: "Office",
  official_email: "Official email",
  office_phone: "Office phone",
  date_joined: "Date joined",
  role: "Role",
  supervisor: "Supervisor",
  employee_number: "Employee number",
  administrator_level: "Administrator level",
  date_assigned: "Date assigned",
  system_access_level: "System access level",
};
const label = (key: string) => labels[key] || key;
const empty = () => Object.fromEntries(Object.keys(labels).map((k) => [k, ""]));
const cleanPhone = (v: string) => v.replace(/[^\d+]/g, "").replace(/(?!^)\+/g, "");
const validPhone = (v: string) => /^(?:\+254[17]\d{8}|0[17]\d{8})$/.test(v.trim());
const validName = (v: string) => /^[A-Za-z]+(?:[ '\-][A-Za-z]+)*$/.test(v.trim());
const date = (v?: string | null) => (v ? new Intl.DateTimeFormat("en-US", { dateStyle: "medium", timeStyle: "short" }).format(new Date(v)) : "Not available");
const available = (v?: string | null) => v?.trim() || "Not available";
const backendErrorMessage = (error: any, fallback: string) => {
  const errors = error?.response?.data?.errors;
  if (errors && typeof errors === "object") {
    const fieldMessages = Object.values(errors)
      .flatMap((value) => Array.isArray(value) ? value : [value])
      .filter((value): value is string => typeof value === "string" && value.trim() !== "");
    if (fieldMessages.length > 0) return fieldMessages.join(" ");
  }
  return error?.response?.data?.message || error?.message || fallback;
};

export function ProfileWorkspacePage({ role }: { role: Role }) {
  const { user, updateUser, changePassword } = useAuth();
  const [data, setData] = useState<Data>(empty);
  const [saved, setSaved] = useState<Data>(empty);
  const [editing, setEditing] = useState(false);
  const [photo, setPhoto] = useState<string | null>(null);
  const [photoChanged, setPhotoChanged] = useState(false);
  const [saving, setSaving] = useState(false);
  const [uploadingPhoto, setUploadingPhoto] = useState(false);
  const [deletingCv, setDeletingCv] = useState(false);
  const [confirmCvDelete, setConfirmCvDelete] = useState(false);
  const [uploadingCv, setUploadingCv] = useState(false);
  const [cvFile, setCvFile] = useState<CvFileRecord | null>(null);
  const [passwordLastChanged, setPasswordLastChanged] = useState<string | null>(null);
  const [academicMeta, setAcademicMeta] = useState<AcademicMetadata | null>(null);
  const [apiCompletion, setApiCompletion] = useState<number | null>(null);
  const [pw, setPw] = useState({ current: "", next: "", confirm: "" });
  const [show, setShow] = useState(false);
  const input = useRef<HTMLInputElement>(null);
  const cvInput = useRef<HTMLInputElement>(null);

  const syncAcademicSelections = (currentData: Data, meta: AcademicMetadata | null): Data => {
    if (!meta) return currentData;

    const next = { ...currentData };

    if (next.programme_id || next.programme) {
      const selectedProgramme = meta.programmes.find(
        (p) => p.programme_id.toString() === (next.programme_id || "") || p.programme_name === next.programme,
      );
      if (selectedProgramme) {
        next.programme_id = String(selectedProgramme.programme_id);
        next.programme = selectedProgramme.programme_name;
        next.department_id = String(selectedProgramme.department_id);
        next.department = selectedProgramme.department_name || next.department;
        next.faculty_id = String(selectedProgramme.faculty_id);
        next.faculty = selectedProgramme.faculty_name || next.faculty;
        if (!next.degree_awarded) {
          next.degree_awarded = selectedProgramme.programme_name;
        }
      }
    }

    if (next.department_id || next.department) {
      const selectedDepartment = meta.departments.find(
        (d) => d.department_id.toString() === (next.department_id || "") || d.name === next.department,
      );
      if (selectedDepartment) {
        next.department_id = String(selectedDepartment.department_id);
        next.department = selectedDepartment.name;
        if (!next.faculty_id && selectedDepartment.faculty_id) {
          const fac = meta.faculties.find((f) => f.faculty_id === selectedDepartment.faculty_id);
          if (fac) {
            next.faculty_id = String(fac.faculty_id);
            next.faculty = fac.name;
          }
        }
      }
    }

    if (next.faculty_id || next.faculty) {
      const selectedFaculty = meta.faculties.find(
        (f) => f.faculty_id.toString() === (next.faculty_id || "") || f.name === next.faculty,
      );
      if (selectedFaculty) {
        next.faculty_id = String(selectedFaculty.faculty_id);
        next.faculty = selectedFaculty.name;
      }
    }

    if (next.graduation_status_id || next.graduation_status) {
      const selectedStatus = meta.graduation_statuses.find(
        (gs) => gs.graduation_status_id.toString() === (next.graduation_status_id || "") || gs.name === next.graduation_status,
      );
      if (selectedStatus) {
        next.graduation_status_id = String(selectedStatus.graduation_status_id);
        next.graduation_status = selectedStatus.name;
      }
    }

    return next;
  };

  useEffect(() => {
    getAcademicMetadata()
      .then((meta) => {
        setAcademicMeta(meta);
        if (role === "graduate") {
          setData((current) => syncAcademicSelections(current, meta));
          setSaved((current) => syncAcademicSelections(current, meta));
        }
      })
      .catch(() => {});
  }, [role]);

  useEffect(() => {
    if (!user) return;
    const d = {
      ...empty(),
      first_name: user.first_name,
      last_name: user.last_name,
      email: user.email,
      official_email: user.role === "graduate" ? "" : user.email,
      programme: user.programme || "",
      year_of_graduation: user.graduation_year?.toString() || "",
    };

    if (role === "graduate") {
      getGraduateProfile()
        .then((prof) => {
          if (prof) {
            const merged: Data = {
              ...d,
              first_name: prof.first_name || d.first_name,
              middle_name: prof.middle_name || "",
              last_name: prof.last_name || d.last_name,
              email: prof.email || d.email,
              phone: prof.phone_number || "",
              gender: prof.gender || "",
              national_id: prof.national_id || "",
              date_of_birth: prof.date_of_birth || "",
              county: prof.county || "",
              sub_county: prof.sub_county || "",
              postal_address: prof.postal_address || "",
              physical_address: prof.physical_address || "",
              admission_number: prof.admission_number || "",
              registration_number: prof.registration_number || "",
              programme: prof.programme || d.programme,
              programme_id: prof.programme_id ? String(prof.programme_id) : "",
              department: prof.department || "",
              department_id: "",
              faculty: prof.faculty || "",
              faculty_id: "",
              year_of_admission: prof.year_of_admission ? String(prof.year_of_admission) : "",
              year_of_graduation: prof.year_of_graduation ? String(prof.year_of_graduation) : d.year_of_graduation,
              degree_awarded: prof.degree_awarded || prof.programme || d.programme || "",
              graduation_status: prof.graduation_status || "",
              graduation_status_id: "",
            };
            const hydrated = syncAcademicSelections(merged, academicMeta);
            setData(hydrated);
            setSaved(hydrated);
            if (prof.password_changed_at) {
              setPasswordLastChanged(prof.password_changed_at);
            }
            if (prof.cv_information?.file) {
              setCvFile(prof.cv_information.file as CvFileRecord);
            }
            if (prof.profile_completion_percentage !== undefined || prof.profile_completion !== undefined) {
              setApiCompletion(prof.profile_completion_percentage ?? prof.profile_completion ?? null);
            }
            if (!photoChanged) {
              const rawAvatar = prof.avatar_url || user.avatar_url || null;
              const resolvedAvatar = resolveAssetUrl(rawAvatar);
              console.log('[ProfileWorkspacePage] prof loaded - prof.avatar_url:', prof.avatar_url, 'rawAvatar:', rawAvatar, 'resolvedAvatar:', resolvedAvatar);
              setPhoto(resolvedAvatar);
              if (rawAvatar && rawAvatar !== user.avatar_url) {
                console.log('[ProfileWorkspacePage] Calling updateUser with rawAvatar:', rawAvatar);
                updateUser({ avatar_url: rawAvatar });
              }
            }
          }
        })
        .catch(() => {
          try {
            const n = { ...d, ...JSON.parse(localStorage.getItem(`mugts.profile.${user.id}`) || "{}") } as Data;
            setData(n);
            setSaved(n);
          } catch {
            setData(d);
            setSaved(d);
          }
        });
    } else {
      try {
        const n = { ...d, ...JSON.parse(localStorage.getItem(`mugts.profile.${user.id}`) || "{}") } as Data;
        setData(n);
        setSaved(n);
      } catch {
        setData(d);
        setSaved(d);
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [user?.id, role, academicMeta]);

  if (!user) return null;

  const sections =
    role === "graduate"
      ? [
          ["Personal information", graduatePersonal, UserRound],
          ["Academic information", graduateAcademic, GraduationCap],
        ]
      : role === "management"
      ? [
          ["Personal information", personal, UserRound],
          ["Official information", official, UsersRound],
        ]
      : [
          ["Personal information", personal, UserRound],
          ["System admin information", admin, ShieldCheck],
        ];

  const fields = sections.flatMap((s) => s[1] as string[]);
  const complete = apiCompletion !== null ? apiCompletion : Math.round((fields.filter((k) => data[k]?.trim()).length / fields.length) * 100);
  console.log("[Profile Avatar]", {
    avatar_url: user?.avatar_url,
    photo,
    resolved: resolveAssetUrl(user?.avatar_url)
  });

  const preview = photoChanged
    ? (photo ? resolveAssetUrl(photo) : null)
    : resolveAssetUrl(user?.avatar_url || photo || null);

  const change = (k: string, v: string) => {
    if (names.has(k) && v && !/^[A-Za-z '\-]*$/.test(v)) {
      toast.error("Names accept letters, spaces, hyphens and apostrophes only.");
      return;
    }
    if ((k === "county" || k === "sub_county") && v && !/^[A-Za-z '\-]*$/.test(v)) {
      const fieldLabel = k === "county" ? "County" : "Sub County";
      toast.error(`${fieldLabel} names may only contain letters, spaces, hyphens and apostrophes.`);
      return;
    }
    if (k === "national_id") v = v.replace(/\D/g, "").slice(0, 8);
    if (k === "phone") {
      v = v.replace(/[^\d+]/g, "").replace(/(?!^)\+/g, "");
    }
    if (k === "year_of_admission" || k === "year_of_graduation") {
      v = v.replace(/\D/g, "").slice(0, 4);
    }

    if (k === "programme" && academicMeta) {
      const selectedProg = academicMeta.programmes.find((p) => p.programme_id.toString() === v || p.programme_name === v);
      if (selectedProg) {
        setData((d) => ({
          ...d,
          programme_id: String(selectedProg.programme_id),
          programme: selectedProg.programme_name,
          department_id: String(selectedProg.department_id),
          department: selectedProg.department_name,
          faculty_id: String(selectedProg.faculty_id),
          faculty: selectedProg.faculty_name,
          degree_awarded: selectedProg.programme_name,
        }));
        return;
      }
    }

    if (k === "department" && academicMeta) {
      const selectedDepartment = academicMeta.departments.find((d) => d.department_id.toString() === v || d.name === v);
      if (selectedDepartment) {
        const fac = academicMeta.faculties.find((f) => f.faculty_id === selectedDepartment.faculty_id);
        setData((d) => ({
          ...d,
          department_id: String(selectedDepartment.department_id),
          department: selectedDepartment.name,
          faculty_id: fac ? String(fac.faculty_id) : d.faculty_id,
          faculty: fac ? fac.name : d.faculty,
        }));
        return;
      }
    }

    if (k === "faculty" && academicMeta) {
      const selectedFaculty = academicMeta.faculties.find((f) => f.faculty_id.toString() === v || f.name === v);
      if (selectedFaculty) {
        setData((d) => ({
          ...d,
          faculty_id: String(selectedFaculty.faculty_id),
          faculty: selectedFaculty.name,
        }));
        return;
      }
    }

    if (k === "graduation_status" && academicMeta) {
      const selectedStatus = academicMeta.graduation_statuses.find((gs) => gs.graduation_status_id.toString() === v || gs.name === v);
      if (selectedStatus) {
        setData((d) => ({
          ...d,
          graduation_status_id: String(selectedStatus.graduation_status_id),
          graduation_status: selectedStatus.name,
        }));
        return;
      }
    }

    setData((d) => ({ ...d, [k]: v }));
  };

  const save = async () => {
    if (!validName(data.first_name) || !validName(data.last_name)) {
      toast.error("Enter valid first and last names.");
      return;
    }
    if (data.national_id && !/^\d{8}$/.test(data.national_id)) {
      toast.error("National ID must be exactly 8 digits.");
      return;
    }
    if (data.phone && !/^(?:\+254[71]|0[71])\d{8}$/.test(data.phone.trim())) {
      toast.error("Enter a valid Kenyan phone number. The number must start with +2547, +2541, 07 or 01 followed by exactly eight additional digits.");
      return;
    }
    if (data.county && !/^[A-Za-z '\-]+$/.test(data.county.trim())) {
      toast.error("County names may only contain letters, spaces, hyphens and apostrophes.");
      return;
    }
    if (data.sub_county && !/^[A-Za-z '\-]+$/.test(data.sub_county.trim())) {
      toast.error("Sub County names may only contain letters, spaces, hyphens and apostrophes.");
      return;
    }
    if (data.postal_address && !/^(?=.*[A-Za-z0-9])[A-Za-z0-9 .\-\/,]+$/.test(data.postal_address.trim())) {
      toast.error("Postal address contains invalid characters or format. Only letters, numbers, spaces, periods, hyphens, slashes and commas are allowed.");
      return;
    }
    if (data.physical_address && !/^(?=.*[A-Za-z0-9])[A-Za-z0-9 .\-\/,]+$/.test(data.physical_address.trim())) {
      toast.error("Physical address contains invalid characters or format. Only letters, numbers, spaces, commas, periods, hyphens and slashes are allowed.");
      return;
    }
    if (data.year_of_admission && !/^\d{4}$/.test(data.year_of_admission.trim())) {
      toast.error("Year of admission must contain exactly four numeric digits (for example: 2022).");
      return;
    }
    if (data.year_of_graduation && !/^\d{4}$/.test(data.year_of_graduation.trim())) {
      toast.error("Year of graduation must contain exactly four numeric digits (for example: 2022).");
      return;
    }
    if (!/^\S+@\S+\.\S+$/.test(data.email)) {
      toast.error("Enter a valid email address.");
      return;
    }
    setSaving(true);
    try {
      if (role === "graduate") {
        const updated = await updateGraduateProfile({
          first_name: data.first_name,
          last_name: data.last_name,
          phone_number: data.phone || null,
          gender: data.gender || null,
          date_of_birth: data.date_of_birth || null,
          county: data.county || null,
          sub_county: data.sub_county || null,
          postal_address: data.postal_address || null,
          physical_address: data.physical_address || null,
          year_of_admission: data.year_of_admission ? Number(data.year_of_admission) : null,
          year_of_graduation: data.year_of_graduation ? Number(data.year_of_graduation) : null,
          degree_awarded: data.degree_awarded || null,
          programme: data.programme || null,
          admission_number: data.admission_number || null,
          registration_number: data.registration_number || null,
        });
        if (updated && (updated.profile_completion_percentage !== undefined || updated.profile_completion !== undefined)) {
          setApiCompletion(updated.profile_completion_percentage ?? updated.profile_completion ?? null);
        }
      }
      const n = { ...data };
      localStorage.setItem(`mugts.profile.${user.id}`, JSON.stringify(n));
      setSaved(n);
      updateUser({
        first_name: n.first_name,
        last_name: n.last_name,
        email: n.email,
        programme: n.programme || null,
        graduation_year: n.year_of_graduation ? Number(n.year_of_graduation) : null,
      });
      setPhotoChanged(false);
      setEditing(false);
      toast.success("Profile updated successfully.");
    } catch (err: any) {
      toast.error(backendErrorMessage(err, "Unable to update profile."));
    } finally {
      setSaving(false);
    }
  };

  const choose = async (file?: File) => {
    if (!file) return;

    const allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/webp", "image/pjpeg"];
    const ext = file.name.split(".").pop()?.toLowerCase() || "";
    const allowedExts = ["jpg", "jpeg", "png", "webp"];

    if (!allowedTypes.includes(file.type) || !allowedExts.includes(ext)) {
      toast.error("Profile photo upload failed. Only JPG, JPEG, PNG and WEBP image files are supported.");
      return;
    }

    setUploadingPhoto(true);
    try {
      const prof = await uploadProfileAvatar(file);
      if (prof?.avatar_url || prof?.file?.storage_key) {
        const rawUrl = prof.avatar_url ?? (prof.file?.storage_key ? `/uploads/${prof.file.storage_key}` : null);
        setPhoto(rawUrl);
        setPhotoChanged(false);
        if (rawUrl) {
          updateUser({ avatar_url: rawUrl });
        }
      }
      toast.success("Profile photo updated successfully.");
    } catch (err: any) {
      toast.error(err?.response?.data?.message || "Profile photo upload failed. Only JPG, JPEG, PNG and WEBP image files are supported.");
    } finally {
      setUploadingPhoto(false);
    }
  };

  const uploadCvAction = async (file?: File) => {
    if (!file) return;

    const ext = file.name.split(".").pop()?.toLowerCase() || "";
    const allowedExts = ["pdf", "doc", "docx"];
    const rejectedExts = ["jpg", "jpeg", "png", "gif", "webp", "svg", "bmp", "heic", "zip", "exe"];

    if (rejectedExts.includes(ext) || file.type.startsWith("image/") || !allowedExts.includes(ext)) {
      toast.error("Please upload a valid CV document (PDF, DOC or DOCX). Images and unsupported file types are not accepted.");
      return;
    }

    setUploadingCv(true);
    try {
      const res = await uploadProfileCv(file);
      if (res?.file) {
        setCvFile(res.file as CvFileRecord);
      }
      toast.success("CV uploaded successfully.");
    } catch (err: any) {
      toast.error(backendErrorMessage(err, "Please upload a valid CV document (PDF, DOC or DOCX). Images and unsupported file types are not accepted."));
    } finally {
      setUploadingCv(false);
    }
  };

  const deleteCvAction = async () => {
    setDeletingCv(true);
    try {
      await deleteProfileCv();
      setCvFile(null);
      setConfirmCvDelete(false);
      toast.success("CV deleted successfully.");
    } catch (err: any) {
      toast.error(err?.response?.data?.message || "CV deletion failed. Please try again or contact the system administrator.");
    } finally {
      setDeletingCv(false);
    }
  };

  const strength = [pw.next.length >= 8, /[A-Z]/.test(pw.next), /[a-z]/.test(pw.next), /\d/.test(pw.next), /[^A-Za-z0-9]/.test(pw.next)].filter(Boolean).length * 20;

  const changePasswordAction = async () => {
    if (pw.current && pw.current === pw.next) {
      toast.error("Your new password must be different from your current password.");
      return;
    }
    if (!pw.current || strength < 80 || pw.next !== pw.confirm) {
      toast.error("Provide your current password, a strong new password, and matching confirmation.");
      return;
    }
    try {
      await changePassword(pw.current, pw.next);
      setPw({ current: "", next: "", confirm: "" });
      setPasswordLastChanged(new Date().toISOString());
      toast.success("Password changed successfully.");
    } catch (e: any) {
      toast.error(e?.response?.data?.message || e?.response?.data?.errors?.new_password || e?.message || "Password could not be changed.");
    }
  };

  const title = role === "graduate" ? "Graduate Profile" : role === "management" ? "Management Profile" : "Administrator Profile";
  const name = [data.first_name, data.middle_name, data.last_name].filter(Boolean).join(" ") || `${user.first_name} ${user.last_name}`;
  const initials = `${data.first_name?.[0] || "M"}${data.last_name?.[0] || "U"}`.toUpperCase();

  const getCvUrl = uploadedFileUrl;

  return (
    <>
      <PageHeader title={title} subtitle="Manage your personal information and account security." />
      <section className="relative overflow-hidden rounded-2xl border border-border bg-card shadow-[var(--shadow-card)]">
        <div className="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-primary via-primary/50 to-[var(--gold)]" />
        <div className="px-5 pb-6 pt-7 sm:px-7">
          <div className="flex flex-col justify-between gap-5 lg:flex-row">
            <div className="flex gap-4 sm:items-center">
              <button
                onClick={() => input.current?.click()}
                className="relative -mt-11 grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-2xl border-4 border-card bg-primary text-2xl font-bold text-primary-foreground shadow-lg"
              >
                {preview ? <img src={preview} alt="Profile" className="h-full w-full object-cover" /> : initials}
              </button>
              <input ref={input} className="sr-only" type="file" accept="image/jpeg,image/png,image/webp" onChange={(e) => choose(e.target.files?.[0])} />
              <input ref={cvInput} className="sr-only" type="file" accept=".pdf,.doc,.docx" onChange={(e) => uploadCvAction(e.target.files?.[0])} />
              <div>
                <h2 className="text-2xl font-bold">{name}</h2>
                <div className="mt-2 flex gap-2">
                  <span className="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">{role}</span>
                  <span className="inline-flex items-center gap-1 rounded-full bg-success/10 px-2.5 py-1 text-xs font-semibold text-success">
                    <BadgeCheck className="h-3.5 w-3.5" />
                    Active
                  </span>
                </div>
                <p className="mt-2 text-sm text-muted-foreground">{available(role === "graduate" ? data.programme : data.designation)}</p>
              </div>
            </div>
            <div className="flex flex-wrap gap-2">
              {role === "graduate" && (
                <Button variant="outline" onClick={() => cvInput.current?.click()} disabled={uploadingCv}>
                  <FileText className="mr-2 h-4 w-4" />
                  {uploadingCv ? "Uploading CV…" : "Upload CV"}
                </Button>
              )}
              {editing ? (
                <>
                  <Button variant="outline" onClick={() => input.current?.click()}>
                    <Upload className="mr-2 h-4 w-4" />
                    Upload Photo
                  </Button>
                  {preview && (
                    <Button
                      variant="outline"
                      className="text-destructive"
                      onClick={() => {
                        setPhoto(null);
                        setPhotoChanged(true);
                      }}
                    >
                      <Trash2 className="mr-2 h-4 w-4" />
                      Remove Photo
                    </Button>
                  )}
                  <Button
                    variant="outline"
                    onClick={() => {
                      setData(saved);
                      setPhoto(null);
                      setPhotoChanged(false);
                      setEditing(false);
                    }}
                  >
                    <X className="mr-2 h-4 w-4" />
                    Cancel
                  </Button>
                  <Button onClick={save} disabled={saving}>
                    <Save className="mr-2 h-4 w-4" />
                    {saving ? "Saving…" : "Save Changes"}
                  </Button>
                </>
              ) : (
                <Button onClick={() => setEditing(true)}>
                  <UserCog className="mr-2 h-4 w-4" />
                  Edit Profile
                </Button>
              )}
            </div>
          </div>

          {role === "graduate" && cvFile && (
            <div className="mt-6 rounded-xl border border-primary/20 bg-primary/5 p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <div className="flex items-center gap-3">
                <div className="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                  <FileText className="h-5 w-5" />
                </div>
                <div>
                  <h4 className="text-sm font-semibold text-foreground flex items-center gap-2">
                    {cvFile.original_filename}
                    <span className="rounded bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground uppercase font-mono">
                      {(cvFile.original_filename.split(".").pop() || "doc").toUpperCase()}
                    </span>
                  </h4>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    Uploaded on {date(cvFile.created_at)} • {(cvFile.byte_size / 1024).toFixed(1)} KB
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-2 w-full sm:w-auto justify-end">
                <Button variant="outline" size="sm" onClick={() => window.open(getCvUrl(cvFile.storage_key), "_blank")}>
                  <ExternalLink className="h-3.5 w-3.5 mr-1.5" />
                  View
                </Button>
                <Button variant="outline" size="sm" asChild>
                  <a href={getCvUrl(cvFile.storage_key)} download={cvFile.original_filename}>
                    <Download className="h-3.5 w-3.5 mr-1.5" />
                    Download
                  </a>
                </Button>
                <Button variant="destructive" size="sm" disabled={deletingCv} onClick={() => setConfirmCvDelete(true)}><Trash2 className="h-3.5 w-3.5 mr-1.5" />Delete</Button>
              </div>
            </div>
          )}

          <div className="mt-6 grid gap-3 border-t border-border pt-5 sm:grid-cols-2 xl:grid-cols-4">
            {[
              ["Email", data.email],
              ["Phone", data.phone],
              ["Member since", "Not available"],
              ["Last login", date(user.last_login_at)],
              ["Account status", "Active"],
              ["Email verification", "Not available"],
              ["Department", data.department],
              ["Role", role],
            ].map(([a, b]) => (
              <Info key={a} a={a} b={b} />
            ))}
          </div>
          <div className="mt-5 rounded-xl border border-border bg-muted/20 p-4">
            <div className="flex justify-between text-sm">
              <b>Profile completion</b>
              <b className="text-primary">{complete}%</b>
            </div>
            <Progress value={complete} className="mt-3" />
          </div>
        </div>
      </section>

      <div className="mt-5 grid gap-5 xl:grid-cols-2">
        {sections.map(([heading, keys, Icon]) => (
          <Section key={heading as string} heading={heading as string} Icon={Icon as typeof UserRound}>
            <Fields
              keys={keys as string[]}
              data={data}
              editing={editing}
              role={role}
              academicMeta={academicMeta}
              change={change}
            />
          </Section>
        ))}
        {role === "graduate" && (
          <Section heading="Employment summary" Icon={BriefcaseBusiness}>
            <p className="text-sm text-muted-foreground">Manage your employment history and career progression in the Employment module.</p>
            <Button asChild className="mt-4">
              <Link to="/employment">Manage Employment</Link>
            </Button>
          </Section>
        )}
        <Section heading={role === "graduate" ? "Account information" : role === "management" ? "System information" : "Security activity"} Icon={ShieldCheck}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Info a="Last login" b={date(user.last_login_at)} />
            <Info a="Account status" b="Active" />
            <Info a={role === "administrator" ? "Login devices" : "Two-factor authentication"} b="Not available" />
            <Info a="Password last changed" b={date(passwordLastChanged)} />
          </div>
        </Section>
      </div>

      <section className="mt-5 rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)] sm:p-6">
        <div className="flex gap-3">
          <span className="grid h-10 w-10 place-items-center rounded-xl bg-primary/10 text-primary">
            <KeyRound className="h-5 w-5" />
          </span>
          <div>
            <h2 className="font-semibold">Security</h2>
            <p className="mt-1 text-sm text-muted-foreground">Change your password without displaying your current password.</p>
          </div>
        </div>
        <div className="mt-5 grid gap-4 md:grid-cols-3">
          {(
            [
              ["current", "Current password"],
              ["next", "New password"],
              ["confirm", "Confirm new password"],
            ] as const
          ).map(([k, l]) => (
            <div key={k}>
              <Label>{l}</Label>
              <div className="relative mt-1.5">
                <Input type={show ? "text" : "password"} value={pw[k]} onChange={(e) => setPw((p) => ({ ...p, [k]: e.target.value }))} />
                <button onClick={() => setShow(!show)} className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground">
                  {show ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
            </div>
          ))}
        </div>
        <div className="mt-4 max-w-md">
          <div className="flex justify-between text-xs text-muted-foreground">
            <span>Password strength</span>
            <span>{strength}%</span>
          </div>
          <Progress value={strength} className="mt-2" />
        </div>
        <Button className="mt-4" onClick={changePasswordAction} disabled={!pw.current || !pw.next || !pw.confirm}>
          <KeyRound className="mr-2 h-4 w-4" />
          Change Password
        </Button>
      </section>
      <AlertDialog open={confirmCvDelete} onOpenChange={setConfirmCvDelete}>
        <AlertDialogContent>
          <AlertDialogHeader><AlertDialogTitle>Delete CV?</AlertDialogTitle><AlertDialogDescription>This action cannot be undone.</AlertDialogDescription></AlertDialogHeader>
          <AlertDialogFooter><AlertDialogCancel disabled={deletingCv}>Cancel</AlertDialogCancel><AlertDialogAction className="bg-destructive text-destructive-foreground hover:bg-destructive/90" disabled={deletingCv} onClick={(event) => { event.preventDefault(); void deleteCvAction(); }}>{deletingCv ? "Deleting CV…" : "Delete CV"}</AlertDialogAction></AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
}

function Info({ a, b }: { a: string; b?: string | null }) {
  return (
    <div>
      <p className="text-xs font-medium text-muted-foreground">{a}</p>
      <p className="mt-1 break-words text-sm font-semibold">{available(b)}</p>
    </div>
  );
}

function Section({ heading, Icon, children }: { heading: string; Icon: typeof UserRound; children: React.ReactNode }) {
  return (
    <section className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)] sm:p-6">
      <div className="flex gap-3">
        <span className="grid h-10 w-10 place-items-center rounded-xl bg-primary/10 text-primary">
          <Icon className="h-5 w-5" />
        </span>
        <div>
          <h2 className="font-semibold">{heading}</h2>
          <p className="mt-1 text-sm text-muted-foreground">Information saved only when you choose Save Changes.</p>
        </div>
      </div>
      <div className="mt-5">{children}</div>
    </section>
  );
}

function Fields({
  keys,
  data,
  editing,
  role,
  academicMeta,
  change,
}: {
  keys: string[];
  data: Data;
  editing: boolean;
  role: Role;
  academicMeta: AcademicMetadata | null;
  change: (k: string, v: string) => void;
}) {
  const readOnlyFields = ["first_name", "last_name", "email"];

  return (
    <div className="grid gap-x-5 gap-y-4 sm:grid-cols-2">
      {keys.map((k) => {
        const isReadOnly = readOnlyFields.includes(k) && role !== "administrator";

        return (
          <div key={k}>
            {editing && !isReadOnly ? (
              <>
                <Label htmlFor={k}>
                  {label(k)}
                  {["first_name", "last_name", "national_id", "phone", "email"].includes(k) && <span className="text-destructive"> *</span>}
                </Label>
                {k === "gender" ? (
                  <Select value={data.gender || ""} onValueChange={(v) => change(k, v)}>
                    <SelectTrigger className="mt-1.5">
                      <SelectValue placeholder="Select gender" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Male">Male</SelectItem>
                      <SelectItem value="Female">Female</SelectItem>
                    </SelectContent>
                  </Select>
                ) : k === "programme" && academicMeta && academicMeta.programmes.length > 0 ? (
                  (() => {
                    const currentProg = academicMeta.programmes.find(
                      (p) => p.programme_id.toString() === data.programme_id || p.programme_name === data.programme,
                    );
                    const progVal = currentProg ? String(currentProg.programme_id) : "";
                    return (
                      <Select value={progVal} onValueChange={(v) => change(k, v)}>
                        <SelectTrigger className="mt-1.5">
                          <SelectValue placeholder="Select programme" />
                        </SelectTrigger>
                        <SelectContent className="max-h-60 overflow-y-auto">
                          {academicMeta.programmes.map((p) => (
                            <SelectItem key={p.programme_id} value={String(p.programme_id)}>
                              {p.programme_name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    );
                  })()
                ) : k === "department" && academicMeta && academicMeta.departments.length > 0 ? (
                  (() => {
                    const currentProg = academicMeta.programmes.find(
                      (p) => p.programme_id.toString() === data.programme_id || p.programme_name === data.programme,
                    );
                    const filteredDepts = currentProg
                      ? academicMeta.departments.filter(
                          (d) => d.department_id === currentProg.department_id || d.faculty_id === currentProg.faculty_id,
                        )
                      : academicMeta.departments;
                    const currentDept = academicMeta.departments.find(
                      (d) => d.department_id.toString() === data.department_id || d.name === data.department,
                    );
                    const deptVal = currentDept ? String(currentDept.department_id) : "";

                    return (
                      <Select value={deptVal} onValueChange={(v) => change(k, v)}>
                        <SelectTrigger className="mt-1.5">
                          <SelectValue placeholder="Select department" />
                        </SelectTrigger>
                        <SelectContent className="max-h-60 overflow-y-auto">
                          {filteredDepts.map((d) => (
                            <SelectItem key={d.department_id} value={String(d.department_id)}>
                              {d.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    );
                  })()
                ) : k === "faculty" ? (
                  <Input
                    id={k}
                    className="mt-1.5 bg-muted cursor-not-allowed text-muted-foreground font-medium"
                    value={data.faculty || ""}
                    readOnly
                    disabled
                    placeholder="Derived automatically from Programme/Department"
                  />
                ) : k === "graduation_status" && academicMeta && academicMeta.graduation_statuses.length > 0 ? (
                  (() => {
                    const currentStatus = academicMeta.graduation_statuses.find(
                      (gs) => gs.graduation_status_id.toString() === data.graduation_status_id || gs.name === data.graduation_status,
                    );
                    const statusVal = currentStatus ? String(currentStatus.graduation_status_id) : "";

                    return (
                      <Select value={statusVal} onValueChange={(v) => change(k, v)}>
                        <SelectTrigger className="mt-1.5">
                          <SelectValue placeholder="Select graduation status" />
                        </SelectTrigger>
                        <SelectContent>
                          {academicMeta.graduation_statuses.map((gs) => (
                            <SelectItem key={gs.graduation_status_id} value={String(gs.graduation_status_id)}>
                              {gs.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    );
                  })()
                ) : k === "degree_awarded" && academicMeta && academicMeta.programmes.length > 0 ? (
                  <Select value={data.degree_awarded || data.programme || ""} onValueChange={(v) => change(k, v)}>
                    <SelectTrigger className="mt-1.5">
                      <SelectValue placeholder="Select degree awarded" />
                    </SelectTrigger>
                    <SelectContent className="max-h-60 overflow-y-auto">
                      {Array.from(new Set(academicMeta.programmes.map((p) => p.programme_name))).map((deg) => (
                        <SelectItem key={deg} value={deg}>
                          {deg}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                ) : (
                  <Input
                    id={k}
                    className="mt-1.5"
                    type={["date_of_birth", "employment_start_date", "date_joined", "date_assigned"].includes(k) ? "date" : k.includes("email") ? "email" : k.includes("phone") ? "tel" : "text"}
                    maxLength={k === "national_id" ? 8 : undefined}
                    value={data[k]}
                    onChange={(e) => change(k, e.target.value)}
                  />
                )}
              </>
            ) : (
              <Info a={label(k)} b={data[k]} />
            )}
          </div>
        );
      })}
    </div>
  );
}
