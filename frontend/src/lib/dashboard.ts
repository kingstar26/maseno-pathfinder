import type { UserRole } from "@/types";

type DashboardPath = "/dashboard" | "/management/dashboard" | "/administrator/dashboard";

export function dashboardPath(role: UserRole | string): DashboardPath {
  const normalizedRole = role.trim().toLowerCase();
  if (normalizedRole === "management") return "/management/dashboard";
  if (normalizedRole === "administrator" || normalizedRole === "admin") return "/administrator/dashboard";
  return "/dashboard";
}