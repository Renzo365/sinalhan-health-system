const fs = require('fs');
const path = require('path');

const files = [
  'admin/activity_log.php',
  'admin/archived_records.php',
  'admin/dashboard.php',
  'admin/reports.php',
  'admin/service_types.php',
  'admin/settings.php',
  'admin/users.php',
  'admin/user_add.php',
  'admin/user_edit.php',
  'appointments/add.php',
  'appointments/edit.php',
  'appointments/list.php',
  'appointments/view.php',
  'auth/change_password.php',
  'auth/profile.php',
  'auth/two_fa.php',
  'health_records/add.php',
  'health_records/edit.php',
  'health_records/list.php',
  'health_records/view.php',
  'patients/edit.php',
  'patients/list.php',
  'patients/register.php',
  'patients/register_offline.php',
  'patients/view.php',
  'queue/assign.php',
  'queue/manage.php'
];

files.forEach(f => {
  const fullPath = path.resolve('c:/xampp/htdocs/sinalhan-health-system', f);
  if (fs.existsSync(fullPath)) {
    let content = fs.readFileSync(fullPath, 'utf8');
    let replaced = false;
    
    if (content.includes('<main class="main-content">')) {
      content = content.replace('<main class="main-content">', '<main class="main-content" id="main-content">');
      replaced = true;
    }
    if (content.includes('<div class="main-content">')) {
      content = content.replace('<div class="main-content">', '<div class="main-content" id="main-content">');
      replaced = true;
    }
    
    if (replaced) {
      fs.writeFileSync(fullPath, content, 'utf8');
      console.log(`Updated ${f}`);
    }
  }
});
