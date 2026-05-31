<?php
// health_records/add.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Allowed roles: admin, staff (BHW has view-only access)
require_role(['admin', 'staff']);

$page_title = 'New Consultation';
$active_menu = 'health_records';

// No extra scripts required (native autocomplete uses global style.css)
$extra_css = [];
$extra_js = [];

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$patientId = (int)($_GET['patient_id'] ?? 0);
$patientDetails = null;

try {
    // If patient_id is provided, retrieve patient details and verify they exist and are active
    if ($patientId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ? AND is_archived = 0");
        $stmt->execute([$patientId]);
        $patientDetails = $stmt->fetch();
        
        if (!$patientDetails) {
            $_SESSION['alert'] = [
                'type' => 'warning',
                'title' => 'Patient Not Found',
                'message' => 'The requested patient profile is either archived or does not exist.'
            ];
            header('Location: ' . BASE_URL . 'health_records/list.php');
            exit;
        }
    }

    // Patient loading delegated to AJAX search to optimize database load
    $patients = [];

    // Fetch active service categories
    $servicesStmt = $pdo->query("SELECT service_id, service_name FROM service_types WHERE is_active = 1 ORDER BY service_name ASC");
    $services = $servicesStmt->fetchAll();

    // Fetch consultation templates
    $templatesStmt = $pdo->query("SELECT template_id, template_name FROM consultation_templates ORDER BY template_name ASC");
    $templates = $templatesStmt->fetchAll();

} catch (Exception $e) {
    error_log("Failed to load options for new health record: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'title' => 'System Error',
        'message' => 'An error occurred while loading form options.'
    ];
    header('Location: ' . BASE_URL . 'health_records/list.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">New Consultation & Health Record</h2>
            <p class="text-secondary mb-0">Record vital signs and clinical details for patient diagnosis and treatment.</p>
        </div>
        <div>
            <?php if ($patientDetails): ?>
                <a href="<?= BASE_URL ?>patients/view.php?id=<?= $patientDetails['patient_id'] ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Patient Profile</span>
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>health_records/list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Records List</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <form action="<?= BASE_URL ?>health_records/add_process.php" method="POST" id="newRecordForm" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="row">
            <!-- Left Side: Patient Select & Vitals Sign Panel -->
            <div class="col-lg-5 mb-4">
                
                <!-- Patient Selector Widget -->
                <div class="card-custom mb-4">
                    <div class="card-custom-header">
                        <h3 class="card-custom-title"><i class="bi bi-person-fill"></i> Patient Selector</h3>
                    </div>
                    <div class="card-custom-body">
                        <?php if ($patientDetails): ?>
                            <!-- Patient Locked in from profile -->
                            <input type="hidden" name="patient_id" value="<?= $patientDetails['patient_id'] ?>">
                            <div class="p-3 bg-light rounded-3">
                                <div class="small text-secondary mb-1">Active Patient profile</div>
                                <h4 class="fw-bold text-primary mb-1">
                                    <?= htmlspecialchars($patientDetails['last_name'] . ', ' . $patientDetails['first_name'] . ($patientDetails['middle_name'] ? ' ' . $patientDetails['middle_name'] : '') . ($patientDetails['suffix'] ? ' ' . $patientDetails['suffix'] : '')) ?>
                                </h4>
                                <p class="mb-0 text-secondary small">
                                    Sex: <strong><?= htmlspecialchars($patientDetails['sex']) ?></strong> | 
                                    Age: <strong><?= (new DateTime())->diff(new DateTime($patientDetails['birthdate']))->y ?> yrs</strong><br>
                                    Purok: <strong><?= htmlspecialchars($patientDetails['purok'] ?? 'N/A') ?></strong>
                                </p>
                            </div>
                        <?php else: ?>
                            <!-- Choose Patient Autocomplete Selector -->
                            <div class="mb-0">
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
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Vital Signs Panel -->
                <div class="card-custom">
                    <div class="card-custom-header">
                        <h3 class="card-custom-title"><i class="bi bi-heart-pulse-fill text-danger"></i> Vital Signs Metrics</h3>
                    </div>
                    <div class="card-custom-body">
                        <div class="row g-3">
                            <!-- Blood Pressure -->
                            <div class="col-sm-6">
                                <label for="blood_pressure" class="form-label font-weight-bold mb-1">Blood Pressure</label>
                                <input type="text" name="blood_pressure" id="blood_pressure" class="form-control" placeholder="e.g. 120/80">
                                <small class="text-secondary small">Format: Systolic/Diastolic</small>
                            </div>
                            
                            <!-- Temperature -->
                            <div class="col-sm-6">
                                <label for="temperature" class="form-label font-weight-bold mb-1">Temperature (°C)</label>
                                <input type="number" step="0.1" name="temperature" id="temperature" class="form-control" placeholder="e.g. 36.5" min="30" max="45">
                                <small class="text-secondary small">Body temperature in Celsius</small>
                            </div>

                            <!-- Weight -->
                            <div class="col-sm-6">
                                <label for="weight_kg" class="form-label font-weight-bold mb-1">Weight (kg)</label>
                                <input type="number" step="0.1" name="weight_kg" id="weight_kg" class="form-control" placeholder="e.g. 65.2" min="1" max="300">
                                <small class="text-secondary small">Weight in kilograms</small>
                            </div>

                            <!-- Height -->
                            <div class="col-sm-6">
                                <label for="height_cm" class="form-label font-weight-bold mb-1">Height (cm)</label>
                                <input type="number" step="0.1" name="height_cm" id="height_cm" class="form-control" placeholder="e.g. 165.0" min="20" max="250">
                                <small class="text-secondary small">Height in centimeters</small>
                            </div>

                            <!-- Heart Rate -->
                            <div class="col-sm-6">
                                <label for="heart_rate" class="form-label font-weight-bold mb-1">Heart Rate (bpm)</label>
                                <input type="number" name="heart_rate" id="heart_rate" class="form-control" placeholder="e.g. 72" min="20" max="250">
                                <small class="text-secondary small">Beats per minute</small>
                            </div>

                            <!-- Respiratory Rate -->
                            <div class="col-sm-6">
                                <label for="respiratory_rate" class="form-label font-weight-bold mb-1">Respiratory Rate</label>
                                <input type="number" name="respiratory_rate" id="respiratory_rate" class="form-control" placeholder="e.g. 18" min="5" max="100">
                                <small class="text-secondary small">Breaths per minute</small>
                            </div>

                            <!-- Real-time BMI Display Card -->
                            <div class="col-12 mt-3 pt-3 border-top">
                                <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-secondary small d-block">Calculated BMI</span>
                                        <strong id="bmi-display" class="fs-5 text-dark">—</strong>
                                    </div>
                                    <span id="bmi-badge" class="badge rounded-pill d-none" style="font-size: 11px; padding: 6px 12px;">Normal</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Side: Consultation Details -->
            <div class="col-lg-7 mb-4">
                
                <div class="card-custom h-100">
                    <div class="card-custom-header">
                        <h3 class="card-custom-title"><i class="bi bi-file-earmark-medical"></i> Consultation & Diagnosis Log</h3>
                    </div>
                    <div class="card-custom-body">
                        <div class="row g-3 mb-4">
                            <!-- Service type -->
                            <div class="col-sm-6">
                                <label for="service_id" class="form-label font-weight-bold mb-1">Service Category <span class="text-danger">*</span></label>
                                <select name="service_id" id="service_id" class="form-select" required>
                                    <option value="" disabled selected>-- Select Category --</option>
                                    <?php foreach ($services as $srv): ?>
                                        <option value="<?= $srv['service_id'] ?>">
                                            <?= htmlspecialchars($srv['service_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Visit Date -->
                            <div class="col-sm-6">
                                <label for="visit_date" class="form-label font-weight-bold mb-1">Visit Date <span class="text-danger">*</span></label>
                                <input type="date" name="visit_date" id="visit_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <!-- Consultation Templates Select -->
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <label for="template_id" class="form-label fw-bold text-primary mb-1">
                                <i class="bi bi-file-earmark-plus"></i> Use Preset Template
                            </label>
                            <select id="template_id" class="form-select">
                                <option value="" selected>-- Select a template to pre-fill clinical textareas --</option>
                                <?php foreach ($templates as $tmpl): ?>
                                    <option value="<?= $tmpl['template_id'] ?>">
                                        <?= htmlspecialchars($tmpl['template_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Chief Complaint -->
                        <div class="mb-3">
                            <label for="chief_complaint" class="form-label font-weight-bold mb-1">Chief Complaint <span class="text-danger">*</span></label>
                            <textarea name="chief_complaint" id="chief_complaint" class="form-control" rows="3" placeholder="Primary complaint / symptoms reported by patient..." required></textarea>
                        </div>

                        <!-- Diagnosis -->
                        <div class="mb-3">
                            <label for="diagnosis" class="form-label font-weight-bold mb-1">Diagnosis</label>
                            <textarea name="diagnosis" id="diagnosis" class="form-control" rows="3" placeholder="Clinical assessment / diagnostic finding..."></textarea>
                        </div>

                        <!-- Treatment -->
                        <div class="mb-3">
                            <label for="treatment" class="form-label font-weight-bold mb-1">Treatment & Procedures</label>
                            <textarea name="treatment" id="treatment" class="form-control" rows="3" placeholder="Medical procedure or care administered in-clinic..."></textarea>
                        </div>

                        <!-- Prescription -->
                        <div class="mb-3">
                            <label for="prescription" class="form-label font-weight-bold mb-1">Prescription</label>
                            <textarea name="prescription" id="prescription" class="form-control" rows="3" placeholder="Medications prescribed (name, dosage, frequency)..."></textarea>
                        </div>

                        <!-- Additional Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label font-weight-bold mb-1">Additional Clinical Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Recommendations, follow-up parameters..."></textarea>
                        </div>

                        <hr class="my-4 border-color">

                        <!-- Submission Controls -->
                        <div class="d-flex justify-content-end gap-3">
                            <?php if ($patientDetails): ?>
                                <a href="<?= BASE_URL ?>patients/view.php?id=<?= $patientDetails['patient_id'] ?>" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>health_records/list.php" class="btn btn-outline-secondary py-2 px-4 rounded-3">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary py-2 px-5 rounded-3">Save Consultation Log</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
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

    // Real-time BMI Calculator
    const weightInput = document.getElementById('weight_kg');
    const heightInput = document.getElementById('height_cm');
    const bmiDisplay = document.getElementById('bmi-display');
    const bmiBadge = document.getElementById('bmi-badge');

    function calculateBMI() {
        const weight = parseFloat(weightInput.value);
        const height = parseFloat(heightInput.value);

        if (weight > 0 && height > 0) {
            const heightM = height / 100;
            const bmi = (weight / (heightM * heightM)).toFixed(1);
            bmiDisplay.textContent = bmi + ' kg/m²';
            
            let status = 'Normal';
            let badgeClass = 'bg-success text-white';

            if (bmi < 18.5) {
                status = 'Underweight';
                badgeClass = 'bg-warning text-dark';
            } else if (bmi >= 18.5 && bmi < 25.0) {
                status = 'Normal';
                badgeClass = 'bg-success text-white';
            } else if (bmi >= 25.0 && bmi < 30.0) {
                status = 'Overweight';
                badgeClass = 'bg-danger bg-opacity-75 text-white';
            } else {
                status = 'Obese';
                badgeClass = 'bg-danger text-white';
            }

            bmiBadge.className = 'badge rounded-pill ' + badgeClass;
            bmiBadge.textContent = status;
            bmiBadge.classList.remove('d-none');
        } else {
            bmiDisplay.textContent = '—';
            bmiBadge.classList.add('d-none');
        }
    }

    if (weightInput && heightInput) {
        weightInput.addEventListener('input', calculateBMI);
        heightInput.addEventListener('input', calculateBMI);
        calculateBMI();
    }

    // Consultation Template Loader
    const templateSelect = document.getElementById('template_id');
    const complaintText = document.getElementById('chief_complaint');
    const diagnosisText = document.getElementById('diagnosis');
    const treatmentText = document.getElementById('treatment');
    const prescriptionText = document.getElementById('prescription');

    let previousTemplateVal = "";

    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            const templateId = this.value;
            if (!templateId) {
                previousTemplateVal = "";
                return;
            }

            const hasExistingContent = 
                complaintText.value.trim().length > 0 ||
                diagnosisText.value.trim().length > 0 ||
                treatmentText.value.trim().length > 0 ||
                prescriptionText.value.trim().length > 0;

            if (hasExistingContent) {
                Swal.fire({
                    title: 'Overwrite Form Fields?',
                    text: 'Selecting a preset template will overwrite any text currently entered in the Chief Complaint, Diagnosis, Treatment, and Prescription fields.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DC3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, load template',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetchTemplate(templateId);
                    } else {
                        templateSelect.value = previousTemplateVal;
                    }
                });
            } else {
                fetchTemplate(templateId);
            }
        });
    }

    function fetchTemplate(templateId) {
        fetch(`../ajax/get_template.php?template_id=${templateId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.template) {
                    const tmpl = data.template;
                    complaintText.value = tmpl.chief_complaint;
                    diagnosisText.value = tmpl.diagnosis;
                    treatmentText.value = tmpl.treatment;
                    prescriptionText.value = tmpl.prescription;
                    previousTemplateVal = templateId;
                    
                    if (typeof window.showToast === 'function') {
                        window.showToast('success', `${tmpl.name} template loaded`);
                    } else {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            timer: 3000,
                            icon: 'success',
                            title: `${tmpl.name} template loaded`,
                            showConfirmButton: false,
                            timerProgressBar: true
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Template Error',
                        text: 'Failed to fetch the template details.',
                        confirmButtonColor: '#0D7377'
                    });
                    templateSelect.value = previousTemplateVal;
                }
            })
            .catch(err => {
                console.error("Template load failure:", err);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'An error occurred while loading the template.',
                    confirmButtonColor: '#0D7377'
                });
                templateSelect.value = previousTemplateVal;
            });
    }

    const form = document.getElementById('newRecordForm');

    form.addEventListener('submit', function(e) {
        const bp = document.getElementById('blood_pressure').value.trim();
        const visitDate = new Date(document.getElementById('visit_date').value);

        // 1. Visit Date limit checking (no future visit logs)
        if (visitDate > new Date()) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Visit Date',
                text: 'Consultation visit date cannot be registered in the future.',
                confirmButtonColor: '#0D7377'
            });
            return;
        }

        // 2. BP regex formatting validation (if provided)
        if (bp && !/^\d{2,3}\/\d{2,3}$/.test(bp)) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Invalid BP Format',
                text: 'Please match the Blood Pressure format "Systolic/Diastolic" (e.g. 120/80).',
                confirmButtonColor: '#0D7377'
            });
            return;
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>
