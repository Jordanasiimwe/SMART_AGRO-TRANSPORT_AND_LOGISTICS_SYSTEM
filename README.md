Project Title
Smart Agro-Transport and Logistics System (SATLS)

Existing Problem 
Smallholder farmers supplying agricultural produce to Nakawa Market face significant challenges due to the absence of a centralized digital platform for coordinating trade with market vendors. Most transactions rely on manual communication methods, personal contacts, and middlemen, resulting in limited access to real-time market information, price manipulation, uncertainty about demand, and frequent post-harvest losses caused by unsold produce. At the same time, vendors struggle to obtain reliable information about available produce, making it difficult to plan purchases and maintain a consistent supply of agricultural products. These inefficiencies reduce farmers' incomes, disrupt vendors' operations, and negatively affect the overall efficiency of the agricultural supply chain.

 Solution to the problem
The Smart Agro-Transport and Logistics System (SATLS) addresses these challenges by providing a web-based platform that directly connects farmers with vendors at Nakawa Market. The system enables farmers to register, list available produce, and receive notifications, while vendors can post their produce requirements, browse available products, and place orders. Through a centralized database, role-based access control, and SMS notifications, SATLS facilitates real-time information sharing, improves coordination between supply and demand, enhances price transparency, reduces dependence on intermediaries, and supports more efficient agricultural trade. By streamlining communication and market access, the system contributes to reduced post-harvest losses and improved market opportunities for both farmers and vendors.




# Smart AgroLink System
**Connecting Farmers to Markets**

> ### Scope Optimization Notice
> This project was originally initiated under the title **Smart Agro Transport and Logistics System**. Following academic presentation feedback, the project scope was intentionally streamlined to eliminate the transport module but we maintained the project name since it was already submitted and approved. 

> The platform's finalized scope focuses exclusively on engineering a robust digital marketplace that bridges the communication and supply gap between rural farmers and urban marketplace vendors, with a localized implementation baseline targeting **Nakawa Market**.


Smart AgroLink System is a comprehensive web-based platform designed to bridge the gap between farmers and vendors. It allows farmers to list their produce and manage orders, while vendors can browse fresh produce, place orders, and track deliveries. The system includes robust administrative tools for user management, financial tracking, and system maintenance.

##  Key Features

###  Farmer Portal
*   **Product Management**: Add, edit, and delete agricultural produce listings with images and pricing.
*   **Order Management**: View incoming orders and track their status.
*   **Profile & Location**: Manage farm details and set geolocation coordinates for distance calculations.
*   **Communication**: Send and receive messages from vendors and admins.

###  Vendor Portal
*   **Marketplace**: Browse available produce from various farmers.
*   **Shopping Cart**: Add items to a cart and checkout using Mobile Money or Cash on Delivery.
*   **Order History**: Track past purchases and view order status (Pending, Approved, Completed).
*   **Direct Contact**: Contact farmers directly via the platform or SMS integration.

###  Admin Dashboard
*   **Statistical Overview**: Visual charts using Chart.js to monitor order statuses (Pending vs Approved vs Cancelled).
*   **User Management**: View, approve, or disable farmer and vendor accounts.
*   **Financial Tracking**: Monitor total sales and the accumulated maintenance fund (1% system fee).
*   **System Maintenance**: Toggle "Maintenance Mode" to restrict user access during updates.
*   **Broadcasts**: Send bulk SMS alerts to all users.

### 🌐 General Features
*   **Localization**: Built-in support for English, Luganda, and Swahili via Google Translate.
*   **Security**: CSRF protection, Password Hashing, and SQL Injection prevention using PDO.
*   **Responsiveness**: Mobile-friendly design for use in the field or market.
*   **Maps Integration**: Redirection to Google Maps for navigation between markets and farms.

##  Technology Stack
*   **Backend**: PHP (Native/OOP)
*   **Database**: MySQL
*   **Frontend**: HTML5, CSS3, JavaScript (Chart.js)
*   **Environment**: Apache Web Server (XAMPP recommended)

##  Installation & Setup

### 1. Prerequisites
*   XAMPP or any PHP/MySQL web server environment.
*   Git (optional).

### 2. File Placement
1.  Copy the project folder into your web server's root directory (e.g., `C:\xampp\htdocs\uict`).

### 3. Database Configuration
1.  Open phpMyAdmin (usually `http://localhost/phpmyadmin`).
2.  Create a new database named `agriconnect_db`.
3.  Import the provided SQL schema (not included in this repo, ensure tables `users`, `farmers`, `vendors`, `products`, `orders`, `order_items`, `messages`, `withdrawals`, `feedback` exist).

### 4. Environment Setup
1.  Navigate to the project root `uict/`.
2.  Create a file named `.env` (or rename `.env.example` if available).
3.  Add your database credentials:
    ```env
    DB_HOST=sql205.infinityfree.com
    DB_NAME=if0_42138888_agriconnect
    DB_USER=if0_42138888
    DB_PASS=VAUhmyFGg7HE
    ```

### 5. Running the Application

1.  Open your brower.
2.  open the link"https://smartagro-linksystem.com".
3.        OR
4.  Start Apache and MySQL in XAMPP.
5.  Open your browser and navigate to:
    `http://localhost/uict/`

## 👥 The Team (Class of 2026)

This project was designed and developed by:

1.  **Icarit David**
2.  **Asiimwe Jordan**
3.  **Nahigo Racheal**
4.  **Nagginda Shirat**
5.  **Tusiime Rhoda**

##  Default Credentials (For Testing)

*   **Admin**:
    *   Username: `admin`
    *   Password: admin123
*   **Farmer**:
    *   Username: King_David
    *   Password: 0761382827
*   **Vendor**:
    *   Username: `vendor`
    *   Password: *(As configured in your database)*

---
&copy; 2026 Smart AgroLink System. All Rights Reserved.
