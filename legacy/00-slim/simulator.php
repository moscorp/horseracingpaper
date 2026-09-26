<?php
/**
 * Prop simulator — enter proppre values, see predictions live.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prop Simulator</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Segoe UI,system-ui,sans-serif;background:#0f1923;color:#c8d6e5;min-height:100vh;padding:20px}
h1{color:#48dbfb;margin-bottom:6px;font-size:1.3rem}
p.sub{color:#8899aa;margin-bottom:20px;font-size:.85rem}
.row{display:flex;gap:12px;margin-bottom:12px;flex-wrap:wrap;align-items:center}
.row label{width:100px;color:#8899aa;font-size:.85rem}
.row input,.row select{padding:6px 10px;background:#1a2735;border:1px solid #2d3e50;color:#c8d6e5;border-radius:4px;font-size:.9rem}
.btn{padding:8px 20px;background:#2d4a6f;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:.9rem}
.btn:hover{background:#3a5f8a}
.result{border:1px solid #2d3e50;background:#1a2735;border-radius:6px;padding:14px;margin-top:16px}
.result h3{color:#48dbfb;margin-bottom:8px}
table{width:100%;border-collapse:collapse;font-size:.85rem;margin-top:8px}
th,td{padding:6px 10px;text-align:left;border-bottom:1px solid #2d3e50}
th{color:#8899aa;font-weight:600}
.badge{display:inline-block;padding:2px 8px;border-radius:3px;font-size:.75rem;font-weight:600;margin:1px}
.badge-pred{background:#2d4a6f;color:#48dbfb}
.badge-symptom{background:#5e4e0e;color:#fcd34d}
.prop-item{display:inline-flex;align-items:center;justify-content:center;width:30px;height:22px;border-radius:3px;font-size:.7rem;font-weight:600;margin:1px}
.prop-50{background:#6e1e1e;color:#fca5a5}
.prop-40{background:#5e4e0e;color:#fcd34d}
.prop-30{background:#0e4e5e;color:#67e8f9}
.prop-20{background:#4e1e5e;color:#c4b5fd}
.prop-10{background:#1e4e2e;color:#86efac}
.prop-other{background:#333;color:#aaa}
#error{color:#fca5a5;display:none;margin-top:8px}
</style>
</head>
<body>
<main>
<h1>Prop Simulator</h1>
<p class="sub">Enter proppre values separated by commas, select venue, click Run to see predictions.</p>

<div class="row">
  <label>Venue:</label><select id="venue"><option value="HV">HV</option><option value="ST" selected>ST</option></select>
</div>
<div class="row">
  <label>Proppre values:</label>
  <input type="text" id="props" style="width:500px" placeholder="40,31,26,28,33,24,34,27,28,24,21,29,23,19">
</div>
<button class="btn" id="btnRun">Run Analysis</button>
<div id="error"></div>
<div class="result" id="result" style="display:none"></div>
</main>

<script>
$(function(){
  function propColor(v) {
    v = parseInt(v);
    if (v >= 50) return 'prop-50';
    if (v >= 40) return 'prop-40';
    if (v >= 30) return 'prop-30';
    if (v >= 20) return 'prop-20';
    if (v >= 10) return 'prop-10';
    return 'prop-other';
  }

  function analyzeLocally(venue, vals) {
    var n = vals.length;
    var freq = {};
    $.each(vals, function(i,v){ freq[v] = (freq[v]||0)+1; });

    var labels = [];
    $.each(vals, function(i,v) {
      var c = freq[v];
      if (c < 2) { labels[i] = null; }
      else if (v >= 10 && v <= 19) { labels[i] = '1x'; }
      else if (v >= 20 && v <= 29) { labels[i] = c >= 3 ? '32x' : '2x'; }
      else if (v >= 30 && v <= 39) { labels[i] = c >= 3 ? '33x' : '3x'; }
      else if (v >= 40 && v <= 49) { labels[i] = c >= 3 ? '34x' : '4x'; }
      else { labels[i] = null; }
    });

    var groups = {};
    $.each(freq, function(v,c) {
      if (c < 2) return;
      var posArr = []; $.each(vals, function(i, vv){ if (vv === v) posArr.push(i); });
      var lbl = '';
      if (v >= 10 && v <= 19) lbl = '1x';
      else if (v >= 20 && v <= 29) lbl = c >= 3 ? '32x' : '2x';
      else if (v >= 30 && v <= 39) lbl = c >= 3 ? '33x' : '3x';
      else if (v >= 40 && v <= 49) lbl = c >= 3 ? '34x' : '4x';
      if (!lbl) return;
      groups[v] = {value:v, count:c, label:lbl, positions:posArr, top:posArr[0], bottom:posArr[posArr.length-1], middle:posArr[Math.floor((posArr.length-1)/2)]};
    });

    var count40=0, count50=0, hasOver50=false;
    var b2 = null, b2p = null, b3 = null, b3p = null, b4 = null, b4p = null;
    var sm20 = null, sm20p = null;
    var first40 = null;
    var mid40 = false;
    var has18 = false, has19 = false, p18=[], p19=[];
    $.each(vals, function(i,v){
      if (v >= 40 && v <= 49) { count40++; if (!first40) first40 = {val:v, pos:i}; }
      if (v >= 50) { count50++; hasOver50 = true; }
      if (v >= 20 && v <= 29) {
        if (b2===null||v>b2){ b2=v; b2p=i; }
        if (sm20===null||v<sm20){ sm20=v; sm20p=i; }
      }
      if (v >= 30 && v <= 39 && (b3===null||v>b3)) { b3=v; b3p=i; }
      if (v >= 40 && v <= 49 && (b4===null||v>b4)) { b4=v; b4p=i; }
      if (v >= 40 && v <= 49) {
        var ratio = i / Math.max(1, n-1);
        if (ratio >= 0.25 && ratio <= 0.75) mid40 = true;
      }
      if (v === 18) { has18 = true; p18.push(i); }
      if (v === 19) { has19 = true; p19.push(i); }
    });

    // Groups by label
    var d32x = [], d33x = [], d3x = [], d2x = [];
    $.each(groups, function(v,g) {
      if (g.label === '32x') d32x.push(g);
      if (g.label === '33x') d33x.push(g);
      if (g.label === '3x') d3x.push(g);
      if (g.label === '2x') d2x.push(g);
    });

    // Apply rules
    var preds = [];

    // rule a: HV only, 33x count>=3 and 3x exists
    if (venue === 'HV') {
      $.each(d33x, function(i,g33) {
        if (g33.count < 3) return;
        $.each(d3x, function(j,g3) {
          if (g3.count !== 2) return;
          preds.push({pos:g33.top, rule:'a', pred:'≤2', desc:'Rule A: top 33 (val='+g33.value+') ≤2'});
          preds.push({pos:g3.bottom, rule:'a', pred:'≤4', desc:'Rule A: bottom 3x (val='+g3.value+') ≤4'});
        });
      });
    }

    // rule b
    if (venue === 'HV') {
      $.each(d32x, function(i,g) { preds.push({pos:g.top, rule:'b', pred:'≤3', desc:'Rule B: top of 32x (val='+g.value+') ≤3'}); });
      $.each(d3x, function(i,g) { preds.push({pos:g.top, rule:'b', pred:'≤3', desc:'Rule B: top of 3x (val='+g.value+') ≤3'}); });
    }

    // rule c
    if (n >= 1) {
      var topV = vals[0];
      if (topV >= 50 && topV <= 59 && freq[topV] < 2 && b3p !== null && b3p <= 3) {
        preds.push({pos:b3p, rule:'c', pred:'≤3', desc:'Rule C: Top1 is 50-59 (no dup), B3 ≤3'});
      }
    }

    // rule d
    if (mid40) {
      if (sm20p !== null) preds.push({pos:sm20p, rule:'d', pred:'≤3', desc:'Rule D: smallest 20-29 (val='+sm20+') ≤3 (40-49 at middle)'});
      else if (first40) preds.push({pos:first40.pos, rule:'d', pred:'≤3', desc:'Rule D: 40-49 (val='+first40.val+') ≤3 (no 20-29)'});
    }

    // rule e: 2+ 32x
    if (d32x.length >= 2) {
      var big = d32x[0];
      if (d32x[1].count > big.count) big = d32x[1];
      preds.push({pos:big.top, rule:'e', pred:'≤3', desc:'Rule E: top of bigger 32x (val='+big.value+') ≤3'});
      preds.push({pos:big.bottom, rule:'e', pred:'≤5', desc:'Rule E: bottom of bigger 32x ≤5'});
    }

    // rule f
    if (has19) {
      var low19 = false;
      $.each(p19, function(i,p){ if(p >= n * 0.66) low19 = true; });
      if (low19 && b3p !== null) {
        preds.push({pos:b3p, rule:'f', pred:'≤3', desc:'Rule F: 19 at lower-bottom, B3 ≤3'});
      }
    }

    // rule g
    if (has18) {
      var low18 = false;
      $.each(p18, function(i,p){ if(p >= n * 0.66) low18 = true; });
      if (low18 && b2 !== null && b3 !== null && b2 < b3) {
        preds.push({pos:b2p, rule:'g', pred:'≤3', desc:'Rule G: 18 at lower-bottom, B2<B3 so B2 ≤3'});
      }
    }

    return {
      vals:vals, labels:labels, freq:freq, groups:groups,
      d32x:d32x, d33x:d33x, d3x:d3x, d2x:d2x,
      count40:count40, count50:count50, hasOver50:hasOver50,
      b2:b2,b2p:b2p, b3:b3,b3p:b3p, b4:b4,b4p:b4p,
      sm20:sm20, sm20p:sm20p, mid40:mid40,
      has18:has18, p18:p18, has19:has19, p19:p19, preds:preds
    };
  }

  function render(data) {
    var html = '<h3>Simulation Results</h3>';
    html += '<div style="color:#8899aa;font-size:.85rem;margin-bottom:4px">' + data.vals.length + ' horses</div>';

    // symptoms
    html += '<div style="margin-bottom:12px;font-size:.85rem;color:#8899aa">';
    if (data.d32x.length) html += '<span class="badge badge-symptom">32x:'+data.d32x.map(function(g){return g.label+'('+g.count+')';}).join(', ')+'</span> ';
    if (data.d33x.length) html += '<span class="badge badge-symptom">33x:'+data.d33x.map(function(g){return g.label+'('+g.count+')';}).join(', ')+'</span> ';
    if (data.b2!==null) html += '<span class="badge badge-symptom">B2='+data.b2+'@'+(data.b2p+1)+'</span> ';
    if (data.b3!==null) html += '<span class="badge badge-symptom">B3='+data.b3+'@'+(data.b3p+1)+'</span> ';
    if (data.b4!==null) html += '<span class="badge badge-symptom">B4='+data.b4+'@'+(data.b4p+1)+'</span> ';
    html += '<span class="badge badge-symptom">40-49:'+data.count40+'</span> ';
    html += '<span class="badge badge-symptom">50+:'+data.count50+'</span> ';
    if (data.has19) html += '<span class="badge badge-symptom">19:'+data.p19.map(function(p){return p+1;}).join(',')+'</span> ';
    if (data.has18) html += '<span class="badge badge-symptom">18:'+data.p18.map(function(p){return p+1;}).join(',')+'</span> ';
    html += '</div>';

    html += '<table><thead><tr><th>Pos</th><th>Prop</th><th>Label</th><th>Predicted</th></tr></thead><tbody>';
    $.each(data.vals, function(i, v) {
      var lbl = data.labels[i] || '-';
      var preds = data.preds.filter(function(p){return p.pos === i;});
      var predHtml = preds.length > 0 ? preds.map(function(p){return '<span class="badge badge-pred">'+p.rule.toUpperCase()+':'+p.pred+'</span>';}).join(' ') : '-';
      html += '<tr><td>'+(i+1)+'</td>';
      html += '<td><span class="prop-item '+propColor(v)+'">'+v+'</span></td>';
      html += '<td>'+lbl+'</td><td>'+predHtml+'</td></tr>';
    });
    html += '</tbody></table>';

    if (data.preds.length === 0) html += '<p style="color:#fcd34d;margin-top:8px"> -- No rules triggered for this input.</p>';

    $('#result').show().html(html);
  }

  $('#btnRun').click(function() {
    $('#error').hide();
    $('#result').hide();
    var raw = $('#props').val().trim();
    if (!raw) { $('#error').text('Enter prop values').show(); return; }
    var vals = $.map(raw.split(','), function(x){ return parseInt(x.trim()); });
    if (vals.length < 3) { $('#error').text('Need 3+ horses').show(); return; }
    var venue = $('#venue').val();
    var data = analyzeLocally(venue, vals);
    render(data);
  });
});
</script>
</body>
</html>