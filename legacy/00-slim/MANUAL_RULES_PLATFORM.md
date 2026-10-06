# Manual Rules Platform — Design Proposal

> Status: **v1 implemented** — see `prop_manual.php` + `prop_manual_api.php`  
> Frozen decisions from §9 are below.  
> Goal: replace weak auto-mined rules with a **human-curated** rule builder that:
> 1) defines a shared pattern vocabulary on the prop string  
> 2) lets you filter races by symptoms  
> 3) turns checked rows + FP outcomes into saved rules  
> 4) backtests each manual rule’s hit rate  

Related data: `racepropresult` (history). Related UI today: `prop.php` (auto train / predict).

---

## 1. Core idea (one sentence)

For each race, build a **fixed prop string** (order by `1/prewin` ASC, tie → `prepla` ASC). Annotate that string with a **band vocabulary** (2x / 32x / B2 / pos21 / w2 …). Manual rules are **IF symptom → THEN pick position(s)**. You author them by selecting filtered race rows whose final positions become the pick targets.

---

## 2. Prop string & band vocabulary

### 2.1 Prop string

```
race horses → sort by (1/prewin ASC, prepla ASC) → [p0, p1, …, pN-1]
```

Index `0` = **top** (favourite side of string).  
Index `N-1` = **bottom**.

Venue buckets (same as today):

| Bucket | Values |
|--------|--------|
| ST | `ST` |
| HV | `HV` |
| Sx | `S1`…`S9` |

### 2.2 Bands (tens groups)

| Band key | Value range | Pair label | Triple+ label | Lone (no same value) |
|----------|-------------|------------|---------------|----------------------|
| `1` | 10–19 | `1x` | `31x` (count=3), `41x`… | `w1` |
| `2` | 20–29 | `2x` | `32x`, `42x`… | `w2` |
| `3` | 30–39 | `3x` | `33x`, `43x`… | `w3` |
| `4` | 40–49 | `4x` | `34x`, `44x`… | `w4` |
| `5` | 50–59 | `5x` | `35x`, `45x`… | `w5` |
| `6` | 60–120 | `6x` | `36x`, `46x`… | `w6` |

**Naming convention (proposed, please confirm):**

- `{n}x` = a **group** of identical values in band `n` with count = 2  
- `{k}{n}x` = same band, count = `k` (e.g. three 21s → `32x`)  
- `w{n}` = a value in band `n` that appears **once** (no duplicate)  
- `B{n}` = **biggest** value in band `n` (among horses in that band)  
- `s{n}` = **smallest** value in band `n`  
- `c{n}x` = count of distinct **pair** groups (`2`-count) in band `n`  
- `c3{n}x` = count of distinct **triple+** groups in band `n` (optional alias)

**Small / big (within a decade):**

```
small = value % 10 <= 4   e.g. 20–24, 30–34
big   = value % 10 > 4    e.g. 25–29, 35–39
```

So `24` = small, `25` = big. Applies to every band the same way.

### 2.3 Group & position tokens (example: band 2)

Given string with duplicates of the same exact prop value:

| Token | Meaning |
|-------|---------|
| `2x` | a pair group (exact value appears twice) |
| `32x` | a triple group (same value ×3); `42x` = ×4 |
| `c2x` | how many distinct `2x` groups in band 2 |
| `c32x` | how many distinct `32x` (and higher) groups — or split `c32x` / `c42x` |
| `pos21` | 1st occurrence (left→right / top→bottom) of a chosen `2x` group |
| `pos22` | 2nd occurrence of that `2x` |
| `pos321` / `pos322` / `pos323` | 1st / 2nd / 3rd of a `32x` group |
| `pos41` … | same idea for `4x` / `41x` etc. |
| `top` | index 0 of whole string |
| `bottom` | last index of whole string |
| `B2` | biggest value in 20–29 |
| `s2` | smallest value in 20–29 |
| `w2` | any singleton in 20–29 |

**Ambiguity when multiple groups exist** (must decide):

Example: two different `2x` groups (21,21 and 22,22). Which is `pos21`? yes, so there need to add a eg. big 2x's pos 21

**Recommendation:** always qualify by **size** and/or **order**:  

| Qualifier | Meaning |
|-----------|---------|
| `small 2x` / `big 2x` | group value is small/big decade digit | << yes, thats correct>>
| `2x#1`, `2x#2` | groups ordered by first appearance left→right | << but we need to identify its small or big>>
| `B2` / `s2` | pick by value extremum, not by group index |

For rule authoring UI, prefer **`small/big` + appearance order** because that matches your spoken rules (“big 2x pick pos21”). << good>>

Same scheme for bands 1,3,4,5,6 → `pos11`, `pos311`, `pos41`, `w5`, `B3`, …

### 2.4 Worked example (your `c2x` case)

```
… 21, 22, 21, 22, 22, 23, 23 …
```

- value 21 appears 2× → one `2x`  
- value 22 appears 3× → one `32x`  
- value 23 appears 2× → one `2x`  
→ `c2x = 2`, `c32x = 1` (or `32x=1`)

Positions (0-based):

| Token | Position of |
|-------|-------------|
| `pos21` of first `2x` (21) | first 21 |
| `pos22` of that group | second 21 |
| `pos321` of the `32x` (22) | first 22 |
| … | … |

---

## 3. Manual rule language (examples → structured form)

Your spoken Sx rules become **condition → picks**:

| # | Natural language | Structured (draft) |
|---|------------------|--------------------|
| 1 | both small 3x inside big 3x → pick bottom small 3x | `IF venue=Sx AND small_3x nested_in big_3x THEN pick bottom(small_3x)` |
| 2 | ≥2 of 32x → pick pos321−1 | `IF c32x≥2 THEN pick relative(pos321, -1)` |
| 3 | only 1 small 3x, next to pos32 is w2 → pick w2 | `IF c_small_3x=1 AND adjacent(pos32, w2) THEN pick that w2` |
| 4 | one 31x: pos311 next w2 → pick 311; pos312 next w1 → pick 312 | two branches / OR arms |
| 5 | only 32x → pick pos321 | `IF only_pattern=32x THEN pick pos321` |
| 6 | only 33x → pick pos332 | `IF only_pattern=33x THEN pick pos332` |
| 7 | contain 4x → pick pos41 | `IF has_4x THEN pick pos41` |
| 8 | c2x=2: big 2x→pos21, small 2x→pos22 | multi-pick rule |
| 9 | one 3x + one 2x + two 32x → pos21, pos32, big pos322 | multi-pick |
| 10 | one 2x → pick w3 inside pos21..pos22 | range pick |
| 11 | one small 3x → pick pos32 | |
| 12 | only one w5 not top, and w2≠B2 → pick w2 | |
| 13 | only one w5 not top, and w3≠B3 → pick w3 | |

Each successful pick awards **+1 mark** (horse marked as selected). Place hit = `finalPosition ≤ 3` (or ≤4 if you want FP4 border for learning — confirm).

---

## 4. Recommended DB structure

Keep **history** in `racepropresult`. Add tables only for vocabulary cache + manual rules + optional evidence.

### 4.1 `prop_race_fingerprint` (optional cache, recomputable)

One row per race. Avoid re-parsing every UI load.

```sql
CREATE TABLE prop_race_fingerprint (
  id            BIGINT PRIMARY KEY AUTO_INCREMENT,
  racingdate    DATE NOT NULL,
  venue         VARCHAR(10) NOT NULL,   -- ST / HV / S1..S9
  venue_bucket  ENUM('ST','HV','Sx') NOT NULL,
  raceno        INT NOT NULL,
  distance      INT NULL,
  go_ch         VARCHAR(50) NULL,
  n_horses      TINYINT NOT NULL,
  prop_string   VARCHAR(255) NOT NULL,  -- e.g. "59,40,33,31,28,28,26,25,24,24,24,21,19,19"
  -- band summary JSON (see §5)
  bands_json    JSON NOT NULL,
  UNIQUE KEY uk_race (racingdate, venue, raceno)
);
```

`bands_json` example:

```json
{
  "2": {
    "groups": [
      {"value": 21, "count": 2, "label": "2x", "size": "small",
       "pos": [2, 5], "tokens": {"pos21": 2, "pos22": 5}},
      {"value": 28, "count": 2, "label": "2x", "size": "big",
       "pos": [0, 1], "tokens": {"pos21": 0, "pos22": 1}}
    ],
    "c2x": 2, "c32x": 0,
    "B2": {"value": 28, "pos": 0},
    "s2": {"value": 21, "pos": 2},
    "w2": [{"value": 26, "pos": 4}]
  },
  "3": { "...": "..." }
}
```

### 4.2 `prop_manual_rule` — the rule head

```sql
CREATE TABLE prop_manual_rule (
  id            BIGINT PRIMARY KEY AUTO_INCREMENT,
  code          VARCHAR(32) NOT NULL UNIQUE,  -- e.g. Sx-R012
  name          VARCHAR(120) NOT NULL,        -- short title
  venue_scope   ENUM('ST','HV','Sx','ALL') NOT NULL DEFAULT 'Sx',
  priority      INT NOT NULL DEFAULT 100,     -- lower = apply first (optional)
  enabled       TINYINT(1) NOT NULL DEFAULT 1,
  mark_weight   DECIMAL(4,2) NOT NULL DEFAULT 1.00,  -- usually +1
  -- machine-executable condition + pick (see §5)
  condition_json JSON NOT NULL,
  pick_json      JSON NOT NULL,
  -- human text (your spoken form)
  note_zh       TEXT NULL,
  created_by    VARCHAR(40) NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
);
```

### 4.3 `prop_manual_rule_evidence` — rows you checked when authoring

Links a rule to the race samples that inspired / validate it.

```sql
CREATE TABLE prop_manual_rule_evidence (
  id            BIGINT PRIMARY KEY AUTO_INCREMENT,
  rule_id       BIGINT NOT NULL,
  racingdate    DATE NOT NULL,
  venue         VARCHAR(10) NOT NULL,
  raceno        INT NOT NULL,
  -- snapshot of what was true when you checked the row
  prop_string   VARCHAR(255) NOT NULL,
  -- which positions were the “answer” from FP borders
  result_json   JSON NOT NULL,
  -- e.g. [{"pos":3,"prop":24,"fp":1},{"pos":7,"prop":28,"fp":2}]
  UNIQUE KEY uk_rule_race (rule_id, racingdate, venue, raceno),
  KEY idx_race (racingdate, venue, raceno)
);
```

### 4.4 `prop_manual_rule_stat` — backtest cache (optional)

```sql
CREATE TABLE prop_manual_rule_stat (
  rule_id       BIGINT NOT NULL,
  venue_scope   VARCHAR(10) NOT NULL,
  date_from     DATE NOT NULL,
  date_to       DATE NOT NULL,
  races_matched INT NOT NULL,
  picks_total   INT NOT NULL,
  picks_placed  INT NOT NULL,   -- FP≤3
  race_hit      INT NOT NULL,    -- ≥1 pick placed in race
  hit_rate      DECIMAL(6,4) NOT NULL,
  computed_at   DATETIME NOT NULL,
  PRIMARY KEY (rule_id, venue_scope, date_from, date_to)
);
```

**No need to copy horse rows** — always join live `racepropresult` for FP.

---

## 5. Best format to save a rule (`condition_json` + `pick_json`)

Use **JSON AST**, not free text, so the engine can evaluate and the UI can edit.

### 5.1 Condition (symptom filter)

```json
{
  "all": [
    { "venue_bucket": "Sx" },
    { "band": "3", "op": "c_label", "label": "3x", "size": "small", "eq": 1 },
    { "band": "2", "op": "adjacent", "from": "pos32", "to": "w2" }
  ]
}
```

Supported ops (v1 proposal):

| op | Meaning |
|----|---------|
| `has_label` | band has ≥1 group with label |
| `c_label` / `c2x` | count of groups equals / ≥ / ≤ |
| `only_label` | string’s only multi-group pattern is X |
| `size` | small/big on a referenced group |
| `nested_in` | all positions of A lie between top/bottom of B |
| `adjacent` | |posA − posB| = 1 |
| `not_top` | token position ≠ 0 |
| `neq_token` | e.g. w2 is not B2 |
| `contains_value` | raw prop value in string (escape hatch) |

### 5.2 Pick (result)

```json
{
  "marks": [
    { "ref": "bottom", "of": { "band": "3", "label": "3x", "size": "small" } },
    { "ref": "pos321", "of": { "band": "2", "label": "32x", "ord": 1 }, "offset": -1 },
    { "ref": "w3", "between": ["pos21", "pos22"], "of_group": { "band": "2", "label": "2x", "ord": 1 } }
  ]
}
```

Evaluation returns **0..N horse indices** → each gets `+mark_weight`.

### 5.3 Why JSON AST over SQL / regex

| Approach | Pros | Cons |
|----------|------|------|
| Free text / regex | fast to type | untestable, ambiguous |
| SQL WHERE on flattened columns | familiar | explosion of columns; hard for nested/adjacent |
| **JSON AST + evaluator** | editable UI, versionable, backtestable | need small interpreter (~200–400 lines) |

**Recommendation:** JSON AST now; optional later “compile to PHP closure cache”.

---

## 6. Symptom selector UI (new page, e.g. `prop_manual.php`)

### 6.1 Filter bar

| Control | Options |
|---------|---------|
| Venue | ST / HV / Sx / All |
| Date from–to | dates |
| Band focus | 1 / 2 / 3 / 4 / 5 / 6 (or multi) |
| Pattern chips | `has 2x`, `c2x=2`, `has 32x`, `only 33x`, `has w5`, `small 3x`, … |
| Relation | nested / adjacent / not top |
| FP interest | has FP1–3 in band / anywhere |

Selectors should emit the same JSON as `condition_json` so **filter = draft rule condition**.

### 6.2 Race list (your visual rules)

Each row = one race:

1. **Horizontal prop string** (left = top)  
   - **Yellow** fill: `?x` pair groups (`2x`,`3x`,`4x`…)  
   - **Light green** fill: `3?x` triples (`32x`,`33x`…)  
   - Border by `finalPosition`:  
     - red = 1, blue = 2, green = 3, brown = 4  
2. **Count** of currently enabled manual rules that fire on this string  
3. **Checkbox** per row  
4. When checked + active symptom filter:  
   - treat filter as **rule condition**  
   - map bordered FP cells → **candidate picks** (auto-fill `pick_json` from selected tokens / positions)  
5. **Confirm** → insert `prop_manual_rule` + `prop_manual_rule_evidence`

### 6.3 Suggested pick-authoring flow

```
Filter races
  → scan yellow/green + FP borders
  → check 5–20 similar races
  → UI proposes: "common tokens among FP≤3 cells" (pos321, w2, …)
  → you confirm / edit pick refs
  → Save rule (condition from filter, picks from proposal)
```

This is how the platform “learns from you” without auto-mining lift.

---

## 7. Making it “alive” (workflow)

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│ Fingerprint │────▶│ Manual Builder│────▶│ Rule Store  │
│ (recompute) │     │ filter+check │     │ condition+  │
└─────────────┘     └──────────────┘     │ pick JSON   │
                                         └──────┬──────┘
                                                │
                    ┌──────────────┐            │
                    │ Backtest tab │◀───────────┘
                    │ per-rule hit │
                    └──────┬───────┘
                           │ promote / disable
                           ▼
                    ┌──────────────┐
                    │ Live predict │  mark horses (+1 per firing rule)
                    │ (later hook) │
                    └──────────────┘
```

**Lifecycle for each rule:**

1. **Draft** — built from checkboxes, `enabled=0`  
2. **Backtest** — see match count + place hit rate  
3. **Enable** — if hit rate ≥ your bar (e.g. race-hit ≥ 55%)  
4. **Monitor** — re-run backtest monthly; disable drifting rules  
5. **Revise** — clone rule, tighten condition, keep evidence link  

**Conflict policy (decide later):**

- **Accumulate marks** (recommended first): every matching rule adds +1; rank by marks then prewin  
- or **first priority wins** only  

---

## 8. Backtest tab

Controls: venue bucket, date from–to, optional “enabled only”.

Table columns:

| Column | Meaning |
|--------|---------|
| Rule code / name | |
| Venue scope | |
| Races matched | condition true |
| Picks | total horses marked |
| Placed picks | FP≤3 among picks |
| Pick hit % | placed / picks |
| Race hit % | races with ≥1 placed pick / matched |
| Avg marks/race | |
| Evidence n | rows used when authored |

Click rule → show matched race strings with highlighted picks (same colour language).

---

## 9. Frozen decisions (§9)

1. **Hit definition:** default `FP≤4`; backtest UI selector for 3 or 4.  
2. **Multiple groups:** always qualify with **small/big**; appearance `ord` / `ord_size` as secondary.  
3. **Positions:** use explicit `pos31` / `pos32` (and `pos321`…); no ambiguous bare `pos32` for “top of 3x”.  
4. **Offset:** optional `offset` on picks (e.g. `pos321` + `offset:-1`); out of range → skip. Prefer big/small group qualifiers.  
5. **Multi-pick:** equal `+1` marks; no priority yet.  
6. **Fingerprint table:** materialize (`prop_race_fingerprint`).  
7. **Page:** new `prop_manual.php` (standalone from `prop.php`).  
8. **Band 6 (60–120):** one band `6`.  
9. **Auto rules:** stay separate; Eval String tab runs enabled manual rules only and returns hit count + marks.

---

## 12. How to run (v1)

1. Open `prop_manual.php` (calls `install` automatically).  
2. Click **Rebuild FP** once (builds fingerprints from `racepropresult`).  
3. **Builder:** set venue/dates → click symptom chips → **Load Races** → check rows → **Propose picks** → name → **Confirm save** (saved as draft `enabled=0`).  
3b. **Miner:** auto-search symptom combos (depth 1–3) → propose ≤3 picks → rank by **lift vs random** at same pick budget (FP≤4). Save drafts from the table, then enable in Rules.  
4. **Rules** tab: enable promising rules.  
5. **Backtest** tab: see race-hit % / pick % (`FP≤` selector).  
6. **Eval String:** paste a prop string → marks + which rules fired.

Files:

| File | Role |
|------|------|
| `schema_manual_rules.sql` | Tables |
| `PropFingerprint.php` | Band vocabulary |
| `PropManualRuleEngine.php` | Condition/pick eval + string feed |
| `prop_manual_api.php` | API |
| `prop_manual.php` | UI |

