# Barangay Sinalhan Patient Management System

A modern, web-based patient management system designed for the Barangay Sinalhan Health Center (Santa Rosa City, Laguna, Philippines). Built with native PHP, Bootstrap 5, MySQL, Progressive Web App (PWA) offline sync capabilities, Server-Sent Events (SSE) queue systems, and HIPAA-compliant AES-256 data encryption.

### 🌟 Features & Roles Matrix
For an exhaustive, module-by-module breakdown of all system features, security guidelines, and user permissions, please refer to the **[Features Catalog (FEATURES.md)](file:///c:/xampp/htdocs/sinalhan-health-system/FEATURES.md)**.

---

### Step 1: Start Services in XAMPP
1. Search for and open the **XAMPP Control Panel** on your computer.
2. Click the **Start** button next to **Apache** (Web Server).
3. Click the **Start** button next to **MySQL** (Database Server).
   * *Both module names should highlight in green, indicating they are running.*

---

### Step 2: Open the Project in VS Code
1. Open **Visual Studio Code**.
2. Go to the top menu and select **File > Open Folder...**
3. Navigate to `C:\xampp\htdocs` and select the **Patient Management System** folder.
4. Click **Select Folder**.

> [!TIP]
> **Recommended VS Code Extensions:**
> To enhance your coding experience, you can install these extensions from the VS Code Marketplace:
> * **PHP Intelephense**: For advanced PHP code completion, formatting, and analysis.
> * **PHP Debug**: Useful if you want to set breakpoints and debug variables.
> * **Bootstrap 5 Quick Snippets**: For Bootstrap utility components autocomplete.

---

### Step 3: Set Up the Database (If not yet configured)
1. Open your web browser and go to: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
2. Click on **New** in the left sidebar to create a new database.
3. Set the **Database name** to `bhc_sinalhan_db` and click **Create**.
4. With `bhc_sinalhan_db` selected, click the **Import** tab on the top menu.
5. Click **Choose File** and select the schema file located in:
   `C:\xampp\htdocs\Patient Management System\sql\bhc_sinalhan_db.sql`
6. Scroll down and click **Import** (or **Go**). This will create all the tables and seed default users/services.

---

### Step 4: Access and Use the Application
1. In your browser, navigate to: 
   [http://localhost/Patient%20Management%20System/](http://localhost/Patient%20Management%20System/)
2. Log in using one of the pre-seeded testing accounts:
   * **Administrator**: Username: `admin` | Password: `admin123`
   * **Staff Account**: Username: `staff01` | Password: `staff123`
   * **BHW Account**: Username: `bhw01` | Password: `bhw123`