<?php
require_once __DIR__ . '/includes/lang.php';
$pageTitle   = __('nav_dashboard');
$currentPage = 'dashboard';
$extraHead   = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ═══ Hero Cards ═══ -->
<div class="hero-grid">
    <div class="hero-card green animate-in">
        <div class="card-icon">💚</div>
        <div class="card-label"><?= __('farm_health_score') ?></div>
        <div class="card-value" id="heroHealth">—</div>
        <div class="card-sub" id="heroHealthSub"><?= __('calculating') ?></div>
    </div>
    <div class="hero-card red animate-in">
        <div class="card-icon">⚠️</div>
        <div class="card-label"><?= __('risk_percentage') ?></div>
        <div class="card-value" id="heroRisk">—</div>
        <div class="card-sub" id="heroRiskSub"><?= __('calculating') ?></div>
    </div>
    <div class="hero-card yellow animate-in">
        <div class="card-icon">🔬</div>
        <div class="card-label"><?= __('latest_disease') ?></div>
        <div class="card-value" id="heroDisease" style="font-size:1.25rem">—</div>
        <div class="card-sub" id="heroDiseaseSub"><?= __('no_detection_yet') ?></div>
    </div>
    <div class="hero-card blue animate-in">
        <div class="card-icon">🔔</div>
        <div class="card-label"><?= __('alert_status') ?></div>
        <div class="card-value" id="heroAlert">—</div>
        <div class="card-sub" id="heroAlertSub"><?= __('checking') ?></div>
    </div>
</div>

<!-- ═══ Risk Ring + Sensor ═══ -->
<div class="dash-grid">
    <div class="panel animate-in">
        <div class="panel-header">
            <div class="panel-title">📊 <?= __('risk_breakdown') ?></div>
            <span class="panel-badge" id="riskBadge"><?= __('low') ?></span>
        </div>
        <div class="ring-wrap">
            <div class="ring-container">
                <svg viewBox="0 0 160 160" width="130" height="130">
                    <circle class="ring-bg" cx="80" cy="80" r="65"/>
                    <circle class="ring-fill" id="healthRing" cx="80" cy="80" r="65"
                            stroke="var(--green)" stroke-dasharray="408.4" stroke-dashoffset="408.4"/>
                </svg>
                <div class="ring-label">
                    <div class="ring-val" id="ringHealth">0</div>
                    <div class="ring-sub"><?= __('health') ?></div>
                </div>
            </div>
            <div class="ring-container">
                <svg viewBox="0 0 160 160" width="130" height="130">
                    <circle class="ring-bg" cx="80" cy="80" r="65"/>
                    <circle class="ring-fill" id="riskRing" cx="80" cy="80" r="65"
                            stroke="var(--red)" stroke-dasharray="408.4" stroke-dashoffset="408.4"/>
                </svg>
                <div class="ring-label">
                    <div class="ring-val" id="ringRisk">0</div>
                    <div class="ring-sub"><?= __('risk') ?> %</div>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:1rem;justify-content:center;font-size:0.82rem;color:var(--text-2);margin-top:0.5rem">
            <span>🌡 <?= __('env_risk') ?>: <strong id="envRiskVal">0</strong>%</span>
            <span>🦠 <?= __('disease_risk') ?>: <strong id="disRiskVal">0</strong>%</span>
        </div>
    </div>

    <div class="panel animate-in">
        <div class="panel-header">
            <div class="panel-title">📡 <?= __('live_sensor_data') ?></div>
            <span class="panel-badge" id="sensorBadge"><?= __('waiting') ?></span>
        </div>
        <div class="sensor-grid">
            <div class="sensor-item"><div class="s-icon">🌡️</div><div class="s-value" id="sTemp">—</div><div class="s-label"><?= __('temperature') ?></div><div class="s-unit">°C</div></div>
            <div class="sensor-item"><div class="s-icon">💧</div><div class="s-value" id="sHum">—</div><div class="s-label"><?= __('humidity') ?></div><div class="s-unit">%</div></div>
            <div class="sensor-item"><div class="s-icon">🔥</div><div class="s-value" id="sMq7">—</div><div class="s-label"><?= __('mq7_gas') ?></div><div class="s-unit">ppm</div></div>
            <div class="sensor-item"><div class="s-icon">🧪</div><div class="s-value" id="sMq3">—</div><div class="s-label"><?= __('mq3') ?></div><div class="s-unit">ppm</div></div>
            <div class="sensor-item"><div class="s-icon">🌧️</div><div class="s-value" id="sRain">—</div><div class="s-label"><?= __('rain') ?></div><div class="s-unit" id="sRainU">—</div></div>
            <div class="sensor-item"><div class="s-icon">🕐</div><div class="s-value" id="sTime" style="font-size:0.9rem">—</div><div class="s-label"><?= __('updated') ?></div><div class="s-unit" id="sNode">—</div></div>
        </div>
    </div>
</div>

<!-- ═══ Charts ═══ -->
<div class="dash-grid">
    <div class="panel animate-in">
        <div class="panel-header"><div class="panel-title">📈 <?= __('temp_humidity_chart') ?></div></div>
        <div class="chart-wrap"><canvas id="chartTH"></canvas></div>
    </div>
    <div class="panel animate-in">
        <div class="panel-header"><div class="panel-title">📉 <?= __('risk_history') ?></div></div>
        <div class="chart-wrap"><canvas id="chartRisk"></canvas></div>
    </div>
</div>

<!-- ═══ Nodes + Alerts ═══ -->
<div class="dash-grid">
    <div class="panel animate-in">
        <div class="panel-header">
            <div class="panel-title">🖧 <?= __('node_connectivity') ?></div>
            <span class="panel-badge" id="nodeBadge">0 <?= __('nodes') ?></span>
        </div>
        <ul class="node-list" id="nodeList"><li class="empty-state"><div class="es-icon">📡</div><?= __('no_nodes') ?></li></ul>
    </div>
    <div class="panel animate-in">
        <div class="panel-header">
            <div class="panel-title">🚨 <?= __('recent_alerts') ?></div>
            <a href="alerts.php" class="btn btn-outline btn-sm"><?= __('view_all') ?></a>
        </div>
        <ul class="alert-list" id="alertList"><li class="empty-state"><div class="es-icon">✅</div><?= __('all_clear') ?></li></ul>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
function setRing(el, pct) { el.style.strokeDashoffset = 408.4 - (408.4 * Math.min(100, Math.max(0, pct)) / 100); }

let chartTH, chartR;
function initCharts() {
    const opts = {
        responsive:true, maintainAspectRatio:false,
        plugins:{legend:{position:'bottom',labels:{boxWidth:12,padding:14,font:{size:11}}}},
        scales:{x:{grid:{display:false},ticks:{font:{size:10},maxTicksLimit:10}},y:{grid:{color:'#e2e8f022'},ticks:{font:{size:10}}}},
        elements:{point:{radius:2,hoverRadius:5},line:{tension:0.35,borderWidth:2}}
    };
    chartTH = new Chart($('#chartTH'),{type:'line',data:{labels:[],datasets:[
        {label:LANG.temperature+' °C',data:[],borderColor:'#ef4444',backgroundColor:'#ef444418',fill:true},
        {label:LANG.humidity+' %',data:[],borderColor:'#3b82f6',backgroundColor:'#3b82f618',fill:true}
    ]},options:opts});
    chartR = new Chart($('#chartRisk'),{type:'line',data:{labels:[],datasets:[
        {label:LANG.risk+' %',data:[],borderColor:'#ef4444',backgroundColor:'#ef444418',fill:true},
        {label:LANG.health+' %',data:[],borderColor:'#22c55e',backgroundColor:'#22c55e18',fill:true}
    ]},options:opts});
}

async function loadRisk(){
    try{
        const d=await api('risk_engine.php');if(d.error)return;
        $('#heroHealth').textContent=d.farm_health_score+'%';
        $('#heroHealth').closest('.hero-card').className='hero-card animate-in '+(d.farm_health_score>=70?'green':d.farm_health_score>=40?'yellow':'red');
        $('#heroHealthSub').textContent=d.farm_health_score>=70?LANG.healthy_condition:d.farm_health_score>=40?LANG.needs_attention:LANG.critical;
        $('#heroRisk').textContent=d.risk_percentage+'%';
        $('#heroRisk').closest('.hero-card').className='hero-card animate-in '+(d.risk_percentage<=30?'green':d.risk_percentage<=60?'yellow':'red');
        $('#heroRiskSub').textContent=d.risk_percentage<=30?LANG.low_risk:d.risk_percentage<=60?LANG.moderate_risk:LANG.high_risk;
        $('#heroDisease').textContent=d.disease_name||'None';
        $('#heroDiseaseSub').textContent=d.disease_confidence?d.disease_confidence+'% • '+d.disease_severity:LANG.no_detections;
        setRing($('#healthRing'),d.farm_health_score);setRing($('#riskRing'),d.risk_percentage);
        $('#ringHealth').textContent=d.farm_health_score;$('#ringRisk').textContent=d.risk_percentage;
        $('#envRiskVal').textContent=d.env_risk;$('#disRiskVal').textContent=d.disease_risk;
        const b=$('#riskBadge');
        if(d.risk_percentage>65){b.textContent=LANG.critical;b.className='panel-badge danger';}
        else if(d.risk_percentage>40){b.textContent=LANG.moderate;b.className='panel-badge warn';}
        else{b.textContent=LANG.low;b.className='panel-badge';}
        if(d.alert_triggered){$('#heroAlert').textContent='🔴 '+LANG.active;$('#heroAlert').style.color='var(--red)';$('#heroAlertSub').textContent=LANG.alert_triggered;}
        else{$('#heroAlert').textContent='🟢 '+LANG.clear;$('#heroAlert').style.color='var(--green)';$('#heroAlertSub').textContent=LANG.no_active_alerts;}
    }catch(e){console.error(e);}
}

async function loadSensor(){
    try{const d=await api('get_latest.php');if(d.error)return;
        $('#sTemp').textContent=d.temperature;$('#sHum').textContent=d.humidity;
        $('#sMq7').textContent=d.mq7;$('#sMq3').textContent=d.mq3;
        $('#sRain').textContent=d.rain>0?LANG.yes:LANG.no;$('#sRainU').textContent=d.rain>0?'🌧 '+LANG.detected:'☀ '+LANG.dry;
        $('#sNode').textContent=d.node_id||'—';
        if(d.created_at)$('#sTime').textContent=new Date(d.created_at).toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'});
        $('#sensorBadge').textContent=LANG.live;$('#sensorBadge').className='panel-badge';
    }catch(e){console.error(e);}
}

async function loadCharts(){
    try{
        const s=await api('api_history.php?type=sensor&limit=30');
        if(Array.isArray(s)&&s.length){
            chartTH.data.labels=s.map(r=>new Date(r.created_at).toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'}));
            chartTH.data.datasets[0].data=s.map(r=>+r.temperature);chartTH.data.datasets[1].data=s.map(r=>+r.humidity);chartTH.update();
        }
        const rr=await api('api_history.php?type=risk&limit=20');
        if(Array.isArray(rr)&&rr.length){
            chartR.data.labels=rr.map(r=>new Date(r.created_at).toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'}));
            chartR.data.datasets[0].data=rr.map(r=>+r.risk_percentage);chartR.data.datasets[1].data=rr.map(r=>+r.farm_health_score);chartR.update();
        }
    }catch(e){console.error(e);}
}

async function loadNodes(){
    try{const d=await api('api_history.php?type=nodes');const l=$('#nodeList');
        if(!Array.isArray(d)||!d.length){l.innerHTML='<li class="empty-state"><div class="es-icon">📡</div>'+LANG.no_nodes+'</li>';return;}
        const on=d.filter(n=>n.status==='online').length;$('#nodeBadge').textContent=on+'/'+d.length+' '+LANG.online;
        l.innerHTML=d.map(n=>`<li class="node-item"><span class="node-dot ${n.status}"></span><span class="node-name">${n.node_id}</span><span class="node-seen">${n.last_seen?new Date(n.last_seen).toLocaleString('en-IN'):'Never'}</span></li>`).join('');
    }catch(e){console.error(e);}
}

async function loadAlerts(){
    try{const d=await api('api_history.php?type=alerts&limit=8');const l=$('#alertList');
        if(!Array.isArray(d)||!d.length){l.innerHTML='<li class="empty-state"><div class="es-icon">✅</div>'+LANG.all_clear+'</li>';return;}
        l.innerHTML=d.map(a=>{
            const ch=a.channel||'system';const cls=ch==='whatsapp'?'wa':ch==='gsm'?'gsm':'sys';
            return `<li class="alert-item"><span class="alert-dot ${+a.resolved?'resolved':cls}"></span><div><div class="alert-msg">${a.message||'Alert'}</div><div class="alert-time">${a.created_at||''} <span class="alert-channel ${cls}">${ch.toUpperCase()}</span></div></div></li>`;
        }).join('');
    }catch(e){console.error(e);}
}

document.addEventListener('DOMContentLoaded',async()=>{
    initCharts();
    await Promise.allSettled([loadSensor(),loadRisk(),loadNodes(),loadAlerts()]);
    await loadCharts();
    setInterval(()=>{loadSensor();loadNodes();},15000);
    setInterval(()=>{loadRisk();loadAlerts();loadCharts();},60000);
});
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
