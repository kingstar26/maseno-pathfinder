import { createFileRoute } from "@tanstack/react-router";
import { ManagementAnalytics } from "@/components/ManagementAnalytics";

export const Route = createFileRoute("/_authenticated/administrator/reports")({
  component: AdministratorReportsPage,
});

function AdministratorReportsPage() {
  return <ManagementAnalytics />;
}