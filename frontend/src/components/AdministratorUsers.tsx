import { useEffect, useMemo, useState } from "react";
import toast from "react-hot-toast";
import {
  Filter,
  GraduationCap,
  RotateCw,
  Search,
  Shield,
  ShieldCheck,
  UserCheck,
  Users,
} from "lucide-react";
import { PageHeader } from "@/components/PageHeader";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import {
  getAdministratorUsers,
  getRoles,
  getStatuses,
  updateUserRole,
  updateUserStatus,
  type AdminRole,
  type AdminStatus,
  type AdministratorUser,
} from "@/lib/api";

export function AdministratorUsers() {
  const [users, setUsers] = useState<AdministratorUser[]>([]);
  const [roles, setRoles] = useState<AdminRole[]>([]);
  const [statuses, setStatuses] = useState<AdminStatus[]>([]);
  const [query, setQuery] = useState("");
  const [role, setRole] = useState("all");
  const [loading, setLoading] = useState(true);
  const [updatingId, setUpdatingId] = useState<number | null>(null);

  const load = async () => {
    setLoading(true);
    try {
      const [u, r, s] = await Promise.all([
        getAdministratorUsers(),
        getRoles(),
        getStatuses(),
      ]);
      setUsers(u);
      setRoles(r);
      setStatuses(s);
    } catch {
      toast.error("Users could not be loaded.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, []);

  const rows = useMemo(
    () =>
      users.filter(
        (u) =>
          (role === "all" || u.role === role) &&
          `${u.name} ${u.email}`.toLowerCase().includes(query.toLowerCase())
      ),
    [users, role, query]
  );

  const status = async (id: number, value: string) => {
    setUpdatingId(id);
    try {
      await updateUserStatus(id, Number(value));
      toast.success("Account status updated.");
      await load();
    } catch {
      toast.error("Status could not be updated.");
    } finally {
      setUpdatingId(null);
    }
  };

  const changeRole = async (id: number, value: string) => {
    setUpdatingId(id);
    try {
      await updateUserRole(id, Number(value));
      toast.success("User role updated.");
      await load();
    } catch {
      toast.error(
        "Role could not be updated. The last administrator is protected."
      );
    } finally {
      setUpdatingId(null);
    }
  };

  const getRoleBadgeStyle = (roleCode: string) => {
    const lower = roleCode.toLowerCase();
    if (lower.includes("admin")) {
      return "bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20";
    }
    if (lower.includes("management")) {
      return "bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20";
    }
    return "bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20";
  };

  const getStatusBadgeStyle = (statusCode: string) => {
    const lower = statusCode.toLowerCase();
    if (lower.includes("active")) {
      return "bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20";
    }
    if (lower.includes("suspended")) {
      return "bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20";
    }
    return "bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20";
  };

  const getRoleIcon = (roleCode: string) => {
    const lower = roleCode.toLowerCase();
    if (lower.includes("admin")) return <Shield className="h-3.5 w-3.5" />;
    if (lower.includes("management")) return <UserCheck className="h-3.5 w-3.5" />;
    return <GraduationCap className="h-3.5 w-3.5" />;
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="User management"
        subtitle="Search accounts and manage roles and account access."
      />

      {/* Filter and Control Bar */}
      <section className="rounded-2xl border border-border/80 bg-card p-5 shadow-[var(--shadow-card)]">
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div className="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
            {/* Search Input */}
            <div className="relative flex-1 max-w-md">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                className="pl-9 bg-background/80"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Search name or email..."
              />
            </div>

            {/* Role Filter */}
            <div className="flex items-center gap-2">
              <Filter className="h-4 w-4 text-muted-foreground shrink-0 hidden sm:block" />
              <select
                aria-label="Filter by role"
                className="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20"
                value={role}
                onChange={(e) => setRole(e.target.value)}
              >
                <option value="all">All roles ({users.length})</option>
                {roles.map((r) => (
                  <option key={r.role_id} value={r.code}>
                    {r.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="flex items-center justify-between gap-3 sm:justify-end">
            <span className="text-xs font-medium text-muted-foreground">
              Showing <strong className="text-foreground">{rows.length}</strong> of{" "}
              {users.length}
            </span>

            <Button
              variant="outline"
              size="sm"
              onClick={() => void load()}
              disabled={loading}
              className="gap-2"
            >
              <RotateCw
                className={`h-4 w-4 ${loading ? "animate-spin" : ""}`}
              />
              <span>Refresh</span>
            </Button>
          </div>
        </div>
      </section>

      {/* Main Users Table / Card List */}
      <section className="rounded-2xl border border-border/80 bg-card shadow-[var(--shadow-card)] overflow-hidden">
        {/* Desktop Table View (sm screens and above) */}
        <div className="hidden sm:block overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-border bg-muted/40 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
              <tr>
                <th className="px-5 py-3.5">User</th>
                <th className="px-4 py-3.5">Email</th>
                <th className="px-4 py-3.5">Role</th>
                <th className="px-4 py-3.5">Account Status</th>
                <th className="px-4 py-3.5">Created Date</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border/60">
              {loading && (
                <>
                  {Array.from({ length: 5 }, (_, idx) => (
                    <tr key={idx} className="animate-pulse">
                      <td className="px-5 py-4">
                        <div className="flex items-center gap-3">
                          <Skeleton className="h-9 w-9 rounded-full" />
                          <Skeleton className="h-4 w-32" />
                        </div>
                      </td>
                      <td className="px-4 py-4">
                        <Skeleton className="h-4 w-40" />
                      </td>
                      <td className="px-4 py-4">
                        <Skeleton className="h-9 w-28 rounded-lg" />
                      </td>
                      <td className="px-4 py-4">
                        <Skeleton className="h-9 w-28 rounded-lg" />
                      </td>
                      <td className="px-4 py-4">
                        <Skeleton className="h-4 w-24" />
                      </td>
                    </tr>
                  ))}
                </>
              )}

              {!loading &&
                rows.map((u) => {
                  const isUpdating = updatingId === u.user_id;
                  const roleStyle = getRoleBadgeStyle(u.role);
                  const statusStyle = getStatusBadgeStyle(u.account_status);

                  return (
                    <tr
                      key={u.user_id}
                      className={`transition-colors duration-150 hover:bg-muted/40 ${
                        isUpdating ? "opacity-60" : ""
                      }`}
                    >
                      {/* Name & Avatar */}
                      <td className="px-5 py-3.5">
                        <div className="flex items-center gap-3">
                          <div className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-primary/10 text-xs font-bold text-primary">
                            {u.name
                              .split(" ")
                              .map((n) => n[0])
                              .join("")
                              .slice(0, 2)
                              .toUpperCase()}
                          </div>
                          <span className="font-semibold text-foreground">
                            {u.name}
                          </span>
                        </div>
                      </td>

                      {/* Email */}
                      <td className="px-4 py-3.5 font-mono text-xs text-muted-foreground">
                        {u.email}
                      </td>

                      {/* Role Selector */}
                      <td className="px-4 py-3.5">
                        <div className="relative inline-flex items-center">
                          <div className="pointer-events-none absolute left-2.5 z-10">
                            {getRoleIcon(u.role)}
                          </div>
                          <select
                            aria-label={`Role for ${u.name}`}
                            disabled={isUpdating}
                            className={`h-9 rounded-lg border pl-7 pr-3 text-xs font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-primary/20 ${roleStyle}`}
                            value={u.role_id}
                            onChange={(e) =>
                              void changeRole(u.user_id, e.target.value)
                            }
                          >
                            {roles.map((r) => (
                              <option
                                key={r.role_id}
                                value={r.role_id}
                                className="bg-background text-foreground"
                              >
                                {r.name}
                              </option>
                            ))}
                          </select>
                        </div>
                      </td>

                      {/* Status Selector */}
                      <td className="px-4 py-3.5">
                        <div className="relative inline-flex items-center">
                          <div className="pointer-events-none absolute left-2.5 z-10">
                            <ShieldCheck className="h-3.5 w-3.5" />
                          </div>
                          <select
                            aria-label={`Status for ${u.name}`}
                            disabled={isUpdating}
                            className={`h-9 rounded-lg border pl-7 pr-3 text-xs font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-primary/20 ${statusStyle}`}
                            value={u.account_status_id}
                            onChange={(e) =>
                              void status(u.user_id, e.target.value)
                            }
                          >
                            {statuses.map((s) => (
                              <option
                                key={s.account_status_id}
                                value={s.account_status_id}
                                className="bg-background text-foreground"
                              >
                                {s.name}
                              </option>
                            ))}
                          </select>
                        </div>
                      </td>

                      {/* Created Date */}
                      <td className="px-4 py-3.5 text-xs text-muted-foreground whitespace-nowrap">
                        {new Date(u.created_at).toLocaleDateString(undefined, {
                          year: "numeric",
                          month: "short",
                          day: "numeric",
                        })}
                      </td>
                    </tr>
                  );
                })}
            </tbody>
          </table>
        </div>

        {/* Mobile Card List View (below sm breakpoint) */}
        <div className="block sm:hidden divide-y divide-border/60">
          {loading && (
            <div className="p-4 space-y-4">
              {Array.from({ length: 3 }, (_, idx) => (
                <Skeleton key={idx} className="h-32 w-full rounded-xl" />
              ))}
            </div>
          )}

          {!loading &&
            rows.map((u) => {
              const isUpdating = updatingId === u.user_id;
              const roleStyle = getRoleBadgeStyle(u.role);
              const statusStyle = getStatusBadgeStyle(u.account_status);

              return (
                <div key={u.user_id} className="p-4 space-y-3">
                  <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-2.5 min-w-0">
                      <div className="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-primary/10 text-xs font-bold text-primary">
                        {u.name
                          .split(" ")
                          .map((n) => n[0])
                          .join("")
                          .slice(0, 2)
                          .toUpperCase()}
                      </div>
                      <div className="min-w-0">
                        <p className="font-semibold text-sm text-foreground truncate">
                          {u.name}
                        </p>
                        <p className="text-xs font-mono text-muted-foreground truncate">
                          {u.email}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-2 pt-1">
                    <div>
                      <label className="text-[10px] uppercase font-semibold text-muted-foreground block mb-1">
                        Role
                      </label>
                      <select
                        aria-label={`Role for ${u.name}`}
                        disabled={isUpdating}
                        className={`w-full h-8 rounded-lg border px-2 text-xs font-semibold ${roleStyle}`}
                        value={u.role_id}
                        onChange={(e) =>
                          void changeRole(u.user_id, e.target.value)
                        }
                      >
                        {roles.map((r) => (
                          <option
                            key={r.role_id}
                            value={r.role_id}
                            className="bg-background text-foreground"
                          >
                            {r.name}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="text-[10px] uppercase font-semibold text-muted-foreground block mb-1">
                        Status
                      </label>
                      <select
                        aria-label={`Status for ${u.name}`}
                        disabled={isUpdating}
                        className={`w-full h-8 rounded-lg border px-2 text-xs font-semibold ${statusStyle}`}
                        value={u.account_status_id}
                        onChange={(e) =>
                          void status(u.user_id, e.target.value)
                        }
                      >
                        {statuses.map((s) => (
                          <option
                            key={s.account_status_id}
                            value={s.account_status_id}
                            className="bg-background text-foreground"
                          >
                            {s.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                </div>
              );
            })}
        </div>

        {/* Empty State */}
        {!loading && !rows.length && (
          <div className="flex flex-col items-center justify-center p-12 text-center">
            <div className="rounded-full bg-muted p-3 text-muted-foreground mb-3">
              <Users className="h-6 w-6" />
            </div>
            <p className="font-semibold text-foreground text-sm">
              No matching users found
            </p>
            <p className="text-xs text-muted-foreground mt-1 max-w-sm">
              Try adjusting your search criteria or role filter.
            </p>
          </div>
        )}
      </section>
    </div>
  );
}