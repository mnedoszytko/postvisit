# Security Audit — PostVisit.ai

> Last updated: 2026-02-15
> Audited by: Claude Code (automated) + manual review
> Scope: Full application codebase (Laravel 12 + Vue 3)

> **Healthcare compliance (HIPAA, GDPR, FHIR):** See [`docs/healthcare-compliance.md`](healthcare-compliance.md)

## Dependency Scan

| Tool | Result | Date |
|------|--------|------|
| `composer audit` | 0 advisories | 2026-02-15 |
| Frontend (bun) | No known vulnerabilities | 2026-02-15 |

## OWASP Top 10 Checklist

### A01:2021 — Broken Access Control

| Check | Status | Notes |
|-------|--------|-------|
| IDOR protection (patient data) | **Done** | `VisitPolicy` + `PatientPolicy` enforce ownership-based access |
| Role-based middleware | **Done** | `RoleMiddleware` protects doctor/admin routes |
| Demo controller protection | **Done** | Reset requires auth, seeder scoped to demo data |
| CORS configuration | **Done** | Sanctum stateful domains configured per environment |

### A02:2021 — Cryptographic Failures

| Check | Status | Notes |
|-------|--------|-------|
| TLS in transit | **Done** | Let's Encrypt via Forge, HTTPS enforced |
| Password hashing | **Done** | bcrypt via `Hash::make()`, `BCRYPT_ROUNDS=12` |
| PHI encryption at rest | **Partial** | PostgreSQL encrypted volumes; Laravel `encrypted` casts ready but not enforced on all fields (risk to demo data) |
| API keys in env | **Done** | All secrets in `.env`, not committed |

### A03:2021 — Injection

| Check | Status | Notes |
|-------|--------|-------|
| SQL injection | **Done** | All queries use Eloquent ORM with parameterized bindings |
| XSS (server) | **Done** | Laravel auto-escapes Blade output |
| XSS (client) | **Done** | All `v-html` usages sanitized with DOMPurify |
| Command injection | **Done** | No shell commands executed with user input |
| Prompt injection | **Done** | QA assistant prompt includes injection protection rules |

### A04:2021 — Insecure Design

| Check | Status | Notes |
|-------|--------|-------|
| Rate limiting (API) | **Done** | 3-layer: route throttle (10/min, 30/hr), global daily budget (500/day via `AiBudgetMiddleware`), per-user limit (50/day) |
| Brute force protection | **Done** | Auth endpoints throttled at 5 req/min |
| Token expiration | **Done** | Sanctum cookie-based SPA auth with session expiry (120 min) |
| AI escalation detection | **Done** | `EscalationDetector` monitors for urgent symptoms with Opus thinking-backed evaluation |

### A05:2021 — Security Misconfiguration

| Check | Status | Notes |
|-------|--------|-------|
| Debug mode | **Done** | `APP_DEBUG=false` in production |
| Security headers | **Partial** | HTTPS enforced; HSTS, CSP recommended for production |
| Session security | **Done** | `secure_cookie=true`, `same_site=lax` in production |
| Error details | **Done** | JSON 401 responses instead of redirects; no stack traces in production |

### A06:2021 — Vulnerable Components

| Check | Status | Notes |
|-------|--------|-------|
| PHP dependencies | **Done** | `composer audit` — 0 advisories |
| JS dependencies | **Done** | No known vulnerabilities |
| Framework version | **Done** | Laravel 12, PHP 8.4 (latest stable) |

### A07:2021 — Authentication Failures

| Check | Status | Notes |
|-------|--------|-------|
| Sanctum auth | **Done** | Cookie-based SPA auth (same-origin, no tokens in localStorage) |
| CSRF protection | **Done** | Sanctum CSRF middleware on all state-changing requests |
| Password policy | **Done** | Laravel default rules (8+ chars) |
| Session lifetime | **Done** | 120 min (appropriate for healthcare app sessions) |

### A08:2021 — Data Integrity Failures

| Check | Status | Notes |
|-------|--------|-------|
| Mass assignment | **Done** | All 22 models use `$fillable` (whitelist approach) |
| Input validation | **Done** | All controllers validate input with `$request->validate()` |
| UUID primary keys | **Done** | Non-sequential, non-guessable identifiers on all tables |

### A09:2021 — Logging & Monitoring

| Check | Status | Notes |
|-------|--------|-------|
| Audit middleware | **Done** | `AuditMiddleware` logs all PHI endpoint access with user, action, resource, IP, PHI categories |
| Audit API | **Done** | `GET /api/v1/audit/logs` with filters (doctor/admin only), CSV export |
| Audit log model | **Done** | Append-only design, tracks `phi_accessed`, `action`, `resource_type`, `success` |
| Login/logout events | **Done** | Auth events logged in `AuthController` |

### A10:2021 — SSRF

| Check | Status | Notes |
|-------|--------|-------|
| External API calls | **Done** | Only to known services (Anthropic, RxNorm, OpenFDA, DailyMed, PubMed) |
| User-controlled URLs | **Done** | Library URL import validates and sanitizes URLs |

---

## Security Architecture Summary

```
[Patient App] ──TLS 1.2+──▶ [API + Sanctum Auth] ──Policies──▶ [Service Layer] ──encrypted──▶ [PostgreSQL]
[Doctor App]  ──TLS 1.2+──▶ [RoleMiddleware]          │                                         │
                              [AuditMiddleware]         │ data minimization                      │ audit logs
                              [AiBudgetMiddleware]      ▼                                         ▼
                              [Rate Limiting]    [Anthropic API]                           [AuditLog table]
```

**Key security properties:**
- Patient data is patient-owned (consent model, right to access/erasure)
- AI never sees direct patient identifiers unless clinically necessary
- Every PHI access is audit-logged with user, action, resource, and IP
- Role-based access control with ownership verification via Laravel Policies
- DOMPurify sanitization on all client-side HTML rendering
- Prompt injection protection in AI system prompts
- 3-layer rate limiting protects against API abuse

## Demo Disclaimer

This application is a **hackathon prototype**. The security architecture described here and in `SECURITY.md` represents both implemented controls and production-ready design. **No real patient data is used.** All clinical scenarios are fictional.
