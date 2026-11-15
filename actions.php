<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login(); verify_csrf();
$pdo = db(); $u = current_user(); $role = $u['account_role']; $action = $_POST['action'] ?? '';
function need(array $roles): void { if (!in_array(current_user()['account_role'], $roles, true)) { http_response_code(403); exit('Not authorised.'); } }
function val(string $key, string $default=''): string { return trim((string)($_POST[$key] ?? $default)); }
function active_member_for_user(PDO $pdo): ?array { $u=current_user(); return $u['student_id'] ? current_member($pdo,$u['student_id']) : null; }
try {
  switch ($action) {
    case 'add_student':
      need(['Admin']);
      $id=val('student_id'); $name=val('full_name'); $type=val('student_type');
      if (!$id || !$name || !filter_var(val('email'),FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a student ID, name and valid email.');
      $pdo->beginTransaction();
      $q=$pdo->prepare('INSERT INTO students(student_id,full_name,student_type,dob,phone,nationality,year_of_study,branch,department,email,admission_date) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
      $q->execute([$id,$name,$type,val('dob'),val('phone'),val('nationality','Indian'),(int)val('year_of_study','1'),val('branch'),val('department'),val('email'),val('admission_date')]);
      if ($type==='Hosteller') $pdo->prepare('INSERT INTO hostellers(student_id,distance_from_residence,emergency_contact) VALUES(?,?,?)')->execute([$id,val('distance') ?: null,val('emergency_contact') ?: null]);
      else $pdo->prepare('INSERT INTO day_scholars(student_id,transport_required,arrival_time) VALUES(?,?,?)')->execute([$id,isset($_POST['transport_required'])?1:0,val('arrival_time') ?: null]);
      $hash=password_hash(val('initial_password','student123'),PASSWORD_DEFAULT);
      $pdo->prepare("INSERT INTO user_accounts(username,password_hash,account_role,student_id) VALUES(?,?,'Student',?)")->execute([$id,$hash,$id]);
      $pdo->commit(); flash('success','Student record and login account created.'); go('students');

    case 'add_staff':
      need(['Admin']); $id=val('staff_id'); $name=val('full_name');
      $pdo->beginTransaction();
      $pdo->prepare('INSERT INTO staff(staff_id,full_name,email,phone,staff_role,date_joined) VALUES(?,?,?,?,?,?)')->execute([$id,$name,val('email'),val('phone'),val('staff_role'),val('date_joined')]);
      $pdo->prepare("INSERT INTO user_accounts(username,password_hash,account_role,staff_id) VALUES(?,?,'Staff',?)")->execute([val('username',$id),password_hash(val('initial_password','staff123'),PASSWORD_DEFAULT),$id]);
      $pdo->commit(); flash('success','Staff profile and login account created.'); go('staff');

    case 'add_hostel':
      need(['Admin']);
      $pdo->prepare('INSERT INTO hostels(hostel_id,hostel_name,hostel_type,warden_staff_id,address) VALUES(?,?,?,?,?)')->execute([val('hostel_id'),val('hostel_name'),val('hostel_type'),val('warden_staff_id') ?: null,val('address')]);
      flash('success','Hostel added.'); go('hostels');

    case 'add_room':
      need(['Admin']);
      $pdo->prepare('INSERT INTO rooms(room_id,hostel_id,room_number,floor_no,capacity,room_status) VALUES(?,?,?,?,?,?)')->execute([val('room_id'),val('hostel_id'),val('room_number'),(int)val('floor_no','0'),(int)val('capacity','2'),val('room_status','Available')]);
      flash('success','Room added.'); go('rooms');

    case 'add_machine':
      need(['Admin']);
      $pdo->prepare('INSERT INTO washing_machines(machine_id,hostel_id,machine_number,location,machine_status) VALUES(?,?,?,?,?)')->execute([val('machine_id'),val('hostel_id'),val('machine_number'),val('location'),val('machine_status','Available')]);
      flash('success','Washing machine added.'); go('facilities');

    case 'allocate_student':
    case 'change_room':
      need(['Admin']); $sid=val('student_id'); $room=val('room_id'); $pdo->beginTransaction();
      $s=$pdo->prepare('SELECT student_id FROM hostellers WHERE student_id=?'); $s->execute([$sid]); if (!$s->fetch()) throw new RuntimeException('This student is not registered as a hosteller.');
      if ($action==='change_room') {
        $q=$pdo->prepare('SELECT allocation_id,member_no FROM allocation_members WHERE student_id=? AND leave_date IS NULL FOR UPDATE'); $q->execute([$sid]); $old=$q->fetch();
        if ($old) {
          $pdo->prepare('UPDATE allocation_members SET leave_date=CURRENT_DATE WHERE allocation_id=? AND member_no=?')->execute([$old['allocation_id'],$old['member_no']]);
          $left=(int)scalar($pdo,'SELECT COUNT(*) FROM allocation_members WHERE allocation_id=? AND leave_date IS NULL',[$old['allocation_id']]);
          if ($left===0) { $pdo->prepare("UPDATE room_allocations SET allocation_status='Completed',end_date=CURRENT_DATE WHERE allocation_id=?")->execute([$old['allocation_id']]); $pdo->prepare("UPDATE rooms SET room_status='Available' WHERE room_id=(SELECT room_id FROM room_allocations WHERE allocation_id=?)")->execute([$old['allocation_id']]); }
        }
      }
      $q=$pdo->prepare("SELECT ra.allocation_id,r.capacity,(SELECT COUNT(*) FROM allocation_members am WHERE am.allocation_id=ra.allocation_id AND am.leave_date IS NULL) AS occupied FROM room_allocations ra JOIN rooms r ON r.room_id=ra.room_id WHERE ra.room_id=? AND ra.allocation_status='Active' FOR UPDATE"); $q->execute([$room]); $alloc=$q->fetch();
      if (!$alloc) { $pdo->prepare("INSERT INTO room_allocations(room_id,start_date,allocation_status) VALUES(?,CURRENT_DATE,'Active')")->execute([$room]); $allocId=(int)$pdo->lastInsertId(); $cap=(int)scalar($pdo,'SELECT capacity FROM rooms WHERE room_id=?',[$room]); $occupied=0; }
      else { $allocId=(int)$alloc['allocation_id']; $cap=(int)$alloc['capacity']; $occupied=(int)$alloc['occupied']; }
      if ($occupied >= $cap) throw new RuntimeException('The selected room has reached its capacity.');
      if (current_member($pdo,$sid)) throw new RuntimeException('This student already has an active room allocation.');
      $next=(int)scalar($pdo,'SELECT COALESCE(MAX(member_no),0)+1 FROM allocation_members WHERE allocation_id=?',[$allocId]);
      $pdo->prepare('INSERT INTO allocation_members(allocation_id,member_no,student_id,join_date) VALUES(?,?,?,CURRENT_DATE)')->execute([$allocId,$next,$sid]);
      $pdo->prepare("UPDATE rooms SET room_status='Occupied' WHERE room_id=?")->execute([$room]);
      $pdo->commit(); flash('success',$action==='change_room'?'Student room changed and previous allocation history retained.':'Student allocated successfully.'); go('allocations');

    case 'add_fee':
      need(['Admin']);
      $parts=explode(':',val('allocation_member')); if(count($parts)!==2) throw new RuntimeException('Choose a valid resident allocation.');
      $pdo->prepare("INSERT INTO hostel_fees(allocation_id,member_no,fee_type,academic_period,amount,due_date,payment_status) VALUES(?,?,?,?,?,?,'Pending')")->execute([(int)$parts[0],(int)$parts[1],val('fee_type'),val('academic_period'),(float)val('amount'),val('due_date')]);
      flash('success','Fee record created.'); go('fees');

    case 'mark_fee_paid':
      need(['Admin','Staff']);
      $pdo->prepare("UPDATE hostel_fees SET payment_status='Paid',payment_date=CURRENT_DATE,payment_method=?,transaction_reference=? WHERE fee_id=?")->execute([val('payment_method'),val('transaction_reference') ?: null,(int)val('fee_id')]);
      flash('success','Fee payment updated.'); go('fees');

    case 'create_complaint':
      need(['Student']); $m=active_member_for_user($pdo); if (!$m) throw new RuntimeException('You need an active room allocation before submitting a hostel complaint.');
      $pdo->prepare('INSERT INTO complaints(allocation_id,member_no,complaint_type,description,priority) VALUES(?,?,?,?,?)')->execute([$m['allocation_id'],$m['member_no'],val('complaint_type'),val('description'),val('priority','Medium')]);
      flash('success','Your complaint has been submitted to the hostel office.'); go('my-requests');

    case 'book_washing':
      need(['Student']); $m=active_member_for_user($pdo); if (!$m) throw new RuntimeException('An active hostel allocation is required.');
      $machine=val('machine_id');
      $hostel=scalar($pdo,'SELECT hostel_id FROM washing_machines WHERE machine_id=? AND machine_status=\'Available\' ',[$machine]);
      if (!$hostel || $hostel!==$m['hostel_id']) throw new RuntimeException('Choose an available machine in your hostel.');
      $conflict=(int)scalar($pdo,"SELECT COUNT(*) FROM washing_bookings WHERE machine_id=? AND booking_date=? AND booking_status IN ('Pending','Approved') AND start_time < ? AND end_time > ?",[$machine,val('booking_date'),val('end_time'),val('start_time')]);
      if ($conflict) throw new RuntimeException('That time slot is already reserved. Choose another time.');
      $pdo->prepare('INSERT INTO washing_bookings(student_id,machine_id,booking_date,start_time,end_time) VALUES(?,?,?,?,?)')->execute([$u['student_id'],$machine,val('booking_date'),val('start_time'),val('end_time')]);
      flash('success','Washing-machine booking request submitted.'); go('my-requests');

    case 'review_booking':
      need(['Staff','Admin']);
      if (!in_array(val('booking_status'),['Approved','Rejected'],true)) throw new RuntimeException('Invalid booking status.');
      $pdo->prepare('UPDATE washing_bookings SET booking_status=? WHERE booking_id=?')->execute([val('booking_status'),(int)val('booking_id')]);
      flash('success','Washing-machine booking updated.'); go('facilities');

    case 'mark_attendance':
      need(['Staff','Admin']);
      $pdo->prepare('INSERT INTO attendance(student_id,attendance_date,attendance_status,marked_by,remarks) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE attendance_status=VALUES(attendance_status),marked_by=VALUES(marked_by),remarks=VALUES(remarks)')->execute([val('student_id'),val('attendance_date'),val('attendance_status'),$u['staff_id'] ?: null,val('remarks') ?: null]);
      flash('success','Attendance saved.'); go('attendance');

    case 'assign_maintenance':
      need(['Staff','Admin']);
      $pdo->beginTransaction();
      $pdo->prepare("INSERT INTO maintenance(complaint_id,staff_id,start_date,maintenance_status,work_notes) VALUES(?,?,NOW(),'Assigned',?)")->execute([(int)val('complaint_id'),val('staff_id') ?: ($u['staff_id'] ?: null),val('work_notes') ?: null]);
      $pdo->prepare("UPDATE complaints SET complaint_status='In Progress' WHERE complaint_id=?")->execute([(int)val('complaint_id')]);
      $pdo->commit(); flash('success','Maintenance work assigned.'); go('maintenance');

    case 'update_maintenance':
      need(['Staff','Admin']);
      $status=val('maintenance_status');
      if ($role==='Staff') { $pdo->prepare('UPDATE maintenance SET maintenance_status=?,completion_date=IF(?=\'Completed\',NOW(),completion_date),cost=?,work_notes=? WHERE maintenance_id=? AND staff_id=?')->execute([$status,$status,(float)val('cost','0'),val('work_notes') ?: null,(int)val('maintenance_id'),$u['staff_id']]); } else { $pdo->prepare('UPDATE maintenance SET maintenance_status=?,completion_date=IF(?=\'Completed\',NOW(),completion_date),cost=?,work_notes=? WHERE maintenance_id=?')->execute([$status,$status,(float)val('cost','0'),val('work_notes') ?: null,(int)val('maintenance_id')]); }
      if ($status==='Completed') $pdo->prepare("UPDATE complaints c JOIN maintenance m ON m.complaint_id=c.complaint_id SET c.complaint_status='Resolved' WHERE m.maintenance_id=?")->execute([(int)val('maintenance_id')]);
      flash('success','Maintenance status saved.'); go('maintenance');

    case 'update_profile_password':
      $old=val('old_password'); $new=val('new_password');
      $q=$pdo->prepare('SELECT password_hash FROM user_accounts WHERE account_id=?'); $q->execute([$u['account_id']]); $hash=$q->fetchColumn();
      if (!$hash || !password_verify($old,$hash) || strlen($new)<8) throw new RuntimeException('Check your current password. The new password must contain at least 8 characters.');
      $pdo->prepare('UPDATE user_accounts SET password_hash=? WHERE account_id=?')->execute([password_hash($new,PASSWORD_DEFAULT),$u['account_id']]);
      flash('success','Password changed.'); go('settings');

    default: throw new RuntimeException('That action is not available.');
  }
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  flash('error', $e instanceof PDOException ? 'Could not save this record. Check for duplicate IDs, missing related records or invalid values.' : $e->getMessage());
  go($_POST['return_page'] ?? 'dashboard');
}
