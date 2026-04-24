#!/usr/bin/env python3
"""
Session 4 — Apply BelongsToTenant trait + tenant_id fillable to all remaining
Eloquent models that need tenancy.

For each PHP model file in the SKIP_LIST-EXCLUDED list:
  1. Add `use App\Models\Concerns\BelongsToTenant;` after the existing
     `use Illuminate\Database\Eloquent\Model;` import (idempotent — skips if already there)
  2. Add `BelongsToTenant` to the `use ... ;` statement at the top of the class
     (handles `use HasFactory;`, `use HasFactory, Notifiable;`, plain `use HasFactory;`,
     and the no-trait case)
  3. Prepend `'tenant_id'` to the `protected $fillable = [...]` array

Run-only-once: idempotent on re-run (won't double-add).

Usage:
  cd database/schema
  python apply_belongs_to_tenant.py
"""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).parent.parent.parent / "app" / "Models"

# Already done in Sessions 2 & 3, OR not Eloquent models, OR intentionally not-tenant-scoped.
SKIP = {
    # Done in Session 2 & 3
    "User.php",
    "Employee.php",
    "PersonalDetail.php",
    "WorkDetail.php",
    "EmployeeSpouseDetail.php",
    "EmployeeEmergencyContact.php",
    "EmployeeEducationHistory.php",
    "Tenant.php",
    "AiConversation.php",
    "AiUsageDaily.php",
}


def edit_file(path: Path) -> str | None:
    """Return a one-line summary of what was changed, or None if already applied."""
    text = path.read_text(encoding="utf-8")
    original = text

    # 1. Add the use-import for BelongsToTenant
    if "BelongsToTenant" not in text:
        # Try to insert after the Eloquent\Model import
        if "use Illuminate\\Database\\Eloquent\\Model;" in text:
            text = text.replace(
                "use Illuminate\\Database\\Eloquent\\Model;",
                "use App\\Models\\Concerns\\BelongsToTenant;\nuse Illuminate\\Database\\Eloquent\\Model;",
                1,
            )
        elif "use Illuminate\\Foundation\\Auth\\User as Authenticatable;" in text:
            text = text.replace(
                "use Illuminate\\Foundation\\Auth\\User as Authenticatable;",
                "use App\\Models\\Concerns\\BelongsToTenant;\nuse Illuminate\\Foundation\\Auth\\User as Authenticatable;",
                1,
            )
        else:
            return f"WARNING: {path.name} — couldn't find an anchor to add use BelongsToTenant"

    # 2. Add BelongsToTenant to the trait `use ...;` line inside the class
    if "BelongsToTenant" not in re.findall(r"use\s+[^;]+;", text)[1] if False else True:
        # Find the first "use ...;" inside a class body (not a top-level import).
        # Pattern: a `use` statement appearing AFTER `class X ... {`
        class_match = re.search(r"class\s+\w+(?:\s+extends\s+\w+)?\s*\{", text)
        if not class_match:
            return f"ERROR: {path.name} — couldn't find class declaration"
        body_start = class_match.end()
        body = text[body_start:]
        # Find the first `use ...;` in the body (could be `use HasFactory;` etc.)
        in_class_use = re.search(r"^(\s*)use\s+([^;]+);", body, re.MULTILINE)
        if in_class_use:
            indent = in_class_use.group(1)
            existing = in_class_use.group(2).strip()
            if "BelongsToTenant" in existing:
                pass  # already added (defensive)
            else:
                new_use = f"{indent}use {existing}, BelongsToTenant;"
                text = text[:body_start] + body[: in_class_use.start()] + new_use + body[in_class_use.end() :]
        else:
            # No existing trait `use` — insert one right after the opening brace
            insertion = f"\n    use BelongsToTenant;\n"
            text = text[:body_start] + insertion + text[body_start:]

    # 3. Prepend 'tenant_id' to the $fillable array
    if "'tenant_id'" not in text:
        # Match `protected $fillable = [` and add 'tenant_id' as the first element.
        # Handle both single-line `[ 'a', 'b' ]` and multi-line variants.
        fillable_match = re.search(
            r"(protected\s+\$fillable\s*=\s*\[)(\s*)",
            text,
        )
        if fillable_match:
            insert = fillable_match.group(1) + fillable_match.group(2) + "'tenant_id', "
            text = text[: fillable_match.start()] + insert + text[fillable_match.end() :]
        else:
            return f"WARNING: {path.name} — no $fillable found, skipping tenant_id insert"

    if text == original:
        return None  # already applied

    path.write_text(text, encoding="utf-8")
    return f"updated {path.relative_to(ROOT.parent.parent)}"


def main() -> None:
    targets = []
    for p in sorted(ROOT.glob("**/*.php")):
        if p.name in SKIP:
            continue
        # Skip non-model directories
        if p.parent.name in {"Concerns", "Scopes"}:
            continue
        targets.append(p)

    print(f"Processing {len(targets)} model files...")
    updated = 0
    for p in targets:
        result = edit_file(p)
        if result:
            print(f"  {result}")
            if result.startswith("updated"):
                updated += 1
        # silent on no-op (already applied)

    print(f"\nDone. {updated} files modified.")


if __name__ == "__main__":
    main()
