<?php /* Manual Rules Platform */ ?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Prop Manual Rules</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;background:#0f1923;color:#c8d6e5;min-height:100vh}
header{background:#1a2735;padding:8px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #2d3e50}
h1{font-size:1.05rem;color:#48dbfb}
.nv{display:flex;gap:6px;align-items:center}
.nv a,.nv button{padding:5px 12px;border:1px solid #2d3e50;background:#1e2d3d;color:#a0b9ce;cursor:pointer;border-radius:3px;font-size:.8rem;text-decoration:none}
.nv button.active,.nv button:hover,.nv a:hover{background:#2d4a6f;color:#fff;border-color:#48dbfb}
main{padding:12px}
.ctrl{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;align-items:center}
.ctrl label{font-size:.78rem;color:#8a9bad}
.ctrl select,.ctrl input,textarea{padding:4px 7px;background:#1a2735;border:1px solid #2d3e50;color:#c8d6e5;border-radius:3px;font-size:.8rem}
.btn{padding:5px 14px;background:#2d4a6f;color:#fff;border:none;border-radius:3px;cursor:pointer;font-size:.82rem}
.btn:hover{background:#3a5f8a}
.btn-g{background:#2d6a4f}.btn-g:hover{background:#40916c}
.btn-r{background:#6a2d2d}.btn-r:hover{background:#8a3a3a}
.tab{display:none}.tab.active{display:block}
.card{background:#1a2735;border:1px solid #2d3e50;border-radius:5px;padding:10px;margin-bottom:8px;overflow-x:auto}
.card h3{font-size:.88rem;color:#48dbfb;margin-bottom:6px}
.chips{display:flex;flex-wrap:wrap;gap:4px;margin:6px 0}
.chip{padding:3px 8px;border:1px solid #2d3e50;border-radius:3px;font-size:.72rem;cursor:pointer;background:#1e2d3d;color:#a0b9ce;user-select:none}
.chip.on{background:#c8a800;color:#222;border-color:#c8a800;font-weight:700}
.chip.rel{background:#1a3a4a}
.chip.rel.on{background:#48dbfb;color:#122}
table{width:100%;border-collapse:collapse;font-size:.76rem}
th,td{padding:4px 6px;border-bottom:1px solid #2d3e50;text-align:left;white-space:nowrap}
th{color:#8899aa}
tr:hover{background:#1e2d3d}
.prop-list{display:flex;gap:1px;flex-wrap:nowrap}
.pp{display:inline-flex;min-width:24px;height:18px;align-items:center;justify-content:center;border-radius:2px;font-size:.58rem;font-weight:700;color:#eee;margin:0 1px;background:#444;padding:0 2px}
.pp.yellow{background:#c8a800;color:#222}
.pp.green{background:#5acc5a;color:#222}
.pp.f1{border:2px solid red;color:#ff6b6b}.pp.f2{border:2px solid #48f;color:#6af}
.pp.f3{border:2px solid #0f8;color:#5f8}.pp.f4{border:2px solid #b85;color:#c96}
.pp.mark{box-shadow:inset 0 0 0 2px #fff}
#loading{display:none;text-align:center;padding:12px;color:#48dbfb}
.stat{display:inline-block;margin-right:10px;font-size:.78rem;color:#8899aa}
.stat b{color:#48dbfb}
.panel-2{display:grid;grid-template-columns:1fr 320px;gap:8px}
@media(max-width:900px){.panel-2{grid-template-columns:1fr}}
textarea{width:100%;min-height:70px;font-family:Consolas,monospace;font-size:.72rem}
.muted{color:#667788;font-size:.72rem}
.ok{color:#5acc5a}.err{color:#ff6b6b}
tr.mn-best{background:#2a3a18 !important;outline:2px solid #c8a800;outline-offset:-2px}
tr.mn-best td{color:#f0f0e0}
tr.mn-good{background:#1e2f3a !important}
.mn-badge{display:inline-block;margin-left:6px;padding:1px 6px;border-radius:3px;font-size:.65rem;font-weight:700;vertical-align:middle}
.mn-badge.best{background:#c8a800;color:#222}
.mn-badge.good{background:#2d6a4f;color:#dff}
.btn-best{background:#c8a800 !important;color:#222 !important;font-weight:700}
</style>
</head><body>

<header>
  <h1>Prop Manual Rules</h1>
  <div class="nv">
    <button class="active" data-tab="builder">Builder</button>
    <button data-tab="backtest">Backtest</button>
    <button data-tab="miner">Miner</button>
    <button data-tab="eval">Eval String</button>
    <button data-tab="rules">Rules</button>
    <a href="prop.php">← Auto Prop</a>
  </div>
</header>

<main>
<div class="ctrl">
  <label>Venue</label>
  <select id="fVenue"><option value="Sx">Sx</option><option value="ST">ST</option><option value="HV">HV</option><option value="ALL">ALL</option></select>
  <label>From</label><input type="date" id="fFrom">
  <label>To</label><input type="date" id="fTo">
  <label>FP≤</label>
  <select id="fFpMax"><option value="4">4</option><option value="3">3</option></select>
  <button class="btn" id="btnRebuild">Rebuild FP</button>
  <button class="btn" id="btnLoad">Load Races</button>
  <span id="status" class="muted"></span>
</div>
<div id="loading">Loading…</div>

<!-- BUILDER -->
<div id="tab-builder" class="tab active">
  <div class="card">
    <h3>Symptom filters (→ condition_json)</h3>
    <div class="chips" id="chips">
      <!-- presence -->
      <span class="chip" data-c='{"op":"has_label","band":"2","label":"2x"}'>has 2x</span>
      <span class="chip" data-c='{"op":"has_label","band":"2","label":"32x"}'>has 32x</span>
      <span class="chip" data-c='{"op":"has_label","band":"3","label":"3x"}'>has 3x</span>
      <span class="chip" data-c='{"op":"has_label","band":"3","label":"33x"}'>has 33x</span>
      <span class="chip" data-c='{"op":"has_label","band":"4","label":"4x"}'>has 4x</span>
      <span class="chip" data-c='{"op":"has_label","band":"4","label":"34x"}'>has 34x</span>
      <span class="chip" data-c='{"op":"has_label","band":"1","label":"31x"}'>has 31x</span>
      <span class="chip" data-c='{"op":"has_label","band":"5","label":"5x"}'>has 5x</span>
      <span class="chip" data-c='{"op":"has_label","band":"6","label":"6x"}'>has 6x</span>
      <!-- counts -->
      <span class="chip" data-c='{"op":"c2x","band":"2","eq":2}'>c2x=2</span>
      <span class="chip" data-c='{"op":"c_label","band":"2","label":"32x","gte":2}'>c32x≥2</span>
      <span class="chip" data-c='{"op":"c_label","band":"3","label":"3x","eq":1}'>c3x=1</span>
      <span class="chip" data-c='{"op":"c_label","band":"3","label":"3x","gte":2}'>c3x≥2</span>
      <span class="chip" data-c='{"op":"only_label","label":"32x"}'>only 32x</span>
      <span class="chip" data-c='{"op":"only_label","label":"33x"}'>only 33x</span>
      <!-- size -->
      <span class="chip" data-c='{"op":"has_label","band":"3","label":"3x","size":"small"}'>small 3x</span>
      <span class="chip" data-c='{"op":"c_label","band":"3","label":"3x","size":"small","eq":1}'>1 small 3x</span>
      <span class="chip" data-c='{"op":"has_label","band":"3","label":"3x","size":"big"}'>big 3x</span>
      <span class="chip" data-c='{"op":"has_label","band":"2","label":"2x","size":"small"}'>small 2x</span>
      <span class="chip" data-c='{"op":"has_label","band":"2","label":"32x","size":"big"}'>big 32x</span>
      <!-- singletons / top -->
      <span class="chip" data-c='{"op":"c_w","band":"5","eq":1}'>only 1 w5</span>
      <span class="chip" data-c='{"op":"c_w","band":"5","gte":2}'>w5≥2</span>
      <span class="chip" data-c='{"op":"c_w","band":"6","eq":1}'>only 1 w6</span>
      <span class="chip" data-c='{"op":"not_top","ref":"w5"}'>w5 not top</span>
      <span class="chip" data-c='{"op":"not_top","ref":"w6"}'>w6 not top</span>
      <span class="chip" data-c='{"op":"not_top","ref":"B2"}'>B2 not top</span>
      <span class="chip" data-c='{"op":"not_top","ref":"w2"}'>w2 not top</span>
      <span class="chip" data-c='{"op":"is_top","ref":"w6"}'>w6 top</span>
      <span class="chip" data-c='{"op":"no_pairs"}'>no ?x</span>
      <!-- relations -->
      <span class="chip" data-c='{"op":"neq_token","a":"w2","b":"B2"}'>w2≠B2</span>
      <span class="chip" data-c='{"op":"neq_token","a":"w3","b":"B3"}'>w3≠B3</span>
      <span class="chip" data-c='{"op":"neq_token","a":"w4","b":"B4"}'>w4≠B4</span>
      <span class="chip" data-c='{"op":"adjacent","from":"B2","to":"B3"}'>B2 adj B3</span>
      <span class="chip" data-c='{"op":"adjacent","from":"pos32","to":"w2","of":{"band":"3","label":"3x","size":"small"}}'>pos32 adj w2</span>
      <span class="chip" data-c='{"op":"adjacent","from":"pos21","to":"w3","of":{"band":"2","label":"2x"}}'>pos21 adj w3</span>
      <span class="chip" data-c='{"op":"nested_in","inner":{"band":"3","label":"3x","size":"small"},"outer":{"band":"3","label":"3x","size":"big"}}'>small 3x in big 3x</span>
    </div>
    <div class="muted">Click chips → list reloads live · chips AND together · Yellow=?x · Green=3?x · Borders FP1–4 · Unlisted symptoms: paste ops into note and we can add a chip (engine already supports more than the bar shows)</div>
  </div>

  <div class="panel-2">
    <div class="card">
      <h3>Races <span class="stat">matched <b id="raceN">0</b></span> <span class="stat">checked <b id="chkN">0</b></span>
        <label class="muted" style="margin-left:10px;font-weight:400"><input type="checkbox" id="chkAll"> Select all</label>
      </h3>
      <table>
        <thead><tr><th><input type="checkbox" id="chkAllHead" title="Select all"></th><th>Date</th><th>V</th><th>#</th><th>Prop string</th><th>Hits</th></tr></thead>
        <tbody id="raceBody"></tbody>
      </table>
    </div>
    <div class="card">
      <h3>Save rule</h3>
      <div class="ctrl" style="margin:0 0 6px">
        <label>Name</label><input id="ruleName" style="flex:1;min-width:120px" placeholder="e.g. only 32x → pos321">
      </div>
      <label class="muted">Note</label>
      <textarea id="ruleNote" placeholder="spoken rule text…"></textarea>
      <label class="muted" style="display:block;margin-top:6px">condition_json (chips or edit)</label>
      <textarea id="condJson"></textarea>
      <label class="muted" style="display:block;margin-top:6px">pick_json (edit if needed)</label>
      <textarea id="pickJson"></textarea>
      <div class="ctrl" style="margin-top:8px">
        <button class="btn" id="btnPropose">Propose picks from checked</button>
        <button class="btn btn-g" id="btnSave">Confirm save</button>
      </div>
      <div id="saveMsg" class="muted" style="margin-top:6px"></div>
    </div>
  </div>
</div>

<!-- BACKTEST -->
<div id="tab-backtest" class="tab">
  <div class="ctrl">
    <button class="btn" id="btnBtAll">Run backtest all</button>
    <label><input type="checkbox" id="btEnOnly"> enabled only</label>
  </div>
  <div class="card">
    <h3>Manual rule hit rates</h3>
    <table>
      <thead><tr>
        <th>Code</th><th>Name</th><th>Scope</th><th>On</th><th>Matched</th>
        <th>Picks</th><th>Placed</th><th>Pick%</th><th>Race hit%</th><th>Ev</th><th></th>
      </tr></thead>
      <tbody id="btBody"></tbody>
    </table>
  </div>
  <div class="card" id="btDetail" style="display:none">
    <h3>Matched races <span id="btDetailTitle"></span></h3>
    <div id="btDetailBody"></div>
  </div>
</div>

<!-- MINER -->
<div id="tab-miner" class="tab">
  <div class="card">
    <h3>Mine high-lift rules (auto combinations)</h3>
    <div class="muted" style="margin-bottom:8px">
      Searches symptom combos → proposes ≤<b id="mnPickLabel">3</b> picks → ranks by <b>lift vs random</b> at same budget (not raw race hit%).
      Defaults: FP≤4, max 3 picks/race, matched ≥20.
      After run: <span style="color:#c8a800;font-weight:700">gold BEST SAVE</span> = best lift×sample (save first); green = also good (non-duplicate).
    </div>
    <div class="ctrl">
      <label>Max picks/race</label>
      <select id="mnMaxPicks"><option value="3" selected>3</option><option value="2">2</option><option value="1">1</option></select>
      <label>Min matched</label>
      <input type="number" id="mnMinMatched" value="20" min="5" max="200" style="width:64px">
      <label>Depth</label>
      <select id="mnDepth"><option value="1">1 symptom</option><option value="2" selected>1–2</option><option value="3">1–3 (slower)</option></select>
      <label><input type="checkbox" id="mnHoldout"> 30% holdout (score on newer dates)</label>
      <button class="btn btn-g" id="btnMine">Run miner</button>
    </div>
    <div id="mnStatus" class="muted"></div>
  </div>
  <div class="card">
    <h3>Candidates <span class="stat" id="mnMeta"></span></h3>
    <table>
      <thead><tr>
        <th>Score</th><th>Symptoms</th><th>Matched</th><th>Avg picks</th>
        <th>Pick%</th><th>Race hit%</th><th>Race lift</th><th>Pick lift</th>
        <th>Base race%</th><th>Marks</th><th></th>
      </tr></thead>
      <tbody id="mnBody"></tbody>
    </table>
  </div>
</div>

<!-- EVAL -->
<div id="tab-eval" class="tab">
  <div class="card">
    <h3>Feed prop string → rule hit count + marks</h3>
    <div class="ctrl">
      <label>Venue bucket</label>
      <select id="evVenue"><option>Sx</option><option>ST</option><option>HV</option></select>
      <input id="evStr" style="flex:1;min-width:240px" placeholder="59,40,33,31,28,28,26,24,24,21">
      <label class="muted"><input type="checkbox" id="evEnOnly" checked> enabled rules only</label>
      <button class="btn btn-g" id="btnEval">Evaluate</button>
    </div>
    <div class="muted" style="margin-bottom:6px">Backtest Detail always tests one rule (even OFF). Eval String uses <b>enabled</b> rules only unless you uncheck above.</div>
    <div id="evOut" class="muted"></div>
  </div>
</div>

<!-- RULES LIST -->
<div id="tab-rules" class="tab">
  <div class="card">
    <h3>Saved rules</h3>
    <table>
      <thead><tr><th>Code</th><th>Name</th><th>Scope</th><th>On</th><th>Ev</th><th>Condition</th><th>Pick</th><th></th></tr></thead>
      <tbody id="rulesBody"></tbody>
    </table>
  </div>
</div>

</main>

<script>
var API = 'prop_manual_api.php';
var races = [];
var checked = {}; // key -> race

function api(action, params, body) {
  var q = $.extend({action: action}, params || {});
  var url = API + '?' + $.param(q);
  if (body !== undefined) {
    return $.ajax({url: url, method: 'POST', contentType: 'application/json', data: JSON.stringify(body)});
  }
  return $.getJSON(url);
}

function showLoad(on) { $('#loading').toggle(!!on); }
function setStatus(t, ok) {
  $('#status').text(t || '').toggleClass('ok', !!ok).toggleClass('err', ok === false);
}

function defaultDates(dates) {
  if (!dates || !dates.length) return;
  $('#fTo').val(dates[0]);
  var d = new Date(dates[0]);
  d.setFullYear(d.getFullYear() - 1);
  var y = d.toISOString().slice(0, 10);
  // prefer earliest if shorter history
  var from = dates[dates.length - 1];
  if (from < y) $('#fFrom').val(y); else $('#fFrom').val(from);
}

function buildCondition() {
  var all = [];
  var venue = $('#fVenue').val();
  if (venue && venue !== 'ALL') all.push({venue_bucket: venue});
  $('#chips .chip.on').each(function() {
    try { all.push(JSON.parse($(this).attr('data-c'))); } catch (e) {}
  });
  return {all: all};
}

function syncCondJson() {
  $('#condJson').val(JSON.stringify(buildCondition(), null, 2));
}

function cellHtml(cells, markPos) {
  markPos = markPos || {};
  var h = '<div class="prop-list">';
  for (var i = 0; i < cells.length; i++) {
    var c = cells[i];
    var cls = 'pp';
    if (c.fill === 'yellow') cls += ' yellow';
    if (c.fill === 'green') cls += ' green';
    if (c.fp === 1) cls += ' f1';
    else if (c.fp === 2) cls += ' f2';
    else if (c.fp === 3) cls += ' f3';
    else if (c.fp === 4) cls += ' f4';
    var mk = markPos[c.pos];
    if (mk === 'hit' || mk === true || (typeof mk === 'number' && mk > 0)) cls += ' mark';
    else if (mk === 'miss') cls += ' mark';
    var title = 'pos' + c.pos;
    if (c.label) title += ' ' + c.label;
    if (c.fp) title += ' FP' + c.fp;
    if (typeof mk === 'number' && mk > 0) title += ' +' + mk + ' marks';
    else if (mk) title += ' PICK';
    h += '<span class="' + cls + '" title="' + title + '">' + c.prop + '</span>';
  }
  return h + '</div>';
}

function raceKey(r) { return r.racingdate + '|' + r.venue + '|' + r.raceno; }

function renderRaces() {
  var html = '';
  for (var i = 0; i < races.length; i++) {
    var r = races[i];
    var k = raceKey(r);
    html += '<tr>' +
      '<td><input type="checkbox" class="rchk" data-k="' + k + '"' + (checked[k] ? ' checked' : '') + '></td>' +
      '<td>' + r.racingdate + '</td>' +
      '<td>' + r.venue + '</td>' +
      '<td>' + r.raceno + '</td>' +
      '<td>' + cellHtml(r.cells) + '</td>' +
      '<td>' + r.rule_hits + '</td>' +
      '</tr>';
  }
  $('#raceBody').html(html);
  $('#raceN').text(races.length);
  updateChkCount();
}

function updateChkCount() {
  var n = Object.keys(checked).length;
  $('#chkN').text(n);
  var all = races.length > 0 && n === races.length;
  $('#chkAll, #chkAllHead').prop('checked', all);
}

function setAllChecked(on) {
  checked = {};
  if (on) {
    for (var i = 0; i < races.length; i++) {
      checked[raceKey(races[i])] = races[i];
    }
  }
  $('.rchk').prop('checked', !!on);
  updateChkCount();
}

var loadTimer = null;
function loadRaces() {
  showLoad(true);
  syncCondJson();
  var cond = buildCondition();
  api('list_races', {
    venue_bucket: $('#fVenue').val(),
    date_from: $('#fFrom').val(),
    date_to: $('#fTo').val()
  }, {condition: cond}).done(function(res) {
    if (res.error) { setStatus(res.error, false); return; }
    races = res.races || [];
    checked = {};
    renderRaces();
    setStatus('Loaded ' + races.length + ' races', true);
  }).fail(function(xhr) {
    setStatus((xhr.responseJSON && xhr.responseJSON.error) || 'load failed', false);
  }).always(function() { showLoad(false); });
}
/** Debounced reload so rapid chip clicks don't spam the API. */
function loadRacesSoon() {
  syncCondJson();
  if (loadTimer) clearTimeout(loadTimer);
  loadTimer = setTimeout(loadRaces, 280);
}

$('.nv button[data-tab]').on('click', function() {
  $('.nv button[data-tab]').removeClass('active');
  $(this).addClass('active');
  $('.tab').removeClass('active');
  $('#tab-' + $(this).data('tab')).addClass('active');
  if ($(this).data('tab') === 'rules') loadRulesList();
  if ($(this).data('tab') === 'backtest') runBacktestAll();
});

$('#chips').on('click', '.chip', function() {
  $(this).toggleClass('on');
  loadRacesSoon();
});
$('#fVenue').on('change', loadRacesSoon);
$('#fFrom, #fTo').on('change', loadRacesSoon);

$('#btnLoad').on('click', loadRaces);
$('#chkAll, #chkAllHead').on('change', function() {
  var on = $(this).is(':checked');
  $('#chkAll, #chkAllHead').prop('checked', on);
  setAllChecked(on);
});
$('#btnRebuild').on('click', function() {
  showLoad(true);
  setStatus('Rebuilding fingerprints…', true);
  api('rebuild_fingerprints', {date_from: $('#fFrom').val(), date_to: $('#fTo').val()}).done(function(res) {
    if (res.error) { setStatus(res.error, false); return; }
    setStatus('Fingerprints rebuilt: ' + (res.races || 0) + ' races' + (res.fingerprint_reset ? ' (table recreated)' : ''), true);
  }).fail(function(xhr) {
    var msg = (xhr.responseJSON && xhr.responseJSON.error)
      || (xhr.responseJSON && xhr.responseJSON.detail)
      || xhr.responseText
      || ('HTTP ' + xhr.status);
    setStatus('rebuild failed: ' + msg, false);
  }).always(function() { showLoad(false); });
});

$('#raceBody').on('change', '.rchk', function() {
  var k = $(this).data('k');
  var r = null;
  for (var i = 0; i < races.length; i++) if (raceKey(races[i]) === k) { r = races[i]; break; }
  if ($(this).is(':checked') && r) checked[k] = r;
  else delete checked[k];
  updateChkCount();
});

$('#btnPropose').on('click', function() {
  var keys = [];
  $.each(checked, function(_, r) {
    keys.push({racingdate: r.racingdate, venue: r.venue, raceno: r.raceno});
  });
  if (!keys.length) { setStatus('Check some races first', false); return; }
  showLoad(true);
  api('propose_picks', {}, {races: keys, fp_max: parseInt($('#fFpMax').val(), 10)}).done(function(res) {
    if (res.error) { setStatus(res.error, false); return; }
    var marks = res.marks || [];
    $('#pickJson').val(JSON.stringify({marks: marks}, null, 2));
    var msg = 'Proposed ' + marks.length + ' pick refs'
      + ' (need≥' + (res.need || '?') + ' of ' + (res.evidence_n || keys.length)
      + ', fp hits in ' + (res.races_with_fp || 0) + ' races)';
    if (res.used_fallback) msg += ' · mixed evidence → top votes used';
    if (res.hint) msg += ' · ' + res.hint;
    if (!marks.length && res.vote_summary && res.vote_summary.length) {
      msg += ' · top votes: ' + res.vote_summary.slice(0, 3).map(function(v) {
        return v.key + '×' + v.count;
      }).join(', ');
    }
    if (!marks.length && res.missing && res.missing.length) {
      msg += ' · missing FP rows: ' + res.missing.slice(0, 3).join(', ');
    }
    setStatus(msg, marks.length > 0);
  }).fail(function(xhr) {
    setStatus((xhr.responseJSON && xhr.responseJSON.error) || 'propose failed', false);
  }).always(function() { showLoad(false); });
});

function parseCondition() {
  var raw = $.trim($('#condJson').val());
  if (raw) {
    try {
      var c = JSON.parse(raw);
      if (c && (c.all || c.any || c.op || c.venue_bucket)) return c;
    } catch (e) {}
  }
  return buildCondition();
}

$('#btnSave').on('click', function() {
  var name = $.trim($('#ruleName').val());
  if (!name) { $('#saveMsg').text('Name required').addClass('err'); return; }
  var cond;
  try { cond = parseCondition(); }
  catch (e) { $('#saveMsg').text('condition_json invalid').addClass('err'); return; }
  var pick;
  try { pick = JSON.parse($('#pickJson').val() || '{"marks":[]}'); }
  catch (e) { $('#saveMsg').text('pick_json invalid').addClass('err'); return; }
  var nMarks = (pick.marks && pick.marks.length) ? pick.marks.length : 0;
  if (!nMarks) {
    if (!confirm('pick_json has no marks. Save empty rule anyway? (Click Propose first)')) return;
  }
  var evidence = [];
  $.each(checked, function(_, r) {
    evidence.push({racingdate: r.racingdate, venue: r.venue, raceno: r.raceno, prop_string: r.prop_string});
  });
  showLoad(true);
  api('save_rule', {}, {
    name: name,
    note_zh: $('#ruleNote').val(),
    venue_scope: $('#fVenue').val() === 'ALL' ? 'ALL' : $('#fVenue').val(),
    condition: cond,
    pick: pick,
    enabled: 0,
    evidence: evidence,
    fp_max: parseInt($('#fFpMax').val(), 10)
  }).done(function(res) {
    if (res.error) { $('#saveMsg').text(res.error).removeClass('ok').addClass('err'); return; }
    $('#saveMsg').text('Saved ' + res.code + ' (id ' + res.id + ', draft)').removeClass('err').addClass('ok');
  }).fail(function(xhr) {
    $('#saveMsg').text((xhr.responseJSON && xhr.responseJSON.error) || 'save failed').addClass('err');
  }).always(function() { showLoad(false); });
});

function pct(x) { return ((x || 0) * 100).toFixed(1) + '%'; }

function runBacktestAll() {
  showLoad(true);
  api('backtest_all', {
    venue_scope: $('#fVenue').val() === 'ALL' ? '' : $('#fVenue').val(),
    date_from: $('#fFrom').val(),
    date_to: $('#fTo').val(),
    fp_max: $('#fFpMax').val(),
    enabled_only: $('#btEnOnly').is(':checked') ? '1' : '0'
  }).done(function(res) {
    if (res.error) { setStatus(res.error, false); return; }
    var html = '';
    (res.results || []).forEach(function(r) {
      html += '<tr>' +
        '<td>' + r.code + '</td>' +
        '<td>' + r.name + '</td>' +
        '<td>' + r.venue_scope + '</td>' +
        '<td>' + (r.enabled ? 'Y' : 'N') + '</td>' +
        '<td>' + r.races_matched + '</td>' +
        '<td>' + r.picks_total + '</td>' +
        '<td>' + r.picks_placed + '</td>' +
        '<td>' + pct(r.pick_hit_rate) + '</td>' +
        '<td><b>' + pct(r.hit_rate) + '</b></td>' +
        '<td>' + (r.evidence_n || 0) + '</td>' +
        '<td><button class="btn bt-one" data-id="' + r.id + '" data-code="' + r.code + '">Detail</button></td>' +
        '</tr>';
    });
    $('#btBody').html(html || '<tr><td colspan="11">No rules</td></tr>');
  }).always(function() { showLoad(false); });
}

$('#btnBtAll').on('click', runBacktestAll);

var mineCandidates = [];

/** Save-value: lift × sample size, soft-penalize tiny n and sparse resolves. Not raw Score. */
function mineSaveValue(c) {
  var matched = c.races_matched || 0;
  var avgPicks = c.avg_picks || 0;
  var raceLift = c.race_lift || 0;
  var pickLift = c.pick_lift || 0;
  if (matched < 15 || raceLift < 1.05) return 0;
  var v = raceLift * pickLift * Math.log(matched + 1) / Math.max(avgPicks, 0.5);
  if (matched < 40) v *= matched / 40;
  if (avgPicks > 0 && avgPicks < 0.85) v *= 0.85; // often 0 picks when condition fires
  if (avgPicks > 2.5) v *= 0.9;
  return v;
}

function mineSymptomKey(c) {
  var s = (c.symptoms || []).slice().sort();
  return s.join('|');
}

/** Prefer fewer symptoms when stats nearly identical (drop redundant combos). */
function mineIsNearDup(a, b) {
  if (!a || !b) return false;
  if (Math.abs((a.races_matched || 0) - (b.races_matched || 0)) > 2) return false;
  if (Math.abs((a.hit_rate || 0) - (b.hit_rate || 0)) > 0.01) return false;
  if (Math.abs((a.pick_hit_rate || 0) - (b.pick_hit_rate || 0)) > 0.01) return false;
  var sa = (a.symptoms || []);
  var sb = (b.symptoms || []);
  // one symptom set contained in the other
  if (sa.length === sb.length) return false;
  var short = sa.length < sb.length ? sa : sb;
  var long = sa.length < sb.length ? sb : sa;
  var ok = true;
  short.forEach(function(x) {
    if (long.indexOf(x) < 0) ok = false;
  });
  return ok;
}

function pickMineHighlights(list) {
  var scored = list.map(function(c, i) {
    return {i: i, c: c, v: mineSaveValue(c)};
  }).filter(function(x) { return x.v > 0; });
  scored.sort(function(a, b) { return b.v - a.v; });

  var best = null;
  var good = [];
  var chosen = [];
  for (var k = 0; k < scored.length; k++) {
    var row = scored[k];
    var dup = chosen.some(function(prev) { return mineIsNearDup(prev.c, row.c); });
    if (dup) continue;
    // also skip exact same symptom set
    var key = mineSymptomKey(row.c);
    if (chosen.some(function(prev) { return mineSymptomKey(prev.c) === key; })) continue;
    if (!best) {
      best = row;
      chosen.push(row);
    } else if (good.length < 2) {
      good.push(row);
      chosen.push(row);
    } else break;
  }
  return {best: best, good: good};
}

function runMiner() {
  var maxPicks = parseInt($('#mnMaxPicks').val(), 10) || 3;
  $('#mnPickLabel').text(maxPicks);
  var fpMax = parseInt($('#fFpMax').val(), 10) || 4;
  showLoad(true);
  $('#mnStatus').text('Mining… (depth ' + $('#mnDepth').val() + ', may take a minute)').removeClass('err ok');
  api('mine_rules', {}, {
    venue_bucket: $('#fVenue').val(),
    date_from: $('#fFrom').val(),
    date_to: $('#fTo').val(),
    fp_max: fpMax,
    max_picks: maxPicks,
    min_matched: parseInt($('#mnMinMatched').val(), 10) || 20,
    max_depth: parseInt($('#mnDepth').val(), 10) || 2,
    holdout_frac: $('#mnHoldout').is(':checked') ? 0.3 : 0
  }).done(function(res) {
    if (res.error) {
      $('#mnStatus').text(res.error).addClass('err');
      $('#mnBody').html('');
      return;
    }
    mineCandidates = res.candidates || [];
    var o = res.opts || {};
    var hi = pickMineHighlights(mineCandidates);
    var bestIdx = hi.best ? hi.best.i : -1;
    var goodIdx = {};
    (hi.good || []).forEach(function(g) { goodIdx[g.i] = true; });

    $('#mnMeta').html(
      'kept <b>' + (res.kept || 0) + '</b> / tried ' + (res.tried || 0) +
      ' · FP ' + (res.fingerprint_n || 0) +
      ' · train ' + (o.train_n || '?') + ' test ' + (o.test_n || '?')
    );

    if (hi.best) {
      $('#mnStatus').html(
        'Best to save: <b style="color:#c8a800">' + hi.best.c.name + '</b>' +
        ' (lift×sample, not raw Score) · gold row = primary · green = also worth saving'
      ).addClass('ok');
    } else {
      $('#mnStatus').text(
        'Done. No strong save candidate — try lower min matched or Rebuild FP.'
      ).addClass('ok');
    }

    var html = '';
    mineCandidates.forEach(function(c, i) {
      var cls = '';
      var badge = '';
      var btnCls = 'btn btn-g mn-save';
      if (i === bestIdx) {
        cls = 'mn-best';
        badge = '<span class="mn-badge best">BEST SAVE</span>';
        btnCls += ' btn-best';
      } else if (goodIdx[i]) {
        cls = 'mn-good';
        badge = '<span class="mn-badge good">also good</span>';
      }
      html += '<tr class="' + cls + '">' +
        '<td><b>' + (c.score || 0).toFixed(2) + '</b></td>' +
        '<td style="white-space:normal;max-width:280px">' + c.name + badge + '</td>' +
        '<td>' + c.races_matched + '</td>' +
        '<td>' + c.avg_picks + '</td>' +
        '<td>' + pct(c.pick_hit_rate) + '</td>' +
        '<td><b>' + pct(c.hit_rate) + '</b></td>' +
        '<td>' + (c.race_lift || 0).toFixed(2) + '×</td>' +
        '<td>' + (c.pick_lift || 0).toFixed(2) + '×</td>' +
        '<td>' + pct(c.baseline_race) + '</td>' +
        '<td>' + c.marks_n + '</td>' +
        '<td><button class="' + btnCls + '" data-i="' + i + '">' +
          (i === bestIdx ? 'Save best' : 'Save') + '</button></td>' +
        '</tr>';
    });
    $('#mnBody').html(html || '<tr><td colspan="11">No candidates passed filters (try lower min matched, or Rebuild FP)</td></tr>');
    if (bestIdx >= 0) {
      var $row = $('#mnBody tr.mn-best');
      if ($row.length) {
        try { $row[0].scrollIntoView({block: 'nearest', behavior: 'smooth'}); } catch (e) {}
      }
    }
  }).fail(function(xhr) {
    $('#mnStatus').text((xhr.responseJSON && xhr.responseJSON.error) || 'mine failed').addClass('err');
  }).always(function() { showLoad(false); });
}

$('#btnMine').on('click', runMiner);
$('#mnMaxPicks').on('change', function() { $('#mnPickLabel').text($(this).val()); });

$('#mnBody').on('click', '.mn-save', function() {
  var i = parseInt($(this).data('i'), 10);
  var c = mineCandidates[i];
  if (!c) return;
  var $btn = $(this);
  $btn.prop('disabled', true).text('…');
  api('save_rule', {}, {
    name: ('mine: ' + c.name).slice(0, 80),
    note_zh: 'Mined score=' + c.score + ' race_lift=' + c.race_lift + ' pick_lift=' + c.pick_lift +
      ' avg_picks=' + c.avg_picks + ' matched=' + c.races_matched,
    venue_scope: $('#fVenue').val() === 'ALL' ? 'ALL' : $('#fVenue').val(),
    condition: c.condition_json,
    pick: c.pick_json,
    enabled: 0,
    evidence: [],
    fp_max: parseInt($('#fFpMax').val(), 10) || 4
  }).done(function(res) {
    if (res.error) {
      setStatus(res.error, false);
      $btn.prop('disabled', false).text('Save');
      return;
    }
    $btn.text('Saved ' + res.code).removeClass('btn-g');
    setStatus('Saved mined draft ' + res.code + ' (OFF) — enable in Rules', true);
  }).fail(function(xhr) {
    setStatus((xhr.responseJSON && xhr.responseJSON.error) || 'save failed', false);
    $btn.prop('disabled', false).text('Save');
  });
});

$('#btBody').on('click', '.bt-one', function() {
  var id = $(this).data('id');
  var code = $(this).data('code');
  showLoad(true);
  api('backtest', {
    id: id,
    date_from: $('#fFrom').val(),
    date_to: $('#fTo').val(),
    fp_max: $('#fFpMax').val()
  }).done(function(res) {
    $('#btDetail').show();
    $('#btDetailTitle').text(code + ' · matched ' + res.races_matched + ' · race hit ' + pct(res.hit_rate));
    var html = '';
    (res.details || []).forEach(function(d) {
      var marks = {};
      (d.picks || []).forEach(function(p) { marks[p.pos] = p.hit ? 'hit' : 'miss'; });
      var cells = d.cells && d.cells.length ? d.cells : (d.prop_string || '').split(',').map(function(v, i) {
        return {pos: i, prop: parseInt(v, 10), fp: null, fill: 'none'};
      });
      var pickInfo = (d.picks || []).map(function(p) {
        return 'p' + p.prop + (p.fp ? ' FP' + p.fp : '') + (p.hit ? '✓' : '✗');
      }).join(', ');
      if (d.no_picks) pickInfo = '(no picks resolved)';
      html += '<div style="margin:4px 0"><span class="muted">' + d.racingdate + ' ' + d.venue + ' R' + d.raceno +
        (d.race_hit ? ' ✓' : '') + '</span> ';
      if (pickInfo) html += '<span class="muted" style="margin-right:6px">picks: ' + pickInfo + '</span>';
      html += cellHtml(cells, marks) + '</div>';
    });
    $('#btDetailBody').html(html || 'No matches');
  }).always(function() { showLoad(false); });
});

$('#btnEval').on('click', function() {
  showLoad(true);
  var enOnly = $('#evEnOnly').is(':checked');
  api('eval_string', {}, {
    prop_string: $('#evStr').val(),
    venue_bucket: $('#evVenue').val(),
    enabled_only: enOnly
  }).done(function(res) {
    if (res.error) { $('#evOut').html('<span class="err">' + res.error + '</span>'); return; }
    var markPos = {};
    (res.marks || []).forEach(function(m, i) { if (m > 0) markPos[i] = m; });
    var html = '<div class="stat">Rules loaded <b>' + (res.rules_loaded || 0) + '</b> · fired <b>' + res.hit_rule_count + '</b></div>';
    if ((res.rules_loaded || 0) === 0) {
      html += '<div class="err">No rules loaded. Enable rules in Rules tab (OFF→ON), or uncheck “enabled rules only”.</div>';
    } else if (res.hit_rule_count === 0) {
      html += '<div class="muted">No rule condition matched this string (venue=' + $('#evVenue').val() + ').</div>';
    } else {
      html += '<div class="muted">Fired: ' + (res.hit_rules || []).join(', ') + '</div>';
    }
    html += cellHtml(res.cells || [], markPos);
    if (res.top_picks && res.top_picks.length) {
      html += '<div style="margin-top:8px;font-size:.78rem"><b>Marked positions (marks &gt; 0):</b></div>';
      html += '<pre style="margin-top:4px;font-size:.7rem">' + JSON.stringify(res.top_picks, null, 2) + '</pre>';
    } else if ((res.rules_loaded || 0) > 0 && res.hit_rule_count > 0) {
      html += '<div class="muted" style="margin-top:6px">Rules fired but no pick positions resolved (check pick_json).</div>';
    }
    $('#evOut').html(html);
  }).always(function() { showLoad(false); });
});

function loadRulesList() {
  showLoad(true);
  api('list_rules').done(function(res) {
    var html = '';
    (res.rules || []).forEach(function(r) {
      html += '<tr>' +
        '<td>' + r.code + '</td>' +
        '<td>' + r.name + '</td>' +
        '<td>' + r.venue_scope + '</td>' +
        '<td><button class="btn tog" data-id="' + r.id + '">' + (r.enabled ? 'ON' : 'OFF') + '</button></td>' +
        '<td>' + (r.evidence_n || 0) + '</td>' +
        '<td><code style="font-size:.65rem">' + JSON.stringify(r.condition_json).slice(0, 80) + '</code></td>' +
        '<td><code style="font-size:.65rem">' + JSON.stringify(r.pick_json).slice(0, 80) + '</code></td>' +
        '<td><button class="btn btn-r del" data-id="' + r.id + '">Del</button></td>' +
        '</tr>';
    });
    $('#rulesBody').html(html || '<tr><td colspan="8">No rules yet</td></tr>');
  }).always(function() { showLoad(false); });
}

$('#rulesBody').on('click', '.tog', function() {
  var id = $(this).data('id');
  api('toggle_rule', {}, {id: id}).done(loadRulesList);
});
$('#rulesBody').on('click', '.del', function() {
  if (!confirm('Delete this rule?')) return;
  api('delete_rule', {}, {id: $(this).data('id')}).done(loadRulesList);
});

// boot
api('install').done(function(res) {
  if (res && res.error) setStatus(res.error, false);
}).fail(function(xhr) {
  setStatus('install failed: ' + ((xhr.responseJSON && xhr.responseJSON.error) || xhr.status), false);
}).always(function() {
  api('diagnose').done(function(d) {
    if (d && d.tables && !d.tables.racepropresult) {
      setStatus('DB missing racepropresult — check ../lib/constants.php db name', false);
      return;
    }
    if (d && d.source_rows === 0) {
      setStatus('racepropresult has 0 proppre rows in DB ' + d.db, false);
    }
  });
  api('dates').done(function(dates) {
    defaultDates(dates);
    syncCondJson();
    $('#pickJson').val(JSON.stringify({marks: []}, null, 2));
  }).fail(function(xhr) {
    setStatus('dates failed: ' + ((xhr.responseJSON && xhr.responseJSON.error) || xhr.status), false);
  });
});
</script>
</body></html>
