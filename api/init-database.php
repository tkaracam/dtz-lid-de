<?php
/**
 * Initialize database with tables and seed data
 * Call once after deployment
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$secret = $_GET['secret'] ?? '';
if ($secret !== 'init2024') {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid secret']);
    exit;
}

try {
    $dbPath = '/var/www/html/database/dtz_production.db';
    
    // Ensure directory exists
    $dir = dirname($dbPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    
    // Create/Connect database
    $pdo = new PDO("sqlite:$dbPath", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $results = [];
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        display_name VARCHAR(100) NOT NULL,
        name VARCHAR(100),
        level VARCHAR(2) DEFAULT 'A2',
        role VARCHAR(20) DEFAULT 'user',
        subscription_status VARCHAR(20) DEFAULT 'free',
        trial_ends_at TIMESTAMP,
        is_active BOOLEAN DEFAULT 1,
        daily_goal INTEGER DEFAULT 10,
        streak_count INTEGER DEFAULT 0,
        last_activity_at TIMESTAMP,
        email_verified_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $results[] = 'users table created';
    
    // Create question_pools table
    $pdo->exec("CREATE TABLE IF NOT EXISTS question_pools (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        module VARCHAR(20) NOT NULL,
        teil INTEGER NOT NULL,
        level VARCHAR(2) DEFAULT 'A2',
        question_type VARCHAR(30) NOT NULL,
        content TEXT NOT NULL,
        correct_answer TEXT NOT NULL,
        explanation TEXT,
        difficulty INTEGER,
        points INTEGER DEFAULT 10,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $results[] = 'question_pools table created';
    
    // Check if questions exist
    $count = $pdo->query("SELECT COUNT(*) FROM question_pools")->fetchColumn();
    
    if ($count == 0) {
        // Seed sample questions
        $questions = [
            // Lesen Teil 1 - Anzeigen verstehen
            ['lesen', 1, 'A2', 'multiple_choice', 
             '{"question": "Sie sehen eine Anzeige in der Zeitung. Was wird angeboten?", "options": ["A) Eine Wohnung zur Miete", "B) Ein Auto zum Verkauf", "C) Ein Jobangebot", "D) Ein Kurs für Deutsch"], "text": "Zu vermieten: 3-Zimmer-Wohnung in der Innenstadt, 75m², ab sofort frei. Tel: 0123-456789"}',
             '{"answer": "A"}', 'Anzeige über Wohnungsvermietung', 3, 10],
            
            ['lesen', 1, 'A2', 'multiple_choice',
             '{"question": "Was kostet der Deutschkurs?", "options": ["A) 100 Euro", "B) 200 Euro", "C) Kostenlos", "D) 150 Euro"], "text": "Sprachkurs Deutsch A2: Intensivkurs 4 Wochen, montags bis freitags, 9-12 Uhr. Kostenlos für Asylbewerber und Geduldete."}',
             '{"answer": "C"}', 'Kurs ist kostenlos für bestimmte Gruppen', 2, 10],
             
            // Hören Teil 1 - Telefonansagen
            ['hoeren', 1, 'A2', 'multiple_choice',
             '{"question": "Wann ist das Bürgerbüro geöffnet?", "options": ["A) Mo-Fr 8-12 Uhr", "B) Di+Do 14-18 Uhr", "C) Mo+Mi+Fr 9-12 Uhr", "D) Jeden Tag 8-16 Uhr"], "audio_text": "Guten Tag, Sie haben das Bürgerbüro erreicht. Unsere Öffnungszeiten: Montag, Mittwoch und Freitag von 9 bis 12 Uhr."}',
             '{"answer": "C"}', 'Öffnungszeiten sind Mo/Mi/Fr', 3, 10],
             
            ['hoeren', 1, 'A2', 'multiple_choice',
             '{"question": "Was soll man für einen Termin machen?", "options": ["A) E-Mail schreiben", "B) Online buchen", "C) Anrufen", "D) Vorbeikommen"], "audio_text": "Für Termine nutzen Sie bitte unsere Online-Terminbuchung auf www.stadt.de. Eine telefonische Terminvereinbarung ist nicht mehr möglich."}',
             '{"answer": "B"}', 'Online-Terminbuchung ist erforderlich', 2, 10],
             
            // Schreiben - Brief
            ['schreiben', 1, 'A2', 'text_input',
             '{"question": "Schreiben Sie eine E-Mail an Ihren Chef. Sie sind krank und können nicht zur Arbeit kommen.", "situation": "Krankmeldung", "min_words": 50, "max_words": 80}',
             '{"criteria": ["Anrede", "Begründung", "Entschuldigung", "Schluss"]}', 'Krankmeldung per E-Mail', 5, 25],
             
            ['schreiben', 2, 'B1', 'text_input',
             '{"question": "Schreiben Sie einen Brief an das Jobcenter. Sie brauchen Unterstützung bei der Jobsuche.", "situation": "Antrag auf Unterstützung", "min_words": 80, "max_words": 120}',
             '{"criteria": ["Adresse", "Betreff", "Begründung", "Bitte", "Schluss"]}', 'Formeller Brief ans Jobcenter', 7, 25],
        ];
        
        $stmt = $pdo->prepare("INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($questions as $q) {
            $stmt->execute($q);
        }
        
        $results[] = count($questions) . ' sample questions added';
    } else {
        $results[] = "$count questions already exist";
    }
    
    // Create admin user
    $adminExists = $pdo->query("SELECT id FROM users WHERE email = 'admin@dtz-lid.de'")->fetch();
    
    if (!$adminExists) {
        $passwordHash = password_hash('Admin123!', PASSWORD_ARGON2ID);
        $pdo->prepare("INSERT INTO users (email, display_name, password_hash, role, subscription_status, level) VALUES (?, ?, ?, 'admin', 'premium', 'B1')")
            ->execute(['admin@dtz-lid.de', 'Administrator', $passwordHash]);
        $results[] = 'admin user created';
    } else {
        $results[] = 'admin user already exists';
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database initialized',
        'details' => $results,
        'db_path' => $dbPath,
        'db_size' => filesize($dbPath)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
