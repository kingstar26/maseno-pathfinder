import { createFileRoute } from "@tanstack/react-router";
import { ProfileWorkspacePage } from "@/components/role-workspace/ProfileWorkspacePage";
export const Route = createFileRoute("/_authenticated/management/profile")({ component: () => <ProfileWorkspacePage role="management" /> });