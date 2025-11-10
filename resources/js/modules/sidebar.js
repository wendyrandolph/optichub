
// Configuration for collapsed and expanded states
const SIDEBAR_CONFIG = {
  open: { width: 'w-64', text: 'block' },
  closed: { width: 'w-20', text: 'hidden' }
};

const sidebar = document.getElementById('sidebar');
const navTexts = document.querySelectorAll('.nav-text');
const toggleButton = document.getElementById('sidebar-toggle');
const toggleIcon = toggleButton ? toggleButton.querySelector('svg') : null;

// Function to apply the state
function setSidebarState(isExpanded) {
  const state = isExpanded ? 'open' : 'closed';

  // 1. Sidebar width
  if (sidebar) {
    sidebar.classList.remove(SIDEBAR_CONFIG.open.width, SIDEBAR_CONFIG.closed.width);
    sidebar.classList.add(SIDEBAR_CONFIG[state].width);
  }

  // 2. Nav text visibility (hides or shows the words next to the icons)
  navTexts.forEach(span => {
    if (isExpanded) {
      span.classList.remove('hidden');
    } else {
      span.classList.add('hidden');
    }
  });

  // 3. Update toggle icon
  if (toggleIcon) {
    const iconName = isExpanded ? 'PanelLeftOpen' : 'Menu';
    // Update the SVG content (Lucide automatically replaces data-lucide attribute)
    toggleIcon.setAttribute('data-lucide', iconName);
    // Re-render the icon
    lucide.createIcons({ attrs: { 'data-lucide': iconName }, elements: [toggleIcon] });
  }

  // 4. Persist state to local storage
  localStorage.setItem('sidebarExpanded', isExpanded);
}

// Initialize state on load
function initializeSidebar() {
  // Check localStorage for preferred state, default to open (true)
  const storedState = localStorage.getItem('sidebarExpanded');
  const isExpanded = storedState === null ? true : storedState === 'true';

  setSidebarState(isExpanded);
  // Show the sidebar only after state is initialized to prevent FOUC (Flash of unstyled content)
  if (sidebar) {
    sidebar.classList.remove('hidden');
  }
}

// Toggle handler
if (toggleButton) {
  toggleButton.addEventListener('click', () => {
    // Check the current state (based on w-64 class presence)
    const isCurrentlyExpanded = sidebar ? sidebar.classList.contains(SIDEBAR_CONFIG.open.width) : true;
    setSidebarState(!isCurrentlyExpanded);
  });
}

// Run initialization
document.addEventListener('DOMContentLoaded', () => {
  // Replace the icons with Lucide SVG elements
  lucide.createIcons();
  initializeSidebar();
});

