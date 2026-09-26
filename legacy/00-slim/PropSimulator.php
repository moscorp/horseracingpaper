<?php
/**
 * PropSimulator — enter proppre values and see predictions.
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
body{font-family:Segoe UI,system-ui,sans-serif;background:#0f1923;color:#c8d6e5;padding:20px}
h1{color:#48dbfb;margin-bottom:8px;font-size:1.3rem}
p.sub{color:#8899aa;margin-bottom:16px;font-size:.85rem}
.row{display:flex;gap:12px;margin-bottom:12px;align-items:center}
.row label{width:100px;color:#8fa0b0;font-size:.85rem}
.row input,.row select{padding:6px 10px;background:#1a2735;border:1px solid #2d3e50;color:#c8d6e5;border-radius:4px;font-size:.9rem}
.btn{padding:8px 20px;background:#2d4a6f;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:.9rem}
.btn:hover{background:#3a5f8a}
.result{border:1px solid #2d3e50;background:#1a2735;border-radius:6px;padding:14px;margin-top:20px}
.result h3{color:#48dbfb;margin-bottom:6px}
table{width:100%;border-collapse:collapse;font-size:.85rem;margin-top:8px}
th,td{padding:6px 10px;text-align:left;border-bottom:1px solid #2d3e50}
th{color:#8899aa;font-weight:600}
.badge{display:inline-block;padding:2px 8px;border-radius:3px;font-size:.7rem;font-weight:600;margin:1px}
.badge-rule{background:#1e3a5f;color:#67e8f9}
.badge-symptom{background:#3e2e0e;color:#fcd34d}
.prob-bar{height:6px;background:#2d3e50;border-radius:3px;overflow:hidden;min-width:80px;display:inline-block}
.prob-fill{height:100%;border-radius:3px}
.prob-fill.hi{background:linear-gradient(90deg,#48dbfb,#00d2ff)}
.prob-fill.lo{background:linear-gradient(90deg,#5e4e0e,#8b7d0e)}
.prop-item{display:inline-flex;width:30px;height:22px;align-items:center;justify-content:center;border-radius:3px;font-size:.7rem;font-weight:600;margin:1px}
.prop-50{background:#6e1e1e;color:#fca5a5}
.prop-40{background:#5e4e0e;color:#fcd34d}
.prop-30{background:#0e4e5e;color:#67e8f9}
.prop-20{background:#4e1e5e;color:#c4b5fd}
.prop-10{background:#1e4e2e;color:#86efac}
#error{color:#fca5a5;display:none;margin-top:8px}
</style>
</head>
<body>
<main>
<h1>Prop Simulator</h1>
<p class="sub">Enter proppre values comma separated. See symptoms and mined rules that apply.</p>

<div class="row">
  <label>Venue:</label>
  <select id="venue"><option value="HV">HV</option><option value="ST" selected>ST</option></select>
</div>
<div class="row">
  <label>Proppre values:</label>
  <input type="text" id="props" style="width:500px" placeholder="40,31,26,28,33,24,34,27,28,24,21,29,23,19">
</div>
<div class="row">
  <label>Prewin (opt.):</label>
  <input type="text" id="prewin" style="width:500px" placeholder="6.3,17,9.7,28,...">
</div>
<button class="btn" id="btnRun">Run Analysis</button>
<div id="error"></div>
<div class="result" id="result" style="display:none"></div>
</main>

<script>
$(function(){
  function pColor(v){
    v = parseInt(v);
    if (v>=50) return 'prop-50';
    if (v>=40) return 'prop-40';
    if (v>=30) return 'prop-30';
    if (v>=20) return 'prop-20';
    return 'prop-10';
  }

  function analyzeLocally(venue, vals, prewinArr) {
    var n = vals.length;
    var freq = {};
    $.each(vals, function(i,v){ freq[v] = (freq[v]||0)+1; });

    var labels = [];
    $.each(vals, function(i,v){
      var c = freq[v];
      if (c < 2) labels[i] = null;
      else if (v>=10 && v<=19) labels[i] = '1x';
      else if (v>=20 && v<=29) labels[i] = c>=3 ? '32x' : '2x';
      else if (v>=30 && v<=39) labels[i] = c>=3 ? '33x' : '3x';
      else if (v>=40 && v<=49) labels[i] = c>=3 ? '34x' : '4x';
      else labels[i] = null;
    });

    var groups = {};
    $.each(freq, function(v,c){
      if (c < 2) return;
      var posArr = []; $.each(vals, function(i, vv){ if (vv === parseInt(v)) posArr.push(i); });
      var lbl = null;
      if (v>=10 && v<=19) lbl = '1x';
      else if (v>=20 && v<=29) lbl = c>=3 ? '32x' : '2x';
      else if (v>=30 && v<=39) lbl = c>=3 ? '33x' : '3x';
      else if (v>=40 && v<=49) lbl = c>=3 ? '34x' : '4x';
      if (!lbl) return;
      groups[v] = {value: parseInt(v), count:c, label:lbl, positions:posArr, top:posArr[0], bottom:posArr[posArr.length-1]};
    });

    var count40=0, count50=0;
    var b2=null,b2p=null, b3=null,b3p=null, b4=null,b4p=null;
    var sm20=null,sm20p=null, first40=null, mid40=false;
    var has18=false, has19=false, p18=[], p19=[];
    $.each(vals, function(i,v){
      if (v>=40 && v<=49) { count40++; if (!first40) first40 = {val:v,pos:i}; }
      if (v>=50) count50++;
      if (v>=20 && v<=29) {
        if (b2===null || v>b2) { b2=v; b2p=i; }
        if (sm20===null || v<sm20) { sm20=v; sm20p=i; }
      }
      if (v>=30 && v<=39 && (b3===null || v>b3)) { b3=v; b3p=i; }
      if (v>=40 && v<=49 && (b4===null || v>b4)) { b4=v; b4p=i; }
      if (v==18) { has18=1; p18.push(i); }
      if (v==19) { has19=1; p19.push(i); }
    });
    $.each(vals, function(i,v){
      if (v>=40 && v<=49) {
        var r = i / Math.max(1, n-1);
        if (r>=0.25 && r<=0.75) mid40 = true;
      }
    });

    var d32x=[], d33x=[], d3x=[], d2x=[];
    $.each(groups, function(v,g){
      if (g.label==='32x') d32x.push(g);
      if (g.label==='33x') d33x.push(g);
      if (g.label==='3x') d3x.push(g);
      if (g.label==='2x') d2x.push(g);
    });
    var total33x = 0, total3x = 0;
    $.each(labels, function(i,l){
      if (l==='33x') total33x++;
      if (l==='3x') total3x++;
    });

    // Build feature vectors for each horse (subscribe)
    var features = [];
    for (var i=0; i<n; i++) {
      var v = vals[i], l = labels[i];
      var f = {
        position: i, total_horses: n,
        prop_val: v, label: l,
        is_top: i===0, is_top2: i<=1, is_top3: i<=2,
        is_bottom: i===n-1,
        is_32x: l==='32x', is_33x: l==='33x', is_3x: l==='3x', is_2x: l==='2x',
        is_B2: i===b2p, is_B3: i===b3p, is_B4: i===b4p,
        is_lonely: freq[v]===1, is_dup: freq[v]>=2,
        race_has_over50: count50>0,
        race_count40_49: count40, race_count50plus: count50,
        race_d32x_count: d32x.length, race_d33x_count: d33x.length,
        race_d3x_count: d3x.length,
        race_total33x: total33x, race_total3x: total3x,
        race_B2: b2, race_B3: b3, race_B4: b4,
        race_has18: has18>0, race_has19: has19>0,
        race_any18Bot: p18.some(function(p){ return p >= n*0.66; }),
        race_any19Bot: p19.some(function(p){ return p >= n*0.66; }),
        prewin: prewinArr[i] || 10,
      };
      features.push(f);
    }

    // Simple rule check
    var ruleSet = [
      {name:'is_top', fn: function(f){ return f.is_top; }},
      {name:'is_top2', fn: function(f){ return f.is_top2; }},
      {name:'is_32x', fn: function(f){ return f.is_32x; }},
      {name:'is_33x', fn: function(f){ return f.is_33x; }},
      {name:'is_3x', fn: function(f){ return f.is_3x; }},
      {name:'is_B2', fn: function(f){ return f.is_B2; }},
      {name:'is_B3', fn: function(f){ return f.is_B3; }},
      {name:'is_top + race_has_over50', fn: function(f){ return f.is_top && f.race_has_over50; }},
    ];

    for (var j=0; j<features.length; j++) {
      var fe = features[j];
      var rulesHit = 0;
      for (var k=0; k<ruleSet.length; k++) {
        if (ruleSet[k].fn(fe)) rulesHit++;
      }
      fe.rules_hit = rulesHit;
      var base = 1.0/(1+(fe.prewin||10));
      fe.probability = Math.min(0.4, base * Math.max(1, rulesHit*0.15+0.05));
    }

    return {
      vals:vals, labels:labels, groups:groups,
      d32x:d32x, d33x:d33x, d3x:d3x, d2x:d2x,
      count40:count40, count50:count50,
      b2:b2, b2p:b2p, b3:b3, b3p:b3p, b4:b4, b4p:b4p,
      sm20:sm20, sm20p:sm20p, mid40:mid40,
      has18:has18, p18:p18, has19:has19, p19:p19,
      features:features
    };
  }

  function render(data) {
    var html = '<h3>Simulation Results</h3>';
    html += '<div style="color:#8899aa;font-size:.85rem;margin-bottom:8px">'+data.vals.length+' horses</div>';

    html += '<div style="margin-bottom:12px;font-size:.85rem;color:#8899aa">';
    if (data.d32x.length) html += '<span class="badge badge-symptom">32x:'+data.d32x.length+'</span> ';
    if (data.d33x.length) html += '<span class="badge badge-symptom">33x:'+data.d33x.length+'</span> ';
    if (data.b2!==null) html += 'B2='+data.b2+'@'+(data.b2p+1)+' ';
if (data.b3!==null) html += 'B3='+data.b3+'@'+(data.b3p+1)+' ';
  if (data.b4!==null) html += 'B4='+data.b4+'@'+(data.b4p+1)+' ';
    if (data.mid40) html += '<span class="badge badge-symptom">mid40</span> ';
    if (data.has18) html += '18@'+data.p18.map(function(x){return x+1;}).join(',')+' ';
    if (data.has19) html += '19@'+data.p19.map(function(x){return x+1;}).join(',')+' ';
    html += '</div>';

    html += '<table><thead><tr><th>#</th><th>Prop</th><th>Label</th><th>Prob</th><th>Rules Hit</th></tr></thead><tbody>';
    $.each(data.features, function(i,f){
      var pct = Math.round(f.probability*100);
      html += '<tr><td>'+(i+1)+'</td>';
      html += '<td><span class="prop-item '+pColor(f.prop_val)+'">'+f.prop_val+'</span></td>';
      html += '<td>'+(f.label||'-')+'</td>';
      html += '<td><div class="prob-bar"><div class="prob-fill hi" style="width:'+pct+'%"></div></div> '+pct+'%</td>';
      html += '<td>'+f.rules_hit+'</td></tr>';
    });
    html += '</tbody></table>';
    $('#result').show().html(html);
  }

  $('#btnRun').click(function(){
    $('#error').hide(); $('#result').hide();
    var raw = $('#props').val().trim();
    if (!raw) { $('#error').text('Enter prop values').show(); return; }
    var vals = $.map(raw.split(','), function(x){ return parseInt(x.trim()); });
    if (vals.length < 3) { $('#error').text('Need 3+ horses').show(); return; }

    var prewinRaw = $('#prewin').val().trim();
    var prewinArr = [];
    if (prewinRaw) prewinArr = $.map(prewinRaw.split(','), function(x){ return parseFloat(x.trim()); });

    var venue = $('#venue').val();
    var data = analyzeLocally(venue, vals, prewinArr);
    render(data);
  });
});
</script>
</body>
</html>