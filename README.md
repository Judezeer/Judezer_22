# RHU Makilala — Health Management Information System

A **production-ready** web-based Patient Appointment & Health Record System with
Medicine Inventory and Dispensing Monitoring for the **Rural Health Unit of
Makilala** (Cotabato, Philippines).

Built with pure **PHP 8.2 (OOP · MVC · PDO)** + **MySQL** + **Bootstrap 5**,
runs out-of-the-box on **XAMPP** (Apache + MySQL + PHP).

![Theme](https://img.shields.io/badge/theme-White%20%2B%20Green-16A34A)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1)
![License](https://img.shields.io/badge/license-MIT-green)

---

## ✨ Features

**By role**

| Role | Capabilities |
|------|--------------|
| **Administrator** | Dashboard, user management, reports, audit logs, DB backup, settings |
| **Nurse / RHU Staff** | Patient registration, appointments (approve/reject/complete), health records |
| **Pharmacist** | Medicine master list, batches (FEFO), stock in/out, dispensing with auto-deduction, receipts, inventory logs |
| **Patient** | Book appointment, view status & history, medical records, profile, notifications |

**System-wide**
- Modern **White + Green medical SaaS UI** (Bootstrap 5, glassmorphism login,
  hero sections, DataTables, Chart.js, SweetAlert2, Font Awesome 6)
- Secure: PDO prepared statements, password hashing, **CSRF tokens on every POST**,
  XSS escaping, role middleware, idle **session timeout**, complete **audit trail**
- Automatic **FEFO** (First-Expiring-First-Out) stock deduction on dispensing
- Low stock, near-expiry and expired-medicine alerts
- In-app notifications with polling badge
- Printable dispensing receipts
- CSV export + print for every report

---

## 🚀 Installation (XAMPP)

1. **Copy the project** into `xampp/htdocs/`:
   ```
   xampp/htdocs/rhu-makilala/
   ```

2. **Start** Apache + MySQL from the XAMPP control panel.

3. **Import the database:**
   - Open <http://localhost/phpmyadmin>
   - Click **Import** → choose `database/rhu_makilala.sql`
   - Click **Go**.
   > This creates the `rhu_makilala` database, all tables and seed users.

4. **Run the one-time installer** (finalizes password hashes for the seed users):
   <http://localhost/rhu-makilala/install.php>

5. **Sign in:** <http://localhost/rhu-makilala/>

   | Role | Username | Password |
   |------|----------|----------|
   | Administrator | `admin` | `Admin@123` |
   | Nurse | `nurse` | `Nurse@123` |
   | Pharmacist | `pharmacist` | `Pharma@123` |
   | Patient | `patient` | `Patient@123` |

6. **Recommended:** delete `install.php` after first login for security.

If your MySQL user/password differ from the XAMPP default (`root` / empty),
edit `config/database.php`.

---

## 🗂 Folder Structure

```
rhu-makilala/
├── config/           bootstrap, database, config constants
├── controllers/      AuthController, AdminController, NurseController,
│                     PharmacistController, PatientController, ApiController
├── models/           BaseModel + entity models (User, Patient, Appointment,
│                     HealthRecord, Medicine, Batch, Dispensing, etc.)
├── views/
│   ├── auth/         login, forgot
│   ├── admin/        dashboard, users, reports, audit, settings
│   ├── nurse/        dashboard, patients, patient_view, appointments, records
│   ├── pharmacist/   dashboard, medicines, batches, dispensing, receipt, logs
│   ├── patient/      dashboard, book, appointments, records, profile, notifications
│   ├── partials/     sidebar, topbar
│   └── shared/       layout, blank, print
├── middlewares/      AuthMiddleware (login, role, csrf guards)
├── helpers/          functions.php, AuditLogger, Notifier
├── database/         rhu_makilala.sql
├── assets/           css/, js/, images/, uploads/
├── vendor/           (reserved for future PHP libraries)
├── .htaccess
├── index.php         front controller / router
├── install.php       one-time password finalizer
└── README.md
```

---

## 🔒 Security Notes

- **All SQL** uses PDO **prepared statements** — no string-concatenated queries.
- **All output** is escaped with `e()` (htmlspecialchars).
- **Every POST** goes through `AuthMiddleware::verifyCsrf()` (except the login
  form, which handles it internally to render a nicer error message).
- **Session** cookies are `HttpOnly` + `use_strict_mode`; sessions **regenerate**
  on login (fixation defense) and expire after **30 minutes** of inactivity.
- Users are stored with **`password_hash()`** (bcrypt) and verified with
  `password_verify()`.
- Every state-changing action writes to `audit_logs` via `AuditLogger::log()`.
- Uploaded photos are MIME-validated and size-capped (2 MB).

**Before production deployment:**
- Change every default password.
- Set `APP_ENV = 'production'` and `APP_DEBUG = false` in `config/config.php`
  (already the default).
- Serve the app under **HTTPS** and turn on the `Set-Cookie: Secure` flag by
  adding `ini_set('session.cookie_secure','1');` to `config/bootstrap.php`.
- Point Apache’s document root to the project folder or place it behind a
  proper reverse-proxy — never expose `install.php` publicly.
- Take regular backups (Admin → **Backup DB** generates a full SQL dump).

---

## 🧑‍💻 Development notes

- URL pattern: `index.php?url=controller/action/param1/param2`
- New controller → drop a `SomethingController.php` in `controllers/` and
  register it in `index.php`'s `$controllerMap`.
- New model → extend `BaseModel` — you automatically get the shared PDO
  connection via `$this->db`.
- New page → add a view under `views/<role>/` and a matching controller action.

---

## 📄 License

MIT — free to use, modify and adapt for the Rural Health Unit of Makilala
and other public-health facilities.
