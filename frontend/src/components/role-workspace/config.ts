import type { ComponentType } from "react";
import {
  BarChart3, Bell, ClipboardList, FileSignature, LayoutDashboard, Megaphone, Settings,
  ShieldCheck, User, UsersRound,
} from "lucide-react";
import type { UserRole } from "@/types";

export type WorkspaceRole = Extract<UserRole, "management" | "administrator">;
export type WorkspacePath =
  | "/management/dashboard" | "/management/graduates" | "/management/surveys" | "/management/recommendations" | "/management/reports" | "/management/announcements" | "/management/settings" | "/management/profile"
  | "/administrator/dashboard" | "/administrator/users" | "/administrator/academics" | "/administrator/reports" | "/administrator/announcements" | "/administrator/settings" | "/administrator/profile";

export type NavigationItem = { label: string; to: WorkspacePath; icon: ComponentType<{ className?: string }> };

export const workspaceNavigation: Record<WorkspaceRole, NavigationItem[]> = {
  management: [
    { label: "Dashboard", to: "/management/dashboard", icon: LayoutDashboard },
    { label: "Graduates", to: "/management/graduates", icon: UsersRound },
    { label: "Tracer Surveys", to: "/management/surveys", icon: ClipboardList },
    { label: "Recommendation Letters", to: "/management/recommendations", icon: FileSignature },
    { label: "Reports", to: "/management/reports", icon: BarChart3 },
    { label: "Announcements", to: "/management/announcements", icon: Megaphone },
    { label: "Settings", to: "/management/settings", icon: Settings },
    { label: "Profile", to: "/management/profile", icon: User },
  ],
  administrator: [
    { label: "Dashboard", to: "/administrator/dashboard", icon: LayoutDashboard },
    { label: "Users", to: "/administrator/users", icon: UsersRound },
    { label: "Academic structure", to: "/administrator/academics", icon: ShieldCheck },
    { label: "Reports", to: "/administrator/reports", icon: BarChart3 },
    { label: "Announcements", to: "/administrator/announcements", icon: Megaphone },
    { label: "System Settings", to: "/administrator/settings", icon: Settings },
    { label: "Profile", to: "/administrator/profile", icon: User },
  ],
};

export function workspaceHome(role: WorkspaceRole): WorkspacePath {
  return role === "management" ? "/management/dashboard" : "/administrator/dashboard";
}

export const roleLabels: Record<WorkspaceRole, string> = {
  management: "Management",
  administrator: "Administrator",
};