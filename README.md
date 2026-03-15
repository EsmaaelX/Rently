# Rently - Hybrid Sharing Economy Platform

[cite_start]Rently is a 2-Tier Client-Server web application built with PHP, MySQL, and JavaScript[cite: 55, 57]. [cite_start]It allows users to rent physical assets (cars, equipment) by the day, and spaces (apartments, sports venues) by the hour[cite: 10, 14].

## 🛠️ Prerequisites

Before you begin, ensure you have the following installed on your Windows machine:
1. [cite_start]**WampServer (WAMP):** [Download here](https://www.wampserver.com/en/) - Provides Apache, PHP, and MySQL[cite: 48].
2. [cite_start]**Visual Studio Code (VS Code):** Or any preferred IDE[cite: 53].
3. [cite_start]**Git (Optional):** If you are using GitHub to sync code with your partner[cite: 168].

---

## 🚀 Installation & Setup Guide

### Step 1: Place the Project in the WAMP Directory
1. Navigate to your WAMP installation folder. By default, this is `C:\wamp64\www\`.
2. Create a new folder named `rently`.
3. Extract or clone all the project files into this folder (`C:\wamp64\www\rently`).

### Step 2: Start WampServer
1. Launch **WampServer** from your Start Menu.
2. Check the WAMP icon in your Windows system tray (bottom right corner).
   * 🔴 **Red:** Services are stopped.
   * 🟠 **Orange:** One service is running (usually means an Apache/MySQL port conflict).
   * 🟢 **Green:** All services are running normally. **Wait for the icon to turn Green.**

### Step 3: Set Up the Database (MySQL via phpMyAdmin)
1. Open your web browser and go to: `http://localhost/phpmyadmin`
2. Log in using the default WAMP credentials:
   * **Username:** `root`
   * **Password:** *(Leave this blank)*
   * **Server Choice:** MySQL
3. Click on the **"Databases"** tab at the top.
4. [cite_start]Create a new database named: `rently_db` (Collation: `utf8mb4_general_ci` is recommended for Hebrew/English support)[cite: 28, 51].
5. Go to the **"Import"** tab, click "Choose File", select the `database.sql` file provided in this project, and click "Go" to create all the tables.

### Step 4: Configure the Database Connection
1. [cite_start]Open the project in VS Code[cite: 53].
2. Locate the database connection file (e.g., `config/db_connect.php`).
3. Ensure the credentials match your local WAMP setup:
   ```php
   <?php
   $host = 'localhost';
   $db_name = 'rently_db';
   $username = 'root'; // Default WAMP user
   $password = '';     // Default WAMP password (empty string)

   try {
       $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
       $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   } catch(PDOException $e) {
       die("Connection failed: " . $e->getMessage());
   }
   ?>
