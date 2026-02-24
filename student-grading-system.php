
<?php
class Database {
    private $host = "localhost";
    private $db_name = "student_grading_system";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?><?php
class Student {
    private $conn;
    private $table_name = "students";

    public $student_id;
    public $student_number;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $address;
    public $enrollment_date;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create student
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET student_number=:student_number,
                      first_name=:first_name,
                      last_name=:last_name,
                      email=:email,
                      phone=:phone,
                      address=:address,
                      enrollment_date=:enrollment_date,
                      status=:status";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->student_number = htmlspecialchars(strip_tags($this->student_number));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->enrollment_date = htmlspecialchars(strip_tags($this->enrollment_date));
        $this->status = htmlspecialchars(strip_tags($this->status));

        // Bind values
        $stmt->bindParam(":student_number", $this->student_number);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":enrollment_date", $this->enrollment_date);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Read all students
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY last_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Read single student
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE student_id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->student_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->student_number = $row['student_number'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->address = $row['address'];
            $this->enrollment_date = $row['enrollment_date'];
            $this->status = $row['status'];
        }
    }

    // Update student
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET first_name=:first_name,
                      last_name=:last_name,
                      email=:email,
                      phone=:phone,
                      address=:address,
                      status=:status
                  WHERE student_id=:student_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->student_id = htmlspecialchars(strip_tags($this->student_id));

        // Bind
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":student_id", $this->student_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete student
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE student_id = ?";
        $stmt = $this->conn->prepare($query);
        $this->student_id = htmlspecialchars(strip_tags($this->student_id));
        $stmt->bindParam(1, $this->student_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get student's GPA
    public function getGPA() {
        $query = "SELECT AVG(fg.grade_point) as gpa
                  FROM final_grades fg
                  JOIN enrollments e ON fg.enrollment_id = e.enrollment_id
                  WHERE e.student_id = :student_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['gpa'] ? round($row['gpa'], 2) : 0;
    }

    // Get enrolled subjects
    public function getEnrolledSubjects() {
        $query = "SELECT s.*, e.enrollment_id, e.academic_year, e.semester,
                         t.first_name as teacher_first, t.last_name as teacher_last,
                         fg.letter_grade, fg.grade_point
                  FROM enrollments e
                  JOIN subjects s ON e.subject_id = s.subject_id
                  LEFT JOIN teachers t ON e.teacher_id = t.teacher_id
                  LEFT JOIN final_grades fg ON e.enrollment_id = fg.enrollment_id
                  WHERE e.student_id = :student_id
                  ORDER BY e.academic_year DESC, e.semester ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Search students
    public function search($keywords) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE first_name LIKE ? OR last_name LIKE ? OR student_number LIKE ? OR email LIKE ?
                  ORDER BY last_name ASC";
        
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);
        $stmt->bindParam(4, $keywords);
        
        $stmt->execute();
        return $stmt;
    }
}
?>