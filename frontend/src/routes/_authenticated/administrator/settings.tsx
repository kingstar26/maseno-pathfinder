import { createFileRoute } from "@tanstack/react-router";
import { AdministratorSettings } from "@/components/AdministratorSettings";
export const Route = createFileRoute("/_authenticated/administrator/settings")({ component: AdministratorSettings });