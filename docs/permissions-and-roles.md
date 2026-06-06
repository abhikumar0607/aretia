# Aretia — Permissions & Roles

This document describes **who can do what** in the Aretia portal today. Access is controlled by a **fixed role** on each user account (`superadmin`, `admin`, `client`, `analyst`, `qa`, `fqa`). There is no custom per-user permission editor yet.

**Legend:** ✓ = allowed · — = not allowed · **Scope** = extra conditions in notes

| Role | Portal label | Typical user |
|------|----------------|--------------|
| **Client** | Client | Company customer (due diligence buyer) |
| **Analyst** | Analyst | Research / lead on assigned cases |
| **QA** | QA | Quality review on assigned cases |
| **FQA** | FQA | Final QA; delivers report to client |
| **Admin** | Admin | Internal operations staff |
| **Super Admin** | Super Admin | Platform owner / full control |

---

## Permission matrix

Columns: **Client** · **Analyst** · **QA** · **FQA** · **Admin** · **Super Admin**

### Portal & account

| Permission | Client | Analyst | QA | FQA | Admin | Super Admin |
|------------|:------:|:-------:|:--:|:---:|:-----:|:-----------:|
| Log in to portal | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Edit own profile (name, phone, photo, password) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| View notifications bell | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| View chat inbox bell | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Access **Permissions & Roles** settings page | — | — | — | — | — | ✓ *(UI: coming soon)* |

### Onboarding & companies

| Permission | Client | Analyst | QA | FQA | Admin | Super Admin |
|------------|:------:|:-------:|:--:|:---:|:-----:|:-----------:|
| Complete company onboarding (KYC upload, submit) | ✓ | — | — | — | — | — |
| Review onboarding queue | — | — | — | — | ✓ | ✓ |
| Approve / reject company onboarding | — | — | — | — | ✓ | ✓ |
| Download KYC documents (review) | — | — | — | — | ✓ | ✓ |
| View client users list | — | — | — | — | ✓ | ✓ |
| Suspend / restore whole company | — | — | — | — | ✓ | ✓ |

**Client scope:** Must finish onboarding before orders/cases/reports. Company must be **Active** for workspace features.

### Orders

| Permission | Client | Analyst | QA | FQA | Admin | Super Admin |
|------------|:------:|:-------:|:--:|:---:|:-----:|:-----------:|
| View orders | ✓ *company* | — | — | — | ✓ *all* | ✓ *all* |
| Create order | ✓ *company* | — | — | — | ✓ | ✓ |
| Bulk import orders | ✓ *company* | — | — | — | ✓ | ✓ |
| Upload order documents | ✓ *own company order* | — | — | — | ✓ | ✓ |
| Approve / reject pending order | — | — | — | — | ✓ | ✓ |
| Set / clear order due date | ✓ *own company* | — | — | — | ✓ | ✓ |

**Client scope:** All users under the **same company name** (equivalent company records) see and open the same orders, not only the user who created them.

### Cases

| Permission | Client | Analyst | QA | FQA | Admin | Super Admin |
|------------|:------:|:-------:|:--:|:---:|:-----:|:-----------:|
| View case list | ✓ *company* | ✓ *assigned* | ✓ *assigned* | ✓ *assigned* | ✓ *all* | ✓ *all* |
| Open case detail | ✓ *company* | ✓ *assigned* | ✓ *assigned* | ✓ *assigned* | ✓ *all* | ✓ *all* |
| Assign case team (Analyst, QA, FQA) | — | — | — | — | ✓ | ✓ |
| Link related cases | ✓ *company* | — | — | — | ✓ | ✓ |
| Upload case documents | ✓ *company case* | ✓ *assigned* | ✓ *assigned* | ✓ *assigned* | ✓ | ✓ |
| Preview / download case documents | ✓ *company* | ✓ *assigned* | ✓ *assigned* | ✓ *assigned* | ✓ | ✓ |

**Assigned** = user is on the case team (`case_analyst` pivot) or is the lead assignee.

**Client scope:** Same as orders — entire company shares access to cases for that company.

### Case workflow (stage changes)

Sequential pipeline: **Assigned → Research started → Research done → QA started → QA done → FQA started → Sent to client** (plus **Cancelled**).

| Permission | Client | Analyst | QA | FQA | Admin | Super Admin |
|------------|:------:|:-------:|:--:|:---:|:-----:|:-----------:|
| Move case to next stage (workflow rules) | — | ✓ *lane* | ✓ *lane* | ✓ *lane* | — | — |
| Move case to **any** stage (override) | — | — | — | — | ✓ | ✓ |

**Lane rules (employees only):**

| Role | May advance from → to |
|------|------------------------|
| **Analyst** | Assigned → Research started → Research done |
| **QA** | Research done → QA started → QA done *(unlocks after research done)* |
| **FQA** | QA done → FQA started → Sent to client *(unlocks after QA done)* |

Employees only see stages in their lane (and the next step once the previous team completes work). Admin / Super Admin are not bound by lanes.

### Reports (deliver to client)

| Permission | Client | Analyst | QA | FQA | Admin | Super Admin |
|------------|:------:|:-------:|:--:|:---:|:-----:|:-----------:|
| Upload / deliver case report | — | — | — | ✓ *FQA started* | ✓ | ✓ |
| View delivered reports list | ✓ *company* | — | — | — | — | — |
| Download delivered report | ✓ *company* | — | — | — | — | — |

**FQA rule:** Report delivery is only allowed when the case stage is **FQA started** (unless Admin / Super Admin uploads).

### Messages & chat

| Permission | Client | Analyst | QA | FQA | Admin | Super Admin |
|------------|:------:|:-------:|:--:|:---:|:-----:|:-----------:|
| Open case chat (after analyst assigned) | ✓ *company case* | ✓ *assigned* | ✓ *assigned* | ✓ *assigned* | ✓ | ✓ |
| Send case message | ✓ *company case* | ✓ *assigned* | ✓ *assigned* | ✓ *assigned* | ✓ | ✓ |
| Receive message notifications | ✓ *all company users on case* | ✓ *case team* | ✓ *case team* | ✓ *case team* | ✓ | ✓ |

**Company chat rule:** When staff message a case, **every active client user** on that company (same company name group) gets inbox + notification, not only the primary contact.

When a **client** messages, the **case team** (analysts on that case) is notified.

Chat requires an **assigned analyst** before clients can use it.

### People & access management

| Permission | Client | Analyst | QA | FQA | Admin | Super Admin |
|------------|:------:|:-------:|:--:|:---:|:-----:|:-----------:|
| View employees list | — | — | — | — | ✓ | ✓ |
| Create / edit employee (Analyst, QA, FQA) | — | — | — | — | ✓ | ✓ |
| Deactivate / activate employee | — | — | — | — | ✓ | ✓ |
| Deactivate / activate client user | — | — | — | — | ✓ | ✓ |
| Delete client user account | — | — | — | — | ✓ | ✓ |
| Deactivate / activate Super Admin | — | — | — | — | — | — |
| Manage another Super Admin | — | — | — | — | — | — |

**Admin** cannot manage Super Admin accounts. **Super Admin** can manage everyone except other Super Admins (self-service delete/deactivate blocked).

### Workflow configuration

| Permission | Client | Analyst | QA | FQA | Admin | Super Admin |
|------------|:------:|:-------:|:--:|:---:|:-----:|:-----------:|
| View workflow stages | — | — | — | — | ✓ | ✓ |
| Add / edit / deactivate workflow stages | — | — | — | — | ✓ | ✓ |
| Set stage owner (Analyst / QA / FQA / Any) | — | — | — | — | ✓ | ✓ |

### Audit & dashboards

| Permission | Client | Analyst | QA | FQA | Admin | Super Admin |
|------------|:------:|:-------:|:--:|:---:|:-----:|:-----------:|
| Operations dashboard (charts, filters) | ✓ *own stats* | ✓ *own workload* | ✓ *own workload* | ✓ *own workload* | ✓ *platform* | ✓ *platform* |
| View audit trail | — | — | — | — | ✓ | ✓ |

---

## How access is enforced

1. **Routes** — Each area is behind `role:…` middleware (see `routes/web.php`). Wrong role → **403 Access Denied**.
2. **Case / order scope** — Controllers check company or case-team membership (clients use **equivalent company** matching by company name).
3. **Workflow** — `App\Support\CaseWorkflow` limits which stage slugs each employee role may select.
4. **Account state** — Inactive users or suspended companies are blocked at login or middleware (`EnsureUserActive`, `EnsureCompanyActive`, `EnsureClientOnboarded`).

---

## Super Admin vs Admin

| Area | Admin | Super Admin |
|------|:-----:|:-----------:|
| Onboarding, orders, cases, workflow, audit, clients, employees | ✓ | ✓ |
| URL prefix | `/admin/…` | `/superadmin/…` |
| Permissions & Roles menu item | Hidden | Visible *(placeholder page)* |
| Manage Super Admin users | — | — *(protected)* |

Functionally, both roles have the same operational powers today; separation is mainly for **URL namespace** and future system settings.

---

## Planned: custom roles (UI placeholder)

The **Permissions & Roles** screen (`/superadmin/roles`) is reserved for Super Admin and currently shows **Coming soon**. Future work may include:

- Custom roles beyond the six built-in types  
- Per-module permission toggles  
- Assigning roles to users without changing code  

Until that ships, all access follows the matrix above.

---

## Quick reference — role purpose

| Role | One-line purpose |
|------|------------------|
| **Client** | Submit orders, track company cases, chat with team, download reports |
| **Analyst** | Run research stages on assigned cases |
| **QA** | Run QA stages after research is complete |
| **FQA** | Final review and deliver report to client |
| **Admin** | Run the business: onboarding, orders, cases, teams, workflow |
| **Super Admin** | Same as Admin plus future platform-level configuration |

---

*Last updated from codebase: fixed roles in `App\Enums\UserRole`, routes in `routes/web.php`, workflow in `App\Support\CaseWorkflow`, company scope in `App\Support\CompanyFilter`.*
