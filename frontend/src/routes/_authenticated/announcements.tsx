import { useCallback, useEffect, useState } from "react";
import { createFileRoute } from "@tanstack/react-router";
import { Megaphone } from "lucide-react";
import { PageHeader } from "@/components/PageHeader";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Skeleton } from "@/components/ui/skeleton";
import { moduleSpecs } from "@/components/module-specs";
import {
  apiErrorMessage,
  getGraduateAnnouncements,
  markGraduateAnnouncementViewed,
  type GraduateAnnouncement,
} from "@/lib/api";

export const Route = createFileRoute("/_authenticated/announcements")({ component: Page });

const previewLimit = 420;

function formatPublishedDate(value: string | null | undefined) {
  if (!value) return "Published recently";

  const date = new Date(value.replace(" ", "T"));
  if (Number.isNaN(date.getTime())) return "Published recently";

  return `Published ${new Intl.DateTimeFormat(undefined, {
    month: "short",
    day: "numeric",
    year: "numeric",
  }).format(date)}`;
}

function Page() {
  const [announcements, setAnnouncements] = useState<GraduateAnnouncement[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedAnnouncement, setSelectedAnnouncement] = useState<GraduateAnnouncement | null>(null);
  const [viewError, setViewError] = useState<string | null>(null);

  const loadAnnouncements = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      setAnnouncements(await getGraduateAnnouncements());
    } catch (err) {
      setError(apiErrorMessage(err, "Unable to load announcements."));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadAnnouncements();
  }, [loadAnnouncements]);

  const spec = moduleSpecs.announcements;
  const newAnnouncementCount = announcements.filter((announcement) => announcement.is_new).length;

  const openAnnouncement = async (announcement: GraduateAnnouncement) => {
    setSelectedAnnouncement(announcement);
    setViewError(null);

    if (!announcement.is_new) return;

    try {
      const viewed = await markGraduateAnnouncementViewed(announcement.announcement_id);
      setAnnouncements((current) => current.map((item) => (
        item.announcement_id === announcement.announcement_id
          ? { ...item, is_new: viewed.is_new, viewed_at: viewed.viewed_at }
          : item
      )));
      setSelectedAnnouncement((current) => (
        current?.announcement_id === announcement.announcement_id
          ? { ...current, is_new: viewed.is_new, viewed_at: viewed.viewed_at }
          : current
      ));
    } catch (err) {
      console.error("Unable to mark announcement as viewed.", err);
      setViewError("We could not save this announcement as viewed. You can still read it and try again later.");
    }
  };

  return (
    <>
      <PageHeader title={spec.title} subtitle={spec.subtitle} />
      {!loading && !error && newAnnouncementCount > 0 ? (
        <p className="mb-4 text-sm font-medium text-primary">
          {newAnnouncementCount} new {newAnnouncementCount === 1 ? "announcement" : "announcements"}
        </p>
      ) : null}
      <section aria-live="polite" className="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)] sm:p-6">
        {loading ? (
          <div className="space-y-4" aria-label="Loading announcements">
            {Array.from({ length: 3 }, (_, index) => (
              <article key={index} className="rounded-2xl border border-border bg-background p-5">
                <Skeleton className="h-6 w-2/3" />
                <Skeleton className="mt-3 h-4 w-36" />
                <Skeleton className="mt-6 h-4 w-full" />
                <Skeleton className="mt-2 h-4 w-5/6" />
              </article>
            ))}
          </div>
        ) : error ? (
          <div className="py-12 text-center">
            <h2 className="text-lg font-semibold">Unable to load announcements</h2>
            <p className="mt-1 text-sm text-muted-foreground">{error}</p>
            <Button className="mt-5" variant="outline" onClick={() => void loadAnnouncements()}>
              Try again
            </Button>
          </div>
        ) : announcements.length === 0 ? (
          <div className="py-12 text-center">
            <Megaphone aria-hidden="true" className="mx-auto size-9 text-muted-foreground" />
            <h2 className="mt-4 text-lg font-semibold">No announcements yet</h2>
            <p className="mt-1 text-sm text-muted-foreground">
              There are currently no published announcements for graduates.
            </p>
          </div>
        ) : (
          <ul className="space-y-4">
            {announcements.map((announcement) => {
              const content = announcement.content || announcement.message;
              const isLong = content.length > previewLimit;
              const preview = isLong ? `${content.slice(0, previewLimit).trimEnd()}…` : content;

              return (
                <li key={announcement.announcement_id}>
                  <article className={`rounded-2xl border bg-background p-5 shadow-sm ${announcement.is_new ? "border-primary/40 bg-primary/[0.03]" : "border-border"}`}>
                    <div className="flex gap-3">
                      <div className="mt-0.5 rounded-full bg-primary/10 p-2 text-primary">
                        <Megaphone aria-hidden="true" className="size-4" />
                      </div>
                      <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                          <div>
                            <h2 className={`text-lg ${announcement.is_new ? "font-bold" : "font-semibold"}`}>{announcement.title}</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                              {formatPublishedDate(announcement.published_at)}
                            </p>
                          </div>
                          <div className="flex items-center gap-2">
                            {announcement.is_new ? (
                              <span className="rounded-full bg-primary px-3 py-1 text-xs font-bold text-primary-foreground">NEW</span>
                            ) : null}
                            <span className="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                              Official update
                            </span>
                          </div>
                        </div>
                        <p className="mt-5 whitespace-pre-line text-sm leading-6 text-muted-foreground">{preview}</p>
                        <Button
                          className="mt-4 px-0"
                          variant="link"
                          onClick={() => void openAnnouncement(announcement)}
                        >
                          Read more <span aria-hidden="true">→</span>
                        </Button>
                      </div>
                    </div>
                  </article>
                </li>
              );
            })}
          </ul>
        )}
      </section>

      <Dialog open={selectedAnnouncement !== null} onOpenChange={(open) => !open && setSelectedAnnouncement(null)}>
        <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
          {selectedAnnouncement ? (
            <>
              <DialogHeader>
                <DialogTitle>{selectedAnnouncement.title}</DialogTitle>
                <DialogDescription>{formatPublishedDate(selectedAnnouncement.published_at)}</DialogDescription>
              </DialogHeader>
              <p className="whitespace-pre-line text-sm leading-7 text-muted-foreground">
                {selectedAnnouncement.content || selectedAnnouncement.message}
              </p>
              {viewError ? <p className="text-sm text-destructive">{viewError}</p> : null}
            </>
          ) : null}
        </DialogContent>
      </Dialog>
    </>
  );
}
