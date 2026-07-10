<?php
session_start();
if (empty($_SESSION['logged_in'])) { header('Location: ../login.php'); exit; }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrfToken = $_SESSION['csrf_token'];
session_write_close(); // Session-Lock sofort freigeben (Proxies brauchen Zugriff)
// UX-Auth-Token-File schreiben (umgeht PHP-Session-Probleme mit CLI-Multi-Worker)
@file_put_contents(sys_get_temp_dir().'/lat_ux_'.hash('sha256',$csrfToken).'.tok','1');
// Load custom agent prompts from settings.json
$_sfPath = __DIR__ . '/settings.json';
$_sfData = file_exists($_sfPath) ? (json_decode(file_get_contents($_sfPath), true) ?? []) : [];
$_agentPrompts = [
    'sqeg'       => $_sfData['agent_prompt_sqeg']       ?? '',
    'ux'         => $_sfData['agent_prompt_ux']         ?? '',
    'pv'         => $_sfData['agent_prompt_pv']         ?? '',
    'pvrefine'   => $_sfData['agent_prompt_pvrefine']   ?? '',
    'pvconvert'  => $_sfData['agent_prompt_pvconvert']  ?? '',
];
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LAT · Landingpage Analyse</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script>(function(){var t=localStorage.getItem('lat_theme');var p=window.matchMedia('(prefers-color-scheme:dark)').matches;if(t==='dark'||(t===null&&p))document.documentElement.setAttribute('data-theme','dark')})();</script>
<style>
@font-face{font-family:'Geist Mono';src:url('https://r2.vercel-storage.com/geist-mono/GeistMono-Regular.woff2') format('woff2');font-weight:400;font-style:normal;font-display:swap}
:root {
  /* Surfaces */
  --bg:#F8FAFC; --bg2:#FFFFFF; --bg3:#F1F5F9; --bg4:#E2E8F0;
  /* Borders */
  --border:#E2E8F0; --border2:#CBD5E1;
  /* Text */
  --text:#0F172A; --text2:#475569; --text3:#94A3B8;
  /* Accent (Indigo) */
  --accent:#4F46E5; --accent2:#4338CA;
  --accent-bg:#EEF2FF; --accent-border:#C7D2FE;
  /* System */
  --green:#16A34A; --green-bg:#F0FDF4; --green-border:#BBF7D0;
  --amber:#D97706; --amber-bg:#FFFBEB; --amber-border:#FDE68A;
  --red:#DC2626; --red-bg:#FEF2F2; --red-border:#FECACA;
  --blue:#2563EB; --blue-bg:#EFF6FF; --blue-border:#BFDBFE;
  /* Radius */
  --radius-sm:6px; --radius:8px; --radius-lg:12px; --radius-xl:16px;
  /* Shadows */
  --shadow-sm:0 1px 2px rgba(15,23,42,.05);
  --shadow:0 1px 4px rgba(15,23,42,.08),0 0 0 1px rgba(15,23,42,.04);
  --shadow-md:0 4px 12px rgba(15,23,42,.10),0 0 0 1px rgba(15,23,42,.04);
  --shadow-lg:0 8px 24px rgba(15,23,42,.12);
}
[data-theme="dark"]{
  --bg:#0D1525; --bg2:#172035; --bg3:#09111D; --bg4:#1C2A42;
  --border:#1E2E4A; --border2:#233050;
  --text:#DCE4F0; --text2:#8296B4; --text3:#6278A0;
  --accent:#6366F1; --accent2:#818CF8;
  --accent-bg:#1A193D; --accent-border:#312E81;
  --green:#4ADE80; --green-bg:#0A2318; --green-border:#134D2E;
  --amber:#FB923C; --amber-bg:#1E1108; --amber-border:#4A2A0E;
  --red:#F87171; --red-bg:#1E0A0A; --red-border:#4A1414;
  --blue:#60A5FA; --blue-bg:#0A1528; --blue-border:#1A3060;
  --shadow-sm:0 1px 3px rgba(0,0,0,.5);
  --shadow:0 2px 8px rgba(0,0,0,.6),0 0 0 1px rgba(255,255,255,.04);
  --shadow-md:0 4px 16px rgba(0,0,0,.7),0 0 0 1px rgba(255,255,255,.04);
  --shadow-lg:0 8px 32px rgba(0,0,0,.8);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.5;font-size:14px}
a{color:inherit;text-decoration:none}
button{font-family:inherit}
.app-shell{display:flex;min-height:100vh}
.sidebar{
  width:220px;flex-shrink:0;position:fixed;top:0;left:0;bottom:0;z-index:100;
  background:var(--bg3);border-right:1px solid var(--border2);
  display:flex;flex-direction:column;overflow-y:auto;
}
.sidebar-logo{
  padding:0 20px;display:flex;align-items:center;gap:10px;
  border-bottom:1px solid var(--border);height:64px;flex-shrink:0;
}
.sidebar-brand{font-family:'Inter',sans-serif;font-size:13px;font-weight:700;color:var(--text2);letter-spacing:0.08em;text-transform:uppercase}
.brand-logo{
  height:28px;width:auto;display:block;flex-shrink:0;
}
.brand-icon-sm{
  width:32px;height:32px;background:linear-gradient(135deg,var(--accent),#818cf8);
  border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.brand-icon-sm svg{color:#fff}
.sidebar-nav{flex:1;padding:8px;display:flex;flex-direction:column}
.nav-section-label{
  font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;
  color:var(--text3);padding:12px 10px 4px;
}
.nav-item{
  display:flex;align-items:center;gap:9px;width:100%;
  padding:8px 10px;border:none;border-radius:var(--radius);background:none;
  cursor:pointer;text-align:left;color:var(--text2);margin-bottom:1px;
  transition:background .12s,color .12s;font-family:inherit;font-size:13px;font-weight:500;
}
.nav-item svg{flex-shrink:0;opacity:.6}
.nav-item:hover{background:var(--bg4);color:var(--text)}
.nav-item:hover svg{opacity:1}
.nav-item.active{background:var(--accent-bg);color:var(--accent);font-weight:600;border-left:2px solid var(--accent);padding-left:8px}
.nav-item.active svg{opacity:1}
.sidebar-footer{
  padding:12px 20px;border-top:1px solid var(--border);font-size:11px;
  color:var(--text3);display:flex;align-items:center;justify-content:space-between;
}
.sidebar-footer a{color:var(--text3);font-size:11px;transition:color .12s}
.sidebar-footer a:hover{color:var(--red)}
.theme-btn{background:none;border:none;cursor:pointer;color:var(--text3);padding:4px 6px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;transition:color .12s,background .12s}
.theme-btn:hover{color:var(--text2);background:var(--bg4)}
.theme-btn .icon-sun{display:none}
.theme-btn .icon-moon{display:block}
[data-theme="dark"] .theme-btn .icon-sun{display:block}
[data-theme="dark"] .theme-btn .icon-moon{display:none}
.main-content{margin-left:220px;flex:1;min-width:0;background:var(--bg)}
.workspace-header{border-bottom:1px solid var(--border);background:var(--bg);display:flex;flex-direction:column;align-items:stretch;position:sticky;top:0;z-index:50;padding-bottom:14px}
.workspace-header-inner{max-width:960px;margin:0 auto;padding:0 32px;display:flex;align-items:center;width:100%;gap:12px;height:52px;flex-shrink:0}
.workspace-header-form{max-width:960px;margin:0 auto;padding:0 32px;width:100%}
.header-input-row{display:flex;gap:10px;align-items:center;margin-bottom:8px}
.header-action-row{display:flex;gap:8px;align-items:center;margin-top:10px}
.workspace-header-form.input-dimmed{opacity:.4;pointer-events:none;transition:opacity .3s}
.workspace-title{font-size:14px;font-weight:600;color:var(--text)}
.workspace-divider{width:1px;height:16px;background:var(--border2);flex-shrink:0}
.workspace-subtitle{font-size:12px;color:var(--text3)}
.tool-panel{display:none}
.tool-panel.active{display:block}
.section-divider{display:flex;align-items:center;gap:12px;margin:28px 0 16px}
.section-divider-line{flex:1;height:1px;background:var(--border2)}
.section-divider-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:1.4px;color:var(--text2);white-space:nowrap}
.input-row{display:flex;gap:10px;align-items:center;margin:0 0 4px}
.url-input{
  flex:1;height:42px;padding:0 14px;border:1px solid var(--border2);border-radius:var(--radius);
  background:var(--bg);font-family:'Geist Mono','Courier New',monospace;font-size:13px;
  color:var(--text);outline:none;transition:border-color .15s,box-shadow .15s,background .15s;min-width:0;
}
.url-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-border);background:#fff}
.mode-toggle{display:flex;border:1px solid var(--border2);border-radius:var(--radius);overflow:hidden;flex-shrink:0;height:42px}
.mode-btn{height:100%;padding:0 14px;border:none;background:var(--bg3);cursor:pointer;font-size:12px;font-weight:600;color:var(--text2);transition:background .12s,color .12s;white-space:nowrap}
.mode-btn.active{background:var(--accent);color:#fff}
.btn-start{
  height:42px;padding:0 20px;background:var(--accent);color:#fff;
  border:none;border-radius:var(--radius);font-size:13px;font-weight:600;
  cursor:pointer;transition:all .15s;font-family:inherit;
  box-shadow:0 1px 3px rgba(79,70,229,.3),0 0 0 1px rgba(79,70,229,.2);flex-shrink:0;
  display:flex;align-items:center;gap:7px;white-space:nowrap;
}
.btn-start:hover{background:var(--accent2);transform:translateY(-1px);box-shadow:0 4px 12px rgba(79,70,229,.35)}
.btn-start:active{transform:translateY(0);box-shadow:var(--shadow-sm)}
.btn-start:focus-visible{outline:3px solid var(--accent-border);outline-offset:2px}
.btn-start:disabled{background:var(--bg4);color:var(--text3);box-shadow:none;transform:none;cursor:not-allowed}
.btn-demo{height:42px;padding:0 14px;border:1px dashed var(--border2);border-radius:var(--radius);background:var(--bg3);color:var(--text2);font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;white-space:nowrap;display:flex;align-items:center;gap:6px;transition:background .1s,border-color .1s;flex-shrink:0}
.btn-demo:hover{background:var(--bg4);border-color:var(--text3);color:var(--text)}
.btn-demo:disabled{opacity:.4;cursor:not-allowed}
/* Toggle Switch */
.toggle-switch{position:relative;display:inline-block;width:36px;height:20px;flex-shrink:0}
.toggle-switch input{opacity:0;width:0;height:0;position:absolute}
.toggle-slider{position:absolute;cursor:pointer;inset:0;background:var(--bg4);border-radius:20px;transition:.2s;border:1px solid var(--border2)}
.toggle-slider:before{content:'';position:absolute;width:14px;height:14px;left:2px;bottom:2px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 2px rgba(0,0,0,.15)}
.toggle-switch input:checked+.toggle-slider{background:var(--accent);border-color:var(--accent)}
.toggle-switch input:checked+.toggle-slider:before{transform:translateX(16px)}
/* Log Collapse */
.log-wrap{border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-top:8px}
.log-header{display:flex;justify-content:space-between;align-items:center;padding:8px 14px;cursor:pointer;background:var(--bg2);user-select:none;transition:background .1s}
.log-header:hover{background:var(--bg3)}
.log-header .log-chevron{transition:transform .2s;color:var(--text3);flex-shrink:0;transform:rotate(180deg)}
.log-wrap.collapsed .log-header .log-chevron{transform:rotate(0deg)}
.log-wrap.collapsed .log-box{display:none}
.log-wrap .log-box{border:none;border-top:1px solid var(--border);border-radius:0;margin-top:0}
.html-textarea{
  width:100%;height:120px;padding:10px 14px;border:1px solid var(--border2);border-radius:var(--radius);
  background:var(--bg3);font-family:'Geist Mono','Courier New',monospace;font-size:12px;
  color:var(--text);resize:vertical;outline:none;transition:border-color .15s,box-shadow .15s;
}
.html-textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-border);background:#fff}
.context-toggle{
  display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;
  color:var(--text3);border:none;background:none;cursor:pointer;padding:4px 0;transition:color .12s;
}
.context-toggle:hover{color:var(--accent)}
.context-fields{display:none;gap:12px;margin-top:12px}
.context-fields.visible{display:flex;flex-wrap:wrap;border-top:1px solid var(--border);padding-top:14px;margin-top:4px}
.ctx-field{display:flex;flex-direction:column;gap:4px;flex:1;min-width:180px}
.ctx-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text3)}
.ctx-input{
  height:36px;padding:0 10px;border:1px solid var(--border2);border-radius:var(--radius-sm);
  background:var(--bg3);font-family:inherit;font-size:13px;color:var(--text);outline:none;
  transition:border-color .12s,background .12s;
}
.ctx-input:focus{border-color:var(--accent);background:#fff}
.input-card{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--radius-lg);padding:24px;margin-bottom:20px;
  box-shadow:var(--shadow-sm);
}
.input-card.input-dimmed{opacity:.4;pointer-events:none;transition:opacity .3s}
#panel-sqeg>.input-card{border-left:4px solid var(--accent);padding:28px 28px 24px}
#progress-section .input-card{background:var(--bg3);border-color:var(--border);border-style:dashed;box-shadow:none;padding:16px 20px;margin-bottom:12px}
.card-header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.card-icon{
  width:38px;height:38px;background:var(--accent-bg);border:1px solid var(--accent-border);
  border-radius:var(--radius);display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.card-icon svg{color:var(--accent)}
.card-title{font-family:'Inter',sans-serif;font-size:16px;font-weight:700;color:var(--text)}
.card-sub{font-size:11px;color:var(--text3);margin-top:2px}
.card-actions{margin-left:auto;display:flex;gap:8px;align-items:center}
.url-display{
  font-family:'Geist Mono','Courier New',monospace;font-size:12px;color:var(--accent);
  background:var(--accent-bg);border:1px solid var(--accent-border);
  border-radius:var(--radius-sm);padding:5px 10px;display:none;word-break:break-all;margin-bottom:12px;
}
.btn-secondary{
  height:36px;padding:0 14px;background:var(--bg2);color:var(--text2);
  border:1px solid var(--border2);border-radius:var(--radius);font-size:12px;
  font-weight:500;cursor:pointer;transition:all .12s;font-family:inherit;
  display:flex;align-items:center;gap:5px;
}
.btn-secondary:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-bg)}
.btn-secondary:focus-visible{outline:3px solid var(--accent-border);outline-offset:2px}
.err-box{
  padding:12px 16px;background:var(--red-bg);border:1px solid var(--red-border);
  border-radius:var(--radius);color:var(--red);font-size:13px;
  display:flex;align-items:flex-start;gap:10px;margin-bottom:16px;
}
.progress-section{margin-bottom:20px}
.progress-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.progress-label{font-size:13px;font-weight:600;color:var(--text)}
.progress-pct{font-size:26px;font-weight:700;color:var(--accent);font-family:'Geist Mono','Courier New',monospace;line-height:1}
.progress-timer-stat{font-size:13px;color:var(--text3);font-family:'Geist Mono','Courier New',monospace}
.progress-bar-bg{height:8px;background:var(--bg4);border-radius:999px;overflow:hidden;margin-bottom:6px}
.progress-bar{
  height:100%;border-radius:999px;width:0%;
  background:linear-gradient(90deg,var(--accent),#818cf8);
  transition:width .35s cubic-bezier(.4,0,.2,1);position:relative;
}
.progress-bar::after{
  content:'';position:absolute;top:0;left:0;right:0;bottom:0;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.4),transparent);
  animation:shimmer 1.6s infinite;
}
@keyframes shimmer{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}
.loader-dots{display:flex;gap:5px;align-items:center;margin-bottom:12px}
.loader-dot{width:6px;height:6px;border-radius:50%;background:var(--accent);opacity:.3;animation:dotpulse 1.4s ease-in-out infinite}
.loader-dot:nth-child(2){animation-delay:.2s}
.loader-dot:nth-child(3){animation-delay:.4s}
@keyframes dotpulse{0%,80%,100%{opacity:.3;transform:scale(1)}40%{opacity:1;transform:scale(1.3)}}
.status-msg{font-size:12px;color:var(--text3);margin-top:4px;margin-bottom:8px}
.log-box{
  background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);
  padding:12px 14px;font-family:'Geist Mono','Courier New',monospace;font-size:11px;color:var(--text3);
  height:200px;overflow-y:auto;line-height:1.7;
}
.log-box .log-ok{color:var(--green)}
.log-box .log-err{color:var(--red)}
.log-box .log-info{color:var(--accent)}
.settings-section{margin-bottom:32px}
.settings-section-title{font-family:'Inter',sans-serif;font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px}
.settings-section-desc{font-size:13px;color:var(--text3);margin-bottom:16px}
/* API-Verbindungen */
.api-test-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)}
.api-test-row:last-child{border-bottom:none}
.api-test-info{display:flex;align-items:center;gap:12px}
.api-test-dot{width:9px;height:9px;border-radius:50%;background:var(--bg4);border:1.5px solid var(--border2);flex-shrink:0;transition:background .2s,border-color .2s}
.api-test-dot.ok{background:var(--green);border-color:var(--green)}
.api-test-dot.err{background:var(--red);border-color:var(--red)}
.api-test-dot.testing{background:var(--amber);border-color:var(--amber)}
/* === TOOLTIPS === */
[data-tip]{position:relative;cursor:default}
[data-tip]::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:var(--text);color:var(--bg);font-size:11px;font-family:'Inter',sans-serif;font-weight:400;line-height:1.6;white-space:pre-line;max-width:280px;text-align:left;padding:7px 11px;border-radius:6px;pointer-events:none;opacity:0;transition:opacity .15s;z-index:100}
[data-tip]:hover::after{opacity:1}
/* === CREDENTIAL SOURCE BADGES === */
.src-badge{display:inline-flex;align-items:center;font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;letter-spacing:.03em}
.src-badge.env{background:#16a34a1a;color:#16a34a;border:1px solid #16a34a33}
.src-badge.json{background:var(--accent-bg);color:var(--accent);border:1px solid var(--accent-border)}
.src-badge.none{background:var(--bg4);color:var(--text3);border:1px solid var(--border)}
.cred-status-chip{display:inline-flex;align-items:center;gap:5px;font-size:11px;padding:4px 10px;border-radius:20px;border:1px solid var(--border);background:var(--bg2)}
.cred-status-chip.ok{background:#16a34a1a;border-color:#16a34a33;color:#16a34a}
.cred-status-chip.warn{background:var(--amber-bg,#fef3c7);border-color:#f59e0b33;color:#b45309}
.cred-status-chip.miss{background:var(--bg3);border-color:var(--border);color:var(--text3)}
.api-test-name{font-size:13px;font-weight:600;color:var(--text)}
.api-test-msg{font-size:11px;color:var(--text3);margin-top:2px;font-family:'Geist Mono','Courier New',monospace}
.btn-sm{padding:4px 12px!important;font-size:12px!important;height:28px!important;min-height:28px!important}
.settings-field{margin-bottom:14px}
.settings-label{display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:6px}
.settings-input{
  width:100%;height:40px;padding:0 14px;border:1px solid var(--border2);
  border-radius:var(--radius);background:var(--bg3);font-family:'Geist Mono','Courier New',monospace;
  font-size:13px;color:var(--text);outline:none;transition:border-color .12s,box-shadow .12s,background .12s;
}
.settings-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-border);background:#fff}
.settings-input-wrap{position:relative}
.settings-toggle-btn{
  position:absolute;right:10px;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;color:var(--text3);
  padding:4px;font-size:11px;font-weight:600;transition:color .12s;
}
.settings-toggle-btn:hover{color:var(--accent)}
.btn-save{
  height:38px;padding:0 18px;background:var(--accent);color:#fff;
  border:none;border-radius:var(--radius);font-size:13px;font-weight:600;
  cursor:pointer;transition:all .15s;font-family:inherit;
  box-shadow:0 1px 3px rgba(79,70,229,.25);
}
.btn-save:hover{background:var(--accent2)}
.success-msg{padding:8px 14px;background:var(--green-bg);border:1px solid var(--green-border);border-radius:var(--radius);color:var(--green);font-size:13px;margin-top:10px;display:none}
.key-masked{font-family:'Geist Mono','Courier New',monospace;font-size:12px;color:var(--text3);margin-bottom:8px}
/* === SCORE HERO === */
.results-header{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:24px}
.score-hero{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--radius-xl);padding:28px 32px;margin-bottom:28px;
  box-shadow:var(--shadow);display:flex;align-items:center;gap:24px;flex-wrap:wrap;
}
.score-hero-num{
  font-family:'Inter',sans-serif;font-size:64px;font-weight:700;line-height:1;
  min-width:100px;flex-shrink:0;
}
.score-hero-num.green{color:var(--green)}
.score-hero-num.amber{color:var(--amber)}
.score-hero-num.red{color:var(--red)}
.score-hero-divider{width:1px;height:64px;background:var(--border);flex-shrink:0}
.score-hero-meta{flex:1;min-width:180px}
.score-hero-level{
  display:inline-flex;align-items:center;gap:6px;padding:4px 12px;
  border-radius:999px;font-size:13px;font-weight:700;margin-bottom:10px;
}
.score-hero-level.green{background:var(--green-bg);color:var(--green);border:1px solid var(--green-border)}
.score-hero-level.amber{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-border)}
.score-hero-level.red{background:var(--red-bg);color:var(--red);border:1px solid var(--red-border)}
.score-hero-bar-wrap{width:100%;margin-bottom:10px}
.score-hero-bar-bg{height:6px;background:var(--bg4);border-radius:999px;overflow:hidden}
.score-hero-bar{height:100%;border-radius:999px;transition:width .6s cubic-bezier(.4,0,.2,1)}
.score-hero-bar.green{background:linear-gradient(90deg,#16A34A,#4ADE80)}
.score-hero-bar.amber{background:linear-gradient(90deg,#D97706,#FCD34D)}
.score-hero-bar.red{background:linear-gradient(90deg,#DC2626,#F87171)}
.score-hero-chips{display:flex;gap:8px;flex-wrap:wrap}
.score-hero-interp{font-size:12px;color:var(--text2);line-height:1.4;margin:4px 0 8px}
.score-chip{
  display:inline-flex;align-items:center;gap:5px;padding:3px 10px;
  border-radius:999px;border:1px solid var(--border2);background:var(--bg3);
  font-size:11px;font-weight:500;color:var(--text2);
}
.score-chip svg{color:var(--text3);flex-shrink:0}
.score-chip.green{background:var(--green-bg);color:var(--green);border-color:var(--green-border)}
.score-chip.amber{background:var(--amber-bg);color:var(--amber);border-color:var(--amber-border)}
.score-chip.red{background:var(--red-bg);color:var(--red);border-color:var(--red-border)}
.score-hero-actions{margin-left:auto;display:flex;gap:8px;flex-shrink:0}
/* legacy badge kept for compat */
.score-badge{display:none}
.ymyl-badge{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:600}
.ymyl-badge.red{background:var(--red-bg);color:var(--red);border:1px solid var(--red-border)}
.ymyl-badge.amber{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-border)}
.ymyl-badge.green{background:var(--green-bg);color:var(--green);border:1px solid var(--green-border)}
.stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px}
.stat-box{padding:16px;border-radius:var(--radius-lg);border:1px solid;text-align:center;background:var(--bg2);box-shadow:var(--shadow-sm)}
.stat-box.green{border-color:var(--green-border)}
.stat-box.amber{border-color:var(--amber-border)}
.stat-box.red{border-color:var(--red-border)}
.stat-box.blue{border-color:var(--blue-border)}
.stat-num{font-family:'Inter',sans-serif;font-size:30px;font-weight:700;line-height:1;margin-bottom:4px}
.stat-box.green .stat-num{color:var(--green)}
.stat-box.amber .stat-num{color:var(--amber)}
.stat-box.red .stat-num{color:var(--red)}
.stat-box.blue .stat-num{color:var(--blue)}
.stat-lbl{font-size:11px;font-weight:500;color:var(--text3)}
/* === CLUSTER OVERVIEW === */
.cluster-overview{display:flex;flex-direction:column;gap:10px;margin-bottom:20px}
.cluster-card{
  display:flex;flex-direction:column;min-width:0;
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--radius-lg);
  box-shadow:var(--shadow-sm);transition:box-shadow .15s,border-color .15s;
  overflow:hidden;
}
.cluster-card:hover{box-shadow:var(--shadow);border-color:var(--border2)}
.cluster-card-header{display:flex;align-items:center;gap:16px;padding:16px 20px;cursor:pointer;user-select:none}
.cluster-card-header:hover .cluster-card-name{color:var(--accent)}
.cluster-card-donut{flex-shrink:0}
.cluster-card-info{flex:1;min-width:0}
.cluster-card-name{font-size:14px;font-weight:600;color:var(--text);line-height:1.35;transition:color .12s}
.cluster-card-toggle{margin-left:auto;flex-shrink:0;color:var(--text3);transition:transform .2s}
.cluster-card.open .cluster-card-toggle{transform:rotate(180deg)}
.cluster-card-body{display:none;border-top:1px solid var(--border)}
.cluster-card.open .cluster-card-body{display:block}
.cluster-crit-row{display:flex;align-items:flex-start;gap:10px;padding:9px 20px;border-bottom:1px solid var(--border);font-size:12px}
.cluster-crit-row:last-child{border-bottom:none}
.cluster-crit-row:hover{background:var(--bg3)}
.cluster-crit-meta{display:flex;gap:6px;align-items:center;flex-shrink:0;width:68px}
.cluster-crit-id{font-family:'Geist Mono','Courier New',monospace;font-size:10px;color:var(--text3)}
.cluster-crit-main{flex:1;min-width:0}
.cluster-crit-name{font-weight:500;color:var(--text2);margin-bottom:2px;line-height:1.3}
.cluster-crit-finding{color:var(--text3);font-size:12px;line-height:1.4}
.cluster-crit-improve{margin-top:4px;font-size:12px;color:var(--accent);line-height:1.4}
.sqeg-scale{display:flex;align-items:center;margin-bottom:20px;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;background:var(--bg2)}
.sqeg-level{flex:1;padding:9px 4px;text-align:center;font-size:11px;font-weight:600;color:var(--text3);cursor:default;border-right:1px solid var(--border);transition:background .2s,color .2s}
.sqeg-level:last-child{border-right:none}
.sqeg-level.active{background:var(--accent);color:#fff}
.needs-met-block{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px 24px;margin-bottom:20px;display:none;box-shadow:var(--shadow)}
.needs-met-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);margin-bottom:12px}
/* UX body card (unterhalb score-hero) */
#ux-body-card{
  background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-xl);
  padding:28px 32px;box-shadow:var(--shadow);margin-top:20px;
}
#ux-body-card .needs-met-block{background:transparent;border:none;box-shadow:none;padding:0;margin-bottom:0;}
#ux-body-card .needs-met-block+.needs-met-block{border-top:1px solid var(--border);padding-top:20px;margin-top:20px;}
#ux-body-card .needs-met-label{font-size:11px;font-weight:700;color:var(--text2);margin-bottom:14px;letter-spacing:0;text-transform:none;}
#ux-body-card .section-divider{margin:24px 0 20px;}
/* ─────────────────────────────────────────────────────────────────────── */
.needs-met-scale{display:flex;gap:6px;flex-wrap:wrap}
.nm-btn{padding:5px 12px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid var(--border2);background:var(--bg3);color:var(--text3)}
.nm-btn.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.priority-matrix{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:24px}
.priority-col{border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;background:var(--bg2);box-shadow:var(--shadow-sm)}
.priority-col-header{padding:10px 14px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.priority-col-header.red{background:var(--red-bg);color:var(--red);border-bottom:1px solid var(--red-border)}
.priority-col-header.amber{background:var(--amber-bg);color:var(--amber);border-bottom:1px solid var(--amber-border)}
.priority-col-header.blue{background:var(--blue-bg);color:var(--blue);border-bottom:1px solid var(--blue-border)}
.priority-item{padding:8px 12px;font-size:12px;color:var(--text2);border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:7px;transition:background .1s}
.priority-item:last-child{border-bottom:none}
.priority-item:hover{background:var(--bg3)}
.pri-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;margin-top:4px}
.pri-dot.red{background:var(--red)}
.pri-dot.amber{background:var(--amber)}
.pri-dot.blue{background:var(--blue)}
.pri-dot.green{background:var(--green)}
.effort-badge{font-size:10px;padding:1px 7px;border-radius:var(--radius-sm);background:var(--bg3);border:1px solid var(--border);color:var(--text3);white-space:nowrap;margin-left:auto;flex-shrink:0;font-weight:500}
.filter-bar{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px}
.filter-btn{padding:5px 14px;border-radius:999px;font-size:12px;font-weight:500;border:1px solid var(--border2);background:var(--bg2);color:var(--text2);cursor:pointer;transition:all .12s}
.filter-btn:hover{border-color:var(--accent);color:var(--accent)}
.filter-btn.active{background:var(--accent);color:#fff;border-color:var(--accent)}
/* Criteria table with expand rows */
.criteria-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
.criteria-table{width:100%;border-collapse:collapse;margin-bottom:24px}
.criteria-table th{text-align:left;padding:9px 14px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text3);background:var(--bg3);border-bottom:1px solid var(--border)}
.criteria-table td{padding:12px 14px;border-bottom:1px solid var(--border);vertical-align:top}
.criteria-table tbody tr.crit-row{cursor:pointer;transition:background .1s}
.criteria-table tbody tr.crit-row:hover td{background:var(--bg3)}
.criteria-table tbody tr.crit-row.expanded td{background:var(--bg3);border-bottom:none}
.criteria-table tbody tr.crit-detail{display:none}
.criteria-table tbody tr.crit-detail.visible{display:table-row}
.criteria-table tbody tr.crit-detail td{background:var(--bg2);border-bottom:1px solid var(--border);border-left:3px solid var(--border2);padding:0 14px 16px 24px}
.crit-detail-inner{border-top:1px solid var(--border);padding-top:12px;display:grid;gap:10px}
.crit-detail-row{font-size:12px;line-height:1.6}
.crit-detail-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text3);margin-bottom:2px}
.crit-chevron{transition:transform .2s;color:var(--text3);flex-shrink:0}
.crit-row.expanded .crit-chevron{transform:rotate(180deg)}
.status-dot{width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0}
.status-dot.green{background:var(--green-bg);color:var(--green);border:1px solid var(--green-border)}
.status-dot.amber{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-border)}
.status-dot.red{background:var(--red-bg);color:var(--red);border:1px solid var(--red-border)}
.crit-id{font-family:'Geist Mono','Courier New',monospace;font-size:10px;color:var(--text3)}
.crit-name{font-size:13px;font-weight:600;color:var(--text)}
.crit-cat{font-size:11px;color:var(--text3)}
.crit-ref{font-size:10px;color:var(--accent);font-family:'Geist Mono','Courier New',monospace}
.finding-beleg{display:inline-block;background:var(--bg3);border-radius:var(--radius-sm);padding:2px 7px;font-size:11px;color:var(--text3);margin-bottom:4px}
.finding-rule{font-size:12px;font-style:italic;color:var(--text2);margin-bottom:4px}
.finding-verdict{font-size:12px;font-weight:600;color:var(--text)}
.suggest{margin-top:6px;padding:8px 12px;background:var(--amber-bg);border-left:2px solid var(--amber-border);border-radius:0 var(--radius-sm) var(--radius-sm) 0;font-size:12px;color:var(--text2);line-height:1.5}
/* Executive Summary */
.exec-summary-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:20px;box-shadow:var(--shadow-sm)}
.exec-summary-header{display:flex;align-items:center;gap:8px;margin-bottom:16px}
.exec-summary-header svg{color:var(--accent);flex-shrink:0}
.exec-summary-title{font-size:14px;font-weight:700;color:var(--text)}
.exec-summary-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.exec-summary-steps{margin-top:12px;background:var(--bg3);border-radius:var(--radius);padding:14px 16px;border-left:3px solid var(--accent)}
.exec-summary-section{background:var(--bg3);border-radius:var(--radius);padding:14px 16px}
.exec-summary-section-title{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text3);margin-bottom:10px}
.exec-summary-score{font-size:13px;font-weight:700;color:var(--text);margin-bottom:6px;line-height:1.4}
.exec-summary-interpretation{font-size:12px;color:var(--text2);line-height:1.6}
.exec-summary-item{display:flex;gap:8px;align-items:flex-start;margin-bottom:8px;font-size:12px;color:var(--text2);line-height:1.5}
.exec-summary-item:last-child{margin-bottom:0}
.exec-summary-bullet{font-size:11px;font-weight:700;flex-shrink:0;margin-top:1px;color:var(--red)}
.exec-summary-num{width:18px;height:18px;border-radius:50%;background:var(--accent);color:#fff;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
.exec-summary-problem{margin-bottom:10px}
.exec-summary-problem:last-child{margin-bottom:0}
.exec-summary-problem-label{font-size:12px;font-weight:700;color:var(--text);line-height:1.4;margin-bottom:2px}
.exec-summary-problem-arrow{font-size:12px;color:var(--text2);line-height:1.5;padding-left:14px}
.exec-summary-loading{display:flex;align-items:center;gap:10px;color:var(--text3);font-size:13px;padding:4px 0}
.export-bar{display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap}
/* === REDUCED MOTION === */
@media(prefers-reduced-motion:reduce){
  *,*::before,*::after{transition-duration:.01ms!important;animation-duration:.01ms!important;animation-iteration-count:1!important}
}
/* === SKELETON SCREENS === */
.skeleton{border-radius:var(--radius);background:var(--bg4);animation:skel-pulse 3s ease-in-out infinite}
@keyframes skel-pulse{0%,100%{opacity:.45}50%{opacity:.8}}
.skeleton-score{height:120px;margin-bottom:24px;border-radius:var(--radius-xl)}
.skeleton-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px}
.skeleton-stat{height:80px;border-radius:var(--radius-lg)}
.skeleton-clusters{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.skeleton-cluster{height:136px;border-radius:var(--radius-lg)}
@media(max-width:768px){
  .sidebar{width:100%;height:auto;position:static;flex-direction:row;overflow-x:auto;border-right:none;border-bottom:1px solid var(--border)}
  .main-content{margin-left:0}
  .stat-grid{grid-template-columns:repeat(2,1fr)}
  .skeleton-stats{grid-template-columns:repeat(2,1fr)}
  .skeleton-clusters{grid-template-columns:repeat(2,1fr)}
  .cluster-overview{gap:8px}
  .priority-matrix{grid-template-columns:1fr}
  .score-hero{flex-direction:column;gap:16px}
  .score-hero-divider{display:none}
  .score-hero-num{font-size:48px}
  .score-hero-actions{margin-left:0}
}
[data-theme="dark"] .url-input:focus,
[data-theme="dark"] .html-textarea:focus,
[data-theme="dark"] .ctx-input:focus,
[data-theme="dark"] .settings-input:focus{background:var(--bg3)}
[data-theme="dark"] .log-header{background:var(--bg2)}
[data-theme="dark"] .log-header:hover{background:var(--bg3)}
/* Nav score badges */
.nav-score{margin-left:auto;font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;background:var(--bg4);color:var(--text3);font-family:'Geist Mono',monospace}
.nav-score.green{background:var(--green-bg);color:var(--green);border:1px solid var(--green-border)}
.nav-score.amber{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-border)}
.nav-score.red{background:var(--red-bg);color:var(--red);border:1px solid var(--red-border)}
/* Module cards on overview */
.module-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:28px}
.module-card{
  background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);
  padding:20px 22px;box-shadow:var(--shadow-sm);cursor:pointer;
  transition:box-shadow .15s,border-color .15s,transform .1s;
  display:flex;flex-direction:column;gap:10px;
}
.module-card:hover{box-shadow:var(--shadow-md);border-color:var(--border2);transform:translateY(-1px)}
.module-card-header{display:flex;align-items:center;gap:10px}
.module-card-icon{width:34px;height:34px;border-radius:var(--radius);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.module-card-icon.sqeg{background:var(--accent-bg);color:var(--accent);border:1px solid var(--accent-border)}
.module-card-icon.perf{background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-border)}
.module-card-icon.geo{background:var(--green-bg);color:var(--green);border:1px solid var(--green-border)}
.module-card-name{font-size:13px;font-weight:700;color:var(--text)}
.module-card-sub{font-size:11px;color:var(--text3)}
.module-card-score{font-size:28px;font-weight:700;line-height:1;font-family:'Inter',sans-serif}
.module-card-score.green{color:var(--green)}
.module-card-score.amber{color:var(--amber)}
.module-card-score.red{color:var(--red)}
.module-card-score.neutral{color:var(--text3)}
.module-card-bar-bg{height:4px;background:var(--bg4);border-radius:999px;overflow:hidden}
.module-card-bar{height:100%;border-radius:999px;transition:width .6s cubic-bezier(.4,0,.2,1)}
.module-card-bar.green{background:var(--green)}
.module-card-bar.amber{background:var(--amber)}
.module-card-bar.red{background:var(--red)}
.module-card-bar.neutral{background:var(--bg4)}
.module-card-label{font-size:11px;color:var(--text3);margin-top:2px}
/* View panels */
.view-panel{display:none}
.view-panel.active{display:block}
.content-wrap{max-width:960px;margin:0 auto;padding:24px 32px 48px}
/* Overview top priorities */
.top-priorities{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px 22px;margin-bottom:28px;box-shadow:var(--shadow-sm)}
.top-priorities-title{font-size:13px;font-weight:700;color:var(--text);margin-bottom:14px;display:flex;align-items:center;gap:8px}
.top-prio-item{display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
.top-prio-item:last-child{border-bottom:none}
.top-prio-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;flex-shrink:0;margin-top:2px;white-space:nowrap}
.top-prio-badge.red{background:var(--red-bg);color:var(--red);border:1px solid var(--red-border)}
.top-prio-badge.amber{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-border)}
.top-prio-text{color:var(--text2);line-height:1.5}
/* === RADAR CHART === */
.radar-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;margin-bottom:24px;box-shadow:var(--shadow-sm)}
.radar-card-title{font-size:13px;font-weight:700;color:var(--text);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.radar-wrap{display:flex;justify-content:center;align-items:center}
.radar-wrap svg{max-width:320px;width:100%;height:auto;overflow:visible}
/* Page Preview */
.page-preview-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:24px;box-shadow:var(--shadow-sm)}
.page-preview-bar{background:var(--bg3);border-bottom:1px solid var(--border);padding:7px 12px;display:flex;align-items:center;gap:8px}
.page-preview-dots{display:flex;gap:4px}
.page-preview-dot{width:8px;height:8px;border-radius:50%;background:var(--border2)}
.page-preview-url-wrap{flex:1;background:var(--bg4);border:1px solid var(--border);border-radius:4px;padding:3px 10px;font-size:11px;color:var(--text3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:'Geist Mono',monospace}
.page-preview-img-wrap{height:220px;overflow:hidden}
.page-preview-img-wrap img{width:100%;height:100%;object-fit:cover;object-position:top;display:block;transition:opacity .3s}
.page-preview-footer{font-size:11px;color:var(--text3);padding:6px 12px;background:var(--bg3);border-top:1px solid var(--border);display:flex;align-items:center;gap:6px}
@media(max-width:900px){.module-grid{grid-template-columns:1fr 1fr}}
/* ── Local PV Generator ──────────────────────────────────────────────── */
.pv-input-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px}
.pv-input-grid .full{grid-column:1/-1}
.pv-generate-btn{display:flex;align-items:center;gap:8px;padding:10px 20px;background:var(--accent);color:#fff;border:none;border-radius:var(--radius);font-size:13px;font-weight:600;cursor:pointer;transition:background .15s;font-family:inherit;margin-top:16px}
.pv-generate-btn:hover{background:var(--accent2)}
.pv-generate-btn:disabled{opacity:.5;cursor:not-allowed}
.pv-generate-btn svg{flex-shrink:0}
.pv-sources-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.pv-source-badge{display:flex;align-items:center;gap:5px;padding:4px 10px;background:var(--bg3);border:1px solid var(--border);border-radius:999px;font-size:11px;color:var(--text2)}
.pv-source-badge svg{color:var(--text3)}
.pv-results-list{display:flex;flex-direction:column;gap:16px;margin-top:24px}
.pv-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px 20px;box-shadow:var(--shadow-sm);position:relative}
.pv-card-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);margin-bottom:8px;display:flex;align-items:center;gap:6px}
.pv-card-label svg{flex-shrink:0}
.pv-card-body{font-size:13px;color:var(--text);line-height:1.6}
.pv-sec-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text3);margin-bottom:4px;margin-top:12px}
.pv-sec-label:first-child{margin-top:0}
.pv-sec-micro{font-size:13px;line-height:1.6;color:var(--accent);background:var(--accent-bg);padding:8px 12px;border-radius:var(--radius-sm);border:1px solid var(--accent-border)}
.pv-sec-content{font-size:13px;line-height:1.75;color:var(--text)}
.pv-sec-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text3);margin-bottom:4px;margin-top:12px}
.pv-sec-label:first-child{margin-top:0}
.pv-sec-micro{font-size:13px;line-height:1.6;color:var(--accent);background:var(--accent-bg);padding:8px 12px;border-radius:var(--radius-sm);border:1px solid var(--accent-border)}
.pv-sec-content{font-size:13px;line-height:1.75;color:var(--text)}
.pv-copy-btn{position:absolute;top:12px;right:12px;display:flex;align-items:center;gap:5px;padding:4px 10px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);font-size:11px;font-weight:500;color:var(--text2);cursor:pointer;transition:background .12s,color .12s;font-family:inherit}
.pv-copy-btn:hover{background:var(--bg4);color:var(--text)}
.pv-copy-btn.copied{background:var(--green-bg);border-color:var(--green-border);color:var(--green)}
.pv-checklist{display:flex;flex-direction:column;gap:6px;margin-top:4px}
.pv-checklist-item{display:flex;align-items:flex-start;gap:10px;padding:8px 10px;border-radius:var(--radius-sm);background:var(--bg3)}
.pv-checklist-status{flex-shrink:0;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-top:1px}
.pv-checklist-status.ok{background:var(--green-bg);color:var(--green)}
.pv-checklist-status.warning{background:var(--amber-bg);color:var(--amber)}
.pv-checklist-status.missing{background:var(--red-bg);color:var(--red)}
.pv-checklist-text{flex:1}
.pv-checklist-item-label{font-size:12px;font-weight:500;color:var(--text)}
.pv-checklist-note{font-size:11px;color:var(--text3);margin-top:2px}
.pv-faq-list{display:flex;flex-direction:column;gap:10px}
.pv-faq-item{}
.pv-faq-q{font-size:13px;font-weight:600;color:var(--text);margin-bottom:3px}
.pv-faq-a{font-size:12px;color:var(--text2);line-height:1.6}
.pv-rec-list{display:flex;flex-direction:column;gap:8px}
.pv-rec-item{display:flex;align-items:flex-start;gap:10px;padding:8px 10px;border-radius:var(--radius-sm);background:var(--bg3)}
.pv-rec-prio{flex-shrink:0;font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;margin-top:2px}
.pv-rec-prio.high{background:var(--red-bg);color:var(--red);border:1px solid var(--red-border)}
.pv-rec-prio.medium{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-border)}
.pv-rec-prio.low{background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-border)}
.pv-rec-body{flex:1}
.pv-rec-module{font-size:11px;font-weight:600;color:var(--text2);margin-bottom:2px}
.pv-rec-text{font-size:12px;color:var(--text)}
.pv-meta-row{display:grid;grid-template-columns:1fr;gap:8px}
.pv-meta-field{}
.pv-meta-field-label{font-size:11px;font-weight:600;color:var(--text3);margin-bottom:3px}
.pv-meta-value{font-size:13px;color:var(--text);line-height:1.5;background:var(--bg3);padding:8px 12px;border-radius:var(--radius-sm);border:1px solid var(--border)}
.pv-hero-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.pv-hero-field{}
.pv-hero-field-label{font-size:11px;font-weight:600;color:var(--text3);margin-bottom:3px}
.pv-hero-value{font-size:13px;color:var(--text);line-height:1.5;background:var(--bg3);padding:8px 12px;border-radius:var(--radius-sm);border:1px solid var(--border)}
.pv-hero-field.full{grid-column:1/-1}
.pv-error-box{background:var(--red-bg);border:1px solid var(--red-border);border-radius:var(--radius);padding:14px 16px;color:var(--red);font-size:13px;margin-top:16px}
.pv-loading{display:flex;flex-direction:column;align-items:center;gap:12px;padding:48px 0;color:var(--text3);font-size:13px}
.pv-loading-spinner{width:32px;height:32px;border:3px solid var(--border2);border-top-color:var(--accent);border-radius:50%;animation:pv-spin .7s linear infinite}
@keyframes pv-spin{to{transform:rotate(360deg)}}
.pv-export-area{font-family:'Geist Mono',monospace;font-size:12px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;white-space:pre-wrap;word-break:break-word;max-height:400px;overflow-y:auto;color:var(--text2);margin-top:4px}
.pv-data-hint{display:flex;align-items:flex-start;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid var(--border);flex-wrap:wrap}
.pv-data-hint-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text3);white-space:nowrap;padding-top:2px}
.pv-data-source-tag{font-size:10px;padding:2px 8px;border-radius:999px;background:var(--bg3);color:var(--text2);border:1px solid var(--border2);white-space:nowrap}
.pv-data-source-tag.gsc{background:var(--blue-bg);color:var(--blue);border-color:var(--blue-border)}
.pv-data-source-tag.sistrix{background:var(--amber-bg);color:var(--amber);border-color:var(--amber-border)}
.pv-data-source-tag.dataforseo{background:var(--green-bg);color:var(--green);border-color:var(--green-border)}
.pv-data-source-tag.pvgis{background:var(--accent-bg);color:var(--accent);border-color:var(--accent-border)}
.pv-data-source-tag.dwd{background:var(--blue-bg);color:var(--blue);border-color:var(--blue-border)}
#pv-dwd-banner{margin-top:20px;padding:12px 16px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius)}
#pv-dwd-banner .pv-data-hint{margin-top:0;padding-top:0;border-top:none}
.pv-dwd-inline-vals{width:100%;margin-top:6px;display:flex;flex-direction:column;gap:3px;font-size:11px;color:var(--text2);padding-left:2px}
.pv-dwd-inline-vals span{display:block}
.pv-dwd-inline-vals strong{color:var(--accent);font-weight:700}
#pv-kw-pills{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;padding:10px 12px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm)}
.pv-kw-pill{display:flex;flex-direction:column;align-items:flex-start;gap:1px;padding:5px 11px;background:var(--bg3);border:1px solid var(--border2);border-radius:999px;cursor:pointer;font-family:inherit;transition:background .12s,border-color .12s;text-align:left}
.pv-kw-pill:hover{background:var(--accent-bg);border-color:var(--accent-border)}
.pv-kw-pill.selected{background:var(--accent-bg);border-color:var(--accent)}
.pv-kw-pill-text{font-size:12px;font-weight:500;color:var(--text);white-space:nowrap}
.pv-kw-pill.selected .pv-kw-pill-text{color:var(--accent)}
.pv-kw-pill-vol{font-size:10px;color:var(--text3);font-family:'Geist Mono',monospace}
.pv-kw-pill-no-data{color:var(--text3)}
.pv-solar-card{display:flex;flex-direction:column;gap:12px}
.pv-solar-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.pv-solar-metric{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 16px}
.pv-solar-value{font-size:22px;font-weight:700;color:var(--accent);font-family:'Geist Mono',monospace;line-height:1.1}
.pv-solar-unit{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-top:2px}
.pv-solar-label{font-size:11px;color:var(--text2);margin-top:4px}
.pv-solar-source{display:flex;align-items:center;gap:8px;font-size:11px;color:var(--text3)}
.pv-data-active .pv-data-hint-label{color:var(--blue)}
#pv-dwd-banner{margin-top:20px;background:var(--bg2);border:1px solid var(--border);border-left:3px solid var(--blue);border-radius:var(--radius);padding:14px 16px}
.pv-dwd-banner-head{display:flex;align-items:center;gap:8px;margin-bottom:12px}
.pv-dwd-banner-title{font-size:12px;font-weight:600;color:var(--text);letter-spacing:.2px}
.pv-dwd-banner-metrics{display:flex;gap:24px;flex-wrap:wrap}
.pv-dwd-bm{display:flex;flex-direction:column;gap:1px;min-width:110px}
.pv-dwd-bm-val{font-size:22px;font-weight:700;color:var(--accent);font-family:'Geist Mono',monospace;line-height:1.1}
.pv-dwd-bm-unit{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px}
.pv-dwd-bm-label{font-size:11px;color:var(--text2);margin-top:2px}
.pv-dwd-banner-meta{margin-top:12px;padding-top:10px;border-top:1px solid var(--border);display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:11px;color:var(--text3)}
.pv-dwd-compare{display:grid;grid-template-columns:1fr 1px 1fr;gap:0 16px;align-items:start;margin-top:4px}
.pv-dwd-compare-div{background:var(--border);width:1px;align-self:stretch;margin-top:28px}
.pv-dwd-compare-col{display:flex;flex-direction:column;gap:10px}
.pv-dwd-compare-head{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text3);margin-bottom:2px}
@media(max-width:900px){.pv-input-grid,.pv-hero-grid{grid-template-columns:1fr}.pv-input-grid .full,.pv-hero-field.full{grid-column:1}}
/* ── PV Tabs ── */
.pv-tabs{display:flex;gap:4px;border-bottom:1px solid var(--border);margin-top:24px;margin-bottom:0}
.pv-tab-btn{padding:8px 16px;font-size:12px;font-weight:600;color:var(--text3);background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;font-family:inherit;transition:color .15s,border-color .15s;margin-bottom:-1px;white-space:nowrap}
.pv-tab-btn:hover{color:var(--text2)}
.pv-tab-btn.active{color:var(--accent);border-bottom-color:var(--accent)}
.pv-refine-btn{margin-left:auto;display:flex;align-items:center;gap:6px;padding:5px 12px;background:var(--accent-bg);border:1px solid var(--accent-border);border-radius:var(--radius-sm);font-size:11px;font-weight:600;color:var(--accent);cursor:pointer;font-family:inherit;transition:background .15s;white-space:nowrap;margin-bottom:-1px}
.pv-refine-btn:hover{background:var(--accent);color:#fff}
.pv-refine-btn:disabled{opacity:.5;cursor:not-allowed}
.pv-refine-btn svg{flex-shrink:0}
.pv-refine-notice{display:flex;align-items:center;gap:8px;padding:8px 14px;background:var(--green-bg);border:1px solid var(--green-border);border-radius:var(--radius-sm);font-size:12px;color:var(--green);margin-top:12px}
.pv-version-bar{display:flex;align-items:center;gap:6px;padding:8px 0 10px;flex-wrap:wrap}
.pv-version-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text3);margin-right:2px}
.pv-version-btn{padding:3px 10px;font-size:11px;font-weight:500;background:var(--bg3);border:1px solid var(--border);border-radius:999px;color:var(--text2);cursor:pointer;font-family:inherit;transition:all .12s;white-space:nowrap}
.pv-version-btn:hover:not(:disabled){border-color:var(--accent);color:var(--accent)}
.pv-version-btn.active{background:var(--accent-bg);border-color:var(--accent-border);color:var(--accent);font-weight:600}
.pv-version-btn:disabled{opacity:.35;cursor:not-allowed}
/* ── Agent System ─────────────────────────────────────────────────── */
.agent-bar{display:flex;align-items:center;gap:8px;padding:6px 0 14px;margin-top:20px}
.agent-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px 4px 6px;background:var(--bg2);border:1px solid var(--border);border-radius:999px;font-size:11px;font-weight:500;color:var(--text2);cursor:pointer;font-family:inherit;transition:all .15s;white-space:nowrap}
.agent-badge:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-bg)}
.agent-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;transition:background .2s}
.agent-dot.idle{background:var(--border)}
.agent-dot.running{background:var(--amber);animation:agentPulse 1s ease-in-out infinite}
.agent-dot.done{background:var(--green)}
.agent-dot.error{background:var(--red)}
@keyframes agentPulse{0%,100%{opacity:1}50%{opacity:.4}}
/* Modal */
.agent-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9000;align-items:center;justify-content:center;padding:20px}
.agent-modal{background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:700px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.agent-modal-header{padding:20px 20px 16px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:10px}
.agent-modal-title{flex:1}
.agent-modal-title h3{margin:0 0 2px;font-size:15px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:7px}
.agent-modal-title p{margin:0;font-size:12px;color:var(--text3)}
.agent-modal-close{background:none;border:none;cursor:pointer;color:var(--text3);font-size:18px;line-height:1;padding:2px 4px;border-radius:var(--radius-sm)}
.agent-modal-close:hover{color:var(--text)}
.agent-modal-body{flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:14px}
.agent-modal-section label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text3);margin-bottom:6px}
.agent-modal-prompt{width:100%;box-sizing:border-box;font-family:'Geist Mono',monospace;font-size:11px;line-height:1.6;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);padding:10px 12px;resize:vertical;min-height:160px}
.agent-modal-prompt:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-border)}
.agent-modal-output{font-family:'Geist Mono',monospace;font-size:10px;line-height:1.5;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text2);padding:10px 12px;max-height:180px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;margin:0}
.agent-modal-footer{padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-shrink:0}
.agent-custom-chip{font-size:10px;padding:2px 8px;background:var(--accent-bg);border:1px solid var(--accent-border);border-radius:999px;color:var(--accent);font-weight:600;display:none}
.pv-tab-panel{display:none}
.pv-tab-panel.active{display:block}
/* ── Benefits Grid ── */
.pv-benefits-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:4px}
.pv-benefit-card{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px}
.pv-benefit-title{font-size:12px;font-weight:700;color:var(--accent);margin-bottom:5px}
.pv-benefit-text{font-size:12px;line-height:1.6;color:var(--text2)}
.pv-benefit-placement{font-size:10px;color:var(--text3);margin-top:6px;padding-top:6px;border-top:1px solid var(--border)}
/* ── CTA Strategy ── */
.pv-cta-strategy{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px}
.pv-cta-block{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px}
.pv-cta-block-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.pv-cta-block-label.primary{color:var(--accent)}
.pv-cta-block-label.secondary{color:var(--text3)}
.pv-cta-element{font-size:11px;color:var(--text3);margin-bottom:6px;font-style:italic}
.pv-cta-example{font-size:12px;color:var(--text);padding:5px 10px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:4px;cursor:pointer;transition:border-color .12s}
.pv-cta-example:hover{border-color:var(--accent)}
.pv-micro-ctas{margin-top:12px}
.pv-micro-cta-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text3);margin-bottom:6px}
.pv-micro-cta-item{display:flex;align-items:center;gap:8px;padding:5px 10px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:4px}
.pv-micro-cta-placement{font-size:10px;color:var(--text3);min-width:120px;flex-shrink:0}
.pv-micro-cta-text{font-size:12px;color:var(--text)}
/* ── Placement Map ── */
.pv-placement-map{display:flex;flex-direction:column;gap:8px;margin-top:4px}
.pv-placement-item{display:flex;gap:12px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;position:relative}
.pv-placement-num{flex-shrink:0;width:26px;height:26px;border-radius:50%;background:var(--accent-bg);border:1px solid var(--accent-border);color:var(--accent);font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center}
.pv-placement-body{flex:1;min-width:0}
.pv-placement-module{font-size:13px;font-weight:700;color:var(--text);margin-bottom:2px}
.pv-placement-visual{font-size:11px;color:var(--text3);margin-bottom:6px}
.pv-placement-fields{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:6px}
.pv-placement-field-tag{font-size:10px;font-family:'Geist Mono',monospace;padding:2px 7px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text2)}
.pv-placement-rec{font-size:12px;color:var(--text2);line-height:1.5;border-top:1px solid var(--border);padding-top:6px;margin-top:4px}
.pv-placement-jump{flex-shrink:0;padding:4px 10px;font-size:11px;font-weight:500;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text2);cursor:pointer;font-family:inherit;transition:background .12s,color .12s;align-self:flex-start}
.pv-placement-jump:hover{background:var(--accent-bg);color:var(--accent);border-color:var(--accent-border)}
/* ── Section Placement Badge ── */
.pv-placement-badge{display:inline-flex;align-items:center;gap:4px;font-size:10px;padding:2px 8px;background:var(--bg3);border:1px solid var(--border);border-radius:999px;color:var(--text3);margin-bottom:8px}
@media(max-width:900px){.pv-benefits-grid,.pv-cta-strategy{grid-template-columns:1fr}}
/* ══ Content Finder ══════════════════════════════════════════════════════════ */
.cf-layout{display:grid;grid-template-columns:380px 1fr;gap:20px;align-items:start}
.cf-chip-container{min-height:44px;padding:8px;border:1px solid var(--border2);border-radius:var(--radius);background:var(--bg3);display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}
.cf-chip{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;background:var(--accent);color:#fff}
.cf-chip-remove{background:rgba(255,255,255,.25);border:none;color:#fff;width:14px;height:14px;border-radius:50%;font-size:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;padding:0;font-family:inherit}
.cf-add-row{display:flex;gap:8px;align-items:center}
.cf-add-row input{flex:1}
.cf-variant-box{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;margin-top:12px}
.cf-variant-box-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text3);margin-bottom:8px}
.cf-variant-chips{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:8px}
.cf-badge{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:700;color:#fff}
.cf-badge-exact{background:var(--text)}
.cf-badge-variant{background:var(--blue)}
.cf-badge-synonym{background:var(--green)}
.cf-variant-legend{display:flex;gap:12px;font-size:11px;color:var(--text3);flex-wrap:wrap;border-top:1px solid var(--border);padding-top:8px;margin-top:4px}
.cf-opt-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:4px}
.cf-opt{display:flex;align-items:center;gap:8px;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;font-size:12px;color:var(--text2);background:var(--bg2);transition:all .12s;user-select:none;line-height:1.3}
.cf-opt.on{border-color:var(--green-border);background:var(--green-bg);color:var(--green);font-weight:600}
.cf-toggle{width:30px;height:16px;border-radius:8px;background:var(--bg4);position:relative;flex-shrink:0;transition:background .15s}
.cf-toggle::after{content:'';width:12px;height:12px;border-radius:50%;background:var(--bg2);position:absolute;top:2px;left:2px;transition:left .15s}
.cf-opt.on .cf-toggle{background:var(--green)}
.cf-opt.on .cf-toggle::after{left:16px}
.cf-run-btn{width:100%;padding:12px;font-size:14px;font-weight:700;background:var(--accent);color:#fff;border:none;border-radius:var(--radius);cursor:pointer;font-family:inherit;transition:all .12s;box-shadow:var(--shadow-sm);display:flex;align-items:center;justify-content:center;gap:8px;margin-top:16px}
.cf-run-btn:hover{background:var(--accent2);transform:translateY(-1px);box-shadow:var(--shadow-md)}
.cf-run-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
.cf-progress-outer{background:var(--bg4);border-radius:20px;height:8px;overflow:hidden;margin-bottom:4px}
.cf-progress-inner{height:100%;border-radius:20px;background:linear-gradient(90deg,var(--accent),var(--blue));width:0%;transition:width .3s}
.cf-crawl-list{margin-top:14px;display:flex;flex-direction:column;gap:3px;max-height:280px;overflow-y:auto}
.cf-crawl-item{display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:var(--radius-sm);font-size:12px;color:var(--text2)}
.cf-crawl-item.active{background:var(--blue-bg);color:var(--blue)}
.cf-crawl-url{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-family:'Geist Mono',monospace;font-size:11px}
.cf-crawl-hits{font-size:10px;font-weight:700;padding:1px 6px;border-radius:20px;background:var(--bg4);color:var(--text3);flex-shrink:0}
.cf-crawl-hits.has-hits{background:var(--green-bg);color:var(--green)}
.cf-substep-bar{display:flex;gap:2px;margin-top:2px}
.cf-substep{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.2px;padding:1px 5px;border-radius:3px;border:1px solid var(--border);color:var(--text3);background:var(--bg3)}
.cf-substep.done{background:var(--green-bg);border-color:var(--green-border);color:var(--green)}
.cf-substep.active{background:var(--blue-bg);border-color:var(--blue-border);color:var(--blue)}
.cf-stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
.cf-stat-card{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;text-align:center}
.cf-stat-val{font-size:28px;font-weight:700;line-height:1}
.cf-stat-label{font-size:11px;color:var(--text3);margin-top:4px}
.cf-filter-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.cf-filter-row input,.cf-filter-row select{font-size:12px;padding:7px 10px;border:1px solid var(--border2);border-radius:var(--radius);background:var(--bg2);color:var(--text);font-family:inherit}
.cf-filter-row input{flex:1;min-width:140px}
.cf-filter-row select{width:auto;min-width:120px}
.cf-table{width:100%;border-collapse:collapse;font-size:12px}
.cf-table th{background:var(--bg3);padding:9px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text3);border-bottom:1px solid var(--border);white-space:nowrap}
.cf-table td{padding:9px 12px;border-bottom:1px solid var(--border);vertical-align:top;color:var(--text2)}
.cf-table tbody tr:hover td{background:var(--bg3)}
.cf-url-mono{font-family:'Geist Mono',monospace;font-size:11px;color:var(--accent);display:block;max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cf-ctx{max-width:260px;line-height:1.5}
.cf-ctx mark{background:#FEF9C3;color:var(--text);font-weight:700;border-radius:2px;padding:0 2px}
[data-theme="dark"] .cf-ctx mark{background:#422006;color:#FDE68A}
.cf-loc{font-size:10px;font-weight:700;text-transform:uppercase;padding:2px 6px;border-radius:var(--radius-sm);background:var(--bg4);color:var(--text3)}
.cf-loc.h1{background:var(--accent-bg);color:var(--accent)}
.cf-loc.img{background:var(--amber-bg);color:var(--amber)}
.cf-loc.meta{background:var(--blue-bg);color:var(--blue)}
.cf-toolbar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.cf-empty{padding:48px 0;text-align:center;color:var(--text3);font-size:13px}
@media(max-width:1100px){.cf-layout{grid-template-columns:1fr}}
@media(max-width:600px){.cf-stat-grid{grid-template-columns:repeat(2,1fr)}.cf-opt-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="app-shell">
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="assets/logo.png" alt="MVV" class="brand-logo">
    <span class="sidebar-brand">L·A·T</span>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Analyse</div>
    <button class="nav-item active" data-view="overview" onclick="showView('overview')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Übersicht
    </button>
    <button class="nav-item" data-view="sqeg" onclick="showView('sqeg')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      SQEG
      <span class="nav-score" id="nav-score-sqeg">–</span>
    </button>
    <button class="nav-item" data-view="technical" onclick="showView('technical')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
      Technical SEO
      <span class="nav-score" id="nav-score-technical">–</span>
    </button>
    <button class="nav-item" data-view="performance" onclick="showView('performance')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Performance
      <span class="nav-score" id="nav-score-perf">–</span>
    </button>
    <button class="nav-item" data-view="geo" onclick="showView('geo')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8l4 4-4 4-4-4 4-4z"/></svg>
      GEO / AEO
      <span class="nav-score" id="nav-score-geo">–</span>
    </button>
    <button class="nav-item" data-view="keywords" onclick="showView('keywords')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
      Keyword Fit
      <span class="nav-score" id="nav-score-kw">–</span>
    </button>
    <button class="nav-item" data-view="ux" onclick="showView('ux')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
      UX / CRO
      <span class="nav-score" id="nav-score-ux">–</span>
    </button>
    <div class="nav-section-label">Tools</div>
    <button class="nav-item" data-view="localpv" onclick="showView('localpv')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      Local PV Generator
    </button>
    <button class="nav-item" data-view="content-finder" onclick="showView('content-finder')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/><line x1="11" y1="8" x2="11" y2="14"/></svg>
      Content Finder
    </button>
    <div class="nav-section-label" style="margin-top:auto">System</div>
    <button class="nav-item" data-view="settings" onclick="showView('settings')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M21 12h-2M19.07 19.07l-1.41-1.41M12 21v-2M4.93 19.07l1.41-1.41M3 12h2M4.93 4.93l1.41 1.41"/></svg>
      Einstellungen
    </button>
  </nav>
  <div class="sidebar-footer">
    <span>LAT v3.0</span>
    <div style="display:flex;align-items:center;gap:8px">
      <button class="theme-btn" id="btn-theme" onclick="toggleTheme()" title="Dark / Light Mode" aria-label="Theme wechseln">
        <svg class="icon-sun" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <svg class="icon-moon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      </button>
      <a href="../login.php?logout=1">Abmelden</a>
    </div>
  </div>
</aside>
<div class="main-content">
<header class="workspace-header">
  <div class="workspace-header-inner">
    <span class="workspace-title" id="view-title">Übersicht</span>
    <span class="workspace-divider"></span>
    <span class="workspace-subtitle" id="view-subtitle">Landingpage Analyse Tool</span>
  </div>
  <div class="workspace-header-form" id="header-form">
    <div class="header-input-row">
      <input type="text" id="url-input" class="url-input" placeholder="URL der Landingpage eingeben" autocomplete="off" spellcheck="false">
      <div class="mode-toggle">
        <button class="mode-btn active" id="mode-url" onclick="setMode('url')">URL</button>
        <button class="mode-btn" id="mode-html" onclick="setMode('html')">HTML</button>
      </div>
    </div>
    <div id="html-textarea-wrap" style="display:none;margin-top:8px">
      <textarea id="html-textarea" class="html-textarea" placeholder="HTML-Quellcode hier einfügen…"></textarea>
    </div>
    <button class="context-toggle" onclick="toggleContext()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
      Analyse verfeinern
    </button>
    <div class="context-fields" id="context-fields">
      <div class="ctx-field">
        <span class="ctx-label">Ziel-Keyword</span>
        <input type="text" id="ctx-keyword" class="ctx-input" placeholder="z.B. beste Zahnversicherung">
      </div>
      <div class="ctx-field">
        <span class="ctx-label">Conversion-Ziel</span>
        <input type="text" id="ctx-goal" class="ctx-input" placeholder="z.B. Newsletter-Anmeldung">
      </div>
      <div class="ctx-field">
        <span class="ctx-label">Zielgruppe</span>
        <input type="text" id="ctx-audience" class="ctx-input" placeholder="z.B. Frauen 35–50">
      </div>
    </div>
    <div class="header-action-row">
      <button class="btn-start" id="btn-start" onclick="startAnalysis()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
        Analyse starten
      </button>
      <button class="btn-demo" id="btn-demo" onclick="startDemo()" title="Vorschau mit Beispieldaten">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2v-4M9 21H5a2 2 0 01-2-2v-4m0 0h18"/></svg>
        Demo
      </button>
    </div>
  </div>
</header>
<div class="content-wrap">

<!-- ═══════════════════════════════════════════════════════════
     VIEW: ÜBERSICHT
════════════════════════════════════════════════════════════ -->
<div class="view-panel active" id="view-overview">

  <div id="progress-section" style="display:none">
    <div class="input-card">
      <div class="progress-header">
        <span class="progress-label" id="progress-label">Analyse startet…</span>
        <span style="display:flex;align-items:center;gap:14px">
          <span class="progress-timer-stat" id="progress-timer"></span>
          <span class="progress-pct" id="progress-pct">0%</span>
        </span>
      </div>
      <div id="progress-bar-wrap"><div class="progress-bar-bg"><div class="progress-bar" id="progress-bar"></div></div></div>
      <div class="status-msg" id="status-msg"></div>
      <div id="loader-wrap"><div class="loader-dots">
        <div class="loader-dot"></div><div class="loader-dot"></div><div class="loader-dot"></div>
      </div></div>
      <div class="log-wrap" id="log-wrap">
        <div class="log-header" onclick="toggleLog()">
          <span style="font-size:12px;font-weight:600;color:var(--text2)">Analyse-Log</span>
          <svg class="log-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="log-box" id="log-box"></div>
      </div>
    </div>
    <div id="skeleton-wrap" style="display:none">
      <div class="skeleton skeleton-score"></div>
      <div class="skeleton-stats"><div class="skeleton skeleton-stat"></div><div class="skeleton skeleton-stat"></div><div class="skeleton skeleton-stat"></div><div class="skeleton skeleton-stat"></div></div>
      <div class="skeleton-clusters"><div class="skeleton skeleton-cluster"></div><div class="skeleton skeleton-cluster"></div><div class="skeleton skeleton-cluster"></div><div class="skeleton skeleton-cluster"></div></div>
    </div>
  </div>

  <div id="results-section" style="display:none">
    <!-- Modul-Kacheln -->
    <div class="module-grid" id="module-grid" style="margin-top:28px">
      <div class="module-card" onclick="showView('sqeg')">
        <div class="module-card-header">
          <div class="module-card-icon sqeg"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
          <div><div class="module-card-name">SQEG</div><div class="module-card-sub">Content &amp; Qualität</div></div>
        </div>
        <div class="module-card-score neutral" id="mc-sqeg-score">–</div>
        <div class="module-card-bar-bg"><div class="module-card-bar neutral" id="mc-sqeg-bar" style="width:0%"></div></div>
        <div class="module-card-label" id="mc-sqeg-label">Noch nicht analysiert</div>
      </div>
      <div class="module-card" id="mc-technical" onclick="showView('technical')">
        <div class="module-card-header">
          <div class="module-card-icon" style="background:var(--bg4);color:var(--text2)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></div>
          <div><div class="module-card-name">Technical SEO</div><div class="module-card-sub">Source Code &amp; On-Page</div></div>
        </div>
        <div class="module-card-score neutral" id="mc-technical-score">–</div>
        <div class="module-card-bar-bg"><div class="module-card-bar neutral" id="mc-technical-bar" style="width:0%"></div></div>
        <div class="module-card-label" id="mc-technical-label">Noch nicht analysiert</div>
      </div>
      <div class="module-card" onclick="showView('performance')">
        <div class="module-card-header">
          <div class="module-card-icon perf"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
          <div><div class="module-card-name">Performance</div><div class="module-card-sub">Sichtbarkeit &amp; Rankings</div></div>
        </div>
        <div class="module-card-score neutral" id="mc-perf-score">–</div>
        <div class="module-card-bar-bg"><div class="module-card-bar neutral" id="mc-perf-bar" style="width:0%"></div></div>
        <div class="module-card-label" id="mc-perf-label">Noch nicht analysiert</div>
      </div>
      <div class="module-card" onclick="showView('geo')">
        <div class="module-card-header">
          <div class="module-card-icon geo"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8l4 4-4 4-4-4 4-4z"/></svg></div>
          <div><div class="module-card-name">GEO / AEO</div><div class="module-card-sub">KI-Sichtbarkeit</div></div>
        </div>
        <div class="module-card-score neutral" id="mc-geo-score">–</div>
        <div class="module-card-bar-bg"><div class="module-card-bar neutral" id="mc-geo-bar" style="width:0%"></div></div>
        <div class="module-card-label" id="mc-geo-label">Noch nicht analysiert</div>
      </div>
      <div class="module-card" onclick="showView('keywords')">
        <div class="module-card-header">
          <div class="module-card-icon" style="background:var(--bg4);color:var(--accent)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg></div>
          <div><div class="module-card-name">Keyword Fit</div><div class="module-card-sub">Intent &amp; Targeting</div></div>
        </div>
        <div class="module-card-score neutral" id="mc-kw-score">–</div>
        <div class="module-card-bar-bg"><div class="module-card-bar neutral" id="mc-kw-bar" style="width:0%"></div></div>
        <div class="module-card-label" id="mc-kw-label">Noch nicht analysiert</div>
      </div>
      <div class="module-card" id="mc-ux" onclick="showView('ux')">
        <div class="module-card-header">
          <div class="module-card-icon" style="background:var(--bg4);color:var(--blue)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
          <div><div class="module-card-name">UX / CRO</div><div class="module-card-sub">Nutzererlebnis &amp; Conversion</div></div>
        </div>
        <div class="module-card-score neutral" id="mc-ux-score">–</div>
        <div class="module-card-bar-bg"><div class="module-card-bar neutral" id="mc-ux-bar" style="width:0%"></div></div>
        <div class="module-card-label" id="mc-ux-label">Noch nicht analysiert</div>
      </div>
    </div>

    <!-- Page Preview (Above the Fold) -->
    <div class="page-preview-card" id="page-preview-card" style="display:none">
      <div class="page-preview-bar">
        <div class="page-preview-dots">
          <div class="page-preview-dot"></div>
          <div class="page-preview-dot"></div>
          <div class="page-preview-dot"></div>
        </div>
        <div class="page-preview-url-wrap" id="page-preview-url"></div>
      </div>
      <div class="page-preview-img-wrap">
        <img id="page-preview-img" src="" alt="Seiten-Vorschau (Above the Fold)">
      </div>
      <div class="page-preview-footer">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        Above the Fold — Screenshot 1280 × 900 px
      </div>
    </div>

    <!-- Radar Chart -->
    <div class="radar-card" id="radar-card" style="display:none">      <div class="radar-card-title">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5"/></svg>
        Score-Überblick
      </div>
      <div class="radar-wrap"><svg id="radar-svg" viewBox="0 0 300 200"></svg></div>
    </div>

    <!-- Top-Prioritäten -->
    <div class="top-priorities" id="top-priorities" style="display:none">
      <div class="top-priorities-title">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Top-Prioritäten
      </div>
      <div id="top-priorities-list"></div>
    </div>
  </div>
</div><!-- /view-overview -->

<!-- ═══════════════════════════════════════════════════════════
     VIEW: SQEG
════════════════════════════════════════════════════════════ -->
<div class="view-panel" id="view-sqeg">
  <div class="agent-bar">
    <button class="agent-badge" onclick="openAgentModal('sqeg')" id="agent-badge-sqeg">
      <span class="agent-dot idle" id="agent-dot-sqeg"></span>
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      SQEG-Analyst
      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </button>
    <span class="agent-custom-chip" id="agent-custom-chip-sqeg">Custom Prompt</span>
  </div>
  <div id="sqeg-results" style="display:none">
    <!-- Score Hero -->
    <div class="score-hero" id="score-hero" style="margin-top:28px">
      <div class="score-hero-num green" id="score-hero-num">–</div>
      <div class="score-hero-divider"></div>
      <div class="score-hero-meta">
        <div id="score-hero-level" class="score-hero-level green" data-tip="Lowest (0–39%): Sehr niedrige Qualität — schwerwiegende Mängel&#10;Low (40–59%): Unterdurchschnittlich — deutlicher Verbesserungsbedarf&#10;Medium (60–74%): Ausreichend — kleinere bis mittlere Mängel&#10;High (75–89%): Gute Qualität — kleinere Optimierungsmöglichkeiten&#10;Highest (90–100%): Exzellente Qualität — Referenzstandard erfüllt">High</div>
        <div class="score-hero-interp" id="score-hero-interp"></div>
        <div class="score-hero-bar-wrap">
          <div class="score-hero-bar-bg"><div class="score-hero-bar green" id="score-hero-bar" style="width:0%"></div></div>
        </div>
        <div class="score-hero-chips">
          <span class="score-chip" id="ymyl-badge" data-tip="YMYL (Your Money or Your Life): Kennzeichnet Seiten, bei denen Google besonders hohe Qualitätsanforderungen stellt — z.B. Finanzen, Gesundheit, Recht"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> –</span>
          <span class="score-chip green" id="chip-cnt-g" data-tip="Kriterien bestanden">✓ <span id="cnt-g">0</span></span>
          <span class="score-chip amber" id="chip-cnt-a" data-tip="Kriterien verbesserungswürdig">◑ <span id="cnt-a">0</span></span>
          <span class="score-chip red" id="chip-cnt-r" data-tip="Kriterien fehlerhaft">✗ <span id="cnt-r">0</span></span>
          <span class="score-chip" id="hero-total-chip" data-tip="Gesamtzahl der bewerteten SQEG-Kriterien"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg> <span id="cnt-total-sqeg">0</span> Prüfpunkte</span>
        </div>
      </div>
      <div class="score-hero-actions">
        <button class="btn-secondary" onclick="startAnalysis()"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg> Re-Analyse</button>
        <button class="btn-secondary" onclick="exportHtml()">↓ Bericht</button>
      </div>
      <div id="sqeg-page-thumb" style="display:none;width:110px;height:74px;border-radius:var(--radius-sm);overflow:hidden;flex-shrink:0;border:1px solid var(--border);cursor:pointer" onclick="showView('ux')" data-tip="Seiten-Screenshot — klicken für UX/CRO-Analyse">
        <img id="sqeg-page-thumb-img" src="" alt="Vorschau" style="width:100%;height:100%;object-fit:cover;object-position:top;display:block">
      </div>
    </div>
    <div id="score-badge" style="display:none"></div>

    <!-- Executive Summary -->
    <div class="exec-summary-card" id="exec-summary" style="display:none">
      <div class="exec-summary-header">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        <span class="exec-summary-title">Executive Summary</span>
      </div>
      <div class="exec-summary-loading" id="exec-summary-loading">
        <div class="loader-dots"><div class="loader-dot"></div><div class="loader-dot"></div><div class="loader-dot"></div></div>
        <span>Zusammenfassung wird erstellt…</span>
      </div>
      <div id="exec-summary-content" style="display:none"></div>
    </div>

    <div class="section-divider"><div class="section-divider-line"></div><span class="section-divider-label">Cluster-Übersicht</span><div class="section-divider-line"></div></div>
    <div class="cluster-overview" id="cluster-overview"></div>
    <button id="detail-toggle-btn" onclick="toggleDetailTable()" aria-expanded="false" aria-controls="detail-table-wrap" style="display:flex;align-items:center;justify-content:space-between;width:100%;margin:24px 0 0;padding:10px 16px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;font-size:12px;font-weight:600;color:var(--text2);transition:background .15s,border-color .15s">
      <span style="display:flex;align-items:center;gap:7px"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Detailanalyse — alle 42 Kriterien</span>
      <svg id="detail-toggle-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="transition:transform .2s;color:var(--text3)"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div id="detail-table-wrap" style="display:none;margin-top:12px">
    <div class="filter-bar">
      <button class="filter-btn active" data-filter="all" onclick="setFilter('all',this)">Alle</button>
      <button class="filter-btn" data-filter="green" onclick="setFilter('green',this)">✓ Bestanden</button>
      <button class="filter-btn" data-filter="amber" onclick="setFilter('amber',this)">◑ Verbesserbar</button>
      <button class="filter-btn" data-filter="red" onclick="setFilter('red',this)">✗ Fehlerhaft</button>
    </div>
    <div class="criteria-table-wrap"><table class="criteria-table" id="criteria-table">
      <thead><tr><th style="width:44px">Status</th><th>Kriterium</th><th>Befund &amp; Bewertung</th><th style="width:28px"></th></tr></thead>
      <tbody id="criteria-tbody"></tbody>
    </table></div>
    </div><!-- /detail-table-wrap -->
    </div><!-- /sqeg-results -->
  <div id="sqeg-empty" style="padding:48px 0;text-align:center;color:var(--text3)">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.4"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <div style="font-size:14px;font-weight:600;margin-bottom:4px">Noch keine Analyse</div>
    <div style="font-size:12px">URL eingeben und Analyse starten</div>
  </div>
</div><!-- /view-sqeg -->

<!-- ═══════════════════════════════════════════════════════════
     VIEW: TECHNICAL SEO
════════════════════════════════════════════════════════════ -->
<div class="view-panel" id="view-technical">
  <div id="technical-results" style="display:none;margin-top:28px">

    <!-- Score Hero -->
    <div class="score-hero" id="tech-score-hero">
      <div class="score-hero-num green" id="tech-score-num">–</div>
      <div class="score-hero-divider"></div>
      <div class="score-hero-meta">
        <div id="tech-score-level" class="score-hero-level green">–</div>
        <div class="score-hero-interp" id="tech-score-interp"></div>
        <div class="score-hero-bar-wrap">
          <div class="score-hero-bar-bg"><div class="score-hero-bar green" id="tech-score-bar" style="width:0%"></div></div>
        </div>
        <div class="score-hero-chips">
          <span class="score-chip green" id="tech-chip-g" data-tip="Prüfpunkte bestanden">✓ <span id="tech-cnt-g">0</span></span>
          <span class="score-chip amber" id="tech-chip-a" data-tip="Prüfpunkte verbesserungswürdig">◑ <span id="tech-cnt-a">0</span></span>
          <span class="score-chip red" id="tech-chip-r" data-tip="Prüfpunkte fehlerhaft">✗ <span id="tech-cnt-r">0</span></span>
          <span class="score-chip" id="tech-chip-total" data-tip="Gesamtzahl der Prüfpunkte in 5 Bereichen"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg> <span id="tech-cnt-total">0</span> Prüfpunkte</span>
        </div>
      </div>
    </div>

    <!-- Executive Summary -->
    <div class="exec-summary-card" id="tech-exec-summary" style="display:none">
      <div class="exec-summary-header">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        <span class="exec-summary-title">Executive Summary</span>
      </div>
      <div id="tech-exec-summary-content"></div>
    </div>

    <div class="section-divider"><div class="section-divider-line"></div><span class="section-divider-label">Cluster-Übersicht</span><div class="section-divider-line"></div></div>
    <div class="cluster-overview" id="tech-cluster-overview"></div>

  </div>
  <div id="technical-empty" style="padding:48px 0;text-align:center;color:var(--text3)">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.4"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
    <div style="font-size:14px;font-weight:600;margin-bottom:4px">Noch keine Analyse</div>
    <div style="font-size:12px">URL eingeben und Analyse starten</div>
  </div>
</div><!-- /view-technical -->

<!-- ═══════════════════════════════════════════════════════════
     VIEW: PERFORMANCE
════════════════════════════════════════════════════════════ -->
<div class="view-panel" id="view-performance">
  <div id="perf-results" style="display:none;margin-top:28px">
    <div class="needs-met-block" id="gsc-panel" style="display:none">
      <div class="needs-met-label">GSC · Top-Keywords (90 Tage)</div>
      <div id="gsc-panel-content"></div>
    </div>
    <div class="needs-met-block" id="sistrix-panel" style="display:none">
      <div class="needs-met-label">Sistrix · URL-Sichtbarkeit (DE)</div>
      <div id="sistrix-panel-content"></div>
    </div>
  </div>
  <div id="perf-empty" style="padding:48px 0;text-align:center;color:var(--text3)">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.4"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    <div style="font-size:14px;font-weight:600;margin-bottom:4px">Noch keine Analyse</div>
    <div style="font-size:12px">URL eingeben und Analyse starten</div>
  </div>
</div><!-- /view-performance -->

<!-- ═══════════════════════════════════════════════════════════
     VIEW: GEO / AEO
════════════════════════════════════════════════════════════ -->
<div class="view-panel" id="view-geo">
  <div id="geo-results" style="display:none;margin-top:28px">
    <div class="needs-met-block" id="geo-panel" style="display:none">
      <div class="needs-met-label">GEO · KI-Sichtbarkeit (AI Search)</div>
      <div id="geo-panel-content"></div>
    </div>
  </div>
  <div id="geo-empty" style="padding:48px 0;text-align:center;color:var(--text3)">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.4"><circle cx="12" cy="12" r="10"/><path d="M12 8l4 4-4 4-4-4 4-4z"/></svg>
    <div style="font-size:14px;font-weight:600;margin-bottom:4px">Noch keine Analyse</div>
    <div style="font-size:12px">URL eingeben und Analyse starten</div>
  </div>
</div><!-- /view-geo -->

<!-- ═══════════════════════════════════════════════════════════
     VIEW: KEYWORD FIT
════════════════════════════════════════════════════════════ -->
<div class="view-panel" id="view-keywords">
  <div id="kw-results" style="display:none;margin-top:28px">
    <div class="needs-met-block" id="kw-intent-panel" style="display:block">
      <div class="needs-met-label">Keyword Fit · Intent-Analyse</div>
      <div id="kw-intent-content"></div>
    </div>
  </div>
  <div id="kw-empty" style="padding:48px 0;text-align:center;color:var(--text3)">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
    <div style="font-size:14px;font-weight:600;margin-bottom:4px">Noch keine Analyse</div>
    <div style="font-size:12px">URL eingeben und Analyse starten</div>
  </div>
</div><!-- /view-keywords -->

<!-- ═══════════════════════════════════════════════════════════
     VIEW: UX / CRO
════════════════════════════════════════════════════════════ -->
<div class="view-panel" id="view-ux">
  <div id="ux-results" style="display:none;margin-top:28px">
    <!-- Score Hero (UX) -->
    <div class="score-hero" id="ux-score-hero">
      <div class="score-hero-num green" id="ux-score-num">–</div>
      <div class="score-hero-divider"></div>
      <div class="score-hero-meta">
        <div class="score-hero-level green" id="ux-score-level">–</div>
        <div class="score-hero-interp" id="ux-score-interp" style="font-size:11px;color:var(--text3)">Ø Desktop + Mobile</div>
        <div class="score-hero-bar-wrap">
          <div class="score-hero-bar-bg"><div class="score-hero-bar green" id="ux-score-bar" style="width:0%"></div></div>
        </div>
        <div class="score-hero-chips">
          <span class="score-chip green" id="ux-chip-g">✓ <span id="ux-cnt-g">0</span></span>
          <span class="score-chip amber" id="ux-chip-a">◑ <span id="ux-cnt-a">0</span></span>
          <span class="score-chip red" id="ux-chip-r">✗ <span id="ux-cnt-r">0</span></span>
        </div>
      </div>
    </div>

    <!-- Device Tabs -->
    <div style="display:flex;gap:0;margin-bottom:0;border-bottom:2px solid var(--border)">
      <button id="ux-tab-mobile" onclick="showUxDevice('mobile')" style="padding:10px 20px;font-size:13px;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid var(--accent);margin-bottom:-2px;color:var(--accent)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>Mobile
        <span id="ux-tab-score-mobile" style="font-size:11px;font-family:'Geist Mono';margin-left:6px;color:var(--text3)"></span>
      </button>
      <button id="ux-tab-desktop" onclick="showUxDevice('desktop')" style="padding:10px 20px;font-size:13px;font-weight:600;border:none;background:none;cursor:pointer;color:var(--text3)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>Desktop
        <span id="ux-tab-score-desktop" style="font-size:11px;font-family:'Geist Mono';margin-left:6px;color:var(--text3)"></span>
      </button>
    </div>

    <!-- Mobile Panel -->
    <div id="ux-device-mobile">
      <div id="ux-loading-mobile" style="display:none;padding:32px 0;text-align:center;color:var(--text3)">
        <div class="loader-dots" style="justify-content:center;margin-bottom:10px"><div class="loader-dot"></div><div class="loader-dot"></div><div class="loader-dot"></div></div>
        <div style="font-size:12px">Mobile-Screenshot + Analyse läuft…</div>
      </div>
      <div class="needs-met-block" id="ux-checks-mobile" style="display:none;margin-top:20px">
        <div id="ux-checks-content-mobile"></div>
      </div>
      <div class="needs-met-block" id="ux-screenshot-mobile" style="display:none;margin-top:0">
        <div class="needs-met-label">Screenshot Mobile (375px)</div>
        <div style="margin-top:10px;border-radius:var(--radius-sm);overflow:hidden;border:1px solid var(--border);max-height:480px;overflow-y:auto">
          <img id="ux-shot-img-mobile" src="" alt="Mobile Screenshot" style="width:100%;display:block">
        </div>
      </div>
      <div class="needs-met-block" id="ux-comment-mobile" style="display:none;margin-top:0">
        <div class="needs-met-label">KI-Kommentar Mobile</div>
        <div id="ux-comment-content-mobile" style="font-size:13px;line-height:1.6;color:var(--text2)"></div>
      </div>
    </div>

    <!-- Desktop Panel -->
    <div id="ux-device-desktop" style="display:none">
      <div id="ux-loading-desktop" style="display:none;padding:32px 0;text-align:center;color:var(--text3)">
        <div class="loader-dots" style="justify-content:center;margin-bottom:10px"><div class="loader-dot"></div><div class="loader-dot"></div><div class="loader-dot"></div></div>
        <div style="font-size:12px">Desktop-Screenshot + Analyse läuft…</div>
      </div>
      <div class="needs-met-block" id="ux-checks-desktop" style="display:none;margin-top:20px">
        <div id="ux-checks-content-desktop"></div>
      </div>
      <div class="needs-met-block" id="ux-screenshot-desktop" style="display:none;margin-top:0">
        <div class="needs-met-label">Screenshot Desktop (1280px)</div>
        <div style="margin-top:10px;border-radius:var(--radius-sm);overflow:hidden;border:1px solid var(--border);max-height:480px;overflow-y:auto">
          <img id="ux-shot-img-desktop" src="" alt="Desktop Screenshot" style="width:100%;display:block">
        </div>
      </div>
      <div class="needs-met-block" id="ux-comment-desktop" style="display:none;margin-top:0">
        <div class="needs-met-label">KI-Kommentar Desktop</div>
        <div id="ux-comment-content-desktop" style="font-size:13px;line-height:1.6;color:var(--text2)"></div>
      </div>
    </div>
  </div>

  <div id="ux-loading" style="display:none;padding:48px 0;text-align:center;color:var(--text3)">
    <div class="loader-dots" style="justify-content:center;margin-bottom:12px"><div class="loader-dot"></div><div class="loader-dot"></div><div class="loader-dot"></div></div>
    <div style="font-size:13px;font-weight:600;margin-bottom:4px">UX-Analyse läuft…</div>
    <div style="font-size:12px" id="ux-loading-msg">Screenshot wird erstellt</div>
  </div>
  <div id="ux-empty" style="padding:48px 0;text-align:center;color:var(--text3)">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.4"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
    <div style="font-size:14px;font-weight:600;margin-bottom:4px">Noch keine Analyse</div>
    <div style="font-size:12px">URL eingeben und Analyse starten</div>
  </div>
</div><!-- /view-ux -->

<!-- ═══════════════════════════════════════════════════════════
     VIEW: LOCAL PV GENERATOR
════════════════════════════════════════════════════════════ -->
<div class="view-panel" id="view-localpv">
  <div class="input-card">
    <div class="card-header">
      <div class="card-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      </div>
      <div>
        <div class="card-title">Local PV Generator</div>
        <div class="card-sub">Erzeuge strukturierte SEO- und CRO-Bausteine für lokale Photovoltaik-Landingpages auf Basis von Stadt oder PLZ.</div>
      </div>
    </div>

    <div class="pv-input-grid">
      <div class="full">
        <div class="settings-field">
          <label class="settings-label" for="pv-city">Stadt oder PLZ <span style="color:var(--red)">*</span></label>
          <input type="text" id="pv-city" class="settings-input" placeholder="z.B. Darmstadt oder 64283" autocomplete="off" spellcheck="false">
        </div>
      </div>
      <div>
        <div class="settings-field" style="margin-top:0">
          <label class="settings-label" for="pv-product">Produkt <span style="color:var(--text3);font-weight:400">(optional)</span></label>
          <input type="text" id="pv-product" class="settings-input" placeholder="z.B. Photovoltaikanlage" autocomplete="off" spellcheck="false">
        </div>
      </div>
      <div>
        <div class="settings-field" style="margin-top:0">
          <label class="settings-label" style="display:flex;align-items:center;justify-content:space-between" for="pv-keyword">
            <span>Primäres Keyword <span style="color:var(--text3);font-weight:400">(optional)</span></span>
            <button id="pv-kw-suggest-btn" onclick="pvSuggestKeywords()" style="font-size:11px;padding:3px 8px;background:var(--bg3);border:1px solid var(--border2);border-radius:var(--radius-sm);cursor:pointer;color:var(--text2);font-family:inherit;font-weight:500;display:flex;align-items:center;gap:4px">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              Keywords vorschlagen
            </button>
          </label>
          <input type="text" id="pv-keyword" class="settings-input" placeholder="z.B. photovoltaik darmstadt" autocomplete="off" spellcheck="false">
          <div id="pv-kw-pills" style="display:none"></div>
        </div>
      </div>
      <div>
        <div class="settings-field" style="margin-top:0">
          <label class="settings-label" for="pv-url">Bestehende Landingpage-URL <span style="color:var(--text3);font-weight:400">(optional)</span></label>
          <input type="url" id="pv-url" class="settings-input" placeholder="https://example.com/pv/darmstadt" autocomplete="off">
        </div>
      </div>
      <div class="full">
        <div class="settings-field" style="margin-top:0">
          <label class="settings-label" for="pv-template">Seitentyp / Template <span style="color:var(--text3);font-weight:400">(optional)</span></label>
          <input type="text" id="pv-template" class="settings-input" placeholder="z.B. Stadtlandingpage, PLZ-Seite, Produktseite" autocomplete="off" spellcheck="false">
        </div>
      </div>
    </div>

    <div id="pv-validation-msg" style="display:none;color:var(--red);font-size:12px;margin-top:8px;font-weight:500">
      Bitte Stadt oder PLZ eingeben.
    </div>

    <button class="pv-generate-btn" id="pv-btn-generate" onclick="pvGenerate()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
      Bausteine generieren
    </button>
    <button class="pv-generate-btn" onclick="pvDemo()" style="background:var(--bg3);border-color:var(--border2);color:var(--text2);margin-top:8px" title="Demo-Daten laden ohne API-Call">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
      Demo
    </button>

    <div style="margin-top:20px;border-top:1px solid var(--border);padding-top:16px">
      <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);margin-bottom:8px">Perspektivisch nutzbare Datenquellen</div>
      <div class="pv-sources-row">
        <div class="pv-source-badge">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 21l-4.35-4.35M11 19A8 8 0 1 1 19 11"/></svg>
          Google Search Console
        </div>
        <div class="pv-source-badge">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          Sistrix
        </div>
        <div class="pv-source-badge">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          DataForSEO
        </div>
        <div class="pv-source-badge">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
          PVGIS / DWD (geplant)
        </div>
      </div>
    </div>
  </div>

  <!-- Lade-Zustand -->
  <div id="pv-loading" style="display:none">
    <div class="pv-loading">
      <div class="pv-loading-spinner"></div>
      <span id="pv-loading-status">Standort wird geprüft…</span>
      <span style="font-size:11px;color:var(--text3)">Dies kann 15–30 Sekunden dauern.</span>
    </div>
  </div>

  <!-- Fehler-Zustand -->
  <div id="pv-error" style="display:none">
    <div class="pv-error-box" id="pv-error-msg"></div>
  </div>

  <!-- Ergebnisbereich mit Tabs -->
  <div id="pv-results" style="display:none">
    <!-- DWD Standort-Solardaten (persistent, oberhalb Tabs) -->
    <div id="pv-dwd-banner" style="display:none"></div>
    <div class="pv-tabs">
      <button class="pv-tab-btn active" onclick="pvSwitchTab('content',this)">Content</button>
      <button class="pv-tab-btn" onclick="pvSwitchTab('placement',this)">Placement Map</button>
      <button class="pv-tab-btn" onclick="pvSwitchTab('checks',this)">SEO / CRO Checks</button>
      <button class="pv-tab-btn" onclick="pvSwitchTab('export',this)">Markdown Export</button>
      <button class="pv-refine-btn" id="pv-btn-refine" onclick="pvRefine()" title="Content mit einem zweiten KI-Pass schärfen">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        Content schärfen
      </button>
      <button class="pv-refine-btn" id="pv-btn-convert" onclick="pvConvert()" title="Auf Basis von Level 2 conversion-optimieren" disabled style="background:var(--amber-bg);border-color:var(--amber-border);color:var(--amber)">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Conversion optimieren
      </button>
    </div>
    <div class="pv-version-bar" id="pv-version-bar" style="display:none">
      <span class="pv-version-label">Version:</span>
      <button class="pv-version-btn active" id="pvv-raw" onclick="pvSwitchVersion('raw')">Rohfassung</button>
      <button class="pv-version-btn" id="pvv-sharpened" onclick="pvSwitchVersion('sharpened')" disabled>Content geschärft</button>
      <button class="pv-version-btn" id="pvv-conversion" onclick="pvSwitchVersion('conversion')" disabled>Conversion optimiert</button>
    </div>
    <div id="pv-tab-content" class="pv-tab-panel active" style="margin-bottom:48px">
      <div id="pv-results-list" style="margin-top:16px"></div>
    </div>
    <div id="pv-tab-placement" class="pv-tab-panel" style="margin-bottom:48px">
      <div id="pv-placement-list" style="margin-top:16px"></div>
    </div>
    <div id="pv-tab-checks" class="pv-tab-panel" style="margin-bottom:48px">
      <div id="pv-checks-list" style="margin-top:16px"></div>
    </div>
    <div id="pv-tab-export" class="pv-tab-panel" style="margin-bottom:48px">
      <div id="pv-export-content" style="margin-top:16px"></div>
    </div>
  </div><!-- /#pv-results -->
</div><!-- /view-localpv -->

<!-- ══════════════════════════════════════════════════════════════════════════
     VIEW: Content Finder
══════════════════════════════════════════════════════════════════════════ -->
<div class="view-panel" id="view-content-finder">
  <div class="cf-layout">

    <!-- ── Linke Spalte: Konfiguration ── -->
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Schritt 1: URL-Eingabe -->
      <div class="input-card">
        <div class="card-header">
          <div class="card-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
          </div>
          <div>
            <div class="card-title">1 · URL-Eingabe</div>
            <div class="card-sub">Manuell oder per CSV/Excel-Upload</div>
          </div>
        </div>
        <!-- Tab-Leiste -->
        <div style="display:flex;border-bottom:1px solid var(--border);margin-bottom:14px">
          <button class="cf-tab-btn active" id="cf-tab-btn-manual" onclick="cfSwitchTab('manual')" style="padding:7px 16px;font-size:13px;font-weight:500;color:var(--text2);border:none;background:none;cursor:pointer;border-bottom:2px solid var(--accent);margin-bottom:-1px;color:var(--accent);font-family:inherit">Manuelle URLs</button>
          <button class="cf-tab-btn" id="cf-tab-btn-file" onclick="cfSwitchTab('file')" style="padding:7px 16px;font-size:13px;font-weight:500;color:var(--text2);border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;font-family:inherit">CSV/Excel Upload</button>
        </div>
        <!-- Tab: Manuelle URLs -->
        <div id="cf-tab-manual">
          <div class="settings-field">
            <label class="settings-label">URLs <span style="color:var(--text3);font-weight:400">(eine pro Zeile)</span></label>
            <textarea id="cf-url-input" class="settings-input" rows="5" placeholder="https://www.beispiel.de/oekostrom&#10;https://www.beispiel.de/photovoltaik&#10;https://www.beispiel.de/beg-foerderung" style="resize:vertical;font-family:'Geist Mono',monospace;font-size:12px"></textarea>
            <div style="font-size:11px;color:var(--text3);margin-top:4px">Unterseiten werden gemäß Crawl-Tiefe automatisch erfasst.</div>
          </div>
          <div class="settings-field" style="margin-top:12px">
            <label class="settings-label" data-tip="Wie viele Ebenen an Unterseiten sollen gecrawlt werden?&#10;&#10;Tiefe 0: Nur die eingegebenen URLs selbst.&#10;Tiefe 1: Diese URLs + alle direkten Unterseiten&#10;         (nur gleiche Domain & gleicher Pfad).&#10;Tiefe 2: Zusätzlich noch eine weitere Ebene darunter.">Crawl-Tiefe</label>
            <select id="cf-depth" class="settings-input">
              <option value="0">Nur diese URL (keine Links folgen)</option>
              <option value="1" selected>1 Ebene tiefer (direkte Unterseiten)</option>
              <option value="2">2 Ebenen tiefer</option>
            </select>
          </div>
        </div>
        <!-- Tab: Upload -->
        <div id="cf-tab-file" style="display:none">
          <div class="pv-upload-zone" id="cf-upload-zone" onclick="document.getElementById('cf-file-input').click()" style="border:1.5px dashed var(--border2);border-radius:var(--radius-lg);padding:28px 16px;text-align:center;background:var(--bg3);cursor:pointer;transition:border-color .12s">
            <div style="font-size:28px">📊</div>
            <div style="font-size:13px;font-weight:600;color:var(--text);margin-top:8px">CSV oder Excel hier ablegen</div>
            <div style="font-size:11px;color:var(--text3);margin-top:4px">.csv · .xlsx (als CSV gespeichert) &nbsp;|&nbsp; Spalte „URL" wird erkannt</div>
            <input type="file" id="cf-file-input" accept=".csv,.txt" style="display:none" onchange="cfHandleFileUpload(this)">
          </div>
          <div id="cf-file-status" style="font-size:12px;color:var(--text2);margin-top:10px;display:none"></div>
        </div>
        <!-- URL-Zähler -->
        <div id="cf-url-count" style="font-size:11px;color:var(--text3);margin-top:8px;display:none"></div>
      </div>

      <!-- Schritt 2: Suchbegriffe -->
      <div class="input-card">
        <div class="card-header">
          <div class="card-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
          </div>
          <div>
            <div class="card-title">2 · Suchbegriffe</div>
            <div class="card-sub">Varianten und Synonyme werden automatisch generiert</div>
          </div>
        </div>
        <!-- Aktive Begriffe (Chips) -->
        <div class="settings-field">
          <label class="settings-label">Aktive Begriffe</label>
          <div class="cf-chip-container" id="cf-chips"></div>
        </div>
        <!-- Neuen Begriff hinzufügen -->
        <div class="cf-add-row">
          <input type="text" id="cf-term-input" class="settings-input" placeholder="Begriff eingeben und Enter …" autocomplete="off" spellcheck="false">
          <button class="pv-generate-btn" onclick="cfAddTermFromInput()" style="margin-top:0;padding:8px 14px">＋</button>
        </div>
        <!-- Varianten-Vorschau -->
        <div class="cf-variant-box" id="cf-variant-box" style="display:none">
          <div class="cf-variant-box-label" id="cf-variant-box-label">Varianten</div>
          <div class="cf-variant-chips" id="cf-variant-chips"></div>
          <div class="cf-variant-legend">
            <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--text);margin-right:3px"></span>Exakt</span>
            <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--blue);margin-right:3px"></span>Schreibvariante</span>
            <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--green);margin-right:3px"></span>Synonym (KI)</span>
          </div>
        </div>

        <!-- Ausschluss-Begriffe -->
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
          <label class="settings-label" style="display:flex;align-items:center;gap:6px" data-tip="Treffer werden ausgeblendet, wenn das vollständige Wort&#10;rund um den Match einen Ausschluss-Begriff enthält.&#10;&#10;Beispiel: Suchbegriff ‚BEG', Ausschluss ‚GewerBEGas'&#10;→ Treffer in ‚GewerBEGas' wird unterdrückt.&#10;&#10;Kein Re-Crawl nötig — wirkt sofort auf alle Ergebnisse.">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--red)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            Ausschluss-Begriffe
            <span style="font-weight:400;color:var(--text3)">(Treffer die dieses Wort enthalten, werden ausgeblendet)</span>
          </label>
          <div id="cf-exclude-chips" style="min-height:36px;padding:6px 8px;border:1px solid var(--border2);border-radius:var(--radius);background:var(--bg3);display:flex;flex-wrap:wrap;gap:5px;margin-bottom:8px"></div>
          <div class="cf-add-row">
            <input type="text" id="cf-exclude-input" class="settings-input" placeholder="z.B. GewerBEGas, Vergabe …" autocomplete="off" spellcheck="false">
            <button class="pv-generate-btn" onclick="cfAddExclude()" style="margin-top:0;padding:8px 14px;background:var(--red-bg);border:1px solid var(--red-border);color:var(--red)">＋</button>
          </div>
          <div style="font-size:11px;color:var(--text3);margin-top:5px">Treffer werden live gefiltert — kein Re-Crawl nötig.</div>
        </div>
      </div>

      <!-- Schritt 3: Optionen -->
      <div class="input-card">
        <div class="card-header">
          <div class="card-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/><circle cx="9" cy="6" r="2" fill="currentColor" stroke="none"/><circle cx="16" cy="12" r="2" fill="currentColor" stroke="none"/><circle cx="9" cy="18" r="2" fill="currentColor" stroke="none"/></svg>
          </div>
          <div>
            <div class="card-title">3 · Erfassungsoptionen</div>
          </div>
        </div>
        <div class="cf-opt-grid">
          <div class="cf-opt on" id="cfopt-plural"      onclick="cfToggleOpt(this,'plural')"      data-tip="Sucht nach weiteren deutschen Wortformen.&#10;Beispiel: ‚Förderung' → findet auch ‚Förderungen'&#10;         ‚Antrag' → findet auch ‚Anträge'">      <div class="cf-toggle"></div>Singular / Plural</div>
          <div class="cf-opt on" id="cfopt-hyphen"      onclick="cfToggleOpt(this,'hyphen')"      data-tip="Findet Schreibweisen mit und ohne Bindestrich.&#10;Beispiel: ‚BEG Förderung' → findet auch ‚BEG-Förderung'&#10;         ‚Photovoltaik Anlage' → ‚Photovoltaik-Anlage'">      <div class="cf-toggle"></div>Bindestrich-Varianten</div>
          <div class="cf-opt on" id="cfopt-umlauts"     onclick="cfToggleOpt(this,'umlauts')"     data-tip="Findet Umlaut-Umschreibungen automatisch.&#10;Beispiel: ‚Ökostrom' → findet auch ‚Oekostrom'&#10;         ‚Förderung' → findet auch ‚Foerderung'">     <div class="cf-toggle"></div>oe ↔ ö / ae ↔ ä / ue ↔ ü</div>
          <div class="cf-opt on" id="cfopt-ai_synonyms" onclick="cfToggleOpt(this,'ai_synonyms')" data-tip="Generiert echte Synonyme via OpenAI (1× pro Begriff, gecacht).&#10;Beispiel: ‚Photovoltaik' → ‚Solar', ‚PV-Anlage', ‚Solarstrom'&#10;Erfordert konfigurierten OpenAI-API-Key."> <div class="cf-toggle"></div>KI-Synonyme (OpenAI)</div>
          <div class="cf-opt on" id="cfopt-partial"     onclick="cfToggleOpt(this,'partial')"     data-tip="Findet den Begriff auch als Teil längerer Wörter.&#10;Beispiel: ‚Ökostrom' trifft auf ‚Ökostromanbieter'&#10;Deaktivieren wenn nur eigenständige Wörter gesucht werden.">     <div class="cf-toggle"></div>Teilwort-Treffer</div>
          <div class="cf-opt on" id="cfopt-js"          onclick="cfToggleOpt(this,'js')"          data-tip="Lädt die Seite vollständig mit Headless-Browser (Puppeteer).&#10;Notwendig für React-, Angular- und andere JS-gerenderte Seiten.&#10;Langsamer, aber vollständig — empfohlen für moderne Websites.">          <div class="cf-toggle"></div>JS-Rendering (Puppeteer)</div>
          <div class="cf-opt on" id="cfopt-ocr"         onclick="cfToggleOpt(this,'ocr')"         data-tip="Extrahiert Text aus Bildern ohne Alt-Text&#10;via OpenAI Vision API (gpt-4o).&#10;Nur für Bilder ≥ 100×80 px. Erfordert OpenAI-Key.">         <div class="cf-toggle"></div>Bild-OCR (OpenAI Vision)</div>
          <div class="cf-opt"    id="cfopt-case"        onclick="cfToggleOpt(this,'case')"        data-tip="Standard aus: ‚BEG' findet auch ‚beg' und ‚Beg'.&#10;Einschalten wenn Groß-/Kleinschreibung unterschieden werden soll,&#10;z.B. Abkürzungen (‚BEG') von normalen Wörtern trennen.">        <div class="cf-toggle"></div>Groß-/Kleinschreibung</div>
        </div>
        <div style="font-size:11px;color:var(--text3);margin-top:10px;line-height:1.5">
          Teilwort findet „Ökostromanbieter" bei Suche nach „Ökostrom". · Bild-OCR extrahiert Text aus Grafiken.
        </div>
      </div>

      <!-- Start-Button -->
      <button class="cf-run-btn" id="cf-run-btn" onclick="cfStart()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
        Content-Analyse starten
      </button>
      <button id="cf-stop-btn" onclick="cfStop()" style="display:none;width:100%;padding:9px;font-size:13px;font-weight:600;background:var(--bg3);color:var(--red);border:1px solid var(--red-border);border-radius:var(--radius);cursor:pointer;font-family:inherit;margin-top:8px">■ Analyse stoppen</button>

    </div>
    <!-- END linke Spalte -->

    <!-- ── Rechte Spalte: Fortschritt + Ergebnisse ── -->
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Fortschritt -->
      <div class="input-card" id="cf-progress-card" style="display:none">
        <div class="card-header">
          <div class="card-icon" style="background:var(--blue-bg)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-.28-4.5"/></svg>
          </div>
          <div>
            <div class="card-title">Analyse-Fortschritt</div>
            <div class="card-sub" id="cf-progress-label">Wird gestartet …</div>
          </div>
        </div>
        <div class="cf-progress-outer"><div class="cf-progress-inner" id="cf-progress-bar"></div></div>
        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text3);margin-top:4px">
          <span id="cf-progress-text">0 von 0 URLs</span>
          <span id="cf-progress-pct">0 %</span>
        </div>
        <div class="cf-crawl-list" id="cf-crawl-list"></div>
      </div>

      <!-- Stat-Grid (nach Abschluss) -->
      <div class="cf-stat-grid" id="cf-stat-grid" style="display:none">
        <div class="cf-stat-card" data-tip="Alle gefundenen Treffer über alle URLs."><div class="cf-stat-val" id="cf-stat-hits" style="color:var(--accent)">0</div><div class="cf-stat-label">Treffer gesamt</div></div>
        <div class="cf-stat-card" data-tip="Anzahl der gecrawlten Seiten, auf denen&#10;mindestens ein Begriff gefunden wurde."><div class="cf-stat-val" id="cf-stat-pages">0</div><div class="cf-stat-label">Seiten mit Treffern</div></div>
        <div class="cf-stat-card" data-tip="Treffer, die in Bildern ohne Alt-Text&#10;per OpenAI Vision (OCR) gefunden wurden."><div class="cf-stat-val" id="cf-stat-ocr" style="color:var(--amber)">0</div><div class="cf-stat-label">Bild-OCR Treffer</div></div>
        <div class="cf-stat-card" data-tip="Treffer auf KI-generierten Synonymen&#10;(nicht auf dem eingegebenen Begriff selbst)."><div class="cf-stat-val" id="cf-stat-synonyms" style="color:var(--green)">0</div><div class="cf-stat-label">Synonym-Treffer</div></div>
      </div>

      <!-- Ergebnisse -->
      <div class="input-card" id="cf-results-card" style="display:none">
        <div class="card-header">
          <div class="card-icon" style="background:var(--green-bg)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div style="flex:1">
            <div class="card-title">Ergebnisse</div>
            <div class="card-sub" id="cf-results-summary">–</div>
          </div>
          <div style="display:flex;gap:6px">
            <button onclick="cfExport('csv')"  style="padding:5px 10px;font-size:11px;font-weight:600;background:var(--bg3);border:1px solid var(--border2);border-radius:var(--radius-sm);cursor:pointer;color:var(--text2);font-family:inherit">↓ CSV</button>
            <button onclick="cfExport('json')" style="padding:5px 10px;font-size:11px;font-weight:600;background:var(--bg3);border:1px solid var(--border2);border-radius:var(--radius-sm);cursor:pointer;color:var(--text2);font-family:inherit">↓ JSON</button>
          </div>
        </div>
        <!-- Filter-Leiste -->
        <div class="cf-filter-row">
          <input type="text" id="cf-filter-text" placeholder="🔍 Ergebnisse filtern …" oninput="cfRenderTable()">
          <select id="cf-filter-term" onchange="cfRenderTable()"><option value="">Alle Begriffe</option></select>
          <select id="cf-filter-type" onchange="cfRenderTable()">
            <option value="">Alle Typen</option>
            <option value="exact">Exakt</option>
            <option value="variant">Schreibvariante</option>
            <option value="synonym">Synonym</option>
          </select>
          <select id="cf-filter-source" onchange="cfRenderTable()">
            <option value="">Alle Quellen</option>
            <option value="Bild-Alt">Bild (Alt)</option>
            <option value="Bild-OCR">Bild-OCR</option>
            <option value="Meta-Title">Meta-Title</option>
            <option value="Meta-Description">Meta-Description</option>
          </select>
        </div>
        <!-- Tabelle -->
        <div style="overflow-x:auto">
          <table class="cf-table">
            <thead>
              <tr>
                <th>URL</th>
                <th data-tip="Der eingegebene Suchbegriff, für den dieser Treffer gefunden wurde.">Suchbegriff</th>
                <th data-tip="Das tatsächlich gefundene Wort.&#10;Schwarz = exakter Treffer&#10;Blau = Schreibvariante (Bindestrich, Umlaut, Plural)&#10;Grün = KI-Synonym">Variante</th>
                <th data-tip="Textausschnitt rund um den Treffer (± 80 Zeichen).&#10;Der Treffer ist gelb hervorgehoben.">Kontext</th>
                <th data-tip="Wo auf der Seite der Treffer gefunden wurde.&#10;H1–H6 = Überschrift&#10;Absatz / Liste / Tabelle = Fließtext&#10;Bild-Alt = Alt-Attribut eines Bildes&#10;Bild-OCR = Aus Bild extrahierter Text&#10;Meta-Title / Meta-Description = Seitenmeta">Position</th>
              </tr>
            </thead>
            <tbody id="cf-table-body">
              <tr><td colspan="5" class="cf-empty">Keine Ergebnisse vorhanden.</td></tr>
            </tbody>
          </table>
        </div>
        <div id="cf-table-more" style="font-size:11px;color:var(--text3);margin-top:8px;text-align:right;display:none"></div>
      </div>

      <!-- Empty state (vor Analyse) -->
      <div id="cf-empty-state" class="input-card" style="padding:48px 24px;text-align:center">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text3)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 16px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
        <div style="font-size:14px;font-weight:600;color:var(--text2)">Bereit für die Analyse</div>
        <div style="font-size:12px;color:var(--text3);margin-top:6px;max-width:280px;margin-left:auto;margin-right:auto;line-height:1.5">URLs eingeben, Suchbegriffe definieren und „Content-Analyse starten" klicken.</div>
      </div>

    </div>
    <!-- END rechte Spalte -->

  </div>
</div><!-- /view-content-finder -->

<div class="view-panel" id="view-settings">
  <div class="input-card">
    <div class="card-header">
      <div class="card-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M21 12h-2M19.07 19.07l-1.41-1.41M12 21v-2M4.93 19.07l1.41-1.41M3 12h2M4.93 4.93l1.41 1.41"/></svg>
      </div>
      <div>
        <div class="card-title">Einstellungen</div>
        <div class="card-sub">API-Keys · Modell · Passwort</div>
      </div>
    </div>
    <!-- Credential-Status-Übersicht -->
    <div id="cred-status-bar" style="display:flex;flex-wrap:wrap;gap:8px;padding:12px 0 4px;margin-bottom:4px;border-bottom:1px solid var(--border)"></div>
    <div style="font-size:11px;color:var(--text3);margin:8px 0 20px">
      <strong style="color:var(--text)">Railway ENV</strong> hat immer Vorrang über hier gespeicherte Werte. Bei aktiven ENV-Variablen sind die Felder gesperrt.
    </div>
    <div class="settings-section">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
        <div class="settings-section-title" style="margin:0">Anthropic API-Key</div>
        <span id="src-badge-anthropic" class="src-badge"></span>
      </div>
      <div class="settings-section-desc">Erforderlich für den SQEG Analyzer. Erhältlich unter console.anthropic.com.</div>
      <div id="key-masked-display" class="key-masked"></div>
      <form id="form-apikey" onsubmit="saveApiKey(event)">
        <div class="settings-field">
          <label class="settings-label" for="s-apikey">API-Key</label>
          <div class="settings-input-wrap">
            <input type="password" id="s-apikey" class="settings-input" placeholder="sk-ant-…" autocomplete="off">
            <button type="button" class="settings-toggle-btn" onclick="toggleSettingsPw('s-apikey',this)">Anzeigen</button>
          </div>
        </div>
        <button type="submit" class="btn-save" id="btn-save-anthropic">Speichern</button>
        <div class="success-msg" id="msg-apikey">✓ API-Key gespeichert.</div>
        <div class="err-box" id="err-apikey" style="display:none;margin-top:10px;"></div>
      </form>
    </div>
    <div style="height:1px;background:var(--border);margin:24px 0"></div>
    <div class="settings-section">
      <div class="settings-section-title">KI-Modell</div>
      <div class="settings-section-desc">Claude-Modell für die SQEG-Analyse auswählen.</div>
      <form id="form-model" onsubmit="saveModel(event)">
        <div class="settings-field">
          <label class="settings-label" for="s-model">Modell</label>
          <select id="s-model" class="settings-input" style="font-family:'Inter',sans-serif;cursor:pointer">
            <option value="claude-sonnet-4-5">claude-sonnet-4-5 (Standard)</option>
            <option value="claude-opus-4-5">claude-opus-4-5 (leistungsstärker)</option>
            <option value="claude-haiku-4-5">claude-haiku-4-5 (schneller / günstiger)</option>
          </select>
        </div>
        <button type="submit" class="btn-save">Speichern</button>
        <div class="success-msg" id="msg-model">✓ Modell gespeichert.</div>
      </form>
    </div>
    <div style="height:1px;background:var(--border);margin:24px 0"></div>
    <div class="settings-section">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
        <div class="settings-section-title" style="margin:0">DataForSEO</div>
        <span id="src-badge-dataforseo" class="src-badge"></span>
      </div>
      <div class="settings-section-desc">Login (E-Mail) und API-Passwort für DataForSEO. Erhältlich unter app.dataforseo.com.</div>
      <form id="form-dataforseo" onsubmit="saveDataforSeo(event)">
        <div class="settings-field">
          <label class="settings-label" for="s-dfs-login">E-Mail (Login)</label>
          <input type="email" id="s-dfs-login" class="settings-input" placeholder="user@example.com" autocomplete="off">
        </div>
        <div class="settings-field">
          <label class="settings-label" for="s-dfs-pw">API-Passwort</label>
          <div class="settings-input-wrap">
            <input type="password" id="s-dfs-pw" class="settings-input" placeholder="Passwort" autocomplete="off">
            <button type="button" class="settings-toggle-btn" onclick="toggleSettingsPw('s-dfs-pw',this)">Anzeigen</button>
          </div>
        </div>
        <button type="submit" class="btn-save" id="btn-save-dataforseo">Speichern</button>
        <div class="success-msg" id="msg-dataforseo">✓ DataForSEO-Zugangsdaten gespeichert.</div>
        <div class="err-box" id="err-dataforseo" style="display:none;margin-top:10px;"></div>
      </form>
    </div>
    <div style="height:1px;background:var(--border);margin:24px 0"></div>
    <div class="settings-section">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
        <div class="settings-section-title" style="margin:0">Sistrix</div>
        <span id="src-badge-sistrix" class="src-badge"></span>
      </div>
      <div class="settings-section-desc">API-Key für Sistrix. Erhältlich unter app.sistrix.com unter API-Zugang.</div>
      <form id="form-sistrix" onsubmit="saveSistrix(event)">
        <div class="settings-field">
          <label class="settings-label" for="s-sistrix">API-Key</label>
          <div class="settings-input-wrap">
            <input type="password" id="s-sistrix" class="settings-input" placeholder="Sistrix API-Key" autocomplete="off">
            <button type="button" class="settings-toggle-btn" onclick="toggleSettingsPw('s-sistrix',this)">Anzeigen</button>
          </div>
        </div>
        <button type="submit" class="btn-save" id="btn-save-sistrix">Speichern</button>
        <div class="success-msg" id="msg-sistrix">✓ Sistrix API-Key gespeichert.</div>
        <div class="err-box" id="err-sistrix" style="display:none;margin-top:10px;"></div>
      </form>
    </div>
    <div style="height:1px;background:var(--border);margin:24px 0"></div>
    <div class="settings-section">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
        <div class="settings-section-title" style="margin:0">PageSpeed Insights</div>
        <span id="src-badge-pagespeed" class="src-badge"></span>
      </div>
      <div class="settings-section-desc">Google API-Key für PageSpeed Insights (optional — ohne Key gilt ein Rate-Limit). Erstellen unter console.cloud.google.com.</div>
      <form id="form-pagespeed" onsubmit="savePageSpeed(event)">
        <div class="settings-field">
          <label class="settings-label" for="s-pagespeed">API-Key</label>
          <div class="settings-input-wrap">
            <input type="password" id="s-pagespeed" class="settings-input" placeholder="AIza…" autocomplete="off">
            <button type="button" class="settings-toggle-btn" onclick="toggleSettingsPw('s-pagespeed',this)">Anzeigen</button>
          </div>
        </div>
        <button type="submit" class="btn-save" id="btn-save-pagespeed">Speichern</button>
        <div class="success-msg" id="msg-pagespeed">✓ PageSpeed API-Key gespeichert.</div>
        <div class="err-box" id="err-pagespeed" style="display:none;margin-top:10px;"></div>
      </form>
    </div>
    <div style="height:1px;background:var(--border);margin:24px 0"></div>
    <div class="settings-section">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
        <div class="settings-section-title" style="margin:0">OpenAI (optional)</div>
        <span id="src-badge-openai" class="src-badge"></span>
      </div>
      <div class="settings-section-desc">OpenAI API-Key als Alternative zu Anthropic. Erhältlich unter platform.openai.com.</div>
      <form id="form-openai" onsubmit="saveOpenAI(event)">
        <div class="settings-field">
          <label class="settings-label" for="s-openai-key">API-Key</label>
          <div class="settings-input-wrap">
            <input type="password" id="s-openai-key" class="settings-input" placeholder="sk-…" autocomplete="off">
            <button type="button" class="settings-toggle-btn" onclick="toggleSettingsPw('s-openai-key',this)">Anzeigen</button>
          </div>
        </div>
        <div class="settings-field">
          <label class="settings-label" for="s-openai-model">Modell</label>
          <select id="s-openai-model" class="settings-input" style="font-family:'Inter',sans-serif;cursor:pointer">
            <option value="">— kein OpenAI-Modell (Anthropic verwenden) —</option>
            <option value="gpt-4o">gpt-4o</option>
            <option value="gpt-4o-mini">gpt-4o-mini</option>
            <option value="o1-mini">o1-mini</option>
          </select>
        </div>
        <button type="submit" class="btn-save" id="btn-save-openai">Speichern</button>
        <div class="success-msg" id="msg-openai">✓ OpenAI-Einstellungen gespeichert.</div>
        <div class="err-box" id="err-openai" style="display:none;margin-top:10px;"></div>
      </form>
    </div>
    <div style="height:1px;background:var(--border);margin:24px 0"></div>
    <div class="settings-section">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
        <div class="settings-section-title" style="margin:0">Google Search Console</div>
        <span id="src-badge-gsc" class="src-badge"></span>
      </div>
      <div class="settings-section-desc">Service-Account-JSON und Standard-Property für GSC. Service-Account unter console.cloud.google.com erstellen, dann in GSC als Nutzer hinzufügen.</div>
      <form id="form-gsc-creds" onsubmit="saveGscCreds(event)">
        <div class="settings-field">
          <label class="settings-label" for="s-gsc-url">Standard-Property (Site-URL)</label>
          <input type="url" id="s-gsc-url" class="settings-input" placeholder="https://www.example.com/" autocomplete="off">
        </div>
        <div class="settings-field">
          <label class="settings-label" for="s-gsc-json">Service-Account JSON</label>
          <textarea id="s-gsc-json" class="settings-input" rows="5" style="resize:vertical;font-family:'Geist Mono',monospace;font-size:11px" placeholder='{"type":"service_account","client_email":"...","private_key":"..."}'></textarea>
        </div>
        <button type="submit" class="btn-save" id="btn-save-gsc-creds">Speichern</button>
        <div class="success-msg" id="msg-gsc-creds">✓ GSC-Credentials gespeichert.</div>
        <div class="err-box" id="err-gsc-creds" style="display:none;margin-top:10px;"></div>
      </form>
    </div>
    <div style="height:1px;background:var(--border);margin:24px 0"></div>
    <div class="settings-section">
      <div class="settings-section-title">GSC Domain-Verwaltung</div>
      <div class="settings-section-desc">GSC-Properties verwalten, die bei der Analyse ausgewählt werden können.</div>
      <div id="gsc-domain-list" style="display:flex;flex-direction:column;gap:8px;margin:14px 0 16px"></div>
      <form id="form-gsc-domain" onsubmit="addGscDomain(event)" style="display:flex;gap:8px;align-items:flex-start">
        <input type="url" id="s-gsc-domain-new" class="settings-input" placeholder="https://www.example.com/" style="flex:1" autocomplete="off">
        <button type="submit" class="btn-save" style="white-space:nowrap;margin-top:0;flex-shrink:0">Hinzufügen</button>
      </form>
      <div class="err-box" id="err-gsc-domain" style="display:none;margin-top:10px;"></div>
    </div>
    <div style="height:1px;background:var(--border);margin:24px 0"></div>
    <div class="settings-section">
      <div class="settings-section-title">API-Verbindungen</div>
      <div class="settings-section-desc">Prüft ob alle konfigurierten APIs erreichbar und authentifiziert sind.</div>
      <div>
        <div class="api-test-row">
          <div class="api-test-info"><span class="api-test-dot" id="dot-ai"></span><div><div class="api-test-name">KI-API (Anthropic / OpenAI)</div><div class="api-test-msg" id="testmsg-ai">—</div></div></div>
          <button type="button" class="btn-secondary btn-sm" onclick="testApiConn('ai')">Testen</button>
        </div>
        <div class="api-test-row">
          <div class="api-test-info"><span class="api-test-dot" id="dot-dataforseo"></span><div><div class="api-test-name">DataForSEO</div><div class="api-test-msg" id="testmsg-dataforseo">—</div></div></div>
          <button type="button" class="btn-secondary btn-sm" onclick="testApiConn('dataforseo')">Testen</button>
        </div>
        <div class="api-test-row">
          <div class="api-test-info"><span class="api-test-dot" id="dot-gsc"></span><div><div class="api-test-name">Google Search Console</div><div class="api-test-msg" id="testmsg-gsc">—</div></div></div>
          <button type="button" class="btn-secondary btn-sm" onclick="testApiConn('gsc')">Testen</button>
        </div>
        <div class="api-test-row">
          <div class="api-test-info"><span class="api-test-dot" id="dot-sistrix"></span><div><div class="api-test-name">Sistrix</div><div class="api-test-msg" id="testmsg-sistrix">—</div></div></div>
          <button type="button" class="btn-secondary btn-sm" onclick="testApiConn('sistrix')">Testen</button>
        </div>
        <div class="api-test-row">
          <div class="api-test-info"><span class="api-test-dot" id="dot-pagespeed"></span><div><div class="api-test-name">PageSpeed Insights</div><div class="api-test-msg" id="testmsg-pagespeed">—</div></div></div>
          <button type="button" class="btn-secondary btn-sm" onclick="testApiConn('pagespeed')">Testen</button>
        </div>
      </div>
      <button type="button" class="btn-secondary" style="margin-top:16px" onclick="testAllApis()">Alle gleichzeitig testen</button>
    </div>
    <div style="height:1px;background:var(--border);margin:24px 0"></div>
    <div class="settings-section">
      <div class="settings-section-title">Login-Passwort ändern</div>
      <div class="settings-section-desc">Mindestens 8 Zeichen. Gespeichert als sicherer Hash.</div>
      <form id="form-password" onsubmit="savePassword(event)">
        <div class="settings-field">
          <label class="settings-label" for="s-pw">Neues Passwort</label>
          <input type="password" id="s-pw" class="settings-input" placeholder="Neues Passwort" autocomplete="new-password" minlength="8">
        </div>
        <div class="settings-field">
          <label class="settings-label" for="s-pw2">Passwort bestätigen</label>
          <input type="password" id="s-pw2" class="settings-input" placeholder="Passwort wiederholen" autocomplete="new-password" minlength="8">
        </div>
        <button type="submit" class="btn-save">Passwort ändern</button>
        <div class="success-msg" id="msg-password">✓ Passwort geändert.</div>
        <div class="err-box" id="err-password" style="display:none;margin-top:10px;"></div>
      </form>
    </div>
    <div style="height:1px;background:var(--border);margin:24px 0"></div>
    <div class="settings-section">
      <div class="settings-section-title">Darstellung</div>
      <div class="settings-section-desc">Helles oder dunkles Farbschema für das Interface wählen.</div>
      <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:14px">
        <div>
          <div style="font-size:13px;font-weight:500;color:var(--text)">Dark Mode</div>
          <div style="font-size:12px;color:var(--text3);margin-top:3px">Dunkles Farbschema für bessere Lesbarkeit bei wenig Licht.</div>
        </div>
        <label class="toggle-switch" title="Dark Mode ein-/ausschalten">
          <input type="checkbox" id="setting-dark-mode" onchange="applyTheme(this.checked)">
          <span class="toggle-slider"></span>
        </label>
      </div>
    </div>
    <div style="height:1px;background:var(--border);margin:24px 0"></div>
    <div class="settings-section">
      <div class="settings-section-title">Entwickler-Optionen</div>
      <div class="settings-section-desc">Optionen für Design-Tests und Entwicklung.</div>
      <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:14px">
        <div>
          <div style="font-size:13px;font-weight:500;color:var(--text)">Demo-Button anzeigen</div>
          <div style="font-size:12px;color:var(--text3);margin-top:3px">Simulierte Analyse ohne API-Aufrufe in der Eingabe-Card einblenden.</div>
        </div>
        <label class="toggle-switch" title="Demo-Button ein-/ausblenden">
          <input type="checkbox" id="setting-demo-btn" onchange="saveDemoSetting(this.checked)">
          <span class="toggle-slider"></span>
        </label>
      </div>
    </div>
  </div>
</div>
</div>
</div>
</div>
<script>
const CSRF_TOKEN = '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>';
const AGENT_CUSTOM_PROMPTS = <?= json_encode($_agentPrompts, JSON_UNESCAPED_UNICODE) ?>;
const sleep=ms=>new Promise(r=>setTimeout(r,ms));

// ── Agent Registry ────────────────────────────────────────────────────────
const AGENTS = {
  sqeg: {
    id: 'sqeg',
    name: 'SQEG-Analyst',
    description: 'Bewertet Content-Qualität & E-E-A-T nach 42 Kriterien des Google Search Quality Evaluator Guides (Sept. 2025). Antwortet als strukturiertes JSON-Array.',
    defaultPrompt: `Du bist ein Google Search Quality Evaluator (SQEG September 2025).\nAntworte AUSSCHLIESSLICH als JSON-Array. Kein Text davor oder danach.\nFormat je Objekt: {"id":"1.1","category":"1: Seitenzweck & Seitentyp","criterion":"Name","sqeg_ref":"Sek. X.X","status":"green|amber|red","finding":"Beleg: [Signal aus HTML] | Regel: [WENN-Bedingung] | Bewertung: [Urteil]","improvement":"[konkreter Vorschlag, leer wenn green]","confidence":80}`,
    status: 'idle',
    lastOutput: null,
    getPrompt(){ return AGENT_CUSTOM_PROMPTS.sqeg || this.defaultPrompt; },
    hasCustom(){ return !!AGENT_CUSTOM_PROMPTS.sqeg; }
  }
};

let _activeAgentId = null;

function updateAgentBadge(id, status){
  const dot = document.getElementById('agent-dot-' + id);
  if(dot) dot.className = 'agent-dot ' + status;
  if(AGENTS[id]) AGENTS[id].status = status;
}

function openAgentModal(id){
  const agent = AGENTS[id];
  if(!agent) return;
  _activeAgentId = id;
  document.getElementById('agent-modal-name').textContent = agent.name;
  document.getElementById('agent-modal-desc').textContent = agent.description;
  document.getElementById('agent-modal-prompt').value = agent.getPrompt();
  const dot = document.getElementById('agent-modal-dot');
  if(dot) dot.className = 'agent-dot ' + agent.status;
  const outputEl = document.getElementById('agent-modal-output');
  if(agent.lastOutput){
    const str = JSON.stringify(agent.lastOutput, null, 2);
    outputEl.textContent = str.length > 3000 ? str.substring(0, 3000) + '\n…(gekürzt)' : str;
  } else {
    outputEl.textContent = 'Noch kein Analyse-Lauf.';
  }
  document.getElementById('agent-modal-overlay').style.display = 'flex';
}

function closeAgentModal(){
  document.getElementById('agent-modal-overlay').style.display = 'none';
  _activeAgentId = null;
}

function resetAgentPrompt(){
  const agent = AGENTS[_activeAgentId];
  if(!agent) return;
  document.getElementById('agent-modal-prompt').value = agent.defaultPrompt;
}

async function saveAgentPrompt(){
  const agent = AGENTS[_activeAgentId];
  if(!agent) return;
  const prompt = document.getElementById('agent-modal-prompt').value.trim();
  const fd = new FormData();
  fd.append('action', 'save_agent_prompt');
  fd.append('agent_id', agent.id);
  fd.append('prompt', prompt === agent.defaultPrompt ? '' : prompt); // empty = revert to default
  fd.append('csrf_token', CSRF_TOKEN);
  try{
    const r = await fetch('settings_save.php', {method:'POST', body:fd});
    const d = await r.json();
    if(d.success){
      AGENT_CUSTOM_PROMPTS[agent.id] = (prompt === agent.defaultPrompt) ? '' : prompt;
      // Update custom chip visibility
      const chip = document.getElementById('agent-custom-chip-' + agent.id);
      if(chip) chip.style.display = AGENT_CUSTOM_PROMPTS[agent.id] ? 'inline-flex' : 'none';
      const msg = document.getElementById('agent-modal-save-msg');
      if(msg){ msg.style.display='inline'; setTimeout(()=>msg.style.display='none', 2500); }
    } else {
      alert('Fehler: ' + (d.error || 'Unbekannter Fehler'));
    }
  }catch(e){ alert('Fehler beim Speichern: ' + e.message); }
}

// Close modal with Escape key
document.addEventListener('keydown', e=>{ if(e.key==='Escape' && _activeAgentId) closeAgentModal(); });
// Init: show custom-prompt chip if a custom prompt is already stored
Object.keys(AGENTS).forEach(id=>{
  const chip = document.getElementById('agent-custom-chip-' + id);
  if(chip && AGENT_CUSTOM_PROMPTS[id]) chip.style.display = 'inline-flex';
});

// === VIEW TITLES ===
const VIEW_META={
  overview:{title:'Übersicht',sub:'Landingpage Analyse Tool'},
  sqeg:{title:'SQEG',sub:'Google Search Quality Evaluator Guidelines'},
  technical:{title:'Technical SEO',sub:'Source Code Analyse · Indexierbarkeit · On-Page'},
  performance:{title:'Performance',sub:'Rankings · Sichtbarkeit · Quick Wins'},
  geo:{title:'GEO / AEO',sub:'KI-Sichtbarkeit in AI-Suchmaschinen'},
  keywords:{title:'Keyword Fit',sub:'Intent-Analyse · Targeting · Potenzial'},
  localpv:{title:'Local PV Generator',sub:'SEO- & CRO-Bausteine für lokale Photovoltaik-Landingpages'},
  'content-finder':{title:'Content Finder',sub:'Vollständige Seitenanalyse nach definierten Begriffen · JS-Rendering · Bild-OCR'},
  settings:{title:'Einstellungen',sub:'API-Keys · Modell · Passwort'},
};

// === ROUTING ===
function showView(name){
  document.querySelectorAll('.view-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('[data-view]').forEach(b=>b.classList.remove('active'));
  const p=document.getElementById('view-'+name);
  if(p)p.classList.add('active');
  const b=document.querySelector('[data-view="'+name+'"]');
  if(b)b.classList.add('active');
  const meta=VIEW_META[name]||{title:name,sub:''};
  document.getElementById('view-title').textContent=meta.title;
  document.getElementById('view-subtitle').textContent=meta.sub;
  // Hide URL-input header for standalone tool views
  const hf=document.getElementById('header-form');
  if(name==='localpv'||name==='settings'||name==='content-finder'){hf.style.display='none';}
  else{hf.style.display='';}
  if(name==='overview'){
    // Progress-Section zeigen wenn Analyse läuft ODER Log-Inhalt vorhanden
    const ps=document.getElementById('progress-section');
    if(ps){
      const hasLog=document.getElementById('log-box')?.innerHTML?.trim();
      ps.style.display=(ps.dataset.active==='1'||hasLog)?'block':'none';
    }
  }
  if(name==='settings'){loadCredentialStatus();loadGscDomains();}
}
// Legacy alias
function showTool(n){showView(n==='sqeg'?'overview':n);}

// === MODE TOGGLE ===
let currentMode='url';
function setMode(mode){
  currentMode=mode;
  document.getElementById('mode-url').classList.toggle('active',mode==='url');
  document.getElementById('mode-html').classList.toggle('active',mode==='html');
  document.getElementById('html-textarea-wrap').style.display=mode==='html'?'block':'none';
}
function toggleDetailTable(){
  const wrap=document.getElementById('detail-table-wrap');
  const icon=document.getElementById('detail-toggle-icon');
  const btn=document.getElementById('detail-toggle-btn');
  const open=wrap.style.display==='none';
  wrap.style.display=open?'block':'none';
  if(icon)icon.style.transform=open?'rotate(180deg)':'';
  if(btn)btn.setAttribute('aria-expanded',open?'true':'false');
}
function toggleLog(){document.getElementById('log-wrap').classList.toggle('collapsed');}
function toggleContext(){document.getElementById('context-fields').classList.toggle('visible')}

// === CRITERIA (SQEG Sept 2025 — 42 Kriterien, 8 Cluster) ===
const CRITERIA=[
  // Cluster 1: Seitenzweck & Seitentyp
  {id:'1.1',cat:'1: Seitenzweck & Seitentyp',name:'Erkennbarer Seitenzweck',             ref:'Sek. 2.2'},
  {id:'1.2',cat:'1: Seitenzweck & Seitentyp',name:'Seitentyp-Klassifikation',             ref:'Sek. 3.1'},
  {id:'1.3',cat:'1: Seitenzweck & Seitentyp',name:'YMYL-Einordnung',                      ref:'Sek. 2.3'},
  {id:'1.4',cat:'1: Seitenzweck & Seitentyp',name:'Hauptinhalt klar abgegrenzt',           ref:'Sek. 2.4.1'},
  // Cluster 2: Inhalt & Tiefe
  {id:'2.1',cat:'2: Inhalt & Tiefe',          name:'Menschlicher Aufwand erkennbar',       ref:'Sek. 3.2'},
  {id:'2.2',cat:'2: Inhalt & Tiefe',          name:'Originalität',                         ref:'Sek. 3.2'},
  {id:'2.3',cat:'2: Inhalt & Tiefe',          name:'Handwerkliche Qualität',               ref:'Sek. 3.2'},
  {id:'2.4',cat:'2: Inhalt & Tiefe',          name:'Faktische Korrektheit',                ref:'Sek. 3.2'},
  {id:'2.5',cat:'2: Inhalt & Tiefe',          name:'Themen-Tiefe & Vollständigkeit',       ref:'Sek. 4.1'},
  {id:'2.6',cat:'2: Inhalt & Tiefe',          name:'Kein Füllmaterial',                    ref:'Sek. 5.2.2'},
  {id:'2.7',cat:'2: Inhalt & Tiefe',          name:'Kein KI/Massen-Content-Missbrauch',    ref:'Sek. 4.6.5'},
  {id:'2.8',cat:'2: Inhalt & Tiefe',          name:'Aktualität des Inhalts',               ref:'Sek. 18.0'},
  // Cluster 3: E-E-A-T
  {id:'3.1',cat:'3: E-E-A-T',                 name:'Eigene Erfahrung (Experience)',        ref:'Sek. 3.4'},
  {id:'3.2',cat:'3: E-E-A-T',                 name:'Fachkompetenz (Expertise)',            ref:'Sek. 3.4'},
  {id:'3.3',cat:'3: E-E-A-T',                 name:'Autorität im Thema',                  ref:'Sek. 3.4'},
  {id:'3.4',cat:'3: E-E-A-T',                 name:'Vertrauenswürdigkeit (Trust) ★',      ref:'Sek. 3.4'},
  {id:'3.5',cat:'3: E-E-A-T',                 name:'YMYL: Richtiges E-E-A-T-Profil',      ref:'Sek. 3.4.1'},
  // Cluster 4: Reputation & Transparenz
  {id:'4.1',cat:'4: Reputation & Transparenz',name:'Website-Reputation',                  ref:'Sek. 3.3.1'},
  {id:'4.2',cat:'4: Reputation & Transparenz',name:'Autor/Creator erkennbar',             ref:'Sek. 3.3.4'},
  {id:'4.3',cat:'4: Reputation & Transparenz',name:'Impressum & rechtliche Angaben',      ref:'Sek. 2.5.3'},
  {id:'4.4',cat:'4: Reputation & Transparenz',name:'Kontaktmöglichkeiten',                ref:'Sek. 2.5.3'},
  {id:'4.5',cat:'4: Reputation & Transparenz',name:'Wer steckt hinter der Seite?',        ref:'Sek. 2.5.2'},
  {id:'4.6',cat:'4: Reputation & Transparenz',name:'Interessenkonflikt offengelegt',       ref:'Sek. 3.4'},
  // Cluster 5: Schaden & Täuschung
  {id:'5.1',cat:'5: Schaden & Täuschung',     name:'Kein täuschendes Design ★',           ref:'Sek. 4.5.3'},
  {id:'5.2',cat:'5: Schaden & Täuschung',     name:'Hauptinhalt zugänglich',              ref:'Sek. 4.5.4'},
  {id:'5.3',cat:'5: Schaden & Täuschung',     name:'Kein Scam/Spam-Verdacht ★',          ref:'Sek. 4.5.5'},
  {id:'5.4',cat:'5: Schaden & Täuschung',     name:'Keine schädlichen Inhalte ★',        ref:'Sek. 4.2'},
  {id:'5.5',cat:'5: Schaden & Täuschung',     name:'Keine gefährlichen Fehlinformationen ★',ref:'Sek. 4.4'},
  {id:'5.6',cat:'5: Schaden & Täuschung',     name:'Keine Seiten-Kompromittierung',       ref:'Sek. 4.6.2'},
  {id:'5.7',cat:'5: Schaden & Täuschung',     name:'Keine Domain-Zweckentfremdung',       ref:'Sek. 4.6.3'},
  // Cluster 6: Technik & UX
  {id:'6.1',cat:'6: Technik & UX',            name:'Core Web Vitals (LCP/CLS/TBT)',       ref:'Sek. 7.0'},
  {id:'6.2',cat:'6: Technik & UX',            name:'Mobile-Tauglichkeit',                 ref:'Sek. 7.0'},
  {id:'6.3',cat:'6: Technik & UX',            name:'Seitentitel & Meta-Description',      ref:'Sek. 3.1'},
  {id:'6.4',cat:'6: Technik & UX',            name:'Strukturierte Daten (Schema.org)',    ref:'Sek. 7.0'},
  {id:'6.5',cat:'6: Technik & UX',            name:'HTTPS & Verbindungssicherheit',       ref:'Sek. 4.5.5'},
  // Cluster 7: Werbung & SC
  {id:'7.1',cat:'7: Werbung & SC',            name:'Ergänzender Inhalt sinnvoll',         ref:'Sek. 2.4.2'},
  {id:'7.2',cat:'7: Werbung & SC',            name:'Werbung klar gekennzeichnet',         ref:'Sek. 2.4.3'},
  {id:'7.3',cat:'7: Werbung & SC',            name:'Werbung nicht übermäßig aufdringlich',ref:'Sek. 2.4.4'},
  // Cluster 8: Needs Met
  {id:'8.1',cat:'8: Needs Met',               name:'Suchabsicht getroffen ★',             ref:'Sek. 13.0'},
  {id:'8.2',cat:'8: Needs Met',               name:'Antwort vollständig',                 ref:'Sek. 13.0'},
  {id:'8.3',cat:'8: Needs Met',               name:'Aktualität der Antwort',              ref:'Sek. 18.0'},
  {id:'8.4',cat:'8: Needs Met',               name:'Verständlichkeit für die Zielgruppe', ref:'Sek. 13.0'},
];
// Gewicht 4 (Kritisch): 3.4, 5.1, 5.3, 5.4, 5.5, 8.1
// Gewicht 3 (Hoch):     1.1, 1.3, 2.4, 3.1–3.3, 3.5, 4.1–4.2, 4.5–4.6, 5.2, 5.6–5.7, 8.2
// Gewicht 2.5:          2.1, 2.2
// Gewicht 2 (Standard): 1.2, 1.4, 2.3, 4.3, 4.4, 6.3, 6.5, 7.1–7.3, 8.3
// Gewicht 1.5 (Ergänz.):2.5–2.8, 6.1–6.2, 6.4, 8.4
const WEIGHTS={
  '3.4':4,'5.1':4,'5.3':4,'5.4':4,'5.5':4,'8.1':4,
  '1.1':3,'1.3':3,'2.4':3,'3.1':3,'3.2':3,'3.3':3,'3.5':3,
  '4.1':3,'4.2':3,'4.5':3,'4.6':3,'5.2':3,'5.6':3,'5.7':3,'8.2':3,
  '2.1':2.5,'2.2':2.5,
  '1.2':2,'1.4':2,'2.3':2,'4.3':2,'4.4':2,'6.3':2,'6.5':2,'7.1':2,'7.2':2,'7.3':2,'8.3':2,
};
function getWeight(id){return WEIGHTS[id]??1.5}
const YMYL_ESCALATION={'2.4':1,'3.2':1,'3.5':1,'4.3':1,'4.4':1};
function getEffectiveWeight(id){
  const base=getWeight(id);
  const esc=YMYL_ESCALATION[id];
  if(!esc)return base;
  if(ymylResult==='clear_ymyl')return base+esc;
  if(ymylResult==='mixed_ymyl')return base+esc*0.5;
  return base;
}
function statusScore(s){return s==='green'?100:s==='amber'?50:0}
const MINI_CALLS=[
  ['1.1','1.2'],['1.3','1.4'],
  ['2.1','2.2'],['2.3','2.4'],['2.5','2.6'],['2.7','2.8'],
  ['3.1','3.2'],['3.3','3.4'],['3.5','4.1'],
  ['4.2','4.3'],['4.4','4.5'],['4.6','5.1'],
  ['5.2','5.3'],['5.4','5.5'],['5.6','5.7'],
  ['6.1','6.2'],['6.3','6.4'],['6.5','7.1'],
  ['7.2','7.3'],
  ['8.1','8.2'],['8.3','8.4'],
];

// === STATE ===
let analysisResults=[],pqResults=[],e8Result=null,ymylResult=null,currentUrl='',currentHtml='';
let isDemoMode=false;
let gscData=null,serpData=null,backlinkData=null,psiData=null,psiDesktopData=null,sistrixData=null,geoData=null,kwData=null,ucrData=null,sitemapData=null;
let analysisStartTime=0,timerInterval=null,lastPct=0;

// === LOG / PROGRESS ===
function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')}
function log(msg,type='info'){
  const box=document.getElementById('log-box');
  const cls=type==='ok'?'log-ok':type==='err'?'log-err':'log-info';
  box.innerHTML+=`<div class="${cls}">[${new Date().toLocaleTimeString()}] ${escHtml(msg)}</div>`;
  box.scrollTop=box.scrollHeight;
}
function setProgress(pct,label='',status=''){
  lastPct=pct;
  document.getElementById('progress-bar').style.width=pct+'%';
  document.getElementById('progress-pct').textContent=Math.round(pct)+'%';
  if(label)document.getElementById('progress-label').textContent=label;
  if(status)document.getElementById('status-msg').textContent=status;
}

// === TIMER ===
function formatTime(s){const m=Math.floor(s/60),sec=Math.round(s%60);return`${m}:${sec.toString().padStart(2,'0')}`}
function updateTimer(){
  const el=document.getElementById('progress-timer');
  if(!el)return;
  el.textContent=formatTime((Date.now()-analysisStartTime)/1000);
}

// === DEMO MODE ===
const DEMO_RESULTS=[
  {id:'1.1',status:'green', finding:'Beleg: Seitenüberschrift „Strom Tarife Vergleich" eindeutig. | Regel: Seitenzweck muss für Nutzer sofort erkennbar sein. | Bewertung: Zweck klar kommuniziert.',improvement:''},
  {id:'1.2',status:'green', finding:'Beleg: Preisvergleichsseite mit CTA „Jetzt wechseln". | Regel: Seitentyp klar klassifizierbar. | Bewertung: Transaktionale Seite korrekt eingeordnet.',improvement:''},
  {id:'1.3',status:'green', finding:'Beleg: Energievergleich ohne Gesundheits-/Finanzberatung. | Regel: YMYL-Einordnung nach Risikolevel. | Bewertung: Kein erhöhter YMYL-Status.',improvement:''},
  {id:'1.4',status:'amber', finding:'Beleg: Sidebar-Werbung grenzt nahtlos an Hauptinhalt. | Regel: MC, SC und Werbung müssen klar getrennt sein. | Bewertung: Abgrenzung verbesserungswürdig.',improvement:'Klare visuelle Trennlinie zwischen Vergleichstabelle und Sidebar-Widgets einziehen.'},
  {id:'2.1',status:'red',   finding:'Beleg: Generische Beschreibungen ohne persönliche Einblicke oder Testberichte. | Regel: Menschlicher Aufwand (originäre Leistung) muss erkennbar sein. | Bewertung: Kein nachweisbarer Mehraufwand.',improvement:'Ergänze redaktionelle Kommentare, Testberichte oder persönliche Erfahrungen mit Tarifen.'},
  {id:'2.2',status:'red',   finding:'Beleg: Texte ähneln generischen Tarifvergleichs-Templates ohne erkennbare Eigenleistung. | Regel: Originalität erfordert einzigartigen Mehrwert. | Bewertung: Keine Originalität feststellbar.',improvement:'Füge exklusive Daten, eigene Berechnungen oder redaktionelle Einschätzungen hinzu.'},
  {id:'2.3',status:'amber', finding:'Beleg: Rechtschreibfehler auf 3 Unterseiten, Tabelle unvollständig. | Regel: Handwerkliche Qualität erfordert fehlerfreie Darstellung. | Bewertung: Einige Mängel festgestellt.',improvement:'Korrekturlesen und Tabellenvollständigkeit sicherstellen.'},
  {id:'2.4',status:'red',   finding:'Beleg: Tarif „Öko-Plus" zeigt falschen Grundpreis (Stand 01/2024, inzwischen erhöht). | Regel: Faktische Korrektheit besonders bei Preisangaben kritisch. | Bewertung: Veraltete Preisdaten gefunden.',improvement:'Automatische Preisaktualisierung implementieren oder manuelle Prüfung wöchentlich durchführen.'},
  {id:'2.5',status:'amber', finding:'Beleg: Themen Netzentgelte und Preisgarantien fehlen. | Regel: Vollständigkeit erfordert alle entscheidungsrelevanten Aspekte. | Bewertung: Wichtige Themenaspekte fehlen.',improvement:'Ergänze Abschnitte zu Netzentgelten, Preisgarantieoptionen und Anbieterbewertungen.'},
  {id:'2.6',status:'amber', finding:'Beleg: Einleitungsabsatz wiederholt Tarifnamen ohne Mehrwert. | Regel: Kein Füllmaterial oder unnötige Wiederholungen. | Bewertung: Leichtes Filler-Content-Problem.',improvement:'Kürze Einleitungen und ersetze Wiederholungen durch konkrete Nutzwert-Aussagen.'},
  {id:'2.7',status:'red',   finding:'Beleg: Produktbeschreibungen folgen einheitlichem Template-Muster, keine stilistischen Variationen. | Regel: KI/Massen-Content darf keinen Spam-Eindruck erzeugen. | Bewertung: Template-Content-Verdacht.',improvement:'Überarbeite Produktbeschreibungen mit individuellen redaktionellen Texten pro Tarif.'},
  {id:'2.8',status:'red',   finding:'Beleg: Seite zeigt „Zuletzt aktualisiert: März 2023". | Regel: Aktualität ist besonders bei Tarifdaten entscheidend. | Bewertung: Erheblich veralteter Inhalt.',improvement:'Regelmäßige Aktualisierungszyklen einrichten, Datum prominent anzeigen.'},
  {id:'3.1',status:'amber', finding:'Beleg: Keine Testberichte oder Erfahrungsberichte von Redakteuren. | Regel: Experience erfordert nachweisbare eigene Erfahrungen mit dem Thema. | Bewertung: Erfahrungsnachweis fehlt.',improvement:'Ergänze Redakteurs-Profile mit Energiemarkt-Erfahrung und persönlichen Einschätzungen.'},
  {id:'3.2',status:'red',   finding:'Beleg: Keine fachlichen Referenzen, keine Quellenangaben zu Tarifdaten. | Regel: Expertise erfordert erkennbare Fachkenntnisse. | Bewertung: Fachkompetenz nicht nachgewiesen.',improvement:'Ergänze Expertenbios, Quellenangaben zu Bundesnetzagentur-Daten und Branchenreferenzen.'},
  {id:'3.3',status:'amber', finding:'Beleg: Domain existiert seit 2019, keine Branchenawards oder Mediennennung. | Regel: Autorität erfordert externe Anerkennung oder Bekanntheit. | Bewertung: Begrenzte Autorität.',improvement:'Baue externe Verlinkungen und Medienerwähnungen auf, erscheine auf Vergleichsportalen.'},
  {id:'3.4',status:'green', finding:'Beleg: SSL-Zertifikat, DSGVO-konformes Cookie-Banner, keine Schadsoftware-Anzeichen. | Regel: Trust als wichtigster E-E-A-T-Faktor. | Bewertung: Grundvertrauen gegeben.',improvement:''},
  {id:'3.5',status:'red',   finding:'Beleg: Transaktionsseite für Energie ohne erkennbare Redaktionskompetenz — bei Kauf-Entscheidungen gilt E-E-A-T-Pflicht. | Regel: Erhöhtes E-E-A-T-Anforderungsprofil für transaktionale Seiten. | Bewertung: YMYL-Anforderungen nicht erfüllt.',improvement:'Transparentes Impressum mit Redaktionsleitung, Fachbeirat oder Partnerschaft mit Verbraucherorganisation aufbauen.'},
  {id:'4.1',status:'amber', finding:'Beleg: Keine Trustpilot-/Google-Bewertungen sichtbar, keine Medienerwähnungen. | Regel: Website-Reputation messbar durch externe Quellen. | Bewertung: Reputation nicht sichtbar.',improvement:'Integriere Kundenbewertungs-Widget und dokumentiere Medienerwähnungen.'},
  {id:'4.2',status:'red',   finding:'Beleg: Keine Autor-Bylines, keine Redakteursprofile verlinkt. | Regel: Inhaltsverantwortung muss zuordenbar sein. | Bewertung: Kein Autor identifizierbar.',improvement:'Füge Autor-Bylines mit verlinkten Redakteursprofilen zu allen Artikeln hinzu.'},
  {id:'4.3',status:'green', finding:'Beleg: Vollständiges Impressum mit Handelsregistereintrag und Verantwortlichem i.S.v. §5 TMG. | Regel: Rechtliche Angaben vollständig und korrekt. | Bewertung: Impressum korrekt.',improvement:''},
  {id:'4.4',status:'red',   finding:'Beleg: Nur Kontaktformular ohne E-Mail-Adresse oder Telefonnummer. | Regel: Mindestens eine direkte Kontaktmöglichkeit erforderlich. | Bewertung: Kontaktmöglichkeiten unzureichend.',improvement:'Ergänze direkte E-Mail-Adresse oder Telefonnummer im Footer und auf der Kontaktseite.'},
  {id:'4.5',status:'amber', finding:'Beleg: „Über uns"-Seite beschreibt Unternehmen sehr allgemein. | Regel: Transparenz über Betreiber und deren Motivation. | Bewertung: Transparenz ausbaubar.',improvement:'Ergänze Unternehmensgeschichte, Team-Fotos und Angaben zur redaktionellen Unabhängigkeit.'},
  {id:'4.6',status:'green', finding:'Beleg: Keine versteckten Werbekooperationen erkennbar, Affiliate-Hinweis im Footer vorhanden. | Regel: Interessenkonflikte müssen offen kommuniziert werden. | Bewertung: Ausreichend transparent.',improvement:''},
  {id:'5.1',status:'green', finding:'Beleg: Keine Fake-Buttons, keine irreführenden UI-Patterns. | Regel: Kein täuschendes Design (Dark Patterns). | Bewertung: Design ist fair.',improvement:''},
  {id:'5.2',status:'green', finding:'Beleg: Vergleichstabelle sofort sichtbar, kein Interstitial-Blocking. | Regel: Hauptinhalt ohne Barrieren zugänglich. | Bewertung: Inhalt zugänglich.',improvement:''},
  {id:'5.3',status:'green', finding:'Beleg: Domain sauber, keine Spam-Signale, keine übertriebenen Versprechen. | Regel: Keine Scam/Spam-Merkmale. | Bewertung: Kein Scam.',improvement:''},
  {id:'5.4',status:'green', finding:'Beleg: Kein anstößiger Inhalt, keine Schadsoftware-Indikatoren. | Regel: Inhalt darf nicht schaden. | Bewertung: Keine schädlichen Inhalte.',improvement:''},
  {id:'5.5',status:'green', finding:'Beleg: Tarifinformationen plausibel, keine nachweislich falschen Behauptungen. | Regel: Keine gefährlichen Fehlinformationen. | Bewertung: Keine Fehlinformationen.',improvement:''},
  {id:'5.6',status:'green', finding:'Beleg: Keine Anzeichen für Hacking, Malware oder unbefugte Inhalte. | Regel: Seite darf nicht kompromittiert sein. | Bewertung: Seite sicher.',improvement:''},
  {id:'5.7',status:'green', finding:'Beleg: Domain konsistent für Energievergleich genutzt, kein Zweckwechsel erkennbar. | Regel: Domain muss für angekündigten Zweck genutzt werden. | Bewertung: Konsistente Nutzung.',improvement:''},
  {id:'6.1',status:'red',   finding:'Beleg: LCP 4.8s (Richtwert: <2.5s), CLS 0.23 (Richtwert: <0.1), TBT 580ms (Richtwert: <200ms). | Regel: Core Web Vitals sind Ranking-Faktor. | Bewertung: Alle drei Metriken im roten Bereich.',improvement:'Bilder in WebP konvertieren, Lazy Loading aktivieren, JavaScript-Blocker identifizieren und defer-Loading einrichten.'},
  {id:'6.2',status:'amber', finding:'Beleg: Responsive Design vorhanden, Tabellen auf Mobilgeräten jedoch horizontal scrollbar ohne Hinweis. | Regel: Vollständige Mobile-Tauglichkeit erforderlich. | Bewertung: Mobile-Erfahrung eingeschränkt.',improvement:'Vergleichstabelle auf Mobile in Karten-Layout umwandeln oder Scroll-Hinweis ergänzen.'},
  {id:'6.3',status:'amber', finding:'Beleg: Title-Tag korrekt, Meta-Description fehlt auf 40% der Seiten (automatisch generiert). | Regel: Seitentitel und Meta-Description sollten optimiert sein. | Bewertung: Meta-Beschreibungen unvollständig.',improvement:'Individuelle Meta-Descriptions für alle Tarifvergleichsseiten einpflegen.'},
  {id:'6.4',status:'red',   finding:'Beleg: Kein Schema.org-Markup gefunden (weder Product, Offer noch FAQPage). | Regel: Strukturierte Daten verbessern SERP-Sichtbarkeit. | Bewertung: Keine strukturierten Daten.',improvement:'Implementiere FAQPage, Product und Offer-Schema auf allen Vergleichsseiten.'},
  {id:'6.5',status:'green', finding:"Beleg: HTTPS aktiv, gültiges SSL-Zertifikat (Let's Encrypt, gültig bis 09/2026). | Regel: HTTPS als Mindeststandard. | Bewertung: Verbindung sicher.",improvement:''},
  {id:'7.1',status:'amber', finding:'Beleg: Sidebar zeigt themenfremde Widgets (Reise-Angebote). | Regel: SC sollte Nutzer beim Hauptziel unterstützen. | Bewertung: SC teilweise irrelevant.',improvement:'Ersetze themenfremde Sidebar-Inhalte durch energierelevante Links (Zählerstand-Rechner, FAQ).'},
  {id:'7.2',status:'green', finding:'Beleg: Keine Werbeanzeigen auf Seite erkennbar (keine AdSense-Tags). | Regel: Vorhandene Werbung muss gekennzeichnet sein. | Bewertung: Keine Werbung vorhanden.',improvement:''},
  {id:'7.3',status:'green', finding:'Beleg: Kein Pop-up, kein Interstitial, keine Push-Notification-Anfrage. | Regel: Werbung darf Nutzerfluss nicht unterbrechen. | Bewertung: Kein aufdringliches Element.',improvement:''},
  {id:'8.1',status:'amber', finding:'Beleg: Keyword „Strom Tarife Vergleich" → Transaktionale Absicht; Seite informiert, führt aber nicht klar zur Entscheidung. | Regel: Suchabsicht (Intent) muss vollständig getroffen werden. | Bewertung: Absicht teilweise erfüllt.',improvement:'Füge klare Handlungsaufforderungen und Entscheidungshilfen (z.B. Tarifrechner) hinzu.'},
  {id:'8.2',status:'amber', finding:'Beleg: Ökostrom-Tarife erwähnt aber nicht detailliert verglichen; Gas fehlt vollständig. | Regel: Vollständige Antwort auf die Suchanfrage. | Bewertung: Antwort unvollständig.',improvement:'Erweitere auf alle relevanten Energiearten und filtere nach relevanten Nutzerbedürfnissen.'},
  {id:'8.3',status:'amber', finding:'Beleg: Letzte Aktualisierung März 2023, aktuelle Preisänderungen nicht reflektiert. | Regel: Aktualität der Antwort bei Preisvergleichen kritisch. | Bewertung: Antwort veraltet.',improvement:'Regelmäßige Datenpflege-Routinen einführen, „Zuletzt aktualisiert"-Datum prominent anzeigen.'},
  {id:'8.4',status:'amber', finding:'Beleg: Fachbegriffe (kWh, Grundpreis, Arbeitspreis) werden nicht erklärt. | Regel: Verständlichkeit für die anvisierte Zielgruppe. | Bewertung: Für Laien schwer verständlich.',improvement:'Ergänze Glossar oder Tooltips für Fachbegriffe direkt in der Vergleichstabelle.'},
];

async function startDemo(){
  isDemoMode=true;
  document.getElementById('exec-summary').style.display='none';
  document.getElementById('exec-summary-content').style.display='none';
  document.getElementById('exec-summary-loading').style.display='flex';
  document.getElementById('btn-demo').disabled=true;
  document.getElementById('header-form').classList.add('input-dimmed');
  document.getElementById('progress-section').style.display='block';
  document.getElementById('progress-bar-wrap').style.display='block';
  document.getElementById('loader-wrap').style.display='block';
  document.getElementById('status-msg').style.display='block';
  document.getElementById('progress-pct').style.display='';
  document.getElementById('results-section').style.display='none';
  document.getElementById('skeleton-wrap').style.display='block';
  document.getElementById('log-wrap').classList.remove('collapsed');
  document.getElementById('log-box').innerHTML='';
  analysisResults=[];pqResults=[];e8Result=null;ymylResult=null;
  gscData=null;serpData=null;backlinkData=null;psiData=null;psiDesktopData=null;sistrixData=null;geoData=null;kwData=null;ucrData=null;sitemapData=null;
  analysisStartTime=Date.now();lastPct=0;
  if(timerInterval)clearInterval(timerInterval);
  timerInterval=setInterval(updateTimer,1000);
  document.getElementById('progress-timer').textContent='';
  ['sqeg-results','perf-results','geo-results','kw-results','ux-results','technical-results'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.display='none';});
  ['sqeg-empty','perf-empty','geo-empty','kw-empty','ux-empty','technical-empty'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.display='block';});
  const uxBC=document.getElementById('ux-body-card');if(uxBC)uxBC.style.display='none';
  ymylResult='none';
  currentUrl='https://www.beispiel-energie.de/strom/tarife';
  // Demo-HTML für Technical SEO Analyse
  currentHtml=`<!DOCTYPE html><html lang="de"><head><title>Stromtarife vergleichen &amp; wechseln – Beispiel Energie</title><meta name="description" content="Jetzt Stromtarife vergleichen und einfach online wechseln. Günstige Tarife für Privat- und Geschäftskunden. Bis zu 400 € sparen – schnell und unkompliziert."><link rel="canonical" href="https://www.beispiel-energie.de/strom/tarife"><meta property="og:title" content="Stromtarife vergleichen – Beispiel Energie"><meta property="og:description" content="Günstige Stromtarife jetzt vergleichen und wechseln."><meta charset="UTF-8"></head><body><h1>Stromtarife vergleichen</h1><img src="/img/hero.jpg" alt="Strom sparen mit Beispiel Energie"><img src="/img/siegel.png" alt="TÜV-geprüft"><p>Finden Sie den günstigsten Stromtarif für Ihren Haushalt.</p></body></html>`;
  log('⚡ Demo-Modus — keine echten API-Aufrufe');
  await sleep(350);
  log('HTML abgerufen (48.3 KB)','ok');
  setProgress(10);
  await sleep(250);
  log('YMYL-Klassifikation: none','ok');
  setProgress(13);
  await sleep(200);
  log('Starte 21 SQEG-Mini-Calls (Demo)…');

  for(let i=0;i<MINI_CALLS.length;i++){
    await sleep(60);
    const names=MINI_CALLS[i].map(id=>CRITERIA.find(c=>c.id===id)?.name||id).join(' · ');
    log('✓ '+names,'ok');
    setProgress(13+((i+1)/21)*77);
  }

  // Enrich demo results with CRITERIA metadata
  analysisResults=DEMO_RESULTS.map(r=>{
    const c=CRITERIA.find(x=>x.id===r.id)||{};
    return{...r,category:c.cat||'',criterion:c.name||r.id,sqeg_ref:c.ref||''};
  });

  // Demo-Daten für Performance + GEO
  gscData={keywords:[
    {query:'strom tarife vergleich',clicks:1240,impressions:18600,ctr:6.7,position:4.2},
    {query:'günstiger stromtarif',clicks:870,impressions:12400,ctr:7.0,position:5.1},
    {query:'strom wechseln online',clicks:640,impressions:9800,ctr:6.5,position:6.3},
    {query:'beispiel energie strom',clicks:530,impressions:4200,ctr:12.6,position:2.1},
    {query:'stromtarif haushalt',clicks:410,impressions:7600,ctr:5.4,position:8.7},
    {query:'stromanbieter wechsel',clicks:290,impressions:5100,ctr:5.7,position:11.2},
    {query:'energie sparen tarif',clicks:210,impressions:4400,ctr:4.8,position:14.3},
    {query:'ökostrom tarife',clicks:180,impressions:3900,ctr:4.6,position:12.8},
  ]};
  sistrixData={success:true,visibility:0.847,kw_count:3241,keywords:[
    {keyword:'strom tarife vergleich',position:4,volume:22000},
    {keyword:'günstiger stromtarif',position:5,volume:18000},
    {keyword:'strom wechseln online',position:6,volume:14500},
    {keyword:'stromtarif haushalt',position:9,volume:9800},
    {keyword:'stromanbieter wechsel',position:11,volume:7200},
  ],opportunities:[
    {keyword:'strom tarif rechner',position:8,gain:82,competition:0.41},
    {keyword:'strompreise aktuell 2025',position:12,gain:74,competition:0.38},
    {keyword:'günstig strom bestellen',position:15,gain:68,competition:0.29},
    {keyword:'ökostrom wechseln',position:17,gain:61,competition:0.35},
  ],competitors:[
    {domain:'verivox.de',competition:0.78},
    {domain:'check24.de',competition:0.71},
    {domain:'e-on.de',competition:0.54},
    {domain:'vattenfall.de',competition:0.48},
  ]};
  geoData={success:true,prompts:[
    {prompt:'Welcher Stromanbieter ist aktuell der günstigste?',model:'ChatGPT'},
    {prompt:'Wie kann ich meinen Stromanbieter wechseln?',model:'Perplexity'},
    {prompt:'Was kostet Strom pro kWh im Vergleich?',model:'ChatGPT'},
    {prompt:'Empfehlt ihr einen günstigen Ökostrom-Tarif?',model:'Gemini'},
    {prompt:'Stromtarif für 3-Personen-Haushalt empfehlen',model:'Perplexity'},
  ],sources:[
    {url:'https://www.beispiel-energie.de/strom/tarife'},
    {url:'https://www.beispiel-energie.de/strom/oekostrom'},
    {url:'https://www.beispiel-energie.de/ratgeber/strom-wechseln'},
  ]};
  // Demo Keyword-Fit-Daten (Sistrix search intent)
  kwData={success:true,results:{
    'strom tarife vergleich':{keyword:'strom tarife vergleich',commercial:0.71,transactional:0.18,informational:0.08,navigational:0.03},
    'günstiger stromtarif':{keyword:'günstiger stromtarif',commercial:0.65,transactional:0.24,informational:0.09,navigational:0.02},
    'strom wechseln online':{keyword:'strom wechseln online',commercial:0.12,transactional:0.74,informational:0.11,navigational:0.03},
    'beispiel energie strom':{keyword:'beispiel energie strom',commercial:0.08,transactional:0.07,informational:0.11,navigational:0.74},
    'stromtarif haushalt':{keyword:'stromtarif haushalt',commercial:0.59,transactional:0.21,informational:0.17,navigational:0.03},
    'stromanbieter wechsel':{keyword:'stromanbieter wechsel',commercial:0.14,transactional:0.68,informational:0.15,navigational:0.03},
  }};
  log('GSC: 8 Keywords geladen (Demo)','ok');
  log('Sistrix: Sichtbarkeit 0.847 · 3241 Keywords (Demo)','ok');
  log('GEO: 5 AI-Prompts · 3 Quellen (Demo)','ok');
  log('Keyword-Fit: Intent für 6 Keywords analysiert (Demo)','ok');
  // Demo UX/CRO-Daten (kein echter Screenshot) — v2: Device-Split Mobile + Desktop
  ucrData={
    mobile:{success:true,device:'mobile',score:60,
      comment:'Die mobile Ansicht der Seite ist grundsätzlich strukturiert, aber der wichtigste Content liegt deutlich unterhalb des initialen Viewports. Der CTA ist vorhanden, wirkt auf kleinen Screens jedoch unterdimensioniert. Trust-Signale sind kaum erkennbar.',
      checks:[
        {id:'U1',name:'Above-the-Fold & Nutzenversprechen',status:'amber',finding:'H1 "Strom Tarife Vergleich" vorhanden · Content unterhalb des Folds (5 Wörter above fold)',detail:'Auf 375px sind nur wenige Inhalte ohne Scrollen sichtbar.',fix:'Hero-Bereich komprimieren, Kernnutzen (z.B. "Spare bis 400 €") direkt above fold.'},
        {id:'U2',name:'Ablenkungsfreiheit & Benutzerführung',status:'amber',finding:'Hauptnavigation mit 6 Links — auf Mobile ablenkend.',detail:'Jeder Nav-Link ist eine potenzielle Ausstiegsoption.',fix:'Navigation auf Mobile ausblenden oder auf Logo + CTA reduzieren.'},
        {id:'U3',name:'Call-to-Action',status:'green',finding:'2 CTAs gefunden: "Tarif wählen", "Jetzt vergleichen".',detail:'',fix:''},
        {id:'U4',name:'Trust & Social Proof',status:'amber',finding:'1 Trust-Signal: Trust-Keywords (bewertung, geprüft).',detail:'Kein Schema.org AggregateRating-Markup, kein Trust-Bild erkannt.',fix:'Kundenbewertungen + TÜV-Siegel im oberen Viewport platzieren.'},
        {id:'U5',name:'Performance Mobile',status:'amber',finding:'PageSpeed Mobile: 71/100 · LCP: 2.8s · CLS: 0.08 · TBT: 180ms',detail:'LCP 2.8s liegt über dem Zielwert von 2.5s.',fix:'Bilder in WebP konvertieren, kritischen CSS inline laden.'},
      ],screenshot_base64:null},
    desktop:{success:true,device:'desktop',score:78,
      comment:'Die Desktop-Ansicht zeigt eine klare Struktur mit gut erkennbarem Value Proposition. Die CTA-Buttons sind visuell prominent und gut positioniert. Trust-Elemente könnten noch weiter nach oben verschoben werden, um sofort Vertrauen aufzubauen.',
      checks:[
        {id:'U1',name:'Above-the-Fold & Nutzenversprechen',status:'green',finding:'H1 vorhanden · Kernbotschaft und Einstieg above fold erkennbar.',detail:'',fix:''},
        {id:'U2',name:'Ablenkungsfreiheit & Benutzerführung',status:'green',finding:'Navigation mit 4 Links — überschaubar, kein Overload.',detail:'',fix:''},
        {id:'U3',name:'Call-to-Action',status:'green',finding:'3 CTAs gefunden: "Tarif wählen", "Jetzt vergleichen", "Mehr erfahren".',detail:'',fix:''},
        {id:'U4',name:'Trust & Social Proof',status:'amber',finding:'2 Trust-Signale: Trust-Keywords (bewertung, geprüft, zertifiziert).',detail:'Bewertungs-Widget vorhanden, aber zu weit unten.',fix:'AggregateRating-Schema.org einbinden und Siegel in die Hero-Section verschieben.'},
        {id:'U5',name:'Performance Desktop',status:'green',finding:'PageSpeed Desktop: 89/100 · LCP: 1.8s · CLS: 0.02 · TBT: 60ms',detail:'',fix:''},
      ],screenshot_base64:null}
  };
  sitemapData={found:true,loc_count:312,sitemap_url:'https://www.beispiel-energie.de/sitemap.xml'};
  psiDesktopData={success:true,strategy:'desktop',perf_score:89,lcp:'1.8 s',cls:'0.02',tbt:'60 ms',fcp:'0.9 s',inp:'120 ms'};
  log('UX/CRO: Mobile 60% + Desktop 78% → Ø 69% (Demo)','ok');

  setProgress(92,'Ergebnisse rendern…','Fast fertig…');
  renderResults('Strom Tarife Vergleich');
  setProgress(100,'Fertig!','Demo-Analyse abgeschlossen.');
  await sleep(600);
  if(timerInterval){clearInterval(timerInterval);timerInterval=null;}
  const totalSec=Math.round((Date.now()-analysisStartTime)/1000);
  document.getElementById('progress-timer').textContent='Fertig in '+formatTime(totalSec);
  document.getElementById('skeleton-wrap').style.display='none';
  document.getElementById('progress-bar-wrap').style.display='none';
  document.getElementById('loader-wrap').style.display='none';
  document.getElementById('status-msg').style.display='none';
  document.getElementById('progress-label').textContent='Demo abgeschlossen';
  document.getElementById('results-section').style.display='block';
  document.getElementById('log-wrap').classList.add('collapsed');
  document.getElementById('progress-section').style.display='block';
  document.getElementById('btn-start').disabled=false;
  document.getElementById('btn-demo').disabled=false;
}

// === START ANALYSIS ===
async function startAnalysis(){
  const urlVal=document.getElementById('url-input').value.trim();
  const htmlVal=document.getElementById('html-textarea').value.trim();
  const keyword=document.getElementById('ctx-keyword').value.trim();
  if(currentMode==='url'&&!urlVal){alert('Bitte eine URL eingeben.');return}
  if(currentMode==='html'&&!htmlVal){alert('Bitte HTML einfügen.');return}

  isDemoMode=false;
  document.getElementById('exec-summary').style.display='none';
  document.getElementById('exec-summary-content').style.display='none';
  document.getElementById('exec-summary-loading').style.display='flex';
  document.getElementById('btn-start').disabled=true;
  document.getElementById('btn-demo').disabled=true;
  document.getElementById('header-form').classList.add('input-dimmed');
  document.getElementById('progress-section').style.display='block';
  document.getElementById('progress-section').dataset.active='1';
  document.getElementById('progress-bar-wrap').style.display='block';
  document.getElementById('loader-wrap').style.display='block';
  document.getElementById('status-msg').style.display='block';
  document.getElementById('progress-pct').style.display='';
  document.getElementById('results-section').style.display='none';
  document.getElementById('skeleton-wrap').style.display='block';
  document.getElementById('log-wrap').classList.remove('collapsed');
  document.getElementById('log-box').innerHTML='';
  analysisResults=[];pqResults=[];e8Result=null;ymylResult=null;
  gscData=null;serpData=null;backlinkData=null;psiData=null;psiDesktopData=null;sistrixData=null;geoData=null;kwData=null;ucrData=null;sitemapData=null;
  analysisStartTime=Date.now();lastPct=0;
  updateAgentBadge('sqeg','running');
  if(timerInterval)clearInterval(timerInterval);
  timerInterval=setInterval(updateTimer,1000);
  document.getElementById('progress-timer').textContent='';
  ['sqeg-results','perf-results','geo-results','kw-results','ux-results','technical-results'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.display='none';});
  ['sqeg-empty','perf-empty','geo-empty','kw-empty','ux-empty','technical-empty'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.display='block';});
  const uxBC2=document.getElementById('ux-body-card');if(uxBC2)uxBC2.style.display='none';
  setProgress(0,'Analyse startet…','Vorbereitung…');
  showView('overview');

  try{
    if(currentMode==='url'){
      currentUrl=urlVal;
      log('Rufe URL ab: '+currentUrl);
      setProgress(2,'HTML abrufen…','Seite wird geladen…');
      const res=await fetch('fetch.php?url='+encodeURIComponent(currentUrl));
      if(!res.ok)throw new Error('fetch.php HTTP '+res.status);
      const data=await res.json();
      if(data.error)throw new Error(data.error);
      currentHtml=data.html;
      log(`HTML abgerufen (${(data.length/1024).toFixed(1)} KB)`,'ok');
    }else{
      currentUrl=urlVal||'(HTML-Modus)';
      currentHtml=htmlVal;
      log('HTML manuell eingefügt ('+( currentHtml.length/1024).toFixed(1)+' KB)','ok');
    }
    setProgress(5);
    const pageText=extractPageText(currentHtml);
    const wordCount=pageText.split(/\s+/).filter(Boolean).length;
    log(`Seitentext extrahiert: ${(pageText.length/1024).toFixed(0)} KB · ~${wordCount.toLocaleString('de-DE')} Wörter (von ${(currentHtml.length/1024).toFixed(0)} KB HTML)`,'ok');
    // Vollständiger Text für Prompts (max. 80.000 Zeichen ≈ 20K Tokens)
    const htmlSnippet=pageText.substring(0,80000);
    const effectiveKeyword=keyword||'';

    // Externe Daten parallel abrufen (Fehler blockieren nicht)
    setProgress(5,'Daten abrufen…','GSC · SERP · Backlinks · PageSpeed · Sistrix · GEO…');
    const [gscRes,serpRes,blRes,psiRes,sistrixRes,geoRes,sitemapRes,psiDeskRes]=await Promise.allSettled([
      currentMode==='url'&&currentUrl?fetchGscData(currentUrl):Promise.resolve(null),
      effectiveKeyword?fetchSerpData(effectiveKeyword):Promise.resolve(null),
      currentMode==='url'&&currentUrl?fetchBacklinkData(currentUrl):Promise.resolve(null),
      currentMode==='url'&&currentUrl?fetchPageSpeedData(currentUrl):Promise.resolve(null),
      currentMode==='url'&&currentUrl?fetchSistrixData(currentUrl):Promise.resolve(null),
      currentMode==='url'&&currentUrl?fetchGeoData(currentUrl):Promise.resolve(null),
      currentMode==='url'&&currentUrl?fetchSitemapData(currentUrl):Promise.resolve(null),
      currentMode==='url'&&currentUrl?fetchPageSpeedData(currentUrl,'desktop'):Promise.resolve(null),
    ]);
    gscData        = gscRes.status==='fulfilled'?gscRes.value:null;
    serpData       = serpRes.status==='fulfilled'?serpRes.value:null;
    backlinkData   = blRes.status==='fulfilled'?blRes.value:null;
    psiData        = psiRes.status==='fulfilled'?psiRes.value:null;
    sistrixData    = sistrixRes.status==='fulfilled'?sistrixRes.value:null;
    geoData        = geoRes.status==='fulfilled'?geoRes.value:null;
    sitemapData    = sitemapRes.status==='fulfilled'?sitemapRes.value:null;
    psiDesktopData = psiDeskRes.status==='fulfilled'?psiDeskRes.value:null;
    if(sitemapData?.found!==undefined)log(`Sitemap: LP-URL ${sitemapData.found?'✓ enthalten':'✗ nicht gefunden'} (${sitemapData.loc_count} URLs geprüft)`,'ok');
    else if(sitemapData?.is_index)log(`Sitemap: Sitemap-Index gefunden (${sitemapData.sub_count} Sub-Sitemaps)`,'ok');
    else if(currentMode==='url')log('Sitemap: konnte nicht abgerufen werden (kein /sitemap.xml?)');
    else log('Sitemap: übersprungen (HTML-Modus)');
    if(psiDesktopData?.success)log(`PageSpeed Desktop: ${psiDesktopData.perf_score}/100 · LCP: ${psiDesktopData.lcp||'–'} · INP: ${psiDesktopData.inp||'–'}`,'ok');
    else if(currentMode==='url')log('PageSpeed Desktop: nicht verfügbar');
    else log('PageSpeed Desktop: übersprungen (HTML-Modus)');

    if(gscData?.keywords?.length)log(`GSC: ${gscData.keywords.length} Keywords geladen`,'ok');
    else if(gscData?._empty)log('GSC: verbunden, aber keine Daten für diese URL (keine Impressionen in 90 Tagen?)');
    else if(gscData?._error)log(`GSC: Fehler — ${gscData._error}`,'err');
    else if(currentMode==='url')log('GSC: übersprungen (HTML-Modus oder keine Verbindung)');
    else log('GSC: übersprungen (HTML-Modus)');
    if(sistrixData?.success&&!sistrixData.no_data)log(`Sistrix: Sichtbarkeit ${sistrixData.visibility??'–'} · ${sistrixData.kw_count??'?'} Keywords (DE)`,'ok');
    else if(sistrixData?.success&&sistrixData.no_data)log('Sistrix: keine Daten für diese URL (in Sistrix nicht indexiert?)');
    else if(sistrixData?.error)log(`Sistrix: ${sistrixData.error}`,'err');
    else if(currentMode==='url')log('Sistrix: keine Daten (API-Key nicht konfiguriert oder Fehler)');
    else log('Sistrix: übersprungen (HTML-Modus)');
    if(geoData?.success)log(`GEO: ${geoData.prompts?.length??0} AI-Prompts · ${geoData.sources?.length??0} Quellen gefunden`,'ok');
    else if(geoData?.error)log(`GEO: ${geoData.error}`,'err');
    else if(currentMode==='url')log('GEO: keine KI-Sichtbarkeitsdaten (Entity nicht in Sistrix AI-Index?)');
    else log('GEO: übersprungen (HTML-Modus)');
    // Keyword-Intent sequenziell (braucht gscData)
    if(currentMode==='url'&&gscData?.keywords?.length){
      try{
        const topKws=gscData.keywords.slice(0,6).map(k=>k.query);
        kwData=await fetchKeywordData(topKws);
        if(kwData?.results){const cnt=Object.values(kwData.results).filter(Boolean).length;log(`Keyword-Fit: Intent für ${cnt} Keywords analysiert`,'ok');}
        else log('Keyword-Fit: keine Intent-Daten (Sistrix nicht konfiguriert?)');
      }catch(e){kwData=null;log('Keyword-Fit: Fehler — '+e.message,'err');}
    }else{kwData=null;}
    // UX/CRO Analyse async — Mobile zuerst, Desktop parallel — beide laden progressiv
    if(currentMode==='url'&&currentUrl){
      ucrData={};
      document.getElementById('ux-empty').style.display='none';
      document.getElementById('ux-loading').style.display='none';
      document.getElementById('ux-results').style.display='block';
      document.getElementById('ux-loading-mobile').style.display='block';
      document.getElementById('ux-loading-desktop').style.display='block';
      showUxDevice('mobile');
      // Mobile zuerst — dann Desktop sequenziell (PHP CLI = single-threaded)
      fetchUxData(currentUrl,'mobile',psiData).then(d=>{
        if(d?.success){
          ucrData.mobile=d;
          log('UX/CRO Mobile: Analyse abgeschlossen · Score '+d.score+'%','ok');
          renderUXAnalysis();
        }else{
          log('UX/CRO Mobile: '+(d?.error||'Fehler'),'err');
          const loadEl=document.getElementById('ux-loading-mobile');
          if(loadEl)loadEl.style.display='none';
        }
      }).catch(e=>{log('UX/CRO Mobile: '+e.message,'err');}).finally(()=>{
        // Desktop NACH Mobile (PHP CLI single-threaded — sequenziell ist stabiler)
        fetchPageSpeedData(currentUrl,'desktop').catch(()=>null).then(psiDesktop=>{
          return fetchUxData(currentUrl,'desktop',psiDesktop);
        }).then(d=>{
          if(d?.success){
            ucrData.desktop=d;
            log('UX/CRO Desktop: Analyse abgeschlossen · Score '+d.score+'%','ok');
            renderUXAnalysis();
            updateModuleCards();
            renderPagePreview();
          }else{
            log('UX/CRO Desktop: '+(d?.error||'Fehler'),'err');
            const loadEl=document.getElementById('ux-loading-desktop');
            if(loadEl)loadEl.style.display='none';
          }
        }).catch(e=>{log('UX/CRO Desktop: '+e.message,'err');const loadEl=document.getElementById('ux-loading-desktop');if(loadEl)loadEl.style.display='none';});
      });
    }else{ucrData=null;}
    if(serpData?.tasks?.[0]?.result?.[0]?.items)log(`SERP: Top-10 für "${effectiveKeyword}" geladen`,'ok');
    else if(effectiveKeyword)log(`SERP: keine Daten für "${effectiveKeyword}"`);
    if(backlinkData?.tasks?.[0]?.result?.[0])log('Backlinks: Profil geladen','ok');
    else log('Backlinks: keine Daten');
    if(psiData?.success)log(`PageSpeed: Score ${psiData.perf_score}/100 (Mobile)`,'ok');
    else if(currentMode==='url')log('PageSpeed: keine Daten');
    setProgress(10);

    // Kontext-Blöcke bauen
    const ctx={
      ctxBlock:    buildCtxBlock(effectiveKeyword,gscData,wordCount,currentUrl),
      serpBlock:   buildSerpBlock(serpData,effectiveKeyword),
      backlinkBlock: buildBacklinkBlock(backlinkData),
      psiBlock:    buildPsiBlock(psiData),
      schemaBlock: buildSchemaBlock(currentHtml),
    };

    log('Klassifiziere YMYL…');
    setProgress(11,'YMYL klassifizieren…','YMYL-Analyse…');
    ymylResult=await classifyYmyl(htmlSnippet,currentUrl);
    log('YMYL: '+ymylResult,'ok');
    setProgress(13);

    log('Starte 21 SQEG-Mini-Calls (42 Kriterien) in Batches…');
    setProgress(18,'SQEG-Kriterien analysieren…','KI-Anfragen…');
    const BATCH_SIZE=5;
    let callsDone=0;
    for(let b=0;b<MINI_CALLS.length;b+=BATCH_SIZE){
      const batch=MINI_CALLS.slice(b,b+BATCH_SIZE);
      const batchResults=await Promise.allSettled(batch.map((ids,j)=>runMiniCall(ids,htmlSnippet,currentUrl,ymylResult,effectiveKeyword,b+j,ctx)));
      batchResults.forEach((r,j)=>{
        const i=b+j;
        const names=MINI_CALLS[i].map(id=>CRITERIA.find(c=>c.id===id)?.name||id).join(' · ');
        if(r.status==='fulfilled'){analysisResults.push(...r.value);log(`✓ ${names}`,'ok')}
        else{log(`✗ ${names}: `+r.reason,'err')}
        callsDone++;
        setProgress(13+(callsDone/21)*77);
      });
    }
    setProgress(92,'Ergebnisse rendern…','Fast fertig…'); // 90→92 via last batch
    renderResults(keyword);
    AGENTS.sqeg.lastOutput = [...analysisResults];
    updateAgentBadge('sqeg','done');
    setProgress(100,'Fertig!','Analyse abgeschlossen.');
    setTimeout(()=>{
      if(timerInterval){clearInterval(timerInterval);timerInterval=null;}
      const totalSec=Math.round((Date.now()-analysisStartTime)/1000);
      document.getElementById('progress-timer').textContent=`Fertig in ${formatTime(totalSec)}`;
      document.getElementById('skeleton-wrap').style.display='none';
      // Fortschrittsbalken + Loader ausblenden, Log-Box bleibt sichtbar
      document.getElementById('progress-bar-wrap').style.display='none';
      document.getElementById('loader-wrap').style.display='none';
      document.getElementById('status-msg').style.display='none';
      document.getElementById('progress-label').textContent='Analyse abgeschlossen';
      document.getElementById('results-section').style.display='block';
      document.getElementById('log-wrap').classList.add('collapsed');
      document.getElementById('progress-section').style.display='block';
    },600);
  }catch(err){
    if(timerInterval){clearInterval(timerInterval);timerInterval=null;}
    document.getElementById('skeleton-wrap').style.display='none';
    log('Kritischer Fehler: '+err.message,'err');
    setProgress(0,'Fehler',err.message);
    updateAgentBadge('sqeg','error');
  }
  document.getElementById('btn-start').disabled=false;
  document.getElementById('btn-demo').disabled=false;
}
// === API HELPER ===
async function callApi(messages,systemPrompt,maxTokens=2000){
  const res=await fetch('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({messages,system:systemPrompt,max_tokens:maxTokens})});
  if(!res.ok){const txt=await res.text().catch(()=>'');throw new Error('HTTP '+res.status+(txt?' — '+txt.substring(0,120):''));}
  const data=await res.json();
  if(data.error)throw new Error(typeof data.error==='object'?data.error.message:data.error);
  return data.content?.[0]?.text??'';
}

// === DATEN-FETCH ===
async function fetchSitemapData(url){
  try{
    const origin=new URL(url).origin;
    const sitemapUrl=origin+'/sitemap.xml';
    const res=await fetch('fetch.php?url='+encodeURIComponent(sitemapUrl));
    if(!res.ok)return null;
    const data=await res.json();
    if(data.error||!data.html)return null;
    const xml=data.html;
    // Sitemap-Index erkennen
    if(xml.includes('<sitemapindex')){
      const subs=(xml.match(/<loc[^>]*>(.*?)<\/loc>/gi)||[]).map(m=>m.replace(/<\/?loc[^>]*>/gi,'').trim());
      return{is_index:true,sub_count:subs.length,sitemap_url:sitemapUrl};
    }
    // Reguläre Sitemap — URL-Normalisierung
    const locs=(xml.match(/<loc[^>]*>(.*?)<\/loc>/gi)||[]).map(m=>m.replace(/<\/?loc[^>]*>/gi,'').trim());
    const normalize=s=>s.replace(/\/$/,'').toLowerCase();
    const urlNorm=normalize(url);
    const found=locs.some(loc=>normalize(loc)===urlNorm);
    return{found,loc_count:locs.length,sitemap_url:sitemapUrl};
  }catch(e){return null;}
}
async function fetchGscData(url){
  try{
    const res=await fetch('gsc.php?action=data',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({url})});
    const d=await res.json();
    if(d.success&&d.keywords?.length)return d;
    if(d.success&&!d.keywords?.length)return{success:true,keywords:[],_empty:true};
    return{success:false,_error:d.error||'Unbekannter Fehler'};
  }catch(e){return{success:false,_error:e.message};}
}
async function fetchSistrixData(url){
  try{
    const res=await fetch('sistrix.php?action=url_data',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},body:JSON.stringify({url,csrf_token:CSRF_TOKEN})});
    if(!res.ok)return null;
    const d=await res.json();
    return d.success?d:null;
  }catch(e){return null;}
}
async function fetchGeoData(url){
  try{
    const res=await fetch('sistrix.php?action=geo_data',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},body:JSON.stringify({url,csrf_token:CSRF_TOKEN})});
    if(!res.ok)return null;
    const d=await res.json();
    return d.success?d:null;
  }catch(e){return null;}
}
async function fetchKeywordData(keywords){
  if(!keywords||!keywords.length)return null;
  try{
    const res=await fetch('keywords.php?action=search_intent',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},body:JSON.stringify({keywords,csrf_token:CSRF_TOKEN})});
    if(!res.ok)return null;
    const d=await res.json();
    return d.success?d:null;
  }catch(e){return null;}
}
async function fetchUxData(url, device, psiPayload){
  try{
    const body={url, device, psi_data:psiPayload||null, csrf_token:CSRF_TOKEN};
    const res=await fetch('ux.php?action=analyze',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},body:JSON.stringify(body)});
    if(!res.ok){
      const txt=await res.text().catch(()=>'');
      return {success:false,error:'HTTP '+res.status+(txt?' — '+txt.substring(0,120):'') };
    }
    return await res.json();
  }catch(e){return {success:false,error:e.message||'fetch-Fehler'};}
}
function showUxDevice(device){
  document.getElementById('ux-device-mobile').style.display=device==='mobile'?'block':'none';
  document.getElementById('ux-device-desktop').style.display=device==='desktop'?'block':'none';
  const tabM=document.getElementById('ux-tab-mobile');
  const tabD=document.getElementById('ux-tab-desktop');
  if(tabM){tabM.style.borderBottomColor=device==='mobile'?'var(--accent)':'transparent';tabM.style.color=device==='mobile'?'var(--accent)':'var(--text3)';}
  if(tabD){tabD.style.borderBottomColor=device==='desktop'?'var(--accent)':'transparent';tabD.style.color=device==='desktop'?'var(--accent)':'var(--text3)';}
}
function renderUxDevice(data){
  if(!data?.success)return;
  const device=data.device||'mobile';
  const checks=data.checks||[];
  const colorMap={green:'var(--green)',amber:'var(--amber)',red:'var(--red)'};
  const bgMap={green:'var(--green-bg)',amber:'var(--amber-bg)',red:'var(--red-bg)'};
  const borderMap={green:'var(--green-border)',amber:'var(--amber-border)',red:'var(--red-border)'};
  const iconMap={green:'✓',amber:'◑',red:'✗'};

  // Check-Liste
  const checksEl=document.getElementById('ux-checks-'+device);
  const checksContent=document.getElementById('ux-checks-content-'+device);
  if(checksEl&&checksContent&&checks.length){
    const g=checks.filter(c=>c.status==='green').length;
    const a=checks.filter(c=>c.status==='amber').length;
    const r=checks.filter(c=>c.status==='red').length;
    let html=`<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px">
      <div style="font-size:12px;font-weight:600;color:var(--text)">5 UX-Kriterien · ${device==='mobile'?'Mobile (375px)':'Desktop (1280px)'}</div>
      <div style="display:flex;gap:6px">
        <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;border:1px solid var(--green-border);background:var(--green-bg);font-size:11px;font-weight:600;color:var(--green)">✓ ${g}</span>
        <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;border:1px solid var(--amber-border);background:var(--amber-bg);font-size:11px;font-weight:600;color:var(--amber)">◑ ${a}</span>
        <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;border:1px solid var(--red-border);background:var(--red-bg);font-size:11px;font-weight:600;color:var(--red)">✗ ${r}</span>
      </div>
    </div><div style="display:flex;flex-direction:column;gap:0">`;
    checks.forEach((c,i)=>{
      const isLast=i===checks.length-1;
      html+=`<div style="display:flex;align-items:flex-start;gap:14px;padding:14px 0;${isLast?'':'border-bottom:1px solid var(--border)'}">
        <div style="width:28px;height:28px;border-radius:50%;background:${bgMap[c.status]};border:1px solid ${borderMap[c.status]};display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:${colorMap[c.status]};flex-shrink:0;margin-top:1px">${iconMap[c.status]}</div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px">
            <span style="font-size:10px;font-weight:600;color:var(--text3);font-family:'Geist Mono','Courier New',monospace">${c.id}</span>
            <span style="font-size:13px;font-weight:600;color:var(--text)">${escHtml(c.name)}</span>
          </div>
          <div style="font-size:12px;color:var(--text2);line-height:1.5">${escHtml(c.finding)}</div>
          ${c.detail?`<div style="font-size:11px;color:var(--text3);margin-top:4px;line-height:1.4">${escHtml(c.detail)}</div>`:''}
          ${c.fix?`<div style="font-size:11px;color:var(--accent);margin-top:5px;padding:4px 8px;background:var(--accent-bg);border-radius:var(--radius-sm);border:1px solid var(--accent-border);line-height:1.4"><strong>Fix:</strong> ${escHtml(c.fix)}</div>`:''}
        </div>
      </div>`;
    });
    html+='</div>';
    checksContent.innerHTML=html;
    checksEl.style.display='block';
  }

  // Screenshot
  const shotEl=document.getElementById('ux-screenshot-'+device);
  const shotImg=document.getElementById('ux-shot-img-'+device);
  if(shotEl&&shotImg&&data.screenshot_base64){
    shotImg.src='data:image/png;base64,'+data.screenshot_base64;
    shotEl.style.display='block';
  }

  // LLM-Kommentar
  const commentEl=document.getElementById('ux-comment-'+device);
  const commentContent=document.getElementById('ux-comment-content-'+device);
  if(commentEl&&commentContent&&data.comment){
    commentContent.textContent=data.comment;
    commentEl.style.display='block';
  }

  // Tab-Score updaten
  const score=data.score||0;
  const tabScore=document.getElementById('ux-tab-score-'+device);
  if(tabScore)tabScore.textContent=score+'%';

  // Loading verstecken
  const loadEl=document.getElementById('ux-loading-'+device);
  if(loadEl)loadEl.style.display='none';
}
function renderUXAnalysis(){
  // ucrData enthält {mobile:..., desktop:...}
  const mobileData=ucrData?.mobile;
  const desktopData=ucrData?.desktop;
  if(!mobileData&&!desktopData)return;

  // Gesamtscore: Durchschnitt beider Devices (oder nur was vorhanden)
  const scores=[];
  if(mobileData?.score!=null)scores.push(mobileData.score);
  if(desktopData?.score!=null)scores.push(desktopData.score);
  const avgScore=scores.length?Math.round(scores.reduce((a,b)=>a+b,0)/scores.length):0;
  const cls=avgScore>=70?'green':avgScore>=50?'amber':'red';

  const numEl=document.getElementById('ux-score-num');
  const lvlEl=document.getElementById('ux-score-level');
  const barEl=document.getElementById('ux-score-bar');
  if(numEl){numEl.textContent=avgScore+'%';numEl.className='score-hero-num '+cls;}
  if(lvlEl){lvlEl.textContent=avgScore>=70?'High':avgScore>=50?'Medium':'Low';lvlEl.className='score-hero-level '+cls;}
  if(barEl){barEl.className='score-hero-bar '+cls;barEl.style.width=avgScore+'%';}

  // Chips aus allen Checks kombiniert
  const allChecks=[...(mobileData?.checks||[]),...(desktopData?.checks||[])];
  const g=allChecks.filter(c=>c.status==='green').length;
  const a=allChecks.filter(c=>c.status==='amber').length;
  const r=allChecks.filter(c=>c.status==='red').length;
  const cntGEl=document.getElementById('ux-cnt-g');const cntAEl=document.getElementById('ux-cnt-a');const cntREl=document.getElementById('ux-cnt-r');
  if(cntGEl)cntGEl.textContent=g;if(cntAEl)cntAEl.textContent=a;if(cntREl)cntREl.textContent=r;

  // Devices rendern
  if(mobileData)renderUxDevice(mobileData);
  if(desktopData)renderUxDevice(desktopData);

  // Module Card + Nav Score
  const mcScore=document.getElementById('mc-ux-score');
  const navScore=document.getElementById('nav-score-ux');
  const mcBar=document.getElementById('mc-ux-bar');
  const mcLabel=document.getElementById('mc-ux-label');
  const mcCard=document.getElementById('mc-ux');
  if(mcScore){mcScore.textContent=avgScore+'%';mcScore.className='module-card-score '+(avgScore>=70?'green':avgScore>=50?'amber':'red');}
  if(navScore){navScore.textContent=avgScore+'%';navScore.className='nav-score'+(avgScore>=70?' green':avgScore>=50?' amber':' red');}
  if(mcBar){mcBar.style.width=avgScore+'%';mcBar.className='module-card-bar '+(avgScore>=70?'green':avgScore>=50?'amber':'red');}
  if(mcLabel)mcLabel.textContent=avgScore>=70?'Gute UX':avgScore>=50?'Optimierungsbedarf':'Kritische Probleme';
  if(mcCard){mcCard.classList.remove('mc-green','mc-amber','mc-red');mcCard.classList.add(avgScore>=70?'mc-green':avgScore>=50?'mc-amber':'mc-red');}
}
function renderPagePreview(){
  // PagePreview nutzt jetzt UX-Screenshot (mobile device) falls vorhanden
  const b64=ucrData?.mobile?.screenshot_base64||ucrData?.desktop?.screenshot_base64||ucrData?.screenshot_base64||null;
  if(!b64)return;
  const src='data:image/png;base64,'+b64;
  const card=document.getElementById('page-preview-card');
  const img=document.getElementById('page-preview-img');
  const urlEl=document.getElementById('page-preview-url');
  if(card&&img){img.src=src;if(urlEl)urlEl.textContent=currentUrl||'–';card.style.display='block';}
  const thumb=document.getElementById('sqeg-page-thumb');
  const thumbImg=document.getElementById('sqeg-page-thumb-img');
  if(thumb&&thumbImg){thumbImg.src=src;thumb.style.display='block';}
}
async function enrichGscWithSerpFeatures(keywords){
  if(!keywords||!keywords.length)return;
  try{
    const res=await fetch('sistrix.php?action=serp_features',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},body:JSON.stringify({keywords,csrf_token:CSRF_TOKEN})});
    if(!res.ok)return;
    const d=await res.json();
    if(!d.results)return;
    document.querySelectorAll('.serp-badges[data-kw]').forEach(el=>{
      const kw=el.getAttribute('data-kw');
      const features=d.results[kw];
      if(!features)return;
      let badges='';
      if(features.ai_overview>0)badges+=`<span data-tip="AI Overview: Google zeigt für dieses Keyword eine KI-generierte Antwort über den organischen Ergebnissen" style="font-size:9px;background:#6c47ff22;color:#6c47ff;border:1px solid #6c47ff44;border-radius:3px;padding:1px 4px;font-weight:600">AI</span>`;
      if(features.featured_snippet>0)badges+=`<span data-tip="Featured Snippet: Google zeigt einen hervorgehobenen Textauszug direkt in den Suchergebnissen" style="font-size:9px;background:var(--accent-bg);color:var(--accent);border:1px solid var(--accent-border);border-radius:3px;padding:1px 4px;font-weight:600">FS</span>`;
      if(features.knowledge_graph>0||features.knowledge_panel>0)badges+=`<span data-tip="Knowledge Graph: Google zeigt ein Informationspanel mit strukturierten Daten zu diesem Thema" style="font-size:9px;background:var(--bg4);color:var(--text3);border:1px solid var(--border);border-radius:3px;padding:1px 4px">KG</span>`;
      el.innerHTML=badges;
    });
  }catch(e){}
}
async function fetchSerpData(keyword){
  try{const res=await fetch('dataforseo.php?action=serp',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({keyword,limit:10})});return await res.json();}catch(e){return null;}
}
async function fetchBacklinkData(url){
  try{const res=await fetch('dataforseo.php?action=backlinks',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({target:url})});return await res.json();}catch(e){return null;}
}
async function fetchPageSpeedData(url,strategy='mobile'){
  try{const res=await fetch('pagespeed.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({url,strategy})});return await res.json();}catch(e){return null;}
}

// === KONTEXT-BLÖCKE ===
function buildCtxBlock(keyword,gsc,wordCount,url){
  let b='';
  if(keyword)b+=`\nZiel-Keyword: ${keyword}`;
  if(wordCount)b+=`\nSeitentext-Umfang: ~${wordCount} Wörter`;
  if(gsc?.keywords?.length){
    const top=gsc.keywords.slice(0,10);
    b+='\n\nGSC-Keyword-Performance (90 Tage):\n'+top.map(k=>`• ${k.query}: ${k.clicks} Klicks, ${k.impressions} Imp., CTR ${k.ctr}%, Pos. ${k.position}`).join('\n');
    try{
      const hostname=new URL(url).hostname.replace(/^www\./,'');
      const brandPart=hostname.split('.')[0];
      if(brandPart&&brandPart.length>2){
        const branded=gsc.keywords.filter(k=>k.query.toLowerCase().includes(brandPart.toLowerCase()));
        const totalClicks=gsc.keywords.reduce((s,k)=>s+k.clicks,0);
        const brandedClicks=branded.reduce((s,k)=>s+k.clicks,0);
        const ratio=totalClicks>0?Math.round(brandedClicks/totalClicks*100):0;
        b+=`\n\nGSC-Branded-Queries: ${branded.length} von ${gsc.keywords.length} Keywords sind Brand-Anfragen (${ratio}% der Klicks). Brand: "${brandPart}".`;
      }
    }catch(e){}
  }
  return b;
}
function buildSerpBlock(serp,keyword){
  if(!serp?.tasks?.[0]?.result?.[0]?.items)return'';
  const items=serp.tasks[0].result[0].items.filter(i=>i.type==='organic').slice(0,10);
  if(!items.length)return'';
  return`\n\nSERP-Benchmark für "${keyword}" (Top ${items.length}):\n`+items.map((i,n)=>`${n+1}. ${i.url||i.relative_url||''} – ${i.title||''}${i.description?'\n   '+i.description.substring(0,100):''}`).join('\n');
}
function buildBacklinkBlock(bl){
  const r=bl?.tasks?.[0]?.result?.[0];
  if(!r)return'';
  return`\n\nBacklink-Profil:\n• Domain Rank: ${r.rank||'–'}\n• Referring Domains: ${r.referring_domains||'–'}\n• Backlinks: ${r.backlinks||'–'}\n• Spam Score: ${r.spam_score||0}%`;
}
function buildPsiBlock(psi){
  if(!psi?.success)return'';
  return`\n\nPageSpeed Mobile:\n• Score: ${psi.perf_score||'–'}/100\n• LCP: ${psi.lcp||'–'} · CLS: ${psi.cls||'–'} · TBT: ${psi.tbt||'–'} · FCP: ${psi.fcp||'–'}`;
}
function buildSchemaBlock(html){
  try{
    const parser=new DOMParser();
    const doc=parser.parseFromString(html,'text/html');
    const types=new Set();
    doc.querySelectorAll('script[type="application/ld+json"]').forEach(s=>{
      try{
        const obj=JSON.parse(s.textContent);
        const arr=Array.isArray(obj)?obj:[obj];
        arr.forEach(o=>{
          [].concat(o['@type']||[]).forEach(t=>types.add(t));
          (o['@graph']||[]).forEach(g=>[].concat(g['@type']||[]).forEach(t=>types.add(t)));
        });
      }catch(e){}
    });
    doc.querySelectorAll('[itemtype]').forEach(el=>{
      const t=(el.getAttribute('itemtype')||'').replace(/https?:\/\/schema\.org\//,'');
      if(t)types.add(t);
    });
    if(!types.size)return'\n\nStrukturierte Daten (Schema.org): Keines gefunden – weder JSON-LD noch Microdata.';
    return`\n\nStrukturierte Daten (Schema.org) gefunden: ${[...types].join(', ')}`;
  }catch(e){return'';}
}

// === PAGE TEXT EXTRACTION ===
function extractPageText(html){
  try{
    const parser=new DOMParser();
    const doc=parser.parseFromString(html,'text/html');
    ['script','style','noscript','nav','footer','aside','head','svg','iframe','template','form'].forEach(tag=>{
      doc.querySelectorAll(tag).forEach(el=>el.remove());
    });
    const text=(doc.body?.textContent||'').replace(/[ \t]+/g,' ').replace(/\n{3,}/g,'\n\n').trim();
    return text;
  }catch(e){
    return html.replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').trim();
  }
}

// === YMYL ===
async function classifyYmyl(htmlSnippet,url){
  const sys=`Du bist ein Google Search Quality Evaluator. Klassifiziere den YMYL-Status der Seite.\nAntworte NUR mit einem dieser drei Werte (kein weiterer Text): clear_ymyl | mixed_ymyl | none\nYMYL-Kategorien: Finanzen, Medizin/Gesundheit, Recht, Sicherheit, große Kaufentscheidungen, Neuigkeiten/gesellschaftliche Themen, Kinderschutz.`;
  const r=await callApi([{role:'user',content:`URL: ${url}\nSeitentext (3000 Zeichen):\n${htmlSnippet.substring(0,3000)}`}],sys,50);
  const c=r.trim().toLowerCase();
  if(c.includes('clear_ymyl'))return 'clear_ymyl';
  if(c.includes('mixed_ymyl'))return 'mixed_ymyl';
  return 'none';
}

// === MINI CALLS ===
async function runMiniCall(ids,htmlSnippet,url,ymyl,keyword,idx,ctx={}){
  const criteriaList=ids.map(id=>{const c=CRITERIA.find(x=>x.id===id);return`${c.id} · ${c.name} · ${c.ref}`}).join('\n');
  const ymylHint=ymyl==='clear_ymyl'?'YMYL: Klar YMYL – erhöhte Qualitätsanforderungen.':ymyl==='mixed_ymyl'?'YMYL: Teilweise YMYL – erhöhte Sorgfalt.':'';
  const sys=AGENTS.sqeg.getPrompt();
  const contextParts=(ctx.ctxBlock||'')+(ctx.serpBlock||'')+(ctx.backlinkBlock||'')+(ctx.psiBlock||'')+(ctx.schemaBlock||'');
  const msg=`URL: ${url}\nSeitentext (vollst\u00e4ndig):\n${htmlSnippet}${keyword?'\nKeyword: '+keyword:''}\n${ymylHint}${contextParts}\n\nZu bewertende Kriterien:\n${criteriaList}`;
  const text=await callApi([{role:'user',content:msg}],sys,2500);
  let m=text.match(/\[[\s\S]*\]/);
  if(!m){
    // Fallback: JSON wurde evtl. abgeschnitten — versuche offenes Array zu schließen
    const partial=text.match(/\[[\s\S]+/);
    if(partial){
      try{m=[partial[0].replace(/,?\s*\{[^}]*$/, '')+']'];JSON.parse(m[0]);}catch(e){m=null;}
    }
  }
  if(!m)throw new Error('Kein JSON-Array in Call '+(idx+1));
  return JSON.parse(m[0]);
}

// === RENDERING ===
function calcScore(){
  let tw=0,ts=0;
  analysisResults.forEach(r=>{const w=getEffectiveWeight(r.id);tw+=w;ts+=statusScore(r.status)*w});
  return tw>0?ts/tw:0;
}
function scoreToLevel(s){
  if(s>=85)return'Highest';if(s>=70)return'High';if(s>=50)return'Medium';if(s>=30)return'Low';return'Lowest';
}
function getScoreInterpretation(s){
  if(s>=90)return{label:'Sehr gute Qualit\u00e4t',sentence:'Sehr hohe Qualit\u00e4t mit nur geringem Optimierungsbedarf.'};
  if(s>=75)return{label:'Gute Qualit\u00e4t',sentence:'Gute Qualit\u00e4t mit kleineren Optimierungsm\u00f6glichkeiten.'};
  if(s>=60)return{label:'Mittlere Qualit\u00e4t',sentence:'Solide Basis mit relevanten Optimierungspotenzialen.'};
  if(s>=40)return{label:'Niedrige Qualit\u00e4t',sentence:'Deutliche Defizite mit priorit\u00e4rem Optimierungsbedarf.'};
  return{label:'Sehr niedrige Qualit\u00e4t',sentence:'Kritischer Zustand mit hohem Handlungsdruck.'};
}

// === EXECUTIVE SUMMARY ===
function renderExecSummary({bewertung,interpretation,probleme,schritte}){
  document.getElementById('exec-summary-loading').style.display='none';
  const c=document.getElementById('exec-summary-content');
  c.innerHTML=`<div class="exec-summary-grid">
    <div class="exec-summary-section">
      <div class="exec-summary-section-title">Gesamtbewertung</div>
      <div class="exec-summary-score">${escHtml(bewertung)}</div>
      <div class="exec-summary-interpretation">${escHtml(interpretation)}</div>
    </div>
    <div class="exec-summary-section">
      <div class="exec-summary-section-title">Hauptprobleme</div>
      ${probleme.map(p=>{
        const label=typeof p==='object'?p.label:p;
        const expl=typeof p==='object'?p.explanation:'';
        return`<div class="exec-summary-problem"><div class="exec-summary-problem-label">✖ ${escHtml(label)}</div>${expl?`<div class="exec-summary-problem-arrow">→ ${escHtml(expl)}</div>`:''}</div>`;
      }).join('')}
    </div>
  </div>
  <div class="exec-summary-steps">
    <div class="exec-summary-section-title" style="margin-bottom:10px">Empfohlene nächste Schritte</div>
    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px">
      ${schritte.map((s,i)=>`<div style="display:flex;align-items:flex-start;gap:10px;background:var(--bg2);border-radius:var(--radius-sm);padding:10px 12px;border:1px solid var(--border)"><span class="exec-summary-num">${i+1}</span><span style="flex:1;font-size:12px;color:var(--text2);line-height:1.5">${escHtml(s)}</span></div>`).join('')}
    </div>
  </div>`;
  c.style.display='block';
  document.getElementById('exec-summary').style.display='block';
}

function parseExecSummary(text){
  const bm=text.match(/Gesamtbewertung:\s*\n(.+)\n([\s\S]*?)(?=\n\s*Hauptprobleme:|$)/i);
  const pm=text.match(/Hauptprobleme:\s*\n([\s\S]*?)(?=\n\s*Empfohlene nächste Schritte:|$)/i);
  const sm=text.match(/Empfohlene nächste Schritte:\s*\n([\s\S]*?)$/i);
  // Parse ✖/→ Zeilenpaare
  const probLines=(pm?pm[1]:'').split('\n').map(l=>l.trim()).filter(l=>l);
  const probleme=[];
  for(let i=0;i<probLines.length&&probleme.length<3;i++){
    if(/^[✖✗x]/iu.test(probLines[i])){
      const label=probLines[i].replace(/^[✖✗x]\s*/iu,'').trim();
      const expl=(probLines[i+1]&&/^→/.test(probLines[i+1]))?probLines[++i].replace(/^→\s*/,'').trim():'';
      probleme.push({label,explanation:expl});
    }
  }
  return{
    bewertung:(bm?bm[1]:'').trim(),
    interpretation:(bm?bm[2]:'').trim(),
    probleme,
    schritte:(sm?sm[1]:'').split('\n').map(l=>l.replace(/^\d+\.\s*/,'')).filter(l=>l.trim()).slice(0,3),
  };
}

// === MODULE CARDS + NAV BADGES ===
function updateModuleCards(){
  // SQEG Score
  const sqegScore=calcScore();
  document.getElementById('mc-sqeg-score').textContent=sqegScore+'%';
  document.getElementById('nav-score-sqeg').textContent=sqegScore+'%';
  const mcSqeg=document.getElementById('mc-sqeg');
  if(mcSqeg){mcSqeg.classList.remove('mc-green','mc-amber','mc-red');mcSqeg.classList.add(sqegScore>=70?'mc-green':sqegScore>=45?'mc-amber':'mc-red');}

  // Performance Score (Heuristik aus Sistrix Visibility)
  let perfScore=0;
  if(sistrixData?.success){
    const vis=parseFloat(sistrixData.visibility||0);
    perfScore=vis>=1?75:vis>=0.1?50:vis>0?30:15;
    if(gscData?.keywords?.length>10)perfScore=Math.min(perfScore+15,100);
  }else if(gscData?.keywords?.length){perfScore=35;}
  const mcPerfEl=document.getElementById('mc-perf-score');
  const navPerfEl=document.getElementById('nav-score-perf');
  const mcPerfCard=document.getElementById('mc-perf');
  if(mcPerfEl)mcPerfEl.textContent=perfScore?perfScore+'%':'–';
  if(navPerfEl)navPerfEl.textContent=perfScore?perfScore+'%':'–';
  if(mcPerfCard){mcPerfCard.classList.remove('mc-green','mc-amber','mc-red');if(perfScore)mcPerfCard.classList.add(perfScore>=70?'mc-green':perfScore>=45?'mc-amber':'mc-red');}

  // GEO Score (Heuristik aus AI Prompts/Sources)
  let geoScore=0;
  if(geoData?.success){
    const prompts=geoData.prompts?.length||0;
    const sources=geoData.sources?.length||0;
    geoScore=prompts>=5?80:prompts>=2?55:prompts>=1?35:sources>=1?25:10;
  }
  const mcGeoEl=document.getElementById('mc-geo-score');
  const navGeoEl=document.getElementById('nav-score-geo');
  const mcGeoCard=document.getElementById('mc-geo');
  if(mcGeoEl)mcGeoEl.textContent=geoScore?geoScore+'%':'–';
  if(navGeoEl)navGeoEl.textContent=geoScore?geoScore+'%':'–';
  if(mcGeoCard){mcGeoCard.classList.remove('mc-green','mc-amber','mc-red');if(geoScore)mcGeoCard.classList.add(geoScore>=70?'mc-green':geoScore>=45?'mc-amber':'mc-red');}

  // Keyword-Fit Score
  let kwScore=0;
  if(kwData?.results){
    const entries=Object.values(kwData.results).filter(Boolean);
    if(entries.length){
      // Dominanten Intent der Page aus Top-Keyword ermitteln
      const intentKeys=['informational','navigational','transactional','commercial'];
      const totals={informational:0,navigational:0,transactional:0,commercial:0};
      entries.forEach(e=>intentKeys.forEach(k=>{totals[k]+=(e[k]||0);}));
      const pageIntent=intentKeys.reduce((a,b)=>totals[a]>totals[b]?a:b);
      // Score = Ø Anteil des dominanten Intents an allen Keywords
      const avgFit=entries.reduce((s,e)=>s+(e[pageIntent]||0),0)/entries.length;
      kwScore=Math.round(avgFit*100);
      // Konsistenz-Bonus: wenn alle Keywords selben Intent teilen
      const allMatch=entries.every(e=>intentKeys.reduce((a,b)=>e[a]>e[b]?a:b)===pageIntent);
      if(allMatch)kwScore=Math.min(kwScore+10,100);
    }
  }
  const mcKwEl=document.getElementById('mc-kw-score');
  const navKwEl=document.getElementById('nav-score-kw');
  const mcKwBar=document.getElementById('mc-kw-bar');
  const mcKwLabel=document.getElementById('mc-kw-label');
  if(mcKwEl){mcKwEl.textContent=kwScore?kwScore+'%':'–';mcKwEl.className='module-card-score '+(kwScore>=70?'green':kwScore>=45?'amber':kwScore>0?'red':'neutral');}
  if(navKwEl){navKwEl.textContent=kwScore?kwScore+'%':'–';navKwEl.className='nav-score'+(kwScore>=70?' green':kwScore>=45?' amber':kwScore>0?' red':'');}
  if(mcKwBar){mcKwBar.style.width=kwScore+'%';mcKwBar.className='module-card-bar '+(kwScore>=70?'green':kwScore>=45?'amber':kwScore>0?'red':'neutral');}
  if(mcKwLabel)mcKwLabel.textContent=kwScore>=70?'Gutes Targeting':kwScore>=45?'Targeting verbesserbar':kwScore>0?'Targeting-Mismatch':'Noch nicht analysiert';

  // UX / CRO Score — Durchschnitt mobile + desktop
  const uxScores=[];
  if(ucrData?.mobile?.score!=null)uxScores.push(ucrData.mobile.score);
  if(ucrData?.desktop?.score!=null)uxScores.push(ucrData.desktop.score);
  const uxScore=uxScores.length?Math.round(uxScores.reduce((a,b)=>a+b,0)/uxScores.length):0;
  const mcUxEl=document.getElementById('mc-ux-score');
  const navUxEl=document.getElementById('nav-score-ux');
  const mcUxBar=document.getElementById('mc-ux-bar');
  const mcUxLabel=document.getElementById('mc-ux-label');
  const mcUxCard=document.getElementById('mc-ux');
  if(mcUxEl){mcUxEl.textContent=uxScore?uxScore+'%':'–';mcUxEl.className='module-card-score '+(uxScore>=70?'green':uxScore>=50?'amber':uxScore>0?'red':'neutral');}
  if(navUxEl){navUxEl.textContent=uxScore?uxScore+'%':'–';navUxEl.className='nav-score'+(uxScore>=70?' green':uxScore>=50?' amber':uxScore>0?' red':'');}
  if(mcUxBar){mcUxBar.style.width=uxScore+'%';mcUxBar.className='module-card-bar '+(uxScore>=70?'green':uxScore>=50?'amber':uxScore>0?'red':'neutral');}
  if(mcUxLabel)mcUxLabel.textContent=uxScore>=70?'Gute UX':uxScore>=50?'UX verbesserbar':uxScore>0?'UX kritisch':'Noch nicht analysiert';
  if(mcUxCard){mcUxCard.classList.remove('mc-green','mc-amber','mc-red');if(uxScore)mcUxCard.classList.add(uxScore>=70?'mc-green':uxScore>=50?'mc-amber':'mc-red');}

  renderRadarChart(sqegScore,perfScore,geoScore);
}

// === RADAR CHART ===
function renderRadarChart(sqeg, perf, geo){
  const svg=document.getElementById('radar-svg');
  const card=document.getElementById('radar-card');
  if(!svg||!card)return;
  const cx=150,cy=105,r=78;
  // 3 axes: SQEG top (-90°), Performance bottom-right (30°), GEO bottom-left (150°)
  const axes=[
    {label:'SQEG',         score:sqeg||0, angle:-Math.PI/2},
    {label:'Performance',  score:perf||0, angle:Math.PI/6},
    {label:'GEO\u202f/\u202fAEO',score:geo||0,  angle:5*Math.PI/6},
  ];
  const pt=(angle,frac)=>({
    x:cx+r*frac*Math.cos(angle),
    y:cy+r*frac*Math.sin(angle)
  });
  const toD=(pts)=>pts.map((p,i)=>(i===0?'M':'L')+p.x.toFixed(1)+','+p.y.toFixed(1)).join(' ')+' Z';

  let h='';
  // Grid rings
  [0.25,0.5,0.75,1].forEach((frac,i)=>{
    const pts=axes.map(a=>pt(a.angle,frac));
    const dash=i<3?'stroke-dasharray="4 3"':'';
    h+=`<path d="${toD(pts)}" fill="none" stroke="var(--border2)" stroke-width="${i===3?1.5:1}" ${dash} opacity="${i===3?0.8:0.5}"/>`;
    if(i<3){
      const lp=pt(-Math.PI/2,frac);
      h+=`<text x="${(lp.x+4).toFixed(1)}" y="${(lp.y-3).toFixed(1)}" font-size="8" fill="var(--text3)" font-family="Inter,sans-serif">${frac*100}%</text>`;
    }
  });
  // Axis lines
  axes.forEach(a=>{
    const ep=pt(a.angle,1);
    h+=`<line x1="${cx}" y1="${cy}" x2="${ep.x.toFixed(1)}" y2="${ep.y.toFixed(1)}" stroke="var(--border2)" stroke-width="1" opacity="0.6"/>`;
  });
  // Data polygon
  const dataPts=axes.map(a=>pt(a.angle,Math.max(a.score,2)/100));
  h+=`<path d="${toD(dataPts)}" fill="var(--accent)" fill-opacity="0.12" stroke="var(--accent)" stroke-width="2.5" stroke-linejoin="round"/>`;
  // Data dots
  dataPts.forEach(p=>{
    h+=`<circle cx="${p.x.toFixed(1)}" cy="${p.y.toFixed(1)}" r="4.5" fill="var(--accent)" stroke="var(--bg)" stroke-width="2.5"/>`;
  });
  // Labels
  axes.forEach((a,i)=>{
    const lp=pt(a.angle,1.28);
    const anchor=Math.abs(a.angle+Math.PI/2)<0.1?'middle':Math.cos(a.angle)>0.05?'start':'end';
    const scoreColor=a.score>=70?'var(--green)':a.score>=45?'var(--amber)':'var(--red)';
    const displayScore=a.score?a.score+'%':'–';
    h+=`<text x="${lp.x.toFixed(1)}" y="${(lp.y-5).toFixed(1)}" font-size="11" font-weight="600" fill="var(--text)" text-anchor="${anchor}" font-family="Inter,sans-serif">${a.label}</text>`;
    h+=`<text x="${lp.x.toFixed(1)}" y="${(lp.y+10).toFixed(1)}" font-size="12" font-weight="700" fill="${scoreColor}" text-anchor="${anchor}" font-family="Inter,sans-serif">${displayScore}</text>`;
  });
  svg.innerHTML=h;
  card.style.display='block';
}

// === TOP PRIORITIES ===
function renderTopPriorities(){
  const list=document.getElementById('top-priorities-list');
  const container=document.getElementById('top-priorities');
  if(!list)return;
  const issues=analysisResults.filter(r=>r.rating==='red'||r.rating==='amber').sort((a,b)=>{
    const w={red:0,amber:1};return (w[a.rating]??2)-(w[b.rating]??2);
  }).slice(0,5);
  if(container)container.style.display='block';
  if(!issues.length){list.innerHTML='<li style="color:var(--text-secondary)">Keine kritischen Befunde.</li>';return;}
  list.innerHTML=issues.map(r=>`<li class="top-prio-item top-prio-${r.rating}">
    <span class="top-prio-dot" style="background:${r.rating==='red'?'var(--red)':'var(--amber)'}"></span>
    <span class="top-prio-label">${escHtml(r.criterion||r.label||'')}</span>
  </li>`).join('');
}

async function generateExecSummary(){
  document.getElementById('exec-summary').style.display='block';
  // Demo mode: static data, no API call
  if(isDemoMode){
    const _dScore=Math.round(calcScore());
    const _dInterp=getScoreInterpretation(_dScore);
    renderExecSummary({
      bewertung:`${_dScore} / 100 \u2013 ${_dInterp.label}`,
      interpretation:'Vertrauenssignale fehlen, Tarifinhalte sind veraltet und Core Web Vitals liegen im kritischen Bereich.',
      probleme:[
        {label:'Keine Autorenschaft erkennbar',explanation:'Nutzer finden keine Person, der sie die Informationen zuordnen können.'},
        {label:'Tarifdaten nicht aktuell',explanation:'Veraltete Preise erhöhen das Risiko falscher Kaufentscheidungen.'},
        {label:'Core Web Vitals im roten Bereich',explanation:'Ladezeit und Layout-Stabilität beeinträchtigen Ranking und Nutzererfahrung.'},
      ],
      schritte:[
        'Autorenprofil mit Name und Qualifikation ergänzen',
        'Tarifdaten-Review-Prozess wöchentlich einrichten',
        'Bilder in WebP konvertieren und Lazy Loading aktivieren',
      ],
    });
    return;
  }
  // Real analysis: build context and call AI
  const score=Math.round(calcScore());
  const hasLowest=analysisResults.some(r=>getEffectiveWeight(r.id)>=4&&r.status==='red');
  const level=hasLowest?'Lowest':scoreToLevel(score);
  const reds=analysisResults.filter(r=>r.status==='red').sort((a,b)=>getEffectiveWeight(b.id)-getEffectiveWeight(a.id));
  const ambers=analysisResults.filter(r=>r.status==='amber').sort((a,b)=>getEffectiveWeight(b.id)-getEffectiveWeight(a.id));
  const greens=analysisResults.filter(r=>r.status==='green').sort((a,b)=>getEffectiveWeight(b.id)-getEffectiveWeight(a.id));
  const fmtCrit=arr=>arr.slice(0,6).map(r=>{
    const c=CRITERIA.find(x=>x.id===r.id)||{};
    const verdict=(r.finding||'').split('|').pop().replace(/^Bewertung:\s*/,'').trim();
    return`- ${c.name}: ${verdict}${r.improvement?' → '+r.improvement:''}`;
  }).join('\n');
  const sys=`Du bist ein UX-Writer und SEO-Experte und erstellst eine Executive Summary für ein Website-Analyse-Dashboard.
Du bekommst neben dem SQEG-Qualitätsscore auch externe Datenpunkte: GSC-Rankings, Sistrix-Sichtbarkeit, Quick-Win-Keywords und KI-Sichtbarkeit (GEO/AEO).
Nutze ALLE verfügbaren Daten für eine ganzheitliche Priorisierung. Wenn Quick-Win-Keywords oder fehlende KI-Sichtbarkeit relevant sind, priorisiere das über reine SQEG-Punkte.
Antworte AUSSCHLIESSLICH in folgendem Format – keine Einleitung, kein Abschlusstext:

Gesamtbewertung:
[X / 100 – Einordnung] ← Einordnung MUSS exakt lauten: Sehr niedrige Qualität / Niedrige Qualität / Mittlere Qualität / Gute Qualität / Sehr gute Qualität
[genau 1 kurzer Satz: benennt 2–3 wichtigste Problemfelder, max. 15 Wörter, keine generischen Aussagen]

Hauptprobleme:
✖ [Problem-Titel, max. 10–12 Wörter]
→ [Ursache ODER Auswirkung, max. 10–12 Wörter, kein „–“ im Satz]
✖ [Problem-Titel, max. 10–12 Wörter]
→ [Ursache ODER Auswirkung, max. 10–12 Wörter, kein „–“ im Satz]
✖ [Problem-Titel, max. 10–12 Wörter]
→ [Ursache ODER Auswirkung, max. 10–12 Wörter, kein „–“ im Satz]

Empfohlene nächste Schritte:
1. [konkrete Aktion, max. 8–10 Wörter, sofort umsetzbar]
2. [konkrete Aktion, max. 8–10 Wörter, sofort umsetzbar]
3. [konkrete Aktion, max. 8–10 Wörter, sofort umsetzbar]

Global-Regeln:
- Genau 3 Probleme (je ✖-Zeile + →-Zeile), genau 3 Maßnahmen
- Kein Score oder KPI-Wert im Fließtext
- Kein gemischter Schreibstil, keine komplexen Satzstrukturen
- Kein einzelner Punkt mit mehreren kombinierten Problemen
- Konsistente sprachliche Struktur über alle Punkte`;
  const msg=`URL: ${currentUrl}\nScore: ${score} / 100 – ${level}\nYMYL: ${ymylResult||'none'}\n\nProbleme (rot, nach Gewicht):\n${fmtCrit(reds)}\n\nVerbesserungspotenziale (amber):\n${fmtCrit(ambers)}\n\nPositive Aspekte:\n${greens.slice(0,4).map(r=>(CRITERIA.find(x=>x.id===r.id)||{}).name||r.id).join(', ')}${(()=>{
  let ext='';
  // GSC-Kontext
  if(gscData?.keywords?.length){
    const topKws=gscData.keywords.slice(0,5).map(k=>`${k.query} (Pos. ${k.position}, ${k.clicks} Klicks)`).join(', ');
    ext+=`\n\nGSC Top-Keywords: ${topKws}`;
  }
  // Sistrix-Kontext
  if(sistrixData?.success&&!sistrixData.no_data){
    if(sistrixData.visibility!==null)ext+=`\nSistrix Sichtbarkeitsindex: ${sistrixData.visibility}`;
    if(sistrixData.opportunities?.length){
      const topOpps=sistrixData.opportunities.slice(0,3).map(o=>`${o.keyword} (Pos. ${o.position}, Gain ${o.gain})`).join(', ');
      ext+=`\nQuick-Win-Keywords (Pos. 4-20): ${topOpps}`;
    }
    if(sistrixData.competitors?.length){
      ext+=`\nOrganische Wettbewerber: ${sistrixData.competitors.map(c=>c.domain).join(', ')}`;
    }
  }
  // GEO-Kontext
  if(geoData?.success&&geoData.prompts?.length){
    ext+=`\nKI-Sichtbarkeit: Marke erscheint in ${geoData.prompts.length} AI-Prompts (ChatGPT/Perplexity)`;
  }else if(geoData?.success){
    ext+=`\nKI-Sichtbarkeit: Marke aktuell nicht in AI-Antworten gefunden`;
  }
  return ext;
})()}`;
  try{
    const text=await callApi([{role:'user',content:msg}],sys,700);
    const parsed=parseExecSummary(text);
    parsed.bewertung=`${score} / 100 \u2013 ${getScoreInterpretation(score).label}`;
    renderExecSummary(parsed);
  }catch(e){
    document.getElementById('exec-summary-loading').style.display='none';
    document.getElementById('exec-summary-content').innerHTML=`<div style="color:var(--text3);font-size:13px">Executive Summary konnte nicht erstellt werden.</div>`;
    document.getElementById('exec-summary-content').style.display='block';
  }
}

function renderResults(keyword){
  document.getElementById('header-form').classList.remove('input-dimmed');
  const score=calcScore();
  const hasLowestSignal=analysisResults.some(r=>getEffectiveWeight(r.id)>=4&&r.status==='red');
  const level=hasLowestSignal?'Lowest':scoreToLevel(score);
  const g=analysisResults.filter(r=>r.status==='green').length;
  const a=analysisResults.filter(r=>r.status==='amber').length;
  const r=analysisResults.filter(r=>r.status==='red').length;
  const cls=score>=70?'green':score>=50?'amber':'red';

  // === Score Hero ===
  document.getElementById('score-hero-num').textContent=Math.round(score)+'%';
  document.getElementById('score-hero-num').className='score-hero-num '+cls;
  const levelEl=document.getElementById('score-hero-level');
  levelEl.textContent=level; levelEl.className='score-hero-level '+cls;
  const interp=getScoreInterpretation(Math.round(score));
  document.getElementById('score-hero-interp').textContent=interp.sentence;
  const bar=document.getElementById('score-hero-bar');
  bar.className='score-hero-bar '+cls; bar.style.width=Math.round(score)+'%';
  // YMYL chip
  const ymylEl=document.getElementById('ymyl-badge');
  if(ymylResult==='clear_ymyl'){ymylEl.innerHTML=`<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> YMYL: Erhöht`;ymylEl.style.color='var(--red)'}
  else if(ymylResult==='mixed_ymyl'){ymylEl.innerHTML=`<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> YMYL: Teilweise`;ymylEl.style.color='var(--amber)'}
  else{ymylEl.innerHTML=`<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Kein YMYL`;ymylEl.style.color='var(--green)'}
  const _cht=g+a+r;
  document.getElementById('cnt-g').textContent=_cht>0?Math.round(g/_cht*100)+'%':'0%';
  document.getElementById('cnt-a').textContent=_cht>0?Math.round(a/_cht*100)+'%':'0%';
  document.getElementById('cnt-r').textContent=_cht>0?Math.round(r/_cht*100)+'%':'0%';
  const totalChipSqeg=document.getElementById('cnt-total-sqeg');
  if(totalChipSqeg)totalChipSqeg.textContent=_cht;

  const gscPanel=document.getElementById('gsc-panel');
  if(gscData?.keywords?.length){
    gscPanel.style.display='block';
    const top=gscData.keywords.slice(0,15);
    const maxClicks=Math.max(...top.map(k=>k.clicks),1);
    document.getElementById('gsc-panel-content').innerHTML=
      '<table style="width:100%;font-size:12px;border-collapse:collapse">'
      +'<thead><tr style="color:var(--text3);font-size:11px"><th style="text-align:left;padding:3px 8px 3px 0">Keyword</th><th style="text-align:right;padding:3px 4px" data-tip="Tatsächliche Klicks aus der Google Search Console (90-Tage-Zeitraum)">Klicks</th><th style="text-align:right;padding:3px 4px" data-tip="Impressionen: Wie oft die Seite in Suchergebnissen angezeigt wurde">Imp.</th><th style="text-align:right;padding:3px 4px" data-tip="Click-Through-Rate: Anteil der Impressionen die zu einem Klick geführt haben">CTR</th><th style="text-align:right;padding:3px 4px" data-tip="Durchschnittliche Google-Ranking-Position (1 = ganz oben)">Pos.</th></tr></thead>'
      +'<tbody>'+top.map(k=>{
        const bar=Math.round((k.clicks/maxClicks)*60);
        const posColor=k.position<=3?'var(--green)':k.position<=10?'var(--amber)':'var(--text3)';
        return`<tr><td style="padding:3px 8px 3px 0"><span style="display:inline-block;width:${bar}px;height:4px;background:var(--blue);border-radius:2px;vertical-align:middle;margin-right:6px"></span><span data-kw="${escHtml(k.query)}">${escHtml(k.query)}</span><span class="serp-badges" data-kw="${escHtml(k.query)}" style="display:inline-flex;gap:3px;margin-left:5px;vertical-align:middle"></span></td><td style="text-align:right;padding:3px 4px">${k.clicks}</td><td style="text-align:right;padding:3px 4px">${k.impressions}</td><td style="text-align:right;padding:3px 4px">${k.ctr}%</td><td style="text-align:right;padding:3px 4px;color:${posColor};font-weight:600">${k.position}</td></tr>`;
      }).join('')+'</tbody></table>';
    // SERP-Features async nachladen (Top 5 Keywords)
    enrichGscWithSerpFeatures(top.slice(0,5).map(k=>k.query));
  }else{gscPanel.style.display='none';}
  const sistrixPanel=document.getElementById('sistrix-panel');
  if(sistrixData?.success&&!sistrixData.no_data){
    sistrixPanel.style.display='block';
    const vis=sistrixData.visibility,kwCnt=sistrixData.kw_count,kws=sistrixData.keywords||[];
    const opps=sistrixData.opportunities||[],comps=sistrixData.competitors||[];
    let html='<div style="display:flex;gap:12px;margin-bottom:12px;flex-wrap:wrap">';
    if(vis!==null)html+=`<div style="background:var(--bg3);border-radius:var(--radius-sm);padding:8px 14px;text-align:center"><div style="font-size:11px;color:var(--text3);margin-bottom:2px">Sichtbarkeitsindex</div><div style="font-size:18px;font-weight:700;color:var(--accent)">${vis}</div></div>`;
    if(kwCnt!==null)html+=`<div style="background:var(--bg3);border-radius:var(--radius-sm);padding:8px 14px;text-align:center"><div style="font-size:11px;color:var(--text3);margin-bottom:2px">Keywords Top\u00a0100</div><div style="font-size:18px;font-weight:700;color:var(--text)">${kwCnt.toLocaleString('de-DE')}</div></div>`;
    html+='</div>';
    if(kws.length){
      const maxVol=Math.max(...kws.map(k=>k.volume),1);
      html+='<table style="width:100%;font-size:12px;border-collapse:collapse"><thead><tr style="color:var(--text3);font-size:11px"><th style="text-align:left;padding:3px 8px 3px 0">Keyword</th><th style="text-align:right;padding:3px 4px">Position</th><th style="text-align:right;padding:3px 4px">Suchvolumen</th></tr></thead><tbody>'
        +kws.map(k=>{
          const bar=Math.round((k.volume/maxVol)*60);
          const posColor=k.position<=3?'var(--green)':k.position<=10?'var(--amber)':'var(--text3)';
          return`<tr><td style="padding:3px 8px 3px 0"><span style="display:inline-block;width:${bar}px;height:4px;background:var(--accent);border-radius:2px;vertical-align:middle;margin-right:6px;opacity:.55"></span>${escHtml(k.keyword)}</td><td style="text-align:right;padding:3px 4px;color:${posColor};font-weight:600">${k.position}</td><td style="text-align:right;padding:3px 4px">${k.volume.toLocaleString('de-DE')}</td></tr>`;
        }).join('')+'</tbody></table>';
    }else{html+='<p style="font-size:12px;color:var(--text3);margin:0">Keine Keywords f\u00fcr diese URL gefunden.</p>';}
    // \u2500\u2500 Wettbewerber \u2500\u2500
    if(comps.length){
      html+='<div style="margin-top:16px"><div style="font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Organische Wettbewerber</div>';
      html+='<div style="display:flex;flex-wrap:wrap;gap:6px">';
      comps.forEach(c=>{
        const pct=Math.round((c.competition||0)*100);
        html+=`<div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:5px 10px;font-size:12px;display:flex;align-items:center;gap:8px"><span style="font-weight:500">${escHtml(c.domain)}</span><span style="font-size:10px;color:var(--text3)">${pct}%</span></div>`;
      });
      html+='</div></div>';
    }
    // \u2500\u2500 Quick Wins (domain.opportunities) \u2500\u2500
    if(opps.length){
      html+='<div style="margin-top:16px"><div style="font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Quick Wins \u2014 Optimierungspotenzial</div>';
      html+='<table style="width:100%;font-size:12px;border-collapse:collapse"><thead><tr style="color:var(--text3);font-size:11px"><th style="text-align:left;padding:3px 8px 3px 0">Keyword</th><th style="text-align:right;padding:3px 4px">Position</th><th style="text-align:right;padding:3px 4px">Gain</th><th style="text-align:right;padding:3px 4px">Wettbewerb</th></tr></thead><tbody>';
      opps.forEach(o=>{
        const gainColor=o.gain>=70?'var(--green)':o.gain>=40?'var(--amber)':'var(--text3)';
        const posColor=o.position<=5?'var(--amber)':'var(--text3)';
        html+=`<tr><td style="padding:3px 8px 3px 0">${escHtml(o.keyword)}</td><td style="text-align:right;padding:3px 4px;color:${posColor};font-weight:600">${o.position}</td><td style="text-align:right;padding:3px 4px;color:${gainColor};font-weight:600">${o.gain}</td><td style="text-align:right;padding:3px 4px;color:var(--text3)">${Math.round((o.competition||0)*100)}%</td></tr>`;
      });
      html+='</tbody></table></div>';
    }
    document.getElementById('sistrix-panel-content').innerHTML=html;
  }else{sistrixPanel.style.display='none';}
  // \u2500\u2500 GEO-Panel \u2500\u2500
  const geoPanel=document.getElementById('geo-panel');
  if(geoData?.success){
    geoPanel.style.display='block';
    const prompts=geoData.prompts||[],sources=geoData.sources||[];
    let ghtml='';
    if(prompts.length){
      ghtml+='<div style="margin-bottom:14px"><div style="font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">AI-Prompts \u2014 die Marke wird in diesen KI-Antworten erw\u00e4hnt</div>';
      ghtml+='<div style="display:flex;flex-direction:column;gap:5px">';
      prompts.slice(0,15).forEach(p=>{
        const model=escHtml(p.model||'');
        const modelBadge=model?`<span style="font-size:10px;background:var(--bg4);border-radius:4px;padding:2px 6px;color:var(--text3);white-space:nowrap">${model}</span>`:'';
        ghtml+=`<div style="display:flex;align-items:center;gap:8px;font-size:12px;padding:4px 0;border-bottom:1px solid var(--border)"><span style="flex:1">${escHtml(p.prompt||'')}</span>${modelBadge}</div>`;
      });
      ghtml+='</div></div>';
    }
    if(sources.length){
      ghtml+='<div><div style="font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Als KI-Quelle zitierte URLs der Domain</div>';
      ghtml+='<div style="display:flex;flex-direction:column;gap:4px">';
      sources.slice(0,10).forEach(s=>{
        ghtml+=`<div style="font-size:12px;color:var(--accent);padding:2px 0;border-bottom:1px solid var(--border);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(s.url||s)}</div>`;
      });
      ghtml+='</div></div>';
    }
    if(!prompts.length&&!sources.length)ghtml='<p style="font-size:12px;color:var(--text3);margin:0">Keine KI-Sichtbarkeitsdaten gefunden (Entity nicht im Sistrix AI-Index).</p>';
    document.getElementById('geo-panel-content').innerHTML=ghtml;
  }else{geoPanel.style.display='none';}
  renderClusterOverview();
  renderCriteriaTable(analysisResults,'all');
  // Technical SEO — deterministisch, immer ausführbar wenn HTML vorhanden
  if(currentHtml){
    renderTechnicalSeo();
    document.getElementById('technical-results').style.display='block';
    document.getElementById('technical-empty').style.display='none';
  }
  // Activate sub-views
  document.getElementById('progress-section').dataset.active='0';
  document.getElementById('results-section').style.display='block';
  document.getElementById('sqeg-results').style.display='block';
  document.getElementById('sqeg-empty').style.display='none';
  if(gscData?.keywords?.length||sistrixData?.success){
    document.getElementById('perf-results').style.display='block';
    document.getElementById('perf-empty').style.display='none';
  }
  if(geoData?.success){
    document.getElementById('geo-results').style.display='block';
    document.getElementById('geo-empty').style.display='none';
  }
  if(kwData?.results){
    renderKeywordFit();
    document.getElementById('kw-results').style.display='block';
    document.getElementById('kw-empty').style.display='none';
  }
  // UX/CRO — wenn ucrData bereits vorhanden (Demo-Modus: mobile+desktop direkt verfügbar)
  if(ucrData?.mobile||ucrData?.desktop){
    document.getElementById('ux-results').style.display='block';
    document.getElementById('ux-empty').style.display='none';
    document.getElementById('ux-loading').style.display='none';
    showUxDevice('mobile');
    renderUXAnalysis();
  }
  // Modul-Kacheln updaten
  updateModuleCards();
  // Top-Prioritäten in Übersicht
  renderTopPriorities();
  // Nach Analyse direkt zum SQEG-View
  showView('sqeg');
  generateExecSummary();
}

// ═══════════════════════════════════════════════════════════
// M2 — TECHNICAL SEO (deterministisch, kein KI-Call)
// ═══════════════════════════════════════════════════════════
function runTechnicalSeo(html, url, psi, sitemap, psiDesktop){
  const doc = (() => {
    try { return new DOMParser().parseFromString(html,'text/html'); } catch(e){ return null; }
  })();

  const checks = [];

  // ── T1: Indexierbarkeit ────────────────────────────────
  let noindex = false;
  if(doc){
    const robots = doc.querySelector('meta[name="robots"],meta[name="ROBOTS"]');
    if(robots){ const c=(robots.getAttribute('content')||'').toLowerCase(); if(c.includes('noindex'))noindex=true; }
    const xRobots = (html.match(/X-Robots-Tag[^:\n]*:[^\n]*/i)||[])[0]||'';
    if(xRobots.toLowerCase().includes('noindex'))noindex=true;
  }
  checks.push({id:'T1',name:'Indexierbarkeit',status:noindex?'red':'green',
    finding:noindex?'Seite ist durch noindex-Direktive von Google ausgeschlossen.':'Kein noindex gefunden — Seite ist indexierbar.',
    detail:noindex?'robots-Meta oder X-Robots-Tag enthält "noindex". Google wird diese Seite nicht in die Suchergebnisse aufnehmen.':'',
    fix:noindex?'<meta name="robots"> auf "index,follow" setzen oder das noindex-Attribut entfernen.':''});

  // ── T2: Canonical URL ─────────────────────────────────
  let canonical='', canonicalOk=false;
  if(doc){ const c=doc.querySelector('link[rel="canonical"]'); if(c){ canonical=c.getAttribute('href')||''; canonicalOk=!!canonical; }}
  checks.push({id:'T2',name:'Canonical URL',status:canonicalOk?'green':'amber',
    finding:canonicalOk?`Canonical vorhanden: ${canonical.length>60?canonical.substring(0,60)+'…':canonical}`:'Kein <link rel="canonical"> gefunden.',
    detail:canonicalOk?'Hilft Google bei Duplicate-Content-Fragen und konsolidiert Link-Equity.':'Ohne Canonical kann Google eine andere URL als bevorzugte Version wählen — besonders relevant bei URL-Parametern.',
    fix:canonicalOk?'':` <link rel="canonical" href="${url||'[URL der Seite]'}"> im <head> ergänzen.`});

  // ── T3: Title-Tag ─────────────────────────────────────
  let title='', titleLen=0;
  if(doc){ title=(doc.querySelector('title')?.textContent||'').trim(); titleLen=title.length; }
  const titleStatus = !title?'red':titleLen<30||titleLen>60?'amber':'green';
  checks.push({id:'T3',name:'Title-Tag',status:titleStatus,
    finding:!title?'Kein <title>-Tag gefunden.':titleLen<30?`Title zu kurz (${titleLen} Zeichen): "${title}"`:titleLen>60?`Title zu lang (${titleLen} Zeichen, Empfehlung max. 60): "${title.substring(0,60)}…"`:`Title ok (${titleLen} Zeichen): "${title}"`,
    detail:titleLen>60?'Google schneidet Titles über ~60 Zeichen in der SERP ab.':titleLen<30?'Sehr kurzer Title ist wenig informativ und für Nutzer kaum scanbar.':'',
    fix:!title?'<title>Keyword · Marke</title> im <head> ergänzen.':titleLen>60?'Title auf max. 60 Zeichen kürzen — Keyword möglichst vorn platzieren.':titleLen<30?'Title ausführlicher gestalten (30–60 Zeichen, Keyword + Marke).':''});

  // ── T4: Meta-Description ─────────────────────────────
  let desc='', descLen=0;
  if(doc){ desc=(doc.querySelector('meta[name="description"],meta[name="Description"]')?.getAttribute('content')||'').trim(); descLen=desc.length; }
  const descStatus = !desc?'red':descLen<80||descLen>155?'amber':'green';
  checks.push({id:'T4',name:'Meta-Description',status:descStatus,
    finding:!desc?'Keine <meta name="description"> gefunden.':descLen<80?`Description zu kurz (${descLen} Zeichen).`:descLen>155?`Description zu lang (${descLen} Zeichen, wird bei ~155 abgeschnitten).`:`Description ok (${descLen} Zeichen).`,
    detail:!desc?'Google generiert dann automatisch ein Snippet — meistens unoptimiert.':descLen>155?'Alles über ~155 Zeichen wird in der SERP abgeschnitten und nie angezeigt.':'',
    fix:!desc?'<meta name="description" content="…"> mit 120–155 Zeichen ergänzen.':descLen>155?`Auf max. 155 Zeichen kürzen. Aktuell: "${desc.substring(0,60)}…"`:''});

  // ── T5: H1-Tag ───────────────────────────────────────
  let h1s=[];
  if(doc){ h1s=Array.from(doc.querySelectorAll('h1')).map(h=>h.textContent.trim()).filter(Boolean); }
  const h1Status = h1s.length===0?'red':h1s.length>1?'amber':'green';
  checks.push({id:'T5',name:'H1-Überschrift',status:h1Status,
    finding:h1s.length===0?'Kein <h1>-Tag auf der Seite.':h1s.length===1?`H1 vorhanden: "${h1s[0].length>80?h1s[0].substring(0,80)+'…':h1s[0]}"`:h1s.length+` H1-Tags gefunden (sollte genau 1 sein): "${h1s[0].length>50?h1s[0].substring(0,50)+'…':h1s[0]}"…`,
    detail:h1s.length===0?'Die H1 ist das wichtigste On-Page-Keyword-Signal nach dem Title-Tag.':h1s.length>1?'Mehrere H1-Tags verwässern das Keyword-Signal. Google kann selbst entscheiden, welches die "echte" H1 ist.':'',
    fix:h1s.length===0?'<h1>Keyword-relevante Hauptüberschrift</h1> ergänzen.':h1s.length>1?`Nur eine H1 behalten, restliche zu H2/H3 herabstufen.`:''});

  // ── T6: Bilder ohne Alt-Text ─────────────────────────
  let imgTotal=0, imgMissingAlt=0, imgExamples=[];
  if(doc){
    const imgs=Array.from(doc.querySelectorAll('img'));
    imgTotal=imgs.length;
    imgs.forEach(img=>{
      const alt=img.getAttribute('alt');
      const src=(img.getAttribute('src')||'');
      if(alt===null||alt.trim()===''){
        imgMissingAlt++;
        if(imgExamples.length<3&&src){ const short=src.split('/').pop().split('?')[0]; if(short.length>2)imgExamples.push(short); }
      }
    });
  }
  const altStatus = imgTotal===0?'green':imgMissingAlt===0?'green':imgMissingAlt<=2?'amber':'red';
  checks.push({id:'T6',name:'Bild Alt-Texte',status:altStatus,
    finding:imgTotal===0?'Keine Bilder auf der Seite.':imgMissingAlt===0?`Alle ${imgTotal} Bilder haben Alt-Text.`:imgMissingAlt===1?`1 von ${imgTotal} Bildern ohne Alt-Text.`:`${imgMissingAlt} von ${imgTotal} Bildern ohne Alt-Text.`,
    detail:imgMissingAlt>0?`Alt-Texte fehlen bei: ${imgExamples.length?imgExamples.join(', '):'(keine src ermittelbar)'}. Relevant für Bild-SEO und Barrierefreiheit.`:'',
    fix:imgMissingAlt>0?'alt="Beschreibung des Bildinhalts" bei jedem inhaltlichen Bild ergänzen. Dekorative Bilder: alt=""':''});

  // ── T7: Open Graph Tags ──────────────────────────────
  let ogTitle='', ogDesc='', ogImage='';
  if(doc){
    ogTitle=(doc.querySelector('meta[property="og:title"]')?.getAttribute('content')||'').trim();
    ogDesc=(doc.querySelector('meta[property="og:description"]')?.getAttribute('content')||'').trim();
    ogImage=(doc.querySelector('meta[property="og:image"]')?.getAttribute('content')||'').trim();
  }
  const ogCount=[ogTitle,ogDesc,ogImage].filter(Boolean).length;
  const ogStatus = ogCount===3?'green':ogCount>=1?'amber':'amber';
  checks.push({id:'T7',name:'Open Graph Tags',status:ogStatus,
    finding:ogCount===3?'og:title, og:description und og:image vorhanden.':ogCount===0?'Keine Open Graph Tags gefunden (og:title, og:description, og:image).':`Nur ${ogCount}/3 OG-Tags vorhanden (${[ogTitle?'og:title':'',ogDesc?'og:description':'',ogImage?'og:image':''].filter(Boolean).join(', ')}).`,
    detail:ogCount<3?'Open Graph Tags steuern die Vorschau beim Teilen in sozialen Medien und Messaging-Apps. Kein Einfluss auf Google-Ranking.':'',
    fix:ogCount<3?`Fehlende Tags ergänzen:${!ogTitle?' <meta property="og:title" content="…">':''}${!ogDesc?' <meta property="og:description" content="…">':''}${!ogImage?' <meta property="og:image" content="[absoluter URL]">':''}`:''});

  // ── T8: URL-Struktur ──────────────────────────────────
  let urlOk=true, urlIssue='';
  try{
    const u=new URL(url||'https://example.com/');
    const path=u.pathname;
    const params=[...u.searchParams.keys()];
    const hasId=/\/[a-f0-9]{20,}|\/\d{6,}/.test(path);
    const hasCryptic=/[?&](sid|session|tok|hash|id)=/i.test(u.search);
    if(hasId||hasCryptic){urlOk=false;urlIssue='URL enthält kryptische IDs oder Session-Parameter.';}
    else if(params.length>3){urlOk=false;urlIssue=`URL hat ${params.length} Query-Parameter — könnte Duplicate Content erzeugen.`;}
    else if(path.split('/').some(s=>s.length>60)){urlOk=false;urlIssue='Ein URL-Segment ist sehr lang (>60 Zeichen).';}
  }catch(e){urlOk=false;urlIssue='URL konnte nicht geparst werden.';}
  checks.push({id:'T8',name:'URL-Struktur',status:urlOk?'green':'amber',
    finding:urlOk?`URL ist sauber und beschreibend: ${(url||'').length>70?(url||'').substring(0,70)+'…':url||''}`:urlIssue,
    detail:urlOk?'':'Nutzer können anhand der URL nicht den Seiteninhalt ableiten.',
    fix:urlOk?'':'Sprechende URL-Segmente verwenden, Session-Parameter serverseitig verarbeiten.'});

  // ── T9: HTTPS ─────────────────────────────────────────
  const isHttps=(url||'').startsWith('https://');
  checks.push({id:'T9',name:'HTTPS',status:isHttps?'green':'red',
    finding:isHttps?'Seite wird über HTTPS ausgeliefert.':'Seite läuft über HTTP — keine verschlüsselte Verbindung.',
    detail:isHttps?'':'Google nutzt HTTPS als leichtes Ranking-Signal. Browser kennzeichnen HTTP-Seiten als "nicht sicher".',
    fix:isHttps?'':'SSL-Zertifikat einrichten und HTTP→HTTPS-Weiterleitung aktivieren.'});

  // ── T10: Core Web Vitals (aus PageSpeed) ─────────────
  if(psi?.success){
    const lcp=parseFloat(psi.lcp)||0;
    const cls=parseFloat(psi.cls)||0;
    const tbt=parseFloat(psi.tbt)||0;
    const lcpStatus=lcp<=2.5?'green':lcp<=4?'amber':'red';
    const clsStatus=cls<=0.1?'green':cls<=0.25?'amber':'red';
    const tbtStatus=tbt<=200?'green':tbt<=600?'amber':'red';
    const overallCwv=[lcpStatus,clsStatus,tbtStatus];
    const cwvStatus=overallCwv.includes('red')?'red':overallCwv.includes('amber')?'amber':'green';
    checks.push({id:'T10',name:'Core Web Vitals (Mobile)',status:cwvStatus,
      finding:`LCP: ${psi.lcp} · CLS: ${psi.cls} · TBT: ${psi.tbt} · PageSpeed-Score: ${psi.perf_score}/100`,
      detail:cwvStatus!=='green'?`${lcp>2.5?'LCP über 2,5s (langsamer Largest Contentful Paint). ':''}`+`${cls>0.1?'CLS über 0,1 (Layout-Verschiebungen sichtbar). ':''}`+`${tbt>200?'TBT über 200ms (Hauptthread blockiert). ':''}`.trim():'',
      fix:cwvStatus!=='green'?'PageSpeed Insights für konkrete Optimierungshinweise nutzen (https://pagespeed.web.dev/).':''});
    const mobileScore=psi.perf_score||0;
    checks.push({id:'T11',name:'Mobile PageSpeed-Score',status:mobileScore>=90?'green':mobileScore>=50?'amber':'red',
      finding:`Mobile Score: ${mobileScore}/100 — ${mobileScore>=90?'Sehr gut':mobileScore>=50?'Verbesserungsbedarf':'Kritisch'}`,
      detail:'',fix:mobileScore<90?'Bilder komprimieren (WebP), Render-blocking JS/CSS vermeiden, Server-Response-Time reduzieren.':''});
  }

  // ── T12: In Sitemap enthalten ─────────────────────────
  const isUrlMode = url && !url.startsWith('(');
  if(sitemap && isUrlMode){
    if(sitemap.is_index){
      checks.push({id:'T12',name:'In Sitemap enthalten',status:'amber',
        finding:`Sitemap-Index gefunden (${sitemap.sub_count} Sub-Sitemaps). LP-URL in Sub-Sitemaps nicht automatisch prüfbar.`,
        detail:'Der Server liefert eine Sitemap-Index-Datei, die auf weitere Sitemaps verweist. Ein tiefes Crawling würde zu viele Requests erfordern.',
        fix:'Manuell prüfen: Ist die LP-URL in einer der verlinkten Sub-Sitemaps enthalten?'});
    } else {
      checks.push({id:'T12',name:'In Sitemap enthalten',status:sitemap.found?'green':'red',
        finding:sitemap.found
          ?`LP-URL ist in der Sitemap (${sitemap.loc_count} URLs geprüft) enthalten.`
          :`LP-URL nicht in sitemap.xml gefunden (${sitemap.loc_count} URLs geprüft).`,
        detail:sitemap.found?'':"Seiten ohne Sitemap-Eintrag werden von Google möglicherweise seltener gecrawlt.",
        fix:sitemap.found?'':'URL zur Sitemap hinzufügen und Sitemap in der Google Search Console einreichen.'});
    }
  } else if(isUrlMode){
    checks.push({id:'T12',name:'In Sitemap enthalten',status:'amber',
      finding:'sitemap.xml konnte nicht abgerufen werden.',
      detail:'Mögliche Ursache: keine /sitemap.xml vorhanden, Server-Fehler oder Zugriffsblockierung.',
      fix:'Prüfen ob eine sitemap.xml unter der Root-Domain existiert und die LP-URL enthält.'});
  }

  // ── T13: Viewport Meta Tag ────────────────────────────
  let hasViewport=false;
  if(doc){ hasViewport=!!doc.querySelector('meta[name="viewport"],meta[name="VIEWPORT"]'); }
  checks.push({id:'T13',name:'Viewport Meta Tag',status:hasViewport?'green':'red',
    finding:hasViewport?'Viewport Meta Tag vorhanden.':'Kein <meta name="viewport"> gefunden.',
    detail:hasViewport?'':'Ohne Viewport-Meta skaliert der Browser die Desktop-Ansicht auf Mobile — Google bewertet dies als mobilunfreundlich.',
    fix:hasViewport?'':'<meta name="viewport" content="width=device-width, initial-scale=1"> im <head> ergänzen.'});

  // ── T14: Structured Data / Schema.org ────────────────
  let schemaTypes=[];
  if(doc){
    Array.from(doc.querySelectorAll('script[type="application/ld+json"]')).forEach(s=>{
      try{const p=JSON.parse(s.textContent);const t=p['@type']||(Array.isArray(p)&&p[0]?p[0]['@type']:null);if(t)schemaTypes.push(t);}catch(e){}
    });
    if(!schemaTypes.length){
      Array.from(doc.querySelectorAll('[itemtype]')).forEach(el=>{const t=(el.getAttribute('itemtype')||'').split('/').pop();if(t&&!schemaTypes.includes(t))schemaTypes.push(t);});
    }
  }
  checks.push({id:'T14',name:'Structured Data (Schema.org)',status:schemaTypes.length?'green':'amber',
    finding:schemaTypes.length?`Schema Markup gefunden: ${schemaTypes.slice(0,3).join(', ')}${schemaTypes.length>3?' + '+(schemaTypes.length-3)+' weitere':''}` :'Kein Schema.org Markup (JSON-LD oder Microdata) gefunden.',
    detail:schemaTypes.length?'':'Structured Data ermöglicht Rich Results in der SERP (Bewertungen, FAQ, Breadcrumb etc.).',
    fix:schemaTypes.length?'':'JSON-LD Markup ergänzen (z.B. WebPage, Product, Article, FAQPage, BreadcrumbList).'});

  // ── T15: Heading-Hierarchie ──────────────────────────
  let h2Count=0,h3Count=0,headingIssue='';
  if(doc){
    h2Count=doc.querySelectorAll('h2').length;
    h3Count=doc.querySelectorAll('h3').length;
    if(h3Count>0&&h2Count===0) headingIssue='H3-Tags vorhanden, aber keine H2 (Hierarchiesprung H1→H3).';
    else if(h2Count===0&&h1s.length>0) headingIssue='Keine H2-Überschriften — fehlende Inhaltsstruktur.';
  }
  checks.push({id:'T15',name:'Heading-Hierarchie',status:headingIssue?'amber':'green',
    finding:headingIssue||`Hierarchie korrekt: H2: ${h2Count} · H3: ${h3Count}`,
    detail:headingIssue?'Korrekte Heading-Hierarchie hilft Google, die Inhaltsstruktur und Abschnitte zu verstehen.':'',
    fix:headingIssue?'Inhalte in logische Abschnitte mit H2/H3 gliedern.':''});

  // ── T16: Twitter Card Tags ────────────────────────────
  let twCard='';
  if(doc){ twCard=(doc.querySelector('meta[name="twitter:card"]')?.getAttribute('content')||'').trim(); }
  checks.push({id:'T16',name:'Twitter Card Tags',status:twCard?'green':'amber',
    finding:twCard?`twitter:card vorhanden: "${twCard}"`:'Kein <meta name="twitter:card"> gefunden.',
    detail:twCard?'':'Twitter/X Card Tags steuern die Link-Vorschau beim Teilen auf Twitter/X.',
    fix:twCard?'':'<meta name="twitter:card" content="summary_large_image"> im <head> ergänzen.'});

  // ── T17: Desktop PageSpeed-Score ─────────────────────
  if(psiDesktop?.success){
    const deskScore=psiDesktop.perf_score||0;
    checks.push({id:'T17',name:'Desktop PageSpeed-Score',status:deskScore>=90?'green':deskScore>=50?'amber':'red',
      finding:`Desktop Score: ${deskScore}/100 — ${deskScore>=90?'Sehr gut':deskScore>=50?'Verbesserungsbedarf':'Kritisch'} · LCP: ${psiDesktop.lcp||'–'} · CLS: ${psiDesktop.cls||'–'}`,
      detail:'',
      fix:deskScore<90?'Render-blocking Ressourcen entfernen, Bilder optimieren, Server-Antwortzeit reduzieren.':''});
  }

  // ── T18: INP (Interaction to Next Paint) ─────────────
  const inpVal=psiDesktop?.inp||psi?.inp||null;
  if(inpVal){
    const inpMs=parseFloat(inpVal)||0;
    checks.push({id:'T18',name:'Interaction to Next Paint (INP)',status:inpMs<=200?'green':inpMs<=500?'amber':'red',
      finding:`INP: ${inpVal} — ${inpMs<=200?'Gut (≤200ms)':inpMs<=500?'Verbesserungsbedarf (≤500ms)':'Kritisch (>500ms)'}`,
      detail:inpMs>200?'INP misst die Reaktionszeit auf Nutzerinteraktionen (Klicks, Formulare, Dropdowns). Zielwert: ≤200ms.':'',
      fix:inpMs>200?'Schwere JavaScript-Ausführung reduzieren, Long Tasks aufteilen, Event-Handler optimieren.':''});
  }

  // ── T19: Cross-Domain Canonical ───────────────────────
  if(canonicalOk&&canonical&&url){
    try{
      const canonOrigin=new URL(canonical).origin;
      const pageOrigin=new URL(url).origin;
      if(canonOrigin!==pageOrigin){
        checks.push({id:'T19',name:'Cross-Domain Canonical',status:'red',
          finding:`Canonical zeigt auf andere Domain: ${canonOrigin} (Seite: ${pageOrigin})`,
          detail:'Ein Cross-Domain-Canonical übergibt den gesamten Link-Wert an eine fremde Domain und macht diese Seite für Google unsichtbar.',
          fix:'Canonical auf die eigene Domain zeigen lassen — oder absichtliche Cross-Domain-Kanonisierung bewusst prüfen.'});
      }
    }catch(e){}
  }

  // ── T20: Render-blocking Scripts ─────────────────────
  let blockingScripts=0,blockingExamples=[];
  if(doc){
    Array.from(doc.querySelectorAll('head script[src]')).forEach(s=>{
      if(!s.hasAttribute('defer')&&!s.hasAttribute('async')){
        blockingScripts++;
        const src=(s.getAttribute('src')||'').split('/').pop().split('?')[0];
        if(blockingExamples.length<3&&src.length>2)blockingExamples.push(src);
      }
    });
  }
  checks.push({id:'T20',name:'Render-blocking Scripts',status:blockingScripts===0?'green':blockingScripts<=2?'amber':'red',
    finding:blockingScripts===0?'Keine render-blocking Scripts im <head> gefunden.'
      :blockingScripts===1?`1 render-blocking Script im <head>: ${blockingExamples.join(', ')}`
      :`${blockingScripts} render-blocking Scripts im <head>: ${blockingExamples.join(', ')}${blockingScripts>3?' u.a.':''}`,
    detail:blockingScripts>0?'Scripts ohne defer/async im <head> blockieren das Browser-Rendering bis sie vollständig geladen sind.':'',
    fix:blockingScripts>0?'defer oder async Attribut zu <script src="…">-Tags im <head> hinzufügen.':''});

  // ── T21: Lazy Loading (Bilder) ────────────────────────
  let lazyMissing=0,lazyTotal=0;
  if(doc){
    const allImgs=Array.from(doc.querySelectorAll('img'));
    const belowFold=allImgs.slice(2); // Erste 2 Bilder (Hero/LCP) ausnehmen
    lazyTotal=belowFold.length;
    belowFold.forEach(img=>{ if(img.getAttribute('loading')!=='lazy')lazyMissing++; });
  }
  const lazyStatus=lazyTotal===0?'green':lazyMissing===0?'green':lazyMissing<=Math.ceil(lazyTotal*0.4)?'amber':'red';
  checks.push({id:'T21',name:'Lazy Loading (Bilder)',status:lazyStatus,
    finding:lazyTotal===0?'Wenige Bilder — Lazy Loading nicht relevant.'
      :lazyMissing===0?`Alle ${lazyTotal} Bilder (ab Position 3) haben loading="lazy".`
      :`${lazyMissing} von ${lazyTotal} Bildern ohne loading="lazy" (ab Bild 3).`,
    detail:lazyMissing>0?'Lazy Loading verhindert das sofortige Laden von Off-Screen-Bildern — verbessert LCP und PageSpeed.':'',
    fix:lazyMissing>0?'loading="lazy" zu allen Bildern hinzufügen, die nicht im initialen Viewport sind. Hero-Bild NICHT lazy laden.':''});

  // ── T22: Moderne Bildformate (WebP/AVIF) ─────────────
  let modernFormatFound=false,imgCount22=0;
  if(doc){
    imgCount22=doc.querySelectorAll('img').length;
    const allSrcs=[
      ...Array.from(doc.querySelectorAll('img[src]')).map(i=>i.getAttribute('src')||''),
      ...Array.from(doc.querySelectorAll('source[srcset]')).map(s=>s.getAttribute('srcset')||''),
    ];
    modernFormatFound=allSrcs.some(s=>/\.(webp|avif)/i.test(s));
  }
  checks.push({id:'T22',name:'Moderne Bildformate (WebP/AVIF)',status:imgCount22===0?'green':modernFormatFound?'green':'amber',
    finding:imgCount22===0?'Keine Bilder gefunden.'
      :modernFormatFound?'WebP oder AVIF Bildformat erkannt.'
      :'Keine WebP- oder AVIF-Bilder gefunden — nur klassische Formate (JPEG/PNG).',
    detail:!modernFormatFound&&imgCount22>0?'WebP ist ~30% kleiner als JPEG bei gleicher Qualität. AVIF nochmals kompakter.':'',
    fix:!modernFormatFound&&imgCount22>0?'Bilder in WebP konvertieren. Mit <picture>-Element Fallback auf JPEG für ältere Browser bereitstellen.':''});

  // ── T23: Mixed Content (HTTP-Ressourcen) ─────────────
  let mixedCount=0,mixedExamples=[];
  if(doc&&(url||'').startsWith('https://')){
    [['img','src'],['script','src'],['link','href'],['iframe','src']].forEach(([sel,attr])=>{
      Array.from(doc.querySelectorAll(`${sel}[${attr}]`)).forEach(el=>{
        const val=el.getAttribute(attr)||'';
        if(val.startsWith('http://')){
          mixedCount++;
          const host=val.replace('http://','').split('/')[0];
          if(mixedExamples.length<3&&host.length>2)mixedExamples.push(host);
        }
      });
    });
  }
  const mixedStatus=!(url||'').startsWith('https://')?'green':mixedCount===0?'green':mixedCount<=2?'amber':'red';
  checks.push({id:'T23',name:'Mixed Content (HTTP-Ressourcen)',status:mixedStatus,
    finding:!(url||'').startsWith('https://')?'HTTP-Seite — Mixed Content nicht relevant.'
      :mixedCount===0?'Keine HTTP-Ressourcen auf HTTPS-Seite.'
      :`${mixedCount} HTTP-Ressource${mixedCount>1?'n':''} auf HTTPS-Seite: ${mixedExamples.join(', ')}${mixedCount>3?' u.a.':''}`,
    detail:mixedCount>0?'Browser blockieren aktive Mixed Content (Scripts, CSS). Passive Mixed Content (Bilder) erzeugt Sicherheitswarnungen.':'',
    fix:mixedCount>0?'Alle Ressourcen auf HTTPS umstellen. Relative Pfade (/pfad/zur/datei) oder protocol-relative URLs (//) verwenden.':''});

  // ── T24: Generische Anchor-Texte ──────────────────────
  let genericAnchors=0,genericExamples=[];
  if(doc){
    const genericRe=/^(hier|hier klicken|klicken|click here|mehr|mehr erfahren|lesen sie mehr|read more|weiterlesen|weiter|details|details anzeigen|öffnen|anzeigen|jetzt|link|seite)$/i;
    Array.from(doc.querySelectorAll('a[href]')).forEach(a=>{
      const text=(a.textContent||'').trim();
      if(genericRe.test(text)){
        genericAnchors++;
        if(genericExamples.length<4&&!genericExamples.includes('"'+text.toLowerCase()+'"'))genericExamples.push('"'+text+'"');
      }
    });
  }
  checks.push({id:'T24',name:'Anchor-Texte (Qualität)',status:genericAnchors===0?'green':genericAnchors<=3?'amber':'red',
    finding:genericAnchors===0?'Keine generischen Anchor-Texte gefunden.'
      :`${genericAnchors} generische${genericAnchors>1?' Anchor-Texte':' Anchor-Text'} erkannt: ${genericExamples.join(', ')}`,
    detail:genericAnchors>0?'Generische Texte wie "hier klicken" geben Google kein Signal über die verlinkte Seite.':'',
    fix:genericAnchors>0?'Anchor-Text durch beschreibenden Text ersetzen, der den Inhalt der Zielseite erklärt.':''});

  // ── T25: Übermäßige Link-Anzahl ───────────────────────
  let linkCount=0;
  if(doc){ linkCount=doc.querySelectorAll('a[href]').length; }
  checks.push({id:'T25',name:'Link-Anzahl (gesamt)',status:linkCount<=100?'green':linkCount<=150?'amber':'red',
    finding:linkCount===0?'Keine Links gefunden.'
      :linkCount<=100?`${linkCount} Links — unauffällig.`
      :linkCount<=150?`${linkCount} Links — im oberen Bereich, Mega-Menüs und Footer prüfen.`
      :`${linkCount} Links — sehr viele, Link-Equity je Link wird verwässert.`,
    detail:linkCount>150?'Sehr viele Links auf einer Seite verteilen den PageRank auf viele Ziele. Mega-Menüs und Footer-Links sind häufige Ursache.':'',
    fix:linkCount>150?'Mega-Menüs, Footer-Links und redundante Navigationselemente reduzieren.':''});

  return checks;
}

function renderTechnicalSeo(){
  const checks = runTechnicalSeo(currentHtml, currentUrl, psiData, sitemapData, psiDesktopData);

  const g=checks.filter(c=>c.status==='green').length;
  const a=checks.filter(c=>c.status==='amber').length;
  const r=checks.filter(c=>c.status==='red').length;
  const total=checks.length;
  const score=Math.round((g*100+a*50)/total);
  const cls=score>=70?'green':score>=45?'amber':'red';
  const levelLabel=score>=90?'Technisch exzellent':score>=75?'Technisch solide':score>=60?'Ausreichend':score>=45?'Verbesserungsbedarf':'Kritische Probleme';
  const interpMap={
    green:'Die Seite erfüllt die technischen SEO-Grundanforderungen. Kleinere Optimierungen möglich.',
    amber:'Es bestehen technische Schwächen, die sich auf Crawlbarkeit, Ladezeit oder Darstellung auswirken können.',
    red:'Kritische technische Probleme gefunden — diese können Rankings und Indexierung direkt beeinträchtigen.',
  };

  // ── Score Hero ────────────────────────────────────────
  const numEl=document.getElementById('tech-score-num');
  const levelEl=document.getElementById('tech-score-level');
  const interpEl=document.getElementById('tech-score-interp');
  const barEl=document.getElementById('tech-score-bar');
  const cntG=document.getElementById('tech-cnt-g');
  const cntA=document.getElementById('tech-cnt-a');
  const cntR=document.getElementById('tech-cnt-r');
  const cntT=document.getElementById('tech-cnt-total');
  const chipG=document.getElementById('tech-chip-g');
  const chipA=document.getElementById('tech-chip-a');
  const chipR=document.getElementById('tech-chip-r');
  if(numEl){numEl.textContent=score+'%';numEl.className='score-hero-num '+cls;}
  if(levelEl){levelEl.textContent=levelLabel;levelEl.className='score-hero-level '+cls;}
  if(interpEl)interpEl.textContent=interpMap[cls]||'';
  if(barEl){barEl.style.width=score+'%';barEl.className='score-hero-bar '+cls;}
  if(cntG)cntG.textContent=total>0?Math.round(g/total*100)+'%':'0%';
  if(cntA)cntA.textContent=total>0?Math.round(a/total*100)+'%':'0%';
  if(cntR)cntR.textContent=total>0?Math.round(r/total*100)+'%':'0%';
  if(cntT)cntT.textContent=total;
  if(chipG)chipG.className='score-chip '+(g>0?'green':'');
  if(chipA)chipA.className='score-chip '+(a>0?'amber':'');
  if(chipR)chipR.className='score-chip '+(r>0?'red':'');

  // ── Executive Summary (deterministisch) ───────────────
  const esCard=document.getElementById('tech-exec-summary');
  const esContent=document.getElementById('tech-exec-summary-content');
  if(esCard&&esContent){
    const redChecks=checks.filter(c=>c.status==='red');
    const amberChecks=checks.filter(c=>c.status==='amber');
    const topIssues=[...redChecks,...amberChecks].slice(0,4);
    const topFixes=[...redChecks,...amberChecks].filter(c=>c.fix).slice(0,4);
    let html=`<div class="exec-summary-grid">`;
    // Linke Spalte — Bewertung
    html+=`<div class="exec-summary-section">
      <div class="exec-summary-section-title">Bewertung</div>
      <div class="exec-summary-score">${score}% — ${levelLabel}</div>
      <div class="exec-summary-interpretation">${escHtml(interpMap[cls]||'')}`;
    if(redChecks.length>0){
      html+=` ${redChecks.length} kritische${redChecks.length>1?'':''} Problem${redChecks.length>1?'e':''} gefunden.`;
    } else if(amberChecks.length>0){
      html+=` ${amberChecks.length} Prüfpunkt${amberChecks.length>1?'e':''} mit Optimierungsbedarf.`;
    } else {
      html+=` Alle Prüfpunkte bestanden — keine Aktion erforderlich.`;
    }
    html+=`</div></div>`;
    // Rechte Spalte — Top-Probleme
    html+=`<div class="exec-summary-section">
      <div class="exec-summary-section-title">Top-Probleme</div>`;
    if(topIssues.length){
      topIssues.forEach(c=>{
        const sym=c.status==='red'?'✗':'◑';
        const symColor=c.status==='red'?'var(--red)':'var(--amber)';
        html+=`<div class="exec-summary-problem">
          <div class="exec-summary-problem-label"><span style="color:${symColor};margin-right:5px">${sym}</span>${escHtml(c.id)} — ${escHtml(c.name)}</div>
          <div class="exec-summary-problem-arrow">${escHtml((c.finding||'').substring(0,120)+((c.finding||'').length>120?'…':''))}</div>
        </div>`;
      });
    } else {
      html+=`<div style="font-size:12px;color:var(--text3);padding:4px 0">Keine Probleme — alle Prüfpunkte grün.</div>`;
    }
    html+=`</div></div>`;
    // Nächste Schritte (volle Breite, max. 3, als horizontale Cards)
    if(topFixes.length){
      const stepItems=[...redChecks,...amberChecks].filter(c=>c.fix).slice(0,3);
      html+=`<div class="exec-summary-steps" style="grid-column:1/-1">
        <div class="exec-summary-section-title" style="margin-bottom:10px">Empfohlene nächste Schritte</div>
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px">`;
      stepItems.forEach((c,i)=>{
        html+=`<div style="display:flex;align-items:flex-start;gap:10px;background:var(--bg2);border-radius:var(--radius-sm);padding:10px 12px;border:1px solid var(--border)"><span class="exec-summary-num">${i+1}</span><span style="flex:1;font-size:12px;color:var(--text2);line-height:1.5"><strong>${escHtml(c.id)}:</strong> ${escHtml(c.fix||'')}</span></div>`;
      });
      html+=`</div></div>`;
    }
    esContent.innerHTML=html;
    esCard.style.display='block';
  }

  // ── Cluster-Cards ──────────────────────────────────────
  const clusterEl=document.getElementById('tech-cluster-overview');
  if(clusterEl){
    const clusters=[
      {id:'A',name:'Indexierbarkeit & Crawling',
        hint:{green:'Seite ist korrekt indexierbar und für Google erreichbar.',amber:'Kleinere Crawling-Probleme — sollten zeitnah behoben werden.',red:'Kritische Indexierungsprobleme — Google kann die Seite nicht korrekt erfassen.'},
        ids:['T1','T2','T8','T9','T12','T19']},
      {id:'B',name:'On-Page Meta & Markup',
        hint:{green:'Meta-Daten, Überschriften und Markup vollständig und korrekt.',amber:'Einzelne Meta-Elemente fehlen oder sind nicht optimal.',red:'Wichtige On-Page-Elemente fehlen — starker SEO-Impact.'},
        ids:['T3','T4','T5','T13','T14','T15','T7','T16']},
      {id:'C',name:'Bilder & Ressourcen',
        hint:{green:'Bilder optimiert und Ressourcen korrekt eingebunden.',amber:'Optimierungspotenzial bei Bild-SEO oder Ressourcen-Laden.',red:'Kritische Probleme mit Bild-Optimierung oder Mixed Content.'},
        ids:['T6','T20','T21','T22','T23']},
      {id:'D',name:'Performance & Core Web Vitals',
        hint:{green:'Sehr gute Ladeperformance auf Mobile und Desktop.',amber:'Performance-Probleme — LCP, INP oder Score unter Zielwert.',red:'Kritische Performance-Probleme — starker Einfluss auf Rankings und Conversions.'},
        ids:['T10','T11','T17','T18']},
      {id:'E',name:'Links & Seitenstruktur',
        hint:{green:'Interne Verlinkung und Seitenstruktur unauffällig.',amber:'Einzelne Auffälligkeiten bei Anchor-Texten oder Link-Menge.',red:'Strukturelle Link-Probleme — verbesserungswürdig.'},
        ids:['T24','T25']},
    ];
    const R=36,SW=10,CX=48,CY=48,circ=2*Math.PI*R;
    clusterEl.innerHTML=clusters.map(cl=>{
      const clChecks=checks.filter(c=>cl.ids.includes(c.id));
      if(!clChecks.length)return'';
      const cg=clChecks.filter(c=>c.status==='green').length;
      const ca=clChecks.filter(c=>c.status==='amber').length;
      const cr=clChecks.filter(c=>c.status==='red').length;
      const cscore=Math.round((cg*100+ca*50)/clChecks.length);
      const ccls=cscore>=70?'green':cscore>=45?'amber':'red';
      const color=ccls==='green'?'var(--green)':ccls==='amber'?'var(--amber)':'var(--red)';
      const dash=(cscore/100*circ).toFixed(1);
      const hint=cl.hint[ccls]||'';
      const cardId='tech-cluster-'+cl.id;
      const rows=clChecks.map(c=>{
        const sym=c.status==='green'?'✓':c.status==='amber'?'◑':'✗';
        return`<div class="cluster-crit-row">`
          +`<div class="cluster-crit-meta"><div class="status-dot ${c.status}">${sym}</div><div class="cluster-crit-id">${escHtml(c.id)}</div></div>`
          +`<div class="cluster-crit-main">`
          +`<div class="cluster-crit-name">${escHtml(c.name)}</div>`
          +(c.finding?`<div class="cluster-crit-finding">${escHtml(c.finding.substring(0,180)+(c.finding.length>180?'…':''))}</div>`:'')
          +(c.fix&&c.status!=='green'?`<div class="cluster-crit-improve">→ ${escHtml(c.fix.substring(0,180)+(c.fix.length>180?'…':''))}</div>`:'')
          +`</div></div>`;
      }).join('');
      return`<div class="cluster-card${cr>0?' open':''}" id="${cardId}">`
        +`<div class="cluster-card-header" role="button" aria-expanded="${cr>0?'true':'false'}" onclick="toggleCluster('${cardId}')">`
        +`<div class="cluster-card-donut"><svg width="96" height="96" viewBox="0 0 96 96">`
        +`<circle cx="${CX}" cy="${CY}" r="${R}" fill="none" stroke="var(--bg4)" stroke-width="${SW}"/>`
        +`<circle cx="${CX}" cy="${CY}" r="${R}" fill="none" stroke="${color}" stroke-width="${SW}" stroke-dasharray="${dash} ${circ.toFixed(1)}" stroke-linecap="round" transform="rotate(-90 ${CX} ${CY})"/>`
        +`<text x="${CX}" y="${CY}" text-anchor="middle" dominant-baseline="central" font-size="18" font-weight="700" fill="${color}" font-family="Inter,sans-serif">${cscore}%</text>`
        +`</svg></div>`
        +`<div class="cluster-card-info">`
        +`<div class="cluster-card-name">${escHtml(cl.name)}</div>`
        +`<div style="font-size:11px;color:var(--text3);margin-top:3px;margin-bottom:6px;font-style:italic;line-height:1.4">${escHtml(hint)}</div>`
        +`<div style="display:flex;gap:10px;font-size:12px">`
        +`<span style="color:var(--green)">✓ ${cg>0?Math.round(cg/clChecks.length*100)+'%':'0%'}</span>`
        +`<span style="color:var(--amber)">◑ ${ca>0?Math.round(ca/clChecks.length*100)+'%':'0%'}</span>`
        +`<span style="color:var(--red)">✗ ${cr>0?Math.round(cr/clChecks.length*100)+'%':'0%'}</span>`
        +`</div></div>`
        +`<svg class="cluster-card-toggle" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>`
        +`</div>`
        +`<div class="cluster-card-body">${rows}</div>`
        +`</div>`;
    }).join('');
  }

  // ── Modul-Kachel + Sidebar-Score ──────────────────────
  const mcEl=document.getElementById('mc-technical-score');
  const navEl=document.getElementById('nav-score-technical');
  const mcBar=document.getElementById('mc-technical-bar');
  const mcLabel=document.getElementById('mc-technical-label');
  const mcCard=document.getElementById('mc-technical');
  if(mcEl){mcEl.textContent=score+'%';mcEl.className='module-card-score '+cls;}
  if(navEl){navEl.textContent=score+'%';}
  if(mcBar){mcBar.style.width=score+'%';mcBar.className='module-card-bar '+cls;}
  if(mcLabel)mcLabel.textContent=levelLabel;
  if(mcCard){mcCard.classList.remove('mc-green','mc-amber','mc-red');mcCard.classList.add('mc-'+cls);}
}

function renderKeywordFit(){
  const el=document.getElementById('kw-intent-content');
  if(!el||!kwData?.results)return;
  document.getElementById('kw-intent-panel').style.display='block';
  const entries=Object.values(kwData.results).filter(Boolean);
  if(!entries.length){el.innerHTML='<p style="font-size:12px;color:var(--text3);margin:0">Keine Intent-Daten verfügbar.</p>';return;}

  const intentKeys=['commercial','transactional','informational','navigational'];
  const intentLabels={commercial:'Commercial',transactional:'Transactional',informational:'Informational',navigational:'Navigational'};
  const intentColors={commercial:'var(--accent)',transactional:'var(--green)',informational:'var(--amber)',navigational:'var(--text3)'};

  // Dominanten Page-Intent bestimmen
  const totals={informational:0,navigational:0,transactional:0,commercial:0};
  entries.forEach(e=>intentKeys.forEach(k=>{totals[k]+=(e[k]||0);}));
  const pageIntent=intentKeys.reduce((a,b)=>totals[a]>totals[b]?a:b);
  const intentScore=Math.round((totals[pageIntent]/entries.length)*100);

  let html=`<div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap">`;
  html+=`<div style="background:var(--bg3);border-radius:var(--radius-sm);padding:8px 14px;text-align:center"><div style="font-size:11px;color:var(--text3);margin-bottom:2px">Dominanter Intent</div><div style="font-size:16px;font-weight:700;color:${intentColors[pageIntent]}">${intentLabels[pageIntent]}</div></div>`;
  html+=`<div style="background:var(--bg3);border-radius:var(--radius-sm);padding:8px 14px;text-align:center"><div style="font-size:11px;color:var(--text3);margin-bottom:2px">Konsistenz</div><div style="font-size:16px;font-weight:700;color:var(--text)">${intentScore}%</div></div>`;
  html+=`<div style="background:var(--bg3);border-radius:var(--radius-sm);padding:8px 14px;text-align:center"><div style="font-size:11px;color:var(--text3);margin-bottom:2px">Keywords analysiert</div><div style="font-size:16px;font-weight:700;color:var(--text)">${entries.length}</div></div>`;
  html+=`</div>`;

  // Tabelle pro Keyword
  html+=`<table style="width:100%;font-size:12px;border-collapse:collapse">`;
  html+=`<thead><tr style="color:var(--text3);font-size:11px">`;
  html+=`<th style="text-align:left;padding:3px 8px 6px 0">Keyword</th>`;
  intentKeys.forEach(k=>html+=`<th style="text-align:right;padding:3px 6px" data-tip="${intentLabels[k]}: Anteil dieses Intents für das Keyword (0–100%)">${intentLabels[k]}</th>`);
  html+=`<th style="text-align:center;padding:3px 4px">Fit</th></tr></thead><tbody>`;

  entries.forEach(e=>{
    const dominant=intentKeys.reduce((a,b)=>e[a]>e[b]?a:b);
    const fit=dominant===pageIntent;
    const fitIcon=fit?`<span style="color:var(--green);font-weight:700">✓</span>`:`<span style="color:var(--amber);font-weight:600">~</span>`;
    html+=`<tr>`;
    html+=`<td style="padding:4px 8px 4px 0;font-weight:500">${escHtml(e.keyword)}</td>`;
    intentKeys.forEach(k=>{
      const pct=Math.round((e[k]||0)*100);
      const isMax=k===dominant;
      html+=`<td style="text-align:right;padding:4px 6px;color:${isMax?intentColors[k]:'var(--text3)'};font-weight:${isMax?'700':'400'}">${pct}%</td>`;
    });
    html+=`<td style="text-align:center;padding:4px">${fitIcon}</td>`;
    html+=`</tr>`;
  });
  html+=`</tbody></table>`;

  // Empfehlungen
  const mismatches=entries.filter(e=>intentKeys.reduce((a,b)=>e[a]>e[b]?a:b)!==pageIntent);
  if(mismatches.length){
    html+=`<div style="margin-top:16px;padding:12px;background:var(--bg3);border-radius:var(--radius-sm);border-left:3px solid var(--amber)">`;
    html+=`<div style="font-size:11px;font-weight:600;color:var(--amber);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Intent-Mismatch erkannt</div>`;
    html+=`<div style="font-size:12px;color:var(--text2)">${mismatches.length} von ${entries.length} Keywords haben einen abweichenden dominanten Intent. Überprüfe ob der Seiten-Content alle Intent-Typen adressiert oder das Targeting schärfer fokussiert werden sollte.</div>`;
    html+=`<div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px">`;
    mismatches.forEach(e=>{
      const d=intentKeys.reduce((a,b)=>e[a]>e[b]?a:b);
      html+=`<span style="font-size:11px;background:var(--bg4);border:1px solid var(--border);border-radius:4px;padding:3px 8px">${escHtml(e.keyword)} <span style="color:${intentColors[d]}">(${intentLabels[d]})</span></span>`;
    });
    html+=`</div></div>`;
  }else if(entries.length>1){
    html+=`<div style="margin-top:16px;padding:12px;background:var(--bg3);border-radius:var(--radius-sm);border-left:3px solid var(--green)">`;
    html+=`<div style="font-size:12px;color:var(--text2)">✓ Alle Keywords haben konsistenten Intent. Das Seiten-Targeting ist klar ausgerichtet.</div>`;
    html+=`</div>`;
  }

  el.innerHTML=html;
}

function toggleCluster(id){
  const card=document.getElementById(id);
  if(!card)return;
  const isOpen=card.classList.toggle('open');
  const btn=card.querySelector('.cluster-card-header');
  if(btn)btn.setAttribute('aria-expanded',isOpen?'true':'false');
}
function renderClusterOverview(){
  const el=document.getElementById('cluster-overview');
  if(!el)return;
  const clusters=[
    {num:'1',name:'Seitenzweck \u0026 Typ',
      hints:{green:'Seitenzweck klar kommuniziert und legitim.',amber:'Seitenzweck erkennbar, aber nicht durchg\u00e4ngig klar.',red:'Seitenzweck unklar oder schwer erkennbar.'}},
    {num:'2',name:'Inhalt \u0026 Tiefe',
      hints:{green:'Inhalt tiefgr\u00fcndig, original und vollst\u00e4ndig.',amber:'Inhaltstiefe vorhanden, aber ausbauf\u00e4hig.',red:'Inhalt oberfl\u00e4chlich, unvollst\u00e4ndig oder ohne erkennbaren Mehrwert.'}},
    {num:'3',name:'E-E-A-T',
      hints:{green:'Expertise und Vertrauenssignale gut belegt.',amber:'E-E-A-T-Signale erkennbar, aber nicht ausreichend nachgewiesen.',red:'Autorit\u00e4t und Vertrauen nicht oder kaum nachweisbar.'}},
    {num:'4',name:'Reputation',
      hints:{green:'Transparenz und Reputation gegeben.',amber:'Teilweise Transparenz- oder Reputationsl\u00fccken.',red:'Fehlende Transparenz oder schwache externe Reputation.'}},
    {num:'5',name:'Schaden \u0026 T\u00e4uschung',
      hints:{green:'Keine T\u00e4uschungs- oder Schadsignale erkennbar.',amber:'Leichte Auff\u00e4lligkeiten \u2014 weiter beobachten.',red:'Kritische T\u00e4uschungs- oder Schadenssignale vorhanden!'}},
    {num:'6',name:'Technik \u0026 UX',
      hints:{green:'Technische Qualit\u00e4t und UX auf gutem Niveau.',amber:'Technische M\u00e4ngel vorhanden, die Nutzer beeintr\u00e4chtigen.',red:'Schwerwiegende technische Probleme \u2014 dringend beheben.'}},
    {num:'7',name:'Werbung \u0026 SC',
      hints:{green:'Werbung klar gekennzeichnet und nicht aufdringlich.',amber:'Werbeintegration oder Supplementary Content ausbauf\u00e4hig.',red:'Werbung dominiert oder irref\u00fchrend platziert.'}},
    {num:'8',name:'Needs Met',
      hints:{green:'Suchabsicht vollst\u00e4ndig und befriedigend getroffen.',amber:'Suchabsicht teilweise getroffen \u2014 L\u00fccken schlie\u00dfen.',red:'Suchabsicht nicht erf\u00fcllt \u2014 zentrales Rankingproblem.'}},
  ];
  const R=36,SW=10,CX=48,CY=48;
  const circ=2*Math.PI*R;
  el.innerHTML=clusters.map(cl=>{
    const res=analysisResults.filter(r=>r.id.startsWith(cl.num+'.'));
    if(!res.length)return'';
    let tw=0,ts=0;
    res.forEach(r=>{const w=getEffectiveWeight(r.id);tw+=w;ts+=statusScore(r.status)*w});
    const score=tw>0?Math.round(ts/tw):0;
    const cls=score>=70?'green':score>=50?'amber':'red';
    const color=cls==='green'?'var(--green)':cls==='amber'?'var(--amber)':'var(--red)';
    const dash=(score/100*circ).toFixed(1);
    const g=res.filter(r=>r.status==='green').length;
    const a=res.filter(r=>r.status==='amber').length;
    const rd=res.filter(r=>r.status==='red').length;
    const hint=cl.hints[cls]||'';
    const cardId='cluster-card-'+cl.num;
    // Kriterien-Rows
    const rows=res.map(r=>{
      const crit=CRITERIA.find(c=>c.id===r.id)||{name:r.criterion||r.id};
      const sym=r.status==='green'?'\u2713':r.status==='amber'?'\u25d1':'\u2717';
      const parts=(r.finding||'').split('|');
      const verdict=(parts[2]||parts[0]||'').replace(/^Bewertung:\s*/,'').trim();
      const improve=r.improvement||'';
      return`<div class="cluster-crit-row">`
        +`<div class="cluster-crit-meta"><div class="status-dot ${r.status}">${sym}</div><div class="cluster-crit-id">SQ${escHtml(r.id)}</div></div>`
        +`<div class="cluster-crit-main">`
        +`<div class="cluster-crit-name">${escHtml(crit.name)}</div>`
        +(verdict?`<div class="cluster-crit-finding">${escHtml(verdict.substring(0,160)+(verdict.length>160?'\u2026':''))}</div>`:'')
        +(improve&&r.status!=='green'?`<div class="cluster-crit-improve">\u2192 ${escHtml(improve.substring(0,160)+(improve.length>160?'\u2026':''))}</div>`:'')
        +`</div></div>`;
    }).join('');
    return`<div class="cluster-card" id="${cardId}">`
      +`<div class="cluster-card-header" role="button" aria-expanded="false" onclick="toggleCluster('${cardId}')">`
      +`<div class="cluster-card-donut"><svg width="96" height="96" viewBox="0 0 96 96">`
      +`<circle cx="${CX}" cy="${CY}" r="${R}" fill="none" stroke="var(--bg4)" stroke-width="${SW}"/>`
      +`<circle cx="${CX}" cy="${CY}" r="${R}" fill="none" stroke="${color}" stroke-width="${SW}" stroke-dasharray="${dash} ${circ.toFixed(1)}" stroke-linecap="round" transform="rotate(-90 ${CX} ${CY})"/>`
      +`<text x="${CX}" y="${CY}" text-anchor="middle" dominant-baseline="central" font-size="18" font-weight="700" fill="${color}" font-family="Inter,sans-serif">${score}%</text>`
      +`</svg></div>`
      +`<div class="cluster-card-info">`
      +`<div class="cluster-card-name">${escHtml(cl.name)}</div>`
      +`<div style="font-size:11px;color:var(--text3);margin-top:3px;margin-bottom:6px;font-style:italic;line-height:1.4">${escHtml(hint)}</div>`
      +`<div style="display:flex;gap:10px;font-size:12px">`
      +`<span style="color:var(--green)">\u2713 ${g>0?Math.round(g/res.length*100)+'%':'0%'}</span>`
      +`<span style="color:var(--amber)">\u25d1 ${a>0?Math.round(a/res.length*100)+'%':'0%'}</span>`
      +`<span style="color:var(--red)">\u2717 ${rd>0?Math.round(rd/res.length*100)+'%':'0%'}</span>`
      +`</div></div>`
      +`<svg class="cluster-card-toggle" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>`
      +`</div>`
      +`<div class="cluster-card-body">${rows}</div>`
      +`</div>`;
  }).join('');
}

function renderPriorityMatrix(){
  const s=document.getElementById('pri-sofort'),q=document.getElementById('pri-quick'),m=document.getElementById('pri-mid');
  s.innerHTML=q.innerHTML=m.innerHTML='';
  analysisResults.forEach(r=>{
    if(r.status==='green')return;
    const w=getEffectiveWeight(r.id);
    const crit=CRITERIA.find(c=>c.id===r.id)||{name:r.criterion||r.id};
    const name=crit.name.length>50?crit.name.substring(0,50)+'…':crit.name;
    const effort=w>=4?'Hoch':w>=3?'Mittel':'Niedrig';
    let col,dot;
    if(r.status==='red'&&w>=3){col=s;dot='red'}
    else if(r.status==='amber'&&w>=2||r.status==='red'&&w<3){col=q;dot='amber'}
    else{col=m;dot='blue'}
    col.innerHTML+=`<div class="priority-item"><div class="pri-dot ${dot}"></div><span>${escHtml(r.id+' · '+name)}</span><span class="effort-badge">${effort}</span></div>`;
  });
  if(!s.innerHTML)s.innerHTML='<div class="priority-item" style="color:var(--green)">✓ Keine kritischen Fehler</div>';
  if(!q.innerHTML)q.innerHTML='<div class="priority-item" style="color:var(--green)">✓ Keine Quick Wins nötig</div>';
  if(!m.innerHTML)m.innerHTML='<div class="priority-item" style="color:var(--text3)">–</div>';
}

let currentFilter='all';
function setFilter(filter,btn){
  currentFilter=filter;
  document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  renderCriteriaTable(analysisResults,filter);
}

function renderCriteriaTable(results,filter){
  const tbody=document.getElementById('criteria-tbody');
  let filtered=filter==='all'?results:results.filter(r=>r.status===filter);
  if(!filtered.length){tbody.innerHTML='<tr><td colspan="3" style="text-align:center;color:var(--text3);padding:24px">Keine Einträge für diesen Filter.</td></tr>';return}
  tbody.innerHTML=filtered.map((r,idx)=>{
    const crit=CRITERIA.find(c=>c.id===r.id)||{cat:'',name:r.criterion||r.id,ref:r.sqeg_ref||''};
    const sym=r.status==='green'?'✓':r.status==='amber'?'◑':'✗';
    const parts=(r.finding||'').split('|');
    const beleg=(parts[0]||'').replace(/^Beleg:\s*/,'').trim();
    const rule=(parts[1]||'').replace(/^Regel:\s*/,'').trim();
    const verdict=(parts[2]||'').replace(/^Bewertung:\s*/,'').trim();
    const imp=r.improvement?`<div class="suggest">💡 ${escHtml(r.improvement)}</div>`:'';
    const rowId='crit-row-'+idx;
    const detailId='crit-detail-'+idx;
    const mainRow=`<tr class="crit-row" id="${rowId}" onclick="toggleCritRow('${rowId}','${detailId}')">
      <td style="width:40px"><div class="status-dot ${r.status}">${sym}</div></td>
      <td><div class="crit-id">${escHtml(r.id)}</div><div class="crit-name">${escHtml(crit.name)}</div><div class="crit-cat">${escHtml(crit.cat)}</div></td>
      <td style="color:var(--text2);font-size:12px">${verdict?escHtml(verdict.substring(0,120))+(verdict.length>120?'…':''):''}</td>
      <td style="width:24px;padding-right:12px"><svg class="crit-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></td>
    </tr>`;
    const detailRow=`<tr class="crit-detail" id="${detailId}">
      <td colspan="4"><div class="crit-detail-inner">
        ${beleg?`<div class="crit-detail-row"><div class="crit-detail-label">Beleg</div>${escHtml(beleg)}</div>`:''}
        ${rule?`<div class="crit-detail-row"><div class="crit-detail-label">Regel</div><em>${escHtml(rule)}</em></div>`:''}
        ${verdict?`<div class="crit-detail-row"><div class="crit-detail-label">Bewertung</div><strong>${escHtml(verdict)}</strong></div>`:''}
        ${r.improvement?`<div class="crit-detail-row">${imp}</div>`:''}
        <div style="font-size:10px;color:var(--text3);font-family:'Geist Mono','Courier New',monospace">${escHtml(crit.ref||r.sqeg_ref||'')} · Gewicht: ${getEffectiveWeight(r.id)}</div>
      </div></td>
    </tr>`;
    return mainRow+detailRow;
  }).join('');
}
function toggleCritRow(rowId,detailId){
  const row=document.getElementById(rowId);
  const detail=document.getElementById(detailId);
  const open=row.classList.toggle('expanded');
  detail.classList.toggle('visible',open);
}

// === EXPORT ===
function exportHtml(){
  const score=calcScore();
  const hasLowestSignal=analysisResults.some(r=>getEffectiveWeight(r.id)>=4&&r.status==='red');
  const level=hasLowestSignal?'Lowest':scoreToLevel(score);
  const cluster5=analysisResults.filter(r=>r.id.startsWith('5.'));
  const html=`<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>SQEG Analyse – ${escHtml(currentUrl)}</title><style>body{font-family:sans-serif;max-width:900px;margin:40px auto;padding:0 20px;color:#1a1917}h1{font-size:22px}h2{font-size:16px;margin:24px 0 8px;border-bottom:1px solid #e3e2df;padding-bottom:6px}table{width:100%;border-collapse:collapse;margin-bottom:16px}th,td{text-align:left;padding:10px 12px;border:1px solid #e3e2df;font-size:13px}th{background:#f8f7f5;font-weight:700}.green{color:#15803d}.amber{color:#b45309}.red{color:#dc2626}.suggest{background:#f0f0ff;padding:6px 10px;border-left:3px solid #4338ca;margin-top:4px;font-size:12px}@media print{body{margin:0}}</style></head><body><h1>SQEG Analyse: ${escHtml(currentUrl)}</h1><p>Score: ${Math.round(score)}% · PQ-Stufe: ${escHtml(level)} · YMYL: ${escHtml(ymylResult||'none')} · ${new Date().toLocaleDateString('de-DE')}</p><h2>42 Kriterien (1.1–8.4) · SQEG September 2025</h2><table><thead><tr><th>ID</th><th>Cluster</th><th>Kriterium</th><th>Status</th><th>Befund</th><th>Verbesserung</th></tr></thead><tbody>${analysisResults.map(r=>{const crit=CRITERIA.find(c=>c.id===r.id)||{cat:'',name:r.criterion||r.id};return`<tr><td>${escHtml(r.id)}</td><td>${escHtml(crit.cat)}</td><td>${escHtml(crit.name)}</td><td class="${r.status}">${r.status}</td><td>${escHtml(r.finding||'')}</td><td>${r.improvement?`<div class="suggest">${escHtml(r.improvement)}</div>`:''}</td></tr>`}).join('')}</tbody></table>${cluster5.length?`<h2>Cluster 5 — Schaden &amp; Täuschung (Kritische Signale)</h2><table><thead><tr><th>ID</th><th>Name</th><th>Status</th><th>Befund</th></tr></thead><tbody>${cluster5.map(r=>{const crit=CRITERIA.find(c=>c.id===r.id)||{name:r.id};return`<tr><td>${escHtml(r.id)}</td><td>${escHtml(crit.name)}</td><td class="${r.status}">${r.status}</td><td>${escHtml(r.finding||'')}</td></tr>`}).join('')}</tbody></table>`:''}</body></html>`;
  const w=window.open('','_blank');w.document.write(html);w.document.close();
}

// === SETTINGS ===
function toggleSettingsPw(id,btn){
  const inp=document.getElementById(id);
  if(inp.type==='password'){inp.type='text';btn.textContent='Verbergen'}else{inp.type='password';btn.textContent='Anzeigen'}
}
async function saveApiKey(e){
  e.preventDefault();
  const key=document.getElementById('s-apikey').value.trim();
  const errEl=document.getElementById('err-apikey'),msgEl=document.getElementById('msg-apikey');
  errEl.style.display='none';msgEl.style.display='none';
  const fd=new FormData();fd.append('action','save_api_key');fd.append('anthropic_api_key',key);fd.append('csrf_token',CSRF_TOKEN);
  try{
    const r=await fetch('settings_save.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.error){errEl.textContent=d.error;errEl.style.display='flex'}
    else{msgEl.style.display='block';document.getElementById('key-masked-display').textContent='API-Key ist hinterlegt.';setTimeout(()=>msgEl.style.display='none',3000)}
  }catch(err){errEl.textContent=err.message;errEl.style.display='flex'}
}
async function saveModel(e){
  e.preventDefault();
  const fd=new FormData();fd.append('action','save_model');fd.append('ai_model',document.getElementById('s-model').value);fd.append('csrf_token',CSRF_TOKEN);
  const r=await fetch('settings_save.php',{method:'POST',body:fd});
  const d=await r.json();
  const msg=document.getElementById('msg-model');
  if(d.success){msg.style.display='block';setTimeout(()=>msg.style.display='none',3000)}
}
async function savePassword(e){
  e.preventDefault();
  const pw=document.getElementById('s-pw').value,pw2=document.getElementById('s-pw2').value;
  const errEl=document.getElementById('err-password'),msgEl=document.getElementById('msg-password');
  errEl.style.display='none';msgEl.style.display='none';
  if(pw.length<8){errEl.textContent='Passwort muss mindestens 8 Zeichen lang sein.';errEl.style.display='flex';return}
  if(pw!==pw2){errEl.textContent='Passwörter stimmen nicht überein.';errEl.style.display='flex';return}
  const fd=new FormData();fd.append('action','save_password');fd.append('new_password',pw);fd.append('confirm_password',pw2);fd.append('csrf_token',CSRF_TOKEN);
  try{
    const r=await fetch('settings_save.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.error){errEl.textContent=d.error;errEl.style.display='flex'}
    else{msgEl.style.display='block';document.getElementById('s-pw').value='';document.getElementById('s-pw2').value='';setTimeout(()=>msgEl.style.display='none',3000)}
  }catch(err){errEl.textContent=err.message;errEl.style.display='flex'}
}
// === CREDENTIAL STATUS ===
const CRED_LABELS={
  anthropic:{label:'Anthropic',badge:'src-badge-anthropic'},
  dataforseo:{label:'DataForSEO',badge:'src-badge-dataforseo'},
  sistrix:{label:'Sistrix',badge:'src-badge-sistrix'},
  pagespeed:{label:'PageSpeed',badge:'src-badge-pagespeed'},
  openai:{label:'OpenAI',badge:'src-badge-openai'},
  gsc:{label:'GSC',badge:'src-badge-gsc'},
};
async function loadCredentialStatus(){
  try{
    const r=await fetch('settings_save.php?action=status');
    if(!r.ok)return;
    const status=await r.json();
    // Status-Bar
    const bar=document.getElementById('cred-status-bar');
    if(bar){
      bar.innerHTML=Object.entries(CRED_LABELS).map(([key,meta])=>{
        const src=status[key]||'none';
        const cls=src==='env'?'ok':src==='json'?'ok':'miss';
        const srcLabel=src==='env'?'Railway ENV':src==='json'?'settings.json':'nicht konfiguriert';
        return`<span class="cred-status-chip ${cls}">${meta.label}: ${srcLabel}</span>`;
      }).join('');
    }
    // Badges + Felder sperren wenn ENV
    Object.entries(CRED_LABELS).forEach(([key,meta])=>{
      const src=status[key]||'none';
      const badgeEl=document.getElementById(meta.badge);
      if(badgeEl){
        badgeEl.className='src-badge '+(src==='env'?'env':src==='json'?'json':'none');
        badgeEl.textContent=src==='env'?'Railway ENV':src==='json'?'settings.json':'nicht konfiguriert';
      }
      // Eingabefelder und Speichern-Button deaktivieren wenn ENV aktiv
      const formMap={
        anthropic:['s-apikey','btn-save-anthropic'],
        dataforseo:['s-dfs-login','s-dfs-pw','btn-save-dataforseo'],
        sistrix:['s-sistrix','btn-save-sistrix'],
        pagespeed:['s-pagespeed','btn-save-pagespeed'],
        openai:['s-openai-key','btn-save-openai'],
        gsc:['s-gsc-url','s-gsc-json','btn-save-gsc-creds'],
      };
      if(src==='env'&&formMap[key]){
        formMap[key].forEach(id=>{
          const el=document.getElementById(id);
          if(el){el.disabled=true;if(el.tagName==='INPUT'||el.tagName==='TEXTAREA')el.placeholder='Konfiguriert über Railway ENV — hier nicht änderbar';}
        });
      }
    });
  }catch(e){console.error('loadCredentialStatus:',e);}
}
async function saveDataforSeo(e){
  e.preventDefault();
  const errEl=document.getElementById('err-dataforseo'),msgEl=document.getElementById('msg-dataforseo');
  errEl.style.display='none';msgEl.style.display='none';
  const fd=new FormData();fd.append('action','save_dataforseo');
  fd.append('dataforseo_login',document.getElementById('s-dfs-login').value.trim());
  fd.append('dataforseo_password',document.getElementById('s-dfs-pw').value.trim());
  fd.append('csrf_token',CSRF_TOKEN);
  try{
    const r=await fetch('settings_save.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.error){errEl.textContent=d.error;errEl.style.display='flex'}
    else{msgEl.style.display='block';setTimeout(()=>msgEl.style.display='none',3000)}
  }catch(err){errEl.textContent=err.message;errEl.style.display='flex'}
}
async function saveSistrix(e){
  e.preventDefault();
  const errEl=document.getElementById('err-sistrix'),msgEl=document.getElementById('msg-sistrix');
  errEl.style.display='none';msgEl.style.display='none';
  const fd=new FormData();fd.append('action','save_sistrix');
  fd.append('sistrix_api_key',document.getElementById('s-sistrix').value.trim());
  fd.append('csrf_token',CSRF_TOKEN);
  try{
    const r=await fetch('settings_save.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.error){errEl.textContent=d.error;errEl.style.display='flex'}
    else{msgEl.style.display='block';setTimeout(()=>msgEl.style.display='none',3000)}
  }catch(err){errEl.textContent=err.message;errEl.style.display='flex'}
}
async function savePageSpeed(e){
  e.preventDefault();
  const errEl=document.getElementById('err-pagespeed'),msgEl=document.getElementById('msg-pagespeed');
  errEl.style.display='none';msgEl.style.display='none';
  const fd=new FormData();fd.append('action','save_pagespeed');
  fd.append('pagespeed_api_key',document.getElementById('s-pagespeed').value.trim());
  fd.append('csrf_token',CSRF_TOKEN);
  try{
    const r=await fetch('settings_save.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.error){errEl.textContent=d.error;errEl.style.display='flex'}
    else{msgEl.style.display='block';setTimeout(()=>msgEl.style.display='none',3000)}
  }catch(err){errEl.textContent=err.message;errEl.style.display='flex'}
}
async function saveOpenAI(e){
  e.preventDefault();
  const errEl=document.getElementById('err-openai'),msgEl=document.getElementById('msg-openai');
  errEl.style.display='none';msgEl.style.display='none';
  const fd=new FormData();fd.append('action','save_openai');
  fd.append('openai_api_key',document.getElementById('s-openai-key').value.trim());
  fd.append('openai_model',document.getElementById('s-openai-model').value);
  fd.append('csrf_token',CSRF_TOKEN);
  try{
    const r=await fetch('settings_save.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.error){errEl.textContent=d.error;errEl.style.display='flex'}
    else{msgEl.style.display='block';setTimeout(()=>msgEl.style.display='none',3000)}
  }catch(err){errEl.textContent=err.message;errEl.style.display='flex'}
}
async function saveGscCreds(e){
  e.preventDefault();
  const errEl=document.getElementById('err-gsc-creds'),msgEl=document.getElementById('msg-gsc-creds');
  errEl.style.display='none';msgEl.style.display='none';
  const fd=new FormData();fd.append('action','save_gsc');
  fd.append('gsc_site_url',document.getElementById('s-gsc-url').value.trim());
  fd.append('gsc_service_account_json',document.getElementById('s-gsc-json').value.trim());
  fd.append('csrf_token',CSRF_TOKEN);
  try{
    const r=await fetch('settings_save.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.error){errEl.textContent=d.error;errEl.style.display='flex'}
    else{msgEl.style.display='block';setTimeout(()=>msgEl.style.display='none',3000)}
  }catch(err){errEl.textContent=err.message;errEl.style.display='flex'}
}
// === GSC DOMAIN MANAGEMENT ===
async function loadGscDomains(){
  const list=document.getElementById('gsc-domain-list');
  if(!list)return;
  try{
    const r=await fetch('gsc.php?action=list');
    const d=await r.json();
    const domains=d.domains||[];
    if(!domains.length){list.innerHTML='<div style="font-size:13px;color:var(--text3)">Keine Domains konfiguriert.</div>';return;}
    list.innerHTML=domains.map(dom=>`
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 12px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm)">
        <div>
          <span style="font-family:'Geist Mono',monospace;font-size:12px;color:var(--text)">${escHtml(dom.site_url||dom.domain)}</span>
          ${dom.sa_email?`<span style="font-size:11px;color:var(--text3);display:block;margin-top:2px">${escHtml(dom.sa_email)}</span>`:''}
          ${dom.source==='env'?'<span style="font-size:10px;color:var(--accent);margin-left:6px">(ENV)</span>':''}
        </div>
        ${dom.source!=='env'?`<button type="button" class="btn-secondary btn-sm" style="color:var(--red);flex-shrink:0" onclick="deleteGscDomain(${JSON.stringify(dom.id)})">Entfernen</button>`:''}
      </div>`).join('');
  }catch(e){console.error('loadGscDomains:',e);}
}
async function addGscDomain(e){
  e.preventDefault();
  const inp=document.getElementById('s-gsc-domain-new');
  const errEl=document.getElementById('err-gsc-domain');
  const url=inp.value.trim();
  errEl.style.display='none';
  if(!url){errEl.textContent='Bitte URL eingeben.';errEl.style.display='flex';return;}
  try{
    const domain=url.replace(/^(sc-domain:|https?:\/\/)/i,'').replace(/\/$/,'');
    const r=await fetch('gsc.php?action=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({domain,site_url:url})});
    const d=await r.json();
    if(d.error){errEl.textContent=d.error;errEl.style.display='flex';return;}
    inp.value='';
    await loadGscDomains();
  }catch(err){errEl.textContent=err.message;errEl.style.display='flex';}
}
async function deleteGscDomain(id){
  if(!confirm('Domain entfernen?'))return;
  try{
    await fetch('gsc.php?action=delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
    await loadGscDomains();
  }catch(e){console.error('deleteGscDomain:',e);}
}
// === API-VERBINDUNGSTESTS ===
async function testApiConn(name){
  const dot=document.getElementById('dot-'+name);
  const msg=document.getElementById('testmsg-'+name);
  if(!dot||!msg)return;
  dot.className='api-test-dot testing';msg.textContent='Teste\u2026';
  const urls={ai:'api.php?action=test',dataforseo:'dataforseo.php?action=test',gsc:'gsc.php?action=list',sistrix:'sistrix.php?action=test',pagespeed:'pagespeed.php?action=test'};
  try{
    const r=await fetch(urls[name]);
    const d=await r.json();
    let ok=false,detail='';
    if(name==='gsc'){
      ok=d.success&&d.domains?.length>0;
      detail=ok?`${d.domains.length} Property konfiguriert`:(d.error||'Keine Properties konfiguriert');
    }else{
      ok=d.success===true;
      if(ok){
        if(name==='dataforseo'&&d.balance!=null)detail=`Guthaben: $${parseFloat(d.balance).toFixed(2)}`;
        else if(name==='sistrix'&&d.remaining!=null)detail=`${d.remaining} Credits verbleibend`;
        else if(name==='ai'&&d.model)detail=`Modell: ${d.model}`;
        else detail='Verbunden';
      }else{detail=d.error||d.message||'Unbekannter Fehler';}
    }
    dot.className='api-test-dot '+(ok?'ok':'err');
    msg.textContent=(ok?'\u2713 ':'\u2717 ')+detail;
  }catch(e){dot.className='api-test-dot err';msg.textContent='\u2717 Netzwerkfehler: '+e.message;}
}
function testAllApis(){['ai','dataforseo','gsc','sistrix','pagespeed'].forEach(n=>testApiConn(n));}
// === THEME ===
function applyTheme(dark){
  document.documentElement.setAttribute('data-theme',dark?'dark':'');
  localStorage.setItem('lat_theme',dark?'dark':'light');
  const cb=document.getElementById('setting-dark-mode');
  if(cb)cb.checked=dark;
}
function toggleTheme(){
  applyTheme(document.documentElement.getAttribute('data-theme')!=='dark');
}
// === DEMO SETTING ===
function loadDemoSetting(){
  const enabled=localStorage.getItem('lat_demo_btn')!=='false';
  document.getElementById('btn-demo').style.display=enabled?'':'none';
  const cb=document.getElementById('setting-demo-btn');
  if(cb)cb.checked=enabled;
}
function saveDemoSetting(checked){
  localStorage.setItem('lat_demo_btn',checked?'true':'false');
  document.getElementById('btn-demo').style.display=checked?'':'none';
}
loadDemoSetting();
const _dmCb=document.getElementById('setting-dark-mode');
if(_dmCb)_dmCb.checked=document.documentElement.getAttribute('data-theme')==='dark';
// Initial view
showView('overview');

// ═══════════════════════════════════════════════════════════
// LOCAL PV GENERATOR
// ═══════════════════════════════════════════════════════════
let pvData = null;
let pvDwdData = null;
let pvVersions = { raw: null, sharpened: null, conversion: null };

function pvUpdateVersionUI(activeKey){
  const bar=document.getElementById('pv-version-bar');
  if(!bar)return;
  const hasAny=pvVersions.raw||pvVersions.sharpened||pvVersions.conversion;
  bar.style.display=hasAny?'flex':'none';
  ['raw','sharpened','conversion'].forEach(k=>{
    const btn=document.getElementById('pvv-'+k);
    if(!btn)return;
    btn.disabled=!pvVersions[k];
    btn.classList.toggle('active',k===(activeKey||(pvVersions.conversion?'conversion':pvVersions.sharpened?'sharpened':'raw')));
  });
  const cb=document.getElementById('pv-btn-convert');
  if(cb) cb.disabled=!pvVersions.sharpened;
}

function pvSwitchVersion(key){
  if(!pvVersions[key])return;
  pvData=pvVersions[key];
  pvRenderResults(pvVersions[key]);
  pvUpdateVersionUI(key);
}

function pvSwitchTab(name,btn){
  document.querySelectorAll('.pv-tab-btn').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('.pv-tab-panel').forEach(p=>p.classList.remove('active'));
  if(btn) btn.classList.add('active');
  const panel=document.getElementById('pv-tab-'+name);
  if(panel) panel.classList.add('active');
}

async function pvRefine(){
  if(!pvData){return;}
  const btn=document.getElementById('pv-btn-refine');
  const origHtml=btn.innerHTML;
  btn.disabled=true;
  btn.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg> Wird geschärft…';
  // Bestehende Hinweis-Box entfernen
  const old=document.getElementById('pv-refine-notice');
  if(old) old.remove();
  try{
    const res=await fetch('localpvrefine.php',{
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},
      body:JSON.stringify({currentJson:pvData,dwdSolarData:pvDwdData,csrf_token:CSRF_TOKEN}),
    });
    let data;
    const rawText=await res.text();
    try{ data=JSON.parse(rawText); }
    catch(parseErr){
      throw new Error(`HTTP ${res.status} — Server-Antwort kein JSON: ${rawText.substring(0,200)}`);
    }
    if(!res.ok||data.error){
      const err=data.error||{};
      const msg=typeof err==='object'?(err.message||JSON.stringify(err)):(err||`HTTP ${res.status}`);
      throw new Error(msg);
    }
    pvData=data;
    pvVersions.sharpened=data;
    pvVersions.conversion=null;
    pvRenderResults(data);
    pvUpdateVersionUI('sharpened');
    // Erfolgs-Hinweis anzeigen
    const notice=document.createElement('div');
    notice.id='pv-refine-notice';
    notice.className='pv-refine-notice';
    notice.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Content erfolgreich geschärft — alle Tabs wurden aktualisiert.';
    document.getElementById('pv-results').insertAdjacentElement('afterbegin',notice);
    setTimeout(()=>notice.remove(),6000);
  }catch(e){
    const errBox=document.getElementById('pv-error-msg');
    errBox.textContent='Fehler beim Schärfen: '+e.message;
    document.getElementById('pv-error').style.display='block';
  }finally{
    btn.disabled=false;
    btn.innerHTML=origHtml;
  }
}

async function pvConvert(){
  if(!pvVersions.sharpened){return;}
  const btn=document.getElementById('pv-btn-convert');
  const origHtml=btn.innerHTML;
  btn.disabled=true;
  btn.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg> Wird optimiert…';
  const old=document.getElementById('pv-refine-notice');
  if(old)old.remove();
  try{
    const res=await fetch('localpvconvert.php',{
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},
      body:JSON.stringify({currentJson:pvVersions.sharpened,dwdSolarData:pvDwdData,csrf_token:CSRF_TOKEN}),
    });
    let data;
    const rawText=await res.text();
    try{data=JSON.parse(rawText);}
    catch(parseErr){throw new Error(`HTTP ${res.status} — Server-Antwort kein JSON: ${rawText.substring(0,200)}`);}
    if(!res.ok||data.error){
      const err=data.error||{};
      const msg=typeof err==='object'?(err.message||JSON.stringify(err)):(err||`HTTP ${res.status}`);
      throw new Error(msg);
    }
    pvData=data;
    pvVersions.conversion=data;
    pvRenderResults(data);
    pvUpdateVersionUI('conversion');
    const notice=document.createElement('div');
    notice.id='pv-refine-notice';
    notice.className='pv-refine-notice';
    notice.innerHTML='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Conversion-Optimierung abgeschlossen (Level 3).';
    document.getElementById('pv-results').insertAdjacentElement('afterbegin',notice);
    setTimeout(()=>notice.remove(),6000);
  }catch(e){
    const errBox=document.getElementById('pv-error-msg');
    errBox.textContent='Fehler bei Conversion-Optimierung: '+e.message;
    document.getElementById('pv-error').style.display='block';
  }finally{
    btn.disabled=!pvVersions.sharpened;
    btn.innerHTML=origHtml;
  }
}

// ── Keyword-Vorschlag via DataForSEO ─────────────────────────────────────
async function pvSuggestKeywords(){
  const city = document.getElementById('pv-city').value.trim();
  if(!city){
    document.getElementById('pv-validation-msg').style.display='block';
    document.getElementById('pv-city').focus();
    return;
  }
  const product = document.getElementById('pv-product')?.value.trim()||'';
  const btn  = document.getElementById('pv-kw-suggest-btn');
  const pills= document.getElementById('pv-kw-pills');
  const orig = btn.innerHTML;
  btn.disabled=true;
  btn.innerHTML='<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg> Lade\u2026';
  try{
    const res=await fetch('dataforseo.php?action=keyword_volume',{
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},
      body:JSON.stringify({product,city,csrf_token:CSRF_TOKEN}),
    });
    const data=await res.json();
    if(!res.ok||data.error) throw new Error(data.error||`HTTP ${res.status}`);
    const kws=(data.keywords||[]).filter(k=>k.keyword);
    if(!kws.length){
      pills.innerHTML='<span class="pv-kw-pill-no-data" style="font-size:12px">Keine Daten gefunden \u2014 DataForSEO API verf\u00fcgbar?</span>';
    } else {
      pills.innerHTML=kws.map(k=>{
        const vol=k.search_volume!=null ? k.search_volume.toLocaleString('de-DE')+'\u202f/\u202fMo.' : 'k.\u00a0A.';
        const ci=k.competition_index!=null ? ` \u00b7 Wettbewerb\u202f${k.competition_index}%` : '';
        return `<button class="pv-kw-pill" data-kw="${escHtml(k.keyword)}" onclick="pvSelectKeyword(this.dataset.kw,this)"><span class="pv-kw-pill-text">${escHtml(k.keyword)}</span><span class="pv-kw-pill-vol">${vol}${ci}</span></button>`;
      }).join('');
    }
    pills.style.display='flex';
  }catch(e){
    pills.innerHTML=`<span class="pv-kw-pill-no-data" style="font-size:12px;color:var(--red)">Fehler: ${escHtml(e.message)}</span>`;
    pills.style.display='flex';
  }finally{
    btn.disabled=false;
    btn.innerHTML=orig;
  }
}
function pvSelectKeyword(kw,pillEl){
  document.getElementById('pv-keyword').value=kw;
  document.querySelectorAll('.pv-kw-pill').forEach(p=>p.classList.remove('selected'));
  pillEl.classList.add('selected');
}

// ── PV Generator Demo ────────────────────────────────────────────────────
function pvDemo(){
  pvDwdData={location:'64283 Darmstadt',geocoded:'Darmstadt, Hessen, Deutschland',lat:49.8728,lon:8.6512,
    station:{id:'01420',name:'Frankfurt/Main',distance_km:28.4},
    irradiance_kWhm2_year:1102,sunshine_hours_year:1821,dataYear:2025,estimated:false,
    germany_avg:{sunshine_hours_year:1914,year:2025,klimanormal_1991_2020:1665,irradiance_kWhm2_year:1073,source:'DWD Regionalmittel'},
    source:'DWD OpenData'};
  const d={
    input:{cityOrPostalCode:'64283 Darmstadt',primaryKeyword:'Photovoltaik Darmstadt',pvCalculatorInHero:true},
    meta:{title:'Photovoltaik Darmstadt – Solaranlage planen & Kosten berechnen',description:'Jetzt PV-Potenzial für Ihr Dach in Darmstadt berechnen. Individuelle Ertragsschätzung, transparente Kosten, regionaler Installateur.'},
    hero:{h1:'Photovoltaik in Darmstadt – Ihr Solarpotenzial berechnen',subline:'Darmstadts Dächer gehören zu den sonnenreichsten in Hessen. Berechnen Sie jetzt Ihren individuellen Ertrag.',calculatorIntro:'Geben Sie Dachfläche und Stromverbrauch ein – der Rechner zeigt Ihnen in Sekunden, wie viel Strom Ihre Anlage erzeugen würde.',primaryCta:'Jetzt PV-Potenzial berechnen',secondaryCta:'Persönliche Beratung anfragen'},
    benefits:[
      {title:'Unabhängigkeit',text:'Mit einer Photovoltaikanlage auf Ihrem Darmstädter Dach reduzieren Sie Ihren Strombezug aus dem Netz dauerhaft. Steigende Strompreise treffen Sie weniger – ein Großteil Ihres Verbrauchs lässt sich durch selbst erzeugten Solarstrom decken.',placement:'Vorteile-Kachel'},
      {title:'Wertsteigerung',text:'Immobilien mit Solaranlage erzielen in der Region Darmstadt nachweislich höhere Verkaufspreise. Eine dokumentierte Anlage mit Einspeisevergütung ist ein konkretes Argument beim Immobilienverkauf.',placement:'Vorteile-Kachel'},
      {title:'Alles aus einer Hand',text:'Von der Dachprüfung über Planung und Montage bis zur Anmeldung beim Netzbetreiber: Regionale Installateure in Darmstadt koordinieren alle Schritte. Sie müssen sich um nichts kümmern.',placement:'Vorteile-Kachel'},
      {title:'Zuverlässiger Partner',text:'Qualifizierte Fachbetriebe in der Region kennen die lokalen Anforderungen, Gebäudetypen und Netzbedingungen. Nach der Installation steht Ihnen ein Ansprechpartner für Wartung und Monitoring zur Verfügung.',placement:'Vorteile-Kachel'},
    ],
    sections:{
      intro:{micro:'Darmstadt zählt mit über 1.820 Sonnenstunden pro Jahr zu den günstigsten PV-Standorten in Hessen.',content:'Photovoltaik in Darmstadt lohnt sich – nicht nur wegen des vergleichsweise sonnigen Klimas, sondern auch weil viele Gebäude in der Region gut geeignete Dachflächen bieten. Ob Einfamilienhaus im Martinsviertel, Gewerbebau im Norden oder Wohnanlage in Bessungen: Die Kombination aus Dachausrichtung, verfügbarer Fläche und lokalem Stromverbrauch entscheidet über den Ertrag. Unser PV-Rechner berechnet Ihr individuelles Potenzial – in wenigen Sekunden, kostenlos und ohne Verpflichtung.',placement:'Direkt unter dem Hero'},
      solarPotential:{micro:'Mit 1.102 kWh/m² Globalstrahlung liegt Darmstadt rund 3 % über dem deutschen Klimanormal.',content:'Darmstadt verzeichnet laut Deutschem Wetterdienst eine Globalstrahlung von 1.102 kWh/m² pro Jahr und ca. 1.821 Sonnenstunden – das ist spürbar mehr als der deutsche Klimanormal von 1.665 Sonnenstunden. Für eine typische Dachanlage mit 10 kWp bedeutet das einen Jahresertrag von ca. 9.000–10.500 kWh, abhängig von Ausrichtung, Neigung und möglicher Verschattung. Die Grafik zeigt, wie sich das Solarpotenzial über die Monate verteilt – und warum besonders Frühjahr und Sommer für den Eigenverbrauch entscheidend sind.',placement:'Vor oder unter der Solarpotenzial-Grafik'},
      statisticsExplanation:{micro:'Die Kennzahlen zeigen, was eine PV-Anlage in Darmstadt realistisch leisten kann.',content:'Die dargestellten Werte basieren auf typischen Anlagen in der Region Darmstadt: Dachneigungen zwischen 25–45°, Südausrichtung mit maximal 30° Abweichung, kein nennenswerter Schattenwurf. In der Praxis variieren Erträge je nach Gebäudetyp und Dachkonstruktion. Der wichtigste Hebel für Wirtschaftlichkeit ist der Eigenverbrauchsanteil: Je mehr erzeugter Strom direkt selbst verbraucht wird, desto schneller zahlt sich die Anlage aus.',placement:'Unter dem Kennzahlen-Block'},
      processIntro:{micro:'In drei Schritten von der Idee zur laufenden Anlage.',content:'Der Weg zur Photovoltaikanlage ist in Darmstadt klar strukturiert. Zuerst berechnen Sie mit unserem Rechner das Grundpotenzial – das dauert unter einer Minute. Anschließend prüft ein Fachbetrieb Ihr Dach vor Ort: Tragfähigkeit, Ausrichtung, Anschluss. Nach der Planung folgt die Montage – je nach Anlagengröße in einem bis drei Tagen. Die Anmeldung beim Netzbetreiber und die Inbetriebnahme koordiniert Ihr Installateur.',placement:'Über dem 3-Schritte-Prozess'},
      projectsIntro:{micro:'Referenzprojekte aus der Region zeigen, was in der Praxis umsetzbar ist.',content:'Installateure in der Region Darmstadt und dem Rhein-Main-Gebiet haben in den letzten Jahren zahlreiche Anlagen auf Bestandsgebäuden, Neubauten und Gewerbeobjekten realisiert. Jedes Projekt hat eigene Anforderungen – von der Denkmalschutzfrage bis zur Statikprüfung. Die Referenzen geben einen realistischen Einblick in Anlagengrößen, Installationsaufwand und erzielte Erträge.',placement:'Über den Referenzprojekt-Karten'},
      economicsText:{micro:'Die Wirtschaftlichkeit einer PV-Anlage hängt vor allem vom Eigenverbrauch ab.',content:'Eine Photovoltaikanlage in Darmstadt ist vor allem dann wirtschaftlich, wenn ein Großteil des erzeugten Stroms direkt selbst verbraucht wird. Jede selbst verbrauchte Kilowattstunde spart den aktuellen Bezugspreis – jede eingespeiste Kilowattstunde bringt die gesetzliche Vergütung. Mit einem Heimspeicher lässt sich der Eigenverbrauchsanteil deutlich erhöhen. Konkrete Amortisationszeiträume hängen von Anlagengröße, Verbrauchsprofil und Finanzierung ab – der Rechner gibt eine erste Orientierung.',placement:'Vor der Wirtschaftlichkeitsgrafik'},
      testimonialsIntro:{micro:'Was sagen Kunden aus Darmstadt und Umgebung?',content:'Kundenstimmen aus der Region Darmstadt zeigen, wie unterschiedlich die Ausgangssituationen sind – und was Eigentümer im Rückblick über ihre Entscheidung denken. Ob kompaktes Reihenhausdach oder größeres Flachdach: Echte Bewertungen von Betreibern lokaler Anlagen helfen einzuschätzen, was Sie von der Planung bis zum laufenden Betrieb erwarten können.',placement:'Über den Kundenstimmen'},
      faqIntro:{micro:'Häufige Fragen zur Photovoltaik in Darmstadt.',content:'Wer sich in Darmstadt mit Photovoltaik beschäftigt, stellt ähnliche Fragen: Lohnt sich die Anlage auf meinem Dach? Welche Förderung gibt es aktuell? Was kostet eine 10-kWp-Anlage? Was passiert bei Stromausfall? Die folgenden FAQ beantworten die häufigsten Fragen von Eigentümern aus Darmstadt und dem Umland – sachlich, ohne Werbebotschaften.',placement:'Über dem FAQ-Accordion'},
      formIntro:{micro:'Noch Fragen? Wir melden uns – unverbindlich und ohne Verkaufsdruck.',content:'Wenn Sie nach dem Ertragsrechner noch Fragen haben oder eine persönliche Einschätzung für Ihr Dach wünschen, können Sie hier unverbindlich Kontakt aufnehmen. Ein regionaler Fachbetrieb aus dem Raum Darmstadt wird sich bei Ihnen melden – für eine kostenlose Ersteinschätzung, ohne Verpflichtung.',placement:'Über dem Kontaktformular als Backup-CTA'},
    },
    ctaStrategy:{
      primaryConversion:{element:'PV-Rechner im Hero',ctaExamples:['Jetzt PV-Potenzial berechnen','Ertrag für mein Dach prüfen','Solarpotenzial berechnen']},
      secondaryConversion:{element:'Formular am Seitenende',ctaExamples:['Persönliche Beratung anfragen','Unverbindliches Angebot einholen','Rückruf zur PV-Beratung vereinbaren']},
      microCtas:[
        {placement:'Nach Solarpotenzial-Grafik',text:'Rechner nutzen – in 60 Sekunden zum Ergebnis'},
        {placement:'Nach Kennzahlen-Block',text:'Ihr Dach berechnen'},
        {placement:'Nach Wirtschaftlichkeit',text:'Individuellen Ertrag berechnen'},
      ]
    },
    faq:[
      {question:'Lohnt sich eine PV-Anlage auf meinem Dach in Darmstadt?',answer:'Das hängt von Dachfläche, Ausrichtung und Ihrem Stromverbrauch ab. Darmstadt hat mit rund 1.820 Sonnenstunden pro Jahr und 1.102 kWh/m² Globalstrahlung gute Voraussetzungen. Unser Rechner gibt Ihnen eine erste Einschätzung in unter einer Minute.'},
      {question:'Welche Fördermöglichkeiten gibt es aktuell?',answer:'Die wichtigste Förderung ist die gesetzliche Einspeisevergütung nach EEG. Zusätzlich bieten KfW und einige Bundesländer zinsgünstige Kredite an. Die Stadt Darmstadt hat darüber hinaus eigene Förderprogramme, die je nach Haushaltsjahr variieren – aktuelle Informationen erhalten Sie beim Energiereferat der Stadt.'},
      {question:'Was kostet eine Photovoltaikanlage in Darmstadt?',answer:'Für ein Einfamilienhaus mit 8–10 kWp Leistung liegen die Installationskosten je nach Dach und Aufwand typischerweise zwischen 12.000 und 18.000 Euro brutto. Ein Heimspeicher kommt mit 8.000–12.000 Euro hinzu. Konkrete Angebote hängen stark vom individuellen Projekt ab.'},
      {question:'Was passiert mit meiner PV-Anlage bei Stromausfall?',answer:'Standardanlagen ohne Speicher schalten bei Netzausfall automatisch ab – aus Sicherheitsgründen, um einspeisende Anlagen zu schützen. Mit einem Notstrom-fähigen Wechselrichter und geeignetem Speicher ist eine Inselbetriebsfähigkeit möglich, was aber höhere Investitionskosten bedeutet.'},
    ],
    seoChecklist:[
      {item:'H1 enthält Hauptkeyword "Photovoltaik Darmstadt"',status:'ok',note:''},
      {item:'Meta-Title unter 60 Zeichen',status:'ok',note:''},
      {item:'Meta-Description mit CTA und lokalem Keyword',status:'ok',note:''},
      {item:'LocalBusiness Schema.org Markup',status:'warning',note:'Empfohlen für lokale Sichtbarkeit'},
      {item:'FAQ Schema (FAQPage) für Accordion',status:'missing',note:'Kann Rich Snippets in SERP auslösen'},
      {item:'Interne Verlinkung zu Standortseiten',status:'warning',note:'Weitere PLZ-Seiten könnten auf diese verlinken'},
    ],
    croChecklist:[
      {item:'PV-Rechner prominent im Hero über the fold',status:'ok',note:''},
      {item:'Primäre CTA eindeutig auf Rechner ausgerichtet',status:'ok',note:''},
      {item:'Vertrauenssignale (Bewertungen, Zertifikate) sichtbar',status:'warning',note:'Social Proof muss mit echten Daten befüllt werden'},
      {item:'Formulareinstieg niedrigschwellig (keine Pflichtfelder außer Kontakt)',status:'warning',note:'Maximal 3 Felder empfohlen'},
      {item:'Ladezeit unter 3 Sekunden (PSI Mobile)',status:'missing',note:'Bildoptimierung und Lazy Loading prüfen'},
    ],
    recommendations:[
      {module:'SEO',priority:'high',recommendation:'FAQ-Schema (FAQPage) implementieren – bei 4+ FAQs mit lokalem Bezug hohe Chance auf Rich Snippets bei "Photovoltaik Darmstadt"-Queries.'},
      {module:'CRO',priority:'high',recommendation:'Vertrauenssignale direkt unter dem Hero platzieren – Bewertungsdurchschnitt, Anzahl Installationen oder Logos bekannter Zertifizierungen erhöhen die Rechner-Nutzungsrate.'},
      {module:'SEO',priority:'medium',recommendation:'LocalBusiness Schema mit Adresse und Servicebereich Darmstadt ergänzen – verbessert lokale Pack-Sichtbarkeit.'},
      {module:'Performance',priority:'medium',recommendation:'PSI Mobile Score prüfen – Hero-Bilder als WebP mit Lazy Loading können LCP verbessern.'},
    ],
    placementMap:[
      {order:1,module:'Hero',visualType:'full-width-hero',contentNeeded:['H1','Subline','Rechner-Microcopy','CTA'],generatedFields:['hero.h1','hero.subline','hero.calculatorIntro','hero.primaryCta'],recommendation:'H1 prominent über dem Rechner, Subline darunter. primaryCta = Rechner-Button, secondaryCta als Textlink.'},
      {order:2,module:'Einleitung',visualType:'text-section',contentNeeded:['Intro-Text'],generatedFields:['sections.intro.micro','sections.intro.content'],recommendation:'sections.intro.micro als Teaser-Satz über dem Fließtext einsetzen.'},
      {order:3,module:'Vorteile',visualType:'4-column-grid',contentNeeded:['4 Kacheln'],generatedFields:['benefits[0-3]'],recommendation:'Je eine Kachel pro benefit-Eintrag. Icon + Title + Text.'},
      {order:4,module:'Solarpotenzial-Grafik',visualType:'chart-section',contentNeeded:['Begleittext vor Grafik'],generatedFields:['sections.solarPotential.micro','sections.solarPotential.content'],recommendation:'solarPotential.micro als Intro-Satz über der Grafik, content darunter.'},
      {order:5,module:'Kennzahlen-Block',visualType:'stat-grid',contentNeeded:['Erklärungstext unter Zahlen'],generatedFields:['sections.statisticsExplanation.micro','sections.statisticsExplanation.content'],recommendation:'statisticsExplanation.content unter den Zahlenblöcken als Kontextualisierung.'},
      {order:6,module:'3-Schritte-Prozess',visualType:'step-by-step',contentNeeded:['Einleitung'],generatedFields:['sections.processIntro.micro','sections.processIntro.content'],recommendation:'processIntro.micro als Abschnittsüberschrift, content als Fließtext davor.'},
      {order:7,module:'Referenzprojekte',visualType:'card-grid',contentNeeded:['Einleitungstext'],generatedFields:['sections.projectsIntro.micro','sections.projectsIntro.content'],recommendation:'projectsIntro.content als Vertrauenstext über den Projekt-Karten.'},
      {order:8,module:'Wirtschaftlichkeit',visualType:'chart-section',contentNeeded:['Begleittext'],generatedFields:['sections.economicsText.micro','sections.economicsText.content'],recommendation:'economicsText.content vor der Grafik. Kein konkreter Amortisationszeitraum ohne individuelle Daten.'},
      {order:9,module:'Kundenstimmen',visualType:'testimonial-slider',contentNeeded:['Einleitung'],generatedFields:['sections.testimonialsIntro.micro','sections.testimonialsIntro.content'],recommendation:'testimonialsIntro.micro als Überschrift, content als Einleitungstext.'},
      {order:10,module:'FAQ',visualType:'accordion',contentNeeded:['FAQ-Einleitung','FAQ-Fragen'],generatedFields:['sections.faqIntro.micro','faq[0-3]'],recommendation:'faqIntro.micro als Sektionsüberschrift. Jeder faq-Eintrag als ein Accordion-Element.'},
      {order:11,module:'Kontaktformular',visualType:'form-section',contentNeeded:['Formular-Einleitung'],generatedFields:['sections.formIntro.micro','sections.formIntro.content'],recommendation:'formIntro.content über dem Formular. Maximal 3 Pflichtfelder. Kein Verkaufsdruck.'},
    ],
    exportMarkdown:'# Photovoltaik Darmstadt – Content-Bausteine\n\n## Meta\n**Title:** Photovoltaik Darmstadt – Solaranlage planen & Kosten berechnen\n**Description:** Jetzt PV-Potenzial für Ihr Dach in Darmstadt berechnen.\n\n## Hero\n**H1:** Photovoltaik in Darmstadt – Ihr Solarpotenzial berechnen\n**Subline:** Darmstadts Dächer gehören zu den sonnenreichsten in Hessen.\n**CTA:** Jetzt PV-Potenzial berechnen\n\n*(Demo-Daten — kein echtes KI-Ergebnis)*',
  };
  pvData=d;
  pvVersions={raw:d,sharpened:null,conversion:null};
  pvRenderResults(d);
  pvUpdateVersionUI('raw');
  document.getElementById('pv-results').style.display='block';
  document.getElementById('pv-loading').style.display='none';
  document.getElementById('pv-error').style.display='none';
}

async function pvGenerate(){
  const city = document.getElementById('pv-city').value.trim();
  const validMsg = document.getElementById('pv-validation-msg');
  if(!city){
    validMsg.style.display='block';
    document.getElementById('pv-city').focus();
    return;
  }
  validMsg.style.display='none';

  const keyword  = document.getElementById('pv-keyword').value.trim();
  const product  = document.getElementById('pv-product')?.value.trim()||'';
  const url      = document.getElementById('pv-url').value.trim();
  const template = document.getElementById('pv-template').value.trim();

  // UI: loading state
  document.getElementById('pv-btn-generate').disabled=true;
  document.getElementById('pv-loading').style.display='block';
  document.getElementById('pv-results').style.display='none';
  document.getElementById('pv-error').style.display='none';
  pvSwitchTab('content', document.querySelector('.pv-tab-btn'));

  // ── Schritt 1: DWD Solardaten vorabladen ─────────────────────────────
  pvDwdData = null;
  let pvDwdError = null;
  const statusEl = document.getElementById('pv-loading-status');
  if(statusEl) statusEl.textContent = '☀ DWD Standortdaten werden abgerufen\u2026';
  try{
    const dwdRes = await fetch('dwd.php?action=solar&location='+encodeURIComponent(city));
    const dwdText = await dwdRes.text();
    let dwd = null;
    try{ dwd = JSON.parse(dwdText); } catch(e){ pvDwdError = `Kein JSON: ${dwdText.substring(0,100)}`; }
    if(dwd && dwd.error){ pvDwdError = typeof dwd.error==='string'?dwd.error:JSON.stringify(dwd.error); dwd=null; }
    if(!dwdRes.ok && !pvDwdError){ pvDwdError = `HTTP ${dwdRes.status}`; }
    if(dwd && dwd.irradiance_kWhm2_year){
      pvDwdData = dwd;
      const note = dwd.estimated ? ' (Schätzung)' : ` · Station ${dwd.station?.name||''}`;
      if(statusEl) statusEl.textContent = `☀ DWD: ${dwd.irradiance_kWhm2_year} kWh/m²${note}`;
    }
  }catch(dwdErr){
    pvDwdError = dwdErr.message||'Netzwerkfehler';
  }
  if(statusEl && !pvDwdData) statusEl.textContent = pvDwdError ? `DWD fehlgeschlagen (${pvDwdError}) · KI generiert\u2026` : 'KI generiert Bausteine\u2026';
  if(statusEl && pvDwdData)  statusEl.textContent += ' · KI generiert Bausteine\u2026';

  // DWD-Banner schon jetzt setzen (auch bei Fehler)
  const dwdBannerEl = document.getElementById('pv-dwd-banner');
  if(dwdBannerEl && pvDwdError && !pvDwdData){
    dwdBannerEl.innerHTML = `<div style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--amber)">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <strong>DWD nicht verfügbar</strong>
      <span style="color:var(--text3)">${escHtml(pvDwdError)}</span>
    </div>`;
    dwdBannerEl.style.display='block';
  }

  const body = {
    cityOrPostalCode: city,
    primaryKeyword:   keyword || (product ? `${product} ${city}` : ''),
    product:          product,
    landingPageUrl:   url,
    templateType:     template,
    csrf_token:       CSRF_TOKEN,
    dwdSolarData:     pvDwdData,
  };

  try{
    const res = await fetch('localpv.php',{
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},
      body:JSON.stringify(body),
    });
    let data;
    const rawText = await res.text();
    try{ data = JSON.parse(rawText); }
    catch(parseErr){
      throw new Error(`HTTP ${res.status} — Server-Antwort kein JSON: ${rawText.substring(0,200)}`);
    }
    if(!res.ok||data.error){
      const err = data.error||{};
      const msg = typeof err==='object' ? (err.message||JSON.stringify(err)) : (err||`HTTP ${res.status}`);
      throw new Error(msg);
    }
    pvData = data;
    pvVersions.raw = data;
    pvVersions.sharpened = null;
    pvVersions.conversion = null;
    pvRenderResults(data);
    pvUpdateVersionUI('raw');
    document.getElementById('pv-results').style.display='block';
  }catch(e){
    document.getElementById('pv-error-msg').textContent = 'Fehler: '+e.message;
    document.getElementById('pv-error').style.display='block';
  }finally{
    document.getElementById('pv-loading').style.display='none';
    document.getElementById('pv-btn-generate').disabled=false;
  }
}

function pvRenderResults(d){
  const m=d.meta||{},h=d.hero||{},sec=d.sections||{};
  const faq=Array.isArray(d.faq)?d.faq:[];
  const cards=[];
  const CI='<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
  // ── DWD-Banner befüllen (persistent, über Tabs) ───────────────────────
  const dwdBanner = document.getElementById('pv-dwd-banner');
  if(dwdBanner){
    if(pvDwdData && pvDwdData.irradiance_kWhm2_year){
      const est   = pvDwdData.estimated;
      const yr    = pvDwdData.dataYear ? `Messjahr ${pvDwdData.dataYear}` : 'Schätzungsbasis';
      const stNm  = pvDwdData.station?.name || '–';
      const stDst = pvDwdData.station?.distance_km ? `${pvDwdData.station.distance_km} km` : '';
      const geo   = pvDwdData.geocoded ? escHtml(pvDwdData.geocoded.split(',')[0]) : '';
      const ga    = pvDwdData.germany_avg;
      const SunIco= '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
      // Vergleichs-HTML (Standort vs. Deutschland)
      const metricHtml = (localVal, deVal, unit, label, deLabel) =>
        `<div class="pv-dwd-bm"><div class="pv-dwd-bm-val">${localVal}</div><div class="pv-dwd-bm-unit">${unit}</div><div class="pv-dwd-bm-label">${label}</div></div>`+
        (deVal?`<div class="pv-dwd-bm" style="opacity:.75"><div class="pv-dwd-bm-val">${deVal}</div><div class="pv-dwd-bm-unit">${unit}</div><div class="pv-dwd-bm-label">${deLabel}</div></div>`:'');
      // Klimanormal 1991-2020 als fairer Benchmark (Jahresmittel 2025 war außergewöhnlich hoch)
      const deIrr     = ga?.irradiance_kWhm2_year || null;
      const deSun     = ga?.klimanormal_1991_2020 || ga?.sunshine_hours_year || null;
      const deSunLbl  = ga?.klimanormal_1991_2020 ? 'Sonnenstunden Ø 1991–2020' : `Sonnenstunden Ø (${ga?.year||''})`;
      const deHead    = 'Deutschland Klimanormal';
      dwdBanner.innerHTML =
        `<div class="pv-dwd-banner-head">${SunIco}<span class="pv-dwd-banner-title">DWD Standort-Solardaten</span></div>`+
        `<div class="pv-dwd-compare">`+
          `<div class="pv-dwd-compare-col"><div class="pv-dwd-compare-head">Standort${geo?' · '+geo:''}</div>`+
            `<div class="pv-dwd-bm"><div class="pv-dwd-bm-val">${pvDwdData.irradiance_kWhm2_year}</div><div class="pv-dwd-bm-unit">kWh/m²/Jahr</div><div class="pv-dwd-bm-label">Globalstrahlung${est?' (Schätzung)':''}</div></div>`+
            (pvDwdData.sunshine_hours_year?`<div class="pv-dwd-bm"><div class="pv-dwd-bm-val">${pvDwdData.sunshine_hours_year}</div><div class="pv-dwd-bm-unit">h/Jahr</div><div class="pv-dwd-bm-label">Sonnenstunden</div></div>`:'')+
          `</div>`+
          `<div class="pv-dwd-compare-div"></div>`+
          `<div class="pv-dwd-compare-col"><div class="pv-dwd-compare-head">${deHead}</div>`+
            (deIrr?`<div class="pv-dwd-bm" style="opacity:.8"><div class="pv-dwd-bm-val">${deIrr}</div><div class="pv-dwd-bm-unit">kWh/m²/Jahr</div><div class="pv-dwd-bm-label">Globalstrahlung Ø 1991–2020</div></div>`:'')+
            (deSun?`<div class="pv-dwd-bm" style="opacity:.8"><div class="pv-dwd-bm-val">${deSun}</div><div class="pv-dwd-bm-unit">h/Jahr</div><div class="pv-dwd-bm-label">${deSunLbl}</div></div>`:'')+
            (!deIrr&&!deSun?`<div style="font-size:11px;color:var(--text3);padding-top:8px">Nicht verfügbar</div>`:'')+
          `</div>`+
        `</div>`+
        `<div class="pv-dwd-banner-meta">`+
        `<span>Station: <strong>${escHtml(stNm)}</strong>${stDst?' · '+escHtml(stDst):''}</span>`+
        `<span>· ${escHtml(yr)}</span>`+
        (ga?.klimanormal_1991_2020?`<span>· DE Klimanormal 1991–2020: ${ga.klimanormal_1991_2020} h/Jahr</span>`:'')+
        `<span>· Lat ${pvDwdData.lat} / Lon ${pvDwdData.lon}</span>`+
        `</div>`;
      // Banner: zwei Spalten + DATENQUELLE-Label unten
      dwdBanner.innerHTML += '<div class="pv-data-hint pv-data-active"><span class="pv-data-hint-label">Datenquelle:</span><span class="pv-data-source-tag dwd">DWD OpenData'+(est?' (Schätzung)':'')+'</span></div>';
      dwdBanner.style.display='block';
    } else {
      dwdBanner.style.display='none';
    }
  }
  function hint(sources){
    if(!sources||!sources.length)return '';
    return '<div class="pv-data-hint"><span class="pv-data-hint-label">Perspektivisch:</span>'+
      sources.map(s=>`<span class="pv-data-source-tag ${escHtml(s.c)}" title="${escHtml(s.d)}">${escHtml(s.l)}</span>`).join('')+
      '</div>';
  }
  function card(icon,label,id,oc,content,h2){
    return `<div class="pv-card"><div class="pv-card-label">${icon}${escHtml(label)}</div>`+
      `<button class="pv-copy-btn" id="pv-copy-${id}" onclick="${escHtml(oc)}">${CI} Kopieren</button>`+
      content+h2+'</div>';
  }
  function activeHint(sources){
    if(!sources||!sources.length)return '';
    return '<div class="pv-data-hint pv-data-active"><span class="pv-data-hint-label">Datenquelle:</span>'+
      sources.map(s=>`<span class="pv-data-source-tag ${escHtml(s.c)}" title="${escHtml(s.d)}">${escHtml(s.l)}</span>`).join('')+
      '</div>';
  }
  function dwdActiveHint(){
    // Vollständiger DWD-Footer für die Solarpotenzial-Karte
    if(!pvDwdData||!pvDwdData.irradiance_kWhm2_year) return '';
    const est  = pvDwdData.estimated;
    const stNm = pvDwdData.station?.name||'–';
    const stD  = pvDwdData.station?.distance_km?` · ${pvDwdData.station.distance_km} km`:'';
    const yr   = pvDwdData.dataYear?`Messjahr ${pvDwdData.dataYear}`:'Schätzung';
    const ga   = pvDwdData.germany_avg;
    const kn   = ga?.klimanormal_1991_2020||null;
    const sun  = pvDwdData.sunshine_hours_year||0;
    const irr  = pvDwdData.irradiance_kWhm2_year||0;
    const diff = kn&&sun ? ((sun-kn)/kn*100).toFixed(1) : null;
    const sign = diff>=0?'+':'';
    return '<div class="pv-data-hint pv-data-active">'+
      '<span class="pv-data-hint-label">Datenquelle:</span>'+
      `<span class="pv-data-source-tag dwd">DWD OpenData${est?' (Schätzung)':''}</span>`+
      '<div class="pv-dwd-inline-vals">'+
        `<span>${irr} kWh/m²/Jahr Globalstrahlung · ${sun} h/Jahr Sonnenstunden</span>`+
        `<span>Station: ${escHtml(stNm)}${escHtml(stD)} · ${escHtml(yr)}</span>`+
        (kn?`<span>DE Klimanormal: ${kn} h/Jahr · Standort <strong>${sign}${diff} %</strong> gegenüber Deutschland</span>`:'')+
      '</div>'+
      '</div>';
  }
  // 0. DWD-Karte wurde in Banner verschoben — kein Card-Eintrag mehr
  // 1. Meta
  cards.push(card(
    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="15" x2="12" y2="15"/></svg>',
    'Meta','meta',"pvCopySection('meta')",
    `<div class="pv-meta-row"><div class="pv-meta-field"><div class="pv-meta-field-label">Title (${(m.title||'').length} Zeichen)</div><div class="pv-meta-value">${escHtml(m.title||'–')}</div></div>`+
    `<div class="pv-meta-field" style="margin-top:8px"><div class="pv-meta-field-label">Description (${(m.description||'').length} Zeichen)</div><div class="pv-meta-value">${escHtml(m.description||'–')}</div></div></div>`,
    hint([{c:'gsc',l:'GSC · CTR & Ø-Position',d:'Aktuelle CTR und Ranking-Position zeigen, ob Title & Description Klicks generieren. Direkte Vorlage für datenbasierte Titeloptimierung.'},
          {c:'dataforseo',l:'DataForSEO · Suchvolumen & SERP-Preview',d:'Keyword-Suchvolumen zur Priorisierung des Haupt-Keywords im Title; SERP-Vorschau prüft Snippet-Darstellung in Google.'}])
  ));
  // 2. Hero
  cards.push(card(
    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>',
    'Hero','hero',"pvCopySection('hero')",
    `<div class="pv-hero-grid">`+
    `<div class="pv-hero-field full"><div class="pv-hero-field-label">H1</div><div class="pv-hero-value">${escHtml(h.h1||'–')}</div></div>`+
    `<div class="pv-hero-field full"><div class="pv-hero-field-label">Subline</div><div class="pv-hero-value">${escHtml(h.subline||'–')}</div></div>`+
    `<div class="pv-hero-field full"><div class="pv-hero-field-label">Calculator Intro (Rechner-Microcopy)</div><div class="pv-hero-value" style="border-color:var(--accent-border)">${escHtml(h.calculatorIntro||'–')}</div></div>`+
    `<div class="pv-hero-field"><div class="pv-hero-field-label">Primärer CTA → Rechner</div><div class="pv-hero-value">${escHtml(h.primaryCta||'–')}</div></div>`+
    `<div class="pv-hero-field"><div class="pv-hero-field-label">Sekundärer CTA → Formular</div><div class="pv-hero-value">${escHtml(h.secondaryCta||'–')}</div></div></div>`,
    hint([{c:'gsc',l:'GSC · Top-Queries',d:'Die häufigsten Suchanfragen der bestehenden LP zeigen, welche Keywords Nutzer wirklich eingeben — ideal zur H1-Schärfung und CTA-Formulierung.'},
          {c:'dataforseo',l:'DataForSEO · Keyword-Varianten',d:'Lokale Varianten und Suchvolumina helfen, den stärksten Begriff für H1 und CTA auszuwählen.'}])
  ));
  // 3–10. Content Sections in LP-Reihenfolge
  const secDefs=[
    {k:'intro',            l:'Einleitungstext',          i:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="13" y1="18" x2="3" y2="18"/></svg>',
     h:[{c:'gsc',l:'GSC · Nutzerintention',d:'Top-Queries zeigen, was Nutzer wirklich suchen.'},
        {c:'sistrix',l:'Sistrix · Wettbewerber',d:'Wie positionieren Wettbewerber ihr Intro?'}]},
    {k:'solarPotential',   l:'Solarpotenzial',           i:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
     h:[{c:'pvgis',l:'PVGIS · Einstrahlungsdaten',d:'Tatsächliche Globalstrahlungsdaten für die PLZ/Region (PVGIS noch nicht integriert).'}], dwdHint:true},
    {k:'statisticsExplanation',l:'Kennzahlenblock',      i:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
     h:[{c:'dataforseo',l:'DataForSEO · Lokales Suchvolumen',d:'Regionale Suchvolumina als Kontext.'},
        {c:'sistrix',l:'Sistrix · Marktdaten',d:'Sichtbarkeitsindex und Keyword-Anzahl.'}]},
    {k:'processIntro',     l:'3-Schritte-Prozess',       i:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>',
     h:[]},
    {k:'projectsIntro',    l:'Referenzprojekte',          i:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
     h:[{c:'gsc',l:'GSC · Seiten-Performance',d:'Klick- und Impressionsdaten zeigen regionale Performance.'}]},
    {k:'economicsText',    l:'Wirtschaftlichkeitsgrafik', i:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
     h:[{c:'dataforseo',l:'DataForSEO · Wettbewerber-Angebote',d:'ROI-Versprechen der Wettbewerber als Benchmark.'}]},
    {k:'testimonialsIntro',l:'Kundenstimmen',             i:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
     h:[]},
    {k:'faqIntro',         l:'FAQ-Einleitung',            i:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
     h:[{c:'dataforseo',l:'DataForSEO · People Also Ask',d:'SERP-Features zeigen die relevantesten Nutzerfragen.'}]},
    {k:'formIntro',        l:'Formular / Backup-CTA',     i:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
     h:[{c:'gsc',l:'GSC · CTR-Optimierung',d:'Niedrige CTR trotz guter Position deutet auf schwache CTAs hin.'}]},
  ];
  secDefs.forEach(s=>{
    const sObj=sec[s.k]||{};
    const sMicro=typeof sObj==='object'?(sObj.micro||'–'):(sObj||'–');
    const sFull=typeof sObj==='object'?(sObj.content||''):(typeof sObj==='string'?sObj:'');
    const sPlace=typeof sObj==='object'?(sObj.placement||''):'';
    const sCopy=sFull?`Micro:\n${sMicro}\n\nContent:\n${sFull}`:sMicro;
    cards.push(card(s.i,s.l,'sec-'+s.k,
      `pvCopySectionText(${JSON.stringify(sCopy)},this)`,
      (sPlace?`<div class="pv-placement-badge"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/></svg>${escHtml(sPlace)}</div>`:'')+
      `<div class="pv-sec-label">Micro / UI-Text</div><div class="pv-sec-micro">${escHtml(sMicro)}</div>`+
      (sFull?`<div class="pv-sec-label">Content / SEO-Text</div><div class="pv-sec-content">${escHtml(sFull)}</div>`:''),
      (s.dwdHint
        ? (pvDwdData
            ? dwdActiveHint()
            : '<div class="pv-data-hint"><span class="pv-data-hint-label" style="color:var(--amber)">DWD:</span><span class="pv-data-source-tag" style="background:var(--amber-bg);color:var(--amber);border-color:var(--amber-border)" title="DWD-Datenabruf fehlgeschlagen">nicht verfügbar (Schätzung aktiv)</span></div>'
              + hint(s.h||[]))
        : hint(s.h||[]))
    ));
  });
  // 11. FAQ
  cards.push(card(
    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'FAQ','faq',"pvCopySection('faq')",
    '<div class="pv-faq-list">'+faq.map((f,i)=>`<div class="pv-faq-item"${i>0?' style="margin-top:10px"':''}><div class="pv-faq-q">${i+1}. ${escHtml(f.question||'')}</div><div class="pv-faq-a">${escHtml(f.answer||'')}</div></div>`).join('')+'</div>',
    hint([{c:'dataforseo',l:'DataForSEO · People Also Ask',d:'Direkte Übernahme echter Nutzerfragen aus der Google SERP — stärkere Relevanzsignale als rein KI-generierte FAQ-Fragen.'},
          {c:'gsc',l:'GSC · W-Fragen aus Queries',d:'Queries mit "wie", "was", "warum", "kosten" sind direkte FAQ-Kandidaten mit belegtem Suchvolumen.'}])
  ));
  // 12. SEO-Checkliste
  cards.push(card(
    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
    'SEO-Checkliste','seoChecklist',"pvCopySection('seoChecklist')",
    '<div class="pv-checklist">'+pvChecklistHtml(d.seoChecklist||[])+'</div>',
    hint([{c:'gsc',l:'GSC · Ø-Position & Klicks',d:'Ranking-Position und CTR pro Keyword zeigen, welche SEO-Maßnahmen wirken und wo der größte Handlungsbedarf besteht.'},
          {c:'sistrix',l:'Sistrix · Sichtbarkeitsindex',d:'Langzeit-Verlauf zeigt Penaltys, Ranking-Gewinne und saisonale Schwankungen — Basis für technische Priorisierung.'},
          {c:'dataforseo',l:'DataForSEO · SERP-Features',d:'Welche Features (Snippets, Local Pack, FAQs) erscheinen für Ziel-Keywords? Direkte Handlungsempfehlungen für strukturierte Daten.'}])
  ));
  // 13. CRO-Checkliste
  cards.push(card(
    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    'CRO-Checkliste','croChecklist',"pvCopySection('croChecklist')",
    '<div class="pv-checklist">'+pvChecklistHtml(d.croChecklist||[])+'</div>',
    hint([{c:'gsc',l:'GSC · CTR-Analyse',d:'Niedrige CTR trotz guter Position = schwache Meta/Hero. Direkter Feedback-Loop zwischen SERP-CTR und CRO-Optimierungen.'},
          {c:'sistrix',l:'Sistrix · Wettbewerber-Snippets',d:'Trust-Signale, Bewertungen und CTAs in Konkurrenz-Ergebnissen als CRO-Benchmark für die eigene LP.'}])
  ));
  // 14. Empfehlungen
  const recsHtml=(Array.isArray(d.recommendations)?d.recommendations:[]).map(r=>
    `<div class="pv-rec-item"><span class="pv-rec-prio ${escHtml(r.priority||'low')}">${escHtml(r.priority||'low')}</span>`+
    `<div class="pv-rec-body"><div class="pv-rec-module">${escHtml(r.module||'')}</div><div class="pv-rec-text">${escHtml(r.recommendation||'')}</div></div></div>`
  ).join('');
  cards.push(card(
    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
    'Empfehlungen','recommendations',"pvCopySection('recommendations')",
    `<div class="pv-rec-list">${recsHtml}</div>`,
    hint([{c:'gsc',l:'GSC',d:'Rankings, CTR und Top-Queries als Grundlage für priorisierte Maßnahmen.'},
          {c:'sistrix',l:'Sistrix',d:'Sichtbarkeit und Wettbewerbervergleich zur Gewichtung der Empfehlungen.'},
          {c:'dataforseo',l:'DataForSEO',d:'Suchvolumen und SERP-Features zur ROI-Einschätzung der empfohlenen Maßnahmen.'}])
  ));
  // 15. Markdown-Export
  cards.push(card(
    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
    'Markdown-Export','exportMarkdown',"pvCopySection('exportMarkdown')",
    `<div class="pv-export-area">${escHtml(d.exportMarkdown||'')}</div>`,
    ''
  ));

  // ── Tab 1: Content — in LP-Reihenfolge (top → bottom) ──────────────────
  // cards-Index: [0]=Meta [1]=Hero [2]=intro [3]=solarPotential
  //              [4]=statisticsExplanation [5]=processIntro [6]=projectsIntro
  //              [7]=economicsText [8]=testimonialsIntro [9]=faqIntro
  //              [10]=formIntro [11]=FAQ-Q&A
  const benefits=Array.isArray(d.benefits)?d.benefits:[];
  const benefitsHtml=benefits.length?
    '<div class="pv-card"><div class="pv-card-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Vorteile (4 Kacheln)</div>'+
    `<button class="pv-copy-btn" id="pv-copy-benefits" onclick="pvCopySection('benefits')">${CI} Kopieren</button>`+
    '<div class="pv-benefits-grid">'+
    benefits.map(b=>`<div class="pv-benefit-card"><div class="pv-benefit-title">${escHtml(b.title||'')}</div><div class="pv-benefit-text">${escHtml(b.text||'')}</div>${b.placement?`<div class="pv-benefit-placement">${escHtml(b.placement)}</div>`:''}</div>`).join('')+
    '</div></div>':'';

  const cta=d.ctaStrategy||{};
  const prim=cta.primaryConversion||{};
  const sec2=cta.secondaryConversion||{};
  const mCtAs=Array.isArray(cta.microCtas)?cta.microCtas:[];
  const ctaHtml=`<div class="pv-card"><div class="pv-card-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>CTA-Strategie</div>`+
    `<button class="pv-copy-btn" id="pv-copy-ctaStrategy" onclick="pvCopySection('ctaStrategy')">${CI} Kopieren</button>`+
    `<div class="pv-cta-strategy">`+
    `<div class="pv-cta-block"><div class="pv-cta-block-label primary">Primär → ${escHtml(prim.element||'PV-Rechner')}</div>`+
    (Array.isArray(prim.ctaExamples)?prim.ctaExamples.map(t=>`<div class="pv-cta-example" title="Kopieren" onclick="pvCopySectionText(${JSON.stringify(t)},this)">${escHtml(t)}</div>`).join(''):'')+
    `</div>`+
    `<div class="pv-cta-block"><div class="pv-cta-block-label secondary">Sekundär → ${escHtml(sec2.element||'Formular')}</div>`+
    (Array.isArray(sec2.ctaExamples)?sec2.ctaExamples.map(t=>`<div class="pv-cta-example" title="Kopieren" onclick="pvCopySectionText(${JSON.stringify(t)},this)">${escHtml(t)}</div>`).join(''):'')+
    `</div></div>`+
    (mCtAs.length?`<div class="pv-micro-ctas"><div class="pv-micro-cta-label">Micro-CTAs (Zwischenabschnitte)</div>`+
      mCtAs.map(mc=>`<div class="pv-micro-cta-item"><span class="pv-micro-cta-placement">${escHtml(mc.placement||'')}</span><span class="pv-micro-cta-text">${escHtml(mc.text||'')}</span></div>`).join('')+
      `</div>`:'')+
    `</div>`;

  // Explizite LP-Reihenfolge statt slice()
  document.getElementById('pv-results-list').innerHTML=[
    cards[0],    // Meta (SEO)
    cards[1],    // Hero
    cards[2],    // Intro / Einstiegstext
    benefitsHtml,// Vorteile (4 Kacheln)
    cards[3],    // Solarpotenzial
    cards[4],    // Kennzahlenblock
    cards[5],    // 3-Schritte-Prozess
    cards[6],    // Referenzprojekte
    cards[7],    // Wirtschaftlichkeitsgrafik
    cards[8],    // Kundenstimmen
    cards[9],    // FAQ-Einleitung
    cards[11],   // FAQ Q&A (direkt nach FAQ-Einleitung)
    cards[10],   // Formular / Backup-CTA (ganz unten = LP-Ende)
    ctaHtml,     // CTA-Strategie (übergreifend)
  ].join('');

  // ── Tab 2: Placement Map ──
  const pm=Array.isArray(d.placementMap)?d.placementMap:[];
  const pmHtml=pm.length?
    '<div class="pv-placement-map">'+pm.map(p=>{
      const anchor='pv-copy-sec-'+(p.generatedFields&&p.generatedFields[0]?p.generatedFields[0].replace(/[^a-zA-Z0-9]/g,'-'):'');
      return `<div class="pv-placement-item">`+
        `<div class="pv-placement-num">${p.order||''}</div>`+
        `<div class="pv-placement-body">`+
        `<div class="pv-placement-module">${escHtml(p.module||'')}</div>`+
        `<div class="pv-placement-visual">${escHtml(p.visualType||'')}</div>`+
        `<div class="pv-placement-fields">${(p.generatedFields||[]).map(f=>`<span class="pv-placement-field-tag">${escHtml(f)}</span>`).join('')}</div>`+
        (p.recommendation?`<div class="pv-placement-rec">${escHtml(p.recommendation)}</div>`:'')+
        `</div>`+
        `<button class="pv-placement-jump" onclick="pvSwitchTab('content',document.querySelector('.pv-tab-btn'));document.getElementById('pv-tab-content').scrollIntoView({behavior:'smooth'})">→ Content</button>`+
        `</div>`;
    }).join('')+'</div>':
    '<div style="color:var(--text3);font-size:13px;padding:24px 0">Keine Placement Map im letzten Ergebnis.</div>';
  document.getElementById('pv-placement-list').innerHTML=pmHtml;

  // ── Tab 3: SEO / CRO Checks + Empfehlungen ──
  document.getElementById('pv-checks-list').innerHTML=cards.slice(12,15).join('');

  // ── Tab 4: Markdown Export ──
  document.getElementById('pv-export-content').innerHTML=cards[15]||cards[cards.length-1]||'';
}

function pvChecklistHtml(items){
  return items.map(item=>{
    const st=item.status||'ok';
    const icon=st==='ok'
      ?'<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
      :st==='warning'
      ?'<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'
      :'<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
    return`<div class="pv-checklist-item"><div class="pv-checklist-status ${escHtml(st)}">${icon}</div>`+
      `<div class="pv-checklist-text"><div class="pv-checklist-item-label">${escHtml(item.item||'')}</div>`+
      `${item.note?`<div class="pv-checklist-note">${escHtml(item.note)}</div>`:''}</div></div>`;
  }).join('');
}

function pvCopySectionText(text,btn){
  navigator.clipboard.writeText(text||'').then(()=>{
    const orig=btn.innerHTML;
    btn.innerHTML='<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Kopiert';
    btn.classList.add('copied');
    setTimeout(()=>{btn.innerHTML=orig;btn.classList.remove('copied');},2000);
  });
}

function pvCopySection(key){
  if(!pvData) return;
  const btn=document.getElementById('pv-copy-'+key);
  let text='';
  if(key==='meta'){
    text=`Title: ${pvData.meta?.title||''}\nDescription: ${pvData.meta?.description||''}`;
  }else if(key==='hero'){
    const h=pvData.hero||{};
    text=`H1: ${h.h1||''}\nSubline: ${h.subline||''}\nCalculator Intro: ${h.calculatorIntro||''}\nPrimärer CTA: ${h.primaryCta||''}\nSekundärer CTA: ${h.secondaryCta||''}`;
  }else if(key==='benefits'){
    const bn=Array.isArray(pvData.benefits)?pvData.benefits:[];
    text=bn.map(b=>`${b.title||''}:\n${b.text||''}`).join('\n\n');
  }else if(key==='ctaStrategy'){
    const cs=pvData.ctaStrategy||{};
    const p=(cs.primaryConversion?.ctaExamples||[]).join('\n');
    const s=(cs.secondaryConversion?.ctaExamples||[]).join('\n');
    const mc=(cs.microCtas||[]).map(m=>`${m.placement}: ${m.text}`).join('\n');
    text=`Primär (Rechner):\n${p}\n\nSekundär (Formular):\n${s}\n\nMicro-CTAs:\n${mc}`;
  }else if(key==='faq'){
    const faq=Array.isArray(pvData.faq)?pvData.faq:[];
    text=faq.map((f,i)=>`${i+1}. ${f.question||''}\n${f.answer||''}`).join('\n\n');
  }else if(key==='seoChecklist'){
    const items=Array.isArray(pvData.seoChecklist)?pvData.seoChecklist:[];
    text=items.map(i=>`[${(i.status||'ok').toUpperCase()}] ${i.item||''}${i.note?' — '+i.note:''}`).join('\n');
  }else if(key==='croChecklist'){
    const items=Array.isArray(pvData.croChecklist)?pvData.croChecklist:[];
    text=items.map(i=>`[${(i.status||'ok').toUpperCase()}] ${i.item||''}${i.note?' — '+i.note:''}`).join('\n');
  }else if(key==='recommendations'){
    const recs=Array.isArray(pvData.recommendations)?pvData.recommendations:[];
    text=recs.map(r=>`[${(r.priority||'').toUpperCase()}] ${r.module||''}: ${r.recommendation||''}`).join('\n');
  }else if(key==='exportMarkdown'){
    text=pvData.exportMarkdown||'';
  }
  navigator.clipboard.writeText(text).then(()=>{
    if(!btn)return;
    const orig=btn.innerHTML;
    btn.innerHTML='<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Kopiert';
    btn.classList.add('copied');
    setTimeout(()=>{btn.innerHTML=orig;btn.classList.remove('copied');},2000);
  });
}

// Enter-Taste im Stadt-Feld
document.getElementById('pv-city').addEventListener('keydown',e=>{if(e.key==='Enter')pvGenerate();});

// ═══════════════════════════════════════════════════════════════════════════════
// === CONTENT FINDER ===
// ═══════════════════════════════════════════════════════════════════════════════

// ── State ────────────────────────────────────────────────────────────────────
let cfTerms    = [];   // [{ term, variants:[{text,type}] }]
let cfExcludeTerms = []; // string[] — Ausschluss-Begriffe
let cfUrls     = [];   // string[]
let cfAllHits  = [];   // alle Treffer aus allen URLs
let cfRunning  = false;
let cfStopped  = false;
let cfOptions  = { plural:true, hyphen:true, umlauts:true, ai_synonyms:true, partial:true, js:true, ocr:true, case:false };

// ── Tab-Umschaltung ───────────────────────────────────────────────────────────
function cfSwitchTab(tab) {
  document.getElementById('cf-tab-manual').style.display = tab === 'manual' ? '' : 'none';
  document.getElementById('cf-tab-file').style.display   = tab === 'file'   ? '' : 'none';
  const btnManual = document.getElementById('cf-tab-btn-manual');
  const btnFile   = document.getElementById('cf-tab-btn-file');
  btnManual.style.borderBottomColor = tab === 'manual' ? 'var(--accent)' : 'transparent';
  btnManual.style.color             = tab === 'manual' ? 'var(--accent)' : 'var(--text2)';
  btnFile.style.borderBottomColor   = tab === 'file'   ? 'var(--accent)' : 'transparent';
  btnFile.style.color               = tab === 'file'   ? 'var(--accent)' : 'var(--text2)';
}

// ── Optionen-Toggle ───────────────────────────────────────────────────────────
function cfToggleOpt(el, key) {
  el.classList.toggle('on');
  cfOptions[key] = el.classList.contains('on');
  // Varianten-Vorschau aktualisieren wenn Begriffe vorhanden
  if (cfTerms.length > 0) cfShowVariantPreview(cfTerms[cfTerms.length - 1].term);
}

// ── Term-Chips ────────────────────────────────────────────────────────────────
function cfRenderChips() {
  const container = document.getElementById('cf-chips');
  container.innerHTML = '';
  cfTerms.forEach((t, i) => {
    const chip = document.createElement('span');
    chip.className = 'cf-chip';
    chip.style.cursor = 'pointer';
    chip.innerHTML = escHtml(t.term) +
      `<button class="cf-chip-remove" onclick="cfRemoveTerm(${i});event.stopPropagation()" title="Entfernen">×</button>`;
    chip.onclick = () => cfShowVariantPreview(t.term);
    container.appendChild(chip);
  });
}

function cfAddTermFromInput() {
  const inp = document.getElementById('cf-term-input');
  const term = (inp.value || '').trim();
  if (!term) return;
  cfAddTerm(term);
  inp.value = '';
  inp.focus();
}

async function cfAddTerm(term) {
  if (!term || cfTerms.some(t => t.term.toLowerCase() === term.toLowerCase())) return;
  // Sofort als Chip anzeigen (Varianten werden nachgeladen)
  cfTerms.push({ term, variants: [{ text: term, type: 'exact' }] });
  cfRenderChips();
  cfShowVariantPreview(term);
  // Varianten vom Server holen
  await cfFetchVariants(term);
}

async function cfFetchVariants(term) {
  try {
    const res = await fetch('contentfinder.php?action=synonyms', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ term, options: cfOptions, csrf_token: CSRF_TOKEN }),
    });
    if (!res.ok) return;
    const d = await res.json();
    if (!d.success) return;
    const entry = cfTerms.find(t => t.term === term);
    if (!entry) return;
    entry.variants = [...(d.variants || []), ...(d.synonyms || [])];
    cfShowVariantPreview(term);
  } catch (_) {}
}

function cfRemoveTerm(i) {
  cfTerms.splice(i, 1);
  cfRenderChips();
  if (cfTerms.length === 0) document.getElementById('cf-variant-box').style.display = 'none';
  else cfShowVariantPreview(cfTerms[Math.min(i, cfTerms.length - 1)].term);
}

// ── Ausschluss-Begriffe ───────────────────────────────────────────────────────
function cfAddExclude() {
  const inp = document.getElementById('cf-exclude-input');
  const term = (inp.value || '').trim();
  if (!term || cfExcludeTerms.some(t => t.toLowerCase() === term.toLowerCase())) { inp.value = ''; return; }
  cfExcludeTerms.push(term);
  inp.value = '';
  cfRenderExcludeChips();
  cfRenderTable(); // Tabelle sofort aktualisieren
}

function cfRemoveExclude(i) {
  cfExcludeTerms.splice(i, 1);
  cfRenderExcludeChips();
  cfRenderTable();
}

function cfRenderExcludeChips() {
  const container = document.getElementById('cf-exclude-chips');
  if (!container) return;
  container.innerHTML = cfExcludeTerms.map((t, i) =>
    `<span class="cf-chip" style="background:var(--red-bg);border:1px solid var(--red-border);color:var(--red)">
      ${escHtml(t)}
      <button class="cf-chip-remove" onclick="cfRemoveExclude(${i})" style="background:rgba(220,38,38,.15);color:var(--red)">×</button>
    </span>`
  ).join('');
}

/**
 * Prüft ob ein Treffer durch einen Ausschluss-Begriff unterdrückt werden soll.
 * Logik: Das vollständige Wort, in dem der Treffer steckt (Zeichen links+rechts des Matches
 * bis zum nächsten Leerzeichen/Satzzeichen), wird gegen alle Ausschluss-Begriffe geprüft.
 */
function cfIsExcluded(hit) {
  if (cfExcludeTerms.length === 0) return false;
  const ctx     = hit.context || '';
  const matched = hit.matched || '';
  if (!matched) return false;

  // Position des Matches im Kontext finden (case-insensitive)
  const ctxLower     = ctx.toLowerCase();
  const matchedLower = matched.toLowerCase();
  const pos = ctxLower.indexOf(matchedLower);
  if (pos === -1) return false;

  // Volles Wort um den Match herum extrahieren
  const before   = ctx.substring(0, pos);
  const after    = ctx.substring(pos + matched.length);
  const wBefore  = (before.match(/\S+$/)  || [''])[0];
  const wAfter   = (after.match(/^\S+/)   || [''])[0];
  const fullWord = (wBefore + matched + wAfter).toLowerCase();

  return cfExcludeTerms.some(ex => fullWord.includes(ex.toLowerCase()));
}

function cfShowVariantPreview(term) {
  const entry = cfTerms.find(t => t.term === term);
  const box   = document.getElementById('cf-variant-box');
  const label = document.getElementById('cf-variant-box-label');
  const chips = document.getElementById('cf-variant-chips');
  if (!entry) { box.style.display = 'none'; return; }
  box.style.display = '';
  label.textContent = 'Varianten-Vorschau · „' + entry.term + '"';
  chips.innerHTML = entry.variants.slice(0, 20).map(v =>
    `<span class="cf-badge cf-badge-${v.type}">${escHtml(v.text)}</span>`
  ).join('');
}

// ── URL-Parsing ───────────────────────────────────────────────────────────────
function cfParseUrls(text) {
  return text.split('\n')
    .map(l => l.trim())
    .filter(l => l && /^https?:\/\//i.test(l));
}

function cfHandleFileUpload(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const text = e.target.result;
    // CSV: jede Zeile die eine http-URL enthält
    const urls = text.split('\n')
      .map(l => l.replace(/[",]/g, ' ').trim())
      .filter(l => /^https?:\/\//i.test(l));
    cfUrls = urls;
    const status = document.getElementById('cf-file-status');
    status.style.display = '';
    status.textContent = urls.length > 0
      ? '✓ ' + urls.length + ' URLs erkannt aus ' + file.name
      : '⚠ Keine URLs gefunden. Spalte mit URLs muss http/https-Links enthalten.';
    status.style.color = urls.length > 0 ? 'var(--green)' : 'var(--amber)';
  };
  reader.readAsText(file);
}

// ── Analyse starten ───────────────────────────────────────────────────────────
async function cfStart() {
  if (cfRunning) return;

  // URLs sammeln
  const activeTab = document.getElementById('cf-tab-manual').style.display !== 'none' ? 'manual' : 'file';
  if (activeTab === 'manual') {
    cfUrls = cfParseUrls(document.getElementById('cf-url-input').value || '');
  }

  if (cfUrls.length === 0) { alert('Bitte mindestens eine gültige URL (http/https) eingeben.'); return; }
  if (cfTerms.length === 0) { alert('Bitte mindestens einen Suchbegriff eingeben.'); return; }

  const maxDepth = parseInt(document.getElementById('cf-depth')?.value ?? '0', 10);

  // Erlaubte Pfad-Präfixe: nur Links unterhalb der eingegebenen Seed-Pfade
  const allowedPaths = cfUrls.map(u => {
    try { return new URL(u).pathname.replace(/\/$/, ''); } catch (_) { return '/'; }
  });

  cfRunning = true;
  cfStopped = false;
  cfAllHits = [];

  // UI vorbereiten
  document.getElementById('cf-run-btn').disabled       = true;
  document.getElementById('cf-stop-btn').style.display = '';
  document.getElementById('cf-empty-state').style.display   = 'none';
  document.getElementById('cf-progress-card').style.display = '';
  document.getElementById('cf-stat-grid').style.display     = 'none';
  document.getElementById('cf-results-card').style.display  = 'none';
  document.getElementById('cf-crawl-list').innerHTML        = '';

  // BFS-Queue: [{url, depth}]
  const visited = new Set(cfUrls.map(u => cfNormalizeUrl(u)));
  const queue   = cfUrls.map(u => ({ url: u, depth: 0 }));
  let totalKnown = queue.length; // wächst bei Link-Entdeckung
  let doneCount  = 0;

  // Seed-URLs sofort in die Crawl-Liste eintragen
  queue.forEach((q, i) => cfAppendCrawlItem(q.url, i));

  while (queue.length > 0 && !cfStopped) {
    const { url, depth } = queue.shift();
    const idx = doneCount;

    // Fortschritt
    const pct = totalKnown > 0 ? Math.round((doneCount / totalKnown) * 100) : 0;
    cfSetProgress(pct, 'Analysiere ' + cfUrlShort(url) + ' …');
    document.getElementById('cf-progress-text').textContent = doneCount + ' von ' + totalKnown + ' URLs';
    document.getElementById('cf-progress-pct').textContent  = pct + ' %';

    const result = await cfCrawlUrl(url, idx);
    doneCount++;

    // Bei Crawl-Tiefe > 0: entdeckte Links in Queue aufnehmen
    if (depth < maxDepth && result && result.links && result.links.length > 0) {
      const seedDomain = cfGetDomain(cfUrls[0]);
      for (const link of result.links) {
        const norm = cfNormalizeUrl(link);
        if (!visited.has(norm) && cfGetDomain(link) === seedDomain && cfPathAllowed(link, allowedPaths)) {
          visited.add(norm);
          queue.push({ url: link, depth: depth + 1 });
          cfAppendCrawlItem(link, totalKnown);
          totalKnown++;
        }
        if (totalKnown > 200) break; // Sicherheitslimit
      }
    }

    // Fortschritt aktualisieren
    const pct2 = Math.round((doneCount / Math.max(totalKnown, 1)) * 100);
    cfSetProgress(pct2, doneCount + ' von ' + totalKnown + ' URLs analysiert');
    document.getElementById('cf-progress-text').textContent = doneCount + ' von ' + totalKnown + ' URLs';
    document.getElementById('cf-progress-pct').textContent  = pct2 + ' %';
  }

  cfFinish();
}

function cfStop() {
  cfStopped = true;
}

// Fügt ein neues URL-Element zur Crawl-Liste hinzu (dynamisch bei Link-Entdeckung)
function cfAppendCrawlItem(url, idx) {
  const list = document.getElementById('cf-crawl-list');
  const div  = document.createElement('div');
  div.className = 'cf-crawl-item';
  div.id = 'cf-ci-' + idx;
  div.innerHTML =
    `<div style="flex:1;min-width:0">
      <div class="cf-crawl-url" title="${escHtml(url)}">${escHtml(cfUrlShort(url))}</div>
      <div class="cf-substep-bar" id="cf-steps-${idx}">
        <span class="cf-substep" id="cf-s-${idx}-fetch">Abruf</span>
        <span class="cf-substep" id="cf-s-${idx}-js">JS</span>
        <span class="cf-substep" id="cf-s-${idx}-ocr">OCR</span>
        <span class="cf-substep" id="cf-s-${idx}-search">Suche</span>
      </div>
    </div>
    <span class="cf-crawl-hits" id="cf-hits-${idx}">–</span>`;
  list.appendChild(div);
}

function cfNormalizeUrl(u) {
  try { return new URL(u).href.toLowerCase().replace(/\/$/, ''); } catch (_) { return u.toLowerCase(); }
}

function cfGetDomain(u) {
  try { return new URL(u).hostname.toLowerCase(); } catch (_) { return ''; }
}

// Gibt true zurück wenn der Link unter einem der erlaubten Pfad-Präfixe liegt.
// Beispiel: allowedPaths=['/strom'] → '/strom/oekostrom' ✓, '/kontakt' ✗
function cfPathAllowed(link, allowedPaths) {
  try {
    const path = new URL(link).pathname;
    return allowedPaths.some(allowed => {
      if (!allowed || allowed === '/') return true; // Root → alles erlaubt
      return path === allowed || path.startsWith(allowed + '/');
    });
  } catch (_) { return false; }
}

function cfUrlShort(u) {
  try { const p = new URL(u); return p.hostname + p.pathname; } catch (_) { return u; }
}

// Crawlt eine URL — gibt das Backend-Ergebnis zurück (inkl. links für BFS)
async function cfCrawlUrl(url, idx) {
  const item = document.getElementById('cf-ci-' + idx);
  if (item) item.classList.add('active');
  cfSetSubstep(idx, 'fetch', 'active');

  try {
    const res = await fetch('contentfinder.php?action=crawl_url', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        url,
        term_list: cfTerms,
        options: cfOptions,
        csrf_token: CSRF_TOKEN,
      }),
    });

    cfSetSubstep(idx, 'fetch', 'done');
    cfSetSubstep(idx, 'js',    'done');
    cfSetSubstep(idx, 'ocr',   'done');
    cfSetSubstep(idx, 'search', 'active');

    if (!res.ok) {
      cfSetSubstep(idx, 'search', '');
      if (item) item.classList.remove('active');
      const hitsEl = document.getElementById('cf-hits-' + idx);
      if (hitsEl) hitsEl.textContent = 'Fehler';
      return null;
    }

    const d = await res.json();
    cfSetSubstep(idx, 'search', 'done');
    if (item) item.classList.remove('active');

    const hitsEl = document.getElementById('cf-hits-' + idx);
    if (d.success && d.hits && d.hits.length > 0) {
      d.hits.forEach(h => cfAllHits.push({ url, ...h }));
      if (hitsEl) { hitsEl.textContent = d.hits.length + ' Treffer'; hitsEl.classList.add('has-hits'); }
    } else {
      if (hitsEl) hitsEl.textContent = '0';
    }

    return d; // enthält d.links für BFS

  } catch (_) {
    cfSetSubstep(idx, 'fetch', '');
    if (item) item.classList.remove('active');
    const hitsEl = document.getElementById('cf-hits-' + idx);
    if (hitsEl) hitsEl.textContent = 'Fehler';
    return null;
  }
}

function cfSetSubstep(idx, step, state) {
  const el = document.getElementById('cf-s-' + idx + '-' + step);
  if (!el) return;
  el.className = 'cf-substep' + (state ? ' ' + state : '');
}

function cfSetProgress(pct, label) {
  document.getElementById('cf-progress-bar').style.width = pct + '%';
  if (label) document.getElementById('cf-progress-label').textContent = label;
}

function cfFinish() {
  cfRunning = false;
  document.getElementById('cf-run-btn').disabled = false;
  document.getElementById('cf-stop-btn').style.display = 'none';
  cfSetProgress(100, 'Analyse abgeschlossen');

  const ocrHits     = cfAllHits.filter(h => h.location === 'Bild-OCR' || h.location === 'Bild-Alt').length;
  const synonymHits = cfAllHits.filter(h => h.type === 'synonym').length;
  const pagesWithHits = [...new Set(cfAllHits.map(h => h.url))].length;

  // Stats
  document.getElementById('cf-stat-hits').textContent     = cfAllHits.length;
  document.getElementById('cf-stat-pages').textContent    = pagesWithHits;
  document.getElementById('cf-stat-ocr').textContent      = ocrHits;
  document.getElementById('cf-stat-synonyms').textContent = synonymHits;
  document.getElementById('cf-stat-grid').style.display   = '';

  // Filter-Dropdowns füllen
  const termSel = document.getElementById('cf-filter-term');
  termSel.innerHTML = '<option value="">Alle Begriffe</option>';
  [...new Set(cfAllHits.map(h => h.term))].forEach(t => {
    const opt = document.createElement('option');
    opt.value = t;
    opt.textContent = t;
    termSel.appendChild(opt);
  });

  // Ergebnisse zeigen
  document.getElementById('cf-results-summary').textContent =
    cfAllHits.length + ' Treffer auf ' + pagesWithHits + ' Seiten';
  document.getElementById('cf-results-card').style.display = '';
  cfRenderTable();
}

// ── Tabelle rendern ───────────────────────────────────────────────────────────
function cfRenderTable() {
  const filterText   = (document.getElementById('cf-filter-text')?.value || '').toLowerCase();
  const filterTerm   = document.getElementById('cf-filter-term')?.value   || '';
  const filterType   = document.getElementById('cf-filter-type')?.value   || '';
  const filterSource = document.getElementById('cf-filter-source')?.value || '';

  let hits = cfAllHits.filter(h => {
    if (filterTerm   && h.term     !== filterTerm)   return false;
    if (filterType   && h.type     !== filterType)   return false;
    if (filterSource && h.location !== filterSource) return false;
    if (filterText   && !(
      h.url.toLowerCase().includes(filterText)     ||
      h.term.toLowerCase().includes(filterText)    ||
      h.variant.toLowerCase().includes(filterText) ||
      h.context.toLowerCase().includes(filterText)
    )) return false;
    if (cfIsExcluded(h)) return false; // Ausschluss-Begriffe
    return true;
  });

  const MAX_ROWS = 100;
  const moreEl = document.getElementById('cf-table-more');
  if (hits.length > MAX_ROWS) {
    if (moreEl) { moreEl.style.display = ''; moreEl.textContent = (hits.length - MAX_ROWS) + ' weitere Treffer — exportiere CSV für vollständige Liste.'; }
    hits = hits.slice(0, MAX_ROWS);
  } else {
    if (moreEl) moreEl.style.display = 'none';
  }

  const tbody = document.getElementById('cf-table-body');
  if (!hits.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="cf-empty">Keine Treffer für diese Filterauswahl.</td></tr>';
    return;
  }

  tbody.innerHTML = hits.map(h => {
    const shortUrl = cfUrlShort(h.url);
    const locClass = cfLocClass(h.location);
    const ctx = escHtml(h.context).replace(
      new RegExp(escHtml(h.matched).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi'),
      m => '<mark>' + m + '</mark>'
    );
    return `<tr>
      <td><span class="cf-url-mono" title="${escHtml(h.url)}">${escHtml(shortUrl)}</span></td>
      <td><span class="cf-badge cf-badge-${h.type}">${escHtml(h.term)}</span></td>
      <td style="font-family:'Geist Mono',monospace;font-size:11px">${escHtml(h.variant)}</td>
      <td class="cf-ctx">${ctx}</td>
      <td><span class="cf-loc ${locClass}">${escHtml(h.location)}</span></td>
    </tr>`;
  }).join('');
}

function cfLocClass(loc) {
  if (!loc) return '';
  const l = loc.toLowerCase();
  if (l === 'h1') return 'h1';
  if (l.includes('bild') || l.includes('img') || l.includes('ocr') || l.includes('alt')) return 'img';
  if (l.includes('meta') || l.includes('title')) return 'meta';
  return '';
}

// ── Export ────────────────────────────────────────────────────────────────────
function cfExport(format) {
  if (!cfAllHits.length) return;
  if (format === 'csv') {
    const header = ['URL','Suchbegriff','Variante','Typ','Position','Kontext'].join(';');
    const rows = cfAllHits.map(h => [h.url, h.term, h.variant, h.type, h.location, '"' + (h.context||'').replace(/"/g,'""') + '"'].join(';'));
    const blob = new Blob(['\uFEFF' + header + '\n' + rows.join('\n')], { type: 'text/csv;charset=utf-8' });
    cfDownload(blob, 'content-finder-' + Date.now() + '.csv');
  } else if (format === 'json') {
    const blob = new Blob([JSON.stringify(cfAllHits, null, 2)], { type: 'application/json' });
    cfDownload(blob, 'content-finder-' + Date.now() + '.json');
  }
}

function cfDownload(blob, filename) {
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  a.click();
  URL.revokeObjectURL(a.href);
}

// ── Event-Listener für Term-Input ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const termInput = document.getElementById('cf-term-input');
  if (termInput) {
    termInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); cfAddTermFromInput(); } });
  }
  const excludeInput = document.getElementById('cf-exclude-input');
  if (excludeInput) {
    excludeInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); cfAddExclude(); } });
  }
  const urlInput = document.getElementById('cf-url-input');
  if (urlInput) {
    urlInput.addEventListener('input', () => {
      const urls = cfParseUrls(urlInput.value);
      const el = document.getElementById('cf-url-count');
      if (el) {
        el.style.display = urls.length > 0 ? '' : 'none';
        el.textContent   = urls.length + ' URL' + (urls.length !== 1 ? 's' : '') + ' erkannt';
      }
    });
  }
});
</script>

<!-- Agent Modal -->
<div class="agent-modal-overlay" id="agent-modal-overlay" onclick="closeAgentModal()">
  <div class="agent-modal" onclick="event.stopPropagation()">
    <div class="agent-modal-header">
      <div class="agent-modal-title">
        <h3>
          <span class="agent-dot idle" id="agent-modal-dot"></span>
          <span id="agent-modal-name"></span>
        </h3>
        <p id="agent-modal-desc"></p>
      </div>
      <button class="agent-modal-close" onclick="closeAgentModal()" title="Schließen">✕</button>
    </div>
    <div class="agent-modal-body">
      <div class="agent-modal-section">
        <label>System-Prompt</label>
        <textarea class="agent-modal-prompt" id="agent-modal-prompt" rows="10" spellcheck="false"></textarea>
      </div>
      <div class="agent-modal-section">
        <label>Letzter Output (Raw)</label>
        <pre class="agent-modal-output" id="agent-modal-output">Noch kein Analyse-Lauf.</pre>
      </div>
    </div>
    <div class="agent-modal-footer">
      <button class="btn-secondary" onclick="resetAgentPrompt()" style="font-size:12px">Auf Standard zurücksetzen</button>
      <div style="display:flex;gap:8px;align-items:center">
        <span id="agent-modal-save-msg" style="font-size:11px;color:var(--green);display:none">✓ Gespeichert</span>
        <button class="btn-primary agent-modal-save-btn" onclick="saveAgentPrompt()" style="font-size:12px;padding:6px 14px">Speichern</button>
      </div>
    </div>
  </div>
</div>

</div><!-- /content-wrap -->
</body>
</html>
