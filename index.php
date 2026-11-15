<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
try { $pdo = db(); } catch (Throwable $e) { http_response_code(503); ?><!doctype html><html><head><meta charset="utf-8"><link rel="stylesheet" href="assets/style.css"><title>Database setup required</title></head><body class="login-page"><section class="panel" style="max-width:620px;margin:7vh auto;padding:32px"><p class="eyebrow">SETUP REQUIRED</p><h1 style="font:600 32px 'Playfair Display',Georgia,serif">The database is not connected yet.</h1><p class="muted">Start Apache and MySQL in XAMPP, import <strong>sql/schema.sql</strong> and then <strong>sql/seed.sql</strong> using phpMyAdmin, and check the credentials in <strong>config.php</strong>.</p><p><a class="button primary" href="login.php">Return to sign in</a></p></section></body></html><?php exit; } $u=current_user(); $role=$u['account_role'];
$page = $_GET['page'] ?? 'dashboard';
$allowed = [
 'Admin'=>['dashboard','students','staff','hostels','rooms','allocations','fees','attendance','complaints','maintenance','facilities','settings'],
 'Staff'=>['dashboard','attendance','complaints','maintenance','facilities','fees','settings'],
 'Student'=>['dashboard','my-requests','fees','settings']
];
if (!in_array($page,$allowed[$role] ?? [],true)) { http_response_code(403); exit('This page is not available for your account.'); }
$flash=take_flash();
$studentId=$u['student_id'] ?? null; $staffId=$u['staff_id'] ?? null;
$displayName=$u['student_name'] ?? $u['staff_name'] ?? ($role==='Admin'?'Administrator':'Portal User');
$nav = [
 'Admin'=>[['dashboard','Overview','⌂'],['students','Students','♙'],['staff','Staff','♧'],['hostels','Hostels','⌂'],['rooms','Rooms','▦'],['allocations','Room allocation','⇄'],['fees','Fee records','₹'],['attendance','Attendance','✓'],['complaints','Complaints','!'],['maintenance','Maintenance','⚒'],['facilities','Facilities','◷']],
 'Staff'=>[['dashboard','Overview','⌂'],['attendance','Attendance','✓'],['complaints','Complaints','!'],['maintenance','Maintenance','⚒'],['facilities','Facilities','◷'],['fees','Fee records','₹']],
 'Student'=>[['dashboard','Overview','⌂'],['my-requests','My services','▤']]
];
function active_nav(string $page,string $item): string { return $page===$item?'active':''; }
function pill(string $value): string {
 $v=strtolower(str_replace(' ','-',$value)); $kind='neutral';
 if (in_array($v,['paid','present','approved','completed','resolved','checked-out'],true)) $kind='good';
 elseif (in_array($v,['pending','open','late','assigned','in-progress','scheduled'],true)) $kind='warn';
 elseif (in_array($v,['rejected','absent','emergency','high','overdue','out-of-service'],true)) $kind='bad';
 return '<span class="pill '.$kind.'">'.h($value).'</span>';
}
function table_view(array $headers,array $data,callable $renderRow=null): void {
 if (!$data) { echo '<div class="empty-state">Nothing to show here yet.</div>'; return; }
 echo '<div class="table-wrap"><table><thead><tr>'; foreach($headers as $header) echo '<th>'.h($header).'</th>'; echo '</tr></thead><tbody>';
 foreach($data as $row) { echo '<tr>'; foreach($row as $cell) echo '<td>'.$cell.'</td>'; echo '</tr>'; } echo '</tbody></table></div>';
}
function field(string $label,string $name,string $type='text',string $placeholder='',bool $required=true,string $extra=''): string {
 return '<label>'.h($label).'<input type="'.h($type).'" name="'.h($name).'" placeholder="'.h($placeholder).'" '.($required?'required':'').' '.$extra.'></label>';
}
function select_field(string $label,string $name,array $options,bool $required=true): string {
 $s='<label>'.h($label).'<select name="'.h($name).'" '.($required?'required':'').'><option value="">Choose</option>';
 foreach($options as $value=>$text) $s.='<option value="'.h((string)$value).'">'.h((string)$text).'</option>';
 return $s.'</select></label>';
}
function post_form(string $title,string $action,string $fields,string $button='Save record',string $return='dashboard',string $id=''): void {
 echo '<section'.($id!==''?' id="'.h($id).'"':'').' class="panel form-panel"><div class="panel-heading"><div><p class="eyebrow">RECORD ENTRY</p><h3>'.h($title).'</h3></div></div><form method="post" action="actions.php" class="form-grid">'.csrf_input().'<input type="hidden" name="action" value="'.h($action).'"><input type="hidden" name="return_page" value="'.h($return).'">'.$fields.'<div class="form-actions"><button class="button primary" type="submit">'.h($button).' <span>→</span></button></div></form></section>';
}
function page_head(string $eyebrow,string $title,string $desc=''): void { echo '<div class="page-heading"><div><p class="eyebrow">'.h($eyebrow).'</p><h1>'.h($title).'</h1>'.($desc?'<p class="muted">'.h($desc).'</p>':'').'</div></div>'; }
function metric(string $label,string|int $value,string $sub=''): void { echo '<div class="metric"><span>'.h($label).'</span><strong>'.h($value).'</strong><small>'.h($sub).'</small></div>'; }
function all_option_map(PDO $pdo,string $sql,string $key,string $label,array $params=[]): array { $out=[]; foreach(rows($pdo,$sql,$params) as $r) $out[$r[$key]]=$r[$label]; return $out; }
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($page)?> | Hostel Portal</title><link rel="stylesheet" href="assets/style.css"></head><body class="app-page">
<aside class="sidebar"><a class="brand" href="index.php"><span class="brand-mark">H</span><span>HOSTEL PORTAL</span></a><div class="side-label">WORKSPACE</div><nav><?php foreach($nav[$role] as [$key,$label,$icon]): ?><a class="nav-link <?=active_nav($page,$key)?>" href="index.php?page=<?=h($key)?>"><span class="nav-icon"><?=h($icon)?></span><?=h($label)?></a><?php endforeach; ?></nav><div class="sidebar-bottom"><div class="user-mini"><span class="avatar"><?=h(strtoupper(substr($displayName,0,1)))?></span><span><strong><?=h($displayName)?></strong><small><?=h($role)?> account</small></span></div><a class="logout-link" href="logout.php">Sign out <span>↗</span></a></div></aside>
<main class="main-area"><header class="topbar"><div class="crumb">HOSTEL OPERATIONS <span>/</span> <?=h(strtoupper(str_replace('-',' ',$page)))?></div><div class="topbar-right">
  <span class="live-dot"></span><span>Local portal</span>
  <details class="profile-menu">
    <summary class="profile-trigger" aria-label="Open account menu">
      <span class="top-avatar"><?=h(strtoupper(substr($displayName,0,1)))?></span>
      <span class="profile-trigger-name"><?=h($displayName)?></span>
      <span class="profile-caret" aria-hidden="true">▾</span>
    </summary>
    <div class="profile-dropdown">
      <div class="profile-dropdown-head">
        <strong><?=h($displayName)?></strong>
        <small><?=h($role)?> account</small>
      </div>
      <a href="index.php?page=settings">Account settings</a>
      <a class="profile-logout" href="logout.php">Sign out <span>↗</span></a>
    </div>
  </details>
</div></header><div class="content">
<?php if($flash): ?><div class="alert <?=h($flash['kind'])?>"><?=h($flash['message'])?></div><?php endif; ?>
<?php if($page==='dashboard'): ?>
  <?php if($role==='Admin'): ?>
    <?php page_head(strtoupper(date('l, d F Y')),'Good afternoon, Admin.','Here is the current picture of hostel operations.'); ?>
    <div class="metric-grid"><?php metric('Registered students',scalar($pdo,'SELECT COUNT(*) FROM students')); metric('Hostel residents',scalar($pdo,'SELECT COUNT(*) FROM hostellers')); metric('Occupied rooms',scalar($pdo,"SELECT COUNT(*) FROM room_allocations WHERE allocation_status='Active'")); metric('Open complaints',scalar($pdo,"SELECT COUNT(*) FROM complaints WHERE complaint_status IN ('Open','In Progress')")); ?></div>
    <div class="dashboard-grid"><section class="panel"><div class="panel-heading"><div><p class="eyebrow">NEEDS ATTENTION</p><h3>Recent complaints</h3></div><a class="text-link" href="index.php?page=complaints">View all →</a></div><?php $d=rows($pdo,"SELECT c.complaint_id,c.complaint_type,c.priority,c.complaint_status,s.full_name FROM complaints c JOIN allocation_members am ON am.allocation_id=c.allocation_id AND am.member_no=c.member_no JOIN students s ON s.student_id=am.student_id ORDER BY c.complaint_date DESC LIMIT 5"); table_view(['Ref.','Issue','Priority','Status','Reported by'],array_map(fn($r)=>[h('#'.$r['complaint_id']),h($r['complaint_type']),pill($r['priority']),pill($r['complaint_status']),h($r['full_name'])],$d)); ?></section><section class="panel side-panel"><p class="eyebrow">QUICK ACTIONS</p><h3>Manage the essentials</h3><a class="quick-action" href="index.php?page=students"><span>＋</span><div><strong>Add a student</strong><small>Create a resident profile and login</small></div></a><a class="quick-action" href="index.php?page=allocations"><span>⇄</span><div><strong>Allocate a room</strong><small>Assign or change a resident's room</small></div></a><a class="quick-action" href="index.php?page=fees"><span>₹</span><div><strong>Update fee record</strong><small>Create dues and confirm payments</small></div></a></section></div>
  <?php elseif($role==='Staff'): ?>
    <?php page_head('STAFF WORKSPACE','Your shift at a glance.','Review requests, update assigned work and keep resident records current.'); ?>
    <div class="metric-grid"><?php metric('Assigned work',scalar($pdo,'SELECT COUNT(*) FROM maintenance WHERE staff_id=? AND maintenance_status IN (\'Assigned\',\'In Progress\')',[$staffId])); metric('Open complaints',scalar($pdo,"SELECT COUNT(*) FROM complaints WHERE complaint_status IN ('Open','In Progress')")); metric('Washing requests',scalar($pdo,"SELECT COUNT(*) FROM washing_bookings WHERE booking_status='Pending'")); ?></div>
    <div class="dashboard-grid"><section class="panel"><div class="panel-heading"><div><p class="eyebrow">YOUR ASSIGNMENTS</p><h3>Maintenance in progress</h3></div><a class="text-link" href="index.php?page=maintenance">Open work queue →</a></div><?php $d=rows($pdo,'SELECT m.maintenance_id,c.complaint_type,m.maintenance_status,m.start_date FROM maintenance m JOIN complaints c ON c.complaint_id=m.complaint_id WHERE m.staff_id=? ORDER BY m.maintenance_id DESC LIMIT 6',[$staffId]); table_view(['Work ID','Issue','Status','Started'],array_map(fn($r)=>[h('#'.$r['maintenance_id']),h($r['complaint_type']),pill($r['maintenance_status']),h($r['start_date']??'Not started')],$d)); ?></section><section class="panel side-panel"><p class="eyebrow">QUICK ACTIONS</p><h3>Daily records</h3><a class="quick-action" href="index.php?page=attendance"><span>✓</span><div><strong>Mark attendance</strong><small>Record the evening roll call</small></div></a><a class="quick-action" href="index.php?page=facilities"><span>◷</span><div><strong>Laundry facilities</strong><small>Washing-machine bookings</small></div></a></section></div>
  <?php else: $m=current_member($pdo,$studentId); ?>
    <?php page_head('RESIDENT PORTAL','Welcome, '.($displayName?:'Resident').'.','Your room, requests and everyday hostel services in one place.'); ?>
    <div class="resident-hero"><div><p class="eyebrow light">YOUR CURRENT ALLOCATION</p><h2><?=h($m ? $m['room_number'].' · '.$m['hostel_name'] : 'No active room assigned')?></h2><p><?=h($m ? 'Your room information is up to date.' : 'Please contact the hostel office to complete your allocation.')?></p></div><div class="hero-stamp"><span>RESIDENT</span><strong><?=h(substr($studentId, -4))?></strong></div></div>
    <div class="metric-grid"><?php metric('Pending fees',scalar($pdo,"SELECT COUNT(*) FROM hostel_fees hf JOIN allocation_members am ON am.allocation_id=hf.allocation_id AND am.member_no=hf.member_no WHERE am.student_id=? AND hf.payment_status IN ('Pending','Overdue')",[$studentId])); metric('My complaints',scalar($pdo,'SELECT COUNT(*) FROM complaints c JOIN allocation_members am ON am.allocation_id=c.allocation_id AND am.member_no=c.member_no WHERE am.student_id=?',[$studentId])); metric('Upcoming bookings',scalar($pdo,"SELECT COUNT(*) FROM washing_bookings WHERE student_id=? AND booking_date>=CURRENT_DATE AND booking_status IN ('Pending','Approved')",[$studentId])); ?></div>
    <div class="panel"><div class="panel-heading"><div><p class="eyebrow">HOSTEL SERVICES</p><h3>What would you like to do?</h3></div><a class="text-link" href="index.php?page=my-requests">Open all services →</a></div><div class="service-grid"><a href="index.php?page=my-requests#service-complaint"><span>✳</span><strong>Report an issue</strong><small>Open the complaint form</small></a><a href="index.php?page=my-requests#service-washing"><span>◷</span><strong>Book a washing machine</strong><small>Open the booking form</small></a><a href="index.php?page=my-requests#service-fees"><span>₹</span><strong>View hostel fees</strong><small>Open your fee statement</small></a></div></div>
  <?php endif; ?>

?>
<?php elseif($page==='students'): ?>
  <?php page_head('RESIDENT DIRECTORY','Students','Create student records and automatically provision portal logins.'); ?>
  <?php post_form('Add a student','add_student',field('Student ID','student_id','text','e.g. 2025UCA2001').field('Full name','full_name').field('Date of birth','dob','date').field('Phone','phone','tel').field('Email','email','email').field('Admission date','admission_date','date').field('Nationality','nationality','text','Indian',false).field('Year of study','year_of_study','number','1-8').field('Branch','branch').field('Department','department').select_field('Student category','student_type',['Hosteller'=>'Hosteller','Day Scholar'=>'Day Scholar']).field('Distance from residence (km)','distance','number','For hostellers',false,'step="0.01"').field('Emergency contact','emergency_contact','tel','For hostellers',false).field('Initial password','initial_password','password','Default: student123',false), 'Create student','students');
  $d=rows($pdo,'SELECT s.student_id,s.full_name,s.email,s.student_type AS category,s.year_of_study FROM students s ORDER BY s.full_name');
  table_view(['Student ID','Name','Email','Category','Year'],array_map(fn($r)=>[h($r['student_id']),h($r['full_name']),h($r['email']),pill($r['category']),h($r['year_of_study'])],$d));

?>
<?php elseif($page==='staff'): ?>
  <?php page_head('PEOPLE & ACCESS','Staff directory','Staff profiles are linked to role-based accounts for hostel operations.'); ?>
  <?php post_form('Add a staff member','add_staff',field('Staff ID','staff_id','text','e.g. STF005').field('Full name','full_name').field('Email','email','email').field('Phone','phone','tel').select_field('Staff role','staff_role',['Warden'=>'Warden','Maintenance'=>'Maintenance','Security'=>'Security','Housekeeping'=>'Housekeeping','Reception'=>'Reception']).field('Date joined','date_joined','date').field('Username','username','text','Defaults to staff ID',false).field('Initial password','initial_password','password','Default: staff123',false),'Create staff account','staff');
  $d=rows($pdo,'SELECT s.staff_id,s.full_name,s.email,s.phone,s.staff_role,IF(s.is_active=1,\'Active\',\'Inactive\') AS state FROM staff s ORDER BY s.full_name');
  table_view(['Staff ID','Name','Email','Phone','Role','Account'],array_map(fn($r)=>[h($r['staff_id']),h($r['full_name']),h($r['email']),h($r['phone']),h($r['staff_role']),pill($r['state'])],$d));

?>
<?php elseif($page==='hostels'): ?>
  <?php page_head('PROPERTY DIRECTORY','Hostels','Maintain hostel buildings and their wardens.'); ?>
  <?php post_form('Register a hostel','add_hostel',field('Hostel ID','hostel_id').field('Hostel name','hostel_name').select_field('Hostel type','hostel_type',['Boys'=>'Boys','Girls'=>'Girls','Co-ed'=>'Co-ed']).select_field('Warden','warden_staff_id',all_option_map($pdo,"SELECT staff_id,full_name FROM staff WHERE staff_role='Warden' AND is_active=1",'staff_id','full_name'),false).field('Address / block','address'),'Add hostel','hostels');
  $d=rows($pdo,'SELECT h.hostel_id,h.hostel_name,h.hostel_type,COALESCE(s.full_name,\'Not assigned\') AS warden,h.address,(SELECT COUNT(*) FROM rooms r WHERE r.hostel_id=h.hostel_id) AS rooms FROM hostels h LEFT JOIN staff s ON s.staff_id=h.warden_staff_id');
  table_view(['Hostel ID','Name','Type','Warden','Address','Rooms'],array_map(fn($r)=>[h($r['hostel_id']),h($r['hostel_name']),h($r['hostel_type']),h($r['warden']),h($r['address']),h($r['rooms'])],$d));

?>
<?php elseif($page==='rooms'): ?>
  <?php page_head('ROOM DIRECTORY','Rooms','Create rooms and maintain occupancy limits.'); ?>
  <?php post_form('Add a room','add_room',field('Room ID','room_id').select_field('Hostel','hostel_id',all_option_map($pdo,'SELECT hostel_id,hostel_name FROM hostels','hostel_id','hostel_name')).field('Room number','room_number').field('Floor','floor_no','number','0').field('Capacity','capacity','number','2').select_field('Room status','room_status',['Available'=>'Available','Occupied'=>'Occupied','Maintenance'=>'Maintenance','Unavailable'=>'Unavailable']), 'Save room','rooms');
  $d=rows($pdo,"SELECT r.room_id,h.hostel_name,r.room_number,r.floor_no,r.capacity,r.room_status,(SELECT COUNT(*) FROM room_allocations ra JOIN allocation_members am ON am.allocation_id=ra.allocation_id WHERE ra.room_id=r.room_id AND ra.allocation_status='Active' AND am.leave_date IS NULL) AS residents FROM rooms r JOIN hostels h ON h.hostel_id=r.hostel_id ORDER BY h.hostel_name,r.room_number");
  table_view(['Room ID','Hostel','Room','Floor','Capacity','Residents','Status'],array_map(fn($r)=>[h($r['room_id']),h($r['hostel_name']),h($r['room_number']),h($r['floor_no']),h($r['capacity']),h($r['residents'].' / '.$r['capacity']),pill($r['room_status'])],$d));

?>
<?php elseif($page==='allocations'): ?>
  <?php page_head('RESIDENT PLACEMENT','Room allocations','A room allocation can contain multiple hostellers; each resident is recorded as an allocation member.'); ?>
  <?php $students=all_option_map($pdo,'SELECT h.student_id,s.full_name FROM hostellers h JOIN students s ON s.student_id=h.student_id ORDER BY s.full_name','student_id','full_name'); $rooms=all_option_map($pdo,"SELECT r.room_id,CONCAT(h.hostel_name,' / ',r.room_number,' (',r.room_id,')') AS label FROM rooms r JOIN hostels h ON h.hostel_id=r.hostel_id WHERE r.room_status NOT IN ('Maintenance','Unavailable') ORDER BY h.hostel_name,r.room_number",'room_id','label'); ?>
  <?php post_form('Allocate a hosteller','allocate_student',select_field('Student','student_id',$students).select_field('Room','room_id',$rooms), 'Allocate room','allocations'); ?>
  <?php post_form('Change a hosteller\'s room','change_room',select_field('Student','student_id',$students).select_field('New room','room_id',$rooms), 'Confirm room change','allocations'); ?>
  <?php $d=rows($pdo,"SELECT ra.allocation_id,h.hostel_name,r.room_number,ra.start_date,ra.end_date,ra.allocation_status,GROUP_CONCAT(s.full_name ORDER BY am.member_no SEPARATOR ', ') AS residents FROM room_allocations ra JOIN rooms r ON r.room_id=ra.room_id JOIN hostels h ON h.hostel_id=r.hostel_id LEFT JOIN allocation_members am ON am.allocation_id=ra.allocation_id AND am.leave_date IS NULL LEFT JOIN students s ON s.student_id=am.student_id GROUP BY ra.allocation_id ORDER BY ra.allocation_id DESC");
  table_view(['Allocation','Hostel','Room','Start','End','Residents','Status'],array_map(fn($r)=>[h('#'.$r['allocation_id']),h($r['hostel_name']),h($r['room_number']),h($r['start_date']),h($r['end_date']??'—'),h($r['residents']??'No current residents'),pill($r['allocation_status'])],$d)); ?>

?>
<?php elseif($page==='fees'): ?>
  <?php page_head('ACCOUNTS','Hostel fees','Create dues against an allocation member and record payments.'); ?>
  <?php if($role==='Admin'): $members=[]; foreach(rows($pdo,"SELECT am.allocation_id,am.member_no,CONCAT(s.full_name,' · ',h.hostel_name,' / ',r.room_number) AS label FROM allocation_members am JOIN students s ON s.student_id=am.student_id JOIN room_allocations ra ON ra.allocation_id=am.allocation_id JOIN rooms r ON r.room_id=ra.room_id JOIN hostels h ON h.hostel_id=r.hostel_id WHERE am.leave_date IS NULL AND ra.allocation_status='Active'") as $mr){$members[$mr['allocation_id'].':'.$mr['member_no']]=$mr['label'];} $memberFields=select_field('Resident / active allocation','allocation_member',$members); echo '<section class="panel form-panel"><div class="panel-heading"><div><p class="eyebrow">RECORD ENTRY</p><h3>Create a fee record</h3></div></div><form method="post" action="actions.php" class="form-grid">'.csrf_input().'<input type="hidden" name="action" value="add_fee"><input type="hidden" name="return_page" value="fees">'.$memberFields.field('Fee type','fee_type','text','Hostel Fee').field('Academic period','academic_period','text','2026-27 / Semester 1').field('Amount (INR)','amount','number','32000','true','step="0.01"').field('Due date','due_date','date').'<div class="form-actions"><button class="button primary">Create fee</button></div></form></section>'; endif; ?>
  <?php $sql="SELECT hf.fee_id,s.full_name,hf.fee_type,hf.academic_period,hf.amount,hf.due_date,hf.payment_status,hf.payment_date FROM hostel_fees hf JOIN allocation_members am ON am.allocation_id=hf.allocation_id AND am.member_no=hf.member_no JOIN students s ON s.student_id=am.student_id"; $params=[]; if($role==='Student'){ $sql.=' WHERE am.student_id=?'; $params=[$studentId]; } $sql.=' ORDER BY hf.due_date DESC'; $d=rows($pdo,$sql,$params); $rr=[]; foreach($d as $r){$cell=[h('#'.$r['fee_id']),h($r['full_name']),h($r['fee_type'].' · '.$r['academic_period']),h('₹'.number_format((float)$r['amount'],2)),h($r['due_date']),pill($r['payment_status']),h($r['payment_date']??'—')]; if($role!=='Student' && $r['payment_status']!=='Paid') $cell[]='<form method="post" action="actions.php" class="inline-form">'.csrf_input().'<input type="hidden" name="action" value="mark_fee_paid"><input type="hidden" name="fee_id" value="'.h($r['fee_id']).'"><input type="hidden" name="payment_method" value="Cash"><button class="text-button">Mark paid</button></form>'; $rr[]=$cell; } table_view($role==='Student'?['Fee','Resident','Description','Amount','Due date','Status','Paid on']:['Fee','Resident','Description','Amount','Due date','Status','Paid on','Action'],$rr); ?>

?>
<?php elseif($page==='attendance'): ?>
  <?php page_head('RESIDENT RECORDS','Attendance','Staff can record daily roll call; residents can view their history from My Services.'); ?>
  <?php if($role!=='Student'): ?><?php post_form('Mark attendance','mark_attendance',select_field('Hosteller','student_id',all_option_map($pdo,'SELECT h.student_id,s.full_name FROM hostellers h JOIN students s ON s.student_id=h.student_id ORDER BY s.full_name','student_id','full_name')).field('Date','attendance_date','date',date('Y-m-d')).select_field('Status','attendance_status',['Present'=>'Present','Absent'=>'Absent','Late'=>'Late','Leave'=>'Leave']).field('Remarks','remarks','text','Optional',false),'Save attendance','attendance'); ?><?php endif; ?>
  <?php $sql='SELECT a.attendance_date,s.student_id,s.full_name,a.attendance_status,COALESCE(st.full_name,\'—\') AS marked_by FROM attendance a JOIN students s ON s.student_id=a.student_id LEFT JOIN staff st ON st.staff_id=a.marked_by'; $params=[]; if($role==='Staff') $sql.=' WHERE a.attendance_date>=DATE_SUB(CURRENT_DATE,INTERVAL 30 DAY)'; if($role==='Student'){ $sql.=' WHERE a.student_id=?'; $params=[$studentId]; } $sql.=' ORDER BY a.attendance_date DESC,s.full_name LIMIT 100'; $d=rows($pdo,$sql,$params); table_view(['Date','Student ID','Name','Status','Marked by'],array_map(fn($r)=>[h($r['attendance_date']),h($r['student_id']),h($r['full_name']),pill($r['attendance_status']),h($r['marked_by'])],$d)); ?>

?>
<?php elseif($page==='complaints'): ?>
  <?php page_head('RESIDENT SUPPORT','Complaints','Review resident issues and move them into maintenance work.'); ?>
  <?php $d=rows($pdo,"SELECT c.complaint_id,c.complaint_type,c.priority,c.description,c.complaint_date,c.complaint_status,s.full_name,r.room_number,h.hostel_name FROM complaints c JOIN allocation_members am ON am.allocation_id=c.allocation_id AND am.member_no=c.member_no JOIN students s ON s.student_id=am.student_id JOIN room_allocations ra ON ra.allocation_id=c.allocation_id JOIN rooms r ON r.room_id=ra.room_id JOIN hostels h ON h.hostel_id=r.hostel_id ORDER BY FIELD(c.priority,'Emergency','High','Medium','Low'),c.complaint_date DESC");
  table_view(['Ref.','Resident','Location','Issue','Priority','Description','Status'],array_map(fn($r)=>[h('#'.$r['complaint_id']),h($r['full_name']),h($r['hostel_name'].' / '.$r['room_number']),h($r['complaint_type']),pill($r['priority']),h($r['description']),pill($r['complaint_status'])],$d));
  if($role==='Staff'||$role==='Admin') { $open=all_option_map($pdo,"SELECT c.complaint_id,CONCAT('#',c.complaint_id,' · ',c.complaint_type) AS label FROM complaints c WHERE c.complaint_status IN ('Open','In Progress')",'complaint_id','label'); post_form('Assign maintenance work','assign_maintenance',select_field('Complaint','complaint_id',$open).select_field('Assign to staff','staff_id',all_option_map($pdo,"SELECT staff_id,CONCAT(full_name,' · ',staff_role) AS label FROM staff WHERE staff_role='Maintenance' AND is_active=1",'staff_id','label'),$role==='Admin').field('Work notes','work_notes','text','Initial inspection notes',false),'Create work order','complaints'); }

?>
<?php elseif($page==='maintenance'): ?>
  <?php page_head('WORK ORDERS','Maintenance','Track the staff member, progress, cost and completion of repair work.'); ?>
  <?php $sql='SELECT m.maintenance_id,c.complaint_id,c.complaint_type,s.full_name AS resident,COALESCE(st.full_name,\'Unassigned\') AS staff,m.maintenance_status,m.start_date,m.completion_date,m.cost FROM maintenance m JOIN complaints c ON c.complaint_id=m.complaint_id JOIN allocation_members am ON am.allocation_id=c.allocation_id AND am.member_no=c.member_no JOIN students s ON s.student_id=am.student_id LEFT JOIN staff st ON st.staff_id=m.staff_id'; $params=[]; if($role==='Staff') {$sql.=' WHERE m.staff_id=?';$params=[$staffId];} $sql.=' ORDER BY m.maintenance_id DESC'; $d=rows($pdo,$sql,$params); $rr=[]; foreach($d as $r){$rr[]=[h('#'.$r['maintenance_id']),h('#'.$r['complaint_id']),h($r['complaint_type']),h($r['resident']),h($r['staff']),pill($r['maintenance_status']),h($r['start_date']??'—'),h($r['completion_date']??'—'),h('₹'.number_format((float)$r['cost'],2))];} table_view(['Work ID','Complaint','Issue','Resident','Assigned staff','Status','Started','Completed','Cost'],$rr); ?>
  <?php $works=rows($pdo,'SELECT maintenance_id,maintenance_status,cost,work_notes FROM maintenance'.($role==='Staff'?' WHERE staff_id=?':'').' ORDER BY maintenance_id DESC', $role==='Staff'?[$staffId]:[]); if($works): ?><section class="panel form-panel"><div class="panel-heading"><div><p class="eyebrow">UPDATE WORK</p><h3>Update a maintenance job</h3></div></div><form method="post" action="actions.php" class="form-grid"><?=csrf_input()?><input type="hidden" name="action" value="update_maintenance"><input type="hidden" name="return_page" value="maintenance"><?=select_field('Work order','maintenance_id',array_column(array_map(fn($x)=>['maintenance_id'=>$x['maintenance_id'],'label'=>'#'.$x['maintenance_id'].' · '.$x['maintenance_status']],$works),'label','maintenance_id'))?><?=select_field('Status','maintenance_status',['Assigned'=>'Assigned','In Progress'=>'In Progress','Completed'=>'Completed','On Hold'=>'On Hold'])?><?=field('Cost (INR)','cost','number','0',false,'step="0.01"')?><?=field('Work notes','work_notes','text','Update notes',false)?><div class="form-actions"><button class="button primary">Save work update</button></div></form></section><?php endif; ?>

?>
<?php elseif($page==='facilities'): ?>
  <?php page_head('SHARED FACILITIES','Facility operations','Washing-machine slots and booking approvals.'); ?>
  <?php if($role==='Admin'): post_form('Add a washing machine','add_machine',field('Machine ID','machine_id').select_field('Hostel','hostel_id',all_option_map($pdo,'SELECT hostel_id,hostel_name FROM hostels','hostel_id','hostel_name')).field('Machine number','machine_number').field('Location','location').select_field('Status','machine_status',['Available'=>'Available','Out of Service'=>'Out of Service']),'Add machine','facilities'); endif; ?>
  <section class="panel"><div class="panel-heading"><div><p class="eyebrow">LAUNDRY</p><h3>Washing-machine requests</h3></div></div><?php $d=rows($pdo,'SELECT wb.booking_id,s.full_name,wm.machine_number,h.hostel_name,wb.booking_date,wb.start_time,wb.end_time,wb.booking_status FROM washing_bookings wb JOIN students s ON s.student_id=wb.student_id JOIN washing_machines wm ON wm.machine_id=wb.machine_id JOIN hostels h ON h.hostel_id=wm.hostel_id ORDER BY wb.booking_date DESC,wb.start_time DESC LIMIT 60'); $rr=[]; foreach($d as $r){$cell=[h('#'.$r['booking_id']),h($r['full_name']),h($r['hostel_name'].' / '.$r['machine_number']),h($r['booking_date']),h(substr($r['start_time'],0,5).'–'.substr($r['end_time'],0,5)),pill($r['booking_status'])]; if($role!=='Student' && $r['booking_status']==='Pending') $cell[]='<form method="post" action="actions.php" class="inline-form">'.csrf_input().'<input type="hidden" name="action" value="review_booking"><input type="hidden" name="booking_id" value="'.h($r['booking_id']).'"><input type="hidden" name="booking_status" value="Approved"><button class="text-button">Approve</button></form> <form method="post" action="actions.php" class="inline-form">'.csrf_input().'<input type="hidden" name="action" value="review_booking"><input type="hidden" name="booking_id" value="'.h($r['booking_id']).'"><input type="hidden" name="booking_status" value="Rejected"><button class="text-button">Reject</button></form>'; $rr[]=$cell;} table_view($role==='Student'?['Ref.','Resident','Machine','Date','Time','Status']:['Ref.','Resident','Machine','Date','Time','Status','Action'],$rr); ?></section>

?>
<?php elseif($page==='my-requests'): ?>
  <?php page_head('STUDENT SERVICES','My services','Choose a service below. Each shortcut takes you directly to its form or account details.'); ?>
  <?php $m=current_member($pdo,$studentId);  ?>
  <section class="panel service-hub">
    <div class="panel-heading"><div><p class="eyebrow">QUICK NAVIGATION</p><h3>Choose a service</h3></div></div>
    <div class="service-shortcuts">
      <a href="index.php?page=my-requests#service-complaint"><span>✳</span><strong>Report an issue</strong><small>Repairs and maintenance</small></a>
      <a href="index.php?page=my-requests#service-washing"><span>◷</span><strong>Washing machine</strong><small>Book a laundry time slot</small></a>
      
      <a href="index.php?page=my-requests#service-fees"><span>₹</span><strong>Hostel fees</strong><small>View dues and payment status</small></a>
    </div>
  </section>

  <?php if($m): ?>
    <?php post_form('Report a hostel issue','create_complaint',select_field('Issue type','complaint_type',['Electrical'=>'Electrical','Plumbing'=>'Plumbing','Furniture'=>'Furniture','Cleanliness'=>'Cleanliness','Internet'=>'Internet','Other'=>'Other']).select_field('Priority','priority',['Low'=>'Low','Medium'=>'Medium','High'=>'High','Emergency'=>'Emergency']).'<label class="wide">Describe the issue<textarea name="description" rows="3" required placeholder="Tell us what needs attention..."></textarea></label>','Submit complaint','my-requests','service-complaint'); ?>
    <?php $machines=all_option_map($pdo,'SELECT machine_id,CONCAT(machine_number,\' · \' ,location) AS label FROM washing_machines WHERE hostel_id=? AND machine_status=\'Available\'', 'machine_id','label',[$m['hostel_id']]); ?>
    <?php post_form('Book a washing machine','book_washing',select_field('Machine','machine_id',$machines).field('Date','booking_date','date','',true,'min="'.date('Y-m-d').'"').field('Start time','start_time','time').field('End time','end_time','time'),'Request a slot','my-requests','service-washing'); ?>
  <?php else: ?>
    <section class="panel service-unavailable" id="service-complaint"><p class="eyebrow">ROOM ALLOCATION REQUIRED</p><h3>Report an issue</h3><p class="muted">An active room allocation is needed before you can submit a room-related complaint. Please contact the hostel office to complete or reactivate your allocation.</p></section>
    <section class="panel service-unavailable" id="service-washing"><p class="eyebrow">ROOM ALLOCATION REQUIRED</p><h3>Book a washing machine</h3><p class="muted">Laundry bookings are available after an active hostel room has been assigned to your account.</p></section>
  <?php endif; ?>

  <section class="panel" id="service-fees"><div class="panel-heading"><div><p class="eyebrow">ACCOUNT SUMMARY</p><h3>My hostel fees</h3></div><a class="text-link" href="index.php?page=fees">Open full fee statement →</a></div><?php $d=rows($pdo,'SELECT hf.fee_type,hf.academic_period,hf.amount,hf.due_date,hf.payment_status,hf.payment_date FROM hostel_fees hf JOIN allocation_members am ON am.allocation_id=hf.allocation_id AND am.member_no=hf.member_no WHERE am.student_id=? ORDER BY hf.due_date DESC',[$studentId]); table_view(['Fee type','Period','Amount','Due date','Status','Paid on'],array_map(fn($r)=>[h($r['fee_type']),h($r['academic_period']),h('₹'.number_format((float)$r['amount'],2)),h($r['due_date']),pill($r['payment_status']),h($r['payment_date']??'—')],$d)); ?></section>

  <section class="panel" id="my-complaints"><div class="panel-heading"><div><p class="eyebrow">ISSUE TRACKER</p><h3>My complaints</h3></div></div><?php $d=rows($pdo,'SELECT c.complaint_id,c.complaint_type,c.priority,c.description,c.complaint_date,c.complaint_status FROM complaints c JOIN allocation_members am ON am.allocation_id=c.allocation_id AND am.member_no=c.member_no WHERE am.student_id=? ORDER BY c.complaint_date DESC',[$studentId]); table_view(['Ref.','Type','Priority','Description','Reported','Status'],array_map(fn($r)=>[h('#'.$r['complaint_id']),h($r['complaint_type']),pill($r['priority']),h($r['description']),h($r['complaint_date']),pill($r['complaint_status'])],$d)); ?></section>
  <section class="panel" id="my-washing-bookings"><div class="panel-heading"><div><p class="eyebrow">LAUNDRY HISTORY</p><h3>My washing-machine bookings</h3></div></div><?php $d=rows($pdo,'SELECT wb.booking_date,wb.start_time,wb.end_time,wm.machine_number,wb.booking_status FROM washing_bookings wb JOIN washing_machines wm ON wm.machine_id=wb.machine_id WHERE wb.student_id=? ORDER BY wb.booking_date DESC',[$studentId]); table_view(['Date','Machine','Time','Status'],array_map(fn($r)=>[h($r['booking_date']),h($r['machine_number']),h(substr($r['start_time'],0,5).'–'.substr($r['end_time'],0,5)),pill($r['booking_status'])],$d)); ?></section>
  <section class="panel" id="my-attendance"><div class="panel-heading"><div><p class="eyebrow">ROLL CALL</p><h3>My attendance</h3></div></div><?php $d=rows($pdo,'SELECT attendance_date,attendance_status,remarks FROM attendance WHERE student_id=? ORDER BY attendance_date DESC LIMIT 30',[$studentId]); table_view(['Date','Status','Remarks'],array_map(fn($r)=>[h($r['attendance_date']),pill($r['attendance_status']),h($r['remarks']??'—')],$d)); ?></section>

<?php elseif($page==='settings'): ?>
  <?php page_head('ACCOUNT SETTINGS','Security','Change your portal password.'); ?>
  <?php post_form('Change password','update_profile_password',field('Current password','old_password','password').field('New password','new_password','password','At least 8 characters'), 'Update password','settings'); ?>
<?php endif; ?>
<div class="page-footer">HOSTEL PORTAL <span>·</span> ACADEMIC PROJECT BUILD <span>·</span> <?=date('Y')?></div>
</div></main></body></html>
