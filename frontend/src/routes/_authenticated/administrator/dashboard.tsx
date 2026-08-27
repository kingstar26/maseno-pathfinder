import { createFileRoute } from "@tanstack/react-router";
import { AdministratorDashboard } from "@/components/AdministratorDashboard";
export const Route = createFileRoute("/_authenticated/administrator/dashboard")({ component: AdministratorDashboard });