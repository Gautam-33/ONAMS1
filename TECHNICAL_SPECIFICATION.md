# Technical Specification Document

## Online Appointment Management System (ONAMS)

---

## 1. System Overview

**Project Name:** Online Appointment Management System (ONAMS)  
**Technology Stack:** PHP, MySQL, Bootstrap 5, JavaScript  
**Development Environment:** XAMPP (Apache, MySQL, PHP)  
**Database:** MySQL 8.0  
**Frontend:** Bootstrap 5, HTML5, CSS3, FontAwesome 6.0

---

## 1.1 Development Methodology

The development of the Online Appointment Management System (ONAMS) followed the **Iterative Waterfall methodology**. This is a software development approach that combines the step-wise steps of the Traditional Waterfall model with the flexibility of the Iterative Model.

**Why Waterfall Was Chosen:**

- The requirements of the system were clear and well-defined
- The project scope was fixed and confined
- There was minimal need for adding new functionalities during development
- The structured approach ensured systematic progress through each phase

**Development Phases:**

1. **Requirements Analysis** - Clear understanding of patient and admin needs
2. **System Design** - Architecture planning and database schema design
3. **Implementation** - Coding the features and algorithms
4. **Testing** - Comprehensive testing of all modules
5. **Deployment** - Launching the system for use

This methodology ensured that the system was developed systematically, with each phase building on the previous one, resulting in a stable and reliable appointment booking system.

---

## 1.2 Report Organization

The report is organized into five detailed chapters as follows:

**Chapter 1: Introduction**

This chapter introduces the Online Appointment Management System by explaining the problem that patients and hospitals face when booking appointments. It clearly states the main goals of the project - to make appointment booking easier, faster, and error-free. It defines the overall scope of what the system will do and any limitations that exist. The purpose of this chapter is to help readers understand why this project is important and the reasons behind its development. It also explains the development methodology (Iterative Waterfall) used so that readers can easily follow the next chapters and understand the goals and boundaries within which the system will work.

**Chapter 2: Background and Literature Review**

This chapter looks at background information and research that relates to appointment booking systems. It studies existing appointment systems, technologies used in healthcare, and past work done by others in the same field. The aim is to provide a strong foundation for why this project is needed and how it can be developed. It explains important findings and methods from other studies, as well as system designs and frameworks that can guide this project. This chapter gives a clear view of what has already been done in appointment booking and what gaps still exist in the market, which helps in creating a better and more innovative system.

**Chapter 3: System Analysis and Design**

This chapter examines what the system needs to do and how it will be built. It identifies the main requirements - both what users need the system to do (functional requirements) and how it should perform and be secure (non-functional requirements). After understanding the needs, the chapter explains how the new system will be designed. It discusses the technologies used (PHP, MySQL, Bootstrap), tools for development, and methods for creating the system. This chapter also includes information about gathering requirements, creating the system architecture, designing the database, and applying design principles. The main goal is to show how the system is planned in detail before moving to the actual development stage.

**Chapter 4: Implementation and Testing**

This chapter focuses on the process of actually building and setting up the Online Appointment Management System. It explains the steps taken to implement the system - how the patient and admin sections were created, how the doctor recommendation algorithm was coded, and how the conflict detection system works. After the system is developed, testing is carried out to check if it works correctly, performs well, and is reliable. The chapter discusses the types of tests that were used (unit testing, system testing) and why they were important for ensuring quality. Additionally, it talks about any problems or challenges faced during development and the solutions that were applied to overcome them, ensuring that the project was successful.

**Chapter 5: Conclusion and Lessons Learned**

The final chapter gives a summary of everything that was accomplished in the project. It reviews the main achievements - a working appointment booking system that successfully matches patients with doctors and prevents double-booking. It also reflects on the challenges that were faced during development and how they were overcome. This chapter shares the key lessons learned from building the system - what worked well, what could be improved, and important insights gained from the experience. It also provides suggestions for future improvements, such as adding more advanced AI-driven recommendations, deploying to cloud services, integrating with payment systems, or expanding to support multiple hospitals. The aim is to reflect on the whole project and offer ideas for making the system even better and more useful in the future.

---

## 2. Project Structure

```
ONAMS/
├── config/
│   ├── database.php          # Database connection class
│   ├── database.sql          # SQL schema
│   └── setup.php             # Auto-setup script
├── includes/
│   ├── admin-header.php      # Admin layout header
│   ├── admin-navbar.php     # Admin navigation
│   ├── algorithm.php         # Cosine similarity algorithm
│   ├── footer.php           # Common footer
│   ├── header.php           # Common header
│   └── session.php           # Session management
├── assets/
│   ├── css/style.css        # Custom styles
│   └── images/              # Doctor photos, default images
├── api/
│   ├── check-availability.php
│   └── get-doctors.php
├── admin/
│   ├── add-doctor.php        # Add new doctor
│   ├── dashboard.php         # Admin dashboard
│   ├── doctor-schedule.php  # Doctor availability
│   ├── edit-doctor.php      # Edit doctor
│   ├── index.php            # Redirect to dashboard
│   ├── login.php            # Admin login
│   ├── logout.php           # Admin logout
│   ├── manage-doctors.php   # Doctor list
│   └── view-appointments.php
├── patient/
│   ├── book-appointment.php
│   ├── cancel-appointment.php
│   ├── dashboard.php
│   ├── login.php
│   ├── logout.php
│   ├── my-appointments.php
│   ├── print-appointment.php
│   ├── simple-book.php      # Booking with AI recommendation
│   ├── signup.php
│   └── update-profile.php
├── algorithm.php            # Main algorithm file
└── index.php                # Homepage
```

---

## 3. Database Schema

### 3.1 Admin Table

```sql
CREATE TABLE admin (
    username VARCHAR(20) PRIMARY KEY,
    password VARCHAR(100) NOT NULL,
    full_name VARCHAR(50),
    email VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

- **Default Credentials:** username: `admin`, password: `admin123`

### 3.2 Patient Table

```sql
CREATE TABLE patient (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(30) NOT NULL,
    gender VARCHAR(10) NOT NULL,
    dob DATE NOT NULL,
    phone VARCHAR(10) NOT NULL,
    username VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(30) NOT NULL UNIQUE,
    address VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_patient_username (username),
    INDEX idx_patient_email (email)
);
```

### 3.3 Doctor Table

```sql
CREATE TABLE doctor (
    DID INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(30) NOT NULL,
    gender VARCHAR(10) NOT NULL,
    dob DATE NOT NULL,
    experience VARCHAR(30) NOT NULL,
    specialisation VARCHAR(50) NOT NULL,
    qualification VARCHAR(100),
    contact VARCHAR(10) NOT NULL,
    address VARCHAR(100),
    email VARCHAR(50),
    photo_path VARCHAR(255),        -- Doctor photo path
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_doctor_specialization (specialisation)
);
```

### 3.4 Doctor Availability Table

```sql
CREATE TABLE doctor_available (
    id INT PRIMARY KEY AUTO_INCREMENT,
    DID INT NOT NULL,
    day VARCHAR(20) NOT NULL,
    starttime TIME NOT NULL,
    endtime TIME NOT NULL,
    max_patients INT DEFAULT 20,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (DID) REFERENCES doctor(DID) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_day (DID, day)
);
```

### 3.5 Booking Table

```sql
CREATE TABLE booking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(30) NOT NULL,
    Fname VARCHAR(30) NOT NULL,
    gender VARCHAR(10) NOT NULL,
    DID INT NOT NULL,
    DOV DATE NOT NULL,
    time_slot TIME NOT NULL,
    Timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('Pending', 'Confirmed', 'Cancelled', 'Completed') DEFAULT 'Pending',
    notes TEXT,
    FOREIGN KEY (username) REFERENCES patient(username) ON DELETE CASCADE,
    FOREIGN KEY (DID) REFERENCES doctor(DID),
    INDEX idx_booking_date (DOV),
    INDEX idx_booking_status (Status),
    INDEX idx_patient_bookings (username, DOV),
    INDEX idx_doctor_booking (DID, DOV)
);
```

---

## 3.6 Algorithm Details

The Online Appointment Management System employs two sophisticated algorithms to ensure efficient appointment booking and intelligent doctor matching. These algorithms work together to provide an automated, error-free appointment scheduling experience.

### Algorithm 1: Conflict Detection Algorithm (Double-Booking Prevention)

**Purpose:** Prevents double-booking by automatically checking if a doctor is already assigned to another appointment at the requested time slot.

**Mathematical Foundation:**

The conflict detection system uses a simple yet powerful logical approach:

```
IF appointment_slot_exists(doctor_id, date, time_slot) THEN
    CONFLICT_DETECTED = TRUE
ELSE
    CONFLICT_DETECTED = FALSE
END IF
```

**Algorithm Principle:**

The algorithm operates on the principle of uniqueness constraint verification - ensuring that no two confirmed appointments can occupy the same doctor's time slot on the same date. This is implemented through a database query that counts existing appointments matching the three criteria:

- Doctor ID (DID) = Selected Doctor
- Date of Visit (DOV) = Selected Date
- Time Slot = Selected Time Slot

**Query Logic:**

```
Count = SELECT COUNT(*) FROM booking
        WHERE DID = doctor_id
        AND DOV = date
        AND time_slot = time_slot
        AND Status IN ('Pending', 'Confirmed')
```

**Conflict Detection Algorithm:**

```
ALGORITHM: CheckConflict(doctor_id, date, time_slot)
INPUT: Doctor ID, Appointment date, Time slot
OUTPUT: Boolean (true if conflict exists, false otherwise)

1. IF doctor_id is null OR date is null OR time_slot is null THEN
   RETURN FALSE (invalid input, allow booking to proceed with error)

2. total_bookings = 0

3. total_bookings = Execute query:
   SELECT COUNT(*) FROM booking
   WHERE DID = doctor_id
   AND DOV = date
   AND time_slot = time_slot
   AND Status IN ('Pending', 'Confirmed')

4. IF total_bookings > 0 THEN
   RETURN TRUE (conflict detected - slot already booked)

5. ELSE
   RETURN FALSE (no conflict - slot is available)

6. END IF
```

---

### Algorithm 2: Cosine Similarity Algorithm (Doctor Recommendation)

**Purpose:** Recommends the most suitable doctors to patients based on their health problem description using vector similarity matching.

**Mathematical Foundation:**

The Cosine Similarity algorithm calculates the similarity between two vectors using the cosine of the angle between them. This approach measures how closely a patient's needs align with each doctor's specialization and experience.

**Cosine Similarity Formula:**

```
cos(θ) = (A · B) / (||A|| × ||B||)

Where:
• A = Patient problem vector (derived from problem description)
• B = Doctor feature vector (based on specialization and experience)
• A · B = Dot product of vectors
• ||A|| = Magnitude of patient vector
• ||B|| = Magnitude of doctor vector
• Result = Similarity score between 0 and 1
```

**Vector Component Explanation:**

- **Patient Vector Components:**
  - Keywords extracted from health problem description
  - Specialization match (1 if matches, 0 if not)
  - Experience preference (0-1 normalized score)

- **Doctor Vector Components:**
  - Specialization value (1 for matching, 0 for non-matching)
  - Experience years (normalized to 0-1 scale)
  - Availability score (0-1 based on free slots)

**Problem to Specialization Mapping:**

```
heart/chest pain/cardiac → Cardiology
skin/rash/acne/eczema → Dermatology
brain/headache/migraine/seizure → Neurology
bone/joint/fracture/back pain → Orthopedics
child/baby/kids → Pediatrics
mental/anxiety/depression → Psychiatry
...and more (70+ keywords mapped to 15 specializations)
```

**Text Preprocessing Algorithm (Keyword Extraction):**

```
ALGORITHM: ExtractKeywords(problem_text)
INPUT: Patient's problem description text
OUTPUT: List of extracted keywords

1. IF problem_text is null OR empty THEN
   RETURN empty list

2. Convert problem_text to lowercase

3. Split text into words using delimiters: space, punctuation

4. Filter out common words (stop words): "the", "a", "is", "and", etc.

5. keywords = list of remaining words

6. RETURN keywords
```

**Vector Construction Algorithm:**

```
ALGORITHM: ConstructPatientVector(keywords, all_specializations)
INPUT: Extracted keywords, List of all specializations
OUTPUT: Patient preference vector

1. patient_vector = empty dictionary

2. FOR each specialization in all_specializations:
      matching_keywords = 0
      FOR each keyword in keywords:
         IF keyword matches specialization THEN
            matching_keywords += 1
      END FOR
      patient_vector[specialization] = matching_keywords / total_keywords
   END FOR

3. RETURN patient_vector


ALGORITHM: ConstructDoctorVector(doctor_record, all_specializations)
INPUT: Doctor record from database, List of specializations
OUTPUT: Doctor feature vector

1. doctor_vector = empty dictionary

2. FOR each specialization in all_specializations:
      IF doctor_record.specialisation == specialization THEN
         doctor_vector[specialization] = 1.0
      ELSE
         doctor_vector[specialization] = 0.0
      END IF
   END FOR

3. experience_normalized = doctor_record.experience / 20 (max 20 years)
   doctor_vector['experience'] = MIN(experience_normalized, 1.0)

4. RETURN doctor_vector
```

**Similarity Calculation Algorithm:**

```
ALGORITHM: CalculateCosineSimilarity(vector_A, vector_B)
INPUT: Patient vector A, Doctor vector B
OUTPUT: Similarity score (0 to 1)

1. IF vector_A is empty OR vector_B is empty THEN
   RETURN 0

2. dot_product = 0
   magnitude_A = 0
   magnitude_B = 0

3. FOR each dimension in vectors:
      dot_product += vector_A[dimension] × vector_B[dimension]
      magnitude_A += vector_A[dimension]²
      magnitude_B += vector_B[dimension]²
   END FOR

4. magnitude_A = √magnitude_A

5. magnitude_B = √magnitude_B

6. IF magnitude_A == 0 OR magnitude_B == 0 THEN
   RETURN 0 (at least one vector has no magnitude)

7. similarity = dot_product / (magnitude_A × magnitude_B)

8. RETURN similarity (normalized between 0 and 1)
```

**Doctor Recommendation Algorithm (Main):**

```
ALGORITHM: RecommendDoctors(patient_problem_text)
INPUT: Patient's health problem description
OUTPUT: Sorted list of recommended doctors with similarity scores

1. keywords = ExtractKeywords(patient_problem_text)

2. IF keywords is empty THEN
   RETURN all active doctors (no specific match)

3. patient_vector = ConstructPatientVector(keywords, all_specializations)

4. all_doctors = SELECT * FROM doctor WHERE status = 'active'

5. recommendations = empty list

6. FOR each doctor in all_doctors:
      doctor_vector = ConstructDoctorVector(doctor, all_specializations)
      similarity_score = CalculateCosineSimilarity(patient_vector,
                                                   doctor_vector)

      IF similarity_score > 0 THEN
         recommendations.add({
            doctor: doctor,
            similarity_score: similarity_score,
            match_percentage: similarity_score × 100
         })
      END IF
   END FOR

7. Sort recommendations by similarity_score in descending order
   (highest match first)

8. RETURN recommendations

9. Display top recommendations to patient in order of best match
```

**Matching Process Flow:**

```
Patient Input → Keyword Extraction → Vector Construction →
Similarity Calculation → Score All Doctors → Sort by Score →
Display Top Matches
```

---

## 4. Core Features

### 4.1 Patient Features

- User registration with validation
- Secure login with password hashing
- Browse doctors by specialization
- View doctor availability
- Book appointments with conflict detection
- View appointment history
- Cancel appointments
- Print appointment receipts

### 4.2 Admin Features

- Secure admin login
- Add/Edit/Delete doctors
- Manage doctor schedules
- View all appointments
- Approve/Confirm appointments
- View patient registrations
- Dashboard with statistics

### 4.3 Doctor Recommendation (AI Feature)

- Cosine similarity algorithm
- 70+ keyword mapping to 15 specializations
- Real-time doctor matching based on patient problem
- Match percentage display
- Best match highlighting

---

## 5. Security Features

1. **Password Hashing:** Using PHP's `password_hash()` and `password_verify()`
2. **SQL Injection Prevention:** Using PDO prepared statements
3. **Input Validation:** Server-side validation for all inputs
4. **Session Management:** Secure session handling
5. **Nepali Phone Validation:** `^(97|98)\d{8}$`
6. **Email Validation:** Using PHP's `FILTER_VALIDATE_EMAIL`

---

## 6. File Paths Reference

### 6.1 Doctor Photo Handling

- **Upload Directory:** `assets/images/`
- **Database Path:** `assets/images/doctor_*.jpg/png`
- **Default Photo:** `assets/images/doctor.jpg`
- **Admin Path with prefix:** `../assets/images/` (for admin folder)

### 6.2 Include Paths

- Database: `../config/database.php`
- Headers: `../includes/admin-header.php`
- Session: `../includes/session.php`

---

## 7. Testing Summary

| Module              | Test Cases | Pass Rate |
| ------------------- | ---------- | --------- |
| Authentication      | 6          | 100%      |
| Registration        | 5          | 100%      |
| Doctor Management   | 5          | 100%      |
| Appointment Booking | 5          | 100%      |
| Dashboard           | 4          | 100%      |
| Recommendation      | 3          | 100%      |

---

## 8. Deployment Instructions

1. **Install XAMPP** (or similar LAMP/WAMP stack)
2. **Start Apache and MySQL** services
3. **Create database** using `config/database.sql`
4. **Run setup** - access any page to auto-create default admin
5. **Copy project files** to `htdocs/ONAMS/`
6. **Access:** `http://localhost/ONAMS/`

### Default Login Credentials:

- **Admin:** username: `admin`, password: `admin123`
- **Patient:** Register new account at `/patient/signup.php`

---

## 9. Known Issues & Solutions

| Issue                    | Solution                                                                 |
| ------------------------ | ------------------------------------------------------------------------ |
| Photo not uploading      | Added `enctype="multipart/form-data"` to form, used `copy()` as fallback |
| Photos not displaying    | Added proper path handling with `../` prefix for admin folder            |
| Database class not found | Created `config/database.php` with Database class                        |
| Clinic table not found   | Removed reference to non-existent table (single hospital system)         |

---

_Document Version: 1.0_  
_Last Updated: April 2026_

## Project Overview

**What Our System Does**

Our Online Appointment Booking System is a simple online tool designed to help both patients and doctors. It solves real problems that happen every day in hospitals and clinics.

**The Problems We Fix**

In the past, booking doctor appointments was difficult. Patients had to wait a long time to get an appointment. They didn't know which doctor was right for them. They couldn't easily find out if a doctor was available. Hospital staff had to deal with messy schedules and often made mistakes. Sometimes two patients would be booked for the same appointment time, which confused everyone. There was no quick way to stop these mistakes from happening.

**How Our System Works**

Our system is an easy-to-use website that connects patients with doctors. It stops all these problems.

The system has two main users:

1. **Hospital Staff (Admins)** - They use a special section to add doctors, set their schedules, and see all appointments. They can easily manage everything without complicated steps.

2. **Patients** - They can go on the website, describe their health problem, find the right doctor, check if the doctor has time available, and book an appointment in just a few seconds. They can also see all their past and future appointments.

**What Makes Our System Special**

- **Smart Doctor Matching** - When patients tell us about their health problem, our system automatically finds the best doctors for them. It matches what the patient needs with what each doctor is good at.

- **No Mistakes with Double Booking** - The system stops the same doctor from being booked twice at the same time. It checks everything automatically.

- **Anyone Can Use It** - The design is simple. You don't need training or help to use it.

- **Safe and Secure** - All patient information is protected. Passwords are hidden and safe.

- **See Available Time Slots** - Patients can see right now when doctors are free and book immediately.

- **Keep Track of Appointments** - Patients can see all their appointments in one place and manage them easily.

**Why This Matters**

Our main goal is to make life easier for patients and doctors. We want to save time, stop mistakes, and make sure everyone is happy. When booking appointments is simple and quick, hospitals work better and patients get better care.
