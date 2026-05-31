<?php
// appointments/add.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff, bhw (scheduling allowed for all roles)
require_role(['admin', 'staff', 'bhw']);

$page_title = 'Schedule Appointment';
$active_menu = 'appointments';

// No extra scripts required (native autocomplete uses global style.css)
$extra_css = [];
$extra_js = [];

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$patientId = (int)($_GET['patient_id'] ?? 0);
$patientDetails = null;

try {
    // If patient_id is locked from URL query
    if ($patientId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ? AND is_archived = 0");
        $stmt->execute([$patientId]);
        $patientDetails = $stmt->fetch();
        
        if (!$patientDetails) {
            $_SESSION['alert'] = [
                'type' => 'warning',
                'title' => 'Patient Not Found',
                'message' => 'The patient profile is archived or does not exist.'
            ];
            header('Location: ' . BASE_URL . 'appointments/list.php');
            exit;
        }
    }

    // Patient loading delegated to AJAX search to optimize database load
    $patients = [];

    // Fetch active service types
    $servicesStmt = $pdo->query("SELECT service_id, service_name FROM service_types WHERE is_active = 1 ORDER BY service_name ASC");
    $services = $servicesStmt->fetchAll();

} catch (Exception $e) {
    error_log("Failed to load options for new appointment: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'An error occurred while loading form options.'
    ];
    header('Location: ' . BASE_URL . 'appointments/list.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Schedule New Appointment</h2>
            <p class="text-secondary mb-0">Book a medical check-up date and time for resident patients.</p>
        </div>
        <div>
            <?php if ($patientDetails): ?>
                <a href="<?= BASE_URL ?>patients/view.php?id=<?= $patientDetails['patient_id'] ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Patient Profile</span>
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>appointments/list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Appointments</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Appointment form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-custom">
                <div class="card-custom-header">
                    <h3 class="card-custom-title"><i class="bi bi-calendar-plus-fill"></i> Appointment Booking Details</h3>
                </div>
                <div class="card-custom-body">
                    <form action="<?= BASE_URL ?>appointments/add_process.php" method="POST" id="newAppointmentForm" class="needs-validation" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <!-- Patient Selection -->
                        <div class="mb-4">
                            <?php if ($patientDetails): ?>
                                <input type="hidden" name="patient_id" value="<?= $patientDetails['patient_id'] ?>">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="small text-secondary mb-1">Target Patient Profile</div>
                                    <h4 class="fw-bold text-primary mb-1">
                                        <?= htmlspecialchars($patientDetails['last_name'] . ', ' . $patientDetails['first_name'] . ($patientDetails['middle_name'] ? ' ' . $patientDetails['middle_name'] : '') . ($patientDetails['suffix'] ? ' ' . $patientDetails['suffix'] : '')) ?>
                                    </h4>
                                    <p class="mb-0 text-secondary small">
                                        Sex: <strong><?= htmlspecialchars($patientDetails['sex']) ?></strong> | 
                                        Age: <strong><?= (new DateTime())->diff(new DateTime($patientDetails['birthdate']))->y ?> yrs</strong> |
                                        Purok: <strong><?= htmlspecialchars($patientDetails['purok'] ?? 'N/A') ?></strong>
                                    </p>
                                </div>
                            <?php else: ?>
                                <label for="patient_search_input" class="form-label font-weight-bold mb-1">Select Patient <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <input type="hidden" name="patient_id" id="patient_id" required>
                                    <div class="input-group">
                                        <span class="input-group-text bg-transparent border-end-0 text-secondary border-color" style="height: 38px;">
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <input type="text" id="patient_search_input" class="form-control border-start-0 border-color" placeholder="Type patient name to search..." autocomplete="off" style="height: 38px; box-shadow: none;" required>
                                    </div>
                                    <ul id="patientSearchSuggestions" class="dropdown-menu shadow border-0 mt-1 w-100 rounded-3 py-2" style="max-height: 280px; overflow-y: auto; display: none; position: absolute; top: 100%; left: 0; z-index: 1050; border: 1px solid var(--border-color) !important;">
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 mb-4">
                            <!-- Service type category -->
                            <div class="col-md-12">
                                <label for="service_id" class="form-label font-weight-bold mb-1">Service Type Category <span class="text-danger">*</span></label>
                                <select name="service_id" id="service_id" class="form-select" required>
                                    <option value="" disabled selected>-- Select Service --</option>
                                    <?php foreach ($services as $srv): ?>
                                        <option value="<?= $srv['service_id'] ?>">
                                            <?= htmlspecialchars($srv['service_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Appointment Date -->
                            <div class="col-md-6">
                                <label for="appointment_date" class="form-label font-weight-bold mb-1">Appointment Date <span class="text-danger">*</span></label>
                                <input type="date" name="appointment_date" id="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                                <small class="text-secondary small">Cannot schedule in the past.</small>
                            </div>

                            <!-- Appointment Time -->
                            <div class="col-md-6">
                                <label for="appointment_time" class="form-label font-weight-bold mb-1">Appointment Time</label>
                                <input type="time" name="appointment_time" id="appointment_time" class="form-control">
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <label for="reason" class="form-label font-weight-bold mb-1">Reason for Visit <span class="text-danger">*</span></label>
                            <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Describe symptoms or purpose of check-up..." required></textarea>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label font-weight-bold mb-1">Administrative Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Any additional information or reminders..."></textarea>
                        </div>

                        <hr class="my-4 border-color">

                        <!-- Action controls -->
                        <div class="d-flex justify-content-end gap-3">
                            <?php if ($patientDetails): ?>
                                <a href="<?= BASE_URL ?>patients/view.php?id=<?= $patientDetails['patient_id'] ?>" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>appointments/list.php" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Schedule Appointment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form Patient Autocomplete Selector
    const patientSearchInput = document.getElementById('patient_search_input');
    const patientSearchSuggestions = document.getElementById('patientSearchSuggestions');
    const selectedPatientId = document.getElementById('patient_id');
    let formSearchTimeout = null;

    if (patientSearchInput && patientSearchSuggestions && selectedPatientId) {
        // Keyboard actions: arrows, enter, escape
        patientSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                patientSearchSuggestions.style.display = 'none';
                patientSearchInput.blur();
                return;
            }

            const items = patientSearchSuggestions.querySelectorAll('.dropdown-item');
            if (items.length === 0) return;

            let activeIndex = -1;
            for (let i = 0; i < items.length; i++) {
                if (items[i].classList.contains('active')) {
                    activeIndex = i;
                    break;
                }
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (activeIndex !== -1) {
                    items[activeIndex].classList.remove('active', 'bg-light');
                }
                activeIndex = (activeIndex + 1) % items.length;
                items[activeIndex].classList.add('active', 'bg-light');
                items[activeIndex].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (activeIndex !== -1) {
                    items[activeIndex].classList.remove('active', 'bg-light');
                }
                activeIndex = (activeIndex - 1 + items.length) % items.length;
                items[activeIndex].classList.add('active', 'bg-light');
                items[activeIndex].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                if (activeIndex !== -1) {
                    e.preventDefault();
                    items[activeIndex].click();
                }
            }
        });

        patientSearchInput.addEventListener('input', function() {
            clearTimeout(formSearchTimeout);
            selectedPatientId.value = '';
            patientSearchInput.classList.remove('is-valid');
            
            const query = this.value.trim();

            if (query.length < 2) {
                patientSearchSuggestions.style.display = 'none';
                patientSearchSuggestions.innerHTML = '';
                return;
            }

            // Debouncer: wait 250ms
            formSearchTimeout = setTimeout(function() {
                fetch(`../ajax/search_patients.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        const results = data.results || [];
                        if (results.length > 0) {
                            let listHtml = '';
                            results.forEach(pat => {
                                const patName = pat.last_name + ', ' + pat.first_name + (pat.middle_name ? ' ' + pat.middle_name.substring(0, 1) + '.' : '') + (pat.suffix ? ' ' + pat.suffix : '');
                                listHtml += `
                                    <li>
                                        <button type="button" class="dropdown-item py-2 px-3 d-flex justify-content-between align-items-center w-100 border-0 bg-transparent text-start select-patient-btn" data-id="${pat.id}" data-name="${patName} (${pat.age} yrs, born ${pat.birthdate})">
                                            <div>
                                                <div class="fw-bold text-primary" style="font-size: 13px;">${patName}</div>
                                                <small class="text-secondary" style="font-size: 11px;">${pat.sex} | Age: ${pat.age} yrs | DOB: ${pat.birthdate}</small>
                                            </div>
                                            <span class="badge bg-light text-dark border" style="font-size: 10px;">${pat.purok || 'N/A'}</span>
                                        </button>
                                    </li>
                                `;
                            });
                            patientSearchSuggestions.innerHTML = listHtml;
                            patientSearchSuggestions.style.display = 'block';

                            // Bind clicks
                            patientSearchSuggestions.querySelectorAll('.select-patient-btn').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    const patId = this.dataset.id;
                                    const patName = this.dataset.name;
                                    
                                    selectedPatientId.value = patId;
                                    patientSearchInput.value = patName;
                                    patientSearchInput.classList.add('is-valid');
                                    patientSearchSuggestions.style.display = 'none';
                                });
                            });
                        } else {
                            patientSearchSuggestions.innerHTML = `
                                <li class="px-3 py-2 text-muted text-center" style="font-size: 12px;">
                                    <i class="bi bi-person-x me-1"></i> No patients found
                                </li>
                            `;
                            patientSearchSuggestions.style.display = 'block';
                        }
                    })
                    .catch(err => {
                        console.error("Patient selection search error:", err);
                    });
            }, 250);
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!patientSearchInput.contains(e.target) && !patientSearchSuggestions.contains(e.target)) {
                patientSearchSuggestions.style.display = 'none';
                if (!selectedPatientId.value) {
                    patientSearchInput.value = '';
                }
            }
        });

        // Show suggestions again if focused and has value
        patientSearchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2 && !selectedPatientId.value) {
                patientSearchSuggestions.style.display = 'block';
            }
        });
    }

    const form = document.getElementById('newAppointmentForm');

    form.addEventListener('submit', function(e) {
        const appDateVal = document.getElementById('appointment_date').value;
        if (!appDateVal) return;

        const appDate = new Date(appDateVal + 'T00:00:00');
        const today = new Date();
        today.setHours(0,0,0,0);

        // Client-side past date check
        if (appDate < today) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Appointment Date',
                text: 'You cannot book an appointment in the past.',
                confirmButtonColor: '#0D7377'
            });
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>
