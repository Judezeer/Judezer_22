# Installation Guide — RHU Makilala HMIS

Quick, verified steps to get the system running on **XAMPP** in under 5 minutes.

---

## 1. Requirements

- **XAMPP 8.2+** (bundles Apache, MySQL/MariaDB and PHP 8.2)
  - Windows: <https://www.apachefriends.org/>
  - macOS/Linux equivalent works too
- Modern browser (Chrome, Edge, Firefox)

Verify PHP version once XAMPP is installed:
```
http://localhost/dashboard/phpinfo.php
```

---

## 2. Copy the project

Extract / clone this project into:

```
<xampp>/htdocs/rhu-makilala/
```

The path segment (`rhu-makilala`) becomes the app's base URL. You may rename it
(e.g. `hmis`) — no other changes required, `BASE_URL` auto-detects.

---

## 3. Start Apache & MySQL

Open the **XAMPP Control Panel** → click **Start** on **Apache** and **MySQL**.

---

## 4. Import the database

1. Open <http://localhost/phpmyadmin>
2. Click the **Import** tab (top).
3. Choose file: `database/rhu_makilala.sql`
4. Click **Go**.

You'll now have a database called `rhu_makilala` with 13 tables and 4 seed users.

> **If MySQL asks for a username/password other than `root` / empty**, edit
> `config/database.php` and change `DB_USER` / `DB_PASS`.

---

## 5. Finalize password hashes

The SQL file uses placeholder hashes so the file itself is portable.
Run the installer **once** to generate real bcrypt hashes:

```
http://localhost/rhu-makilala/install.php
```

You'll see a confirmation page listing the default credentials.

---

## 6. Sign in

Open <http://localhost/rhu-makilala/> and use any account below:

| Role | Username | Password |
|------|----------|----------|
| Administrator | `admin` | `Admin@123` |
| Nurse | `nurse` | `Nurse@123` |
| Pharmacist | `pharmacist` | `Pharma@123` |
| Patient | `patient` | `Patient@123` |

---

## 7. First-time housekeeping

1. **Delete `install.php`** from the project root — you no longer need it.
2. Sign in as **admin** → **Settings**: set the clinic name, address and
   contact.
3. **Users**: create real accounts for your nurses, pharmacists and patients,
   then either change or disable the seed accounts.

---

## Troubleshooting

**"Database connection failed."**
- Make sure MySQL is running in XAMPP.
- Confirm the database name is exactly `rhu_makilala`.
- Verify `DB_USER` and `DB_PASS` in `config/database.php`.

**Login says "Invalid username or password."**
- Did you run `install.php`? Without it, the seed passwords are unusable.
- Try re-running `install.php` — it's idempotent.

**Blank white page / 500 error**
- Temporarily set `APP_DEBUG = true` in `config/config.php` to see the
  underlying error, then set it back to `false` when done.

**Uploads fail**
- Confirm the folder `assets/uploads/` is writable by Apache.
  On Linux/macOS: `chmod -R 775 assets/uploads`.
- Uploads are capped at 2 MB per file.

---

## Deploying to a shared server / VPS

- Point the web root to the project folder (not to `xampp/htdocs`).
- Serve over HTTPS.
- Enable `session.cookie_secure = 1` in `config/bootstrap.php`.
- Delete `install.php`.
- Set database credentials appropriate for that environment.
- Add a scheduled MySQL backup (or use the admin **Backup DB** button
  periodically).
