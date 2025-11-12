$(document).ready(function() {
    // --- Modal Function ---
    function showNotificationModal(type, title, message) {
        const modal = $('#notificationModal');
        const iconHtml = type === 'success' 
            ? '<i class="material-icons">check_circle</i>' 
            : '<i class="material-icons">error</i>';
        
        modal.find('#modalHeaderIcon').html(iconHtml).removeClass('success error').addClass(type);
        modal.find('#modalTitle').text(title);
        modal.find('#modalMessage').text(message);
        modal.css('display', 'flex');
    }

    // --- General Modal Close Logic ---
    $('.modal .close-button, .modal .modal-close-btn').on('click', function() {
        $(this).closest('.modal').css('display', 'none');
    });
    $(window).on('click', function(event) {
        if ($(event.target).is('.modal')) {
            $(event.target).css('display', 'none');
        }
    });

    // --- FullCalendar Initialization ---
    if ($('#calendar').length) {
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek'
            },
            events: 'get_reservations.php',
            dayClick: function(date, jsEvent, view) {
                $('#block_date_start').val(date.format());
                $('#block_date_end').val('');
            },
            eventClick: function(calEvent, jsEvent, view) {
                jsEvent.preventDefault();
                if (calEvent.backgroundColor === '#28a745') {
                    const date = calEvent.start.format('YYYY-MM-DD');
                    const friendlyDate = calEvent.start.format('MMMM D, YYYY');
                    
                    $('#modalDateTitle').text(`Reservations for ${friendlyDate}`);
                    $('#modalReservationsList').html('<p>Loading...</p>');
                    $('#dateDetailsModal').css('display', 'flex');

                    $.ajax({
                        url: 'get_reservation_details.php',
                        type: 'GET',
                        data: { date: date },
                        dataType: 'json',
                        success: function(response) {
                            const list = $('#modalReservationsList');
                            list.empty();
                            if (response.success && response.reservations.length > 0) {
                                let table = '<table><thead><tr><th>Time</th><th>Name</th><th>Guests</th><th>Phone</th><th>Status</th></tr></thead><tbody>';
                                response.reservations.forEach(res => {
                                    table += `<tr><td>${res.res_time_formatted}</td><td>${res.res_name}</td><td>${res.num_guests}</td><td>${res.res_phone}</td><td><span class="status-badge ${res.status.toLowerCase()}">${res.status}</span></td></tr>`;
                                });
                                table += '</tbody></table>';
                                list.html(table);
                            } else {
                                list.html('<p>No reservation details found for this date.</p>');
                            }
                        },
                        error: function() {
                            $('#modalReservationsList').html('<p>Could not load reservation details.</p>');
                        }
                    });
                }
            }
        });
    }

    // --- Form Submission Logic (FIXED) ---
    $('#blockDateForm').on('submit', function(e) {
        e.preventDefault();
        
        const startDate = $('#block_date_start').val();
        const endDate = $('#block_date_end').val();

        if (!startDate) {
            showNotificationModal('error', 'Error', 'Please select a start date.');
            return;
        }

        $.ajax({
            url: 'blocked_dates.php',
            type: 'POST',
            data: {
                action: 'block',
                block_date_start: startDate,
                block_date_end: endDate
            },
            dataType: 'json',
            success: function(response) {
                showNotificationModal(response.success ? 'success' : 'error', response.success ? 'Success!' : 'Error', response.message);
                if (response.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            },
            error: function() {
                showNotificationModal('error', 'Error', 'An unexpected server error occurred.');
            }
        });
    });

    // --- Unblock Button Logic ---
    $(document).on('click', '.unblock-date-btn', function() {
        var dateText = $(this).closest('.blocked-date-item').find('span').text();
        var formatted_date = moment(new Date(dateText)).format('YYYY-MM-DD');

        $.ajax({
            url: 'blocked_dates.php',
            type: 'POST',
            data: {
                action: 'unblock',
                block_date: formatted_date
            },
            dataType: 'json',
            success: function(response) {
                showNotificationModal(response.success ? 'success' : 'error', response.success ? 'Success!' : 'Error', response.message);
                if (response.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            },
            error: function() {
                showNotificationModal('error', 'Error', 'An unexpected error occurred.');
            }
        });
    });
});