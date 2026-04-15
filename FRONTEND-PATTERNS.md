# Frontend Patterns & Interaction Reference

> **Purpose:** Living reference for how each page's JavaScript works.
> Consult before modifying any view to avoid breaking existing functionality.
> Last updated: 2026-04-15

---

## CSP Policy (SecurityHeaders middleware)

```
script-src 'self' 'nonce-{$nonce}' 'unsafe-hashes' https://cdn.jsdelivr.net
```

**Rules:**
- `<script nonce="{{ $cspNonce ?? '' }}">` blocks execute normally
- `addEventListener` inside a nonce'd script block is always safe
- Inline `onclick="..."` / `onchange="..."` attributes are **blocked** by CSP
- Dynamically generated HTML with `onclick` in template literals is **blocked**
- When adding new event handlers, always use `addEventListener` or event delegation

**Safe pattern (use this):**
```html
<button type="button" id="myBtn">Click</button>
<script nonce="{{ $cspNonce ?? '' }}">
document.getElementById('myBtn').addEventListener('click', myFunction);
</script>
```

**Unsafe pattern (avoid):**
```html
<button onclick="myFunction()">Click</button>  <!-- blocked by CSP -->
```

**For dynamically created buttons (use event delegation or createElement):**
```javascript
// GOOD: createElement + addEventListener
const btn = document.createElement('button');
btn.addEventListener('click', function() { removeItem(i); });
row.appendChild(btn);

// GOOD: Event delegation
document.getElementById('list').addEventListener('click', function(e) {
    const rm = e.target.closest('[data-remove]');
    if (rm) removeItem(parseInt(rm.dataset.remove));
});

// BAD: inline handler in template literal
list.innerHTML += `<button onclick="removeItem(${i})">X</button>`;
```

---

## External Libraries

| Library | CDN | Used In |
|---------|-----|---------|
| Bootstrap 5.3.2 | jsdelivr | layouts/app.blade.php (global) |
| Bootstrap Icons 1.11.3 | jsdelivr | layouts/app.blade.php (global) |
| Select2 4.1.0-rc.0 | jsdelivr | hr/onboarding/page.blade.php |
| Chart.js 4.4.7 | jsdelivr | accounting/dashboard, executive-dashboard |

---

## Common JS Patterns

### 1. HTML Escaping

Two functions exist — use whichever is already in scope:
```javascript
function obEsc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escHtml(s) { /* identical */ }
```
Always escape user-entered values before inserting into innerHTML.

### 2. DataTransfer API (file inputs)

Used to programmatically set `input.files` when collecting files from an in-memory array:
```javascript
const dt = new DataTransfer();
filesArray.forEach(f => dt.items.add(f));
const inp = document.createElement('input');
inp.type = 'file'; inp.name = 'field_name[]'; inp.multiple = true;
inp.style.display = 'none';
inp.files = dt.files;
container.appendChild(inp);
```
**Files:** onboarding/page.blade.php, user/profile.blade.php, employees/edit.blade.php

### 3. Dynamic Form Arrays (hidden inputs)

Sections with "Add to List" use in-memory JS arrays synced to hidden inputs:
```javascript
// In-memory store
let entries = [];

// After push, render hidden inputs
entries.forEach((e, i) => {
    h.innerHTML += `<input type="hidden" name="items[${i}][name]" value="${escHtml(e.name)}">`;
});
```

**Naming conventions:**
- Education: `edu_qualification[]`, `edu_institution[]`, `edu_year[]`, `edu_certificate[]`
- Spouse: `spouses[i][name]`, `spouses[i][nric_no]`, `spouses[i][tel_no]`, etc.
- Emergency: `emergency[order][name]`, `emergency[order][tel_no]`, `emergency[order][relationship]`
- Accounting line items: `items[i][description]`, `lines[i][account_id]`

### 4. Bootstrap Modal Re-open on Validation Error

```javascript
@if($errors->any())
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('modalId')).show();
});
@endif
```

### 5. Conditional Section Toggle (Marital Status -> Spouse)

Disables/enables all inputs inside a container based on a dropdown value:
```javascript
function toggleSection(val) {
    const section = document.getElementById('sectionId');
    const isActive = val === 'married';
    section.querySelectorAll('input, select, textarea, button').forEach(el => {
        el.disabled = !isActive;
    });
    section.style.opacity = isActive ? '1' : '0.4';
}
```
**Watch out:** This disables ALL buttons inside the section, including "Add to List" buttons. If you add a new button inside a toggle-able section, it will be disabled when the section is inactive.

### 6. Company -> Office Location Autofill

Company `<option>` elements carry `data-address` attribute. On change and on page load, the address is copied to the office location input:
```javascript
function autofillOfficeLocation(selectEl, targetId) {
    const selected = selectEl.options[selectEl.selectedIndex];
    const target = document.getElementById(targetId);
    if (!target || !selected || !selected.value) return;
    target.value = selected.dataset.address || '-';
}
```

### 7. Event Delegation for Dynamic Rows

Used in accounting forms for line items:
```javascript
tbody.addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        e.target.closest('tr').remove();
        recalc();
    }
});
```

---

## Page-by-Page Reference

### HR Module

#### `hr/onboarding/page.blade.php` — New Onboarding (modal form)
- **Modal:** `#addOnboardingModal` (line 264), re-opens on validation errors
- **Sections F/G/H:** Add-to-list with in-memory arrays (`obEduEntries`, `obSpouseEntries`, `obEcEntries`)
- **Add buttons:** `#obAddEduBtn`, `#obAddSpouseBtn`, `#obAddEcBtn` — bound via addEventListener
- **Remove buttons:** Created via `document.createElement('button')` + addEventListener (CSP-safe)
- **Company select:** `#addOBCompanySelect` — triggers `autofillOfficeLocation` and `filterManagersByCompany`
- **Manager select:** `#reporting_manager` — triggers `fetchManagerEmail`
- **Marital status:** `#obMaritalStatus` — `onchange` toggles spouse section (disables all inputs)
- **Asset cards:** `onclick="toggleAsset('...')"` — inline handler (legacy, still works with unsafe-hashes)
- **DOMContentLoaded (4):** office location sync, Google ID sync, modal re-open, spouse section toggle
- **Select2:** Used for searchable dropdowns
- **File handling:** Certificate files via DataTransfer API into `edu_certificate[]`

#### `hr/onboarding/edit.blade.php` — Edit Onboarding
- Similar to page.blade.php but as a full-page form (no modal)
- Same section F/G/H patterns
- Company select: `#editOBCompanySelect` — DOMContentLoaded syncs office location

#### `hr/employees/edit.blade.php` — Employee Edit
- **Sections F/G/H:** Card-based layout with inline edit/delete for existing records
- **Education:** Existing entries have Edit/Close toggle + inline fields. New entries via `#empAddEduBtn`
- **Spouse:** Existing entries with `empToggleSpouseEdit()`. New entries via `#empAddSpouseBtn`
- **Emergency:** Fixed 2-slot form (no add/remove)
- **Delete tracking:** `edu_delete_ids`, `del_spouse_ids` hidden inputs (comma-separated IDs)
- **Certificate management:** Keep/remove existing + add new (max 5 per entry) via DataTransfer
- **Company select:** `#empCompanySelect` — DOMContentLoaded syncs office location

#### `hr/employees/index.blade.php` — Employee Listing
- Filters, search, pagination
- Minimal JS (form submission)

#### `hr/offboarding/index.blade.php` — Offboarding List
- Overview widget included
- Filter/search forms

#### `hr/payroll/` — Payroll pages
- Upload, calculation, payslip generation
- File upload handlers

#### `hr/leave/` — Leave management
- Approval workflows
- Modal-based interactions

#### `hr/claims/` — Expense claims
- Receipt upload, approval workflow

### IT Module

#### `it/onboarding.blade.php` — IT Onboarding View
- Overview widget included (read-only listing)
- PIC assignment modals
- Minimal JS

#### `it/offboarding.blade.php` — IT Offboarding View
- Overview widget included (read-only listing)
- PIC assignment

#### `it/assets/page.blade.php` — Asset Inventory
- **Complex JS:** Search, filter, photo upload, condition/status sync
- **File uploads:** Async FileReader for photo preview
- **Inline handlers:** `onchange` on filter dropdowns (legacy)
- **addEventListener:** Used for search, file input, photo management

#### `it/assets/edit.blade.php` — Asset Edit
- Similar patterns to page.blade.php

### User Module

#### `user/profile.blade.php` — Self-Service Profile
- **Most complex user-facing form** (~1400 lines)
- **NRIC files:** Add/remove with DataTransfer API (`profileNricNewFiles[]`)
- **Education:** Add/edit/remove with inline edit toggle
- **Spouse:** Add/edit/remove cards
- **Inline handlers:** 15+ (legacy, CSP-vulnerable)
- **DOMContentLoaded:** Bank toggle, spouse toggle

#### `user/account.blade.php` — Account Settings
- Profile photo upload
- Password change

#### `user/claims/index.blade.php` — Employee Claims
- Fetch-based AJAX for claim operations

#### `user/leave/index.blade.php` — Leave Application
- Leave request form
- Calendar view

### Superadmin Module

#### `superadmin/role-management.blade.php` — Roles & Permissions
- Select2 for role assignment
- Dynamic permission checkboxes

#### `superadmin/system-overview.blade.php` — System Dashboard
- Fetch-based metrics loading

### Accounting Module

#### `accounting/receivables/invoice-form.blade.php` — Sales Invoice
- **CSP-compliant:** Uses addEventListener + event delegation
- Dynamic line items with real-time calculations
- Tax calculation via `data-rate` attributes

#### `accounting/journal-entries/form.blade.php` — Journal Entries
- **CSP-compliant:** createElement + event delegation
- Debit/credit balance validation

#### `accounting/dashboard.blade.php` — Accounting Dashboard
- Chart.js bar chart
- `@json()` data injection

#### `accounting/ai/chatbot.blade.php` — AI Assistant
- Fetch-based chat interface

### Partials

#### `partials/onboarding-overview-widget.blade.php`
- YTD cards with company filter
- `@push('scripts')` for filter JS

#### `partials/offboarding-overview-widget.blade.php`
- Same pattern as onboarding widget

#### `partials/dashboard-widgets-style.blade.php`
- CSS only, no JS

#### `partials/leave-modal.blade.php`
- Bootstrap modal for leave requests

### Auth

#### `auth/set-password.blade.php`
- Password strength checker (inline handlers — legacy)
- Real-time validation feedback

---

## CSP Migration Status

| Status | Files |
|--------|-------|
| **Compliant** | accounting/invoice-form, journal-entries/form, bill-form, budgets/form, banking/reconciliation |
| **Partially fixed** | hr/onboarding/page (Add to List buttons fixed), hr/employees/edit (Add buttons fixed) |
| **Needs migration** | user/profile, auth/set-password, it/assets/page, accounting/dashboard, hr/onboarding/edit |

**Priority for migration:**
1. user/profile.blade.php (15+ inline handlers, most user-facing)
2. hr/onboarding/edit.blade.php (HR daily use)
3. it/assets/page.blade.php (IT daily use)
4. auth/set-password.blade.php (affects all new users)
5. accounting/dashboard.blade.php (single handler, low risk)

---

## Testing Checklist (Manual)

When modifying any page with JS interactions, verify:

- [ ] "Add to List" buttons respond (education, spouse, emergency contacts)
- [ ] Remove/delete buttons on dynamically added entries work
- [ ] Form submits with correct data (check hidden inputs in browser DevTools)
- [ ] File uploads attach correctly (DataTransfer API)
- [ ] Conditional toggles work (marital status -> spouse section)
- [ ] Company selection auto-fills office location
- [ ] Modal re-opens on validation errors
- [ ] No console errors (F12 -> Console tab)
- [ ] CSP violations check (F12 -> Console, look for "Refused to execute inline event handler")
