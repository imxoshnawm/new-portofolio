<?php
require_once 'config.php';
require_once 'helpers.php';

$lang = $_GET['lang'] ?? 'en'; // Default to English if not set

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pdo = getConnection();
$message = '';
$error = '';

// Handle file uploads
function handleFileUpload($file, $uploadDir = 'uploads/') {
    try {
        // Create directory if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Validate file upload
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file parameters');
        }

        // Check upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File too large');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file uploaded');
            default:
                throw new Exception('Unknown error');
        }

        // Get file info
        $fileName = basename($file['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Generate unique filename
        $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
        $targetPath = $uploadDir . $newFileName;

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to move uploaded file');
        }

        return $newFileName;

    } catch (Exception $e) {
        error_log("File upload error: " . $e->getMessage());
        throw $e;
    }
}

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_about':
                try {
                    $profileImage = null;
                    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                        // Handle file upload
                        $profileImage = handleFileUpload($_FILES['profile_image'], 'uploads/profile/');
                    }
                    
                    $sql = "UPDATE about_me SET 
                            name_en = ?, name_ku = ?, 
                            title_en = ?, title_ku = ?, 
                            description_en = ?, description_ku = ?, 
                            university_en = ?, university_ku = ?, 
                            skills_en = ?, skills_ku = ?";
                    
                    $params = [
                        $_POST['name_en'], $_POST['name_ku'],
                        $_POST['title_en'], $_POST['title_ku'],
                        $_POST['description_en'], $_POST['description_ku'],
                        $_POST['university_en'], $_POST['university_ku'],
                        $_POST['skills_en'], $_POST['skills_ku']
                    ];
                    
                    // Only add profile_image to update if a new file was uploaded
                    if ($profileImage !== null) {
                        $sql .= ", profile_image = ?";
                        $params[] = $profileImage;
                    }
                    
                    $sql .= " WHERE id = 1";
                    
                    $stmt = $pdo->prepare($sql);
                    if (!$stmt->execute($params)) {
                        throw new Exception("Database update failed");
                    }
                    
                    $message = "About Me updated successfully!";
                    
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'add_project':
                try {
                    $imageFile = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $imageFile = handleFileUpload($_FILES['image'], 'uploads/projects/');
                    }
                    
                    $sql = "INSERT INTO projects (
                        title_en, title_ku, 
                        description_en, description_ku,
                        category, technologies_en, technologies_ku,
                        image_url, project_link, project_link_text_en, 
                        project_link_text_ku, status, display_order
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_POST['title_en'], $_POST['title_ku'],
                        $_POST['description_en'], $_POST['description_ku'],
                        $_POST['category'], $_POST['technologies_en'], $_POST['technologies_ku'],
                        $imageFile ? 'uploads/projects/' . $imageFile : null,
                        $_POST['project_link'] ?? '', // Add null coalescing operator
                        $_POST['project_link_text_en'] ?? 'View Project', // Add default text
                        $_POST['project_link_text_ku'] ?? 'بینینی پڕۆژە', // Add default text
                        $_POST['status'], (int)$_POST['display_order']
                    ]);
                    
                    $message = "Project added successfully!";
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'add_certificate':
                try {
                    $imageFile = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $imageFile = handleFileUpload($_FILES['image'], 'uploads/certificates/');
                    }
                    
                    $sql = "INSERT INTO certificates (
                        title_en, title_ku,
                        description_en, description_ku,
                        issuing_organization, issue_date,
                        image_path, display_order
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_POST['title_en'], $_POST['title_ku'],
                        $_POST['description_en'], $_POST['description_ku'],
                        $_POST['issuing_organization'], $_POST['issue_date'],
                        $imageFile ? 'uploads/certificates/' . $imageFile : null,
                        $_POST['display_order']
                    ]);
                    
                    $message = "Certificate added successfully!";
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'add_cv':
                try {
                    // First deactivate all existing CVs
                    $stmt = $pdo->prepare("UPDATE cv_resumes SET is_active = 0");
                    $stmt->execute();

                    $cvFile = null;
                    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
                        $cvFile = handleFileUpload($_FILES['cv_file'], 'uploads/cv/');
                    }
                    
                    // Then add new CV as active
                    $sql = "INSERT INTO cv_resumes (
                        title_en, title_ku,
                        file_path, is_active,
                        language, display_order
                    ) VALUES (?, ?, ?, 1, ?, ?)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_POST['title_en'], $_POST['title_ku'],
                        $cvFile ? 'uploads/cv/' . $cvFile : null,
                        $_POST['language'] ?? 'both',
                        (int)$_POST['display_order']
                    ]);
                    
                    $message = "CV updated successfully!";
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'add_achievement':
                try {
                    $imageFile = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $imageFile = handleFileUpload($_FILES['image'], 'uploads/achievements/');
                    }
                    
                    $sql = "INSERT INTO achievements (
                        title_en, title_ku, 
                        description_en, description_ku,
                        year, image_path, display_order
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_POST['title_en'], $_POST['title_ku'],
                        $_POST['description_en'], $_POST['description_ku'],
                        $_POST['year'],
                        $imageFile ? 'uploads/achievements/' . $imageFile : null,
                        (int)$_POST['display_order']
                    ]);
                    
                    $message = "Achievement added successfully!";
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'add_experience':
                $stmt = $pdo->prepare("INSERT INTO experience (company_en, company_ku, position_en, position_ku, description_en, description_ku, year, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['company_en'], $_POST['company_ku'],
                    $_POST['position_en'], $_POST['position_ku'],
                    $_POST['description_en'], $_POST['description_ku'],
                    $_POST['year'],
                    (int)$_POST['display_order']
                ]);
                $message = "Experience added successfully!";
                break;
                
            case 'add_report':
                try {
                    $reportFile = null;
                    if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
                        $reportFile = handleFileUpload($_FILES['report_file'], 'uploads/reports/');
                    }
                    
                    $sql = "INSERT INTO reports (
                        title_en, title_ku,
                        description_en, description_ku,
                        file_url, display_order
                    ) VALUES (?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_POST['title_en'], $_POST['title_ku'],
                        $_POST['description_en'], $_POST['description_ku'],
                        $reportFile ? 'uploads/reports/' . $reportFile : null,
                        $_POST['display_order']
                    ]);
                    
                    $message = "Report added successfully!";
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'update_contact':
                $stmt = $pdo->prepare("UPDATE contact_info SET linkedin = ?, email = ?, phone = ?, cv_url = ? WHERE id = 1");
                $stmt->execute([
                    $_POST['linkedin'], $_POST['email'], $_POST['phone'], $_POST['cv_url']
                ]);
                $message = "Contact information updated successfully!";
                break;
                
            case 'delete_item':
                $table = $_POST['table'];
                $id = (int)$_POST['id'];
                $allowedTables = ['projects', 'certificates', 'cv_resumes', 'achievements', 'experience', 'reports'];
                
                if (in_array($table, $allowedTables)) {
                    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = "Item deleted successfully!";
                }
                break;
                
            case 'edit_project':
                try {
                    $id = (int)$_POST['id'];
                    
                    $sql = "UPDATE projects SET 
                            title_en = ?, title_ku = ?,
                            description_en = ?, description_ku = ?,
                            category = ?, technologies_en = ?, technologies_ku = ?,
                            display_order = ?
                            WHERE id = ?";
                    
                    $params = [
                        $_POST['title_en'], $_POST['title_ku'],
                        $_POST['description_en'], $_POST['description_ku'],
                        $_POST['category'], $_POST['technologies_en'], $_POST['technologies_ku'],
                        (int)$_POST['display_order'],
                        $id
                    ];
                    
                    // Handle image upload if provided
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $imageFile = handleFileUpload($_FILES['image'], 'uploads/projects/');
                        $sql = "UPDATE projects SET 
                                image_url = ?,
                                title_en = ?, title_ku = ?,
                                description_en = ?, description_ku = ?,
                                category = ?, technologies_en = ?, technologies_ku = ?,
                                display_order = ?
                                WHERE id = ?";
                        array_unshift($params, 'uploads/projects/' . $imageFile);
                    }
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $message = "Project updated successfully!";
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'edit_achievement':
                try {
                    $id = (int)$_POST['id'];
                    $imageFile = null;
                    
                    $sql = "UPDATE achievements SET 
                            title_en = ?, title_ku = ?,
                            description_en = ?, description_ku = ?,
                            year = ?, display_order = ?";
                    
                    $params = [
                        $_POST['title_en'], $_POST['title_ku'],
                        $_POST['description_en'], $_POST['description_ku'],
                        $_POST['year'], (int)$_POST['display_order']
                    ];

                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $imageFile = handleFileUpload($_FILES['image'], 'uploads/achievements/');
                        $sql .= ", image_path = ?";
                        $params[] = 'uploads/achievements/' . $imageFile;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $id;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $message = "Achievement updated successfully!";
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'edit_experience':
                try {
                    $id = (int)$_POST['id'];
                    
                    $sql = "UPDATE experience SET 
                            company_en = ?, company_ku = ?,
                            position_en = ?, position_ku = ?,
                            description_en = ?, description_ku = ?,
                            year = ?, display_order = ?
                            WHERE id = ?";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_POST['company_en'], $_POST['company_ku'],
                        $_POST['position_en'], $_POST['position_ku'],
                        $_POST['description_en'], $_POST['description_ku'],
                        $_POST['year'], (int)$_POST['display_order'],
                        $id
                    ]);
                    
                    $message = "Experience updated successfully!";
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
                
            case 'edit_certificate':
                try {
                    $id = (int)$_POST['id'];
                    $imageFile = null;
                    
                    $sql = "UPDATE certificates SET 
                            title_en = ?, title_ku = ?,
                            description_en = ?, description_ku = ?,
                            issuing_organization = ?, issue_date = ?";
                    
                    $params = [
                        $_POST['title_en'], $_POST['title_ku'],
                        $_POST['description_en'], $_POST['description_ku'],
                        $_POST['issuing_organization'], $_POST['issue_date']
                    ];

                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $imageFile = handleFileUpload($_FILES['image'], 'uploads/certificates/');
                        $sql .= ", image_path = ?";
                        $params[] = 'uploads/certificates/' . $imageFile;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $id;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $message = "Certificate updated successfully!";
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;

            case 'edit_report':
                try {
                    $id = (int)$_POST['id'];
                    $reportFile = null;
                    
                    $sql = "UPDATE reports SET 
                            title_en = ?, title_ku = ?,
                            description_en = ?, description_ku = ?";
                    
                    $params = [
                        $_POST['title_en'], $_POST['title_ku'],
                        $_POST['description_en'], $_POST['description_ku']
                    ];

                    if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
                        $reportFile = handleFileUpload($_FILES['report_file'], 'uploads/reports/');
                        $sql .= ", file_url = ?";
                        $params[] = 'uploads/reports/' . $reportFile;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $id;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $message = "Report updated successfully!";
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch current data
$about = $pdo->query("SELECT * FROM about_me LIMIT 1")->fetch();
$contact = $pdo->query("SELECT * FROM contact_info LIMIT 1")->fetch();
$projects = $pdo->query("SELECT * FROM projects ORDER BY category, display_order")->fetchAll();
$certificates = $pdo->query("SELECT * FROM certificates ORDER BY display_order")->fetchAll();
// Get active CV only
$cv_resumes = $pdo->query("SELECT * FROM cv_resumes WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1")->fetch();
$achievements = $pdo->query("SELECT * FROM achievements ORDER BY display_order")->fetchAll();
$experience = $pdo->query("SELECT * FROM experience ORDER BY display_order")->fetchAll();
$reports = $pdo->query("SELECT * FROM reports ORDER BY display_order")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Portfolio Management</title>
        <link rel="stylesheet" href="assets/css/style.css">


<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #333;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

/* Header Styles */
.header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 1.5rem 5%;
    margin-bottom: 2rem;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
}

.header h1 {
    font-size: 2rem;
    color: #2c3e50;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
}

.header-links {
    display: flex;
    gap: 1.5rem;
    margin-top: 1rem;
}

.header-links a {
    text-decoration: none;
    color: #555;
    transition: all 0.3s ease;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.header-links a:hover {
    color: #667eea;
    transform: translateY(-2px);
}

.header-links a i {
    font-size: 1.2rem;
}

/* Navigation Tabs */
.nav {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin: 2rem 0;
    justify-content: center;
}

.nav button {
    padding: 1rem 2rem;
    border: none;
    border-radius: 25px;
    background: rgba(255, 255, 255, 0.9);
    color: #555;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav button:hover,
.nav button.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

/* Section Styles */
.section {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 2.5rem;
    margin: 0 5% 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: none;
}

.section.active {
    display: block;
    animation: fadeIn 0.4s ease-out;
}

.section h2 {
    font-size: 2rem;
    color: #2c3e50;
    margin-bottom: 2.5rem;
    text-align: center;
    position: relative;
}

.section h2::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 4px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 2px;
}

/* Form Styles */
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.8rem;
    color: #2c3e50;
    font-weight: 500;
    font-size: 1.1rem;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="url"],
.form-group input[type="number"],
.form-group input[type="date"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Grid Layout */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

/* Item List Styles */
.item-list {
    margin-top: 3rem;
}

.item {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    border-left: 4px solid #667eea;
}

.item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.item h4 {
    color: #2c3e50;
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

/* Button Styles */
.btn {
    padding: 1rem 2rem;
    border: none;
    border-radius: 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
}

/* File Upload Styles */
.drag-drop {
    border: 2px dashed #667eea;
    border-radius: 15px;
    padding: 2.5rem;
    text-align: center;
    background: rgba(255, 255, 255, 0.9);
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
}

.drag-drop:hover {
    background: rgba(102, 126, 234, 0.05);
    border-color: #764ba2;
}

.drag-drop i {
    font-size: 2.5rem;
    color: #667eea;
    margin-bottom: 1rem;
}

.drag-drop p {
    color: #4a5568;
    margin-bottom: 0.5rem;
}

/* Message Styles */
.message {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    animation: slideIn 0.4s ease;
}

.message.success {
    background: #c6f6d5;
    color: #2f855a;
    border: 1px solid #9ae6b4;
}

.message.error {
    background: #fed7d7;
    color: #c53030;
    border: 1px solid #feb2b2;
}

/* Note Styles */
.note {
    color: #666;
    font-style: italic;
    margin-bottom: 1rem;
}

.current-cv {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

.current-cv .card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    margin-top: 1rem;
}

#cv-drop-zone {
    border: 2px dashed #667eea;
    padding: 2rem;
    text-align: center;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

#cv-drop-zone:hover {
    background: rgba(102, 126, 234, 0.05);
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .header {
        padding: 1rem 3%;
    }
    
    .section {
        padding: 1.5rem;
        margin: 0 3% 1.5rem;
    }
    
    .grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .nav {
        flex-direction: column;
        align-items: stretch;
        padding: 0 3%;
    }
    
    .nav button {
        width: 100%;
        justify-content: center;
    }
}
</style>
<script src="edit_functions.js"></script>
</head>
<body>
    <div class="header">
        <h1>Portfolio Admin Panel</h1>
        <p>Manage your portfolio content</p>
      
        <div class="header-links">  
            <a href="index.php" class="view-portfolio">View Portfolio</a> | 
        <a href="logout.php"  class="logout"> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="nav">
            <button onclick="showSection('about')">About Me</button>
            <button onclick="showSection('projects')">Projects</button>
            <button onclick="showSection('certificates')">Certificates</button>
            <button onclick="showSection('cv')">CV Management</button>
            <button onclick="showSection('achievements')">Achievements</button>
            <button onclick="showSection('experience')">Experience</button>
            <button onclick="showSection('reports')">Reports</button>
            <button onclick="showSection('contact')">Contact</button>
        </div>

        <!-- About Me Section -->
        <div id="about" class="section active">
            <h2>About Me Information</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_about">
                
                <div class="grid">
                    <div class="form-group">
                        <label>Name (English)</label>
                        <input type="text" name="name_en" value="<?php echo htmlspecialchars($about['name_en'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Name (Kurdish)</label>
                        <input type="text" name="name_ku" value="<?php echo htmlspecialchars($about['name_ku'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Title (English)</label>
                        <input type="text" name="title_en" value="<?php echo htmlspecialchars($about['title_en'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Title (Kurdish)</label>
                        <input type="text" name="title_ku" value="<?php echo htmlspecialchars($about['title_ku'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="description_en" required><?php echo htmlspecialchars($about['description_en'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish)</label>
                        <textarea name="description_ku" required><?php echo htmlspecialchars($about['description_ku'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>University (English)</label>
                        <input type="text" name="university_en" value="<?php echo htmlspecialchars($about['university_en'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>University (Kurdish)</label>
                        <input type="text" name="university_ku" value="<?php echo htmlspecialchars($about['university_ku'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Skills (English) - Comma separated</label>
                        <input type="text" name="skills_en" value="<?php echo htmlspecialchars($about['skills_en'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Skills (Kurdish) - Comma separated</label>
                        <input type="text" name="skills_ku" value="<?php echo htmlspecialchars($about['skills_ku'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Profile Image Upload -->
<div class="form-group">
    <label>Profile Image</label>
    <div class="drag-drop" id="profile-drop-zone">
        <i class="fas fa-cloud-upload-alt"></i>
        <p>Drag & drop your image here or click to select</p>
        <input type="file" name="profile_image" id="profile-file" 
               accept="image/*" style="display: none;">
        <div id="profile-file-name"></div>
    </div>
</div>
                
                <div class="btn-group">
    <button type="submit" class="btn-update">
        <i class="fas fa-save"></i>
        <?php echo ($lang === 'ku') ? 'نوێکردنەوە' : 'Update'; ?>
    </button>
    <button type="button" class="btn-update" style="background: #718096;" onclick="cancelEdit('editForm')">
        <i class="fas fa-times"></i>
        <?php echo ($lang === 'ku') ? 'هەڵوەشاندنەوە' : 'Cancel'; ?>
    </button>
</div>
            </form>
        </div>

        <!-- Projects Section -->
        <div id="projects" class="section">
            <h2>Projects Management</h2>
            
            <h3>Add New Project</h3>
            <form method="POST" enctype="multipart/form-data" class="add-form" id="addProjectForm">
                <input type="hidden" name="action" value="add_project">
                
                <div class="grid">
                    <div class="form-group">
                        <label>Title (English)</label>
                        <input type="text" name="title_en" required>
                    </div>
                    <div class="form-group">
                        <label>Title (Kurdish)</label>
                        <input type="text" name="title_ku" required>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="description_en" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish)</label>
                        <textarea name="description_ku" required></textarea>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="completed">Completed</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="concept">Concept</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" value="0">
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Technologies (English) - Comma separated</label>
                        <input type="text" name="technologies_en">
                    </div>
                    <div class="form-group">
                        <label>Technologies (Kurdish) - Comma separated</label>
                        <input type="text" name="technologies_ku">
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Project Link</label>
                        <input type="url" name="project_link" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label>Project Link Text (English)</label>
                        <input type="text" name="project_link_text_en" value="View Project">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Project Link Text (Kurdish)</label>
                    <input type="text" name="project_link_text_ku" value="بینینی پڕۆژە">
                </div>

                <div class="form-group">
                    <label>Project Image</label>
                    <input type="file" name="image" accept="image/*" id="project-file" 
                           class="drag-drop" id="project-drop-zone" >
                </div>
                
                <div class="form-group">
                    <label>Project Status</label>
                    <select name="status" required>
                        <option value="completed">Completed</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="concept">Concept</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Add Project</button>
            </form>
            
            <div class="item-list">
                <h3>Existing Projects</h3>
                <?php foreach ($projects as $project): ?>
                    <div class="item">
                        <h4><?php echo htmlspecialchars($project['title_en']); ?> / <?php echo htmlspecialchars($project['title_ku']); ?></h4>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($project['category']); ?></p>
                        <p><strong>EN:</strong> <?php echo htmlspecialchars(substr($project['description_en'], 0, 100)); ?>...</p>
                        <p><strong>KU:</strong> <?php echo htmlspecialchars(substr($project['description_ku'], 0, 100)); ?>...</p>
                        <div class="item-actions">
        <button onclick="editProject(<?php echo $project['id']; ?>)" class="btn btn-edit">Edit</button>
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="delete_item">
            <input type="hidden" name="table" value="projects">
            <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
        </form>
    </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="editProjectForm" class="edit-form" style="display: none;">
    <h3>Edit Project</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit_project">
        <input type="hidden" name="id" id="edit_project_id">
        
        <div class="grid">
            <div class="form-group">
                <label>Title (English)</label>
                <input type="text" name="title_en" id="edit_title_en" required>
            </div>
            <div class="form-group">
                <label>Title (Kurdish)</label>
                <input type="text" name="title_ku" id="edit_title_ku" required>
            </div>
        </div>
        
        <div class="grid">
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="description_en" id="edit_description_en" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish)</label>
                        <textarea name="description_ku" id="edit_description_ku" required></textarea>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="edit_category" required>
                            <option value="completed">Completed</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="concept">Concept</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" id="edit_display_order" value="0">
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Technologies (English) - Comma separated</label>
                        <input type="text" name="technologies_en" id="edit_technologies_en">
                    </div>
                    <div class="form-group">
                        <label>Technologies (Kurdish) - Comma separated</label>
                        <input type="text" name="technologies_ku" id="edit_technologies_ku">
                    </div>
                </div>
                
                <div class="form-group">
                    <div id="current_project_image"></div>
                    <label>Project Image (Leave empty to keep current image)</label>
                    <input type="file" name="image" accept="image/*" id="edit_image">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Update Project</button>
                    <button type="button" class="btn btn-secondary" onclick="cancelEdit('editProjectForm')">Cancel</button>
                </div>
    </form>
</div>
        </div>

        <!-- Certificates Section -->
        <div id="certificates" class="section">
            <h2>Certificates Management</h2>
            
            <h3>Add New Certificate</h3>
            <form method="POST" enctype="multipart/form-data" class="add-form">
                <input type="hidden" name="action" value="add_certificate">
                
                <div class="grid">
                    <div class="form-group">
                        <label>Title (English)</label>
                        <input type="text" name="title_en" required>
                    </div>
                    <div class="form-group">
                        <label>Title (Kurdish)</label>
                        <input type="text" name="title_ku" required>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="description_en" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish)</label>
                        <textarea name="description_ku" required></textarea>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Issue Date</label>
                        <input type="date" name="issue_date" required>
                    </div>
                    <div class="form-group">
                        <label>Issuing Organization</label>
                        <input type="text" name="issuing_organization" required>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" required placeholder="e.g., IT, AI, Programming">
                    </div>
                    <div class="form-group">
                        <label>Serial Number</label>
                        <input type="text" name="serial_number">
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" value="0">
                    </div>
                    <div class="form-group">
                        <label>Certificate Image</label>
                        <input type="file" name="image" accept="image/*" required>
                    </div>
                </div>
                
                <button type="submit" class="btn">Add Certificate</button>
            </form>
            
            <div class="item-list">
                <h3>Existing Certificates</h3>
                <?php foreach ($certificates as $cert): ?>
                    <div class="item">
                        <h4><?php echo htmlspecialchars($cert['title_en']); ?> / <?php echo htmlspecialchars($cert['title_ku']); ?></h4>
                        <p><strong>Organization:</strong> <?php echo htmlspecialchars($cert['issuing_organization']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($cert['issue_date']); ?></p>
                        <div class="item-actions">
                            <button onclick="editCertificate(<?php echo $cert['id']; ?>)" class="btn btn-edit">Edit</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="table" value="certificates">
                                <input type="hidden" name="id" value="<?php echo $cert['id']; ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="editCertificateForm" class="edit-form" style="display: none;">
    <h3>Edit Certificate</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit_certificate">
        <input type="hidden" name="id" id="edit_certificate_id">
        
        <div class="grid">
            <div class="form-group">
                <label>Title (English)</label>
                <input type="text" name="title_en" id="edit_certificate_title_en" required>
            </div>
            <div class="form-group">
                <label>Title (Kurdish)</label>
                <input type="text" name="title_ku" id="edit_certificate_title_ku" required>
            </div>
        </div>
        
        <div class="grid">
            <div class="form-group">
                <label>Description (English)</label>
                <textarea name="description_en" id="edit_certificate_description_en"></textarea>
            </div>
            <div class="form-group">
                <label>Description (Kurdish)</label>
                <textarea name="description_ku" id="edit_certificate_description_ku"></textarea>
            </div>
        </div>
        
        <div class="grid">
            <div class="form-group">
                <label>Issue Date</label>
                <input type="date" name="issue_date" id="edit_certificate_issue_date" required>
            </div>
            <div class="form-group">
                <label>Issuing Organization</label>
                <input type="text" name="issuing_organization" id="edit_certificate_org" required>
            </div>
        </div>
        
        <div class="form-group">
            <div id="current_certificate_image"></div>
            <label>Certificate Image (Leave empty to keep current image)</label>
            <input type="file" name="image" accept="image/*">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Update Certificate</button>
            <button type="button" class="btn btn-secondary" onclick="cancelEdit('editCertificateForm')">Cancel</button>
        </div>
    </form>
</div>
        </div>

        <!-- CV Management Section -->
        <div id="cv" class="section">
            <h2>CV Management</h2>
            
            <h3>Upload New CV</h3>
            <p class="note">Note: Uploading a new CV will automatically deactivate the previous one</p>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_cv">
                
                <div class="grid">
                    <div class="form-group">
                        <label>Title (English)</label>
                        <input type="text" name="title_en" required>
                    </div>
                    <div class="form-group">
                        <label>Title (Kurdish)</label>
                        <input type="text" name="title_ku" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Language</label>
                    <select name="language" required>
                        <option value="both">Both Languages</option>
                        <option value="en">English Only</option>
                        <option value="ku">Kurdish Only</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>CV File (PDF)</label>
                    <div class="drag-drop" id="cv-drop-zone">
                        <i class="fas fa-file-pdf"></i>
                        <p>Drop your CV file here or click to select</p>
                        <input type="file" name="cv_file" accept=".pdf" required id="cv-file">
                        <div id="cv-file-name"></div>
                    </div>
                </div>
                
                <button type="submit" class="btn">Upload New CV</button>
            </form>

            <?php if ($cv_resumes): ?>
            <div class="current-cv">
                <h3>Current Active CV</h3>
                <div class="card">
                    <h4><?php echo htmlspecialchars($cv_resumes['title_en']); ?> / <?php echo htmlspecialchars($cv_resumes['title_ku']); ?></h4>
                    <p>Language: <?php echo htmlspecialchars($cv_resumes['language']); ?></p>
                    <a href="<?php echo htmlspecialchars($cv_resumes['file_path']); ?>" class="btn" target="_blank">View Current CV</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Achievements Section -->
        <div id="achievements" class="section">
            <h2>Achievements Management</h2>
            
            <h3>Add New Achievement</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_achievement">
                
                <div class="grid">
                    <div class="form-group">
                        <label>Title (English)</label>
                        <input type="text" name="title_en" required>
                    </div>
                    <div class="form-group">
                        <label>Title (Kurdish)</label>
                        <input type="text" name="title_ku" required>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="description_en"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish)</label>
                        <textarea name="description_ku"></textarea>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Year</label>
                        <input type="text" name="year" placeholder="e.g., 2024">
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" value="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Achievement Image</label>
                    <div class="drag-drop" id="achievement-drop-zone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop your image here or click to select</p>
                        <input type="file" name="image" accept="image/*" id="achievement-file">
                        <div id="achievement-file-name"></div>
                    </div>
                </div>
                
                <button type="submit" class="btn">Add Achievement</button>
            </form>

            <div class="item-list">
                <h3>Existing Achievements</h3>
                <?php foreach ($achievements as $achievement): ?>
                    <div class="item">
                        <div class="item-content">
                            <?php if ($achievement['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($achievement['image_path']); ?>" 
                                     alt="Achievement image" style="max-width: 100px; border-radius: 8px;">
                            <?php endif; ?>
                            <div>
                                <h4><?php echo htmlspecialchars($achievement['title_en']); ?> / <?php echo htmlspecialchars($achievement['title_ku']); ?></h4>
                                <p><strong>Year:</strong> <?php echo htmlspecialchars($achievement['year']); ?></p>
                            </div>
                        </div>
                        <div class="item-actions">
                            <button onclick="editAchievement(<?php echo $achievement['id']; ?>)" class="btn btn-edit">Edit</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="table" value="achievements">
                                <input type="hidden" name="id" value="<?php echo $achievement['id']; ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="editAchievementForm" class="edit-form" style="display: none;">
    <h3>Edit Achievement</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit_achievement">
        <input type="hidden" name="id" id="edit_achievement_id">
        
        <div class="grid">
            <div class="form-group">
                <label>Title (English)</label>
                <input type="text" name="title_en" id="edit_achievement_title_en" required>
            </div>
            <div class="form-group">
                <label>Title (Kurdish)</label>
                <input type="text" name="title_ku" id="edit_achievement_title_ku" required>
            </div>
        </div>
        
        <div class="grid">
            <div class="form-group">
                <label>Description (English)</label>
                <textarea name="description_en" id="edit_achievement_description_en"></textarea>
            </div>
            <div class="form-group">
                <label>Description (Kurdish)</label>
                <textarea name="description_ku" id="edit_achievement_description_ku"></textarea>
            </div>
        </div>
        
        <div class="form-group">
            <div id="current_achievement_image"></div>
            <label>Achievement Image</label>
            <input type="file" name="image" accept="image/*">
        </div>
        
        <div class="grid">
            <div class="form-group">
                <label>Year</label>
                <input type="text" name="year" id="edit_achievement_year">
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" id="edit_achievement_display_order" value="0">
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Update Achievement</button>
            <button type="button" class="btn btn-secondary" onclick="cancelEdit('editAchievementForm')">Cancel</button>
        </div>
    </form>
</div>
        </div>

        <!-- Experience Section -->
        <div id="experience" class="section">
            <h2>Experience Management</h2>
            
            <h3>Add New Experience</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_experience">
                
                <div class="grid">
                    <div class="form-group">
                        <label>Company (English)</label>
                        <input type="text" name="company_en" required>
                    </div>
                    <div class="form-group">
                        <label>Company (Kurdish)</label>
                        <input type="text" name="company_ku" required>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Position (English)</label>
                        <input type="text" name="position_en" required>
                    </div>
                    <div class="form-group">
                        <label>Position (Kurdish)</label>
                        <input type="text" name="position_ku" required>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="description_en"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish)</label>
                        <textarea name="description_ku"></textarea>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Year</label>
                        <input type="text" name="year" placeholder="e.g., 2023-2024">
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" value="0">
                    </div>
                </div>
                
                <button type="submit" class="btn">Add Experience</button>
            </form>
            
            <div class="item-list">
                <h3>Existing Experience</h3>
                <?php foreach ($experience as $exp): ?>
                    <div class="item">
                        <h4><?php echo htmlspecialchars($exp['company_en']); ?> / <?php echo htmlspecialchars($exp['company_ku']); ?></h4>
                        <p><strong>Position:</strong> <?php echo htmlspecialchars($exp['position_en']); ?> / <?php echo htmlspecialchars($exp['position_ku']); ?></p>
                        <p><strong>Year:</strong> <?php echo htmlspecialchars($exp['year']); ?></p>
                        <div class="item-actions">
        <button onclick="editExperience(<?php echo $exp['id']; ?>)" class="btn btn-edit">Edit</button>
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="delete_item">
            <input type="hidden" name="table" value="experience">
            <input type="hidden" name="id" value="<?php echo $exp['id']; ?>">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
        </form>
    </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="editExperienceForm" class="edit-form" style="display: none;">
    <h3>Edit Experience</h3>
    <form method="POST">
        <input type="hidden" name="action" value="edit_experience">
        <input type="hidden" name="id" id="edit_experience_id">
        
        <div class="grid">
            <div class="form-group">
                <label>Company (English)</label>
                <input type="text" name="company_en" id="edit_company_en" required>
            </div>
            <div class="form-group">
                <label>Company (Kurdish)</label>
                <input type="text" name="company_ku" id="edit_company_ku" required>
            </div>
        </div>
        
        <div class="grid">
            <div class="form-group">
                <label>Position (English)</label>
                <input type="text" name="position_en" id="edit_position_en" required>
            </div>
            <div class="form-group">
                <label>Position (Kurdish)</label>
                <input type="text" name="position_ku" id="edit_position_ku" required>
            </div>
        </div>
        
        <div class="grid">
            <div class="form-group">
                <label>Description (English)</label>
                <textarea name="description_en" id="edit_description_en"></textarea>
            </div>
            <div class="form-group">
                <label>Description (Kurdish)</label>
                <textarea name="description_ku" id="edit_description_ku"></textarea>
            </div>
        </div>
        
        <div class="grid">
            <div class="form-group">
                <label>Year</label>
                <input type="text" name="year" id="edit_year">
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" id="edit_display_order" value="0">
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Update Experience</button>
            <button type="button" class="btn btn-secondary" onclick="cancelEdit('editExperienceForm')">Cancel</button>
        </div>
    </form>
</div>
        </div>

        <!-- Reports Section -->
        <div id="reports" class="section">
            <h2>Reports Management</h2>
            
            <h3>Add New Report</h3>
            <form method="POST" enctype="multipart/form-data" class="add-form">
                <input type="hidden" name="action" value="add_report">
                
                <div class="grid">
                    <div class="form-group">
                        <label>Title (English)</label>
                        <input type="text" name="title_en" required>
                    </div>
                    <div class="form-group">
                        <label>Title (Kurdish)</label>
                        <input type="text" name="title_ku" required>
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="description_en"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish)</label>
                        <textarea name="description_ku"></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Report File</label>
                    <div class="drag-drop" id="report-drop-zone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop your file here or click to select</p>
                        <input type="file" name="report_file" id="report-file" 
                               accept=".pdf,.doc,.docx" style="display: none;">
                        <div id="report-file-name"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" value="0">
                </div>
                
                <button type="submit" class="btn">Add Report</button>
            </form>
            
            <div class="item-list">
                <h3>Existing Reports</h3>
                <?php foreach ($reports as $report): ?>
                    <div class="item">
                        <h4><?php echo htmlspecialchars($report['title_en']); ?> / <?php echo htmlspecialchars($report['title_ku']); ?></h4>
                        <p><strong>EN:</strong> <?php echo htmlspecialchars($report['description_en']); ?></p>
                        <p><strong>KU:</strong> <?php echo htmlspecialchars($report['description_ku']); ?></p>
                        <?php if ($report['file_url']): ?>
                            <p><strong>File:</strong> <a href="<?php echo htmlspecialchars($report['file_url']); ?>" target="_blank">Download</a></p>
                        <?php endif; ?>
                        <div class="item-actions">
                            <button onclick="editReport(<?php echo $report['id']; ?>)" class="btn btn-edit">Edit</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="table" value="reports">
                                <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="editReportForm" class="edit-form" style="display: none;">
    <h3>Edit Report</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit_report">
        <input type="hidden" name="id" id="edit_report_id">
        
        <div class="grid">
            <div class="form-group">
                <label>Title (English)</label>
                <input type="text" name="title_en" id="edit_report_title_en" required>
            </div>
            <div class="form-group">
                <label>Title (Kurdish)</label>
                <input type="text" name="title_ku" id="edit_report_title_ku" required>
            </div>
        </div>
        
        <div class="grid">
            <div class="form-group">
                <label>Description (English)</label>
                <textarea name="description_en" id="edit_report_description_en"></textarea>
            </div>
            <div class="form-group">
                <label>Description (Kurdish)</label>
                <textarea name="description_ku" id="edit_report_description_ku"></textarea>
            </div>
        </div>
        
        <div class="form-group">
            <div id="current_report_file"></div>
            <label>Report File (Leave empty to keep current file)</label>
            <input type="file" name="report_file" accept=".pdf,.doc,.docx">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Update Report</button>
            <button type="button" class="btn btn-secondary" onclick="cancelEdit('editReportForm')">Cancel</button>
        </div>
    </form>
</div>
        </div>

        <!-- Contact Section -->
        <div id="contact" class="section">
            <h2>Contact Information</h2>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_contact">
                
                <div class="grid">
                    <div class="form-group">
                        <label>LinkedIn URL</label>
                        <input type="url" name="linkedin" value="<?php echo htmlspecialchars($contact['linkedin'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($contact['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="grid">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($contact['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>CV URL</label>
                        <input type="url" name="cv_url" value="<?php echo htmlspecialchars($contact['cv_url'] ?? ''); ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn">Update Contact Info</button>
            </form>
        </div>
    </div>

<script>
function editCertificate(id) {
    // Hide add form
    const addForm = document.querySelector('.add-form');
    if (addForm) addForm.style.display = 'none';
    
    // Show edit form
    const editForm = document.getElementById('editCertificateForm');
    if (editForm) {
        editForm.style.display = 'block';
        
        fetch(`get_item.php?table=certificates&id=${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_certificate_id').value = data.id;
                document.getElementById('edit_certificate_title_en').value = data.title_en || '';
                document.getElementById('edit_certificate_title_ku').value = data.title_ku || '';
                document.getElementById('edit_certificate_description_en').value = data.description_en || '';
                document.getElementById('edit_certificate_description_ku').value = data.description_ku || '';
                document.getElementById('edit_certificate_issue_date').value = data.issue_date || '';
                document.getElementById('edit_certificate_org').value = data.issuing_organization || '';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading certificate: ' + error.message);
            });
    }
}

function editExperience(id) {
    const addForm = document.querySelector('.add-form');
    if (addForm) addForm.style.display = 'none';
    
    const editForm = document.getElementById('editExperienceForm');
    if (editForm) {
        editForm.style.display = 'block';
        
        fetch(`get_item.php?table=experience&id=${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_experience_id').value = data.id;
                document.getElementById('edit_company_en').value = data.company_en || '';
                document.getElementById('edit_company_ku').value = data.company_ku || '';
                document.getElementById('edit_position_en').value = data.position_en || '';
                document.getElementById('edit_position_ku').value = data.position_ku || '';
                document.getElementById('edit_description_en').value = data.description_en || '';
                document.getElementById('edit_description_ku').value = data.description_ku || '';
                document.getElementById('edit_year').value = data.year || '';
                document.getElementById('edit_display_order').value = data.display_order || 0;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading experience: ' + error.message);
            });
    }
}

function editAchievement(id) {
    const addForm = document.querySelector('.add-form');
    if (addForm) addForm.style.display = 'none';
    
    const editForm = document.getElementById('editAchievementForm');
    if (editForm) {
        editForm.style.display = 'block';
        
        fetch(`get_item.php?table=achievements&id=${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_achievement_id').value = data.id;
                document.getElementById('edit_achievement_title_en').value = data.title_en || '';
                document.getElementById('edit_achievement_title_ku').value = data.title_ku || '';
                document.getElementById('edit_achievement_description_en').value = data.description_en || '';
                document.getElementById('edit_achievement_description_ku').value = data.description_ku || '';
                document.getElementById('edit_achievement_year').value = data.year || '';
                document.getElementById('edit_achievement_display_order').value = data.display_order || 0;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading achievement: ' + error.message);
            });
    }
}

function editReport(id) {
    const addForm = document.querySelector('.add-form');
    if (addForm) addForm.style.display = 'none';
    
    const editForm = document.getElementById('editReportForm');
    if (editForm) {
        editForm.style.display = 'block';
        
        fetch(`get_item.php?table=reports&id=${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_report_id').value = data.id;
                document.getElementById('edit_report_title_en').value = data.title_en || '';
                document.getElementById('edit_report_title_ku').value = data.title_ku || '';
                document.getElementById('edit_report_description_en').value = data.description_en || '';
                document.getElementById('edit_report_description_ku').value = data.description_ku || '';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading report: ' + error.message);
            });
    }
}

// Section Navigation
function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(sectionId).classList.add('active');
}

// File Upload Handling
function setupFileUpload(prefix, acceptTypes) {
    const dropZone = document.getElementById(`${prefix}-drop-zone`);
    const fileInput = document.getElementById(`${prefix}-file`);
    const fileNameDisplay = document.getElementById(`${prefix}-file-name`);

    if (!dropZone || !fileInput) return;

    // Click to select
    dropZone.addEventListener('click', () => fileInput.click());

    // File input change
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    // Drag and drop events
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    function handleFiles(files) {
        if (!files.length) return;
        
        const file = files[0];
        const fileType = file.type;
        const validTypes = acceptTypes.split(',');
        
        if (validTypes.includes(fileType) || 
            validTypes.some(type => file.name.toLowerCase().endsWith(type.replace('*','')))) {
            
            fileInput.files = files;
            
            if (fileType.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    fileNameDisplay.innerHTML = `
                        <img src="${e.target.result}" style="max-width: 150px; margin-top: 10px"><br>
                        Selected: ${file.name}
                    `;
                }
                reader.readAsDataURL(file);
            } else {
                fileNameDisplay.textContent = `Selected: ${file.name}`;
            }
            
            dropZone.classList.add('has-file');
        } else {
            alert('Invalid file type. Please select a valid file.');
        }
    }
}

// Edit form functionality
function editProject(id) {
    fetch(`get_item.php?table=projects&id=${id}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.error) throw new Error(data.error);

            // Show edit form and hide add form
            document.querySelector('.add-form').style.display = 'none';
            const editForm = document.getElementById('editProjectForm');
            editForm.style.display = 'block';
            editForm.classList.add('loading');
            
            // Fill form fields with existing data
            Object.keys(data).forEach(key => {
                const input = document.getElementById(`edit_${key}`);
                if (input) {
                    input.value = data[key] || '';
                }
                
            });

            // Show current image if exists
            if (data.image_url) {
                const imagePreview = document.getElementById('current_project_image');
                imagePreview.innerHTML = `
                    <div class="current-image">
                        <img src="${data.image_url}" alt="Current image" style="max-width: 200px">
                        <p>Current image</p>
                    </div>`;
            }

            editForm.classList.remove('loading');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading project: ' + error.message);
            document.getElementById('editProjectForm').style.display = 'none';
            document.querySelector('.add-form').style.display = 'block';
        });
}

function cancelEdit(formId) {
    // Hide edit form and show add form
    document.getElementById(formId).style.display = 'none';
    document.querySelector('.add-form').style.display = 'block';
    
    // Clear edit form
    document.getElementById(formId).reset();
}

// Add form submission handler
function initFormSubmission() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            submitButton.disabled = true;
            submitButton.innerHTML = 'Processing...';
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const message = doc.querySelector('.message');
                
                if (message) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = message.className;
                    messageDiv.textContent = message.textContent;
                    
                    const existingMessages = form.parentElement.querySelectorAll('.message');
                    existingMessages.forEach(msg => msg.remove());
                    
                    form.parentElement.insertBefore(messageDiv, form);
                    
                    if (message.classList.contains('success')) {
                        const sectionId = form.closest('.section').id;
                        refreshSection(sectionId);
                        
                        // Hide edit form and show add form after successful update
                        if (form.closest('.edit-form')) {
                            form.closest('.edit-form').style.display = 'none';
                            document.querySelector('.add-form').style.display = 'block';
                        }
                    }
                }
                
                if (form.classList.contains('add-form')) {
                    form.reset();
                }
                
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting form');
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize file uploads
    setupFileUpload('profile', 'image/*');
    setupFileUpload('project', 'image/*');
    setupFileUpload('certificate', 'image/*');
    setupFileUpload('cv', '.pdf');
    setupFileUpload('achievement', 'image/*');
    setupFileUpload('report', '.pdf,.doc,.docx');
    
    // Set current section from URL
    const currentSection = new URLSearchParams(window.location.search).get('section') || 'about';
    showSection(currentSection);
    
    // Initialize form submissions
    initFormSubmission();
});

function refreshSection(sectionId) {
    fetch(`admin.php?section=${sectionId}`)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newSection = doc.getElementById(sectionId);
            
            if (newSection) {
                const itemList = newSection.querySelector('.item-list');
                if (itemList) {
                    const currentItemList = document.querySelector(`#${sectionId} .item-list`);
                    if (currentItemList) {
                        currentItemList.innerHTML = itemList.innerHTML;
                    }
                }
            }
        })
        .catch(error => console.error('Error refreshing section:', error));
}

function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(sectionId).classList.add('active');
    
    // Update URL without page reload
    const url = new URL(window.location);
    url.searchParams.set('section', sectionId);
    window.history.pushState({}, '', url);
}
</script>
</body>
</html>