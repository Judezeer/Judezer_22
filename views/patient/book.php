<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-calendar-plus text-primary me-2"></i>Book an Appointment</div>
            <div class="card-body">
                <form method="post" action="<?= e(url('index.php?url=patient/book')) ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Preferred date *</label><input type="date" min="<?= date('Y-m-d') ?>" class="form-control" name="appointment_date" required></div>
                        <div class="col-md-6"><label class="form-label">Preferred time *</label><input type="time" class="form-control" name="appointment_time" required></div>
                        <div class="col-md-12"><label class="form-label">Purpose *</label><input class="form-control" name="purpose" placeholder="e.g. General consultation" required></div>
                        <div class="col-md-12"><label class="form-label">Additional notes</label><textarea class="form-control" name="notes" rows="3"></textarea></div>
                    </div>

                    <div class="alert alert-light border rounded-lg mt-3 mb-0" style="font-size:13px">
                        <i class="fa-solid fa-circle-info text-primary me-2"></i>
                        <strong>Please note:</strong> You can only book one appointment per day.
                        Time slots are ~15 minutes apart. If your preferred slot is taken,
                        please choose a nearby time. Your request will be reviewed by an RHU
                        staff member.
                    </div>
                    <div class="text-end mt-3">
                        <button class="btn btn-primary btn-lg"><i class="fa-solid fa-paper-plane me-1"></i>Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
