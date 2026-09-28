<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
<title>Fan Hub Plus — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barriecito&family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css">
<link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
<link rel="stylesheet" href="/admin/assets/css/admin.css">
<link rel="stylesheet" href="/admin/assets/css/admin-fixes.css?v=breadcrumbs1">
</head>
<body>
<div class="app-shell">
<header class="topbar">
  <a class="brand" href="/" aria-label="Fan Hub Plus home"><span>FanHubPlus</span></a>
  <nav class="top-shortcuts" aria-label="Quick sections">
    <a href="#content" data-tip="Content"><i data-lucide="library"></i><span>Content</span></a>
    <a href="#users" data-tip="Users"><i data-lucide="users"></i><span>Users</span></a>
    <a href="#events" data-tip="Events"><i data-lucide="calendar-days"></i><span>Events</span></a>
    <a href="#submissions" data-tip="Fan Submissions"><i data-lucide="inbox"></i><span>Fan Submissions</span></a>
    <a href="#activity" data-tip="Reports"><i data-lucide="chart-no-axes-column-increasing"></i><span>Reports</span></a>
  </nav>
  <div class="profile-wrap">
    <button class="profile-btn" id="profileBtn"><span class="avatar" id="avatar">A</span><span class="profile-copy"><strong id="adminName">Loading…</strong><small id="adminEmail">Loading admin profile…</small></span><i data-lucide="chevron-down"></i></button>
    <div class="profile-menu" id="profileMenu"><a href="#account"><i data-lucide="key-round"></i>Change password</a><button data-action="logout"><i data-lucide="log-out"></i>Logout</button></div>
  </div>
</header>

<aside class="rail" id="rail">
  <button class="rail-toggle" id="railToggle"><i data-lucide="panel-left"></i><span>Navigation</span></button>
  <nav id="sideNav"></nav>
</aside>

<main class="main container">
  <section class="page active" id="page-dashboard" data-page="dashboard">
    <div class="page-head"><div><p class="eyebrow">Overview</p><h1>Content Pipeline</h1><p>Manage the Fan Hub Plus universe from one connected workspace.</p></div><button class="primary" data-create="content"><i data-lucide="plus"></i>Add content</button></div>
    <div class="dashboard-grid row g-3">
      <div class="pipeline-zone col-xl-9">
        <div class="toolbar"><div class="search"><i data-lucide="search"></i><input id="globalSearch" placeholder="Search content, characters, merch…"></div><div class="view-toggle"><button class="active" data-view="cards"><i data-lucide="layout-grid"></i></button><button data-view="list"><i data-lucide="list"></i></button></div></div>
        <div class="stats" id="stats"></div>
        <div class="section-title"><div><h2>Content Pipeline</h2><p>Published, draft and review-ready records will appear here.</p></div><a href="#content">Open library <i data-lucide="arrow-up-right"></i></a></div>
        <div class="pipeline" id="pipeline"><div class="empty-state"><span class="empty-icon"><i data-lucide="route"></i></span><h3>No content has been added yet</h3><p>Published and review-ready records from the backend will appear here.</p><button class="secondary" data-create="content"><i data-lucide="plus"></i>Add first content item</button></div></div>
      </div>
      <aside class="events-panel col-xl-3"><div class="events-head"><div><p class="eyebrow">Calendar</p><h2>Upcoming Events</h2></div><a href="#events"><i data-lucide="arrow-up-right"></i></a></div><div id="eventPreview" class="event-stack"><div class="empty-state compact"><span class="empty-icon"><i data-lucide="calendar-x-2"></i></span><h3>No upcoming events</h3><p>Upcoming events from the backend will appear here.</p><button class="secondary" data-create="events">Add event</button></div></div><div class="side-mini"><div><span>Pending submissions</span><strong data-stat="pending_submissions">—</strong></div><a href="#submissions">Review queue <i data-lucide="arrow-right"></i></a></div></aside>
    </div>
  </section>
  <section class="page" id="page-resource"><div class="page-head"><div><p class="breadcrumb-line" id="adminBreadcrumbs">Home &gt; Library &gt; Content</p><p class="eyebrow" id="resourceGroup">Library</p><h1 id="resourceTitle">Content</h1><p id="resourceDesc">Manage records from the connected backend.</p></div><button class="primary" id="addResource"><i data-lucide="plus"></i><span>Add record</span></button></div><div class="data-card"><div class="data-toolbar"><div class="search"><i data-lucide="search"></i><input id="resourceSearch" placeholder="Search backend records…"></div><div class="filter-row" id="filters"></div></div><div class="table-wrap"><table><thead id="tableHead"></thead><tbody id="tableBody"></tbody></table><div class="empty-state table-empty" id="tableEmpty"><span class="empty-icon"><i data-lucide="database"></i></span><h3>No records found</h3><p>This section has no matching records in the backend.</p></div></div></div></section>
</main>

<nav class="quickbar" aria-label="Quick add"><span class="quickbar-label">Quick add</span><button data-create="articles" data-tooltip="Add article" aria-label="Add article"><i data-lucide="newspaper"></i></button><button data-create="characters" data-tooltip="Add character" aria-label="Add character"><i data-lucide="user-round"></i></button><button data-create="merchandise" data-tooltip="Add merchandise" aria-label="Add merchandise"><i data-lucide="shopping-bag"></i></button><button data-create="events" data-tooltip="Add event" aria-label="Add event"><i data-lucide="calendar-plus"></i></button><button data-create="feedback" data-tooltip="Review feedback" aria-label="Review feedback"><i data-lucide="message-circle-reply"></i></button><button class="more" data-create="content" data-tooltip="Quick add" aria-label="Quick add"><i data-lucide="plus"></i></button></nav>

<div class="modal-backdrop" id="modalBackdrop"><div class="modal"><div class="modal-head"><div><p class="breadcrumb-line" id="adminModalBreadcrumbs">Home &gt; Library &gt; Record</p><p class="eyebrow">Backend record</p><h2 id="modalTitle">Add content</h2></div><button class="icon-btn" id="closeModal"><i data-lucide="x"></i></button></div><form id="recordForm" enctype="multipart/form-data"><div class="form-grid" id="formFields"></div><div class="modal-actions"><button type="button" class="secondary" id="cancelModal">Cancel</button><button type="submit" class="primary"><i data-lucide="save"></i>Save record</button></div></form></div></div>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script><script src="/admin/assets/js/admin.js?v=breadcrumbs1"></script><script src="/admin/assets/js/admin-fixes.js?v=notification1"></script>
</body></html>
