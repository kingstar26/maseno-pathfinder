import { createFileRoute } from "@tanstack/react-router";
import { ModulePage } from "@/components/ModulePage";
import { moduleSpecs } from "@/components/module-specs";
import { ManagementAnalytics } from "@/components/ManagementAnalytics";
import { useAuth } from "@/context/auth-context";

export const Route = createFileRoute("/_authenticated/reports")({ component: ReportsPage });

function ReportsPage() {
  const { user } = useAuth();
  if (user?.role === "management") {
    return <ManagementAnalytics />;
  }
  return <ModulePage {...moduleSpecs.reports} />;
}