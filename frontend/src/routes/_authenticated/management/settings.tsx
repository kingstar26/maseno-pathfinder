import { createFileRoute } from "@tanstack/react-router";
import { SettingsWorkspacePage } from "@/components/role-workspace/WorkspaceForms";
export const Route = createFileRoute("/_authenticated/management/settings")({ component: () => <SettingsWorkspacePage role="management" /> });