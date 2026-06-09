<?php
session_start();
if (empty($_SESSION['logged_in'])) { header('Location: ../login.php'); exit; }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrfToken = $_SESSION['csrf_token'];
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
.sidebar-nav{flex:1;padding:8px}
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
.main-content{margin-left:220px;flex:1;min-width:0;background:var(--bg2)}
.workspace-header{border-bottom:1px solid var(--border);background:var(--bg2);display:flex;flex-direction:column;align-items:stretch;position:sticky;top:0;z-index:50;padding-bottom:14px}
.workspace-header-inner{max-width:960px;margin:0 auto;padding:0 32px;display:flex;align-items:center;width:100%;gap:12px;height:52px;flex-shrink:0}
.workspace-header-form{max-width:960px;margin:0 auto;padding:0 32px;width:100%}
.header-input-row{display:flex;gap:10px;align-items:center;margin-bottom:8px}
.header-action-row{display:flex;gap:8px;align-items:center;margin-top:10px}
.workspace-header-form.input-dimmed{opacity:.4;pointer-events:none;transition:opacity .3s}
.workspace-title{font-size:14px;font-weight:600;color:var(--text)}
.workspace-divider{width:1px;height:16px;background:var(--border2);flex-shrink:0}
.workspace-subtitle{font-size:12px;color:var(--text3)}
.container{max-width:960px;margin:0 auto;padding:24px 32px 48px}
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
.cluster-crit-finding{color:var(--text3);font-size:11px;line-height:1.4}
.cluster-crit-improve{margin-top:4px;font-size:11px;color:var(--accent);line-height:1.4}
.sqeg-scale{display:flex;align-items:center;margin-bottom:20px;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;background:var(--bg2)}
.sqeg-level{flex:1;padding:9px 4px;text-align:center;font-size:11px;font-weight:600;color:var(--text3);cursor:default;border-right:1px solid var(--border);transition:background .2s,color .2s}
.sqeg-level:last-child{border-right:none}
.sqeg-level.active{background:var(--accent);color:#fff}
.needs-met-block{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:20px;display:none;box-shadow:var(--shadow-sm)}
.needs-met-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);margin-bottom:10px}
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
      <span class="nav-score" id="nav-score-sqeg" style="display:none"></span>
    </button>
    <button class="nav-item" data-view="performance" onclick="showView('performance')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Performance
      <span class="nav-score" id="nav-score-perf" style="display:none"></span>
    </button>
    <button class="nav-item" data-view="geo" onclick="showView('geo')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8l4 4-4 4-4-4 4-4z"/></svg>
      GEO / AEO
      <span class="nav-score" id="nav-score-geo" style="display:none"></span>
    </button>
    <button class="nav-item" data-view="keywords" onclick="showView('keywords')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
      Keyword Fit
      <span class="nav-score" id="nav-score-kw" style="display:none"></span>
    </button>
    <button class="nav-item" data-view="ux" onclick="showView('ux')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
      UX / CRO
      <span class="nav-score" id="nav-score-ux" style="display:none"></span>
    </button>
    <div class="nav-section-label">System</div>
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
<div class="container">

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
          <span class="score-chip" id="hero-timer-chip" data-tip="Dauer der Analyse (Zeit vom Start bis zum letzten API-Call)"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> –</span>
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
    <button onclick="toggleDetailTable()" style="display:flex;align-items:center;justify-content:space-between;width:100%;margin:24px 0 0;padding:10px 16px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;font-size:12px;font-weight:600;color:var(--text2);transition:background .15s,border-color .15s" onmouseover="this.style.borderColor='var(--border2)'" onmouseout="this.style.borderColor='var(--border)'">
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
    <table class="criteria-table" id="criteria-table">
      <thead><tr><th style="width:44px">Status</th><th>Kriterium</th><th>Befund &amp; Bewertung</th><th style="width:28px"></th></tr></thead>
      <tbody id="criteria-tbody"></tbody>
    </table>
    </div><!-- /detail-table-wrap -->
    </div><!-- /detail-table-wrap -->
  </div>
  <div id="sqeg-empty" style="padding:48px 0;text-align:center;color:var(--text3)">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.4"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <div style="font-size:14px;font-weight:600;margin-bottom:4px">Noch keine Analyse</div>
    <div style="font-size:12px">URL eingeben und Analyse starten</div>
  </div>
</div><!-- /view-sqeg -->

<!-- ═══════════════════════════════════════════════════════════
     VIEW: PERFORMANCE
════════════════════════════════════════════════════════════ -->
<div class="view-panel" id="view-performance">
  <div id="perf-results" style="display:none;margin-top:24px">
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
  <div id="geo-results" style="display:none;margin-top:24px">
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
  <div id="kw-results" style="display:none;margin-top:24px">
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
  <div id="ux-results" style="display:none;margin-top:24px">
    <!-- Score Hero (UX) -->
    <div class="score-hero" id="ux-score-hero" style="margin-bottom:20px">
      <div class="score-hero-num green" id="ux-score-num">–</div>
      <div class="score-hero-divider"></div>
      <div class="score-hero-meta">
        <div class="score-hero-level green" id="ux-score-level">–</div>
        <div class="score-hero-interp" id="ux-score-interp"></div>
        <div class="score-hero-bar-wrap">
          <div class="score-hero-bar-bg"><div class="score-hero-bar green" id="ux-score-bar" style="width:0%"></div></div>
        </div>
        <div class="score-hero-chips">
          <span class="score-chip green" id="ux-chip-g" data-tip="UX-Kriterien bestanden">✓ <span id="ux-cnt-g">0</span></span>
          <span class="score-chip amber" id="ux-chip-a" data-tip="UX-Kriterien verbesserungswürdig">◑ <span id="ux-cnt-a">0</span></span>
          <span class="score-chip red" id="ux-chip-r" data-tip="UX-Kriterien kritisch">✗ <span id="ux-cnt-r">0</span></span>
        </div>
      </div>
    </div>
    <!-- Screenshot -->
    <div class="needs-met-block" id="ux-screenshot-panel" style="display:none;margin-bottom:16px">
      <div class="needs-met-label">Screenshot (1280 × 900 px)</div>
      <div id="ux-screenshot-wrap" style="margin-top:10px;border-radius:var(--radius-sm);overflow:hidden;border:1px solid var(--border)">
        <img id="ux-screenshot-img" src="" alt="Seiten-Screenshot" style="width:100%;display:block">
      </div>
    </div>
    <!-- Findings -->
    <div class="needs-met-block" id="ux-findings-panel" style="display:none">
      <div class="needs-met-label">UX-Analyse — 5 Kriterien</div>
      <div id="ux-findings-content" style="margin-top:12px"></div>
    </div>
    <!-- Summary -->
    <div class="needs-met-block" id="ux-summary-panel" style="display:none;margin-top:16px">
      <div class="needs-met-label">Gesamtbewertung</div>
      <div id="ux-summary-content" style="font-size:13px;line-height:1.6;color:var(--text2);margin-top:8px"></div>
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

// === VIEW TITLES ===
const VIEW_META={
  overview:{title:'Übersicht',sub:'Landingpage Analyse Tool'},
  sqeg:{title:'SQEG',sub:'Google Search Quality Evaluator Guidelines'},
  performance:{title:'Performance',sub:'Rankings · Sichtbarkeit · Quick Wins'},
  geo:{title:'GEO / AEO',sub:'KI-Sichtbarkeit in AI-Suchmaschinen'},
  keywords:{title:'Keyword Fit',sub:'Intent-Analyse · Targeting · Potenzial'},
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
  if(name==='overview'){
    // Progress-Section nur zeigen wenn Analyse läuft
    const ps=document.getElementById('progress-section');
    if(ps)ps.style.display=ps.dataset.active==='1'?'block':'none';
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
  const open=wrap.style.display==='none';
  wrap.style.display=open?'block':'none';
  if(icon)icon.style.transform=open?'rotate(180deg)':'';
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
let gscData=null,serpData=null,backlinkData=null,psiData=null,sistrixData=null,geoData=null,kwData=null,ucrData=null;
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
  gscData=null;serpData=null;backlinkData=null;psiData=null;sistrixData=null;geoData=null;kwData=null;ucrData=null;
  analysisStartTime=Date.now();lastPct=0;
  if(timerInterval)clearInterval(timerInterval);
  timerInterval=setInterval(updateTimer,1000);
  document.getElementById('progress-timer').textContent='';
  ['sqeg-results','perf-results','geo-results','kw-results','ux-results'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.display='none';});
  ['sqeg-empty','perf-empty','geo-empty','kw-empty','ux-empty'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.display='block';});
  showView('overview');

  const sleep=ms=>new Promise(r=>setTimeout(r,ms));
  currentUrl='https://www.beispiel-energie.de/strom/tarife';
  ymylResult='none';

  setProgress(2,'Demo-Daten laden…','Simulierte Analyse…');
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
  // Demo UX/CRO-Daten (kein echter Screenshot)
  ucrData={success:true,score:68,level:'Medium',
    summary:'Die Seite hat einen klar strukturierten Aufbau mit erkennbarem Value Proposition. Der Haupt-CTA ist sichtbar, könnte aber visuell prominenter sein. Trust-Signale sind vorhanden, wirken aber noch ausbaufähig.',
    findings:[
      {area:'Value Proposition',rating:'green',issue:'Hauptnutzen (günstiger Strom, Tarifvergleich) ist im Header klar kommuniziert.',recommendation:'Stärker emotional formulieren — z.B. "Wechsel in 5 Minuten, spare bis zu 400 €/Jahr".'},
      {area:'CTA',rating:'amber',issue:'Haupt-CTA "Tarif wählen" ist sichtbar, aber visuell nicht dominant genug.',recommendation:'Button-Farbe stärker vom Hintergrund abheben (Kontrastverhältnis ≥ 4.5:1) und Größe erhöhen.'},
      {area:'Trust-Signale',rating:'amber',issue:'Kundenbewertungen (4.7/5) vorhanden, aber zu klein und weit unten.',recommendation:'Bewertungs-Widget und TÜV-Siegel in den oberen Viewport-Bereich verschieben.'},
      {area:'Visuelle Hierarchie',rating:'green',issue:'Klare Struktur: Header → Vergleichstabelle → Vorteile → CTA. Schriftgrößen-Hierarchie eingehalten.',recommendation:'Abstände zwischen Sektionen leicht vergrößern, um Scan-Pfad zu verbessern.'},
      {area:'Above-the-Fold',rating:'red',issue:'Vergleichstabelle beginnt erst nach dem Scroll. Hero-Bereich zu groß — wichtigster Content ist nicht sofort sichtbar.',recommendation:'Hero-Bereich verkleinern oder Teaser-Zeile der Vergleichstabelle already above the fold platzieren.'},
    ],
    sub_scores:{value_prop:78,cta:62,trust:60,hierarchy:80,above_fold:48},
    screenshot_base64:null};
  log('UX/CRO: Analyse mit 5 Kriterien abgeschlossen (Demo)','ok');

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
  gscData=null;serpData=null;backlinkData=null;psiData=null;sistrixData=null;geoData=null;kwData=null;ucrData=null;
  analysisStartTime=Date.now();lastPct=0;
  if(timerInterval)clearInterval(timerInterval);
  timerInterval=setInterval(updateTimer,1000);
  document.getElementById('progress-timer').textContent='';
  ['sqeg-results','perf-results','geo-results','kw-results','ux-results'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.display='none';});
  ['sqeg-empty','perf-empty','geo-empty','kw-empty','ux-empty'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.display='block';});
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
    const [gscRes,serpRes,blRes,psiRes,sistrixRes,geoRes]=await Promise.allSettled([
      currentMode==='url'&&currentUrl?fetchGscData(currentUrl):Promise.resolve(null),
      effectiveKeyword?fetchSerpData(effectiveKeyword):Promise.resolve(null),
      currentMode==='url'&&currentUrl?fetchBacklinkData(currentUrl):Promise.resolve(null),
      currentMode==='url'&&currentUrl?fetchPageSpeedData(currentUrl):Promise.resolve(null),
      currentMode==='url'&&currentUrl?fetchSistrixData(currentUrl):Promise.resolve(null),
      currentMode==='url'&&currentUrl?fetchGeoData(currentUrl):Promise.resolve(null),
    ]);
    gscData      = gscRes.status==='fulfilled'?gscRes.value:null;
    serpData     = serpRes.status==='fulfilled'?serpRes.value:null;
    backlinkData = blRes.status==='fulfilled'?blRes.value:null;
    psiData      = psiRes.status==='fulfilled'?psiRes.value:null;
    sistrixData  = sistrixRes.status==='fulfilled'?sistrixRes.value:null;
    geoData      = geoRes.status==='fulfilled'?geoRes.value:null;

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
    // UX/CRO Analyse async (Screenshot + Vision-LLM) — läuft parallel zu SQEG-Calls
    if(currentMode==='url'&&currentUrl){
      fetchUxData(currentUrl).then(d=>{
        ucrData=d;
        if(d?.success){
          log('UX/CRO: Screenshot + Analyse abgeschlossen','ok');
          renderUXAnalysis();
          document.getElementById('ux-results').style.display='block';
          document.getElementById('ux-empty').style.display='none';
          document.getElementById('ux-loading').style.display='none';
          updateModuleCards();
        }else{
          log('UX/CRO: '+(d?.error||'Fehler'),'err');
          document.getElementById('ux-loading').style.display='none';
          document.getElementById('ux-empty').style.display='block';
        }
      }).catch(e=>{log('UX/CRO: Fehler — '+e.message,'err');document.getElementById('ux-loading').style.display='none';document.getElementById('ux-empty').style.display='block';});
      // Loading-State sofort anzeigen
      document.getElementById('ux-empty').style.display='none';
      document.getElementById('ux-loading').style.display='block';
      document.getElementById('ux-loading-msg').textContent='Screenshot wird erstellt…';
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
    },600);
  }catch(err){
    if(timerInterval){clearInterval(timerInterval);timerInterval=null;}
    document.getElementById('skeleton-wrap').style.display='none';
    log('Kritischer Fehler: '+err.message,'err');
    setProgress(0,'Fehler',err.message);
  }
  document.getElementById('btn-start').disabled=false;
  document.getElementById('btn-demo').disabled=false;
}
// === API HELPER ===
async function callApi(messages,systemPrompt,maxTokens=2000){
  const res=await fetch('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({messages,system:systemPrompt,max_tokens:maxTokens})});
  const data=await res.json();
  if(data.error)throw new Error(typeof data.error==='object'?data.error.message:data.error);
  return data.content?.[0]?.text??'';
}

// === DATEN-FETCH ===
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
async function fetchUxData(url){
  try{
    const res=await fetch('ux.php?action=analyze',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF_TOKEN},body:JSON.stringify({url,csrf_token:CSRF_TOKEN})});
    if(!res.ok)return null;
    const d=await res.json();
    return d;
  }catch(e){return null;}
}
function renderUXAnalysis(){
  if(!ucrData?.success)return;
  const score=ucrData.score||0;
  const cls=score>=70?'green':score>=50?'amber':'red';
  // Score Hero
  const numEl=document.getElementById('ux-score-num');
  const lvlEl=document.getElementById('ux-score-level');
  const barEl=document.getElementById('ux-score-bar');
  const interpEl=document.getElementById('ux-score-interp');
  if(numEl){numEl.textContent=score+'%';numEl.className='score-hero-num '+cls;}
  if(lvlEl){lvlEl.textContent=ucrData.level||'–';lvlEl.className='score-hero-level '+cls;}
  if(barEl){barEl.className='score-hero-bar '+cls;barEl.style.width=score+'%';}
  if(interpEl)interpEl.textContent=ucrData.summary||'';
  // Chips
  const findings=ucrData.findings||[];
  const cntG=findings.filter(f=>f.rating==='green').length;
  const cntA=findings.filter(f=>f.rating==='amber').length;
  const cntR=findings.filter(f=>f.rating==='red').length;
  const cntGEl=document.getElementById('ux-cnt-g');const cntAEl=document.getElementById('ux-cnt-a');const cntREl=document.getElementById('ux-cnt-r');
  if(cntGEl)cntGEl.textContent=cntG;if(cntAEl)cntAEl.textContent=cntA;if(cntREl)cntREl.textContent=cntR;
  // Screenshot
  const shotPanel=document.getElementById('ux-screenshot-panel');
  const shotImg=document.getElementById('ux-screenshot-img');
  if(ucrData.screenshot_base64&&shotPanel&&shotImg){
    shotImg.src='data:image/png;base64,'+ucrData.screenshot_base64;
    shotPanel.style.display='block';
  }else if(shotPanel){shotPanel.style.display='none';}
  // Findings
  const findPanel=document.getElementById('ux-findings-panel');
  const findContent=document.getElementById('ux-findings-content');
  if(findPanel&&findContent&&findings.length){
    findPanel.style.display='block';
    const ratingIcon={green:'✓',amber:'◑',red:'✗'};
    const ratingColor={green:'var(--green)',amber:'var(--amber)',red:'var(--red)'};
    const subScoreKey={
      'Value Proposition':'value_prop','CTA':'cta',
      'Trust-Signale':'trust','Visuelle Hierarchie':'hierarchy','Above-the-Fold':'above_fold'
    };
    let html='<div style="display:flex;flex-direction:column;gap:10px">';
    findings.forEach(f=>{
      const ic=ratingIcon[f.rating]||'◑';const col=ratingColor[f.rating]||'var(--text3)';
      const subKey=subScoreKey[f.area];const subScore=subKey&&ucrData.sub_scores?ucrData.sub_scores[subKey]:null;
      const scoreTag=subScore!=null?`<span style="font-size:10px;font-weight:700;color:${col};background:${col}18;border:1px solid ${col}44;border-radius:4px;padding:1px 6px;margin-left:6px">${subScore}%</span>`:'';
      html+=`<div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px">`;
      html+=`<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">`;
      html+=`<span style="font-size:13px;color:${col};font-weight:700">${ic}</span>`;
      html+=`<span style="font-size:13px;font-weight:600;color:var(--text)">${escHtml(f.area)}</span>${scoreTag}`;
      html+=`</div>`;
      html+=`<div style="font-size:12px;color:var(--text2);margin-bottom:4px"><strong>Befund:</strong> ${escHtml(f.issue)}</div>`;
      html+=`<div style="font-size:12px;color:var(--text3)"><strong>Empfehlung:</strong> ${escHtml(f.recommendation)}</div>`;
      html+=`</div>`;
    });
    html+='</div>';
    findContent.innerHTML=html;
  }else if(findPanel){findPanel.style.display='none';}
  // Summary
  const sumPanel=document.getElementById('ux-summary-panel');
  const sumContent=document.getElementById('ux-summary-content');
  if(sumPanel&&sumContent&&ucrData.summary){
    sumPanel.style.display='block';
    sumContent.textContent=ucrData.summary;
  }else if(sumPanel){sumPanel.style.display='none';}
  renderPagePreview();
}
function renderPagePreview(){
  if(!ucrData?.screenshot_base64)return;
  const src='data:image/png;base64,'+ucrData.screenshot_base64;
  // Übersicht-Card
  const card=document.getElementById('page-preview-card');
  const img=document.getElementById('page-preview-img');
  const urlEl=document.getElementById('page-preview-url');
  if(card&&img){img.src=src;if(urlEl)urlEl.textContent=currentUrl||'–';card.style.display='block';}
  // SQEG Score-Hero Thumbnail
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
async function fetchPageSpeedData(url){
  try{const res=await fetch('pagespeed.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({url,strategy:'mobile'})});return await res.json();}catch(e){return null;}
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
  const sys=`Du bist ein Google Search Quality Evaluator (SQEG September 2025).\nAntworte AUSSCHLIESSLICH als JSON-Array. Kein Text davor oder danach.\nFormat je Objekt: {"id":"1.1","category":"1: Seitenzweck & Seitentyp","criterion":"Name","sqeg_ref":"Sek. X.X","status":"green|amber|red","finding":"Beleg: [Signal aus HTML] | Regel: [WENN-Bedingung] | Bewertung: [Urteil]","improvement":"[konkreter Vorschlag, leer wenn green]","confidence":80}`;
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
  if(navKwEl){navKwEl.textContent=kwScore?kwScore+'%':'–';navKwEl.style.display=kwScore?'':'none';}
  if(mcKwBar){mcKwBar.style.width=kwScore+'%';mcKwBar.className='module-card-bar '+(kwScore>=70?'green':kwScore>=45?'amber':kwScore>0?'red':'neutral');}
  if(mcKwLabel)mcKwLabel.textContent=kwScore>=70?'Gutes Targeting':kwScore>=45?'Targeting verbesserbar':kwScore>0?'Targeting-Mismatch':'Noch nicht analysiert';

  // UX / CRO Score
  const uxScore=ucrData?.success?(ucrData.score||0):0;
  const mcUxEl=document.getElementById('mc-ux-score');
  const navUxEl=document.getElementById('nav-score-ux');
  const mcUxBar=document.getElementById('mc-ux-bar');
  const mcUxLabel=document.getElementById('mc-ux-label');
  const mcUxCard=document.getElementById('mc-ux');
  if(mcUxEl){mcUxEl.textContent=uxScore?uxScore+'%':'–';mcUxEl.className='module-card-score '+(uxScore>=70?'green':uxScore>=50?'amber':uxScore>0?'red':'neutral');}
  if(navUxEl){navUxEl.textContent=uxScore?uxScore+'%':'–';navUxEl.style.display=uxScore?'':'none';}
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
  const timerChip=document.getElementById('hero-timer-chip');
  const totalSec=Math.round((Date.now()-analysisStartTime)/1000);
  if(totalSec>0)timerChip.innerHTML=`<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> ${formatTime(totalSec)}`;

  document.getElementById('cnt-g').textContent=g;
  document.getElementById('cnt-a').textContent=a;
  document.getElementById('cnt-r').textContent=r;

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
  // UX/CRO — wenn ucrData bereits vorhanden (Demo-Modus)
  if(ucrData?.success){
    renderUXAnalysis();
    document.getElementById('ux-results').style.display='block';
    document.getElementById('ux-empty').style.display='none';
  }
  // Modul-Kacheln updaten
  updateModuleCards();
  // Top-Prioritäten in Übersicht
  renderTopPriorities();
  // Nach Analyse direkt zum SQEG-View
  showView('sqeg');
  generateExecSummary();
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
  if(card)card.classList.toggle('open');
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
        +`<div class="cluster-crit-meta"><div class="status-dot ${r.status}">${sym}</div><div class="cluster-crit-id">${escHtml(r.id)}</div></div>`
        +`<div class="cluster-crit-main">`
        +`<div class="cluster-crit-name">${escHtml(crit.name)}</div>`
        +(verdict?`<div class="cluster-crit-finding">${escHtml(verdict.substring(0,160)+(verdict.length>160?'\u2026':''))}</div>`:'')
        +(improve&&r.status!=='green'?`<div class="cluster-crit-improve">\u2192 ${escHtml(improve.substring(0,160)+(improve.length>160?'\u2026':''))}</div>`:'')
        +`</div></div>`;
    }).join('');
    return`<div class="cluster-card" id="${cardId}">`
      +`<div class="cluster-card-header" onclick="toggleCluster('${cardId}')">`
      +`<div class="cluster-card-donut"><svg width="96" height="96" viewBox="0 0 96 96">`
      +`<circle cx="${CX}" cy="${CY}" r="${R}" fill="none" stroke="var(--bg4)" stroke-width="${SW}"/>`
      +`<circle cx="${CX}" cy="${CY}" r="${R}" fill="none" stroke="${color}" stroke-width="${SW}" stroke-dasharray="${dash} ${circ.toFixed(1)}" stroke-linecap="round" transform="rotate(-90 ${CX} ${CY})"/>`
      +`<text x="${CX}" y="${CY}" text-anchor="middle" dominant-baseline="central" font-size="18" font-weight="700" fill="${color}" font-family="Inter,sans-serif">${score}%</text>`
      +`</svg></div>`
      +`<div class="cluster-card-info">`
      +`<div class="cluster-card-name">${escHtml(cl.name)}</div>`
      +`<div style="font-size:11px;color:var(--text3);margin-top:3px;margin-bottom:6px;font-style:italic;line-height:1.4">${escHtml(hint)}</div>`
      +`<div style="display:flex;gap:10px;font-size:12px">`
      +`<span style="color:var(--green)">${g} \u2713</span>`
      +`<span style="color:var(--amber)">${a} \u25d1</span>`
      +`<span style="color:var(--red)">${rd} \u2717</span>`
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
</script>
</body>
</html>
