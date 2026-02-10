# Security Audit — PostVisit.ai

> Last updated: 2026-02-10
> Audited by: Claude Code (automated) + manual review
> Scope: Full application codebase (Laravel 12 + Vue 3)

## Dependency Scan

| Tool | Result | Date |
|------|--------|------|
| `composer audit` | 0 advisories | 2026-02-10 |
| Frontend (bun) | No known vulnerabilities | 2026-02-10 |

## OWASP Top 10 Checklist

### A01:2021 — Broken Access Control

| Check | Status | Notes |
|-------|--------|-------|
| IDOR protection (patient data) | **TODO** | Routes use implicit model binding without ownership verification. Need Laravel Policies for Patient, Visit, Document, ChatSession. |
| Role-based middleware | OK | `RoleMiddleware` protects doctor routes. |
| Demo controller protection | **TODO** | `reset()` runs `migrate:fresh` — must be disabled or guarded in production. |
| CORS configuration | **TODO** | Default `allowed_origins: ['*']` — must restrict to app domain in production. |

**Action items:**
- [ ] Create authorization Policies (Patient, Visit, Document, ChatSession)
- [ ] Add ownership checks in controllers (patient can only see own data)
- [ ] Guard DemoController behind `APP_ENV=local` check
- [ ] Publish and configure `config/cors.php` with specific origins

### A02:2021 — Cryptographic Failures

| Check | Status | Notes |
|-------|--------|-------|
| TLS in transit | OK | Let's Encrypt via Forge |
| Password hashing | OK | bcrypt via `Hash::make()` |
| PHI encryption at rest | **TODO** | `ssn_encrypted` field exists but no application-level encryption implemented yet |
| API keys in env | OK | All secrets in `.env`, not committed |

**Action items:**
- [ ] Implement Laravel's `Crypt::encrypt()` for SSN and other PHI fields
- [ ] Add encrypted cast to sensitive model attributes

### A03:2021 — Injection

| Check | Status | Notes |
|-------|--------|-------|
| SQL injection | OK | All queries use Eloquent ORM with parameterized bindings |
| XSS (server) | OK | Laravel auto-escapes Blade output |
| XSS (client) | OK | No `v-html` or `innerHTML` in Vue components |
| Command injection | OK | No shell commands executed with user input |
| Prompt injection | **TODO** | AI input (term, context) needs sanitization before Opus calls |

### A04:2021 — Insecure Design

| Check | Status | Notes |
|-------|--------|-------|
| Rate limiting | **TODO** | No throttle middleware on API routes |
| Brute force protection | **TODO** | Login endpoint has no rate limit |
| Token expiration | **TODO** | Sanctum tokens never expire (`expiration: null`) |

**Action items:**
- [ ] Add `throttle:api` middleware to API route group
- [ ] Add stricter throttle on auth endpoints (`throttle:5,1`)
- [ ] Set Sanctum token expiration (e.g., 30 days)

### A05:2021 — Security Misconfiguration

| Check | Status | Notes |
|-------|--------|-------|
| Debug mode | OK | `APP_DEBUG=true` only in `.env` (not committed) |
| Security headers | **TODO** | Missing HSTS, CSP, X-Frame-Options, X-Content-Type-Options |
| Session security | **TODO** | `secure_cookie` not enforced, `same_site` is 'lax' |
| Error details | OK | Laravel handles error responses appropriately |

**Action items:**
- [ ] Add security headers middleware (HSTS, CSP, X-Frame-Options)
- [ ] Set `SESSION_SECURE_COOKIE=true` in production
- [ ] Set `SESSION_SAME_SITE=strict` in production

### A06:2021 — Vulnerable Components

| Check | Status | Notes |
|-------|--------|-------|
| PHP dependencies | OK | `composer audit` — 0 advisories |
| JS dependencies | OK | No known vulnerabilities |
| Framework version | OK | Laravel 12, PHP 8.4 (latest) |

### A07:2021 — Authentication Failures

| Check | Status | Notes |
|-------|--------|-------|
| Sanctum auth | OK | Cookie-based SPA auth + Bearer tokens |
| CSRF protection | OK | Sanctum CSRF middleware configured |
| Password policy | **TODO** | No password complexity rules beyond Laravel defaults |
| Session lifetime | OK | 120 min (acceptable for demo) |

### A08:2021 — Data Integrity Failures

| Check | Status | Notes |
|-------|--------|-------|
| Mass assignment | OK | All models use `$fillable` (whitelist approach) |
| Input validation | OK | All controllers validate input with `$request->validate()` |
| FormRequest classes | **TODO** | Should migrate to FormRequest classes for consistency |

### A09:2021 — Logging & Monitoring

| Check | Status | Notes |
|-------|--------|-------|
| Audit log model | OK | `AuditLog` model with `phi_accessed`, `action`, `resource_type` fields |
| Audit middleware | **TODO** | No middleware automatically logs PHI access |
| Log storage | OK | Append-only design, separate from app data |

**Action items:**
- [ ] Create `AuditMiddleware` that logs all PHI endpoint access
- [ ] Apply to patient, visit, document, chat route groups

### A10:2021 — SSRF

| Check | Status | Notes |
|-------|--------|-------|
| External API calls | OK | Only to known services (Anthropic, RxNorm, OpenAI) |
| User-controlled URLs | OK | No endpoints accept URLs from user input |

## Positive Findings

- All database queries use parameterized Eloquent ORM
- No `v-html` or `innerHTML` in Vue components
- Passwords hashed with bcrypt (`BCRYPT_ROUNDS=12`)
- All models use `$fillable` whitelist
- UUID primary keys (non-guessable)
- Sanctum CSRF middleware configured
- API keys stored in environment variables only
- Audit log model well-designed with PHI tracking fields

## Priority Fix List (for production)

| Priority | Issue | Effort |
|----------|-------|--------|
| P0 | Guard DemoController behind APP_ENV check | 10 min |
| P0 | Configure CORS with specific origins | 10 min |
| P0 | Add rate limiting to API routes | 15 min |
| P1 | Create authorization Policies | 2 hrs |
| P1 | Add audit logging middleware | 1 hr |
| P1 | Implement PHI encryption | 2 hrs |
| P2 | Add security headers middleware | 30 min |
| P2 | Set Sanctum token expiration | 10 min |
| P2 | Migrate to FormRequest classes | 2 hrs |
| P3 | Add prompt injection sanitization | 1 hr |

## Demo Disclaimer

This application is a **hackathon prototype**. The security architecture described in `SECURITY.md` represents the production design. Items marked **TODO** above are documented for transparency and will be addressed before any real-world deployment. **No real patient data is used.**
