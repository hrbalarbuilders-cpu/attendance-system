<?php
// Shared responsive table styles
// Include this file in pages that have tables to get consistent styling
// NOTE: Uses CSS variables from design-system.css (loaded via header.php)
?>
<style>
  /* ===== Shared Table Styles ===== */
  .card-main {
    border-radius: var(--radius-xl, 16px);
    border: 1px solid var(--border-light, #e5e7eb);
    box-shadow: var(--shadow-md, 0 4px 12px rgba(15, 23, 42, 0.06));
    background: var(--bg-card, #fff);
    overflow: hidden;
  }

  .card-main-header {
    background: var(--bg-muted, #f9fafb);
    border-bottom: 1px solid var(--border-light, #e5e7eb);
    padding: var(--space-4, 16px) var(--space-5, 20px);
  }

  .table {
    margin-bottom: 0;
  }

  .table thead th {
    background: var(--color-accent-light, #dbeafe);
    font-weight: var(--font-semibold, 600);
    font-size: var(--text-sm, 0.85rem);
    color: #1e3a5f;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: var(--space-3, 12px) var(--space-4, 16px);
    border-bottom: 1px solid #bfdbfe;
    white-space: nowrap;
  }

  .table tbody td {
    padding: var(--space-4, 14px) var(--space-4, 16px);
    vertical-align: middle;
    border-bottom: 1px solid var(--gray-100, #f3f4f6);
    color: var(--text-primary, #374151);
  }

  .table tbody tr:last-child td {
    border-bottom: none;
  }

  .table-hover tbody tr:hover {
    background: var(--bg-muted, #f9fafb);
  }

  /* Status toggle switch */
  .switch {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
  }

  .switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #d1d5db;
    transition: .3s;
    border-radius: 999px;
  }

  .slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
  }

  input:checked+.slider {
    background-color: #111827;
  }

  input:checked+.slider:before {
    transform: translateX(24px);
  }

  /* Dark switch style (for archive toggle etc.) */
  .switch-dark {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
  }

  .switch-dark input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .slider-dark {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #111827;
    transition: .3s;
    border-radius: 999px;
  }

  .slider-dark:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
  }

  input:checked+.slider-dark {
    background-color: #111827;
  }

  input:checked+.slider-dark:before {
    transform: translateX(24px);
  }

  /* Icon switch (filter/archive toggle) */
  .switch-icon {
    position: relative;
    display: inline-block;
    width: 72px;
    height: 36px;
    cursor: pointer;
  }

  .switch-icon input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .slider-icon {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #e5e7eb;
    border-radius: 999px;
    transition: .3s;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding: 4px;
  }

  .slider-icon .icon-filter,
  .slider-icon .icon-trash {
    width: 28px;
    height: 28px;
    padding: 5px;
    border-radius: 50%;
    background: #fff;
    color: #6b7280;
    transition: background 0.3s, color 0.3s, opacity 0.3s, transform 0.3s;
    flex-shrink: 0;
    opacity: 1;
    transform: scale(1);
  }

  .slider-icon .icon-trash {
    position: absolute;
    right: 4px;
    background: transparent;
    color: transparent;
    opacity: 0;
    transform: scale(0.7);
  }

  /* When checked: show trash icon, hide filter */
  .switch-icon input:checked+.slider-icon {
    background-color: #fef2f2;
  }

  .switch-icon input:checked+.slider-icon .icon-filter {
    background: transparent;
    color: transparent;
    opacity: 0;
    transform: scale(0.7);
  }

  .switch-icon input:checked+.slider-icon .icon-trash {
    background: #fff;
    color: #dc2626;
    opacity: 1;
    transform: scale(1);
  }

  /* Action dropdown */
  .table .dropdown-menu {
    position: absolute !important;
    min-width: 140px;
    font-size: 0.85rem;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
    border: 1px solid #e5e7eb;
  }

  .table .dropdown-item {
    padding: 8px 14px;
    transition: background 0.15s;
  }

  .table .dropdown-item:hover {
    background: #f3f4f6;
  }

  /* Action button */
  .btn-action {
    background: transparent;
    border: none;
    padding: 6px 8px;
    border-radius: 6px;
    color: #6b7280;
    transition: background 0.15s, color 0.15s;
  }

  .btn-action:hover {
    background: #f3f4f6;
    color: #111827;
  }

  /* Pagination */
  .pagination {
    gap: 4px;
  }

  .pagination .page-link {
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    color: #374151;
    padding: 8px 14px;
    font-size: 0.9rem;
    transition: all 0.15s;
  }

  .pagination .page-link:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
  }

  .pagination .page-item.active .page-link {
    background: #111827;
    border-color: #111827;
    color: #fff;
  }

  .pagination .page-item.disabled .page-link {
    color: #9ca3af;
    background: transparent;
  }

  /* Empty state */
  .table-empty {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
  }

  .table-empty-icon {
    font-size: 3rem;
    margin-bottom: 12px;
    opacity: 0.5;
  }

  /* ===== Responsive Table - Card Layout on Mobile ===== */
  @media (max-width: 768px) {
    .table-responsive {
      overflow-x: auto !important;
    }

    /* Hide table header on mobile */
    .table thead {
      display: none;
    }

    /* Make table behave like block */
    .table tbody,
    .table tbody tr,
    .table tbody td {
      .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
      }

      /* Hide table header on mobile */
      .table thead {
        display: none;
      }

      /* Make table behave like block */
      .table tbody,
      .table tbody tr,
      .table tbody td {
        display: block;
        width: 100%;
      }

      /* Card style for each row */
      .table tbody tr {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        margin-bottom: 16px;
        padding: 16px 12px;
        display: flex;
        flex-direction: column;
        gap: 10px;
      }

      .table tbody td {
        border: none;
        padding: 10px 0;
        font-size: 1.05rem;
        text-align: left;
        background: none;
        word-break: break-word;
      }

      .table tbody td[data-label]::before {
        content: attr(data-label) ": ";
        font-weight: 600;
        color: #64748b;
        font-size: 0.98rem;
        display: block;
        margin-bottom: 2px;
      }

      .table tbody td:last-child {
        padding-bottom: 0;
      }

      .table-empty {
        padding: 28px 12px;
        font-size: 1.05rem;
      }

      /* Improve dropdown and switch spacing */
      .dropdown-menu {
        font-size: 0.98rem;
      }

      .switch,
      .switch-icon {
        transform: scale(0.95);
        margin-bottom: 4px;
      }

      .btn,
      .btn-sm {
        font-size: 1rem;
        padding: 8px 12px;
      }

      .section-title {
        font-size: 1.2rem !important;
      }

      .table tbody td[data-label="Employee"]::before,
      .table tbody td[data-label="Title"]::before,
      .table tbody td.cell-stack::before {
        margin-bottom: 4px;
      }

      /* Card header section */
      .card-main-header {
        padding: 12px !important;
      }

      .card-main-header .d-flex {
        flex-direction: column;
        gap: 8px;
      }

      .card-main-header input[type="search"] {
        max-width: 100% !important;
      }

      /* Section title */
      .section-title {
        font-size: 1.4rem !important;
      }

      /* Pagination responsive */
      .pagination {
        flex-wrap: wrap;
        justify-content: center;
        gap: 4px;
      }

      .pagination .page-link {
        padding: 6px 10px;
        font-size: 0.85rem;
      }
    }

    @media (max-width: 480px) {
      .table tbody tr {
        padding: 10px 12px;
      }

      .table tbody td {
        font-size: 0.9rem;
        padding: 6px 0;
      }

      .table tbody td::before {
        font-size: 0.8rem;
      }

      .section-title {
        font-size: 1.2rem !important;
      }

      /* Smaller switch on mobile */
      .switch {
        width: 44px;
        height: 24px;
      }

      .slider:before {
        height: 18px;
        width: 18px;
      }

      input:checked+.slider:before {
        transform: translateX(20px);
      }
    }

    /* ===== End Responsive Table ===== */
</style>