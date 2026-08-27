import { createFileRoute } from "@tanstack/react-router";
import { ProfileWorkspacePage } from "@/components/role-workspace/ProfileWorkspacePage";

export const Route = createFileRoute("/_authenticated/profile")({ component: Page });

function Page() {
  return <ProfileWorkspacePage role="graduate" />;
}
