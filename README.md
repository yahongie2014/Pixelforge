# PromptEdit — AI Studio & Generation Platform

> **Senior Product Engineer Assessment**  
> 🔗 **Assessment Specification:** [https://pixelforge.promptedit.dev/assessment](https://pixelforge.promptedit.dev/assessment)  
> 🌐 **Live Staging URL:** [https://staging.wp-expo.io/login](https://staging.wp-expo.io/login)

---

## 🚀 Overview

**PromptEdit** is a full-stack AI image generation application built with **Laravel 12**, **Inertia.js**, and **Vue 3**, powered by the fictional **Pixelforge API**. 

The application is engineered with production-grade architectural patterns, strict type-safety, BCMath financial precision (9 decimal places), distributed Redis caching, failure logging, and comprehensive operator reconciliation reporting.

### 🌟 Key Highlights
- **Architecture**: Domain-driven action classes, full **Repository Pattern**, and **Form Request validation** across all endpoints.
- **Global Enums**: First-class typed enums across PHP and Vue (`GenerationStatus`, `GenerationSize`, `GenerationQuality`, `CreditPurchaseTier`, etc.).
- **Financial Precision**: All provider costs, user markups (+25%), and balances are computed with **BCMath arbitrary-precision arithmetic (9 decimal places)** to prevent IEEE 754 floating-point drift. Failed generations are guaranteed **$0.00** cost.
- **Resilient Webhook Pipeline**: Strict HMAC-SHA256 signature verification, idempotent deduplication via unique event IDs, and status rank state-machine protection against out-of-order webhook delivery.
- **Operator Reconciliation Report (`/admin/report`)**: Day-by-day auditing that compares internal ledger records against Pixelforge's `GET /v1/usage` API with automatic discrepancy detection.
- **Redis Infrastructure**: Distributed rate-limiting, double-click concurrency locks, 60-second keystroke estimation caching, and asynchronous queue workers.
- **Observability**: **Laravel Telescope** for operators (gated to `is_admin`) and dedicated structured failure logging (`storage/logs/failures.log`).
- **One-Click Demo Login**: Pre-configured credentials and instant autofill buttons for Operator Admin and Standard User.
- **Full Test Coverage**: **47 / 47 passing tests (181 assertions)** with 100% green builds.

---

## 🔑 Demo Credentials

The application is seeded with two ready-to-use accounts. Both accounts can also be instantly autofilled directly from the login page (**[https://staging.wp-expo.io/login](https://staging.wp-expo.io/login)**):

| Role | Email | Password | Starting Balance | Capabilities |
|------|-------|----------|------------------|--------------|
| **Operator Admin** | `admin@promptedit.com` | `password` | **$50.00** | Full Studio, Admin Reconciliation Report (`/admin/report`), Laravel Telescope (`/telescope`) |
| **Standard User** | `user@promptedit.com` | `password` | **$15.00** | Studio generation, real-time polling, credit purchase modal |

---

## 📂 Project Folder Tree

```text
├── app/
│   ├── Actions/                                # Single-purpose domain action classes
│   │   ├── GenerateAdminReportAction.php       # Daily reconciliation against GET /v1/usage
│   │   ├── HandleWebhookEventAction.php        # Dedup, out-of-order guard, billing
│   │   ├── PurchaseCreditsAction.php           # Simulated credit top-ups
│   │   ├── SubmitGenerationAction.php          # Validation, Redis lock, Pixelforge dispatch
│   │   └── SyncGenerationFromApiAction.php     # Client polling status synchronization
│   ├── Enums/                                  # Global PHP enums
│   │   ├── CreditPurchaseTier.php              # Available credit purchase tiers ($5 - $100)
│   │   ├── CreditTransactionType.php           # Ledger transaction types
│   │   ├── GenerationQuality.php               # low, medium, high (with token ranges)
│   │   ├── GenerationSize.php                  # 1024x1024, 1536x1024, 1024x1536
│   │   ├── GenerationStatus.php                # queued, processing, succeeded, failed
│   │   └── PixelforgeEventType.php             # Webhook event classifications
│   ├── Http/
│   │   ├── Controllers/                        # Ultra-thin HTTP controllers
│   │   │   ├── AdminReportController.php       # Operator reconciliation portal
│   │   │   ├── CreditController.php            # Balance top-up endpoints
│   │   │   ├── GenerationController.php        # Studio, keystroke estimates, status polling
│   │   │   └── PixelforgeWebhookController.php # Webhook endpoint
│   │   ├── Middleware/
│   │   │   ├── EnsureUserIsAdmin.php           # Admin route gate
│   │   │   ├── HandleInertiaRequests.php       # Global props (auth user, global enums)
│   │   │   └── LogRequestFailures.php          # 4xx & 5xx HTTP request failure interceptor
│   │   └── Requests/                           # Form Requests with strict validation
│   │       ├── AdminReportRequest.php
│   │       ├── BuyCreditRequest.php
│   │       ├── EstimateGenerationRequest.php
│   │       ├── GenerationIndexRequest.php
│   │       ├── PixelforgeWebhookRequest.php    # HMAC-SHA256 signature verification in authorize()
│   │       └── StoreGenerationRequest.php
│   ├── Models/
│   │   ├── CreditTransaction.php               # Immutable ledger records
│   │   ├── Generation.php                      # Generation record with 9dp decimal normalizer
│   │   ├── User.php                            # User model with atomic lockForUpdate() balance
│   │   └── WebhookEvent.php                    # Deduplicated webhook log
│   ├── Policies/
│   │   └── GenerationPolicy.php                # Authorization (owner & admin bypass)
│   ├── Providers/
│   │   ├── AppServiceProvider.php              # Redis rate limiters & queue failure hooks
│   │   ├── RepositoryServiceProvider.php       # Interface-to-Eloquent DI container bindings
│   │   └── TelescopeServiceProvider.php        # Operator gate, night mode, request tagging
│   ├── Repositories/
│   │   ├── Contracts/                          # Repository Interfaces
│   │   │   ├── CreditTransactionRepositoryInterface.php
│   │   │   ├── GenerationRepositoryInterface.php
│   │   │   ├── ReportRepositoryInterface.php
│   │   │   ├── UserRepositoryInterface.php
│   │   │   └── WebhookEventRepositoryInterface.php
│   │   └── Eloquent/                           # Eloquent Implementations
│   │       ├── EloquentCreditTransactionRepository.php
│   │       ├── EloquentGenerationRepository.php
│   │       ├── EloquentReportRepository.php
│   │       ├── EloquentUserRepository.php
│   │       └── EloquentWebhookEventRepository.php
│   └── Services/
│       └── PixelforgeService.php               # Pixelforge API client, BCMath pricing, HMAC check
├── config/
│   ├── logging.php                             # Dedicated 'failures' channel configuration
│   ├── services.php                            # Pixelforge credentials & markup settings
│   └── telescope.php                           # Laravel Telescope watchers & settings
├── database/
│   ├── migrations/                             # Database schema migrations
│   └── seeders/
│       └── DatabaseSeeder.php                  # Seeds operator admin and demo user
├── docker/
│   ├── entrypoint.sh                           # Production container bootstrap & cache warmer
│   ├── nginx/default.conf                      # Optimized Nginx config with gzip & static cache
│   ├── php/php.ini                             # PHP 8.2 production settings
│   ├── php/php-fpm.conf                        # PHP-FPM pool configuration
│   └── supervisor/supervisord.conf             # Manages PHP-FPM, Nginx, Queue Workers, Scheduler
├── resources/
│   ├── js/
│   │   ├── Components/
│   │   │   ├── ApplicationLogo.vue             # Unified PromptEdit gradient brand logo
│   │   │   ├── BuyCreditsModal.vue             # Simulated credit purchase modal
│   │   │   ├── Generation/                     # Domain Vue components
│   │   │   │   ├── CostSummary.vue             # Cost breakdown display
│   │   │   │   ├── EstimatePanel.vue           # Upfront keystroke estimate panel
│   │   │   │   ├── GenerationCard.vue          # Generation item with live status badge
│   │   │   │   ├── ImageViewModal.vue          # Full-resolution image lightbox
│   │   │   │   ├── StatusBadge.vue             # Color-coded animated status badges
│   │   │   │   ├── StudioForm.vue              # Studio form with directives & aspect ratio
│   │   │   │   └── TokenBreakdown.vue          # Granular token counter
│   │   │   └── UI/                             # Reusable design system components
│   │   │       ├── DataTable.vue               # Responsive table container
│   │   │       ├── SectionCard.vue             # Card layout with title & actions slot
│   │   │       └── StatCard.vue                # Metric card with icons & color themes
│   │   ├── composables/
│   │   │   ├── useEstimate.js                  # Keystroke estimation composable with debounce
│   │   │   └── useGenerationPoller.js          # Polling composable with exponential backoff
│   │   ├── constants/
│   │   │   └── enums.js                        # Mirrors backend PHP enums
│   │   ├── Layouts/
│   │   │   ├── AuthenticatedLayout.vue         # Main layout with balance pill & operator nav
│   │   │   └── GuestLayout.vue                 # Login & auth layout with branded header
│   │   ├── Pages/
│   │   │   ├── Admin/Report.vue                # Operator financial reconciliation dashboard
│   │   │   ├── Auth/Login.vue                  # Login page with quick autofill controls
│   │   │   └── Generations/Index.vue           # Studio orchestrator page
│   │   └── utils/
│   │       └── format.js                       # Centralized currency, date, and token formatters
├── tests/
│   ├── Feature/
│   │   ├── AdminReportTest.php                 # Authorization, date validation, API reconciliation
│   │   ├── CreditTest.php                      # Balance purchase tier validation
│   │   ├── GenerationTest.php                  # Studio requests, estimate API, policies
│   │   ├── PixelforgeWebhookTest.php           # HMAC signatures, dedup, billing, out-of-order
│   │   ├── TelescopeAccessTest.php             # Operator gate & Redis concurrency lock tests
│   │   └── Auth/...                            # Authentication tests
│   └── Unit/
│       └── PixelforgeServiceTest.php           # Pricing math, BCMath formulas, signature tests
├── docker-compose.yml                          # Multi-container orchestration (App, MySQL, Redis)
├── Dockerfile                                  # 3-Stage production build (Composer + Node + Alpine PHP 8.2)
└── README.md
```

---

## 🛠️ Installation & Launch

You can run PromptEdit either with **Docker** (recommended for production parity) or **Locally**.

### Method A: Docker (Recommended)

The Docker setup uses a multi-stage Dockerfile packaging PHP 8.2-FPM (with native BCMath and Redis extensions), Nginx, Redis 7, MySQL 8, and Supervisord.

#### 1. Clone repository
```bash
git clone <repository-url>
cd Task
```

#### 2. Start containers
```bash
docker-compose up -d --build
```

The stack automatically:
- Boots **MySQL 8** (`promptedit_db` on port `3308`) and waits for healthy status.
- Boots **Redis 7** (`promptedit_redis` on port `6381`) and waits for healthy status.
- Builds the multi-stage image (`composer install --no-dev`, `vite build`, `php-fpm-alpine`).
- Runs database migrations and seeds demo accounts (`admin@promptedit.com` and `user@promptedit.com`).
- Starts Supervisord managing PHP-FPM, Nginx, 2 Queue Workers, and the Laravel Scheduler.

#### 3. Open in Browser
- **Application:** [http://localhost:8000](http://localhost:8000)
- **Login:** [http://localhost:8000/login](http://localhost:8000/login) (Click **Operator Admin** or **Standard User** to autofill)
- **Admin Report:** [http://localhost:8000/admin/report](http://localhost:8000/admin/report)
- **Laravel Telescope:** [http://localhost:8000/telescope](http://localhost:8000/telescope)

---

### Method B: Local Development

#### Prerequisites
- PHP 8.2+ with `bcmath`, `pdo_sqlite`, `curl`, and `mbstring` extensions
- Composer 2.x
- Node.js 20+ and npm
- (Optional) Redis server for distributed queues/cache (falls back gracefully to array/database driver)

#### 1. Install dependencies
```bash
composer install
npm install
```

#### 2. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

Ensure your `.env` contains the Pixelforge credentials:
```env
PIXELFORGE_BASE_URL=https://pixelforge.promptedit.dev/v1
PIXELFORGE_API_KEY=pf_ArsOLNvJJQ7ANqPWOMJ2FsGn3hzx5m34SxG2zxbla995c0c6
PIXELFORGE_WEBHOOK_SECRET=
PIXELFORGE_MARKUP_PERCENT=25.0
```

#### 3. Database setup & seeding
```bash
touch database/database.sqlite
php artisan migrate --seed
```

#### 4. Build assets & run dev servers
In one terminal, compile frontend assets:
```bash
npm run dev
```

In another terminal, start the PHP server:
```bash
php artisan serve
```

Visit [http://localhost:8000](http://localhost:8000).

---

## 🧪 Running Tests

The test suite covers unit math, authorization policies, webhook deduplication, out-of-order state guards, financial billing, Redis locks, and admin reconciliation:

```bash
php artisan test
```

### Test Suite Output
```text
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Unit\PixelforgeServiceTest (4 tests)
   PASS  Tests\Feature\AdminReportTest (4 tests)
   PASS  Tests\Feature\Auth\AuthenticationTest (4 tests)
   PASS  Tests\Feature\Auth\EmailVerificationTest (3 tests)
   PASS  Tests\Feature\Auth\PasswordConfirmationTest (3 tests)
   PASS  Tests\Feature\Auth\PasswordResetTest (4 tests)
   PASS  Tests\Feature\Auth\PasswordUpdateTest (2 tests)
   PASS  Tests\Feature\Auth\RegistrationTest (2 tests)
   PASS  Tests\Feature\CreditTest (1 test)
   PASS  Tests\Feature\ExampleTest (1 test)
   PASS  Tests\Feature\GenerationTest (4 tests)
   PASS  Tests\Feature\PixelforgeWebhookTest (4 tests)
   PASS  Tests\Feature\ProfileTest (5 tests)
   PASS  Tests\Feature\TelescopeAccessTest (5 tests)

Tests:    47 passed (181 assertions)
Duration: 5.37s
```

Verify frontend compilation with zero errors:
```bash
npm run build
```

---

## 📖 Feature Walkthrough & Usage

### 1. Studio & Generation Form
- **Real-Time Keystroke Estimation**: As you type prompts or select dimensions/quality, the form queries `GET /generations/estimate`. Responses are cached in Redis for 60 seconds to minimize redundant compute.
- **Prompt Directives**: Supports all Pixelforge test directives in prompts:
  - `[succeed]` — Generates a successful image.
  - `[fail]` — Simulates a generation failure ($0 charged to user).
  - `[delay:15]` — Simulates a 15-second processing delay to test live polling and status badges.
  - `[overload]` — Tests HTTP 503 provider handling.
  - `[violates-policy]` — Tests content moderation policy rejection.
- **Reference Images**: Supports multiple image URLs (765 input image tokens per image).
- **Concurrency Guard**: Rapid double-clicking on the Generate button is blocked via an atomic Redis lock (`Cache::lock`), preventing duplicate charges.

### 2. Pricing & Financial Ledger
- **Pricing Formula**:
  $$\text{Provider Cost} = \sum \frac{\text{Tokens} \times \text{Rate}}{1,000,000}$$
  $$\text{User Cost} = \text{Provider Cost} \times 1.25 \quad (+25\%\text{ markup})$$
- **BCMath Precision**: Calculations are executed with 9 decimal places (`bcadd`, `bcsub`, `bcmul`, `bcdiv`, `bccomp`).
- **Zero Cost for Failures**: If a generation fails or is rejected, actual provider and user costs are strictly **$0.000000000**, with no deductions from user balances.

### 3. Webhooks & Real-Time Synchronization
- **HMAC-SHA256 Verification**: Incoming requests to `POST /webhooks/pixelforge` verify the `Pixelforge-Signature` header (`t=<timestamp>,v1=<signature>`) with replay prevention (300-second window).
- **Idempotency & Deduplication**: Every event ID is logged in `webhook_events`. Duplicates receive an immediate HTTP 200 response and are ignored.
- **Out-of-Order Protection**: Generation states follow a monotonic hierarchy (`queued: 1`, `processing: 2`, `succeeded: 3`, `failed: 3`). A delayed `processing` event will never overwrite a completed `succeeded` record.

### 4. Operator Admin Report (`/admin/report`)
Accessible to accounts with `is_admin = true` (`admin@promptedit.com`):
- **Day-by-Day Reconciliation**: Aggregates internal completed generations in UTC and reconciles them against Pixelforge's `GET /v1/usage` API.
- **Audit Badges**:
  - `Exact Match`: Internal provider costs match Pixelforge's unrounded report exactly.
  - `Diff`: Highlights discrepancies down to 9 decimal places with actionable diff amounts.
- **Per-User Breakdown**: Displays succeeded/failed generations, revenue generated, provider costs incurred, operator profit, and live balances.

### 5. Laravel Telescope (`/telescope`)
Accessible to operators directly from the navigation bar:
- Gated via `TelescopeServiceProvider` to authenticated admin accounts (`$user->is_admin`).
- Features real-time request tagging (`domain:generations`, `domain:webhooks`, `domain:admin`, `status:200`, `user:{id}`).
- Dark theme enabled by default.

---

## 🔒 Security & Quality Standards
- **CSRF Exemption**: Webhook endpoint `/webhooks/pixelforge` is explicitly exempt from CSRF in `bootstrap/app.php` and protected via cryptographic signatures.
- **Row-Level Locking**: Credit balance deductions and top-ups execute inside database transactions with `lockForUpdate()`.
- **Sensitive Data Redaction**: Telescope and failure log channels scrub tokens, cookies, and passwords from request dumps.

---

## 👤 Author
Developed by **Ahmed Saeed** for the **Senior Product Engineer Assessment** at PromptEdit.
- **Website**: [https://coder79.me](https://coder79.me)
- **LinkedIn**: [https://linkedin.com/in/devahmedsaeed](https://linkedin.com/in/devahmedsaeed)
- **GitHub Repository**: [https://github.com/yahongie2014/Pixelforge](https://github.com/yahongie2014/Pixelforge)
- **Assessment Spec**: [https://pixelforge.promptedit.dev/assessment](https://pixelforge.promptedit.dev/assessment)
- **Live Staging**: [https://staging.wp-expo.io/login](https://staging.wp-expo.io/login)
