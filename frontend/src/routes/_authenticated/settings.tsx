import { createFileRoute } from "@tanstack/react-router";
import { SettingsWorkspacePage } from "@/components/role-workspace/WorkspaceForms";

export const Route = createFileRoute("/_authenticated/settings")({ component: Page });

function Page() {
  return <SettingsWorkspacePage role="graduate" />;
}
