import { createFileRoute } from "@tanstack/react-router";
import { EmploymentPage } from "@/components/GraduateModules";

export const Route = createFileRoute("/_authenticated/employment")({ component: EmploymentPage });
