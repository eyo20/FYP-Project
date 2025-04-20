-- Create database for tutoring platform
CREATE DATABASE tutoring_platform;
USE tutoring_platform;

-- Users table (base user entity)
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admin table
CREATE TABLE Admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_user FOREIGN KEY (admin_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Students table
CREATE TABLE Students (
    students_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    major VARCHAR(100) NULL,
    year INT NULL,
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Create tutors table (modified to include fields from both schemas)
CREATE TABLE tutors (
    tutors_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100),
    major VARCHAR(100),
    year VARCHAR(50),
    bio TEXT NULL,
    qualifications TEXT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    rating DECIMAL(3,2) DEFAULT 0.0,
    hourly_rate DECIMAL(10,2),
    image_url VARCHAR(255),
    CONSTRAINT fk_tutors_user FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Create subjects table (modified to match new requirements)
CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT,
    subject_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    FOREIGN KEY (tutor_id) REFERENCES tutors(tutors_id)
);

-- Courses table
CREATE TABLE Courses (
    courses_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    course_title VARCHAR(100) NOT NULL,
    CONSTRAINT fk_courses_subject FOREIGN KEY (subject_id) REFERENCES Subjects(subject_id) ON DELETE CASCADE
);

-- Tutor_courses junction table
CREATE TABLE Tutor_courses (
    subject_id INT NOT NULL,
    course_id INT NOT NULL,
    PRIMARY KEY (subject_id, course_id),
    CONSTRAINT fk_tutor_courses_subject FOREIGN KEY (subject_id) REFERENCES Subjects(subject_id) ON DELETE CASCADE,
    CONSTRAINT fk_tutor_courses_course FOREIGN KEY (course_id) REFERENCES Courses(courses_id) ON DELETE CASCADE
);

-- Create time_slots table (new table)
CREATE TABLE time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT,
    start_time TIME,
    end_time TIME,
    FOREIGN KEY (tutor_id) REFERENCES tutors(tutors_id)
);

-- Availability table
CREATE TABLE Availability (
    slot_id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    CONSTRAINT fk_availability_tutor FOREIGN KEY (tutor_id) REFERENCES Tutors(tutors_id) ON DELETE CASCADE,
    CHECK (status IN ('open', 'booked', 'cancelled'))
);

-- Create locations table (new table)
CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(100),
    location_value VARCHAR(50)
);

-- Session table
CREATE TABLE Session (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    slot_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cancellation_deadline DATETIME NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_session_tutor FOREIGN KEY (tutor_id) REFERENCES Tutors(tutors_id) ON DELETE CASCADE,
    CONSTRAINT fk_session_student FOREIGN KEY (student_id) REFERENCES Students(students_id) ON DELETE CASCADE,
    CONSTRAINT fk_session_course FOREIGN KEY (course_id) REFERENCES Courses(courses_id) ON DELETE CASCADE,
    CONSTRAINT fk_session_slot FOREIGN KEY (slot_id) REFERENCES Availability(slot_id) ON DELETE CASCADE,
    CHECK (status IN ('scheduled', 'completed', 'cancelled'))
);

-- Messages table
CREATE TABLE Messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    sent_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE Reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_session FOREIGN KEY (session_id) REFERENCES Session(session_id) ON DELETE CASCADE,
    CHECK (rating BETWEEN 1 AND 5)
);

-- Payments table
CREATE TABLE Payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(50) NOT NULL,
    paid_date DATETIME NULL,
    transaction_id VARCHAR(100) NULL,
    refund_amount DECIMAL(10,2) DEFAULT 0.0,
    refund_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_session FOREIGN KEY (session_id) REFERENCES Session(session_id) ON DELETE CASCADE,
    CHECK (status IN ('pending', 'completed', 'failed', 'refunded'))
);

-- Add some indexes for performance
CREATE INDEX idx_users_role ON Users(role);
CREATE INDEX idx_subjects_name ON Subjects(subject_name);
CREATE INDEX idx_courses_code ON Courses(course_code);
CREATE INDEX idx_availability_status ON Availability(status);
CREATE INDEX idx_session_status ON Session(status);
CREATE INDEX idx_payments_status ON Payments(status);

-- Insert sample tutor data (in English)
-- Note: This requires creating a user first since tutor has a user_id foreign key
INSERT INTO Users (username, password, email, role) VALUES 
('jameswilson', 'hashedpassword123', 'james.wilson@example.com', 'tutor');

INSERT INTO tutors (user_id, name, major, year, bio, rating, hourly_rate, image_url) VALUES 
(LAST_INSERT_ID(), 'James Wilson', 'Computer Science', 'Junior', 'Computer Science major with a focus on algorithms and data structures. Winner of the university programming competition. I excel at explaining complex concepts in simple terms and enjoy helping fellow students succeed!', 4.8, 25.00, '/api/placeholder/80/80');

-- Insert sample subjects
INSERT INTO subjects (tutor_id, subject_name) VALUES 
(1, 'Data Structures'),
(1, 'Java Programming'),
(1, 'Algorithm Fundamentals'),
(1, 'Database Principles');

-- Insert sample time slots
INSERT INTO time_slots (tutor_id, start_time, end_time) VALUES
(1, '09:00:00', '10:00:00'),
(1, '10:00:00', '11:00:00'),
(1, '11:00:00', '12:00:00'),
(1, '13:00:00', '14:00:00'),
(1, '14:00:00', '15:00:00'),
(1, '15:00:00', '16:00:00'),
(1, '16:00:00', '17:00:00'),
(1, '19:00:00', '20:00:00');

-- Insert sample locations
INSERT INTO locations (location_name, location_value) VALUES
('Library', 'library'),
('Study Room', 'study-room'),
('Campus Cafe', 'cafe');
