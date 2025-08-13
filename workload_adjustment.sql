-- Adjust existing task affair times to align with day-based workload
-- Sets start_time to midnight of its date and end_time to midnight of the next day
UPDATE task_affairs
SET start_time = DATE_FORMAT(start_time, '%Y-%m-%d 00:00:00'),
    end_time   = DATE_FORMAT(DATE_ADD(DATE(end_time), INTERVAL 1 DAY), '%Y-%m-%d 00:00:00')
WHERE 1;
