# Hostel Portal Management System

A role-based College Hostel Management & Maintenance System built with **PHP 8 + MySQL 8**. It is designed for local demonstration with XAMPP. The website uses PHP sessions, password hashes, PDO prepared statements, role checks and CSRF tokens.

## 1. Software required

- Windows 10/11
- XAMPP with Apache, MySQL and PHP 8.0 or later
- A modern browser (Chrome / Edge)
- phpMyAdmin (included with XAMPP)

## 2. Installation

1. Install and open XAMPP.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Copy this complete `Hostel_Management_System` folder into `C:\xampp\htdocs\`.
4. Open `http://localhost/phpmyadmin`.
5. Select **Import** and import `sql/schema.sql` first. **Important:** this academic setup script drops and recreates the project tables with these names. Back up any existing `hostel_management` database before importing it.
6. Import `sql/seed.sql` next. This creates sample hostel, room, resident, staff and service records.
7. Open `config.php`. The default XAMPP values are host `127.0.0.1`, database `hostel_management`, username `root`, password blank. Change these if your MySQL configuration differs.
8. Open `http://localhost/Hostel_Management_System/login.php`.

If MySQL does not start, check for a port conflict (commonly port 3306) before changing any project code.

## 3. Demo logins

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Staff (Maintenance) | `staff01` | `staff123` |
| Student | `2025UCA1890` | `student123` |

Change demo passwords from **Settings** before using the system with real data. These are sample records only.

## 4. What each role can do

### Admin
- Add student records; the application creates the Student/Hosteller or Student/Day Scholar relation and student account in one transaction.
- Add staff and role-based staff accounts.
- Add hostels, rooms and washing machines.
- Allocate a resident to a room or move them to another room while retaining allocation history.
- Create hostel fee records and mark dues as paid.
- View attendance, complaints, maintenance jobs, laundry bookings.
- Assign maintenance work to maintenance staff.

### Staff
- Mark or update daily attendance.
- Review complaints and create maintenance work orders.
- Update assigned maintenance status, notes and cost.
- Approve washing-machine requests.
- Update fee payment status.

### Student
- View current allocation and personal service information.
- Submit maintenance complaints.
- Request a washing-machine time slot from a machine in their own hostel.
- - View fee status, complaint progress, washing bookings.

## 5. Database design notes

The submitted initial schema is documented in `INITIAL_RELATIONAL_SCHEMA.txt`. The final 15-table implementation is documented in `FINAL_RELATIONAL_SCHEMA.txt`. The final SQL intentionally excludes the optional cleaning and electricity log modules to keep the project compact.

- A `ROOM_ALLOCATION` represents one allocation period for a room. `ALLOCATION_MEMBER` identifies the resident(s) occupying the allocation. This supports roommates without repeating room data for every student.
- A hosteller can have historical allocations but only one active allocation membership. The generated `active_student_id` unique key enforces this rule.
- `HOSTELLER` and `DAY_SCHOLAR` are subtypes of `STUDENT`; hostel-only relations reference `HOSTELLER`.
- `ATTENDANCE` is identified by `(Student_ID, Attendance_Date)`.
- `HOSTEL_FEES` and `COMPLAINTS` reference `(Allocation_ID, Member_No)` so they belong to the correct resident within a shared-room allocation without storing a duplicate student ID.
- `MAINTENANCE` references `COMPLAINT` and `STAFF` directly. This is simpler than a separate assignment bridge because one work order is assigned to one staff member in this project version.
- Age is not stored; it is derived from date of birth when needed.

## 6. Important implementation limits

- This is an academic local application, not a production hostel ERP. It does not include a real payment gateway, SMS/email delivery, password reset email, or cloud hosting.
- Washing-machine overlap is checked by the PHP transaction before a booking is inserted. For multi-user production deployment, add a database locking strategy or a dedicated booking-slot table to prevent simultaneous booking races.
- Room capacity is checked by the allocation action before adding a member. For a high-concurrency production setup, enforce the capacity rule with a transactional database trigger/locking strategy as well.
- Set correct MySQL credentials and take a database backup before replacing any existing schema.

## 7. Project report evidence

The project report includes interface demonstration captures from the supplied website package. These are reference captures; take fresh screenshots after running the application locally if your evaluator requires proof of your own live XAMPP session. After importing the SQL files and running the local site, capture your own final evidence using `Win + Shift + S` and replace or supplement the report captures if your teacher expects screenshots from your own live XAMPP run. Recommended evidence: admin login, student creation, room allocation, student complaint submission, staff maintenance update and the resulting rows in phpMyAdmin.

## 8. Final simplified scope

The final implementation contains 15 relations, with visitor management excluded from the final scope. The optional CLEANING and ELECTRICITY_VOLTAGE modules and the redundant MAINTENANCE_ASSIGNMENT bridge from the submitted initial design are omitted. A STAFF relation, USER_ACCOUNTS relation and WASHING_BOOKINGS relation support the actual role-based website. WASHING_MACHINES now represents hostel-owned shared machines. See `INITIAL_RELATIONAL_SCHEMA.txt` for the submitted baseline and `FINAL_RELATIONAL_SCHEMA.txt` for the normalized final design.

## 9. Live localhost screenshots for submission

The screenshots in `screenshots/` were supplied with the earlier website build and should be treated as interface reference captures. After running this final build locally, use `Win + Shift + S` to capture:

1. `http://localhost/Hostel_Management_System/login.php` (login page)
2. Admin dashboard and Add Student page
3. Student dashboard and My Services page
4. Staff maintenance queue with a job marked Completed
5. washing booking page
6. phpMyAdmin showing the final 16 tables in `hostel_management`

Replace the corresponding reference images in `screenshots/` and re-export the report if the evaluator requires live localhost proof. The assistant cannot capture a screenshot from the user's own localhost session.
