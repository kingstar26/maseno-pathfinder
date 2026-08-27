-- announcement_recipients already provides per-graduate announcement state.
-- This key makes a recipient's view state idempotent for each announcement.
ALTER TABLE announcement_recipients
  ADD UNIQUE KEY uq_announcement_recipient_graduate (announcement_id, graduate_profile_id);
