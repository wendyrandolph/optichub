<div class="hero-section">
  <h1>Calendar</h1>

  <div class="calendar-legend">
    <span><span class="legend-box admin-complete"></span> Admin (Completed)</span>
    <span><span class="legend-box admin-incomplete"></span> Admin (Incomplete)</span>
    <span><span class="legend-box client-complete"></span> Client (Completed)</span>
    <span><span class="legend-box client-incomplete"></span> Client (Incomplete)</span>
    <span><span class="legend-box overdue"></span> Overdue Task</span>
  </div>
</div>


<div id="calendar" style="margin-top: 2rem;"></div>

<div id="calendarModal" class="calendar-modal hidden">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('calendarModal').classList.add('hidden')">&times;</span>
    <div id="calendar-modal-body"></div>
  </div>
</div>



<!-- FullCalendar CSS & JS via CDN -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,listWeek'
      },
      events: '/calendar/events',
      eventClick: function(info) {
        const {
          title,
          start,
          end,
          extendedProps
        } = info.event;

        let content = `
    <div class="modal-title">${title}</div>
    <div class="modal-subtext">${start.toLocaleString()}${end ? ' – ' + end.toLocaleString() : ''}</div>
    <hr>
  `;

        if (extendedProps.type === 'task') {
          content += `
      <p><strong>Status:</strong> ${extendedProps.status || '—'}</p>
      <p><strong>Assigned to:</strong> ${extendedProps.assigned || '—'}</p>
      <p><strong>Description:</strong><br>${extendedProps.description || '—'}</p>
    `;
        } else if (extendedProps.type === 'time') {
          content += `
      <p><strong>User:</strong> ${extendedProps.user || '—'}</p>
      <p><strong>Project:</strong> ${extendedProps.project || '—'}</p>
      <p><strong>Hours:</strong> ${extendedProps.hours || '—'}</p>
      <p><strong>Task:</strong> ${extendedProps.task || '—'}</p>
    `;
        }

        document.getElementById('calendar-modal-body').innerHTML = content;
        document.getElementById('calendarModal').classList.remove('hidden');
      }

    });

    calendar.render();
  });
</script>

<style>
  #calendar {
    background: #fff;
    padding: 1rem;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
  }
</style>