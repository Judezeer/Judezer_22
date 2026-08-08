<?php
class PharmacistController
{
    private MedicineModel $meds;
    private BatchModel $batches;
    private DispensingModel $disp;
    private InventoryLogModel $ilog;
    private PatientModel $patients;

    public function __construct(bool $skipGuard = false)
    {
        if (!$skipGuard) AuthMiddleware::requireRole('pharmacist', 'admin');
        $this->meds     = new MedicineModel();
        $this->batches  = new BatchModel();
        $this->disp     = new DispensingModel();
        $this->ilog     = new InventoryLogModel();
        $this->patients = new PatientModel();
    }

    // -------------------- Dashboard --------------------
    public function dashboard(): void
    {
        Notifier::refreshInventoryAlerts();

        $stats = [
            'medicines'       => $this->meds->countAvailable(),
            'low_stock'       => $this->meds->countLowStock(),
            'expired'         => $this->meds->countExpired(),
            'dispensed_today' => $this->disp->countToday(),
        ];
        $lowList = $this->meds->lowStockList();
        $expList = $this->meds->expiredList();
        view('pharmacist.dashboard', [
            'title'         => 'Pharmacist Dashboard',
            'active'        => 'dashboard',
            'stats'         => $stats,
            'disp_chart'    => $this->disp->monthlySeries(),
            'low_list'      => $lowList,
            'near_list'     => $this->meds->nearExpiryList((int)(setting('near_expiry_days','60'))),
            'exp_list'      => $expList,
            'alert_low'     => $lowList,
            'alert_expired' => $expList,
            'alert_role'    => 'pharmacist',
        ]);
    }

    // -------------------- Medicines --------------------
    public function medicines(): void
    {
        $q = $_GET['q'] ?? null;
        view('pharmacist.medicines', [
            'title'  => 'Medicine Inventory',
            'active' => 'medicines',
            'meds'   => $this->meds->all($q),
            'search' => $q,
        ]);
    }

    public function medicine_save(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'code'          => clean($_POST['code'] ?? ''),
            'name'          => clean($_POST['name'] ?? ''),
            'generic_name'  => clean($_POST['generic_name'] ?? ''),
            'category'      => clean($_POST['category'] ?? ''),
            'dosage_form'   => clean($_POST['dosage_form'] ?? ''),
            'strength'      => clean($_POST['strength'] ?? ''),
            'unit'          => clean($_POST['unit'] ?? 'piece'),
            'description'   => clean($_POST['description'] ?? ''),
            'reorder_level' => (int)($_POST['reorder_level'] ?? 20),
            'supplier'      => clean($_POST['supplier'] ?? ''),
            'status'        => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
        ];
        if (!$d['code'] || !$d['name']) json_response(['ok'=>false,'message'=>'Code and name are required.'], 422);
        try {
            if ($id > 0) {
                $this->meds->update($id, $d);
                AuditLogger::log('update', 'medicines', 'Updated medicine #' . $id);
                json_response(['ok'=>true,'message'=>'Medicine updated.']);
            } else {
                $newId = $this->meds->create($d);
                AuditLogger::log('insert', 'medicines', 'Created medicine #' . $newId);
                json_response(['ok'=>true,'message'=>'Medicine added.']);
            }
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function medicine_delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $this->meds->delete($id);
            AuditLogger::log('delete', 'medicines', 'Deleted medicine #' . $id);
            json_response(['ok'=>true,'message'=>'Medicine deleted.']);
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    // -------------------- Batches --------------------
    public function batches(): void
    {
        view('pharmacist.batches', [
            'title'   => 'Batch Management',
            'active'  => 'batches',
            'batches' => $this->batches->all(),
            'meds'    => $this->meds->all(),
        ]);
    }

    public function stock_in(): void
    {
        $d = [
            'medicine_id'     => (int)($_POST['medicine_id'] ?? 0),
            'batch_no'        => clean($_POST['batch_no'] ?? ''),
            'quantity'        => (int)($_POST['quantity'] ?? 0),
            'expiration_date' => $_POST['expiration_date'] ?? null,
            'received_date'   => $_POST['received_date'] ?? date('Y-m-d'),
            'supplier'        => clean($_POST['supplier'] ?? ''),
            'remarks'         => clean($_POST['remarks'] ?? ''),
        ];
        if (!$d['medicine_id'] || !$d['batch_no'] || $d['quantity'] <= 0 || !$d['expiration_date']) {
            json_response(['ok'=>false,'message'=>'Please fill in all required fields.'], 422);
        }
        try {
            $bid = $this->batches->stockIn($d, current_user()['id'] ?? 0);
            AuditLogger::log('stock_in', 'inventory', 'Batch #' . $bid . ' (+' . $d['quantity'] . ')');

            // Notify admin if this brings the medicine above the low-stock line
            json_response(['ok'=>true,'message'=>'Stock added successfully.']);
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function stock_out(): void
    {
        $bid   = (int)($_POST['batch_id'] ?? 0);
        $qty   = (int)($_POST['quantity'] ?? 0);
        $reason = clean($_POST['reason'] ?? 'Manual adjustment');
        try {
            $this->batches->stockOut($bid, $qty, $reason, current_user()['id'] ?? 0);
            AuditLogger::log('stock_out', 'inventory', "Batch #$bid (-$qty): $reason");
            json_response(['ok'=>true,'message'=>'Stock out recorded.']);
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function batch_delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $this->batches->delete($id);
            AuditLogger::log('delete', 'medicine_batches', 'Deleted batch #' . $id);
            json_response(['ok'=>true,'message'=>'Batch deleted.']);
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    // -------------------- Inventory logs --------------------
    public function inventory(): void
    {
        view('pharmacist.inventory_logs', [
            'title'  => 'Inventory Logs',
            'active' => 'inventory',
            'logs'   => $this->ilog->all([
                'action' => $_GET['action'] ?? null,
                'from'   => $_GET['from'] ?? null,
                'to'     => $_GET['to'] ?? null,
            ]),
        ]);
    }

    // -------------------- Dispensing --------------------
    public function dispensing(): void
    {
        view('pharmacist.dispensing', [
            'title'    => 'Dispense Medicine',
            'active'   => 'dispensing',
            'patients' => $this->patients->all(),
            'meds'     => $this->meds->all(),
            'history'  => $this->disp->all(['from'=>date('Y-m-01')]),
        ]);
    }

    public function dispense_save(): void
    {
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $notes = clean($_POST['notes'] ?? '');
        $items = $_POST['items'] ?? [];
        if (!$patientId) json_response(['ok'=>false,'message'=>'Please select a patient.'], 422);
        if (!is_array($items) || !$items) json_response(['ok'=>false,'message'=>'Add at least one medicine.'], 422);
        try {
            $id = $this->disp->dispense($patientId, current_user()['id'] ?? 0, $items, $notes ?: null);
            AuditLogger::log('dispense', 'dispensing', 'Dispense #' . $id);
            json_response(['ok'=>true,'message'=>'Medicines dispensed.','id'=>$id]);
        } catch (Throwable $e) {
            json_response(['ok'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function receipt($id = 0): void
    {
        $d = $this->disp->find((int)$id);
        if (!$d) { flash('err','Receipt not found.','danger'); redirect('index.php?url=pharmacist/dispensing'); }
        view('pharmacist.receipt', [
            'title'  => 'Receipt ' . $d['receipt_no'],
            'active' => 'dispensing',
            'd'      => $d,
            'items'  => $this->disp->items((int)$id),
            '_layout'=> 'shared.print',
        ]);
    }
}
