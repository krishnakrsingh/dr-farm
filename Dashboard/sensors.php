<?php
require_once __DIR__ . '/includes/lang.php';
$pageTitle   = __('nav_sensors');
$currentPage = 'sensors';
$extraHead   = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ═══ Live Sensor Panel ═══ -->
<div class="panel animate-in" style="margin-bottom:1.25rem">
    <div class="panel-header">
        <div class="panel-title">📡 <?= __('realtime_readings') ?></div>
        <span class="panel-badge" id="liveBadge"><?= __('waiting') ?></span>
    </div>
    <div class="sensor-grid" id="sensorGrid">
        <div class="sensor-item"><div class="s-icon">🌡️</div><div class="s-value" id="sT">—</div><div class="s-label"><?= __('temperature') ?></div><div class="s-unit">°C</div></div>
        <div class="sensor-item"><div class="s-icon">💧</div><div class="s-value" id="sH">—</div><div class="s-label"><?= __('humidity') ?></div><div class="s-unit">%</div></div>
        <div class="sensor-item"><div class="s-icon">🔥</div><div class="s-value" id="sM7">—</div><div class="s-label"><?= __('mq7_gas') ?></div><div class="s-unit">ppm</div></div>
        <div class="sensor-item"><div class="s-icon">🧪</div><div class="s-value" id="sM3">—</div><div class="s-label"><?= __('mq3') ?></div><div class="s-unit">ppm</div></div>
        <div class="sensor-item"><div class="s-icon">🌧️</div><div class="s-value" id="sR">—</div><div class="s-label"><?= __('rain') ?></div><div class="s-unit" id="sRU">—</div></div>
        <div class="sensor-item"><div class="s-icon">📍</div><div class="s-value" id="sN">—</div><div class="s-label"><?= __('node_id') ?></div><div class="s-unit" id="sTs">—</div></div>
    </div>
</div>

<!-- ═══ Charts ═══ -->
<div class="dash-grid" style="margin-bottom:1.25rem">
    <div class="panel animate-in">
        <div class="panel-header"><div class="panel-title">🌡️ <?= __('temp_over_time') ?></div></div>
        <div class="chart-wrap"><canvas id="cTemp"></canvas></div>
    </div>
    <div class="panel animate-in">
        <div class="panel-header"><div class="panel-title">💧 <?= __('humidity_over_time') ?></div></div>
        <div class="chart-wrap"><canvas id="cHum"></canvas></div>
    </div>
</div>

<div class="dash-grid">
    <div class="panel animate-in">
        <div class="panel-header"><div class="panel-title">🔥 <?= __('gas_sensors') ?></div></div>
        <div class="chart-wrap"><canvas id="cGas"></canvas></div>
    </div>
    <div class="panel animate-in">
        <div class="panel-header">
            <div class="panel-title">🖧 <?= __('node_status') ?></div>
            <span class="panel-badge" id="nodeBdg">0</span>
        </div>
        <ul class="node-list" id="nList"><li class="empty-state"><div class="es-icon">📡</div><?= __('no_nodes') ?></li></ul>
    </div>
</div>

<!-- ═══ History Table ═══ -->
<div class="panel animate-in" style="margin-top:1.25rem">
    <div class="panel-header"><div class="panel-title">📋 <?= __('recent_readings') ?></div></div>
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:0.82rem">
            <thead><tr style="border-bottom:2px solid var(--border);text-align:left">
                <th style="padding:0.5rem"><?= __('time') ?></th><th style="padding:0.5rem"><?= __('node') ?></th><th style="padding:0.5rem"><?= __('temperature') ?></th>
                <th style="padding:0.5rem"><?= __('humidity') ?></th><th style="padding:0.5rem"><?= __('mq7_gas') ?></th><th style="padding:0.5rem"><?= __('mq3') ?></th><th style="padding:0.5rem"><?= __('rain') ?></th>
            </tr></thead>
            <tbody id="sTable"><tr><td colspan="7" class="empty-state"><?= __('loading') ?></td></tr></tbody>
        </table>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
let cTemp,cHum,cGas;
const chartOpts = {
    responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false}},
    scales:{x:{grid:{display:false},ticks:{font:{size:10},maxTicksLimit:10}},y:{grid:{color:'#e2e8f022'},ticks:{font:{size:10}}}},
    elements:{point:{radius:2,hoverRadius:5},line:{tension:0.35,borderWidth:2}}
};

function initCharts(){
    cTemp=new Chart($('#cTemp'),{type:'line',data:{labels:[],datasets:[{label:LANG.temperature,data:[],borderColor:'#ef4444',backgroundColor:'#ef444418',fill:true}]},options:chartOpts});
    cHum=new Chart($('#cHum'),{type:'line',data:{labels:[],datasets:[{label:LANG.humidity,data:[],borderColor:'#3b82f6',backgroundColor:'#3b82f618',fill:true}]},options:chartOpts});
    cGas=new Chart($('#cGas'),{type:'line',data:{labels:[],datasets:[
        {label:'MQ7',data:[],borderColor:'#f59e0b',backgroundColor:'#f59e0b18',fill:true},
        {label:'MQ3',data:[],borderColor:'#8b5cf6',backgroundColor:'#8b5cf618',fill:true}
    ]},options:{...chartOpts,plugins:{legend:{display:true,position:'bottom',labels:{boxWidth:12,font:{size:11}}}}}});
}

async function loadLive(){
    try{const d=await api('get_latest.php');if(d.error)return;
        $('#sT').textContent=d.temperature;$('#sH').textContent=d.humidity;
        $('#sM7').textContent=d.mq7;$('#sM3').textContent=d.mq3;
        $('#sR').textContent=d.rain>0?LANG.yes:LANG.no;$('#sRU').textContent=d.rain>0?'🌧':'☀';
        $('#sN').textContent=d.node_id;
        if(d.created_at)$('#sTs').textContent=new Date(d.created_at).toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'});
        $('#liveBadge').textContent=LANG.live;$('#liveBadge').className='panel-badge';
    }catch(e){console.error(e);}
}

async function loadHistory(){
    try{
        const s=await api('api_history.php?type=sensor&limit=50');if(!Array.isArray(s)||!s.length)return;
        const lbl=s.map(r=>new Date(r.created_at).toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'}));
        cTemp.data.labels=lbl;cTemp.data.datasets[0].data=s.map(r=>+r.temperature);cTemp.update();
        cHum.data.labels=lbl;cHum.data.datasets[0].data=s.map(r=>+r.humidity);cHum.update();
        cGas.data.labels=lbl;cGas.data.datasets[0].data=s.map(r=>+r.mq7);cGas.data.datasets[1].data=s.map(r=>+r.mq3);cGas.update();
        const recent=s.slice(-20).reverse();
        $('#sTable').innerHTML=recent.map(r=>`<tr style="border-bottom:1px solid var(--border)">
            <td style="padding:0.4rem 0.5rem">${r.created_at}</td><td style="padding:0.4rem 0.5rem">${r.node_id}</td>
            <td style="padding:0.4rem 0.5rem">${r.temperature}°C</td><td style="padding:0.4rem 0.5rem">${r.humidity}%</td>
            <td style="padding:0.4rem 0.5rem">${r.mq7}</td><td style="padding:0.4rem 0.5rem">${r.mq3}</td>
            <td style="padding:0.4rem 0.5rem">${+r.rain?'🌧 '+LANG.yes:'☀ '+LANG.no}</td>
        </tr>`).join('');
    }catch(e){console.error(e);}
}

async function loadNodes(){
    try{const d=await api('api_history.php?type=nodes');const l=$('#nList');
        if(!Array.isArray(d)||!d.length){l.innerHTML='<li class="empty-state"><div class="es-icon">📡</div>'+LANG.no_nodes+'</li>';return;}
        const on=d.filter(n=>n.status==='online').length;$('#nodeBdg').textContent=on+'/'+d.length+' '+LANG.online;
        l.innerHTML=d.map(n=>`<li class="node-item"><span class="node-dot ${n.status}"></span><span class="node-name">${n.node_id}</span><span class="node-seen">${n.last_seen?new Date(n.last_seen).toLocaleString('en-IN'):'Never'}</span></li>`).join('');
    }catch(e){console.error(e);}
}

document.addEventListener('DOMContentLoaded',async()=>{
    initCharts();
    await Promise.allSettled([loadLive(),loadHistory(),loadNodes()]);
    setInterval(()=>{loadLive();loadNodes();},15000);
    setInterval(loadHistory,60000);
});
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
