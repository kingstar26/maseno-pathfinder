import { createFileRoute } from "@tanstack/react-router";
import { AdministratorUsers } from "@/components/AdministratorUsers";
export const Route = createFileRoute("/_authenticated/administrator/users")({ component: AdministratorUsers });