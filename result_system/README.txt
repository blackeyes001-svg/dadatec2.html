STUDENT RESULT MANAGEMENT SYSTEM (PHP + MySQL)

HOW TO RUN (XAMPP)
1. Copy this folder (result_system) into C:\xampp\htdocs\
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open http://localhost/phpmyadmin, click Import, choose database.sql, click Go.
4. Open http://localhost/result_system/setup.php ONCE to create the admin account.
   Then DELETE setup.php.
5. Open http://localhost/result_system/ and login:
   Admin email: admin@school.com
   Admin password: admin123   (change it after first login if you add that feature)

If your MySQL has a password, change it in config.php.

WHAT THE SYSTEM DOES
Admin: add, edit, delete and search students; add, edit, delete courses;
       add, edit, delete results; see dashboard totals.
Student: login and view only their own results, total units and GPA; print result.

DEFENCE NOTES (what to say if you are asked)
- Database: 4 tables: users, students, courses, results. results links a student to a course.
  A student can have only one result per course (UNIQUE key).
- Security: prepared statements (SQL injection), password_hash/password_verify (passwords),
  htmlspecialchars through e() (XSS), CSRF token on every form, sessions and role checks.
- CRUD: Create = INSERT, Read = SELECT, Update = UPDATE, Delete = DELETE.
- Grade: A 70-100 (5), B 60-69 (4), C 50-59 (3), D 45-49 (2), E 40-44 (1), F 0-39 (0).
- GPA = total of (grade point x unit) divided by total units.
- config.php = database connection + helper functions. header.php and footer.php are reused.

FILES
config.php, header.php, footer.php, style.css, database.sql, setup.php
login.php, logout.php, index.php
admin_dashboard.php
students.php, student_form.php, student_delete.php
courses.php, course_form.php, course_delete.php
results.php, result_form.php, result_delete.php
student_dashboard.php
