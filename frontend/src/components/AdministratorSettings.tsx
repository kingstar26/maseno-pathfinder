import { FormEvent, useEffect, useState } from "react";
import toast from "react-hot-toast";
import {
  CheckCircle2,
  Globe,
  Loader2,
  Lock,
  Mail,
  Moon,
  RotateCw,
  Save,
  ShieldCheck,
  Sun,
  UserPlus,
} from "lucide-react";
import { PageHeader } from "@/components/PageHeader";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { useTheme } from "@/context/theme-context";
import {
  getSystemSettings,
  updateSystemSettings,
  type SystemSettings,
} from "@/lib/api";

export function AdministratorSettings() {
  const { theme, setTheme } = useTheme();
  const [settings, setSettings] = useState<SystemSettings>({
    system_name: "",
    email_configuration: "",
    registration_enabled: "true",
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const fetchSettings = async () => {
    setLoading(true);
    try {
      const res = await getSystemSettings();
      setSettings(res);
    } catch {
      toast.error("System settings could not be loaded.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void fetchSettings();
  }, []);

  const save = async (e: FormEvent) => {
    e.preventDefault();
    if (!settings.system_name.trim()) {
      toast.error("System name is required.");
      return;
    }
    setSaving(true);
    try {
      const next = await updateSystemSettings(settings);
      setSettings(next);
      toast.success("System settings saved successfully.");
    } catch {
      toast.error("Settings could not be saved.");
    } finally {
      setSaving(false);
    }
  };

  const isRegistrationEnabled = settings.registration_enabled === "true";

  const headerActions = (
    <div className="flex items-center gap-2">
      <Button
        type="button"
        variant="outline"
        onClick={() => void fetchSettings()}
        disabled={loading || saving}
        className="gap-2"
      >
        <RotateCw className={`h-4 w-4 ${loading ? "animate-spin" : ""}`} />
        <span>Reset</span>
      </Button>

      <Button
        type="submit"
        form="administrator-settings-form"
        disabled={loading || saving}
        className="gap-2 px-5"
      >
        {saving ? (
          <Loader2 className="h-4 w-4 animate-spin" />
        ) : (
          <Save className="h-4 w-4" />
        )}
        <span>Save Settings</span>
      </Button>
    </div>
  );

  return (
    <div className="space-y-6 w-full max-w-7xl mx-auto">
      <PageHeader
        title="System settings"
        subtitle="Configure shared administrator-managed system options."
        actions={headerActions}
      />

      {loading ? (
        <div className="space-y-6">
          <div className="grid gap-6 grid-cols-1 md:grid-cols-2">
            <Skeleton className="h-52 w-full rounded-2xl" />
            <Skeleton className="h-52 w-full rounded-2xl" />
            <Skeleton className="h-52 w-full rounded-2xl" />
            <Skeleton className="h-52 w-full rounded-2xl" />
          </div>
        </div>
      ) : (
        <form id="administrator-settings-form" onSubmit={(e) => void save(e)} className="space-y-6 w-full">
          {/* Responsive 2-Column Grid Layout for Desktop & Tablet */}
          <div className="grid gap-6 grid-cols-1 md:grid-cols-2">
            {/* Card 1: System Identity */}
            <section className="rounded-2xl border border-border/80 bg-card p-6 shadow-[var(--shadow-card)] flex flex-col justify-between h-full">
              <div className="space-y-4">
                <div className="flex items-center gap-3 border-b border-border/60 pb-3.5">
                  <div className="rounded-xl bg-blue-500/10 p-2.5 text-blue-600 dark:text-blue-400 shrink-0">
                    <Globe className="h-5 w-5" />
                  </div>
                  <div>
                    <h2 className="text-base font-bold tracking-tight text-foreground">
                      System Identity
                    </h2>
                    <p className="text-xs text-muted-foreground">
                      Public application title & header branding.
                    </p>
                  </div>
                </div>

                <div className="space-y-2">
                  <Label
                    htmlFor="system-name-input"
                    className="text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                  >
                    System Name
                  </Label>
                  <Input
                    id="system-name-input"
                    className="bg-background/80"
                    value={settings.system_name}
                    onChange={(e) =>
                      setSettings({ ...settings, system_name: e.target.value })
                    }
                    placeholder="Maseno Pathfinder Graduate Tracer System"
                    required
                  />
                  <p className="text-xs text-muted-foreground pt-1">
                    Displayed in page headers, notification emails, and exports.
                  </p>
                </div>
              </div>
            </section>

            {/* Card 2: Appearance & Theme */}
            <section className="rounded-2xl border border-border/80 bg-card p-6 shadow-[var(--shadow-card)] flex flex-col justify-between h-full">
              <div className="space-y-4">
                <div className="flex items-center justify-between border-b border-border/60 pb-3.5">
                  <div className="flex items-center gap-3">
                    <div className="rounded-xl bg-amber-500/10 p-2.5 text-amber-600 dark:text-amber-400 shrink-0">
                      {theme === "dark" ? (
                        <Moon className="h-5 w-5" />
                      ) : (
                        <Sun className="h-5 w-5" />
                      )}
                    </div>
                    <div>
                      <h2 className="text-base font-bold tracking-tight text-foreground">
                        Appearance & Theme
                      </h2>
                      <p className="text-xs text-muted-foreground">
                        Select interface visual color scheme.
                      </p>
                    </div>
                  </div>
                  <span className="rounded-full bg-muted px-2.5 py-0.5 text-xs font-bold capitalize text-muted-foreground shrink-0">
                    {theme} Mode
                  </span>
                </div>

                <div className="space-y-2">
                  <Label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Color Theme Selection
                  </Label>
                  <div className="grid grid-cols-2 gap-3 pt-1">
                    <button
                      type="button"
                      onClick={() => setTheme("light")}
                      className={`flex items-center justify-center gap-2.5 rounded-xl border p-3 text-xs font-semibold transition-all ${
                        theme === "light"
                          ? "border-primary bg-primary/10 text-primary ring-2 ring-primary/20 shadow-sm"
                          : "border-border/80 bg-background text-muted-foreground hover:bg-muted/50"
                      }`}
                    >
                      <Sun className="h-4 w-4 shrink-0" />
                      <span>Light Theme</span>
                    </button>

                    <button
                      type="button"
                      onClick={() => setTheme("dark")}
                      className={`flex items-center justify-center gap-2.5 rounded-xl border p-3 text-xs font-semibold transition-all ${
                        theme === "dark"
                          ? "border-primary bg-primary/10 text-primary ring-2 ring-primary/20 shadow-sm"
                          : "border-border/80 bg-background text-muted-foreground hover:bg-muted/50"
                      }`}
                    >
                      <Moon className="h-4 w-4 shrink-0" />
                      <span>Dark Theme</span>
                    </button>
                  </div>
                </div>
              </div>
            </section>

            {/* Card 3: Registration Access */}
            <section className="rounded-2xl border border-border/80 bg-card p-6 shadow-[var(--shadow-card)] flex flex-col justify-between h-full">
              <div className="space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-border/60 pb-3.5">
                  <div className="flex items-center gap-3">
                    <div className="rounded-xl bg-emerald-500/10 p-2.5 text-emerald-600 dark:text-emerald-400 shrink-0">
                      <UserPlus className="h-5 w-5" />
                    </div>
                    <div>
                      <h2 className="text-base font-bold tracking-tight text-foreground">
                        Registration Access
                      </h2>
                      <p className="text-xs text-muted-foreground">
                        Graduate portal registration control.
                      </p>
                    </div>
                  </div>

                  {/* Status Badge */}
                  <span
                    className={`self-start sm:self-auto inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold transition-all shrink-0 ${
                      isRegistrationEnabled
                        ? "bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20"
                        : "bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20"
                    }`}
                  >
                    {isRegistrationEnabled ? (
                      <>
                        <CheckCircle2 className="h-3.5 w-3.5" />
                        Registration Enabled
                      </>
                    ) : (
                      <>
                        <Lock className="h-3.5 w-3.5" />
                        Registration Disabled
                      </>
                    )}
                  </span>
                </div>

                <div className="flex items-center justify-between rounded-xl border border-border/60 bg-muted/30 p-4 transition-colors hover:bg-muted/50">
                  <label
                    htmlFor="registration-toggle"
                    className="flex flex-col cursor-pointer select-none pr-3"
                  >
                    <span className="text-sm font-semibold text-foreground">
                      Allow New Graduate Registration
                    </span>
                    <span className="text-xs text-muted-foreground mt-0.5">
                      When disabled, new graduates cannot register self-service accounts.
                    </span>
                  </label>

                  <input
                    id="registration-toggle"
                    type="checkbox"
                    className="h-5 w-5 rounded border-input text-primary focus:ring-2 focus:ring-primary/20 cursor-pointer shrink-0"
                    checked={isRegistrationEnabled}
                    onChange={(e) =>
                      setSettings({
                        ...settings,
                        registration_enabled: e.target.checked ? "true" : "false",
                      })
                    }
                  />
                </div>
              </div>
            </section>

            {/* Card 4: Email & SMTP Configuration */}
            <section className="rounded-2xl border border-border/80 bg-card p-6 shadow-[var(--shadow-card)] flex flex-col justify-between h-full">
              <div className="space-y-4">
                <div className="flex items-center gap-3 border-b border-border/60 pb-3.5">
                  <div className="rounded-xl bg-purple-500/10 p-2.5 text-purple-600 dark:text-purple-400 shrink-0">
                    <Mail className="h-5 w-5" />
                  </div>
                  <div>
                    <h2 className="text-base font-bold tracking-tight text-foreground">
                      Email & SMTP Configuration
                    </h2>
                    <p className="text-xs text-muted-foreground">
                      Administrative reference for email dispatch.
                    </p>
                  </div>
                </div>

                <div className="space-y-3">
                  <div className="space-y-1.5">
                    <Label
                      htmlFor="email-config-input"
                      className="text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                    >
                      Configuration Note
                    </Label>
                    <Input
                      id="email-config-input"
                      className="bg-background/80"
                      value={settings.email_configuration}
                      onChange={(e) =>
                        setSettings({
                          ...settings,
                          email_configuration: e.target.value,
                        })
                      }
                      placeholder="e.g. SMTP via mail.maseno.ac.ke:587 (TLS)"
                    />
                  </div>

                  {/* Informational Security Notice */}
                  <div className="rounded-xl border border-border/60 bg-muted/40 p-3.5 text-xs text-muted-foreground space-y-1">
                    <div className="flex items-center gap-2 text-foreground font-semibold">
                      <ShieldCheck className="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                      <span>Secure Backend Environment</span>
                    </div>
                    <p className="leading-relaxed text-[11px]">
                      SMTP parameters are stored in backend <code className="font-mono text-primary text-[11px]">.env</code> and handled by PHPMailer. Credentials are never exposed to the client interface.
                    </p>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </form>
      )}
    </div>
  );
}