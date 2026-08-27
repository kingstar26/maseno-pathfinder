import { createFileRoute } from "@tanstack/react-router";
import { RecommendationLettersPage } from "@/components/GraduateModules";
export const Route = createFileRoute("/_authenticated/recommendations")({ component: RecommendationLettersPage });
