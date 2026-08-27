import { createFileRoute } from "@tanstack/react-router";
import { EventsPage } from "@/components/GraduateModules";

export const Route = createFileRoute("/_authenticated/events")({ component: EventsPage });
