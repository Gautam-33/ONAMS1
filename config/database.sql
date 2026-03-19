-- ONAMS Database Setup (Merged)
-- Online Appointment Management System

CREATE DATABASE IF NOT EXISTS hospital_appointment_system;
USE hospital_appointment_system;

-- Admin table
CREATE TABLE IF NOT EXISTS admin (
    username VARCHAR(20) PRIMARY KEY,
    password VARCHAR(100) NOT NULL,
    full_name VARCHAR(50),
    email VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (password: admin123)
INSERT INTO admin (username, password, full_name, email) VALUES 
('admin', 'admin123', 'System Administrator', 'admin@hospital.com');

-- Patient table
CREATE TABLE IF NOT EXISTS patient (
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

-- Doctor table
CREATE TABLE IF NOT EXISTS doctor (
    DID INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(30) NOT NULL,
    gender VARCHAR(10) NOT NULL,
    dob DATE NOT NULL,
    experience VARCHAR(30) NOT NULL COMMENT '(years)',
    specialisation VARCHAR(50) NOT NULL,
    qualification VARCHAR(100),
    contact VARCHAR(10) NOT NULL,
    address VARCHAR(100),
    email VARCHAR(50),
    photo_path VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_doctor_specialization (specialisation)
);

-- Doctor availability table
CREATE TABLE IF NOT EXISTS doctor_available (
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

-- Booking table
CREATE TABLE IF NOT EXISTS booking (
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

-- Create indexes for performance
CREATE INDEX idx_booking_doctor_date ON booking(DID, DOV);
CREATE INDEX idx_doctor_available_composite ON doctor_available(DID, day);
