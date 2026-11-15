-- College Hostel Management & Maintenance System
-- MySQL 8.0+ / InnoDB / utf8mb4
CREATE DATABASE IF NOT EXISTS hostel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hostel_management;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS visitors, maintenance, complaints, hostel_fees,
  washing_bookings, washing_machines, attendance, allocation_members,
  room_allocations, rooms, hostels, user_accounts, staff, day_scholars, hostellers, students;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE students (
  student_id VARCHAR(20) PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  student_type ENUM('Hosteller','Day Scholar') NOT NULL,
  dob DATE NOT NULL,
  phone VARCHAR(15) NOT NULL,
  nationality VARCHAR(40) NOT NULL DEFAULT 'Indian',
  year_of_study TINYINT UNSIGNED NOT NULL,
  branch VARCHAR(60) NOT NULL,
  department VARCHAR(80) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  admission_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_student_year CHECK (year_of_study BETWEEN 1 AND 8)
) ENGINE=InnoDB;

CREATE TABLE hostellers (
  student_id VARCHAR(20) PRIMARY KEY,
  distance_from_residence DECIMAL(7,2) NULL,
  emergency_contact VARCHAR(15) NULL,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE day_scholars (
  student_id VARCHAR(20) PRIMARY KEY,
  transport_required BOOLEAN NOT NULL DEFAULT FALSE,
  arrival_time TIME NULL,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

DELIMITER $$
CREATE TRIGGER trg_hosteller_category BEFORE INSERT ON hostellers FOR EACH ROW
BEGIN
  DECLARE v_type VARCHAR(20);
  SELECT student_type INTO v_type FROM students WHERE student_id=NEW.student_id;
  IF v_type <> 'Hosteller' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Only Hosteller category students can be inserted into HOSTELLERS';
  END IF;
END$$
CREATE TRIGGER trg_day_scholar_category BEFORE INSERT ON day_scholars FOR EACH ROW
BEGIN
  DECLARE v_type VARCHAR(20);
  SELECT student_type INTO v_type FROM students WHERE student_id=NEW.student_id;
  IF v_type <> 'Day Scholar' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Only Day Scholar category students can be inserted into DAY_SCHOLARS';
  END IF;
END$$
DELIMITER ;

CREATE TABLE staff (
  staff_id VARCHAR(20) PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(15) NOT NULL,
  staff_role ENUM('Warden','Maintenance','Security','Housekeeping','Reception') NOT NULL,
  date_joined DATE NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE user_accounts (
  account_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  account_role ENUM('Admin','Staff','Student') NOT NULL,
  student_id VARCHAR(20) NULL UNIQUE,
  staff_id VARCHAR(20) NULL UNIQUE,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_account_owner CHECK (
    (account_role = 'Admin' AND student_id IS NULL AND staff_id IS NULL) OR
    (account_role = 'Student' AND student_id IS NOT NULL AND staff_id IS NULL) OR
    (account_role = 'Staff' AND staff_id IS NOT NULL AND student_id IS NULL)
  )
) ENGINE=InnoDB;

CREATE TABLE hostels (
  hostel_id VARCHAR(15) PRIMARY KEY,
  hostel_name VARCHAR(80) NOT NULL,
  hostel_type ENUM('Boys','Girls','Co-ed') NOT NULL,
  warden_staff_id VARCHAR(20) NULL,
  address VARCHAR(180) NOT NULL,
  UNIQUE KEY uq_hostel_name (hostel_name),
  FOREIGN KEY (warden_staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE rooms (
  room_id VARCHAR(20) PRIMARY KEY,
  hostel_id VARCHAR(15) NOT NULL,
  room_number VARCHAR(12) NOT NULL,
  floor_no SMALLINT NOT NULL DEFAULT 0,
  capacity TINYINT UNSIGNED NOT NULL,
  room_status ENUM('Available','Occupied','Maintenance','Unavailable') NOT NULL DEFAULT 'Available',
  UNIQUE KEY uq_room_in_hostel (hostel_id, room_number),
  FOREIGN KEY (hostel_id) REFERENCES hostels(hostel_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_room_capacity CHECK (capacity BETWEEN 1 AND 12)
) ENGINE=InnoDB;

CREATE TABLE room_allocations (
  allocation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id VARCHAR(20) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NULL,
  allocation_status ENUM('Active','Completed','Cancelled') NOT NULL DEFAULT 'Active',
  active_room_id VARCHAR(20) GENERATED ALWAYS AS (CASE WHEN allocation_status = 'Active' THEN room_id ELSE NULL END) STORED,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_one_active_allocation_per_room (active_room_id),
  FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_allocation_dates CHECK (end_date IS NULL OR end_date >= start_date)
) ENGINE=InnoDB;

CREATE TABLE allocation_members (
  allocation_id BIGINT UNSIGNED NOT NULL,
  member_no SMALLINT UNSIGNED NOT NULL,
  student_id VARCHAR(20) NOT NULL,
  join_date DATE NOT NULL,
  leave_date DATE NULL,
  active_student_id VARCHAR(20) GENERATED ALWAYS AS (CASE WHEN leave_date IS NULL THEN student_id ELSE NULL END) STORED,
  PRIMARY KEY (allocation_id, member_no),
  UNIQUE KEY uq_student_one_active_room (active_student_id),
  FOREIGN KEY (allocation_id) REFERENCES room_allocations(allocation_id) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES hostellers(student_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_member_dates CHECK (leave_date IS NULL OR leave_date >= join_date)
) ENGINE=InnoDB;

CREATE TABLE attendance (
  student_id VARCHAR(20) NOT NULL,
  attendance_date DATE NOT NULL,
  attendance_status ENUM('Present','Absent','Late','Leave') NOT NULL,
  marked_by VARCHAR(20) NULL,
  remarks VARCHAR(240) NULL,
  PRIMARY KEY (student_id, attendance_date),
  FOREIGN KEY (student_id) REFERENCES hostellers(student_id) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (marked_by) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE washing_machines (
  machine_id VARCHAR(20) PRIMARY KEY,
  hostel_id VARCHAR(15) NOT NULL,
  machine_number VARCHAR(15) NOT NULL,
  location VARCHAR(100) NOT NULL,
  machine_status ENUM('Available','Out of Service') NOT NULL DEFAULT 'Available',
  UNIQUE KEY uq_machine_per_hostel (hostel_id, machine_number),
  FOREIGN KEY (hostel_id) REFERENCES hostels(hostel_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE washing_bookings (
  booking_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(20) NOT NULL,
  machine_id VARCHAR(20) NOT NULL,
  booking_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  booking_status ENUM('Pending','Approved','Rejected','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES hostellers(student_id) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (machine_id) REFERENCES washing_machines(machine_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_booking_time CHECK (end_time > start_time)
) ENGINE=InnoDB;

CREATE TABLE hostel_fees (
  fee_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  allocation_id BIGINT UNSIGNED NOT NULL,
  member_no SMALLINT UNSIGNED NOT NULL,
  fee_type VARCHAR(50) NOT NULL,
  academic_period VARCHAR(20) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  due_date DATE NOT NULL,
  payment_date DATE NULL,
  payment_status ENUM('Pending','Paid','Overdue','Waived') NOT NULL DEFAULT 'Pending',
  payment_method ENUM('Cash','UPI','Card','Bank Transfer') NULL,
  transaction_reference VARCHAR(80) NULL,
  UNIQUE KEY uq_fee_period (allocation_id, member_no, fee_type, academic_period),
  FOREIGN KEY (allocation_id, member_no) REFERENCES allocation_members(allocation_id, member_no) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_fee_amount CHECK (amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE complaints (
  complaint_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  allocation_id BIGINT UNSIGNED NOT NULL,
  member_no SMALLINT UNSIGNED NOT NULL,
  complaint_type ENUM('Electrical','Plumbing','Furniture','Cleanliness','Internet','Other') NOT NULL,
  description TEXT NOT NULL,
  complaint_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  priority ENUM('Low','Medium','High','Emergency') NOT NULL DEFAULT 'Medium',
  complaint_status ENUM('Open','In Progress','Resolved','Closed','Rejected') NOT NULL DEFAULT 'Open',
  FOREIGN KEY (allocation_id, member_no) REFERENCES allocation_members(allocation_id, member_no) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE maintenance (
  maintenance_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  complaint_id BIGINT UNSIGNED NOT NULL,
  staff_id VARCHAR(20) NULL,
  start_date DATETIME NULL,
  completion_date DATETIME NULL,
  maintenance_status ENUM('Assigned','In Progress','Completed','On Hold') NOT NULL DEFAULT 'Assigned',
  cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  work_notes VARCHAR(500) NULL,
  FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_maintenance_cost CHECK (cost >= 0),
  CONSTRAINT chk_maintenance_dates CHECK (completion_date IS NULL OR start_date IS NULL OR completion_date >= start_date)
) ENGINE=InnoDB;


CREATE INDEX idx_allocation_room_status ON room_allocations(room_id, allocation_status);
CREATE INDEX idx_attendance_date ON attendance(attendance_date);
CREATE INDEX idx_booking_date_machine ON washing_bookings(machine_id, booking_date, start_time, end_time);
CREATE INDEX idx_complaint_status ON complaints(complaint_status, priority);
CREATE INDEX idx_maintenance_staff_status ON maintenance(staff_id, maintenance_status);
CREATE INDEX idx_fee_status_due ON hostel_fees(payment_status, due_date);
