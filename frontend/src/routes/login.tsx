import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { useForm } from "react-hook-form";
import { motion } from "framer-motion";
import { AlertCircle, Eye, EyeOff, Home, Lock, LogIn, Mail } from "lucide-react";
import { useState } from "react";
import { useAuth } from "@/context/auth-context";
import { dashboardPath } from "@/lib/dashboard";
import { MasenoLogo } from "@/components/layout/Logo";

export const Route = createFileRoute("/login")({ component: LoginPage });
interface LoginForm {
  email: string;
  password: string;
  remember: boolean;
}

function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [showPassword, setShowPassword] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginForm>({ defaultValues: { remember: true } });
  const onSubmit = handleSubmit(async (data) => {
    setSubmitError(null);
    try {
      const user = await login(data.email, data.password);
      await navigate({ to: dashboardPath(user.role) });
    } catch (error) {
      setSubmitError(
        error instanceof Error ? error.message : "Sign in failed. Check your credentials.",
      );
    }
  });
  return (
    <div className="relative grid min-h-screen overflow-hidden bg-[radial-gradient(circle_at_95%_8%,color-mix(in_srgb,var(--primary)_14%,transparent),transparent_24rem),var(--background)] lg:grid-cols-2">
      <Link
        to="/"
        aria-label="Home"
        className="absolute right-4 top-4 z-20 grid h-10 w-10 place-items-center rounded-xl border border-border bg-card text-primary shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
      >
        <Home className="h-5 w-5" />
      </Link>
      <div className="relative hidden overflow-hidden bg-[var(--navy-deep)] text-primary-foreground lg:flex lg:flex-col lg:justify-between lg:p-12">
        <div
          className="absolute inset-0 opacity-40"
          style={{
            backgroundImage:
              "radial-gradient(circle at 15% 20%, oklch(0.82 0.16 82 / 0.55), transparent 18rem), radial-gradient(circle at 90% 85%, oklch(1 0 0 / 0.18), transparent 22rem)",
          }}
        />
        <div className="relative">
          <MasenoLogo size={56} light />
        </div>
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="relative"
        >
          <h1 className="mt-4 text-4xl font-bold leading-[1.12] xl:text-5xl">
            Welcome to the <span className="text-gold">Maseno University</span>
            <br />
            Graduate Tracer System
          </h1>
          <p className="mt-4 max-w-md text-primary-foreground/80">
            Stay connected with Maseno University and manage your graduate journey in one secure
            place.
          </p>
        </motion.div>
        <p className="relative text-xs text-primary-foreground/65">
          © {new Date().getFullYear()} Maseno University. All Rights Reserved.
        </p>
      </div>
      <div className="flex items-center justify-center p-4 sm:p-8 lg:p-12">
        <motion.div
          initial={{ opacity: 0, y: 12 }}
          animate={{ opacity: 1, y: 0 }}
          className="w-full max-w-md rounded-3xl border border-white/70 bg-card/95 p-6 shadow-[0_24px_70px_oklch(0.22_0.04_262_/_0.16)] backdrop-blur sm:p-8"
        >
          <div className="lg:hidden">
            <MasenoLogo size={46} />
            <div className="mt-7 rounded-2xl bg-[var(--navy-deep)] p-5 text-white">
              <p className="text-xl font-bold leading-tight">
                Welcome to <span className="text-gold">Maseno University</span>
              </p>
              <p className="mt-2 text-sm text-white/75">
                Stay connected and manage your graduate journey in one secure place.
              </p>
            </div>
          </div>
          <h2 className="mt-6 text-3xl font-semibold tracking-tight text-primary sm:text-4xl">
            Hi, welcome back
          </h2>
          <p className="mt-2 text-base text-foreground">Please fill in your details to log in</p>
          <form onSubmit={onSubmit} className="mt-7 space-y-4">
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
              type={showPassword ? "text" : "password"}
              autoComplete="current-password"
              error={errors.password?.message}
              rightSlot={
                <button
                  type="button"
                  onClick={() => setShowPassword((value) => !value)}
                  className="rounded p-1 text-muted-foreground hover:text-primary"
                  aria-label={showPassword ? "Hide password" : "Show password"}
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              }
              {...register("password", { required: "Password is required." })}
            />
            {submitError && (
              <div
                role="alert"
                className="flex gap-2 rounded-xl border border-destructive/25 bg-destructive/5 p-3 text-sm text-destructive"
              >
                <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                {submitError}
              </div>
            )}
            <label className="flex items-center gap-2 text-sm text-muted-foreground">
              <input
                type="checkbox"
                className="h-4 w-4 rounded border-input accent-[var(--navy)]"
                {...register("remember")}
              />
              Remember me
            </label>
            <button
              type="submit"
              disabled={isSubmitting}
              className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 text-sm font-semibold text-primary-foreground shadow-md transition-all hover:-translate-y-0.5 hover:bg-[var(--navy-deep)] hover:shadow-lg focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary/25 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-65"
            >
              <LogIn className="h-4 w-4" />
              {isSubmitting ? "Signing in…" : "Sign in"}
            </button>
          </form>
          <p className="mt-6 text-center text-sm text-muted-foreground">
            Don't have an account?{" "}
            <Link to="/register" className="font-semibold text-primary hover:underline">
              Create one
            </Link>
          </p>
        </motion.div>
      </div>
    </div>
  );
}
interface FloatingInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label: string;
  icon: React.ComponentType<{ className?: string }>;
  error?: string;
  rightSlot?: React.ReactNode;
}
export function FloatingInput({
  label,
  icon: Icon,
  error,
  rightSlot,
  className,
  ...rest
}: FloatingInputProps) {
  return (
    <div>
      <div
        className={`group relative flex items-center rounded-xl border bg-card transition-all focus-within:border-primary focus-within:shadow-[0_0_0_3px_oklch(0.34_0.13_262_/_0.15)] ${error ? "border-destructive" : "border-input"} ${className ?? ""}`}
      >
        <Icon className="ml-3 h-4 w-4 shrink-0 text-muted-foreground" />
        <input
          {...rest}
          placeholder=" "
          aria-invalid={Boolean(error)}
          className="peer h-12 w-full bg-transparent px-3 pt-4 text-sm text-foreground outline-none placeholder-transparent"
        />
        <label className="pointer-events-none absolute left-9 top-1/2 -translate-y-1/2 text-sm text-muted-foreground transition-all peer-placeholder-shown:top-1/2 peer-focus:top-3 peer-focus:text-[11px] peer-focus:text-primary peer-[:not(:placeholder-shown)]:top-3 peer-[:not(:placeholder-shown)]:text-[11px]">
          {label}
        </label>
        {rightSlot && <div className="pr-3">{rightSlot}</div>}
      </div>
      {error && <p className="mt-1.5 text-xs font-medium text-destructive">{error}</p>}
    </div>
  );
}
