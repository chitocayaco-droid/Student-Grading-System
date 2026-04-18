# 📚 Bethel GradeMaster - Student Grading System

A comprehensive web-based student grading management system for Bethel International School.

---

## 🎯 Features

### 👑 Administrator Functions
- Manage students, teachers, and courses  
- Reset passwords and change usernames  
- View help requests from users  
- Enroll students in courses  

### 👨‍🏫 Teacher Functions  
- Enter and manage student grades  
- View class rosters and student performance  

### 👨‍🎓 Student Functions
- View personal grades and GPA  
- Update profile information  

---

## 🛠️ Technology Stack
- PHP 7.4+  
- MySQL  
- HTML5  
- CSS3  
- JavaScript  
- XAMPP/WAMP recommended  

---

## 📋 Prerequisites
- XAMPP / WAMP / MAMP installed  
- PHP 7.4 or higher  
- MySQL 5.7 or higher  

---

## 🚀 Installation Guide

### Step 1: Install XAMPP
Download from: https://www.apachefriends.org/

### Step 2: Start Services
Start Apache and MySQL

### Step 3: Copy Project Files
Copy to:
C:\xampp\htdocs\grading_system\

### Step 4: Create Database
Go to http://localhost/phpmyadmin and create:
grading_system

### Step 5: Import SQL
Run the SQL script provided in the documentation.

### Step 6: Create Required Folders
uploads/
uploads/profiles/
assets/
assets/css/
includes/

### Step 7: Add Default Profile Image
uploads/profiles/default.jpg

### Step 8: Configure Database
Edit config/database.php

### Step 9: Run System
http://localhost/grading_system/

---

## 🔑 Default Login Credentials

| Role    | Username  | Password    |
|--------|----------|-------------|
| Admin  | admin     | admin123    |
| Teacher| teacher1  | teacher123  |
| Student| student1  | student123  |

---

## 🐛 Troubleshooting

- White screen → Check services  
- Images not loading → Check uploads folder  
- Login issues → Use default credentials  

---

## 📞 Support
admin@bethel.edu

---

Version 2.0 | April 2025
