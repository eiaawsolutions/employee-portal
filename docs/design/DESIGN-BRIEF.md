# EIAAW Workforce — Design Brief

**Status:** retrospective. Session 11 close — consolidates design decisions that
shipped across Sessions 6-11. Future design work (Session 12's inline-handler
migration, Session 13+ new surfaces) must conform to this brief.

**Last updated:** 2026-04-24

---

## 1. Audience, use cases, brand tone

**Primary audience:** operations lead / founder / HR manager at a 50-500
person APAC mid-market company. Technical enough to evaluate "Postgres
Row-Level Security" as a positive differentiator. Outgrew spreadsheets + a
folksy SME HR tool; not yet ready for Workday / SAP SuccessFactors.

**Secondary audience:** CFO-adjacent finance head who wants HR + Accounting
on the same backbone without a second SaaS bill.

**Anti-audience:** the <25-person startup looking for a $2/seat pick, and
the 5,000+ enterprise needing a full HRIS with custom workflow engine.

**Use cases** (ordered by signup intent weight):
1. Replace a spreadsheet-based HR system with something auditable
2. Add payroll + EA-form automation on top of existing HR
3. Consolidate HR + IT Asset tracking + Finance into one platform
4. Stand up a formal HR system for a fast-growing team

**Brand personality / tone:**
- **Confident, comparative, direct.** Not folksy. Not startup-cute. Not
  corporate-cold. Think "Linear for HR" or "Stripe docs for payroll."
- Leads with technical depth (RLS, HMAC audit log, AI assistant architecture)
  because the ICP respects proof over promises.
- Short sentences. Concrete numbers. No adverb-heavy marketing copy.
- Serif italic accents (Instrument Serif) are the one approved flourish —
  used for emphasis in headlines and stat blocks, never body copy.

---

## 2. Product-type anchor (from ui-ux-pro-max's 161-type library)

**Primary type:** SaaS platform with dual surface area
- **Marketing apex** (ep.eiaawsolutions.com) — editorial landing + pricing +
  features + security + legal, optimized for buyer research and signup
  conversion
- **Tenant workspace** ({slug}.ep.eiaawsolutions.com) — functional product
  shell: dashboards, forms, tables, approval workflows

---

## 3. Taste profile (Track C enforcement)

**Selected baseline:** **high-end-visual-design** (the Apple/Linear-adjacent
aesthetic — cinematic spatial rhythm, haptic depth, never repeats the same
layout twice). Rationale: ICP pays for depth, and shipping-AI-slop-SaaS-design
would undercut the "AI-native platform" positioning. Premium visual language
is the entry fee, not the differentiator.

**Always-on baseline:** **design-taste-frontend** — metric-based rules for
DESIGN_VARIANCE, MOTION_INTENSITY, VISUAL_DENSITY. Enforced in every blade
file.

**Content-generation guard:** **full-output-enforcement** whenever producing
large design files. No placeholder patterns. No "// TODO rest of component".

**Deliberately not used:**
- `gpt-taste` — over-indexed on awwwards motion; would feel performative for
  a HR SaaS
- `minimalist-ui` — too document-style; strips the editorial personality
- `industrial-brutalist-ui` — wrong audience signal for a mid-market buyer

---

## 4. Color system

**Source of truth:** `public/brand/eiaaw-tokens.css` (locked in Session 1).
**Palette archetype (retrospective per color-psychology.md):** Trust & Reliability × Engineered Energy hybrid —
teal primary signals clarity + calm competence; warm bg (`#FAF7F2` / `#F3EDE0`)
avoids the cold-corporate-SaaS feel; ink (`#0F1A1D`) anchors seriousness.

| Token | Hex | Role |
|---|---|---|
| `--primary` | `#1FA896` | Teal. Brand accent, state-success, AI bubble glow. |
| `--primary-dark` | `#11766A` | Hover/active variant, eyebrow color, serif italic accents. |
| `--primary-tint` | `#E5F4F1` | Pills, AI bubble background, subtle accents. |
| `--bg` | `#FAF7F2` | Main canvas — warm off-white. |
| `--bg-warm` | `#F3EDE0` | Secondary canvas — slightly deeper warm. |
| `--surface` | `#FFFFFF` | Card / input surface. |
| `--ink` | `#0F1A1D` | Primary text + dark CTA buttons. |
| `--ink-2` | `#2A3438` | Secondary text. |
| `--mute` | `#6B7A7F` | Tertiary text, eyebrow-on-warm, captions. |
| `--line` | `#D9CFBC` | Input borders. |
| `--line-soft` | `#E8DFCC` | Card borders, subtle dividers. |
| `--danger` | `#B4412B` | Errors, destructive actions, negative trends. |
| `--warn` | `#C68A2E` | Pending, caution, threshold warnings. |
| `--success` | `#2F8C6E` | Positive state, completion. |

**Forbidden:**
- Adding new palette tokens without updating `eiaaw-tokens.css`
- Using raw hex in blade files (only `var(--*)`)
- Pastel-y gradient cards (the 2024 "AI startup landing" look)
- Heavy drop shadows (kills the editorial feel)

---

## 5. Typography

**Fonts:**
- `--sans: 'Inter'` — UI, body, headings under 32px
- `--serif: 'Instrument Serif'` — display accents, italic emphasis in
  headlines, stat block numbers
- `--mono: 'JetBrains Mono'` — eyebrows, tags, tabular numbers, code, metadata

**Type scale:**
- Display headline: `clamp(38px, 6vw, 76px)` with `letter-spacing: -0.03em`
- Section heading (h2): `clamp(30px, 4vw, 52px)` with `-0.025em`
- Subsection (h3): `clamp(22px, 2vw, 28px)` with `-0.02em`
- Body: 15-16px, 1.55-1.65 line-height
- Eyebrow: 11px mono, uppercase, 0.14em tracking, dash prefix
- Metadata / tag: 10.5-11px mono, uppercase, 0.12em tracking

**Forbidden:**
- 6+ line paragraphs in body (break up — editorial cadence)
- Headlines over 90px (overdone hero cliché)
- Serif italic for body (reserved for headline emphasis only)
- Weight > 600 (Inter 500 is the heaviest we use; 800 optional for logo only)

---

## 6. Motion contract

**Easing:** `--ease: cubic-bezier(.2,.7,.2,1)` (defined in tokens).
**Default duration:** 180-350ms for UI interactions; 600-900ms for
entry/reveal transitions.
**Reduced-motion:** `@media (prefers-reduced-motion: reduce)` zeroes every
`transition` and `animation`. Non-negotiable.

**Forbidden:**
- Parallax that fights scroll
- Auto-playing background video
- Bounce easing (violates the "confident / direct" tone)
- Motion longer than 1.2s (feels performative)

---

## 7. Component inventory

**Primitives (all in `eiaaw-tokens.css` or inline):**
- `.eiaaw-lockup` — brand lockup (image + stacked text)
- `.eiaaw-btn` — base button with `--primary` / `--outline` modifiers
- `.eyebrow` — mono caps with leading dash
- `.mk-pill` — capsule with optional dot
- `.mk-display` — display headline with serif italic `<em>` support
- `.mk-container` / `.mk-container--narrow` — layout containers

**Marketing-surface components (`layouts/marketing.blade.php` + per-page
styles):**
- `.mk-nav` — sticky blur nav
- `.mk-footer` — 5-column footer grid with trust strip
- `.ln-hero-stat` — serif italic stat block
- `.pr-tier` — pricing card with featured variant (ink-on-bg)
- `.ft-mod` / `.ft-mod--ai` — alternating feature module section

**App-shell components (`layouts/app.blade.php`):**
- `.trial-banner` — slim banner shown within 7d of trial end
- `.ai-fab` + `.ai-drawer` — floating AI assistant

**Forbidden:**
- Adding shadcn/ui components without mapping them to the token system
- Mixing Bootstrap modal patterns with the EIAAW design — we're migrating
  away from Bootstrap; don't add new Bootstrap JS dependencies

---

## 8. Accessibility floor

- **WCAG AA for all text** — verified against the warm-bg palette (ink on
  `--bg` is ~14.5:1; muted on `--bg` is ~4.9:1 which sits just above AA
  floor — flag if we drop below)
- **WCAG AAA for body text** on primary backgrounds
- **Keyboard navigation:** every interactive element reachable via Tab; focus
  rings visible (`box-shadow: 0 0 0 3px rgba(31,168,150,0.12)` on inputs,
  outline on buttons)
- **Reduced motion:** honored as above
- **aria-live** on dynamic regions (AI drawer messages, trial banner)
- **Color never the only signal** — status pills have both color + dot or
  icon; required-field errors have both color + text

---

## 9. Anti-slop guardrails (what we REFUSE to ship)

1. **No "AI gradient card" pattern** — the purple-to-pink-to-orange card
   that every 2024 AI startup landing page uses
2. **No default shadcn/ui without customisation** — every primitive gets
   EIAAW tokens applied; no "looks like every Next.js template"
3. **No emoji in marketing body copy** — can appear in docs and internal
   UI if the user requests, never in marketing
4. **No "powerful AI features" / "cutting-edge" / "seamless" / "unlock" /
   "transform" / "revolutionise"** — any of these is an instant rewrite
5. **No hero stat block that's 4+ big numbers** — 3 max, each with a
   concrete unit
6. **No "trusted by Google / Apple / X" brand strip unless we actually are**
7. **No stock photography** — illustrations or diagrams only, or nothing
8. **No CTAs that stack more than 2 buttons** — primary + secondary max
9. **No FAQ section with <5 Qs or >12 Qs** — the sweet spot is 6-10 Qs
10. **No "join the waitlist" CTA when we actually accept signups** —
    pretend-scarcity is not on brand

---

## 10. Sequencing reference (for future design work)

Per the CLAUDE.md master playbook, when building a new surface:

1. Read `references/color-psychology.md` to *explain the fit* of the locked
   palette to the new surface (the archetype is fixed; the fit needs naming)
2. Run `ui-ux-pro-max` to pick concrete style / layout / type rules within
   the locked palette
3. Consult `ckm-brand` if brand-voice ambiguity (usually not — the tone is
   locked here)
4. `ckm-design-system` for any new tokens or component specs (always add
   to `eiaaw-tokens.css`, never inline)
5. Pick a Track C taste-profile for this specific surface — default is
   `high-end-visual-design`; use `minimalist-ui` only for document-heavy
   internal dashboards
6. `design-taste-frontend` runs as the always-on baseline check
7. `impeccable craft` for the surface build
8. `ckm-ui-styling` to implement in the existing blade + token stack
9. `full-output-enforcement` on any file >300 lines

Skip this whole chain only when editing existing surfaces (use
`redesign-existing-projects` skill instead).

---

## 11. Open assumptions

- The Instrument Serif font license permits commercial use at ep.eiaawsolutions.com
  (verify before launch — it's an SIL OFL font so should be fine)
- Inter license: SIL OFL, confirmed commercial-safe
- JetBrains Mono license: OFL, confirmed commercial-safe
- The warm-bg palette works as well on dark-mode OS tenants as on light-mode
  (we don't ship a dark mode; document this as a Session 13+ deferral)
- The ICP's technical comfort with terms like "RLS" and "HMAC" holds in
  APAC; we're taking the positioning bet that it does — track signup-page
  scroll depth post-launch to validate
