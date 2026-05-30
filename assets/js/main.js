/* assets/js/main.js */

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    
    // Check and restore sidebar collapse state from localStorage
    if (sidebar && localStorage.getItem('sidebar-collapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }

    // Toggle sidebar collapse
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // Save state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebar-collapsed', isCollapsed);
        });
    }

    // Auto-collapse sidebar on smaller screens (tablet size < 992px)
    function handleResize() {
        if (window.innerWidth < 992 && sidebar) {
            sidebar.classList.add('collapsed');
        } else if (window.innerWidth >= 992 && sidebar) {
            // Restore setting from storage on resize back to desktop
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize(); // Run once on load

    // Toast Alert Helper using SweetAlert2
    window.showToast = function(icon, title) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                icon: icon,
                title: title
            });
        }
    };

    // Initialize Select2 elements if library is loaded
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2-enable').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
});
