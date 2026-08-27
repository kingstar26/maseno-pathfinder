import { createFileRoute } from "@tanstack/react-router";
import { AdministratorAcademics } from "@/components/AdministratorAcademics";
export const Route = createFileRoute("/_authenticated/administrator/academics")({ component: AdministratorAcademics });