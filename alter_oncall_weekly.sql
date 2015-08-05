-- Add support for hidden events to `oncall_weekly`

-- Change this to the appropriate database.
USE opsweekly;

-- Add support for hidden events.
ALTER TABLE oncall_weekly ADD COLUMN hide_event tinyint(1) NOT NULL DEFAULT 0;
