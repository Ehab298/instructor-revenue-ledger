# Instructor Revenue Ledger

The money core of an LMS platform: students pay for subscriptions up front, instructors earn a revenue share of every payment, and the platform pays each instructor what they are owed through an (unreliable) external payment provider — **accurately, at scale, and even when parts of the system fail or run more than once**.

This implementation is built around one principle: **money correctness is a database guarantee, not a code hope.** Every state transition that moves money is an atomic claim; the ledger is append-only; and uncertain outcomes are resolved by asking questions, never by retrying payments.

---

## Requirements

- PHP 8.3+, Composer
- MySQL (or SQLite for a quick demo)
- Node not required — the student-facing page is self-contained; Filament assets are bundled

## Quick start

```bash
# 1. Install dependencies
composer install

# 2. Configure the environment
cp .env.example .env
php artisan key:generate
#   set DB_CONNECTION / DB_DATABASE / DB_USERNAME / DB_PASSWORD in .env

# 3. Create the schema with demo data
php artisan migrate --seed

# 4. Run the test suite
php artisan test
```

### Seeded accounts

| Role | Email | Password |
|---|---|---|
| Admin | `admin@lms.com` | `password` |
| Instructors | `ahmed.abdelrahman@lms.com` … `layla.mansour@lms.com` | `password` |
| Students | 20 random users (`role = student`) | `password` |

---

## How the system works

### 1. Taking money in — subscriptions

A public page (`/subscribe`) lets a student pick themselves, one or more courses, and a plan:

| Plan | Price (paid up front, day one) | Access |
|---|---|---|
| Monthly | 1,200 EGP | 1 month |
| Quarterly | 6,000 EGP | 3 months |
| Annual | 10,000 EGP | 12 months |

`CreateSubscription` runs inside a single database transaction:

1. Locks the student row (`lockForUpdate`) so concurrent requests serialize.
2. Rejects the request if **any** selected course is already covered by an active subscription (`ends_at > now`).
3. Creates the subscription, attaches the courses.
4. Allocates revenue and writes `earning` ledger rows — **committed together with the subscription, or not at all**.

### 2. Splitting the money — revenue allocation

`App\Services\Revenue\RevenueAllocator` — pure integer math, no floating point anywhere:

- Platform keeps **exactly 30%**, instructors share **exactly 70%**.
- The 70% pool is divided **equally among the distinct instructors** of the subscription's courses (an instructor owning several courses is counted once).
- If the pool doesn't divide evenly, the leftover piastres are handed out one by one to the first instructors, so shares differ by at most 1 piastre.
- Invariant enforced and tested: `platform + Σ instructors = total` — **no money is ever lost to rounding**.

All amounts are stored as **integer piastres** (1 EGP = 100 piastres). Division by 100 happens in exactly one place: `App\Support\Money::format()`, at the display edge.

### 3. Paying money out — the payout pipeline

```
php artisan payouts:run
        │ dispatches one job per instructor
        ▼
ProcessInstructorPayouts   locks the instructor row, skips if a payout is
                           in flight, computes the ledger balance,
                           creates a `pending` payout (unique provider
                           reference) and dispatches:
        ▼
ProcessPayout              atomically claims pending → processing,
                           calls provider->pay() ONCE per reference
        ├─ paid            → success + one deducting ledger row
        ├─ failed          → failed; balance untouched; safe to retry later
        └─ timeout         → timeout; NO retry; dispatches:
                ▼
ResolvePayout              asks provider->checkStatus() (query only!)
                            ├─ was paid  → success + ledger row (exactly once)
                            └─ not paid  → failed; safe to schedule a new payout
```

**Why this can't double-pay** — every transition is an atomic claim:

```sql
UPDATE payouts SET status = 'processing' WHERE id = ? AND status = 'pending'
```

Only the worker that flips the row proceeds; retried jobs, racing workers, or a second command run lose the claim and do nothing. The deducting ledger row is written **in the same transaction** as the `success` transition, by the claim winner only.

### 4. The unreliable provider

`MockPaymentProvider` simulates reality: it may succeed, fail permanently, or **time out after already moving the money**. It records the *truth* internally before reporting anything — so a timeout hides a fact that `checkStatus($reference)` reveals later, exactly like a real provider's status API.

The iron rule when the outcome is unknown: **never re-send, only query.**

### 5. Answering the three questions

There is no stored balance column — the ledger is the single source of truth:

```
total earned   = Σ(earning) − Σ(refund)
total paid     = Σ(payout)
still owed     = earned − paid
```

Timezout payouts are *not* deducted: they are neither paid nor failed yet.

### 6. Filament screens (read-only)

At `/admin` (login with the seeded admin):

- **Instructors** — per-instructor: total earned / total paid / outstanding balance, plus full payout history (amount, status badges, provider reference, attempts).
- **Payout history** — all payouts platform-wide, filterable by status.
- **Dashboard widget** — platform-wide earnings / paid out / outstanding.

---

## Running the payout demo

Terminal 1 (worker — `QUEUE_CONNECTION=database` by default):

```bash
php artisan queue:work
```

Terminal 2:

```bash
# Create a subscription on http://localhost:8000/subscribe (or seed a ledger row)
php artisan payouts:run
```

Watch the job chain execute in the worker, then verify in `/admin/instructors` that the balance dropped to 0.00 EGP and a payout row appeared.

## Running the tests

```bash
php artisan test
```

The suite is intentionally small and dense — every test protects money:

| Test | Proves |
|---|---|
| `running_the_process_twice_never_double_pays` | the challenge's required guarantee #1 |
| `retried_job_after_crash_never_pays_twice` | required guarantee #2 (crashed worker, job retried) |
| `timeout_then_delayed_confirmation_no_duplicate` | required guarantee #3 (provider succeeds then goes silent) |
| `permanent_failure_keeps_balance_and_retry_pays_once` | failed payments never corrupt the balance |
| `successful_run_pays_and_deducts_balance` | the full money flow lands in the ledger exactly once |
| `subscription_records_instructor_earnings` | intake side writes correct, referenced earning rows |
| 6 unit tests (allocator + provider) | split math incl. rounding edge cases; the timeout truth is discoverable |

---

## Design decisions (the ones the challenge left open)

| Question | Decision | Why |
|---|---|---|
| When is money earned? | Immediately on successful payment | The full term is paid up front; refunds (not final withdrawals) are the correcting mechanism |
| How to divide between instructors? | Equally among distinct instructors of the payment | Simple, explainable, and invariant-safe; an instructor with 3 courses isn't triple-counted |
| Uneven splits? | Leftover piastres to the first instructors | Keeps `platform + instructors = total` without inventing a "rounding bucket" |
| Money storage unit? | Integer piastres | Zero floating-point in money paths; the industry-standard approach (Stripe-style cents) |
| Modify a paid subscription? | Never — ledger is append-only | Retroactive re-splitting after payouts is exactly the "recover paid money" trap; new courses = new payment |
| Unknown provider outcome? | Query (`checkStatus`), never re-send | The only safe answer to "did the money move?" |
| Refunds mid-term | Schema + balance math fully support `refund` rows (pro-rata, negative balance carried against future earnings) | Implemented at the ledger level; the refund service itself is listed under limitations below |

## Scaling notes

- The command never loops over instructors: it dispatches **one queued job per instructor**, so payout work spreads across workers.
- Balance queries aggregate `ledger_transactions` on `(instructor_id, type)`.
- At tens of millions of rows the next step is a denormalized `instructor_balances` table updated inside the same transactions as the ledger writes, with the ledger remaining the audit truth.

## Known limitations

- The refund service (mid-term cancellation) is designed but not wired: the ledger type, balance arithmetic, and negative-balance semantics exist; the pro-rata calculation + endpoint are not.
- Payout scheduling is manual (`payouts:run`); wiring it to the scheduler (`routes/console.php`) is trivial but deliberately left explicit for review.
- The mock provider's "truth" lives in memory (singleton) — fine for tests and demo; a real provider binding would be a stateless HTTP client behind the same interface.

## Project map

```
app/
├── Console/Commands/ProcessPayoutsCommand.php      payouts:run
├── Enums/SubscriptionPlan.php                      pricing + durations (piastres)
├── Filament/                                       instructor & payout screens + stats widget
├── Jobs/                                           ProcessInstructorPayouts → ProcessPayout → ResolvePayout
├── Services/
│   ├── Payments/                                   PaymentProvider contract + MockPaymentProvider
│   ├── Revenue/                                    RevenueAllocator, AllocateSubscriptionEarnings
│   └── Subscriptions/CreateSubscription.php        intake transaction
├── Models/                                         User (balance accessors), Subscription, Payout, LedgerTransaction
└── Support/Money.php                               the single display-formatting point
```
