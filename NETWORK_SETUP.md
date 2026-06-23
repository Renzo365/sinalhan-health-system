# Local Network Setup & Deployment Guide
This guide describes how to configure, deploy, and test the **Sinalhan Health Center Patient Management System** on a local, offline network. This setup is designed for environments with limited or no internet connectivity (e.g., a local clinic running a central XAMPP server with multiple client PCs, tablets, or laptops).

---

## 1. Network Topology Scenarios

Select the scenario that best matches your testing or deployment environment:

### Scenario A: Wi-Fi Router (Recommended for Mobility)
*Best if health center staff need to carry tablets or laptops around the clinic.*
```
                   [ Wi-Fi Router ]
                    /      |     \
         (Ethernet)/       |      \(Wi-Fi)
                  /     (Wi-Fi)    \
          [Server PC]  [Client 1]  [Client 2]
```
* **Server Connection:** Connect the Server PC directly to one of the router's **LAN ports** (usually yellow/black) using an Ethernet cable. Wired connection prevents dropouts.
* **Client Connections:** Connect laptops, tablets, or phones wirelessly to the router's Wi-Fi network.

### Scenario B: Ethernet Switch (Wired Desktop Fleet)
*Best for fixed desktop computers without Wi-Fi capabilities.*
```
                     [ Ethernet Switch ]
                    /    /    |    \    \
                   /    /     |     \    \
             [Server] [PC 1] [PC 2] [PC 3] [PC 4]
```
* **Connections:** Connect all PCs (Server and Clients) to any numbered ports on a 5-port or 8-port Ethernet switch using Cat5e/Cat6 RJ-45 patch cables.

### Scenario C: Mobile Hotspot (Quick Debugging/Testing)
*Best for testing immediately with your personal phone and laptop.*
* **Connections:** Enable **Mobile Hotspot** on your phone. Connect your PC/Server to your phone's hotspot Wi-Fi. (Both devices are now on the same LAN).

---

## 2. Server Static IP Configuration

To prevent the server's IP address from changing every time the router restarts, you must assign it a static IP address.

### Option A: Via Router DHCP Reservation (Best Practice)
1. Log into your Wi-Fi router's admin panel (usually `http://192.168.1.1` or `http://192.168.0.1`).
2. Navigate to **DHCP Client List** and find your Server PC.
3. Add a **DHCP Reservation** / **Static IP Lease** mapping the Server's MAC address to a permanent IP (e.g., `192.168.1.100`).

### Option B: Manually in Windows (For Switch-only Setups)
If you are using a network switch (which has no DHCP server), you must configure static IPs manually:
1. Open the **Run** dialog (`Win + R`), type `ncpa.cpl` and press **Enter** (opens Network Connections).
2. Right-click your network adapter (Ethernet or Wi-Fi) and select **Properties**.
3. Double-click **Internet Protocol Version 4 (TCP/IPv4)**.
4. Select **Use the following IP address** and enter:
   * **IP Address:** `192.168.1.10`
   * **Subnet Mask:** `255.255.255.0`
   * **Default Gateway:** *Leave blank (or enter router IP if using a router)*
5. Click **OK** to save.

---

## 3. Server Software Configuration (XAMPP / Apache)

By default, XAMPP only listens to requests coming from the host machine (`localhost`). You must allow network-wide incoming requests.

### 1. Modify `httpd.conf`
1. Open the **XAMPP Control Panel**.
2. Click the **Config** button next to **Apache** and select `httpd.conf`.
3. Locate the line:
   ```apache
   Listen 80
   ```
   Ensure it is **not** prefixed with `127.0.0.1` (which restricts requests to localhost). It should look like `Listen 80` or `Listen 0.0.0.0:80`.
4. Locate the Directory block for your document root:
   ```apache
   <Directory "C:/xampp/htdocs">
       ...
       # Look for this line:
       Require local
   </Directory>
   ```
   Change `Require local` to:
   ```apache
   Require all granted
   ```
5. Save the file (`Ctrl + S`) and **Restart** Apache.

### 2. Configure Windows Defender Firewall
Windows Defender Firewall will block incoming HTTP traffic by default.
1. Open **Windows Defender Firewall with Advanced Security** (search in start menu).
2. Click **Inbound Rules** in the left sidebar.
3. Click **New Rule...** in the right sidebar.
4. Choose **Port** -> **Next**.
5. Choose **TCP** and specify local ports: `80, 443` -> **Next**.
6. Select **Allow the connection** -> **Next**.
7. Keep all profiles checked (**Domain**, **Private**, **Public**) -> **Next**.
8. Name it `XAMPP Apache Server` and click **Finish**.

---

## 4. CRITICAL: PWA & HTTPS Requirements

Modern browsers (Chrome, Edge, Safari) **will not register Service Workers or allow IndexedDB/Sync features over HTTP** unless accessed via `localhost`. Since client devices (phones/tablets/laptops) access the system via the server's IP address (e.g., `http://192.168.1.10/...`), **the offline syncer and offline caching will fail to load** unless you use one of the options below:

### Option A: Bypass Browser Security Flags (Easiest for Testing)
For quick testing on client PCs or Android devices running Google Chrome, you can tell the browser to treat your server's IP address as secure:
1. Open Chrome on the **client device** and navigate to:
   ```
   chrome://flags/#unsafely-treat-insecure-origin-as-secure
   ```
2. Enable the flag.
3. In the text box below it, enter your server's address including the port:
   ```
   http://192.168.1.10
   ```
4. Relaunch Chrome. The Service Worker will now register and cache assets correctly.
> [!NOTE]
> This flag is not available on iOS (Safari/Chrome on iPhones). For iOS devices or production, use Option B.

### Option B: Configure Local HTTPS (Recommended for Production)
To run a fully secure offline local network, you can generate a local SSL certificate using `mkcert` and configure Apache to use HTTPS:
1. Download `mkcert` on the server PC.
2. In your terminal, run:
   ```bash
   mkcert -install
   mkcert 192.168.1.10 localhost 127.0.0.1
   ```
   *(This generates a trusted local CA and a certificate for your server's IP)*.
3. Move the generated `.pem` files to `C:/xampp/apache/conf/`.
4. Edit `C:/xampp/apache/conf/extra/httpd-ssl.conf` to point to these certificates:
   ```apache
   SSLCertificateFile "conf/192.168.1.10.pem"
   SSLCertificateKeyFile "conf/192.168.1.10-key.pem"
   ```
5. Enable SSL in XAMPP and restart Apache. Clients must import the root CA certificate onto their devices to establish trust.

---

## 5. Local Hostname Resolution (Optional)

Instead of forcing clinic staff to type an IP address like `192.168.1.10`, you can set up a user-friendly name (e.g., `http://sinalhan.local/`).

### Method A: Edit the Clients' `hosts` File
If you have a fixed set of client computers, you can map the IP on each machine:
1. On a client PC, open Notepad as **Administrator**.
2. Open the file `C:\Windows\System32\drivers\etc\hosts`.
3. Add the following line at the bottom:
   ```text
   192.168.1.10  sinalhan.local
   ```
4. Save the file. The client can now access the system by typing `http://sinalhan.local/sinalhan-health-system/`.

### Method B: Router DNS Settings
If your router supports local DNS configuration, add a DNS static lease mapping `sinalhan.local` to `192.168.1.10`. This maps the address for all devices on the network automatically.

---

## 6. Troubleshooting Connection Issues

| Issue | Potential Cause | Verification & Resolution |
|---|---|---|
| **"Site cannot be reached" (Timeout)** | Firewall block | Run `ping <SERVER_IP>` in the client's Command Prompt. If ping fails, check Windows Firewall on the server. Temporarily disable the firewall to verify. |
| **"Site cannot be reached" (Refused)** | Apache not running / wrong port | Ensure Apache is running in the XAMPP Control Panel. Verify Apache is listening on port 80 and not a custom port like 8080. |
| **IP keeps changing** | DHCP lease expired | Configure a Static IP on the server (see Section 2). |
| **"Offline Syncer" is disabled/failing** | Secure Origin requirement | Ensure you are testing via `localhost` OR using the browser bypass flags (Section 4) OR running via HTTPS. Check the browser console (`F12`) for service worker registration errors. |
| **Database error on page load** | MySQL is offline | Verify that the MySQL module is running in the Server's XAMPP control panel. The client PCs do not need MySQL installed; only the server does. |
