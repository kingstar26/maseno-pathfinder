import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { useForm } from "react-hook-form";
import { motion, AnimatePresence } from "framer-motion";
import {
  AlertCircle,
  ArrowLeft,
  ArrowRight,
  CalendarDays,
  Check,
  ChevronDown,
  GraduationCap,
  Home,
  Lock,
  Mail,
  User,
} from "lucide-react";
import { useEffect, useState, type SelectHTMLAttributes } from "react";
import { useAuth, type RegisterPayload } from "@/context/auth-context";
import { dashboardPath } from "@/lib/dashboard";
import { FloatingInput } from "./login";
import { MasenoLogo } from "@/components/layout/Logo";
import { getPublicProgrammes } from "@/lib/api";

export const Route = createFileRoute("/register")({ component: RegisterPage });
type Step = 1 | 2 | 3;
type FormData = RegisterPayload & { confirm_password: string };
type ProgrammeOption = { id: string; name: string };

const minimumGraduationYear = 1990;
const passwordRules = [
  ["At least 8 characters", (value: string) => value.length >= 8],
  ["One uppercase letter", (value: string) => /[A-Z]/.test(value)],
  ["One lowercase letter", (value: string) => /[a-z]/.test(value)],
  ["One number", (value: string) => /\d/.test(value)],
  ["One special character", (value: string) => /[^A-Za-z0-9]/.test(value)],
] as const;

function RegisterPage() {
  const { register: registerAccount } = useAuth();
  const navigate = useNavigate();
  const [step, setStep] = useState<Step>(1);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [programmeOptions, setProgrammeOptions] = useState<ProgrammeOption[]>([]);
  const [programmesError, setProgrammesError] = useState<string | null>(null);
  useEffect(() => {
    getPublicProgrammes()
      .then((programmes) => setProgrammeOptions(programmes.map((programme) => ({ id: String(programme.programme_id), name: programme.programme_name }))))
      .catch(() => setProgrammesError("Programme options could not be loaded. Please refresh and try again."));
  }, []);
  const {
    register,
    handleSubmit,
    trigger,
    getValues,
    formState: { errors, isSubmitting },
  } = useForm<FormData>({ mode: "onTouched" });
  const fields: Record<Step, (keyof FormData)[]> = {
    1: ["first_name", "last_name"],
    2: ["programme", "graduation_year"],
    3: ["email", "password", "confirm_password"],
  };
  const next = async () => {
    if (await trigger(fields[step]))
      setStep((current) => (current < 3 ? ((current + 1) as Step) : current));
  };
  const onSubmit = handleSubmit(async (data) => {
    setSubmitError(null);
    try {
      const user = await registerAccount(data);
      await navigate({ to: dashboardPath(user.role) });
    } catch (error) {
      setSubmitError(
        error instanceof Error
          ? error.message
          : "Registration could not be completed. Please try again.",
      );
    }
  });
  const maximumGraduationYear = new Date().getFullYear() + 1;
  const graduationYears = Array.from(
    { length: maximumGraduationYear - minimumGraduationYear + 1 },
    (_, index) => maximumGraduationYear - index,
  );
  return (
    <div className="min-h-screen bg-[radial-gradient(circle_at_8%_2%,oklch(0.34_0.13_262_/_0.12),transparent_22rem),radial-gradient(circle_at_95%_90%,oklch(0.82_0.16_82_/_0.22),transparent_25rem),var(--background)] px-4 py-6 sm:px-6 sm:py-10">
      <div className="mx-auto max-w-2xl">
        <div className="mb-6 flex items-center justify-between">
          <MasenoLogo size={44} />
          <div className="flex items-center gap-2">
            <Link
              to="/"
              aria-label="Home"
              className="grid h-10 w-10 place-items-center rounded-lg border border-border bg-card text-primary transition hover:bg-muted"
            >
              <Home className="h-5 w-5" />
            </Link>
            <Link
              to="/login"
              className="rounded-lg px-3 py-2 text-sm font-semibold text-primary hover:bg-primary/5"
            >
              Sign in
            </Link>
          </div>
        </div>
        <div className="rounded-3xl border border-white/70 bg-card/95 p-5 shadow-[0_24px_70px_oklch(0.22_0.04_262_/_0.12)] backdrop-blur sm:p-9">
          <StepIndicator step={step} />
          <form onSubmit={onSubmit} className="mt-8">
            <AnimatePresence mode="wait">
              <motion.div
                key={step}
                initial={{ opacity: 0, x: 16 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -16 }}
                className="space-y-4"
              >
                {step === 1 && (
                  <>
                    <div>
                      <h1 className="text-xl font-semibold">Create your graduate account</h1>
                      <p className="mt-1 text-sm text-muted-foreground">
                        Register directly and continue to your Graduate dashboard.
                      </p>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                      <FloatingInput
                        icon={User}
                        label="First name"
                        error={errors.first_name?.message}
                        {...register("first_name", { required: "First name is required." })}
                      />
                      <FloatingInput
                        icon={User}
                        label="Last name"
                        error={errors.last_name?.message}
                        {...register("last_name", { required: "Last name is required." })}
                      />
                    </div>
                  </>
                )}
                {step === 2 && (
                  <>
                    <h2 className="text-xl font-semibold">Academic information</h2>
                    <p className="text-sm text-muted-foreground">Your Maseno programme details.</p>
                    <FloatingSelect
                      icon={GraduationCap}
                      label="Programme"
                      error={errors.programme?.message}
                      {...register("programme", { required: "Programme is required." })}
                    >
                      <option value="" disabled>
                        Select Programme
                      </option>
                      {programmeOptions.map((programme) => (
                        <option key={programme.id} value={programme.id}>
                          {programme.name}
                        </option>
                      ))}
                    </FloatingSelect>
                    {programmesError && <p className="text-sm text-destructive">{programmesError}</p>}{" "}
                    <FloatingSelect
                      icon={CalendarDays}
                      label="Graduation year"
                      error={errors.graduation_year?.message}
                      {...register("graduation_year", {
                        required: "Graduation year is required.",
                        setValueAs: (value) => (value === "" ? undefined : Number(value)),
                        validate: (value) =>
                          (value !== undefined &&
                            Number.isInteger(value) &&
                            value >= minimumGraduationYear &&
                            value <= maximumGraduationYear) ||
                          "Enter a valid year.",
                      })}
                    >
                      <option value="" disabled>
                        Select graduation year
                      </option>
                      {graduationYears.map((year) => (
                        <option key={year} value={year}>
                          {year}
                        </option>
                      ))}
                    </FloatingSelect>
                  </>
                )}
                {step === 3 && (
                  <>
                    <h2 className="text-xl font-semibold">Secure your account</h2>
                    <FloatingInput
                      icon={Mail}
                      label="Email address"
                      type="email"
                      autoComplete="email"
                      error={errors.email?.message}
                      {...register("email", {
                        required: "Email is required.",
                        pattern: {
                          value: /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/,
                          message: "Enter a valid email address.",
                        },
                      })}
                    />
                    <FloatingInput
                      icon={Lock}
                      label="Password"
                      type="password"
                      autoComplete="new-password"
                      error={errors.password?.message}
                      {...register("password", {
                        required: "Password is required.",
                        validate: {
                          minLength: (value) =>
                            value.length >= 8 || "Password must have at least 8 characters.",
                          uppercase: (value) =>
                            /[A-Z]/.test(value) || "Password must include an uppercase letter.",
                          lowercase: (value) =>
                            /[a-z]/.test(value) || "Password must include a lowercase letter.",
                          number: (value) => /\d/.test(value) || "Password must include a number.",
                          special: (value) =>
                            /[^A-Za-z0-9]/.test(value) ||
                            "Password must include a special character.",
                        },
                      })}
                    />
                    <FloatingInput
                      icon={Lock}
                      label="Confirm password"
                      type="password"
                      autoComplete="new-password"
                      error={errors.confirm_password?.message}
                      {...register("confirm_password", {
                        required: "Please confirm your password.",
                        validate: (value) =>
                          value === getValues("password") || "Passwords do not match.",
                      })}
                    />
                  </>
                )}
              </motion.div>
            </AnimatePresence>
            {submitError && (
              <div
                role="alert"
                className="mt-5 flex gap-2 rounded-xl border border-destructive/25 bg-destructive/5 p-3 text-sm text-destructive"
              >
                <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                {submitError}
              </div>
            )}
            <div className="mt-8 flex items-center justify-between gap-3">
              <button
                type="button"
                onClick={() =>
                  setStep((current) => (current > 1 ? ((current - 1) as Step) : current))
                }
                disabled={step === 1}
                className="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50"
              >
                <ArrowLeft className="h-4 w-4" />
                Back
              </button>
              {step < 3 ? (
                <button
                  type="button"
                  onClick={next}
                  className="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:-translate-y-0.5"
                >
                  <span>Next</span>
                  <ArrowRight className="h-4 w-4" />
                </button>
              ) : (
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="inline-flex items-center gap-2 rounded-xl bg-[var(--gold)] px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60"
                >
                  <Check className="h-4 w-4" />
                  {isSubmitting ? "CreatingÃ¢â‚¬Â¦" : "Create account"}
                </button>
              )}
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
function FloatingSelect({
  label,
  icon: Icon,
  error,
  className,
  children,
  ...rest
}: SelectHTMLAttributes<HTMLSelectElement> & {
  label: string;
  icon: React.ComponentType<{ className?: string }>;
  error?: string;
}) {
  return (
    <div>
      <div
        className={`group relative flex items-center rounded-xl border bg-card transition-all focus-within:border-primary focus-within:shadow-[0_0_0_3px_oklch(0.34_0.13_262_/_0.15)] ${error ? "border-destructive" : "border-input"} ${className ?? ""}`}
      >
        <Icon className="ml-3 h-4 w-4 shrink-0 text-muted-foreground" />
        <select
          {...rest}
          aria-invalid={Boolean(error)}
          className="h-12 w-full appearance-none bg-transparent px-3 pt-4 text-sm text-foreground outline-none"
        >
          {children}
        </select>
        <ChevronDown className="pointer-events-none mr-3 h-4 w-4 shrink-0 text-muted-foreground" />
        <label className="pointer-events-none absolute left-9 top-3 text-[11px] text-muted-foreground">
          {label}
        </label>
      </div>
      {error && <p className="mt-1.5 text-xs font-medium text-destructive">{error}</p>}
    </div>
  );
}
function StepIndicator({ step }: { step: Step }) {
  return (
    <div className="flex items-center gap-2">
      {["Account", "Details", "Security"].map((label, index) => {
        const number = (index + 1) as Step;
        const active = step === number;
        const done = step > number;
        return (
          <div key={label} className="flex flex-1 items-center gap-2">
            <span
              className={`grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-semibold ${done ? "bg-[var(--success)] text-white" : active ? "bg-primary text-primary-foreground" : "bg-muted text-muted-foreground"}`}
            >
              {done ? <Check className="h-4 w-4" /> : number}
            </span>
            <span
              className={`hidden text-sm font-semibold sm:inline ${active ? "text-foreground" : "text-muted-foreground"}`}
            >
              {label}
            </span>
            {index < 2 && (
              <span className={`h-0.5 flex-1 ${done ? "bg-[var(--success)]" : "bg-border"}`} />
            )}
          </div>
        );
      })}
    </div>
  );
}
