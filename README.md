This is a student grading system thing.

Important to make it work:

COPY AND PASTE THIS TO SQL FOR DATABASE (LINES 7-104: +NEW) (LINES 108-115, 119-120: INSIDE GRADING SYSTEM DATABASE)

-- Create database
CREATE DATABASE IF NOT EXISTS grading_system;
USE grading_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    enrollment_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Teachers table
CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    teacher_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    department VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Courses table
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    credits INT NOT NULL,
    teacher_id INT,
    description TEXT,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

-- Enrollments table
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date DATE DEFAULT CURRENT_DATE,
    semester VARCHAR(20),
    year INT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id, semester, year)
);

-- Grades table
CREATE TABLE grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    assignment_name VARCHAR(100),
    assignment_type ENUM('quiz', 'midterm', 'final', 'project', 'homework') NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    max_score DECIMAL(5,2) NOT NULL,
    weight DECIMAL(3,2) DEFAULT 1.00,
    date_given DATE,
    comments TEXT,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO users (username, password, full_name, role) VALUES
('admin', MD5('admin123'), 'System Administrator', 'admin'),
('teacher1', MD5('teacher123'), 'John Smith', 'teacher'),
('student1', MD5('student123'), 'Alice Johnson', 'student');

INSERT INTO students (user_id, student_id, first_name, last_name, email, enrollment_date) VALUES
(3, 'STU001', 'Alice', 'Johnson', 'alice@email.com', '2024-01-15');

INSERT INTO teachers (user_id, teacher_id, first_name, last_name, email, department) VALUES
(2, 'TCH001', 'John', 'Smith', 'john.smith@email.com', 'Computer Science');

INSERT INTO courses (course_code, course_name, credits, teacher_id, description) VALUES
('CS101', 'Introduction to Programming', 3, 1, 'Basic programming concepts'),
('CS102', 'Data Structures', 3, 1, 'Advanced data structures and algorithms');

INSERT INTO enrollments (student_id, course_id, semester, year) VALUES
(1, 1, 'Fall', 2024),
(1, 2, 'Fall', 2024);

—--------------------- (inside grading-system)

ALTER TABLE students ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER address;
ALTER TABLE teachers ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER department;
