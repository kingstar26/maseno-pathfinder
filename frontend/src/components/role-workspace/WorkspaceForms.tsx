import {
  BellRing,
  Check,
  KeyRound,
  LockKeyhole,
  Monitor,
  Moon,
  RotateCcw,
  Save,
  ShieldCheck,
  Sun,
  UserCog,
} from "lucide-react";
import { useEffect, useState } from "react";
import toast from "react-hot-toast";
import { useAuth } from "@/context/auth-context";
import { useTheme } from "@/context/theme-context";
import { PageHeader } from "@/components/PageHeader";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { getUserPreferences, updateUserPreferences } from "@/lib/api";

type SettingsForm = {
  emailNotifications: boolean;
  emailAnnouncements: boolean;
  emailSystemNotifications: boolean;
  pushNotifications: boolean;
  surveyReminders: boolean;
  profileVisibility: boolean;
  activityVisibility: boolean;
  compactMode: boolean;
};

const defaultSettings: SettingsForm = {
  emailNotifications: true,
  emailAnnouncements: true,
  emailSystemNotifications: true,
  pushNotifications: true,
  surveyReminders: true,
  profileVisibility: true,
  activityVisibility: false,
  compactMode: false,
};

function settingsKey(userId: string) {
  return `mugts.settings.${userId}`;
}

const emailPreferenceKeys = {
  emailAnnouncements: "email_announcements",
  emailSystemNotifications: "email_system_notifications",
} as const;

export function SettingsWorkspacePage({
  role = "administrator",
}: {
  role?: "graduate" | "management" | "administrator";
}) {
  const { theme, setTheme } = useTheme();
  const { user, changePassword } = useAuth();
  const [settings, setSettings] = useState<SettingsForm>(defaultSettings);
  const [savedSettings, setSavedSettings] = useState<SettingsForm>(defaultSettings);
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [changingPassword, setChangingPassword] = useState(false);
  const [preferencesState, setPreferencesState] = useState<"loading" | "loaded" | "failed">("loading");

  useEffect(() => {
    if (!user) return;
    let mounted = true;
    setPreferencesState("loading");

    getUserPreferences()
      .then((prefs) => {
        if (!mounted) return;
        const parsedBackend: Partial<SettingsForm> = {};
        // The legacy global email key supplies a predictable fallback for
        // accounts created before category-specific preferences existed.
        if (prefs.email_notifications !== undefined) {
          const enabled = prefs.email_notifications === "true";
          parsedBackend.emailNotifications = enabled;
          parsedBackend.emailAnnouncements = enabled;
          parsedBackend.emailSystemNotifications = enabled;
        }
        if (prefs[emailPreferenceKeys.emailAnnouncements] !== undefined) {
          parsedBackend.emailAnnouncements = prefs[emailPreferenceKeys.emailAnnouncements] === "true";
        }
        if (prefs[emailPreferenceKeys.emailSystemNotifications] !== undefined) {
          parsedBackend.emailSystemNotifications = prefs[emailPreferenceKeys.emailSystemNotifications] === "true";
        }
        if (prefs.pushNotifications !== undefined) parsedBackend.pushNotifications = prefs.pushNotifications === "true";
        if (prefs.surveyReminders !== undefined) parsedBackend.surveyReminders = prefs.surveyReminders === "true";
        if (prefs.profileVisibility !== undefined) parsedBackend.profileVisibility = prefs.profileVisibility === "true";
        if (prefs.activityVisibility !== undefined) parsedBackend.activityVisibility = prefs.activityVisibility === "true";
        if (prefs.compactMode !== undefined) parsedBackend.compactMode = prefs.compactMode === "true";

        const stored = localStorage.getItem(settingsKey(user.id));
        const localParsed = stored ? (JSON.parse(stored) as Partial<SettingsForm>) : {};
        // Saved email preferences are server-authoritative. Keep browser-local
        // persistence only for unrelated device/UI settings.
        delete localParsed.emailNotifications;
        delete localParsed.emailAnnouncements;
        delete localParsed.emailSystemNotifications;

        const next = { ...defaultSettings, ...localParsed, ...parsedBackend };
        setSettings(next);
        setSavedSettings(next);
        setPreferencesState("loaded");
      })
      .catch(() => {
        if (!mounted) return;
        setPreferencesState("failed");
        toast.error("Saved settings could not be loaded. Please refresh and try again.");
      });

    return () => {
      mounted = false;
    };
  }, [user]);

  useEffect(() => {
    document.documentElement.dataset.compact = settings.compactMode ? "true" : "false";
  }, [settings.compactMode]);

  const updateTheme = (next: "light" | "dark") => {
    setTheme(next);
    toast.success(`${next === "dark" ? "Dark" : "Light"} theme applied.`);
  };
  const updateSetting = <K extends keyof SettingsForm>(key: K, value: SettingsForm[K]) => {
    setSettings((current) => ({ ...current, [key]: value }));
  };
  const saveSettings = async () => {
    if (!user || (role === "graduate" && preferencesState !== "loaded")) return;
    try {
      const payload: Record<string, boolean> = role === "graduate"
        ? {
            [emailPreferenceKeys.emailAnnouncements]: settings.emailAnnouncements,
            [emailPreferenceKeys.emailSystemNotifications]: settings.emailSystemNotifications,
          }
        : {
            email_notifications: settings.emailNotifications,
            pushNotifications: settings.pushNotifications,
            surveyReminders: settings.surveyReminders,
            profileVisibility: settings.profileVisibility,
            activityVisibility: settings.activityVisibility,
            compactMode: settings.compactMode,
          };
      const saved = await updateUserPreferences(payload);
      if (
        role === "graduate" && (
          saved[emailPreferenceKeys.emailAnnouncements] !== String(settings.emailAnnouncements) ||
          saved[emailPreferenceKeys.emailSystemNotifications] !== String(settings.emailSystemNotifications)
        )
      ) {
        throw new Error("The server did not confirm the saved email preferences.");
      }
    } catch {
      toast.error("Notification preferences could not be saved. Please try again.");
      return;
    }
    localStorage.setItem(settingsKey(user.id), JSON.stringify(settings));
    setSavedSettings(settings);
    toast.success("Your settings have been saved.");
  };
  const resetSettings = () => {
    setSettings(defaultSettings);
    setTheme("light");
    if (user) localStorage.removeItem(settingsKey(user.id));
    setSavedSettings(defaultSettings);
    toast.success("Settings reset to their defaults.");
  };
  const updatePassword = async () => {
    if (currentPassword && currentPassword === newPassword) {
      toast.error("Your new password must be different from your current password.");
      return;
    }
    if (newPassword.length < 8) {
      toast.error("Use at least 8 characters for your new password.");
      return;
    }
    if (newPassword !== confirmPassword) {
      toast.error("Your new passwords do not match.");
      return;
    }
    setChangingPassword(true);
    try {
      await changePassword(currentPassword, newPassword);
      setCurrentPassword("");
      setNewPassword("");
      setConfirmPassword("");
      toast.success("Your password has been updated.");
    } catch (error: any) {
      const msg = error?.response?.data?.message || error?.response?.data?.errors?.new_password || error?.message || "Password could not be updated.";
      toast.error(msg);
    } finally {
      setChangingPassword(false);
    }
  };

  const hasChanges = JSON.stringify(settings) !== JSON.stringify(savedSettings);
  return (
    <>
      <PageHeader
        title="Settings"
        subtitle={`Manage your ${role === "graduate" ? "graduate portal" : role} preferences on this device.`}
        actions={
          <>
            <Button variant="outline" onClick={resetSettings}>
              <RotateCcw className="mr-2 h-4 w-4" />
              Reset to Default
            </Button>
          <Button onClick={saveSettings} disabled={!hasChanges || (role === "graduate" && preferencesState !== "loaded")}>
              <Save className="mr-2 h-4 w-4" />
              Save Settings
            </Button>
          </>
        }
      />
      <div className="space-y-5">
          <SettingsSection
            icon={Monitor}
            title="Appearance"
            description="Choose how MUGTS looks across every page on this device."
          >
            <div className="grid gap-3 sm:grid-cols-3">
              {(
                [
                  ["light", "Light", Sun, "A bright, focused workspace"],
                  ["dark", "Dark", Moon, "A comfortable low-light view"],
                ] as const
              ).map(([value, label, Icon, detail]) => (
                <button
                  key={value}
                  type="button"
                  onClick={() => updateTheme(value)}
                  className={`rounded-xl border p-4 text-left transition-all hover:-translate-y-0.5 hover:border-primary/50 ${theme === value ? "border-primary bg-primary/5 ring-2 ring-primary/15" : "border-border bg-muted/15"}`}
                >
                  <Icon className="h-5 w-5 text-primary" />
                  <p className="mt-4 font-semibold">{label}</p>
                  <p className="mt-1 text-xs text-muted-foreground">{detail}</p>
                  {theme === value && (
                    <span className="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-primary">
                      <Check className="h-3.5 w-3.5" />
                      Selected
                    </span>
                  )}
                </button>
              ))}
            </div>
          </SettingsSection>
          <SettingsSection
            icon={BellRing}
            title={role === "graduate" ? "Email Notifications" : "Notification preferences"}
            description={role === "graduate" ? "Choose which important MUGTS updates you'd like to receive by email." : "Choose the updates you want to receive."}
          >
            {role === "graduate" && preferencesState === "loaded" ? (
              <>
                <PreferenceToggle
                  label="Announcements"
                  detail="Get an email when Management publishes a new graduate announcement."
                  checked={settings.emailAnnouncements}
                  onChange={(value) => updateSetting("emailAnnouncements", value)}
                />
                <PreferenceToggle
                  label="System notifications"
                  detail="Receive important system-related notifications by email."
                  checked={settings.emailSystemNotifications}
                  onChange={(value) => updateSetting("emailSystemNotifications", value)}
                />
              </>
            ) : role === "graduate" ? (
              <p className="text-sm text-muted-foreground">
                {preferencesState === "loading"
                  ? "Loading saved email preferences…"
                  : "Saved email preferences could not be loaded. Please refresh and try again."}
              </p>
            ) : (
              <PreferenceToggle
                label="Email notifications"
                detail="Important account and university updates."
                checked={settings.emailNotifications}
                onChange={(value) => updateSetting("emailNotifications", value)}
              />
            )}
            <PreferenceToggle
              label="Browser notifications"
              detail="Allow timely portal alerts on this device."
              checked={settings.pushNotifications}
              onChange={(value) => updateSetting("pushNotifications", value)}
            />
            <PreferenceToggle
              label="Survey reminders"
              detail="Receive reminders for incomplete tracer surveys."
              checked={settings.surveyReminders}
              onChange={(value) => updateSetting("surveyReminders", value)}
            />
          </SettingsSection>
          <SettingsSection
            icon={ShieldCheck}
            title="Privacy settings"
            description="Control the visibility of your personal information."
          >
            <PreferenceToggle
              label="Profile visibility"
              detail="Allow authorised University staff to view your profile."
              checked={settings.profileVisibility}
              onChange={(value) => updateSetting("profileVisibility", value)}
            />
            <PreferenceToggle
              label="Activity visibility"
              detail="Share account activity with authorised University staff."
              checked={settings.activityVisibility}
              onChange={(value) => updateSetting("activityVisibility", value)}
            />
          </SettingsSection>
          <SettingsSection
            icon={UserCog}
            title="Account preferences"
            description="Tune the workspace to suit how you work."
          >
            <PreferenceToggle
              label="Compact workspace"
              detail="Use denser spacing in tables and workspace content."
              checked={settings.compactMode}
              onChange={(value) => updateSetting("compactMode", value)}
            />
          </SettingsSection>
          <SettingsSection
            icon={LockKeyhole}
            title="Password management"
            description="Use a strong password to keep your account secure."
          >
            <div className="grid gap-3 sm:grid-cols-2">
              <div className="sm:col-span-2">
                <Label htmlFor="current-password">Current password</Label>
                <Input
                  id="current-password"
                  type="password"
                  autoComplete="current-password"
                  className="mt-2"
                  value={currentPassword}
                  onChange={(event) => setCurrentPassword(event.target.value)}
                />
              </div>
              <div>
                <Label htmlFor="new-password">New password</Label>
                <Input
                  id="new-password"
                  type="password"
                  autoComplete="new-password"
                  className="mt-2"
                  value={newPassword}
                  onChange={(event) => setNewPassword(event.target.value)}
                />
              </div>
              <div>
                <Label htmlFor="confirm-password">Confirm new password</Label>
                <Input
                  id="confirm-password"
                  type="password"
                  autoComplete="new-password"
                  className="mt-2"
                  value={confirmPassword}
                  onChange={(event) => setConfirmPassword(event.target.value)}
                />
              </div>
            </div>
            <Button
              className="mt-4"
              onClick={updatePassword}
              disabled={changingPassword || !currentPassword || !newPassword || !confirmPassword}
            >
              <KeyRound className="mr-2 h-4 w-4" />
              {changingPassword ? "Updating…" : "Update Password"}
            </Button>
          </SettingsSection>
      </div>
    </>
  );
}

function SettingsSection({
  icon: Icon,
  title,
  description,
  children,
}: {
  icon: typeof Monitor;
  title: string;
  description: string;
  children: React.ReactNode;
}) {
  return (
    <section className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)] sm:p-6">
      <div className="flex gap-3">
        <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
          <Icon className="h-5 w-5" />
        </span>
        <div>
          <h2 className="font-semibold">{title}</h2>
          <p className="mt-1 text-sm text-muted-foreground">{description}</p>
        </div>
      </div>
      <div className="mt-5">{children}</div>
    </section>
  );
}

function PreferenceToggle({
  label,
  detail,
  checked,
  onChange,
}: {
  label: string;
  detail: string;
  checked: boolean;
  onChange: (checked: boolean) => void;
}) {
  return (
    <div className="flex items-center justify-between gap-4 border-b border-border py-3 last:border-b-0 last:pb-0 first:pt-0">
      <div>
        <p className="text-sm font-medium">{label}</p>
        <p className="mt-1 text-xs leading-5 text-muted-foreground">{detail}</p>
      </div>
      <Switch checked={checked} onCheckedChange={onChange} aria-label={label} />{" "}
    </div>
  );
}
