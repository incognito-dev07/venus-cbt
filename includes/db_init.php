<?php
function initializeTables($db) {
    // Users table
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT,
        google_id TEXT,
        profile_image TEXT,
        bio TEXT DEFAULT "Welcome to my profile!",
        auth_provider TEXT DEFAULT "local",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        privacy_settings TEXT DEFAULT \'{"hide_avg_leaderboard":false}\',
        current_streak INTEGER DEFAULT 0,
        longest_streak INTEGER DEFAULT 0,
        last_activity DATE,
        total_points INTEGER DEFAULT 0,
        total_tests INTEGER DEFAULT 0,
        avg_score REAL DEFAULT 0
    )');

    // Follows table
    $db->exec('CREATE TABLE IF NOT EXISTS follows (
        follower_id INTEGER,
        following_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (follower_id, following_id),
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    // Friends table
    $db->exec('CREATE TABLE IF NOT EXISTS friends (
        user_id INTEGER,
        friend_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, friend_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    // Tests table
    $db->exec('CREATE TABLE IF NOT EXISTS tests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        course_id TEXT NOT NULL,
        score INTEGER NOT NULL,
        total INTEGER NOT NULL,
        percentage REAL NOT NULL,
        time_taken INTEGER NOT NULL,
        answers TEXT NOT NULL,
        questions TEXT NOT NULL,
        hidden_from_history INTEGER DEFAULT 0,
        date_taken DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    // Messages table
    $db->exec('CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        from_id INTEGER NOT NULL,
        to_id INTEGER NOT NULL,
        message TEXT NOT NULL,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    // Notifications table
    $db->exec('CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        data TEXT NOT NULL,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    // Leaderboard points table
    $db->exec('CREATE TABLE IF NOT EXISTS leaderboard_points (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        course_id TEXT,
        points INTEGER NOT NULL,
        test_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
    )');

    // Indexes
    $db->exec('CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_follows_follower ON follows(follower_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_follows_following ON follows(following_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_messages_to_read ON messages(to_id, is_read)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_messages_from_to ON messages(from_id, to_id, created_at)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_tests_user_date ON tests(user_id, date_taken DESC)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_tests_course ON tests(course_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_leaderboard_user ON leaderboard_points(user_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_leaderboard_course ON leaderboard_points(course_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_leaderboard_created ON leaderboard_points(created_at)');
}
?>