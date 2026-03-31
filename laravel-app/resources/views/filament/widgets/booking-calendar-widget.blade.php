<div>
    <h3>Booking Calendar</h3>
    <div id="calendar"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: @json($events),
                eventContent: function(arg) {
                    return { html: `<span style="display:inline-block;width:32px;height:32px;border-radius:50%;background:${arg.event.backgroundColor};color:#fff;text-align:center;line-height:32px;">${arg.event.title}</span>` };
                }
            });
            calendar.render();
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
</div>
