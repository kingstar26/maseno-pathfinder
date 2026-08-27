import { Plus, Search } from "lucide-react";
import { PageHeader } from "@/components/PageHeader";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useBackendCollection } from "./use-backend-collection";

const sections = {
  users: ["Photo", "Name", "Email", "Role", "Status", "Last Login", "Actions"],
  faculties: ["Faculty", "Code", "Departments", "Actions"],
  departments: ["Department", "Faculty", "Code", "Programmes", "Actions"],
  programmes: ["Programme", "Department", "Code", "Level", "Actions"],
} as const;

export function UsersWorkspacePage() {
  const { configured, isLoading, isError } = useBackendCollection<unknown[]>("administrator-users");
  return <><PageHeader title="Users" subtitle="Manage accounts and institutional master data from one controlled workspace." actions={<Button disabled={!configured}><Plus className="mr-2 h-4 w-4" />Add user</Button>} /><Tabs defaultValue="users"><TabsList className="h-auto w-full justify-start overflow-x-auto"><TabsTrigger value="users">Users</TabsTrigger><TabsTrigger value="faculties">Faculties</TabsTrigger><TabsTrigger value="departments">Departments</TabsTrigger><TabsTrigger value="programmes">Programmes</TabsTrigger></TabsList>{Object.entries(sections).map(([section, columns]) => <TabsContent key={section} value={section} className="mt-5"><section className="rounded-2xl border border-border bg-card p-4 shadow-[var(--shadow-card)] sm:p-5"><div className="flex flex-wrap justify-between gap-3"><div className="relative w-full max-w-sm"><Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" /><Input disabled={!configured} className="pl-9" placeholder={`Search ${section}...`} aria-label={`Search ${section}`} title="Search becomes available when records are available." /></div>{section !== "users" && <Button disabled={!configured}><Plus className="mr-2 h-4 w-4" />Add {section.slice(0, -1)}</Button>}</div><div className="mt-5 overflow-x-auto"><Table><TableHeader className="sticky top-0 z-10 bg-muted/80 backdrop-blur"><TableRow>{columns.map((column) => <TableHead key={column} className="whitespace-nowrap">{column}</TableHead>)}</TableRow></TableHeader><TableBody><TableRow><TableCell colSpan={columns.length} className="h-28 text-center text-muted-foreground">{isLoading ? "Loading recordsâ€¦" : isError ? "Records could not be loaded. Please try again." : "No records available yet."}</TableCell></TableRow></TableBody></Table></div><p className="mt-4 text-xs text-muted-foreground">{configured ? "Select a record to use its available actions." : "There are no records to display yet."}</p></section></TabsContent>)}</Tabs></>;
}