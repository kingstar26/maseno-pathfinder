import { Link, useRouterState, useNavigate } from "@tanstack/react-router";
import { AnimatePresence, motion } from "framer-motion";
import toast from "react-hot-toast";
import {
  BarChart3,
  Bell,
  Briefcase,
  Building2,
  Calendar,
  ClipboardList,
  FileSignature,
  GraduationCap,
  LayoutDashboard,
  LogOut,
  Megaphone,
  Settings,
  User,
  X,
} from "lucide-react";
import { MasenoLogo } from "./Logo";
import { useAuth } from "@/context/auth-context";
import { cn } from "@/lib/utils";
import { workspaceNavigation, type WorkspacePath } from "@/components/role-workspace/config";

type GraduatePath =
  | "/dashboard"
  | "/profile"
  | "/employment"
  | "/business"
  | "/further-studies"
  | "/survey"
  | "/recommendations"
  | "/announcements"
  | "/events"
  | "/notifications"
  | "/reports"
  | "/settings";
export const graduateItems: {
  label: string;
  to: GraduatePath;
  icon: React.ComponentType<{ className?: string }>;
}[] = [
  { label: "Dashboard", to: "/dashboard", icon: LayoutDashboard },
  { label: "Profile", to: "/profile", icon: User },
  { label: "Employment", to: "/employment", icon: Briefcase },
  { label: "Tracer Survey", to: "/survey", icon: ClipboardList },
  { label: "Recommendation Letters", to: "/recommendations", icon: FileSignature },
  { label: "Events", to: "/events", icon: Calendar },
  { label: "Notifications", to: "/notifications", icon: Bell },
  { label: "Announcements", to: "/announcements", icon: Megaphone },
  { label: "Settings", to: "/settings", icon: Settings },
];

export function Sidebar({
  mobileOpen,
  onCloseMobile,
  collapsed,
}: {
  mobileOpen: boolean;
  onCloseMobile: () => void;
  collapsed: boolean;
}) {
  const { logout, user } = useAuth();
  const navigate = useNavigate();
  const pathname = useRouterState({ select: (state) => state.location.pathname });
  const items =
    user?.role === "management" || user?.role === "administrator"
      ? workspaceNavigation[user.role]
      : graduateItems;
  const content = (
    <div className="flex h-full flex-col bg-sidebar text-sidebar-foreground">
      <div className={cn("flex items-center justify-between py-5", collapsed ? "px-3" : "px-5")}>
        <MasenoLogo size={36} showText={!collapsed} light />
        <button
          onClick={onCloseMobile}
          className="rounded-md p-1.5 hover:bg-sidebar-accent lg:hidden"
          aria-label="Close sidebar"
        >
          <X className="h-5 w-5" />
        </button>
      </div>
      <nav className="flex-1 overflow-y-auto px-3 pb-4">
        <ul className="space-y-1">
          {items.map(({ label, to, icon: Icon }) => (
            <li key={to}>
              <Link
                to={to as GraduatePath | WorkspacePath}
                onClick={onCloseMobile}
                title={collapsed ? label : undefined}
                className={cn(
                  "group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all",
                  collapsed ? "justify-center" : "gap-3",
                  pathname === to
                    ? "bg-[var(--gold)] text-[oklch(0.22_0_0)] shadow-sm"
                    : "text-sidebar-foreground/85 hover:bg-sidebar-accent hover:text-sidebar-foreground",
                )}
              >
                <Icon className="h-[18px] w-[18px] shrink-0" />
                {!collapsed && <span className="truncate">{label}</span>}
              </Link>
            </li>
          ))}
        </ul>
      </nav>
      <div className="border-t border-sidebar-border p-3">
        <button
          onClick={() => {
            logout();
            navigate({ to: "/login" });
          }}
          className={cn(
            "flex w-full items-center rounded-lg px-3 py-2.5 text-sm font-medium bg-[var(--gold)] text-[oklch(0.22_0_0)] hover:bg-[var(--gold)]/90",
            collapsed ? "justify-center" : "gap-3",
          )}
          title={collapsed ? "Logout" : undefined}
        >
          <LogOut className="h-[18px] w-[18px]" />
          {!collapsed && "Logout"}
        </button>
      </div>
    </div>
  );
  return (
    <>
      <aside
        className={cn(
          "hidden shrink-0 transition-[width] duration-300 lg:block",
          collapsed ? "w-20" : "w-64",
        )}
      >
        <div
          className={cn(
            "fixed inset-y-0 left-0 z-30 transition-[width] duration-300",
            collapsed ? "w-20" : "w-64",
          )}
        >
          {content}
        </div>
      </aside>
      <AnimatePresence>
        {mobileOpen && (
          <>
            <motion.div
              className="fixed inset-0 z-40 bg-black/50 lg:hidden"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={onCloseMobile}
            />
            <motion.aside
              className="fixed inset-y-0 left-0 z-50 w-72 lg:hidden"
              initial={{ x: "-100%" }}
              animate={{ x: 0 }}
              exit={{ x: "-100%" }}
              transition={{ type: "tween", duration: 0.25 }}
            >
              {content}
            </motion.aside>
          </>
        )}
      </AnimatePresence>
    </>
  );
}
