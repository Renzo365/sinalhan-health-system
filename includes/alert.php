<?php
// includes/alert.php

// Renders SweetAlert2 alerts from session flash data
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    $type = json_encode($alert['type']);
    $title = json_encode($alert['title']);
    $message = json_encode($alert['message']);
    
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: {$type},
                    title: {$title},
                    text: {$message},
                    confirmButtonColor: '#0D7377',
                    timer: 4000,
                    timerProgressBar: true
                });
            } else {
                console.warn('SweetAlert2 (Swal) is not defined. Falling back to native alert.');
                alert({$message});
            }
        });
    </script>
    ";
    unset($_SESSION['alert']);
}

// Print queue ticket SweetAlert dialog
if (isset($_SESSION['print_ticket'])) {
    $ticket = $_SESSION['print_ticket'];
    $number = json_encode($ticket['number']);
    $patient = json_encode($ticket['patient_name']);
    $service = json_encode($ticket['service_name']);
    $date = json_encode($ticket['date']);
    $time = json_encode($ticket['time']);
    
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Queue Ticket Issued',
                    html: `
                        <div class='text-center p-3 border rounded bg-white shadow-sm' style='font-family: monospace; border: 2px dashed #ccc !important;'>
                            <h5 class='fw-bold mb-0'>SINALHAN HEALTH CENTER</h5>
                            <small class='text-secondary'>Barangay Sinalhan, Santa Rosa City</small>
                            <hr style='border-top: 2px dashed #ccc;'>
                            <div class='my-3'>
                                <small class='text-secondary d-block'>TICKET NUMBER</small>
                                <span class='fw-bold text-primary display-4' style='letter-spacing: 2px;'>#\${{$number}}</span>
                            </div>
                            <hr style='border-top: 2px dashed #ccc;'>
                            <div class='text-start small px-2'>
                                <div class='mb-1'>Patient: <strong class='text-dark'>\${{$patient}}</strong></div>
                                <div class='mb-1'>Service: <strong class='text-dark'>\${{$service}}</strong></div>
                                <div class='mb-1'>Date: <span class='text-secondary'>\${{$date}}</span></div>
                                <div>Time: <span class='text-secondary'>\${{$time}}</span></div>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class=\"bi bi-printer-fill me-1\"></i> Print Ticket',
                    cancelButtonText: 'Close',
                    confirmButtonColor: '#0D7377',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let printContent = \`
                            <div style='text-align: center; font-family: monospace; border: 2px dashed #000; padding: 20px; width: 300px; margin: auto;'>
                                <h3 style='margin-bottom: 2px; font-size: 16px;'>SINALHAN HEALTH CENTER</h3>
                                <small style='font-size: 10px;'>Barangay Sinalhan, Santa Rosa City</small>
                                <hr style='border-top: 2px dashed #000; margin: 10px 0;'>
                                <div>
                                    <small style='font-size: 11px;'>TICKET NUMBER</small>
                                    <h1 style='font-size: 40px; margin: 5px 0;'>#\${{$number}}</h1>
                                </div>
                                <hr style='border-top: 2px dashed #000; margin: 10px 0;'>
                                <div style='text-align: left; font-size: 12px; line-height: 1.5;'>
                                    <div>Patient: <strong>\${{$patient}}</strong></div>
                                    <div>Service: <strong>\${{$service}}</strong></div>
                                    <div>Date: \${{$date}}</div>
                                    <div>Time: \${{$time}}</div>
                                </div>
                                <hr style='border-top: 2px dashed #000; margin: 10px 0;'>
                                <small style='font-size: 10px;'>Please wait for your turn.</small>
                            </div>
                        \`;
                        let originalContent = document.body.innerHTML;
                        document.body.innerHTML = printContent;
                        window.print();
                        document.body.innerHTML = originalContent;
                        window.location.reload();
                    }
                });
            }
        });
    </script>
    ";
    unset($_SESSION['print_ticket']);
}
?>
