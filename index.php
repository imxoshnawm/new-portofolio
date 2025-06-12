<?php
require_once 'config.php';

// Check if language is set in URL, default to English
$lang = isset($_GET['lang']) && $_GET['lang'] === 'ku' ? 'ku' : 'en';

$pdo = getConnection();
$about = $pdo->query("SELECT * FROM about_me LIMIT 1")->fetch();
$contact = $pdo->query("SELECT * FROM contact_info LIMIT 1")->fetch();
$projects = $pdo->query("SELECT * FROM projects ORDER BY category, display_order LIMIT 6")->fetchAll();
$achievements = $pdo->query("SELECT * FROM achievements ORDER BY display_order LIMIT 4")->fetchAll();
$experience = $pdo->query("SELECT * FROM experience ORDER BY display_order, year DESC")->fetchAll();
$certificates = $pdo->query("SELECT * FROM certificates ORDER BY display_order LIMIT 6")->fetchAll();
$reports = $pdo->query("SELECT * FROM reports ORDER BY display_order LIMIT 4")->fetchAll();
$cv_resumes = $pdo->query("SELECT * FROM cv_resumes WHERE is_active = 1 ORDER BY display_order LIMIT 1")->fetch();
$projects_completed = $pdo->query("SELECT * FROM projects WHERE category = 'completed' ORDER BY display_order")->fetchAll();
$projects_ongoing = $pdo->query("SELECT * FROM projects WHERE category = 'ongoing' ORDER BY display_order")->fetchAll();
$projects_concept = $pdo->query("SELECT * FROM projects WHERE category = 'concept' ORDER BY display_order")->fetchAll();
// Helper function to get localized text
function getLocalizedText($data, $field, $lang, $default = '') {
    $key = $field . '_' . $lang;
    return isset($data[$key]) && !empty($data[$key]) ? $data[$key] : 
           (isset($data[$field]) ? $data[$field] : $default);
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>   
   /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #dee2e6 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(52, 152, 219, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(52, 152, 219, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(52, 152, 219, 0.06) 0%, transparent 50%),
                linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { transform: translateX(0) translateY(0); }
            25% { transform: translateX(2px) translateY(-2px); }
            50% { transform: translateX(-1px) translateY(1px); }
            75% { transform: translateX(1px) translateY(-1px); }
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(52, 152, 219, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #3498db;
            text-shadow: 0 2px 4px rgba(52, 152, 219, 0.3);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #1a1a1a;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #3498db;
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-links a:hover {
            color: #3498db;
            transform: translateY(-2px);
        }

        .lang-switcher {
            display: flex;
            gap: 0.5rem;
        }

        .lang-switcher a {
            padding: 0.5rem 1rem;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 20px;
            text-decoration: none;
            color: #3498db;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .lang-switcher a.active,
        .lang-switcher a:hover {
            background: #3498db;
            color: white;
            transform: scale(1.05);
        }

        /* Mobile menu */
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 0.5rem;
        }

        .mobile-menu-toggle span {
            width: 25px;
            height: 3px;
            background: #3498db;
            margin: 3px 0;
            transition: 0.3s;
            border-radius: 2px;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 249, 250, 0.8) 100%);
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 30%, rgba(52, 152, 219, 0.05) 0%, transparent 60%),
                radial-gradient(circle at 70% 70%, rgba(52, 152, 219, 0.03) 0%, transparent 60%);
            z-index: -1;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: #1a1a1a;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 1s ease;
        }

        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            color: #3498db;
            animation: fadeInUp 1s ease 0.3s both;
        }

        .hero-content div {
            animation: fadeInUp 1s ease 0.6s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            margin: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }

        .btn-secondary {
            background: rgba(26, 26, 26, 0.9);
            box-shadow: 0 4px 15px rgba(26, 26, 26, 0.3);
        }

        .btn-secondary:hover {
            box-shadow: 0 8px 25px rgba(26, 26, 26, 0.4);
        }

        /* Sections */
        .section {
            padding: 5rem 2rem;
            position: relative;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #1a1a1a;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2980b9);
            border-radius: 2px;
        }

        /* Cards */
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(52, 152, 219, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(52, 152, 219, 0.1);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2980b9);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(52, 152, 219, 0.15);
        }

        /* Project styles */
        .project-card {
            display: flex;
            align-items: center;
            margin-bottom: 4rem;
            gap: 3rem;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .project-card:hover {
            transform: translateY(-5px);
        }

        .project-card:nth-child(even) {
            flex-direction: row-reverse;
        }

        .project-card img {
            width: 50%;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
        }

        .project-meta {
            flex: 1;
            padding: 2rem;
        }

        .project-meta h3 {
            color: #3498db;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .project-meta p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        #projects .grid {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .project-list {
            display: flex;
            flex-direction: column;
            gap: 6rem;
            padding: 2rem 0;
        }

        .project-item {
            display: flex;
            align-items: center;
            gap: 4rem;
            max-width: 1300px;
            margin: 0 auto;
        }

        /* Odd items (1st, 3rd, etc): image right, text left */
        .project-item:nth-child(odd) {
            flex-direction: row-reverse;
        }

        /* Even items (2nd, 4th, etc): image left, text right */
        .project-item:nth-child(even) {
            flex-direction: row;
        }

        @media (max-width: 480px) {
            .project-item,
            .project-item:nth-child(odd),
            .project-item:nth-child(even) {
                flex-direction: column !important;
            }
        }

        /* Specific overrides for second and fourth items */
        .project-item:nth-child(2n) {
            flex-direction: row; /* Even items (2nd, 4th, etc): image left, text right */
        }

        .project-image {
            flex: 1;
            width: 52%; /* زیادکردن لە 48% بۆ 52% */
        }

        .project-image img {
            width: 100%;
            height: auto;
            max-height: 550px; /* زیادکردن لە 500px بۆ 550px */
            object-fit: cover;
        }

        .project-content {
            flex: 1;
            width: 48%; /* کەمکردنەوە لە 52% بۆ 48% */
            padding: 2.5rem; /* زیادکردنی padding */
        }

        .project-content h3 {
            font-size: 2.2rem; /* گەورەکردنی سایزی ناونیشان */
            margin-bottom: 1.5rem;
            color: #3498db;
        }

        .project-content p {
            font-size: 1.2rem; /* گەورەکردنی سایزی تێکست */
            line-height: 1.8;
            color: #555;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .project-item {
                flex-direction: row !important;
                gap: 1rem;
            }

            /* First project (odd): Image right, text left */
            .project-item:nth-child(odd) {
                flex-direction: row-reverse !important;
            }

            /* Second project (even): Image left, text right */
            .project-item:nth-child(even) {
                flex-direction: row !important;
            }

            .project-image,
            .project-content {
                width: 50%;
            }

            .project-image img {
                height: 300px;
                object-fit: cover;
                width: 100%;
            }

            .project-content {
                padding: 1rem;
            }

            .project-content h3 {
                font-size: 1.4rem;
            }

            .project-content p {
                font-size: 1rem;
                line-height: 1.4;
            }
        }

        /* Additional media query for very small screens */
        @media (max-width: 480px) {
            .project-item,
            .project-item:nth-child(even) {
                flex-direction: column !important;
                gap: 1rem;
            }

            .project-image,
            .project-content {
                width: 100%;
            }

            .project-image {
                width: 100%;
                aspect-ratio: 1 / 1;
                position: relative;
                background: #f0f0f0;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }

            .project-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                aspect-ratio: 1 / 1;
                display: block;
                background: #e0e0e0;
            }

            .project-content {
                padding: 1.5rem;
                text-align: center;
            }
        }

        /* Project tabs */
        .project-tabs {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(52, 152, 219, 0.2);
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #3498db;
        }

        .tab-button.active,
        .tab-button:hover {
            background: #3498db;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
        }

        .project-category {
            display: none;
        }

        .project-category.active {
            display: block;
        }

        /* Skills */
        .skills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .skill-tag {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .skill-tag:hover {
            background: #3498db;
            color: white;
            transform: scale(1.05);
        }

        /* Profile image */
        .profile-image {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-image img {
            width: 400px;
            height: 350px;
            border-radius: 0;
            object-fit: cover;
            box-shadow: 0 15px 30px rgba(52, 152, 219, 0.4);
        }

        /* Contact section */
        .contact {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.05) 0%, rgba(255, 255, 255, 0.9) 100%);
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .contact-item {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(52, 152, 219, 0.1);
            transition: all 0.3s ease;
        }

        .contact-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(52, 152, 219, 0.15);
        }

        .contact-item h3 {
            color: #3498db;
            margin-bottom: 1rem;
        }

        .contact-item a {
            color: #1a1a1a;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-item a:hover {
            color: #3498db;
        }

        /* Empty category */
        .empty-category {
            text-align: center;
            padding: 3rem;
            color: #666;
            font-style: italic;
        }

        /* Experience Section Styles */
        .experience-section {
            background: #e6f3ff;
            color: #000;
            padding: 6rem 0;
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .experience-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .experience-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 4rem;
            color: #000;
        }

        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
            padding-left: 2rem;
            display: flex;
            flex-direction: column;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #3498db;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 3rem;
            padding-left: 2rem;
            animation: fadeInUp 0.5s ease forwards;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 0;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #3498db;
            box-shadow: 0 0 0 4px 3498db(0, 255, 0, 0.2);
        }

        .timeline-date {
            color: #3498db;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .timeline-company {
            color: #000;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .timeline-position {
            color: #000;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .timeline-description {
            color: rgba(0, 0, 0, 0.7);
            line-height: 1.6;
            font-size: 1rem;
        }

        /* RTL support for timeline */
        [dir="rtl"] .timeline {
            padding-left: 0;
            padding-right: 2rem;
        }

        [dir="rtl"] .timeline::before {
            left: auto;
            right: 0;
        }

        [dir="rtl"] .timeline-item {
            padding-left: 0;
            padding-right: 2rem;
        }

        [dir="rtl"] .timeline-item::before {
            left: auto;
            right: -6px;
        }

        @media (max-width: 768px) {
            .experience-title {
                font-size: 2.5rem;
                margin-bottom: 3rem;
            }

            .timeline {
                padding-left: 1rem;
            }

            [dir="rtl"] .timeline {
                padding-left: 0;
                padding-right: 1rem;
            }

            .timeline-item {
                padding-left: 1.5rem;
            }

            [dir="rtl"] .timeline-item {
                padding-left: 0;
                padding-right: 1.5rem;
            }

            .timeline-company {
                font-size: 1.3rem;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                flex-direction: column;
                padding: 2rem;
                box-shadow: 0 4px 20px rgba(52, 152, 219, 0.1);
            }

            .nav-links.active {
                display: flex;
            }

            .mobile-menu-toggle {
                display: flex;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.1rem;
            }

            .section {
                padding: 3rem 1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            /* Mobile grid - 2 columns for specific sections */
            #projects .grid,
            #certificates .grid,
            #experience .grid,
            #achievements .grid,
            #reports .grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            /* Smaller cards for mobile */
            .card {
                padding: 1.5rem;
            }

            .project-card img {
                height: 120px;
            }

            .project-card h3 {
                font-size: 1rem;
            }

            .project-card p {
                font-size: 0.9rem;
            }

            .btn {
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }

            .tab-button {
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }

            .project-tabs {
                gap: 0.5rem;
            }

            .skills {
                justify-content: center;
            }

            .skill-tag {
                font-size: 0.8rem;
                padding: 0.2rem 0.6rem;
            }
        }

        @media (max-width: 480px) {
            .nav {
                padding: 1rem;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .section {
                padding: 2rem 0.5rem;
            }

            .section-title {
                font-size: 1.8rem;
            }

            /* Single column for very small screens */
            #projects .grid,
            #certificates .grid,
            #experience .grid,
            #achievements .grid,
            #reports .grid {
                grid-template-columns: 1fr;
            }
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Animations */
        .card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .card:nth-child(even) {
            animation-delay: 0.1s;
        }

        .card:nth-child(odd) {
            animation-delay: 0.2s;
        }

        /* Icons */
        .fas, .fab {
            margin-right: 0.5rem;
        }

        /* Certificate and Achievement cards */
        .certificate-card,
        .achievement-card {
            text-align: center;
        }

        .certificate-card img,
        .achievement-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 15px;
            margin-bottom: 1rem;
        }

        .certificate-meta,
        .achievement-meta {
            padding: 1rem 0;
        }

        .organization,
        .date,
        .year {
            color: #666;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }

        .description {
            margin-top: 1rem;
            line-height: 1.6;
        }

        /* Experience cards */
        .experience-card h3 {
            color: #3498db;
            margin-bottom: 0.5rem;
        }

        .experience-card h4 {
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        /* Report cards */
        .report-card h3 {
            color: #3498db;
            margin-bottom: 1rem;
        }

        /* CV section */
        .cv-section {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 249, 250, 0.8) 100%);
        }

        .cv-download {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .about-content {
            display: flex;
            align-items: center;
            gap: 4rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .about-text {
            flex: 1;
            text-align: left;
        }

        .about-text[dir="rtl"] {
            text-align: right;
        }

        .about-highlight {
            background-color: #FFE600;
            padding: 0 5px;
            font-weight: bold;
        }

        .about-image {
            flex: 1;
            position: relative;
            max-width: 500px;
        }

        .about-image img {
            width: 100%;
            height: auto;
            border-radius: 0;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .about-content {
                flex-direction: column;
                gap: 2rem;
            }

            .about-image {
                order: -1;
                max-width: 100%;
            }
        }

        /* Achievement cards with offset layout */
        .achievements-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18rem;
            margin-top: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .achievement-card {
            background: #ffffff;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .achievement-card:nth-child(2) {
            margin-top: 0;
        }

        .achievement-card:nth-child(3) {
            margin-top: 18rem;
        }

        .achievement-card:nth-child(4) {
            margin-top: 0;
        }

        .achievement-card img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            margin: 0;
        }

        .achievement-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .achievement-meta {
            padding: 2rem;
            text-align: left;
            color: #333;
            background: #ffffff;
        }

        .achievement-meta h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .achievement-meta .year {
            color: #666;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .achievement-meta .description {
            color: #555;
            line-height: 1.6;
            margin: 0;
        }

        @media (max-width: 768px) {
            .achievements-grid {
                grid-template-columns: 1fr;
                gap: 4rem;
            }
            
            .achievement-card:nth-child(odd) {
                margin-top: 0;
            }
            
            .achievement-card img {
                height: 300px;
            }
        }
    </style>  
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">Portfolio</div>
            
            <!-- Mobile Menu Toggle -->
            <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <ul class="nav-links" id="navLinks">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#projects">Projects</a></li>
                <li><a href="#experience">Experience</a></li> <!-- Add this line -->
                <li><a href="#contact">Contact</a></li>
            </ul>
            
            <div class="lang-switcher">
                <a href="?lang=en" class="active">EN</a>
                <a href="?lang=ku">کوردی</a>
            </div>
        </nav>
    </header>


    <section id="home" class="hero">
        <div class="hero-content">
            <h1><?php echo htmlspecialchars(getLocalizedText($about, 'name', $lang, 'Your Name')); ?></h1>
            <p><?php echo htmlspecialchars(getLocalizedText($about, 'title', $lang, 'Programmer and AI Expert')); ?></p>
            <div>
                <a href="#projects" class="btn"><?php echo $lang === 'ku' ? 'کارەکانم ببینە' : 'View My Work'; ?></a>
                <?php if($cv_resumes): ?>
                    <a href="<?php echo htmlspecialchars($cv_resumes['file_path']); ?>" class="btn btn-secondary" target="_blank">
                        <i ></i> <?php echo $lang === 'ku' ? 'بینینی CV' : 'View  CV'; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
    </section>

    <section id="about" class="section">
        <div class="container">
            <h2 class="section-title"><?php echo $lang === 'ku' ? 'دەربارەی من' : 'About Me'; ?></h2>
            <div class="about-content">
                <div class="about-text" <?php echo $lang === 'ku' ? 'dir="rtl"' : ''; ?>>
                    <h3><?php echo $lang === 'ku' ? 'من' : 'I\'m'; ?> <?php echo htmlspecialchars(getLocalizedText($about, 'name', $lang)); ?></h3>
                    <?php 
                    $position = htmlspecialchars(getLocalizedText($about, 'title', $lang));
                    echo $lang === 'ku' ? 
                        sprintf('من <span class="about-highlight">%s</span>.   ', $position) :
                        sprintf('A <span class="about-highlight">%s</span>.    ', $position);
                    ?>
                    <p><?php echo htmlspecialchars(getLocalizedText($about, 'description', $lang)); ?></p>
                    <?php 
                    $university = getLocalizedText($about, 'university', $lang);
                    if ($university): ?>
                        <p><strong><?php echo $lang === 'ku' ? 'زانکۆ:' : 'University:'; ?></strong> <?php echo htmlspecialchars($university); ?></p>
                    <?php endif; ?>
                    <?php 
                    $skills = getLocalizedText($about, 'skills', $lang);
                    if ($skills): ?>
                        <div class="skills">
                            <?php foreach (explode(',', $skills) as $skill): ?>
                                <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="about-image">
                    <?php if (!empty($about['profile_image'])): ?>
                        <img src="uploads/profile/<?php echo htmlspecialchars($about['profile_image']); ?>" 
                             alt="<?php echo htmlspecialchars(getLocalizedText($about, 'name', $lang)); ?>">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
<section id="projects" class="section">
    <div class="container">
        <h2 class="section-title"><?php echo $lang === 'ku' ? 'پرۆژەکانم' : 'My Projects'; ?></h2>
        
        <!-- Project Category Tabs -->
        <div class="project-tabs">
            <div class="tab-button active" onclick="showCategory('completed')">
                <?php echo $lang === 'ku' ? 'پڕۆژە تەواوبووەکان' : 'Completed Projects'; ?>
            </div>
            <div class="tab-button" onclick="showCategory('ongoing')">
                <?php echo $lang === 'ku' ? 'پڕۆژە بەردەوامەکان' : 'Ongoing Projects'; ?>
            </div>
            <div class="tab-button" onclick="showCategory('concept')">
                <?php echo $lang === 'ku' ? 'پڕۆژە بیرۆکەکان' : 'Concept Projects'; ?>
            </div>
        </div>

        <!-- Completed Projects -->
        <div id="completed-projects" class="project-category active">
            <?php if (!empty($projects_completed)): ?>
                <div class="project-list">
                    <?php foreach ($projects_completed as $project): ?>
                        <article class="project-item">
                            <div class="project-image">
                                <?php if ($project['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($project['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars(getLocalizedText($project, 'title', $lang)); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="project-content">
                                <h3><?php echo htmlspecialchars(getLocalizedText($project, 'title', $lang)); ?></h3>
                                <p><?php echo htmlspecialchars(getLocalizedText($project, 'description', $lang)); ?></p>
                                <?php 
                                $technologies = getLocalizedText($project, 'technologies', $lang);
                                if ($technologies): ?>
                                    <div class="skills">
                                        <?php foreach (explode(',', $technologies) as $tech): ?>
                                            <span class="skill-tag"><?php echo htmlspecialchars(trim($tech)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($project['project_link'])): ?>
                                    <div style="margin-top:1rem;">
                                        <a href="<?php echo htmlspecialchars($project['project_link']); ?>" 
                                           class="btn" target="_blank">
                                            <i class="fas fa-external-link-alt"></i> 
                                            <?php echo htmlspecialchars(getLocalizedText($project, 'project_link_text', $lang, 
                                                  $lang === 'ku' ? 'بینینی پڕۆژە' : 'View Project')); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-category">
                    <p><?php echo $lang === 'ku' ? 'هێشتا هیچ پڕۆژەیەکی تەواوبوو نەماوە' : 'No completed projects yet'; ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Ongoing Projects -->
        <div id="ongoing-projects" class="project-category">
            <?php if (!empty($projects_ongoing)): ?>
                <div class="project-list">
                    <?php foreach ($projects_ongoing as $project): ?>
                        <article class="project-item">
                            <div class="project-image">
                                <?php if ($project['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($project['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars(getLocalizedText($project, 'title', $lang)); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="project-content">
                                <h3><?php echo htmlspecialchars(getLocalizedText($project, 'title', $lang)); ?></h3>
                                <p><?php echo htmlspecialchars(getLocalizedText($project, 'description', $lang)); ?></p>
                                <?php 
                                $technologies = getLocalizedText($project, 'technologies', $lang);
                                if ($technologies): ?>
                                    <div class="skills">
                                        <?php foreach (explode(',', $technologies) as $tech): ?>
                                            <span class="skill-tag"><?php echo htmlspecialchars(trim($tech)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($project['project_link'])): ?>
                                    <div style="margin-top:1rem;">
                                        <a href="<?php echo htmlspecialchars($project['project_link']); ?>" 
                                           class="btn" target="_blank">
                                            <i class="fas fa-external-link-alt"></i> 
                                            <?php echo htmlspecialchars(getLocalizedText($project, 'project_link_text', $lang, 
                                                  $lang === 'ku' ? 'بینینی پڕۆژە' : 'View Project')); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-category">
                    <p><?php echo $lang === 'ku' ? 'هێشتا هیچ پڕۆژەیەکی بەردەوام نەماوە' : 'No ongoing projects yet'; ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Concept Projects -->
        <div id="concept-projects" class="project-category">
            <?php if (!empty($projects_concept)): ?>
                <div class="project-list">
                    <?php foreach ($projects_concept as $project): ?>
                        <article class="project-item">
                            <div class="project-image">
                                <?php if ($project['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($project['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars(getLocalizedText($project, 'title', $lang)); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="project-content">
                                <h3><?php echo htmlspecialchars(getLocalizedText($project, 'title', $lang)); ?></h3>
                                <p><?php echo htmlspecialchars(getLocalizedText($project, 'description', $lang)); ?></p>
                                <?php 
                                $technologies = getLocalizedText($project, 'technologies', $lang);
                                if ($technologies): ?>
                                    <div class="skills">
                                        <?php foreach (explode(',', $technologies) as $tech): ?>
                                            <span class="skill-tag"><?php echo htmlspecialchars(trim($tech)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($project['project_link'])): ?>
                                    <div style="margin-top:1rem;">
                                        <a href="<?php echo htmlspecialchars($project['project_link']); ?>" 
                                           class="btn" target="_blank">
                                            <i class="fas fa-external-link-alt"></i> 
                                            <?php echo htmlspecialchars(getLocalizedText($project, 'project_link_text', $lang, 
                                                  $lang === 'ku' ? 'بینینی پڕۆژە' : 'View Project')); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-category">
                    <p><?php echo $lang === 'ku' ? 'هێشتا هیچ پڕۆژەیەکی بیرۆکە نەماوە' : 'No concept projects yet'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($certificates): ?>
<section id="certificates" class="section">
    <div class="container">
        <h2 class="section-title"><?php echo $lang === 'ku' ? 'بڕوانامەکان' : 'Certificates'; ?></h2>
        <div class="grid">
            <?php foreach ($certificates as $cert): ?>
                <div class="card certificate-card">
                    <?php if ($cert['image_path']): ?>
                        <img src="<?php echo htmlspecialchars($cert['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars(getLocalizedText($cert, 'title', $lang)); ?>">
                    <?php endif; ?>
                    <div class="certificate-meta">
                        <h3><?php echo htmlspecialchars(getLocalizedText($cert, 'title', $lang)); ?></h3>
                        <p class="organization">
                            <i class="fas fa-building"></i> 
                            <?php echo htmlspecialchars($cert['issuing_organization']); ?>
                        </p>
                        <p class="date">
                            <i class="fas fa-calendar-alt"></i> 
                            <?php echo htmlspecialchars($cert['issue_date']); ?>
                        </p>
                        <p class="description">
                            <?php echo htmlspecialchars(getLocalizedText($cert, 'description', $lang)); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

    <section class="experience-section" id="experience">
        <div class="experience-container">
            <h2 class="experience-title"><?php echo $lang === 'ku' ? 'ئەزموونەکان' : 'Experience'; ?></h2>
            <div class="timeline">
                <?php if (!empty($experience)): ?>
                    <?php foreach ($experience as $exp): ?>
                        <div class="timeline-item">
                            <div class="timeline-date"><?php echo htmlspecialchars($exp['year']); ?></div>
                            <div class="timeline-company"><?php echo htmlspecialchars(getLocalizedText($exp, 'company', $lang)); ?></div>
                            <div class="timeline-position"><?php echo htmlspecialchars(getLocalizedText($exp, 'position', $lang)); ?></div>
                            <div class="timeline-description">
                                <?php echo htmlspecialchars(getLocalizedText($exp, 'description', $lang)); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-category">
                        <p><?php echo $lang === 'ku' ? 'هێشتا هیچ ئەزموونێک نەخراوەتە ناو سیستەمەکە' : 'No experience entries yet'; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($achievements): ?>
    <section id="achievements" class="section">
        <div class="container">
            <h2 class="section-title"><?php echo $lang === 'ku' ? 'دەستکەوتەکان' : 'Achievements'; ?></h2>
            <div class="achievements-grid">
                <?php foreach ($achievements as $achievement): ?>
                    <div class="achievement-card">
                        <?php if ($achievement['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($achievement['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars(getLocalizedText($achievement, 'title', $lang)); ?>">
                        <?php endif; ?>
                        <div class="achievement-meta">
                            <h3><?php echo htmlspecialchars(getLocalizedText($achievement, 'title', $lang)); ?></h3>
                            <?php if ($achievement['year']): ?>
                                <p class="year"><?php echo htmlspecialchars($achievement['year']); ?></p>
                            <?php endif; ?>
                            <?php 
                            $description = getLocalizedText($achievement, 'description', $lang);
                            if ($description): ?>
                                <p class="description"><?php echo htmlspecialchars($description); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($reports): ?>
    <section id="reports" class="section">
        <div class="container">
            <h2 class="section-title"><?php echo $lang === 'ku' ? 'ڕاپۆرتەکان' : 'Reports'; ?></h2>
            <div class="grid">
                <?php foreach ($reports as $report): ?>
                    <div class="card report-card">
                        <h3><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars(getLocalizedText($report, 'title', $lang)); ?></h3>
                        <?php 
                        $description = getLocalizedText($report, 'description', $lang);
                        if ($description): ?>
                            <p><?php echo htmlspecialchars($description); ?></p>
                        <?php endif; ?>
                        <?php if ($report['file_url']): ?>
                            <div style="margin-top:1rem;">
                                <a href="<?php echo htmlspecialchars($report['file_url']); ?>" class="btn" target="_blank">
                                    <i class="fas fa-download"></i> <?php echo $lang === 'ku' ? 'داونلۆد' : 'Download'; ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if($cv_resumes): ?>
    <section class="section cv-section">
        <div class="container">
            <h2 class="section-title"><?php echo $lang === 'ku' ? 'CV ' : 'CV & Resume'; ?></h2>
            <div class="card" style="text-align:center;">
                <h3><?php echo htmlspecialchars(getLocalizedText($cv_resumes, 'title', $lang)); ?></h3>
                <p><?php echo $lang === 'ku' ? 'سەیرکردنی' : 'View CV'; ?></p>
                <div style="margin-top:2rem;">
                    <a href="<?php echo htmlspecialchars($cv_resumes['file_path']); ?>" class="btn btn-secondary cv-download" target="_blank">
                        <i class="fas fa-file-pdf"></i>
                        <?php echo $lang === 'ku' ? 'بینینی CV' : 'View CV'; ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section id="contact" class="section contact">
        <div class="container">
            <h2 class="section-title"><?php echo $lang === 'ku' ? 'پەیوەندی' : 'Get In Touch'; ?></h2>
            <div class="contact-info">
                <?php if ($contact['email']): ?>
                    <div class="contact-item">
                        <h3><i class="fas fa-envelope"></i> <?php echo $lang === 'ku' ? 'ئیمەیڵ' : 'Email'; ?></h3>
                        <p><a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>"><?php echo htmlspecialchars($contact['email']); ?></a></p>
                    </div>
                <?php endif; ?>
                <?php if ($contact['phone']): ?>
                    <div class="contact-item">
                        <h3><i class="fas fa-phone"></i> <?php echo $lang === 'ku' ? 'تەلەفۆن' : 'Phone'; ?></h3>
                        <p><a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>"><?php echo htmlspecialchars($contact['phone']); ?></a></p>
                    </div>
                <?php endif; ?>
                <?php if ($contact['linkedin']): ?>
                    <div class="contact-item">
                        <h3><i class="fab fa-linkedin"></i> LinkedIn</h3>
                        <p><a href="<?php echo htmlspecialchars($contact['linkedin']); ?>" target="_blank"><?php echo $lang === 'ku' ? 'پەیوەندی لەگەڵ من' : 'Connect with me'; ?></a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

   
 <script>
        // Mobile Menu Functions
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            
            navLinks.classList.toggle('active');
            menuToggle.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            if (navLinks.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        function closeMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            
            navLinks.classList.remove('active');
            menuToggle.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close menu when clicking on menu links
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });

        // Close menu when window is resized to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    setTimeout(() => {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 100);
                }
            });
        });
    </script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards
        document.querySelectorAll('.card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });

        // Stats counter animation
        function animateCounter(element, target, duration = 2000) {
            let start = 0;
            const increment = target / (duration / 16);
            const timer = setInterval(() => {
                start += increment;
                if (start >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(start);
                }
            }, 16);
        }

        // Animate stats when they come into view
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const numbers = entry.target.querySelectorAll('.stat-number');
                    numbers.forEach(num => {
                        const target = parseInt(num.textContent);
                        num.textContent = '0';
                        animateCounter(num, target);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        });

        const statsSection = document.querySelector('.stats');
        if (statsSection) {
            statsObserver.observe(statsSection);
        }
    </script>
    <script>
        // Mobile Menu Functions
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            
            navLinks.classList.toggle('active');
            menuToggle.classList.toggle('active');
        }

        function closeMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            
            navLinks.classList.remove('active');
            menuToggle.classList.remove('active');
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const nav = document.querySelector('.nav');
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            const navLinks = document.getElementById('navLinks');
            
            if (!nav.contains(event.target) && navLinks.classList.contains('active')) {
                closeMobileMenu();
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const navLinks = document.getElementById('navLinks');
                const menuToggle = document.querySelector('.mobile-menu-toggle');
                
                navLinks.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });

        // Enhanced smooth scrolling with mobile menu close
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    // Close mobile menu first
                    closeMobileMenu();
                    
                    // Then scroll
                    setTimeout(() => {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 100);
                }
            });
        });
    </script>

<script>
        // Project Category Functions
        function showCategory(category) {
            // Hide all categories
            const categories = document.querySelectorAll('.project-category');
            categories.forEach(cat => {
                cat.classList.remove('active');
            });
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab-button');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected category
            const selectedCategory = document.getElementById(category + '-projects');
            if (selectedCategory) {
                selectedCategory.classList.add('active');
            }
            
            // Add active class to clicked tab
            const activeTab = Array.from(tabs).find(tab => 
                tab.getAttribute('onclick').includes(category)
            );
            if (activeTab) {
                activeTab.classList.add('active');
            }
        }

        // Achievement Functions 
        function editAchievement(id) {
            // Hide add form
            document.querySelector('.add-form').style.display = 'none';
            
            // Show edit form
            const editForm = document.getElementById('editAchievementForm');
            editForm.style.display = 'block';
            
            // Fetch achievement data
            fetch(`get_item.php?table=achievements&id=${id}`)
                .then(response => response.json())
                .then((data) => {
                    document.getElementById('edit_achievement_id').value = data.id;
                    document.getElementById('edit_achievement_title_en').value = data.title_en;
                    document.getElementById('edit_achievement_title_ku').value = data.title_ku;
                    document.getElementById('edit_achievement_description_en').value = data.description_en;
                    document.getElementById('edit_achievement_description_ku').value = data.description_ku;
                    document.getElementById('edit_achievement_year').value = data.year;
                    document.getElementById('edit_achievement_display_order').value = data.display_order;
                    
                    if (data.image_url) {
                        document.getElementById('current_achievement_image').innerHTML = 
                            `<img src="${data.image_url}" alt="Current image" style="max-width: 200px;">`;
                    }
                });
        }
    </script>
<script>
// File Upload Handling
function setupDragAndDrop(prefix, acceptTypes) {
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
        if (files.length > 0) {
            const file = files[0];
            const fileType = file.type;
            const validTypes = acceptTypes.split(',');
            
            if (validTypes.includes(fileType) || 
                validTypes.some(type => file.name.toLowerCase().endsWith(type.replace('*','')))) {
                
                fileInput.files = files;
                fileNameDisplay.textContent = `Selected: ${file.name}`;
                dropZone.classList.add('has-file');
                
                // Preview for images
                if (fileType.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        fileNameDisplay.innerHTML = `
                            <img src="${e.target.result}" style="max-width: 150px; margin-top: 10px"><br>
                            Selected: ${file.name}
                        `;
                    }
                    reader.readAsDataURL(file);
                }
            } else {
                alert('Invalid file type. Please select a valid file.');
            }
        }
    }
}

// Initialize all file upload zones
document.addEventListener('DOMContentLoaded', function() {
    const uploadZones = [
        { zone: 'profile', accept: 'image/*' },
        { zone: 'project', accept: 'image/*' },
        { zone: 'certificate', accept: 'image/*' },
        { zone: 'cv', accept: '.pdf,.doc,.docx' },
        { zone: 'report', accept: '.pdf,.doc,.docx' },
        { zone: 'achievement', accept: 'image/*' }
    ];

    uploadZones.forEach(config => {
        setupDragAndDrop(config.zone, config.accept);
    });
});

// Project tabs and mobile menu functions
    </script>
        <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }

        // Project category tabs
        function showCategory(category) {
            // Hide all categories
            const categories = document.querySelectorAll('.project-category');
            categories.forEach(cat => cat.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab-button');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected category
            const selectedCategory = document.getElementById(category + '-projects');
            if (selectedCategory) {
                selectedCategory.classList.add('active');
            }
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 4px 20px rgba(52, 152, 219, 0.15)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = '0 4px 20px rgba(52, 152, 219, 0.1)';
            }
        });

        // Intersection Observer for animations
        

        // Observe all cards for animation
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });
        });

        // Parallax effect for background
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelector('body::before');
            const speed = scrolled * 0.5;
        });

        // Add loading animation
        window.addEventListener('load', function() {
            document.body.style.opacity = '1';
            document.body.style.transform = 'translateY(0)';
        });

        // Add CSS for initial load state
        document.body.style.opacity = '0';
        document.body.style.transform = 'translateY(20px)';
        document.body.style.transition = 'all 0.8s ease';
    </script>
</body>
</html>