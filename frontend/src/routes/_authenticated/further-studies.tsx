import { createFileRoute } from "@tanstack/react-router";
import { ModulePage } from "@/components/ModulePage";
import { moduleSpecs } from "@/components/module-specs";

export const Route = createFileRoute("/_authenticated/further-studies")({ component: Page });

function Page() {
  return <ModulePage {...moduleSpecs["further-studies"]} />;
}
