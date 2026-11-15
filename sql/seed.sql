USE hostel_management;

INSERT INTO students (student_id,full_name,student_type,dob,phone,nationality,year_of_study,branch,department,email,admission_date) VALUES
('2025UCA1890','Mohd Fardeen','Hosteller','2006-04-12','9876543210','Indian',2,'Computer Applications','Computer Applications','fardeen.demo@example.edu','2025-08-01'),
('2025UCA1917','Shinjini Kar','Hosteller','2006-02-18','9876543211','Indian',2,'Computer Applications','Computer Applications','shinjini.demo@example.edu','2025-08-01'),
('2025UCA1933','Muskan Poddar','Hosteller','2006-09-22','9876543212','Indian',2,'Computer Applications','Computer Applications','muskan.demo@example.edu','2025-08-01'),
('2025UCA1944','Aarav Sharma','Day Scholar','2005-12-02','9876543213','Indian',3,'Computer Applications','Computer Applications','aarav.demo@example.edu','2024-08-01');
INSERT INTO hostellers (student_id,distance_from_residence,emergency_contact) VALUES
('2025UCA1890',450.00,'9876500001'),('2025UCA1917',520.00,'9876500002'),('2025UCA1933',610.00,'9876500003');
INSERT INTO day_scholars (student_id,transport_required,arrival_time) VALUES ('2025UCA1944',TRUE,'08:45:00');

INSERT INTO staff (staff_id,full_name,email,phone,staff_role,date_joined) VALUES
('STF001','Rakesh Mehta','rakesh.mehta@example.edu','9811100001','Warden','2022-04-01'),
('STF002','Imran Khan','imran.khan@example.edu','9811100002','Maintenance','2023-06-15'),
('STF003','Neha Verma','neha.verma@example.edu','9811100003','Security','2023-08-01'),
('STF004','Pooja Singh','pooja.singh@example.edu','9811100004','Housekeeping','2024-01-10');

INSERT INTO user_accounts (username,password_hash,account_role,student_id,staff_id) VALUES
('admin','$2y$12$8l6un6MADkf57zjpAEDBre9KolUBtJr.ZWfCaH9J1vbbouN5uUtMy','Admin',NULL,NULL),
('staff01','$2y$12$EX1X/TSrkRqVx7NuefP8ruXLfeeDEM0IaINio76lN5LhUuUTq8/Qi','Staff',NULL,'STF002'),
('2025UCA1890','$2y$12$ShQJLTPUaLwqHCOCNMLD1ehm3VgAJdEG2ad0w7dCYArUYg09dR1uK','Student','2025UCA1890',NULL),
('2025UCA1917','$2y$12$ShQJLTPUaLwqHCOCNMLD1ehm3VgAJdEG2ad0w7dCYArUYg09dR1uK','Student','2025UCA1917',NULL);

INSERT INTO hostels (hostel_id,hostel_name,hostel_type,warden_staff_id,address) VALUES
('HST01','Aravali Hostel','Boys','STF001','North Campus, Main Residential Block'),
('HST02','Shivalik Hostel','Girls',NULL,'North Campus, East Residential Block');
INSERT INTO rooms (room_id,hostel_id,room_number,floor_no,capacity,room_status) VALUES
('AR-101','HST01','101',1,3,'Occupied'),('AR-102','HST01','102',1,2,'Available'),('AR-201','HST01','201',2,2,'Available'),
('SH-101','HST02','101',1,3,'Available'),('SH-102','HST02','102',1,2,'Available');
INSERT INTO room_allocations (allocation_id,room_id,start_date,allocation_status) VALUES (1,'AR-101','2025-08-01','Active');
INSERT INTO allocation_members (allocation_id,member_no,student_id,join_date) VALUES
(1,1,'2025UCA1890','2025-08-01'),(1,2,'2025UCA1917','2025-08-01'),(1,3,'2025UCA1933','2025-08-01');
INSERT INTO attendance (student_id,attendance_date,attendance_status,marked_by,remarks) VALUES
('2025UCA1890',CURRENT_DATE,'Present','STF001','Evening roll call'),
('2025UCA1917',CURRENT_DATE,'Present','STF001','Evening roll call'),
('2025UCA1933',CURRENT_DATE,'Late','STF001','Reported after roll call');
INSERT INTO washing_machines (machine_id,hostel_id,machine_number,location,machine_status) VALUES
('WM-A1','HST01','WM-01','Ground floor laundry room','Available'),
('WM-A2','HST01','WM-02','Ground floor laundry room','Available'),
('WM-S1','HST02','WM-01','First floor laundry room','Available');
INSERT INTO washing_bookings (student_id,machine_id,booking_date,start_time,end_time,booking_status) VALUES
('2025UCA1890','WM-A1',CURRENT_DATE,'17:00:00','17:45:00','Approved');
INSERT INTO hostel_fees (allocation_id,member_no,fee_type,academic_period,amount,due_date,payment_status) VALUES
(1,1,'Hostel Fee','2026-27 / Semester 1',32000.00,DATE_ADD(CURRENT_DATE,INTERVAL 10 DAY),'Pending'),
(1,2,'Hostel Fee','2026-27 / Semester 1',32000.00,DATE_ADD(CURRENT_DATE,INTERVAL 10 DAY),'Pending');
INSERT INTO complaints (complaint_id,allocation_id,member_no,complaint_type,description,priority,complaint_status) VALUES
(1,1,1,'Electrical','Study table socket is not working.','Medium','In Progress'),
(2,1,2,'Plumbing','Washroom tap is leaking.','High','Open');
INSERT INTO maintenance (complaint_id,staff_id,start_date,maintenance_status,work_notes) VALUES (1,'STF002',NOW(),'In Progress','Socket inspection scheduled.');
