<?php
class PatientController
{
    private PatientModel $patients;
    private AppointmentModel $appts;
    private HealthRecordModel $records;
    private NotificationModel $notifs;

    public function __construct()
    {
        AuthMiddleware::requireRole('patient', 'admin');
        $this->patients = new PatientModel();
        $this->appts    = new AppointmentModel();
        $this->records  = new HealthRecordModel();
        $this->notifs   = new NotificationModel();
    }

    private function myProfile(): ?array
    {
        return $this->patients->findByUser((int)current_user()['id']);
    }

    public function dashboard(): void
    {
        $p = $this->myProfile();
        $upcoming = $p ? $this->appts->upcomingForPatient((int)$p['id'], 5) : [];
        $recent   = $p ? $this->records->forPatient((int)$p['id']) : [];
        view('patient.dashboard', [
            'title'    => 'My Dashboard',
            'active'   => 'dashboard',
            'p'        => $p,
            'upcoming' => $upcoming,
            'records'  => array_slice($recent, 0, 5),
            'notifs'   => $this->notifs->forUser((int)current_user()['id'], 'patient', 5),
        ]);
    }

    public function appointments(): void
    {
        $p = $this->myProfile();
        view('patient.appointments', [
            'title'  => 'My Appointments',
            'active' => 'appointments',
            'p'      => $p,
            'appts'  => $p ? $this->appts->all(['patient_id'=>(int)$p['id']]) : [],
        ]);
    }

    public function book(): void
    {
        $p = $this->myProfile();
        if (!$p) {
            flash('err','Your patient profile is not set up. Please contact the RHU staff.','danger');
            redirect('index.php?url=patient/dashboard');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $d = [
                'patient_id'       => (int)$p['id'],
                'appointment_date' => $_POST['appointment_date'] ?? null,
                'appointment_time' => $_POST['appointment_time'] ?? null,
                'purpose'          => clean($_POST['purpose'] ?? ''),
                'notes'            => clean($_POST['notes'] ?? ''),
                'status'           => 'pending',
                'created_by'       => (int)current_user()['id'],
            ];
            if (!$d['appointment_date'] || !$d['appointment_time'] || !$d['purpose']) {
                flash('err','Please complete all required fields.','danger');
                redirect('index.php?url=patient/book');
            }

            // Cannot book in the past
            if (strtotime($d['appointment_date']) < strtotime(date('Y-m-d'))) {
                flash('err','You cannot book an appointment in the past.','danger');
                redirect('index.php?url=patient/book');
            }

            // Strict conflict check for patients — no override allowed
            $conflicts = $this->appts->findConflicts(
                (int)$p['id'],
                (string)$d['appointment_date'],
                (string)$d['appointment_time'],
                null,
                15
            );
            if ($conflicts) {
                $first = $conflicts[0];
                if ($first['type'] === 'same_patient_same_day') {
                    flash('err','You already have an appointment on that day. Please pick another date or contact the RHU staff.','danger');
                } else {
                    flash('err','That time slot is unavailable. Please pick a different time (slots are ~15 minutes apart).','danger');
                }
                redirect('index.php?url=patient/book');
            }

            $id = $this->appts->create($d);
            AuditLogger::log('insert','appointments','Patient booked #' . $id);
            Notifier::toRole('nurse','appt_new','New Appointment Request',
                $p['first_name'] . ' ' . $p['last_name'] . ' requested an appointment on ' . fmt_date($d['appointment_date']),
                'index.php?url=nurse/appointments');
            flash('ok','Appointment request submitted. Please wait for approval.');
            redirect('index.php?url=patient/appointments');
        }
        view('patient.book', [
            'title'  => 'Book Appointment',
            'active' => 'book',
            'p'      => $p,
        ]);
    }

    public function profile(): void
    {
        $p = $this->myProfile();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $p) {
            $d = [
                'contact_no'     => clean($_POST['contact_no'] ?? ''),
                'email'          => clean($_POST['email'] ?? ''),
                'address'        => clean($_POST['address'] ?? ''),
                'barangay'       => clean($_POST['barangay'] ?? ''),
                'blood_type'     => clean($_POST['blood_type'] ?? ''),
                'allergies'      => clean($_POST['allergies'] ?? ''),
                'emergency_name' => clean($_POST['emergency_name'] ?? ''),
                'emergency_no'   => clean($_POST['emergency_no'] ?? ''),
            ];
            $this->patients->update((int)$p['id'], $d);
            AuditLogger::log('update','patients','Patient updated own profile.');
            flash('ok','Profile updated.');
            redirect('index.php?url=patient/profile');
        }
        view('patient.profile', [
            'title'  => 'My Profile',
            'active' => 'profile',
            'p'      => $p,
        ]);
    }

    public function records(): void
    {
        $p = $this->myProfile();
        view('patient.records', [
            'title'   => 'My Medical Records',
            'active'  => 'records',
            'records' => $p ? $this->records->forPatient((int)$p['id']) : [],
        ]);
    }

    public function notifications(): void
    {
        $uid = (int)current_user()['id'];
        $this->notifs->markAllRead($uid, 'patient');
        view('patient.notifications', [
            'title'  => 'Notifications',
            'active' => 'notifications',
            'notifs' => $this->notifs->forUser($uid, 'patient', 100),
        ]);
    }
}
