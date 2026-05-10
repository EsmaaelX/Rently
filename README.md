# Rently 🏠

Rently is a comprehensive, modern, and highly interactive marketplace platform for renting out assets. Built with PHP and a sleek user interface, it connects asset owners with renters in a secure and professional environment.

## 🌟 Features

- **Advanced Rental Booking System:** 
  - Dynamic visual availability calendars with color-coded statuses.
  - Multi-stage approval workflows (Dual-approval).
  - Queue-based booking for pending or rejected requests.
- **Robust Listing Management:**
  - Create and edit listings with dynamic, category-specific attributes.
  - Interactive maps, representative image seeding, and robust search/filtering options.
  - Request deletion workflow for reported listings.
- **Strict Administrative Controls:**
  - Mandatory admin approval for new listings and individual booking requests before going live.
  - Comprehensive admin dashboard for overseeing users, listings, bookings, and support tickets.
- **Enhanced User Interactions:**
  - **Support Ticketing System:** Dedicated support page for users to submit and manage tickets.
  - **Verified Reviews & Reports:** Users can only review or report a listing after completing a confirmed rental, ensuring genuine feedback.
  - **AI Chatbot:** Interactive smart chatbot for instant platform assistance.
- **Premium Design & UI/UX:**
  - Responsive, high-end design featuring a premium hero section and dark mode accents.
  - Collapsible advanced search filters and smart dynamic footers based on user authentication status.
  - Multi-language support with proper RTL/LTR layout management.

## 🛠️ Technologies Used

- **Backend:** PHP 8+
- **Database:** MySQL
- **Frontend:** HTML5, Vanilla CSS3 (Custom Design System), JavaScript
- **Local Environment:** WAMP/XAMPP compatible

## 🚀 Installation & Setup

1. **Clone the Repository**
   ```bash
   git clone https://github.com/yourusername/Rently.git
   ```

2. **Move to your Web Server Directory**
   - If using WAMP, place the `Rently` folder inside your `www` directory (e.g., `C:\wamp64\www\Rently`).
   - If using XAMPP, place it in `htdocs`.

3. **Database Setup**
   - Open your MySQL administration tool (e.g., phpMyAdmin) and ensure the database service is running.
   - Navigate to `http://localhost/Rently/init_db.php` in your browser. This script will automatically:
     - Create the required database.
     - Build all the necessary tables (users, assets, bookings, reviews, reports, etc.).
     - Seed the database with sample data for immediate testing.

4. **Access the Application**
   - Open `http://localhost/Rently/index.php` in your web browser.
   - You can log in using the seeded test accounts or register a new one.

## 📖 Usage

- **As a User:** Register an account, browse available assets, add items to your wishlist, and submit a rental request. You can also contact support or use the chatbot for help.
- **As an Owner:** Add new listings with high-quality images and specific details. Manage your incoming booking requests, and interact with user reviews.
- **As an Admin:** Manage the platform. Approve or reject newly submitted listings, oversee transactions, and handle platform support tickets.

## 🤝 Contributing

Contributions, issues, and feature requests are welcome! Feel free to check the issues page.

## 📝 License

This project is licensed under the MIT License.
