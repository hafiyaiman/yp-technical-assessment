# 🎓 Exam Portal

A full-featured online examination platform built for academic institutions. Supports role-based access for admins, lecturers, and students — with timed exams, auto-grading, open-text grading, OTP authentication.

## Stack

| Layer     | Technology                               |
| --------- | ---------------------------------------- |
| Framework | Laravel 11                               |
| Frontend  | Livewire 3, Volt, TallStackUI v3         |
| Styling   | Tailwind CSS 4 (via `@tailwindcss/vite`) |
| Database  | MySQL                                    |
| Storage   | Cloudflare R2 (S3-compatible)            |
| Testing   | Pest (SQLite in-memory)                  |

---

## Getting Started

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure your database

Create a MySQL database in TablePlus (or any client), then update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=exam_portal
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Run migrations and seed demo data

```bash
php artisan migrate
php artisan db:seed
```

The seeder creates a full demo dataset: 1 admin, 7 lecturers, 49 students, 5 classes, 6 subjects, teaching assignments, and several exams in various states.

### 5. Start the development server

```bash
php artisan serve
npm run dev
```

---

## Demo Accounts

| Role         | Email                                         | Password |
| ------------ | --------------------------------------------- | -------- |
| Admin        | admin@example.com                             | password |
| Lecturer     | lecturer@example.com                          | password |
| Lecturer 1–6 | lecturer1@example.com … lecturer6@example.com | password |
| Student      | student@example.com                           | password |
| Student 1–48 | student1@example.com … student48@example.com  | password |

---

## Authentication

Login uses a **two-step OTP flow**:

1. User enters email and password.
2. A 6-digit OTP is sent to their email (expires in 10 minutes, up to 5 failed attempts allowed).
3. User enters the OTP at `/login/otp` to complete sign-in.

### Local Email (Mailpit)

Emails are sent to Mailpit locally. Configure `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
```

Open **http://127.0.0.1:8025** to view OTP emails in the Mailpit UI.

## Roles & Permissions

| Role           | Capabilities                                                                       |
| -------------- | ---------------------------------------------------------------------------------- |
| `system-admin` | Manage users, classes, subjects, enrollments, teaching assignments                 |
| `lecturer`     | Create and manage exams, grade open-text answers, view results for own assignments |
| `student`      | Take assigned exams, view own results                                              |

Check roles and permissions in code:

```php
$user->hasRole('lecturer');
$user->hasPermission('manage-exams');
```

Protect routes with middleware:

```php
Route::middleware('role:lecturer')->group(...);
Route::middleware('permission:manage-exams')->group(...);
```

---

## Routes Overview

### Admin (`/admin`)

| Path                          | Purpose                                        |
| ----------------------------- | ---------------------------------------------- |
| `/admin/users`                | Manage admin, lecturer, and student accounts   |
| `/admin/classes`              | Set up classes, link subjects, enroll students |
| `/admin/subjects`             | Manage reusable subjects                       |
| `/admin/teaching-assignments` | Assign lecturers to class-subject pairs        |

### Lecturer (`/lecturer`)

| Path                                           | Purpose                                    |
| ---------------------------------------------- | ------------------------------------------ |
| `/lecturer/my-classes`                         | View assigned class-subject cards          |
| `/lecturer/exams`                              | List, filter, publish, and close exams     |
| `/lecturer/teaching/{assignment}/exams/create` | Create a new exam for an assignment        |
| `/lecturer/exams/{exam}/edit`                  | Build and edit exam questions              |
| `/lecturer/exams/{exam}/submissions`           | Grade open-text answers and review results |

### Student (`/student`)

| Path                                    | Purpose                                |
| --------------------------------------- | -------------------------------------- |
| `/student/exams`                        | Browse available exams                 |
| `/student/exams/{exam}`                 | View exam instructions before starting |
| `/student/attempts/{attempt}`           | Take the timed exam with autosave      |
| `/student/attempts/{attempt}/review`    | Review answers before submitting       |
| `/student/attempts/{attempt}/submitted` | Confirmation or expired state          |
| `/student/results`                      | View past results and pending reviews  |

---

## Examination Rules

- Students belong to one active class via `users.school_class_id`.
- Lecturers can only create and manage exams for their own teaching assignments.
- Students can only access published exams assigned to their class.
- Each student gets **one attempt** per exam.
- **Multiple-choice** questions are auto-scored on submission.
- **Open-text** questions require manual lecturer grading.
- Exam timers are enforced **server-side** via `expires_at`.

---

## Cloudflare R2 Storage

Configure R2 credentials in `.env`:

```env
FILESYSTEM_DISK=r2
CLOUDFLARE_R2_ACCESS_KEY_ID=
CLOUDFLARE_R2_SECRET_ACCESS_KEY=
CLOUDFLARE_R2_REGION=auto
CLOUDFLARE_R2_BUCKET=
CLOUDFLARE_R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
CLOUDFLARE_R2_URL=
CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT=false
CLOUDFLARE_R2_VISIBILITY=private
```

Verify the connection with Tinker:

```bash
php artisan tinker
```

```php
Storage::disk('r2')->put('health-check.txt', 'ok');
Storage::disk('r2')->get('health-check.txt');
Storage::disk('r2')->delete('health-check.txt');
```

---

## Testing

Tests use SQLite in-memory — no MySQL or R2 credentials required.

```bash
php artisan test
```

---
