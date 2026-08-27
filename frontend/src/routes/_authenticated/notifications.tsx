import { createFileRoute } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { PageHeader } from "@/components/PageHeader";
import { moduleSpecs } from "@/components/module-specs";
import {
  getGraduateNotifications,
  markGraduateNotificationRead,
  deleteGraduateNotification,
  deleteGraduateNotificationsBulk,
} from "@/lib/api";
import type { NotificationItem } from "@/types";
import { Button } from "@/components/ui/button";
import { Trash2 } from "lucide-react";

export const Route = createFileRoute("/_authenticated/notifications")({ component: Page });

function Page() {
  const [items, setItems] = useState<NotificationItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [deletingIds, setDeletingIds] = useState<number[]>([]);
  const [bulkDeleting, setBulkDeleting] = useState(false);

  const fetch = async () => {
    setLoading(true);
    setError(null);
    try {
      const list = await getGraduateNotifications();
      setItems(list as NotificationItem[]);
    } catch (err: any) {
      setError(err?.message || "Could not load notifications.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void fetch();
  }, []);

  const handleOpen = async (id?: number) => {
    if (!id) return;
    try {
      await markGraduateNotificationRead(id);
      window.dispatchEvent(new Event("mugts:notifications-updated"));
      void fetch();
    } catch (err) {
      // ignore
    }
  };

  const toggleSelect = (id: number) => {
    setSelectedIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  };

  const selectAll = (checked: boolean) => {
    if (checked) setSelectedIds(items.map((i) => i.notification_id as number));
    else setSelectedIds([]);
  };

  const handleDeleteSingle = async (e: React.MouseEvent, id: number) => {
    e.stopPropagation();
    const ok = window.confirm("Delete this notification? This action cannot be undone.");
    if (!ok) return;
    setDeletingIds((s) => [...s, id]);
    try {
      await deleteGraduateNotification(id);
      window.dispatchEvent(new Event("mugts:notifications-updated"));
      void fetch();
      setSelectedIds((prev) => prev.filter((x) => x !== id));
    } catch (err) {
      // show error? minimal: console
      console.error(err);
    } finally {
      setDeletingIds((s) => s.filter((x) => x !== id));
    }
  };

  const handleBulkDelete = async () => {
    if (selectedIds.length === 0) return;
    const ok = window.confirm(`Delete ${selectedIds.length} selected notification(s)? This cannot be undone.`);
    if (!ok) return;
    setBulkDeleting(true);
    try {
      await deleteGraduateNotificationsBulk(selectedIds);
      window.dispatchEvent(new Event("mugts:notifications-updated"));
      void fetch();
      setSelectedIds([]);
    } catch (err) {
      console.error(err);
    } finally {
      setBulkDeleting(false);
    }
  };

  const spec = moduleSpecs["notifications"];

  return (
    <>
      <PageHeader title={spec.title} subtitle={spec.subtitle} />
      <section className="rounded-2xl border border-border bg-card p-4 shadow-[var(--shadow-card)] sm:p-5">
        <div className="mb-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <label className="inline-flex items-center gap-2">
              <input
                type="checkbox"
                aria-label="Select all notifications"
                checked={selectedIds.length > 0 && selectedIds.length === items.length}
                onChange={(e) => selectAll(e.target.checked)}
              />
              <span className="text-sm text-muted-foreground">Select all</span>
            </label>
            <span className="text-sm text-muted-foreground">{selectedIds.length} selected</span>
          </div>
          <div>
            <Button variant="destructive" size="sm" disabled={selectedIds.length === 0 || bulkDeleting} onClick={handleBulkDelete}>
              Delete selected
            </Button>
          </div>
        </div>

        {loading ? (
          <p className="text-sm text-muted-foreground">Loading notifications…</p>
        ) : error ? (
          <p className="text-sm text-destructive">{error}</p>
        ) : items.length === 0 ? (
          <div className="py-12 text-center">
            <div className="text-3xl">🔔</div>
            <h3 className="mt-4 text-lg font-semibold">No notifications</h3>
            <p className="mt-1 text-sm text-muted-foreground">You're all caught up.</p>
          </div>
        ) : (
          <ul className="space-y-3">
            {items.map((n) => {
              const isUnread = (n.is_read ?? 0) === 0;
              const isDeleting = deletingIds.includes(n.notification_id as number);
              const isSelected = selectedIds.includes(n.notification_id as number);
              return (
                <li
                  key={n.notification_id}
                  className={`rounded-md border p-3 ${isUnread ? "bg-muted/5 border-primary/10" : "bg-background"}`}
                >
                  <div className="flex items-start gap-3">
                    <div className="shrink-0">
                      <input
                        type="checkbox"
                        aria-label={`Select notification ${n.title}`}
                        checked={isSelected}
                        onChange={(e) => {
                          e.stopPropagation();
                          toggleSelect(n.notification_id as number);
                        }}
                        onClick={(e) => e.stopPropagation()}
                      />
                    </div>
                    <button
                      className="flex w-full flex-1 items-start justify-between text-left"
                      onClick={() => void handleOpen(n.notification_id)}
                    >
                      <div className="flex-1">
                        <div className="flex items-center gap-2">
                          {isUnread && <span className="h-2 w-2 rounded-full bg-primary" />}
                          <div className={`${isUnread ? "font-semibold text-foreground" : "text-foreground"}`}>{n.title}</div>
                          {isUnread && <span className="ml-2 rounded bg-primary/10 px-2 py-0.5 text-xs text-primary">New</span>}
                        </div>
                        <div className="mt-1 text-sm text-muted-foreground">{n.message}</div>
                        <div className="mt-2 text-xs text-muted-foreground">{new Date(n.created_at).toLocaleString()}</div>
                      </div>
                    </button>
                    <div className="ml-3 flex items-start">
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={(e) => void handleDeleteSingle(e as any, n.notification_id as number)}
                        aria-label={`Delete notification ${n.title}`}
                      >
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </div>
                  </div>
                </li>
              );
            })}
          </ul>
        )}
      </section>
    </>
  );
}
