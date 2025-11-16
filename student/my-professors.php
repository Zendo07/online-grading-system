<?php

session_start();
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$student_id = $_SESSION['user_id'];
$page_title = "My Professors";

// Fetch all subjects the student is enrolled in with professor information
$query = "
    SELECT DISTINCT
        c.class_id,
        c.class_code,
        c.class_name,
        c.subject,
        c.section,
        u.user_id as professor_id,
        u.full_name as professor_name,
        u.email as professor_email,
        u.profile_picture as professor_picture
    FROM enrollments e
    INNER JOIN classes c ON e.class_id = c.class_id
    INNER JOIN users u ON c.teacher_id = u.user_id
    WHERE e.student_id = :student_id
    AND e.status = 'active'
    ORDER BY c.class_code ASC
";

try {
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $professors_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching professors: " . $e->getMessage());
    $professors_data = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - indEx</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/main.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/student-pages/my-professors.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/student-nav.php'; ?>
    
    <main class="main-content">
        <div class="page-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">My Professors</h1>
                <p class="page-subtitle">View your instructors for enrolled subjects</p>
            </div>

            <!-- Professors Grid -->
            <div class="professors-container">
                <?php if (empty($professors_data)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <h3>No Professors Found</h3>
                        <p>You haven't enrolled in any subjects yet. Join a class to see your professors here.</p>
                        <a href="<?php echo BASE_URL; ?>student/join-class.php" class="btn-primary">Join a Class</a>
                    </div>
                <?php else: ?>
                    <div class="professors-grid">
                        <?php foreach ($professors_data as $data): ?>
                            <div class="professor-card">
                                <div class="card-header">
                                    <h3 class="subject-title"><?php echo htmlspecialchars($data['subject']); ?></h3>
                                </div>
                                
                                <div class="card-divider"></div>
                                
                                <div class="professor-info">
                                    <div class="professor-avatar">
                                        <img src="<?php echo getProfilePicture($data['professor_picture'], $data['professor_name']); ?>" 
                                             alt="<?php echo htmlspecialchars($data['professor_name']); ?>"
                                             onerror="this.src='<?php echo BASE_URL; ?>assets/images/default-avatar.png'">
                                    </div>
                                    <div class="professor-details">
                                        <h4 class="professor-name"><?php echo htmlspecialchars($data['professor_name']); ?></h4>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
    
    <script src="<?php echo BASE_URL; ?>assets/js/student-pages/my-professors.js?v=<?php echo time(); ?>"></script>
</body>
</html>