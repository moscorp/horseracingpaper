<?php
?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Prop Analysis</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;background:#0f1923;color:#c8d6e5;min-height:100vh}
header{background:#1a2735;padding:8px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #2d3e50}
h1{font-size:1.1rem;color:#48dbfb}
.nv{display:flex;gap:6px}
.nv button{padding:5px 14px;border:1px solid #2d3e50;background:#1e2d3d;color:#a0b9ce;cursor:pointer;border-radius:3px;font-size:.82rem}
.nv button.active,.nv button:hover{background:#2d4a6f;color:#fff;border-color:#48dbfb}
main{padding:12px;max-width:100%}
.ctrl{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;align-items:center}
.ctrl label{font-size:.8rem;color:#8a9bad}
.ctrl select,.ctrl input{padding:4px 7px;background:#1a2735;border:1px solid #2d3e50;color:#c8d6e5;border-radius:3px;font-size:.8rem}
.btn-run{padding:5px 16px;background:#2d4a6f;color:#fff;border:none;border-radius:3px;cursor:pointer;font-size:.85rem}
.btn-run:hover{background:#3a5f8a}
.tab-panel{display:none}.tab-panel.active{display:block}
.card{background:#1a2735;border-radius:5px;padding:10px;margin-bottom:8px;border:1px solid #2d3e50;overflow-x:auto}
.card h3{font-size:.9rem;margin-bottom:6px;color:#48dbfb}
table{width:100%;border-collapse:collapse;font-size:.78rem}
th,td{padding:4px 7px;text-align:left;border-bottom:1px solid #2d3e50;white-space:nowrap}
th{color:#8899aa;font-weight:700;background:#1a2735}
tr:hover{background:#1e2d3d}
.race-row{cursor:pointer}
.prop-list{display:flex;gap:1px;flex-wrap:nowrap;overflow-x:auto}
.pp{display:inline-flex;width:25px;height:19px;align-items:center;justify-content:center;border-radius:2px;font-size:.6rem;font-weight:700;flex-shrink:0;color:#eee;margin:0 1px;background:#444}
.pp.s50{background:#a02020}.pp.s40{background:#958b20}.pp.s30{background:#1a6e82}.pp.s20{background:#5b2580}.pp.s10{background:#186e18}
.pp.f1{border:2px solid red;text-decoration:underline;color:red;font-weight:900}.pp.f2{border:2px solid #48f;text-decoration:underline;color:#48f;font-weight:900}
.pp.f3{border:2px solid #0f8;text-decoration:underline;color:#0f8;font-weight:900}.pp.f4{border:2px solid #b85;text-decoration:underline;color:#b85;font-weight:900}
.pp.hb{font-weight:900;box-shadow:inset 0 0 0 2px #fff}
.pp.lx{background:#c8a800;color:#222}.pp.lxx{background:#5acc5a;color:#222}
.prb{height:5px;background:#2d3e50;border-radius:2px;overflow:hidden;min-width:60px;display:inline-block}.prb .fhi{height:100%;background:#48dbfb}.prb .flo{height:100%;background:#b38600}
.sgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:8px;margin-bottom:8px}
.sc{background:#1a2735;border:1px solid #2d3e50;padding:8px;border-radius:4px;text-align:center}
.sc .v{font-size:1.2rem;color:#48dbfb;font-weight:700}.sc .l{font-size:.68rem;color:#8899aa}
#loading{display:none;text-align:center;padding:15px;color:#48dbfb}
.live-f{display:none;flex-wrap:wrap;gap:6px;margin-bottom:6px}.live-f select,.live-f input{padding:3px 6px;background:#1a2735;border:1px solid #2d3e50;color:#c8d6e5;border-radius:3px;font-size:.78rem}
.live-f.on{display:flex}
.bdg{display:inline-block;padding:1px 6px;border-radius:3px;font-size:.65rem;font-weight:600;background:#243;color:#67e8f9}
.bdg-y{background:#c8a800;color:#222}.bdg-g{background:#5acc5a;color:#222}
</style>
</head><body>

<header>
  <h1>Prop Racing</h1>
  <div class="nv">
    <button class="active" data-tab="bt">Backtest</button>
    <button data-tab="rl">Rules</button>
    <button data-tab="pr">Predict</button>
  </div>
</header>

<main>
<div class="ctrl">
  <label>Venue:</label><select id="fVenue"><option value="">All</option></select>
  <label>From:</label><input type="date" id="fFrom">
  <label>To:</label><input type="date" id="fTo">
  <button class="btn-run" id="btnRun">Run</button>
</div>
<div class="live-f" id="lf">
  <label>Dist:</label><select id="fDist"><option value="">All</option></select>
  <label>Go:</label><select id="fGo"><option value="">All</option></select>
  <label>Top1:</label><select id="fTop"><option value="">All</option></select>
  <label>Lab:</label><select id="fLab"><option value="">All</option></select>
  <label>LabCt:</label><select id="fLabCt"><option value="">All</option><option>1</option><option>2</option><option>3</option></select>
  <label>32/33:</label><select id="fXx"><option value="">All</option></select>
  <label>XxCt:</label><select id="fXxCt"><option value="">All</option><option>1</option><option>2</option><option>3</option></select>
  <label>Has:</label><input type="text" id="fHas" placeholder="18,19" style="width:60px">
</div>
<div id="loading">Loading...</div>

<div id="tabB" class="tab-panel active">
  <div class="sgrid" id="statsRow"></div>
  <div class="card">
    <h3>Races <span id="rc"></span></h3>
    <table><thead><tr><th>Date</th><th>V</th><th>R#</th><th>Dist</th><th>Go</th><th>Prop String</th></tr></thead><tbody id="rb"></tbody></table>
  </div>
  <div class="card" id="rd" style="display:none"></div>
</div>

<div id="tabR" class="tab-panel">
  <div class="sgrid" id="rulesStats"></div>
  <div class="ctrl" style="margin-bottom:6px">
    <label>Rules venue:</label>
    <select id="fRulesVenue"><option value="ST">ST</option><option value="HV">HV</option><option value="Sx">Sx</option></select>
  </div>
  <div class="card"><h3>Mined Rules <span id="rlC"></span> <small id="rlMeta" style="color:#8899aa"></small></h3>
    <table><thead><tr><th>#</th><th>Rule</th><th>Sup</th><th>Hit</th><th>Acc</th><th>Base</th><th>Lift</th></tr></thead><tbody id="rlB"></tbody></table>
  </div>
</div>

<div id="tabP" class="tab-panel"><div id="pb"></div></div>
</main>

<script>
$(function(){
  var AR=[],ML=[],PD=null;

  function api(a,p){return $.getJSON('prop_api.php',$.extend({action:a},p||{}))}

  api('venues').done(function(v){
    var s=$('#fVenue').empty().append('<option value="">All</option>');
    var sx=false;
    $.each(v,function(_,x){
      if(/^S\d+$/.test(x)){
        if(!sx){s.append('<option value="Sx_group">Sx (S1-S9)</option>');sx=true}
      }else{s.append('<option>'+x+'</option>')}
    });
  }).fail(function(){console.log('venues failed')});

  // Default From = Sept 1 of (current year - 2); To = today until list_dates returns
  var defFrom = (new Date().getFullYear() - 2) + '-09-01';
  var defTo = (function(){
    var d=new Date();
    return d.getFullYear()+'-'+('0'+(d.getMonth()+1)).slice(-2)+'-'+('0'+d.getDate()).slice(-2);
  })();
  $('#fFrom').val(defFrom);
  $('#fTo').val(defTo);

  api('list_dates').done(function(d){
    if(d.length) $('#fTo').val(d[0]);
    // From stays Sept 1 of (year-2) as requested
    load();
  }).fail(function(){
    console.log('dates failed');
    load();
  });

  // ---- style helper ----
  function pcl(v){
    if(v>=50)return's50';if(v>=40)return's40';if(v>=30)return's30';if(v>=20)return's20';if(v>=10)return's10';return's0'
  }
  function fcl(fp){
    if(fp===1)return'f1';if(fp===2)return'f2';if(fp===3)return'f3';if(fp===4)return'f4';return''
  }
  function top1(vals){
    var t=0;if(vals.length)t=vals[0];
    return t>=70?'70-79':(t>=60?'60-69':(t>=50?'50-59':(t>=40?'40-49':(t>=30?'30-39':(t>=20?'20-29':'10-19')))))
  }

  function load(){
    var ven=$('#fVenue').val(),fr=$('#fFrom').val(),to=$('#fTo').val();
    // Never call predict with empty dates (full history OOMs → 500)
    if(!fr){fr=defFrom;$('#fFrom').val(fr)}
    if(!to){to=defTo;$('#fTo').val(to)}
    var params={date_from:fr,date_to:to};
    if(ven==='Sx_group'){params.venue_like='S1,S2,S3,S4,S5,S6,S7,S8,S9'}else if(ven){params.venue=ven}
    $('#loading').text('Loading...').show();
    api('predict',params)
    .done(function(d){
      if(d.error){$('#loading').text('Error: '+d.error).show();return}
      if(!d||!d.races){$('#loading').text('No races loaded').show();return}
      AR=d.races;ML=d.mined_rules||[];PD=d;
      if(d.rules_venue)$('#fRulesVenue').val(d.rules_venue);

      var dists={},gos={},tops={},labs={},xx={};
      $.each(AR,function(i,r){
        dists[r.distance]=1;gos[r.go_ch||'']=1;tops[top1(r.prop_order)]=1;
        if(r.labels)$.each(r.labels,function(j,L){if(L){labs[L]=1;if(L==='32x'||L==='33x')xx[L]=1}});
      });
      fillSel('#fDist',Object.keys(dists));fillSel('#fGo',Object.keys(gos));
      fillSel('#fTop',Object.keys(tops));fillSel('#fLab',Object.keys(labs));fillSel('#fXx',Object.keys(xx));
      $('#lf').addClass('on');
      refresh();renderRules();renderPredict();updateStats();
      $('#loading').hide();
    }).fail(function(jq,status,err){
      try{var o=JSON.parse(jq.responseText);$('#loading').text('Error: '+(o.error||status)).show()}
      catch(e){$('#loading').text('Error: '+err+' (HTTP '+(jq&&jq.status||'?')+')').show()}
    });
  }

  function fillSel(el,keys){var s=$(el).empty().append('<option value="">All</option>');$.each(keys,function(_,k){s.append('<option>'+k+'</option>')});}

  function refresh(){
    var d=$('#fDist').val(),g=$('#fGo').val(),t2=$('#fTop').val(),
    lab=$('#fLab').val(),lc=parseInt($('#fLabCt').val())||0,
    xx=$('#fXx').val(),xc=parseInt($('#fXxCt').val())||0,
    has=$('#fHas').val().trim();

    var f=AR.filter(function(r){
      if(d&&r.distance!=d)return false;
      if(g&&r['go_ch']!=g)return false;
      if(t2&&top1(r.prop_order)!==t2)return false;
      if(lab){var ok=false;if(r.labels)for(var j=0;j<r.labels.length;j++){if(r.labels[j]===lab){ok=true;break}}if(!ok)return false;}
      if(lc){var cnt=0,seen={};if(r.labels)for(var j=0;j<r.labels.length;j++){var Lb=r.labels[j];if(Lb&&/^[1234]x$/.test(Lb)&&!seen[Lb]){seen[Lb]=1;cnt++}}if(cnt!=lc)return false;}
      if(xx){var ok2=false;if(r.labels)for(var j=0;j<r.labels.length;j++){if(r.labels[j]===xx){ok2=true;break}}if(!ok2)return false;}
      if(xc){var cnt2=0;if(r.labels)for(var j=0;j<r.labels.length;j++){if(r.labels[j]===xx||r.labels[j]==='33x')cnt2++}if(cnt2!=xc)return false;}
      if(has&&r.prop_order){
        var nums=has.split(',').map(function(s){return parseInt(s.trim())});
        var found=false;
        for(var k=0;k<nums.length;k++){for(var p=0;p<r.prop_order.length;p++){if(r.prop_order[p]===nums[k]){found=true;break}}}if(!found)return false;}
      return true;
    });
    var rows='';
    for(var i=0;i<f.length;i++){
      var r=f[i];
      // Map finalPosition by original prop-order position (not probability rank)
      var fpByPos={};
      if(r.predictions){
        for(var k=0;k<r.predictions.length;k++){
          var pj=r.predictions[k];
          if(pj&&pj.position!=null) fpByPos[pj.position]=parseInt(pj.finalPosition)||null;
        }
      }
      var prs='<div class="prop-list">';
      for(var j=0;j<r.horse_count;j++){
        var v=r.prop_order[j];
        var cls='pp '+pcl(v);
        var fp=fpByPos[j]!=null?fpByPos[j]:null;
        if(fp===1||fp===2||fp===3||fp===4)cls+=' '+fcl(fp);
        if(r.symptoms){
          if(r.symptoms.B2_pos===j) cls+=' hb';
          if(r.symptoms.B3_pos===j) cls+=' hb';
          if(r.symptoms.B4_pos===j) cls+=' hb';
        }
        if(r.labels&&r.labels[j]){
          var ll=r.labels[j];
          if(ll==='2x'||ll==='3x'||ll==='4x') cls+=' lx';
          if(ll==='32x'||ll==='33x'||ll==='34x') cls+=' lxx';
        }
        prs+='<span class="'+cls+'">'+v+'</span>';
      }
      prs+='</div>';
      rows+='<tr class="race-row" data-rk="'+r.race_key+'">'+
        '<td>'+r.racingdate+'</td><td>'+r.venue+'</td><td>R'+r.raceno+'</td>'+
        '<td>'+r.distance+'</td><td>'+r.go_ch+'</td>'+
        '<td style="max-width:400px">'+prs+'</td></tr>';
    }
    document.getElementById('rb').innerHTML=rows;
    document.getElementById('rc').textContent='('+f.length+')';
  }

  function renderRules(){
    var vk=$('#fRulesVenue').val()||'ST';
    var m=ML;
    var meta='';
    if(PD&&PD.venue_rules&&PD.venue_rules[vk]){
      m=PD.venue_rules[vk].rules||[];
      var vr=PD.venue_rules[vk];
      meta='train='+(vr.train_venue||vk)+' races='+(vr.train_races||'?')+' horses='+(vr.train_horses||'?')+' base='+(vr.baseline||'?')+'%';
    }
    var rows='';
    if(!m.length) rows='<tr><td colspan="7">No rules for '+vk+'</td></tr>';
    else for(var i=0;i<m.length;i++){
      var rr=m[i]; var c=rr.accuracy>=80?'#86efac':(rr.accuracy>=50?'#fcd34d':'#fca5a5');
      rows+='<tr><td>'+(i+1)+'</td><td>'+rr.rule_name+'</td><td>'+rr.support+'</td>'+
        '<td>'+rr.hit+'</td><td style="color:'+c+'">'+rr.accuracy+'%</td>'+
        '<td>'+rr.baseline+'%</td><td>'+rr.lift+'</td></tr>';
    }
    $('#rlB').html(rows);
    $('#rlC').text('('+m.length+')');
    $('#rlMeta').text(meta);
    var avg=0;if(m.length){for(var i=0;i<m.length;i++)avg+=m[i].accuracy;avg=Math.round(avg/m.length)}
    var html='<div class="sc"><span class="v">'+m.length+'</span><span class="l">'+vk+' Rules</span></div>'+
      '<div class="sc"><span class="v">'+avg+'%</span><span class="l">Avg Acc</span></div>';
    if(PD&&PD.venue_rules){
      $.each(['ST','HV','Sx'],function(_,k){
        var n=(PD.venue_rules[k]&&PD.venue_rules[k].rules)?PD.venue_rules[k].rules.length:0;
        var hr=(PD.venue_rules[k]&&PD.venue_rules[k].train_horses)?PD.venue_rules[k].train_horses:0;
        html+='<div class="sc"><span class="v">'+n+'</span><span class="l">'+k+' ('+hr+'h)</span></div>';
      });
    }
    $('#rulesStats').html(html);
  }

  function renderPredict(){
    var html='';if(!PD||!PD.races)return;
    if(PD.metrics){
      var m=PD.metrics;
      html+='<div class="sgrid" style="margin-bottom:8px">'+
        '<div class="sc"><span class="v">'+m.top1_place_pct+'%</span><span class="l">Top1 Place</span></div>'+
        '<div class="sc"><span class="v">'+m.top3_place_pct+'%</span><span class="l">Top3 Place</span></div>'+
        '<div class="sc"><span class="v">'+m.race_any_top3_pct+'%</span><span class="l">Race Any Top3</span></div>'+
        '<div class="sc"><span class="v">'+(m.finalpick3_place_pct||0)+'%</span><span class="l">FinalPick3</span></div>'+
        '<div class="sc"><span class="v">'+(m.rules_only_place_pct||0)+'%</span><span class="l">RulesOnly Pick3</span></div>'+
        '</div>';
    }
    for(var i=0;i<PD.races.length;i++){
      var r=PD.races[i];
      html+='<div class="card"><h3>'+r.race_key+' <small>'+r.venue+' '+r.distance+'m</small></h3>';
      if(r.finalpicks&&r.finalpicks.length){
        html+='<div style="margin-bottom:4px;font-size:.75rem;color:#8899aa">Blend picks: ';
        for(var pi=0;pi<r.finalpicks.length;pi++){
          var pk=r.finalpicks[pi];
          var hit=pk.finalPosition&&pk.finalPosition<=3;
          html+='<span class="bdg'+(hit?' bdg-g':'')+'" style="margin-right:4px">#'+(pi+1)+' pos'+pk.position+' p'+pk.prop_val+(pk.finalPosition?' FP'+pk.finalPosition:'')+'</span>';
        }
        html+='</div>';
      }
      if(r.finalpicks_rules&&r.finalpicks_rules.length){
        html+='<div style="margin-bottom:6px;font-size:.75rem;color:#8899aa">Rules-only picks: ';
        for(var pi=0;pi<r.finalpicks_rules.length;pi++){
          var pk=r.finalpicks_rules[pi];
          var hit=pk.finalPosition&&pk.finalPosition<=3;
          html+='<span class="bdg'+(hit?' bdg-y':'')+'" style="margin-right:4px">#'+(pi+1)+' pos'+pk.position+' p'+pk.prop_val+' rh'+pk.rule_hits+' L'+pk.totalLift+(pk.finalPosition?' FP'+pk.finalPosition:'')+'</span>';
        }
        html+='</div>';
      }
      html+='<table><thead><tr><th>#</th><th>Horse</th><th>Prop</th><th>P1</th><th>P2</th><th>Prob</th><th>Rules</th><th>Actual</th></tr></thead><tbody>';
      $.each(r.predictions,function(j,p){
        var p1=Math.round(p.baseProb*100),p2=Math.round(p.ruleProb*100),pct=Math.round(p.probability*100);
        var lb=p.label||'',pcl2='';
        if(lb==='2x'||lb==='3x'||lb==='4x') pcl2='lx';
        if(lb==='32x'||lb==='33x'||lb==='34x') pcl2='lxx';
        html+='<tr style="background:'+(p.finalPosition&&p.finalPosition<=3?'rgba(80,230,120,0.12)':'')+'">'+
          '<td>'+(j+1)+'</td><td>'+p.horse+'</td>'+
          '<td><span class="pp '+pcl2+'">'+p.prop_val+'</span></td>'+
          '<td style="color:#888">'+p1+'%</td>'+
          '<td style="color:'+(p2>0?'#ffff00':'#444')+'">'+p2+'%</td>'+
          '<td><div class="prb"><div class="fhi" style="width:'+pct+'%"></div></div> '+pct+'%</td>'+
          '<td>'+p.rule_hits+'</td>'+
          '<td>'+(p.finalPosition||'')+'</td></tr>';
      });
      html+='</tbody></table></div>';
    }
    document.getElementById('pb').innerHTML=html;
  }

  function updateStats(){
    // Recompute pick metrics from currently filtered races
    var f=AR.filter(function(r){
      var d=$('#fDist').val(),g=$('#fGo').val(),t2=$('#fTop').val(),
      lab=$('#fLab').val(),lc=parseInt($('#fLabCt').val())||0,
      xx=$('#fXx').val(),xc=parseInt($('#fXxCt').val())||0,
      has=$('#fHas').val().trim();
      if(d&&r.distance!=d)return false;
      if(g&&r['go_ch']!=g)return false;
      if(t2&&top1(r.prop_order)!==t2)return false;
      if(lab){var ok=false;if(r.labels)for(var j=0;j<r.labels.length;j++){if(r.labels[j]===lab){ok=true;break}}if(!ok)return false;}
      if(lc){var cnt=0,seen={};if(r.labels)for(var j=0;j<r.labels.length;j++){var Lb=r.labels[j];if(Lb&&/^[1234]x$/.test(Lb)&&!seen[Lb]){seen[Lb]=1;cnt++}}if(cnt!=lc)return false;}
      if(xx){var ok2=false;if(r.labels)for(var j=0;j<r.labels.length;j++){if(r.labels[j]===xx){ok2=true;break}}if(!ok2)return false;}
      if(xc){var cnt2=0;if(r.labels)for(var j=0;j<r.labels.length;j++){if(r.labels[j]===xx||r.labels[j]==='33x')cnt2++}if(cnt2!=xc)return false;}
      if(has&&r.prop_order){
        var nums=has.split(',').map(function(s){return parseInt(s.trim())});
        var found=false;
        for(var k=0;k<nums.length;k++){for(var p=0;p<r.prop_order.length;p++){if(r.prop_order[p]===nums[k]){found=true;break}}}if(!found)return false;}
      return true;
    });
    var t1h=0,t1t=0,t3h=0,t3t=0,rah=0,rat=0,fp3h=0,fp3t=0,roh=0,rot=0;
    for(var i=0;i<f.length;i++){
      var preds=f[i].predictions||[];
      if(!preds.length)continue;
      var hasOut=false;
      for(var j=0;j<preds.length;j++){if(preds[j].finalPosition>0){hasOut=true;break}}
      if(!hasOut)continue;
      rat++;t1t++;
      if(preds[0].finalPosition>0&&preds[0].finalPosition<=3)t1h++;
      var any=false;
      var lim=Math.min(3,preds.length);
      for(var j=0;j<lim;j++){
        t3t++;
        if(preds[j].finalPosition>0&&preds[j].finalPosition<=3){t3h++;any=true}
      }
      if(any)rah++;
      var pks=f[i].finalpicks||[];
      for(var j=0;j<pks.length;j++){
        fp3t++;
        if(pks[j].finalPosition>0&&pks[j].finalPosition<=3)fp3h++;
      }
      var rks=f[i].finalpicks_rules||[];
      for(var j=0;j<rks.length;j++){
        rot++;
        if(rks[j].finalPosition>0&&rks[j].finalPosition<=3)roh++;
      }
    }
    function pct(h,t){return t?Math.round(1000*h/t)/10:0}
    var html='';
    html+='<div class="sc"><span class="v">'+f.length+'</span><span class="l">Races</span></div>';
    html+='<div class="sc"><span class="v">'+pct(t1h,t1t)+'%</span><span class="l">Top1 Place ('+t1h+'/'+t1t+')</span></div>';
    html+='<div class="sc"><span class="v">'+pct(t3h,t3t)+'%</span><span class="l">Top3 Place ('+t3h+'/'+t3t+')</span></div>';
    html+='<div class="sc"><span class="v">'+pct(rah,rat)+'%</span><span class="l">Race Hit ('+rah+'/'+rat+')</span></div>';
    html+='<div class="sc"><span class="v">'+pct(fp3h,fp3t)+'%</span><span class="l">FinalPick3 ('+fp3h+'/'+fp3t+')</span></div>';
    html+='<div class="sc"><span class="v">'+pct(roh,rot)+'%</span><span class="l">RulesOnly ('+roh+'/'+rot+')</span></div>';
    html+='<div class="sc"><span class="v">'+ML.length+'</span><span class="l">Rules</span></div>';
    if(PD&&PD.holdout) html+='<div class="sc"><span class="v">OOS</span><span class="l">Train &lt; '+PD.train_before+'</span></div>';
    $('#statsRow').html(html);
  }

  $('.live-f select,.live-f input').on('change',function(){refresh();updateStats()});
  $('#fRulesVenue').on('change',function(){renderRules()});
  $('#btnRun').click(function(){load()});
  $('.nv button').click(function(){
    var t=$(this).data('tab');
    $('.nv button').removeClass('active');$(this).addClass('active');
    $('.tab-panel').removeClass('active').hide();
    if(t==='bt'){$('#tabB').addClass('active').show()}
    if(t==='rl'){$('#tabR').addClass('active').show();renderRules()}
    if(t==='pr'){$('#tabP').addClass('active').show();renderPredict()}
  });
  $('body').on('click','.race-row',function(){
    var rk=$(this).data('rk');
    var r=null;
    $.each(AR,function(i,x){if(x.race_key===rk){r=x;return false}});
    if(!r)return;
    var html='<h3>'+r.race_key+' <small>'+r.venue+' '+r.distance+'m</small></h3>';
    html+='<table><thead><tr><th>#</th><th>Horse</th><th>Prop</th><th>Prob</th><th>Rules</th><th>Actual</th></tr></thead><tbody>';
    $.each(r.predictions,function(j,p){
      var pct=Math.round(p.probability*100);
      var clr=pct>=20?'fhi':(pct>=10?'flo':'');
      var lb=p.label||'',pcl2='';
      if(lb==='2x'||lb==='3x'||lb==='4x') pcl2='lx';
      if(lb==='32x'||lb==='33x'||lb==='34x') pcl2='lxx';
      html+='<tr style="background:'+(p.finalPosition<=3?'rgba(80,230,120,0.12)':'')+'">'+
        '<td>'+p.position+'</td><td>'+p.horse+'</td>'+
        '<td><span class="pp '+pcl2+'">'+p.prop_val+'</span></td>'+
        '<td><div class="prb"><div class="'+clr+'" style="width:'+pct+'%"></div></div> '+pct+'%</td>'+
        '<td>'+p.rule_hits+'</td><td>'+(p.finalPosition||'')+'</td></tr>';
    });
    html+='</tbody></table>';
    $('#rd').show().html(html);
  });

  // load() is called after list_dates sets From/To (see above)
});
</script>
</body></html>