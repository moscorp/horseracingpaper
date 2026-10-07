# Prop Analysis & Prediction

Horse racing prop-pattern miner for PHP 5.4+ / jQuery.

## Intended pipeline (project purpose)

```
foreach race (venue / date / raceno):
  1. Order horses by 1/prewin (tie → prepla)  → prop string is FIXED
  2. Read overall prop pattern:
       duplicates → 1x/2x/3x/32x/33x/…, B2/B3/B4, positions,
       sequential pairs, mid/leader/tail of each band, …
  3. Attach each horse's finalPosition (placed = FP≤3)
  4. Accumulate pattern×role → placed statistics across races
  5. Keep rules with lift above baseline  → unlimited rule set per venue

predict / backtest:
  match mined rules on a new prop string → score → pick3 by rule priority
```

We care about **which 3 horses the patterns point to**, not exact 1st/2nd/3rd order.

Hardcoded symptom WHERE-clauses are **not** the discovery path. Training auto-crosses
**pattern** (33x, 32x, B3, …) × **role** (mid, leader, tail, top, …) and keeps whatever
the history supports — e.g. `is_33x=true AND group_mid=true` if data confirms it.

Rules DB (persist/accumulate) = next stage when retrain cost matters.

Unlimited train + backtest: `TOP_N_RULES = 0`.

## Files

| File | Purpose |
|------|---------|
| `../lib/constants.php` | DB config |
| `PropAnalyzer.php` | Feature extraction — 75+ per-horse features, cross-features, sequential pairs |
| `PropPrediction.php` | Chunked rule mining + prediction + symptom race-rules + finalpick3 + metrics |
| `prop_api.php` | REST API — venues, dates, train, predict (holdout), predict_raw |
| `prop.php` | Dashboard: Backtest / Rules / Predict tabs + Top-K hit metrics |
| `PropSimulator.php` | Standalone simulator for manual prop entry |

## Architecture: Per-Venue Training

`PropPrediction::createPerVenue($db, …, $dateBefore)` creates 3 independent predictors:

| Instance | Venues | Trained on |
|----------|--------|------------|
| `$venuePredictors['ST']` | Sha Tin | ST races only |
| `$venuePredictors['HV']` | Happy Valley | HV races only |
| `$venuePredictors['Sx']` | S1–S9 | All Sx races combined |

**Holdout (walk-forward):** when `date_from` is set on `predict`, training uses `racingdate < date_from` only. The selected From–To window is out-of-sample.

## Training: How Rules Are Mined

Chunked, memory-safe. Target = `placed` (finalPosition ≤ 3).

### Feature Categories

| Category | Examples |
|----------|---------|
| **Boolean** | `is_top`, `is_33x`, `has_sequential_33x`, `group_leader` |
| **Categorical** | `prop_range`, `race_d33x_count`, `group_size`, `label` |
| **Compound** | `is_B2=true AND race_any18Bot=true AND B2_lt_B3=true` |

Rule score = `lift × log₁₀(support)` → top N kept.

Boolean polarity is stored as `field=true` / `field=false` so both sides can mine correctly.

### Training Configuration

| Parameter | Value | Notes |
|-----------|-------|-------|
| **TOP_N_RULES** | 0 (unlimited) | Keep every rule that passes minLift |
| **minLift** | 1.15 | Weak associations filtered out |
| **baselinePlaced** | dynamic | Place rate in training data |

Race-level-only rules (same for every horse in a race) are mined for inspection but **skipped at scoring** so they do not flatten rankings.

## Symptom Race-Rules (a–g)

Hard-coded from `prop symptom.txt`, applied as boosts after mined-rule scoring:

| ID | Condition | Boosts |
|----|-----------|--------|
| a | HV + 33x + 3x | Top of 33x, bottom of 3x |
| b | HV + 32x + 3x | Top of 3x, mid of 32x |
| c | Top1 lonely 50–59, next is B3 | B3 |
| d | Mid-string 40–49 | Smallest 20–29 (else mid-40) |
| e | Two 32x sets | Top + bottom of bigger 32x |
| f | 19 in lower third | B3 |
| g | 18 in lower third + B2 &lt; B3 | B2 |

## Prediction: Formulas

### baseProb
```
baseProb = 1 / (1 + prewin)
```
Market-implied win chance. Example: prewin=3.2 → baseProb≈0.238 (24%).

### rule_hits / totalLift
Count **every** matching mined rule (no cap) + symptom hits.
```
excessLift = Σ max(0, lift − 1)   for each matched rule
```

### ruleProb (within-race, 0..1)
Normalize lift / hits / symptom vs other horses in the **same race**:
```
liftRel = (excessLift − min) / (max − min)
hitsRel = (rule_hits − min) / (max − min)
symRel  = (symptom − min) / (max − min)

ruleProb = 0.55×liftRel + 0.30×hitsRel + 0.15×symRel
```

### probability (display)
```
raw = 0.20×baseProb + 0.50×ruleProb + 0.20×propNorm + 0.10×symRel
      where propNorm = min(prop, 45+(prop−45)×0.3) / 100

probability = map raw within race to ~6%–52% (forced spread)
```

### finalpick3 composite (blend)
```
score = 0.40×(prob/maxProb) + 0.25×baseProb + 0.15×propNorm
      + 0.12×(excessLift/maxLift) + 0.08×(rule_hits/maxHits)
```

### finalpick3onlyrules (pattern-only — no prop / market)
```
score = 0.65 × (excessLift / maxLift) + 0.35 × (rule_hits / maxHits)
```
Lift > hits: rule *quality* outweighs match *count*. Ties → higher lift → higher hits → earlier prop-order position.

This matches the project goal: prop string order is fixed; patterns hint which horses in that string are more likely to place — pick the best 3 by rule priority only.

## Backtest Metrics

| Metric | Meaning |
|--------|---------|
| **Top1 Place %** | Rank-1 pick finished ≤ 3 |
| **Top3 Place %** | Among top-3 ranked picks, share that placed |
| **Race Any Top3 %** | Races where ≥1 of top-3 placed |
| **FinalPick3 %** | Blend pick3 place rate |
| **RulesOnly Pick3 %** | `finalpick3onlyrules` place rate (no market/prop) |

These replace the old “Placed b/a” base-rate counter.

## API

| Endpoint | Notes |
|----------|-------|
| `?action=predict&date_from=&date_to=` | Holdout train + predict; returns `metrics`, `finalpicks`, `holdout` |
| `?action=train&holdout=1&date_from=` | Train on dates before `date_from` |
| `?action=predict_raw` | Live props CSV → predictions + finalpicks |

## Next Stage

- Persist mined rules + predictions to DB for hit-rate history
- Grid-search coefficients per venue
- Temporal / go_ch cross-features
