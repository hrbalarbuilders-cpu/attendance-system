<?php
// Shared top nav (HR / Sales / Settings) styles.
// Keep class names `.top-nav-wrapper` and `.top-nav-pill` for existing JS.
?>
<style>
  /* Row that contains the pill + inside arrows (excludes the scrollbar area) */
  .top-nav-row {
    display: flex;
    justify-content: center;
  }

  /* The pill container itself (wraps content, but won't exceed available width) */
  .top-nav-viewport {
    --topnav-accent: #111827;
    background: #ffffff;
    border-radius: 28px;
    padding: 8px;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
    display: flex;
    flex-direction: column;
    gap: 6px;
    width: max-content;
    max-width: 100%;
    box-sizing: border-box;
  }

  /* Inner row for arrows + scrollable tabs */
  .top-nav-inner-row {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  /* The scrollable area INSIDE the pill */
  .top-nav-wrapper {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    scroll-behavior: smooth;
    scrollbar-width: none; /* Firefox */
    min-width: 0;
    max-width: 100%;
  }
  .top-nav-wrapper::-webkit-scrollbar { display: none; }

  .top-nav-shell {
    position: relative;
    flex-shrink: 1;
    min-width: 0;
  }

  .top-nav-scrollbar {
    width: 100%;
    height: 6px;
    background: #e5e7eb;
    border-radius: 999px;
    overflow: hidden;
    display: block !important; /* always visible */
  }
  .top-nav-scrollbar-thumb {
    height: 100%;
    width: 100%; /* full width when no scrolling needed */
    background: #111827; /* black */
    border-radius: 999px;
    transform: translateX(0);
    will-change: transform, width;
  }

  .top-nav-arrow {
    width: 40px;
    height: 40px;
    min-width: 40px;
    min-height: 40px;
    flex-shrink: 0;
    border-radius: 999px;
    border: none;
    background: #111827; /* black */
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
    user-select: none;
  }
  .top-nav-arrow:hover {
    background: #1f2937;
  }
  .top-nav-arrow:disabled {
    opacity: 0.45;
    cursor: not-allowed;
  }

  /* Arrows are always visible (desktop + mobile). */

  .top-nav-wrapper .top-nav-pill {
    position: relative;
    appearance: none;
    border: none;
    background: transparent;
    border-radius: 999px;
    padding: 10px 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    text-decoration: none;
    white-space: nowrap;
    transition: background 0.15s ease, color 0.15s ease;
  }

  .top-nav-wrapper .top-nav-pill:hover {
    background: #111827;
    color: #ffffff;
  }

  .top-nav-wrapper .top-nav-pill.active {
    background: #111827;
    color: #ffffff;
  }

  .top-nav-wrapper .top-nav-pill .icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    font-size: 1rem;
    line-height: 1;
    opacity: 0.9;
  }
</style>

<script>
  (function () {
    function initTopNavShell(shell) {
      var scroller = shell.querySelector('.top-nav-wrapper');
      var leftBtn = shell.querySelector('[data-topnav-arrow="left"]');
      var rightBtn = shell.querySelector('[data-topnav-arrow="right"]');
      var bar = shell.querySelector('.top-nav-scrollbar');
      var thumb = shell.querySelector('.top-nav-scrollbar-thumb');
      if (!scroller || !leftBtn || !rightBtn) return;

      function updateUI() {
        var maxScrollLeft = scroller.scrollWidth - scroller.clientWidth;
        // handle floating point and negative widths
        if (maxScrollLeft < 0) maxScrollLeft = 0;
        leftBtn.disabled = scroller.scrollLeft <= 2;
        rightBtn.disabled = scroller.scrollLeft >= (maxScrollLeft - 2);

        // Update scrollbar indicator (always visible)
        if (bar && thumb) {
          var barWidth = bar.clientWidth || 0;
          var canScroll = maxScrollLeft > 2;
          if (canScroll) {
            var ratioVisible = scroller.clientWidth / scroller.scrollWidth;
            if (!isFinite(ratioVisible) || ratioVisible <= 0) ratioVisible = 1;

            // Thumb size proportional to visible area, with a minimum
            var thumbWidth = Math.max(44, Math.floor(barWidth * ratioVisible));
            if (thumbWidth > barWidth) thumbWidth = barWidth;
            thumb.style.width = thumbWidth + 'px';

            // Thumb position proportional to scrollLeft
            var maxThumbX = Math.max(0, barWidth - thumbWidth);
            var scrollRatio = maxScrollLeft > 0 ? (scroller.scrollLeft / maxScrollLeft) : 0;
            var thumbX = Math.round(maxThumbX * scrollRatio);
            thumb.style.transform = 'translateX(' + thumbX + 'px)';
          } else {
            // No scrolling needed - thumb fills entire bar
            thumb.style.width = '100%';
            thumb.style.transform = 'translateX(0)';
          }
        }
      }

      function scrollByAmount(dir) {
        var amount = Math.max(160, Math.floor(scroller.clientWidth * 0.65));
        scroller.scrollBy({ left: dir * amount, behavior: 'smooth' });
      }

      leftBtn.addEventListener('click', function () { scrollByAmount(-1); });
      rightBtn.addEventListener('click', function () { scrollByAmount(1); });
      scroller.addEventListener('scroll', updateUI, { passive: true });
      window.addEventListener('resize', updateUI);

      // Initial state
      updateUI();
      // Also update once content paints
      setTimeout(updateUI, 50);
    }

    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.top-nav-shell').forEach(initTopNavShell);
    });
  })();
</script>
