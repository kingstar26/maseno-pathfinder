import { Bell, ChevronDown, LogOut, Menu, Search, Settings, User, X } from "lucide-react";
import { useState, useEffect } from "react";
import { Link, useNavigate } from "@tanstack/react-router";
import { AnimatePresence, motion } from "framer-motion";
import toast from "react-hot-toast";
import { useAuth } from "@/context/auth-context";
import { resolveAssetUrl } from "@/lib/api";
import { getGraduateNotificationsUnreadCount } from "@/lib/api";
import { workspaceHome, workspaceNavigation, type WorkspacePath } from "@/components/role-workspace/config";
import { graduateItems } from "./Sidebar";

export function Header({
  onOpenMobileSidebar,
  onToggleSidebar,
}: {
  onOpenMobileSidebar: () => void;
  onToggleSidebar: () => void;
}) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);
  const staffRole =
    user?.role === "management" || user?.role === "administrator" ? user.role : null;
  const profilePath = staffRole ? (`/${staffRole}/profile` as WorkspacePath) : "/profile";
  const settingsPath =
    staffRole === "administrator"
      ? "/administrator/settings"
      : staffRole
        ? (`/${staffRole}/settings` as WorkspacePath)
        : "/settings";
  const notificationPath = staffRole ? workspaceHome(staffRole) : "/notifications";
  const name = user ? `${user.first_name} ${user.last_name}`.trim() : "Graduate";
  const initial = user?.first_name?.[0]?.toUpperCase() || "G";
  const lastLogin = formatLastLogin(user?.last_login_at);
  const avatarSrc = resolveAssetUrl(user?.avatar_url);
  console.log('[Header] Render - user.avatar_url:', user?.avatar_url, 'resolved avatarSrc:', avatarSrc);

  return (
    <header className="sticky top-0 z-20 border-b border-border/70 bg-background/85 backdrop-blur-md">
      <div className="flex h-16 items-center gap-3 px-4 sm:px-6">
        <button
          onClick={onOpenMobileSidebar}
          className="rounded-lg p-2 hover:bg-muted lg:hidden"
          aria-label="Open menu"
        >
          <Menu className="h-5 w-5" />
        </button>
        <button
          onClick={onToggleSidebar}
          className="hidden rounded-lg p-2 hover:bg-muted lg:inline-flex"
          aria-label="Collapse or expand sidebar"
        >
          <Menu className="h-5 w-5" />
        </button>
        <div className="min-w-0 flex-1">
          <p className="truncate text-sm font-semibold">
            Welcome back, {user?.first_name || "Graduate"}
          </p>
          <p className="truncate text-xs text-muted-foreground">Last login: {lastLogin}</p>
        </div>
        <PortalSearch />
        <Link
          to={notificationPath}
          className="relative rounded-lg p-2.5 hover:bg-muted"
          aria-label="Notifications"
        >
          <Bell className="h-5 w-5" />
          {!staffRole && <UnreadBadge />}
        </Link>
        <div className="relative">
          <button
            onClick={() => setOpen((value) => !value)}
            className="flex items-center gap-2 rounded-full border border-border bg-card py-1 pl-1 pr-2.5 transition hover:shadow-sm"
          >
            <span className="grid h-8 w-8 overflow-hidden place-items-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
              {avatarSrc ? (
                <img src={avatarSrc} alt={name} className="h-full w-full object-cover" />
              ) : (
                initial
              )}
            </span>
            <span className="hidden max-w-36 truncate text-sm font-medium sm:inline">{name}</span>
            <ChevronDown className="h-4 w-4 text-muted-foreground" />
          </button>
          <AnimatePresence>
            {open && (
              <motion.div
                initial={{ opacity: 0, y: -6 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -6 }}
                className="absolute right-0 top-12 z-50 w-52 overflow-hidden rounded-xl border border-border bg-popover shadow-lg"
              >
                <Link
                  to={profilePath}
                  onClick={() => setOpen(false)}
                  className="flex items-center gap-2 px-4 py-3 text-sm hover:bg-muted"
                >
                  <User className="h-4 w-4" />
                  My profile
                </Link>
                <Link
                  to={settingsPath}
                  onClick={() => setOpen(false)}
                  className="flex items-center gap-2 px-4 py-3 text-sm hover:bg-muted"
                >
                  <Settings className="h-4 w-4" />
                  Settings
                </Link>
                <button
                  onClick={() => {
                    logout();
                    navigate({ to: "/login" });
                  }}
                  className="flex w-full items-center gap-2 border-t border-border px-4 py-3 text-left text-sm text-[var(--gold)] hover:bg-[var(--gold)]/10"
                >
                  <LogOut className="h-4 w-4" />
                  Logout
                </button>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      </div>
    </header>
  );}

type SearchableItem = {
  label: string;
  to: string;
  icon: React.ComponentType<{ className?: string }>;
  keywords: string[];
};

function PortalSearch() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [query, setQuery] = useState("");
  const [openDropdown, setOpenDropdown] = useState(false);

  const role = user?.role || "graduate";

  const searchIndex: Record<string, SearchableItem[]> = {
    graduate: [
      { label: "Dashboard", to: "/dashboard", icon: graduateItems[0].icon, keywords: ["home", "overview", "stats", "dashboard", "main"] },
      { label: "Profile", to: "/profile", icon: graduateItems[1].icon, keywords: ["profile", "personal", "bio", "contact", "account", "details"] },
      { label: "Employment", to: "/employment", icon: graduateItems[2].icon, keywords: ["employment", "job", "work", "career", "company", "position", "jobs"] },
      { label: "Business", to: "/business", icon: graduateItems[2].icon, keywords: ["business", "entrepreneurship", "startup", "enterprise", "self-employed"] },
      { label: "Further Studies", to: "/further-studies", icon: graduateItems[1].icon, keywords: ["further studies", "education", "masters", "phd", "postgraduate"] },
      { label: "Tracer Survey", to: "/survey", icon: graduateItems[3].icon, keywords: ["survey", "tracer", "questionnaire", "feedback", "answers", "review"] },
      { label: "Recommendation Letters", to: "/recommendations", icon: graduateItems[4].icon, keywords: ["recommendation", "letter", "recommendations", "letters", "request", "referee", "reference"] },
      { label: "Events", to: "/events", icon: graduateItems[5].icon, keywords: ["events", "event", "workshop", "webinar", "gathering", "meet", "calendar"] },
      { label: "Notifications", to: "/notifications", icon: graduateItems[6].icon, keywords: ["notifications", "alerts", "messages", "unread"] },
      { label: "Announcements", to: "/announcements", icon: graduateItems[7].icon, keywords: ["announcements", "news", "notice", "updates"] },
      { label: "Settings", to: "/settings", icon: graduateItems[8].icon, keywords: ["settings", "preferences", "password", "security", "options"] },
    ],
    management: [
      { label: "Dashboard", to: "/management/dashboard", icon: graduateItems[0].icon, keywords: ["dashboard", "home", "overview"] },
      { label: "Graduates", to: "/management/graduates", icon: graduateItems[1].icon, keywords: ["graduates", "students", "alumni", "users"] },
      { label: "Tracer Surveys", to: "/management/surveys", icon: graduateItems[3].icon, keywords: ["tracer surveys", "survey", "surveys", "questionnaire"] },
      { label: "Recommendation Letters", to: "/management/recommendations", icon: graduateItems[4].icon, keywords: ["recommendation letters", "recommendation", "letter", "letters", "requests"] },
      { label: "Reports", to: "/management/reports", icon: graduateItems[0].icon, keywords: ["reports", "analytics", "export", "statistics", "charts"] },
      { label: "Announcements", to: "/management/announcements", icon: graduateItems[7].icon, keywords: ["announcements", "news", "notice", "updates"] },
      { label: "Settings", to: "/management/settings", icon: graduateItems[8].icon, keywords: ["settings", "preferences", "options"] },
      { label: "Profile", to: "/management/profile", icon: graduateItems[1].icon, keywords: ["profile", "account"] },
    ],
    administrator: [
      { label: "Dashboard", to: "/administrator/dashboard", icon: graduateItems[0].icon, keywords: ["dashboard", "home", "overview"] },
      { label: "Users", to: "/administrator/users", icon: graduateItems[1].icon, keywords: ["users", "accounts", "staff", "graduates", "management", "admin"] },
      { label: "Academic Structure", to: "/administrator/academics", icon: graduateItems[8].icon, keywords: ["academic structure", "academic", "faculties", "departments", "programmes", "structure"] },
      { label: "Reports", to: "/administrator/reports", icon: graduateItems[0].icon, keywords: ["reports", "analytics", "statistics"] },
      { label: "Announcements", to: "/administrator/announcements", icon: graduateItems[7].icon, keywords: ["announcements", "news", "updates"] },
      { label: "Settings", to: "/administrator/settings", icon: graduateItems[8].icon, keywords: ["settings", "system settings", "configuration"] },
      { label: "Profile", to: "/administrator/profile", icon: graduateItems[1].icon, keywords: ["profile", "account"] },
    ],
  };

  const currentItems = searchIndex[role] || searchIndex.graduate;
  const cleanQuery = query.trim().toLowerCase();

  const matches = cleanQuery
    ? currentItems.filter(
        (item) =>
          item.label.toLowerCase().includes(cleanQuery) ||
          item.keywords.some((kw) => kw.toLowerCase().includes(cleanQuery)),
      )
    : [];

  const handleSelect = (to: string) => {
    setQuery("");
    setOpenDropdown(false);
    navigate({ to: to as never });
  };

  return (
    <div className="relative flex-1 max-w-xs sm:max-w-sm">
      <div className="relative">
        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <input
          type="search"
          value={query}
          onFocus={() => setOpenDropdown(true)}
          onChange={(event) => {
            setQuery(event.target.value);
            setOpenDropdown(true);
          }}
          placeholder="Search your portal"
          className="h-10 w-full rounded-full border border-input bg-card pl-10 pr-9 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"
        />
        {query && (
          <button
            type="button"
            onClick={() => {
              setQuery("");
              setOpenDropdown(false);
            }}
            className="absolute right-2 top-1/2 -translate-y-1/2 rounded p-0.5 text-muted-foreground hover:bg-muted"
            aria-label="Clear search"
          >
            <X className="h-4 w-4" />
          </button>
        )}
      </div>

      {query.trim() && openDropdown && (
        <div className="absolute right-0 top-12 z-50 w-full min-w-[280px] sm:min-w-[320px] overflow-hidden rounded-xl border border-border bg-popover p-1 shadow-lg">
          {matches.length ? (
            matches.map((item) => (
              <button
                key={item.to}
                type="button"
                onMouseDown={(e) => {
                  e.preventDefault();
                  handleSelect(item.to);
                }}
                className="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm hover:bg-muted transition-colors"
              >
                <item.icon className="h-4 w-4 text-primary shrink-0" />
                <span className="font-medium text-foreground">{item.label}</span>
              </button>
            ))
          ) : (
            <div className="px-4 py-5 text-center">
              <p className="text-sm font-medium text-foreground">No matching pages were found.</p>
              <p className="mt-1 text-xs text-muted-foreground">Try searching using a different keyword.</p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

function UnreadBadge() {
  const [count, setCount] = useState<number | null>(null);

  useEffect(() => {
    let mounted = true;
    const fetchCount = async () => {
      try {
        const unread = await getGraduateNotificationsUnreadCount();
        if (mounted) setCount(unread);
      } catch (err) {
        if (mounted) setCount(null);
      }
    };

    void fetchCount();

    const handler = () => {
      void fetchCount();
    };

    window.addEventListener('mugts:notifications-updated', handler);
    return () => {
      mounted = false;
      window.removeEventListener('mugts:notifications-updated', handler);
    };
  }, []);

  if (!count) return null;

  return (
    <span className="pointer-events-none absolute -right-0.5 -top-0.5 inline-flex items-center justify-center rounded-full bg-destructive px-1.5 py-0.5 text-[10px] font-medium text-destructive-foreground">
      {count > 9 ? '9+' : count}
    </span>
  );
}

function formatLastLogin(value?: string | null): string {
  if (!value) return "Not available";

  const parsed = new Date(value.includes("T") ? value : value.replace(" ", "T"));
  if (Number.isNaN(parsed.getTime())) return "Not available";

  return new Intl.DateTimeFormat(undefined, {
    weekday: "long",
    hour: "numeric",
    minute: "2-digit",
  }).format(parsed);
}
