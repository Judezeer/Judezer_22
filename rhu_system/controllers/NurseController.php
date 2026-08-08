<?php
class NurseController
{
    private PatientModel $patients;
    private AppointmentModel $appts;
    private HealthRecordModel $records;

    public function __construct(bool $skipGuard = false)
    {
        if (!$skipGuard) AuthMiddleware::requireRole('nurse', 'admin');
        $this->patients = new PatientModel();
        $this->appts    = new AppointmentModel();
        $this->records  = new HealthRecordModel();
    }

    // -------------------- Dashboard --------------------
    public function dashboard(): void
    {
        $stats = [
            'patients'    => $this->patients->count(),
            'today_appts' => $this->appts->countToday(),
            'pending'     => $this->appts->countByStatus()['pending'] ?? 0,
            'completed'   => $this->appts->countByStatus()['completed'] ?? 0,
        ];
        view('nurse.dashboard', [
            'title'         => 'Nurse Dashboard',
            'active'        => 'dashboard',
            'stats'         => $stats,
            'patients_chart'=> $this->patients->monthlySeries(),
            'appts_chart'   => $this->appts->monthlySeries(),
            'today_list'    => $this->appts->all(['date'=>date('Y-m-d')]),
        ]);
    }

    // -------------------- Patients --------------------
    public function patients(): void
    {
        $q = $_GET['q'] ?? null;
        view('nurse.patients', [
            'title'    => 'Patients',
            'active'   => 'patients',
            'patients' => $this->patients->all($q),
            'search'   => $q,
        ]);
    }

    public function patient_view($id = 0): void
    {
        $p = $this->patients->find((int)$id);
        if (!$p) { flash('err','Patient not found.','danger'); redirect('index.php?url=nurse/patients'); }
        view('nurse.patient_view', [
            'title'     => 'Patient: ' . $p['first_name'] . ' ' . $p['last_name'],
            'active'    => 'patients',
            'p'         => $p,
            'records'   => $this->records->forPatient((int)$id),
            'appts'     => $this->appts->all(['patient_id'=>(int)$id]),
        ]);
    }

    public function patient_save(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $db = Database::conn();

        $photo = null;
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = $this->handlePhoto($_FILES['photo']);
        }

        $d = [
            'first_name'     => clean($_POST['first_name'] ?? ''),
            'middle_name'    => clean($_POST['middle_name'] ?? ''),
            'last_name'      => clean($_POST['last_name'] ?? ''),
            'sex'            => ($_POST['sex'] ?? 'male') === 'female' ? 'female' : 'male',
            'birthdate'      => $_POST['birthdate'] ?? null,
            'civil_status'   => $_POST['civil_status'] ?? 'single',
            'contact_no'     => clean($_POST['contact_no'] ?? ''),
            'email'          => clean($_POST['email'] ?? ''),
            'address'        => clean($_POST['address'] ?? ''),
            'barangay'       => clean($_POST['barangay'] ?? ''),
            'blood_type'     => clean($_POST['blood_type'] ?? ''),
            'allergies'      => clean($_POST['allergies'] ?? ''),
            'philhealth_no'  => clean($_POST['philhealth_no'] ?? ''),
            'emergency_name' => clean($_POST['emergency_name'] ?? ''),
            'emergency_no'   => clean($_POST['emergency_no'] ?? ''),
        ];
        if ($photo) $d['photo'] = $photo;

        if (!$d['first_name'] || !$d['last_name'] || !$d['birthdate'] || !$d['address']) {
            flash('err','First name, last name, birthdate and address are required.','danger');
            redirect('index.php?url=nurse/patients');
        }

        try {
            if ($id > 0) {
                $this->patients->update($id, $d);
                AuditLogger::log('update', 'patients', 'Updated patient #' . $id);
                flash('ok','Patient updated successfully.');
            } else {
                $d['patient_code'] = next_patient_code($db);
                $d['created_by']   = current_user()['id'] ?? null;
                $newId = $this->patients->create($d);
                AuditLogger::log('insert', 'patients', 'Created patient #' . $newId);
                flash('ok','Patient registered successfully.');
            }
        } catch (Throwable $e) {
            flash('err','Save failed: ' . $e->getMessage(), 'danger');
        }
        redirect('index.php?url=nurse/patients');
    }

    public function patient_delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $this->patients->delete($id);
            AuditLogger::log('delete', 'patients', 'Deleted patient #' . $id);
            json_response(['ok'=>true,'message'=>'Patient deleted.']);
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    private function handlePhoto(array $file): ?string
    {
        $allowed = ['image/jpeg','image/png','image/webp'];
        if (!in_array(mime_content_type($file['tmp_name']), $allowed, true)) return null;
        if ($file['size'] > 2 * 1024 * 1024) return null; // 2MB
        if (!is_dir(UPLOAD_PATH . 'patients')) mkdir(UPLOAD_PATH . 'patients', 0775, true);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $name = 'p_' . uniqid() . '.' . preg_replace('/[^a-z0-9]/i','', $ext);
        $dest = UPLOAD_PATH . 'patients/' . $name;
        move_uploaded_file($file['tmp_name'], $dest);
        return 'patients/' . $name;
    }

    // -------------------- Appointments --------------------
    public function appointments(): void
    {
        view('nurse.appointments', [
            'title'    => 'Appointments',
            'active'   => 'appointments',
            'appts'    => $this->appts->all([
                'status' => $_GET['status'] ?? null,
                'date'   => $_GET['date'] ?? null,
            ]),
            'patients' => $this->patients->all(),
        ]);
    }

    public function appointment_save(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'patient_id'       => (int)($_POST['patient_id'] ?? 0),
            'appointment_date' => $_POST['appointment_date'] ?? null,
            'appointment_time' => $_POST['appointment_time'] ?? null,
            'purpose'          => clean($_POST['purpose'] ?? ''),
            'notes'            => clean($_POST['notes'] ?? ''),
            'status'           => $_POST['status'] ?? 'pending',
        ];
        if (!$d['patient_id'] || !$d['appointment_date'] || !$d['appointment_time'] || !$d['purpose']) {
            json_response(['ok'=>false,'message'=>'Please fill in all required fields.'], 422);
        }

        // -------- Conflict detection --------
        // Nurse/Admin gets warning + can override with `override=1`
        $conflicts = $this->appts->findConflicts(
            (int)$d['patient_id'],
            (string)$d['appointment_date'],
            (string)$d['appointment_time'],
            $id > 0 ? $id : null,
            15 // ±15 minute overlap window
        );
        if ($conflicts && empty($_POST['override'])) {
            json_response([
                'ok'        => false,
                'conflict'  => true,
                'message'   => 'Scheduling conflict detected. Please review before continuing.',
                'conflicts' => $conflicts,
            ], 409); // 409 Conflict
        }

        try {
            if ($id > 0) {
                $this->appts->update($id, $d);
                AuditLogger::log('update', 'appointments',
                    'Updated appointment #' . $id . (!empty($_POST['override']) ? ' (conflict override)' : '')
                );
                json_response(['ok'=>true,'message'=>'Appointment updated.']);
            } else {
                $d['created_by'] = current_user()['id'] ?? null;
                $newId = $this->appts->create($d);
                AuditLogger::log('insert', 'appointments',
                    'Created appointment #' . $newId . (!empty($_POST['override']) ? ' (conflict override)' : '')
                );
                json_response(['ok'=>true,'message'=>'Appointment scheduled.']);
            }
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function appt_status(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $st = $_POST['status'] ?? '';
        if (!in_array($st, ['pending','approved','rejected','completed','cancelled','rescheduled'], true)) {
            json_response(['ok'=>false,'message'=>'Invalid status.'], 400);
        }
        try {
            $this->appts->setStatus($id, $st, current_user()['id'] ?? null);
            AuditLogger::log('update', 'appointments', "Set status of #$id to $st");

            // Notify patient
            $appt = $this->appts->find($id);
            if ($appt) {
                $p = $this->patients->find((int)$appt['patient_id']);
                if ($p && !empty($p['user_id'])) {
                    Notifier::toUser((int)$p['user_id'],
                        'appt_' . $st,
                        'Appointment ' . ucfirst($st),
                        'Your appointment on ' . fmt_date($appt['appointment_date']) . ' has been ' . $st . '.',
                        'index.php?url=patient/appointments'
                    );
                }
            }
            json_response(['ok'=>true,'message'=>'Status updated.']);
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function appointment_delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $this->appts->delete($id);
            AuditLogger::log('delete', 'appointments', 'Deleted appointment #' . $id);
            json_response(['ok'=>true,'message'=>'Appointment deleted.']);
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    // -------------------- Health Records --------------------
    public function records(): void
    {
        view('nurse.records', [
            'title'    => 'Health Records',
            'active'   => 'records',
            'records'  => $this->records->all(),
            'patients' => $this->patients->all(),
        ]);
    }

    public function record_save(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'patient_id'      => (int)($_POST['patient_id'] ?? 0),
            'appointment_id'  => !empty($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : null,
            'record_type'     => $_POST['record_type'] ?? 'consultation',
            'visit_date'      => $_POST['visit_date'] ?? date('Y-m-d'),
            'bp'              => clean($_POST['bp'] ?? ''),
            'temperature'     => clean($_POST['temperature'] ?? ''),
            'pulse'           => clean($_POST['pulse'] ?? ''),
            'weight'          => clean($_POST['weight'] ?? ''),
            'height'          => clean($_POST['height'] ?? ''),
            'chief_complaint' => clean($_POST['chief_complaint'] ?? ''),
            'diagnosis'       => clean($_POST['diagnosis'] ?? ''),
            'treatment'       => clean($_POST['treatment'] ?? ''),
            'prescription'    => clean($_POST['prescription'] ?? ''),
            'vaccine'         => clean($_POST['vaccine'] ?? ''),
            'remarks'         => clean($_POST['remarks'] ?? ''),
            'attended_by'     => current_user()['id'] ?? null,
        ];
        if (!$d['patient_id']) json_response(['ok'=>false,'message'=>'Please select a patient.'], 422);
        try {
            if ($id > 0) {
                $this->records->update($id, $d);
                AuditLogger::log('update', 'health_records', 'Updated record #' . $id);
                json_response(['ok'=>true,'message'=>'Record updated.']);
            } else {
                $newId = $this->records->create($d);
                AuditLogger::log('insert', 'health_records', 'Created record #' . $newId);
                json_response(['ok'=>true,'message'=>'Record saved.']);
            }
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function record_delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $this->records->delete($id);
            AuditLogger::log('delete', 'health_records', 'Deleted record #' . $id);
            json_response(['ok'=>true,'message'=>'Record deleted.']);
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }
}
