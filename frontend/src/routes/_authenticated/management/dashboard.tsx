import { createFileRoute } from "@tanstack/react-router";
import { WorkspaceDashboard } from "@/components/role-workspace/WorkspaceDashboard";
export const Route = createFileRoute("/_authenticated/management/dashboard")({ component: () => <WorkspaceDashboard role="management" /> });