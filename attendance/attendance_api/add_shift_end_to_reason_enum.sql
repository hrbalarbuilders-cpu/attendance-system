-- Add 'shift_end' to the reason ENUM in attendance_logs table

ALTER TABLE `attendance_logs` 
MODIFY COLUMN `reason` ENUM('lunch','tea','short_leave','shift_start','shift_end') NOT NULL DEFAULT 'shift_start';

