import { createFileRoute } from "@tanstack/react-router";
import { ProfileWorkspacePage } from "@/components/role-workspace/ProfileWorkspacePage";
export const Route = createFileRoute("/_authenticated/administrator/profile")({ component: () => <ProfileWorkspacePage role="administrator" /> });