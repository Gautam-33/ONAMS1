# Online Appointment Management System (ONAMS)

A web-based appointment booking system for hospitals with intelligent doctor recommendations using Cosine Similarity Algorithm and conflict detection.

## Features

### Patient Features
- 🔐 Secure registration and login
- 🏥 Browse and search doctors by specialization
- 🤖 AI-powered doctor recommendations (Cosine Similarity)
- 📅 Book appointments with conflict detection
- 📋 View appointment history
- ❌ Cancel appointments
- 🖨️ Print appointment receipts

### Admin Features
- 🔐 Secure admin dashboard
- 👨‍⚕️ Add/Edit/Delete doctors
- 📅 Manage doctor schedules
- 👀 View all appointments
- 📊 Dashboard with statistics
- 👥 View registered patients

### Smart Algorithms
1. **Cosine Similarity Algorithm** - Recommends best doctors based on patient problem description
2. **Conflict Detection Algorithm** - Prevents double-booking of appointments

## Tech Stack

- **Backend:** PHP 8.0
- **Database:** MySQL 8.0
- **Frontend:** Bootstrap 5, HTML5, CSS3
- **JavaScript:** Vanilla JS
- **Development:** XAMPP

## Installation

### Step 1: Setup Environment
1. Install XAMPP (or WAMP/LAMP)
2. Start Apache and MySQL services
3. Create database: Import `config/database.sql`

### Step 2: Deploy Project
1. Copy project to `htdocs/ONAMS/`
2. Visit `http://localhost/ONAMS/`

### Step 3: Default Login
- **Admin:** `admin` / `admin123`
- **Patient:** Register at `/patient/signup.php`

## Project Structure

```
ONAMS/
├── config/          # Database configuration
├── includes/        # Shared PHP files
├── assets/          # CSS, Images
├── api/             # JSON endpoints
├── admin/           # Admin pages
├── patient/        # Patient pages
└── index.php       # Homepage
```

## Database Tables

- `admin` - Administrator accounts
- `patient` - Patient registrations
- `doctor` - Doctor profiles
- `doctor_available` - Doctor schedules
- `booking` - Appointment records

## Key Algorithms

### Cosine Similarity Formula
```
cos(θ) = (A · B) / (||A|| × ||B||)
```
- Maps patient problem to doctor specialization
- Calculates match percentage
- Ranks doctors by similarity score

### Conflict Detection
- Checks doctor availability on selected day
- Counts existing bookings for time slot
- Prevents double-booking

## Screenshots

- Homepage with doctor list
- Patient registration
- Admin dashboard
- Appointment booking with recommendations

## License

This project is for educational purposes.

## Credits

Developed by: Yam Bahadur B.K. & Ganesh Wod  
Supervisor: Er. Yubraj Devkota  
Institution: Kathmandu Shiksha Campus, TU