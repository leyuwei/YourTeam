ALTER TABLE task_affairs
  ADD COLUMN status ENUM('pending','confirmed') DEFAULT 'pending' AFTER end_time;
UPDATE task_affairs SET status='confirmed';
