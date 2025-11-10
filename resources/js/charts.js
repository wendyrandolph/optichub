// resources/js/charts.js
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const fmtCurrency = v => {
  try { return '$' + Number(v).toLocaleString(); } catch { return '$' + v; }
};
const fmtPercent = v => `${v}%`;

document.addEventListener('DOMContentLoaded', initCharts);

function initCharts() {
  const canvases = document.querySelectorAll('canvas[data-chart]');
  canvases.forEach((cv) => {
    const cfgScript = cv.nextElementSibling;
    if (!cfgScript || cfgScript.tagName !== 'SCRIPT') return;

    let cfg;
    try { cfg = JSON.parse(cfgScript.textContent || '{}'); }
    catch (e) {
      console.error('[charts] JSON parse error:', e);
      return;
    }

    if (!cfg || !cfg.type) return;

    // Apply consistent styling
    cfg.options = cfg.options || {};
    cfg.options.responsive = true;
    cfg.options.maintainAspectRatio = false;

    cfg.options.plugins = cfg.options.plugins || {};
    cfg.options.plugins.legend = {
      display: true,
      position: 'bottom',
      labels: {
        boxWidth: 14,
        color: '#4B5563', // Tailwind gray-700
        font: { size: 12, family: 'Inter, system-ui, sans-serif' }
      }
    };

    cfg.options.plugins.tooltip = {
      backgroundColor: 'rgba(0,0,0,0.8)',
      padding: 10,
      cornerRadius: 6,
      titleFont: { size: 13, weight: '600' },
      bodyFont: { size: 12 }
    };

    // Apply subtle gridlines
    cfg.options.scales = cfg.options.scales || {};
    for (const axis of ['x', 'y']) {
      cfg.options.scales[axis] = cfg.options.scales[axis] || {};
      cfg.options.scales[axis].grid = {
        color: 'rgba(0,0,0,0.05)',
        drawBorder: false,
      };
      cfg.options.scales[axis].ticks = {
        color: '#6B7280', // gray-500
        font: { size: 11 },
      };
    }

    // Apply brand colors if flagged
    (cfg.data?.datasets || []).forEach(ds => {
      switch (ds._brand) {
        case 'primary':
          ds.borderColor = 'rgb(46,93,149)';
          ds.backgroundColor = 'rgba(46,93,149,0.3)';
          break;
        case 'secondary':
          ds.borderColor = 'rgb(103,156,213)';
          ds.backgroundColor = 'rgba(103,156,213,0.3)';
          break;
        case 'green':
          ds.borderColor = 'rgb(98,172,57)';
          ds.backgroundColor = 'rgba(98,172,57,0.25)';
          break;
        case 'danger':
          ds.borderColor = 'rgb(220,53,69)';
          ds.backgroundColor = 'rgba(220,53,69,0.3)';
          break;
      }

      ds.borderRadius = 6;
      ds.barPercentage = 0.85;
      ds.categoryPercentage = 0.85;
    });

    // Format y-axis labels as currency or percent if requested
    if (cfg._yCurrency) {
      cfg.options.scales.y.ticks.callback = fmtCurrency;
    }
    if (cfg._yPercent) {
      cfg.options.scales.y.ticks.callback = fmtPercent;
    }

    if (!cv.style.height) cv.style.height = '280px';

    new Chart(cv.getContext('2d'), cfg);
  });
}
