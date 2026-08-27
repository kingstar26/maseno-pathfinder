import { createFileRoute } from "@tanstack/react-router";
import { ManagementAnalytics } from "@/components/ManagementAnalytics";

export const Route = createFileRoute("/_authenticated/management/reports")({
  component: ManagementReportsPage,
});

function ManagementReportsPage() {
  return <ManagementAnalytics />;
}