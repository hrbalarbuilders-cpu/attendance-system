<?php
// Shared Sales navbar (for Leads, Clients, Sources etc.)
?>
<style>
.top-nav-wrapper {
  background: #ffffff;
  border-radius: 8px;
  padding: 10px 14px;
  box-shadow: 0 4px 14px rgba(15,23,42,0.06);
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}
.top-nav-wrapper .top-nav-pill {
  padding: 8px 16px;
  border-radius: 8px;
  background: transparent;
  color: #222;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  display: inline-flex;
  gap: 8px;
  align-items: center;
}
.top-nav-wrapper .top-nav-pill.active,
.top-nav-wrapper .top-nav-pill:hover {
  background: #111827;
  color: #fff;
}
.top-nav-wrapper .top-nav-pill .icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 4px;
  background: rgba(255,255,255,0.12);
  font-size: 0.9rem;
}
</style>

<div class="top-nav-wrapper">
  <a class="top-nav-pill active" href="list_leads.php"><span class="icon">📈</span> Leads</a>
  <a class="top-nav-pill" href="clients.php"><span class="icon">👥</span> Clients</a>
  <a class="top-nav-pill" href="source_of_leads.php"><span class="icon">📂</span> Source of Leads</a>
  <a class="top-nav-pill" href="looking_for.php"><span class="icon">🔎</span> Looking For</a>
</div>