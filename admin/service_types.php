<?php
// admin/service_types.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/role_guard.php';

// Enforce admin-only access
require_role(['admin']);

$page_title = 'Service Categories Management';
$active_menu = 'services';

// Load DataTables styles and scripts via CDN
$extra_css = ['https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css'];
$extra_js = [
    'https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js'
];

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

try {
    // Fetch all service categories ordered alphabetically
    $stmt = $pdo->query("SELECT service_id, service_name, description, is_active FROM service_types ORDER BY service_name ASC");
    $services = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Service types fetch failure: " . $e->getMessage());
    $services = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Service Categories</h2>
            <p class="text-secondary mb-0">Define health services offered by the health center (e.g. Prenatal, Immunization).</p>
        </div>
        <div>
            <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Add Category</span>
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card-custom">
        <div class="card-custom-header">
            <h3 class="card-custom-title"><i class="bi bi-heart-pulse-fill"></i> Medical Service Categories</h3>
        </div>
        <div class="card-custom-body">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle" id="servicesTable">
                    <thead>
                        <tr>
                            <th>Service Category Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th class="text-center" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($s['service_name']) ?></strong></td>
                                <td class="text-secondary"><?= htmlspecialchars($s['description'] ?? 'No description provided.') ?></td>
                                <td>
                                    <?php if ($s['is_active'] == 1): ?>
                                        <span class="badge-custom badge-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge-custom badge-inactive"><i class="bi bi-dash-circle-fill"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- Edit Modal Trigger -->
                                        <button class="btn btn-sm btn-outline-primary border-0 p-1 edit-service-btn" 
                                                data-id="<?= $s['service_id'] ?>"
                                                data-name="<?= htmlspecialchars($s['service_name']) ?>"
                                                data-desc="<?= htmlspecialchars($s['description'] ?? '') ?>"
                                                title="Edit Service Details">
                                            <i class="bi bi-pencil-square fs-5"></i>
                                        </button>
                                        
                                        <!-- Toggle active status -->
                                        <?php if ($s['is_active'] == 1): ?>
                                            <button class="btn btn-sm btn-outline-danger border-0 p-1 toggle-status-btn"
                                                    data-id="<?= $s['service_id'] ?>"
                                                    data-name="<?= htmlspecialchars($s['service_name']) ?>"
                                                    data-status="0"
                                                    title="Deactivate Category">
                                                <i class="bi bi-x-circle-fill fs-5"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-success border-0 p-1 toggle-status-btn"
                                                    data-id="<?= $s['service_id'] ?>"
                                                    data-name="<?= htmlspecialchars($s['service_name']) ?>"
                                                    data-status="1"
                                                    title="Activate Category">
                                                <i class="bi bi-check-circle-fill fs-5"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</main>

<!-- Modal: Add Service -->
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-primary text-white border-0 py-3" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                <h5 class="modal-title font-weight-bold" id="addServiceModalLabel">
                    <i class="bi bi-plus-circle-fill me-2"></i> Add Service Category
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>admin/service_type_process.php" method="POST" id="addServiceForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="mb-3">
                        <label for="add_service_name" class="form-label font-weight-bold mb-1">Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="service_name" id="add_service_name" class="form-control" placeholder="e.g. Immunization" required>
                    </div>

                    <div class="mb-2">
                        <label for="add_description" class="form-label font-weight-bold mb-1">Description</label>
                        <textarea name="description" id="add_description" class="form-control" rows="3" placeholder="Brief description of the service..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                    <button type="button" class="btn btn-secondary py-2 px-3 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary py-2 px-4 rounded-3">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Service -->
<div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-primary text-white border-0 py-3" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                <h5 class="modal-title font-weight-bold" id="editServiceModalLabel">
                    <i class="bi bi-pencil-square me-2"></i> Modify Service Category
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>admin/service_type_process.php" method="POST" id="editServiceForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="service_id" id="edit_service_id" value="">

                    <div class="mb-3">
                        <label for="edit_service_name" class="form-label font-weight-bold mb-1">Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="service_name" id="edit_service_name" class="form-control" placeholder="e.g. Dental Care" required>
                    </div>

                    <div class="mb-2">
                        <label for="edit_description" class="form-label font-weight-bold mb-1">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3" placeholder="Brief description of the service..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                    <button type="button" class="btn btn-secondary py-2 px-3 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary py-2 px-4 rounded-3">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden form for toggling status -->
<form action="<?= BASE_URL ?>admin/service_type_process.php" method="POST" id="toggleStatusForm" style="display: none;">
    <input type="hidden" name="action" value="toggle_status">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="service_id" id="status_service_id" value="">
    <input type="hidden" name="status" id="status_val" value="">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize DataTable
    if ($.fn.DataTable) {
        $('#servicesTable').DataTable({
            responsive: true,
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 3 } // Disable sorting on action column
            ],
            order: [[0, 'asc']] // Sort alphabetically
        });
    }

    // 2. Event delegation for table action buttons
    const editModal = new bootstrap.Modal(document.getElementById('editServiceModal'));
    const servicesTable = document.getElementById('servicesTable');

    if (servicesTable) {
        servicesTable.addEventListener('click', function(e) {
            // Edit button handler
            const editBtn = e.target.closest('.edit-service-btn');
            if (editBtn) {
                const id = editBtn.getAttribute('data-id');
                const name = editBtn.getAttribute('data-name');
                const desc = editBtn.getAttribute('data-desc');

                document.getElementById('edit_service_id').value = id;
                document.getElementById('edit_service_name').value = name;
                document.getElementById('edit_description').value = desc;

                editModal.show();
                return;
            }

            // Status toggle button handler
            const toggleBtn = e.target.closest('.toggle-status-btn');
            if (toggleBtn) {
                const id = toggleBtn.getAttribute('data-id');
                const name = toggleBtn.getAttribute('data-name');
                const status = toggleBtn.getAttribute('data-status'); // 1 = activate, 0 = deactivate
                const actionText = status === '1' ? 'activate' : 'deactivate';
                const buttonColor = status === '1' ? '#28A745' : '#DC3545';

                Swal.fire({
                    title: `Are you sure?`,
                    text: `You are about to ${actionText} the service category '${name}'.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: buttonColor,
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: `Yes, ${actionText} it!`
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('status_service_id').value = id;
                        document.getElementById('status_val').value = status;
                        document.getElementById('toggleStatusForm').submit();
                    }
                });
            }
        });
    }
});
</script>

<?php
// Load SweetAlert session alerts
require_once __DIR__ . '/../includes/alert.php';
require_once __DIR__ . '/../includes/footer.php';
?>
