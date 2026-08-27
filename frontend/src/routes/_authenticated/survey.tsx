import { createFileRoute } from "@tanstack/react-router";
import { TracerSurveyPage } from "@/components/GraduateModules";
export const Route = createFileRoute("/_authenticated/survey")({ component: TracerSurveyPage });
