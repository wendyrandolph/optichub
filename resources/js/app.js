
import '../css/app.css';
import * as bootstrap from 'bootstrap';
// resources/js/app.js
import '../css/marketing.css'
import '@fortawesome/fontawesome-free/css/all.css?inline';
import './charts'; // add this line


/**
 * Initialize Tooltips (Example)
 */
document.addEventListener('DOMContentLoaded', () => {
  // Basic setup for a global variable if you need it later
  window.bootstrap = bootstrap;

  // Example: Initialize tooltips if you use them
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
});


// Import Axios
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';