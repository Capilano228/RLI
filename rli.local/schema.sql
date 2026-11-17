CREATE DATABASE IF NOT EXISTS rli_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rli_school;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS menu_votes;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS schedule;
DROP TABLE IF EXISTS ball_tickets;
DROP TABLE IF EXISTS cash_outs;
DROP TABLE IF EXISTS fines;
DROP TABLE IF EXISTS work_checks;
DROP TABLE IF EXISTS special_announcements;
DROP TABLE IF EXISTS laws;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;


CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    login VARCHAR(60) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','teacher') NOT NULL DEFAULT 'student',
    profile VARCHAR(50) DEFAULT NULL,
    school_class VARCHAR(10) DEFAULT NULL,
    residence_type ENUM('city','boarding') NOT NULL DEFAULT 'city',
    branch VARCHAR(50) NOT NULL DEFAULT 'none',
    position_title VARCHAR(100) NOT NULL DEFAULT '',
    position_code VARCHAR(50) NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    author_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE laws (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE special_announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_ball TINYINT(1) NOT NULL DEFAULT 0,
    author_id INT UNSIGNED DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE work_checks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    hours DECIMAL(4,1) NOT NULL,
    work_type ENUM('physical','mental') NOT NULL DEFAULT 'physical',
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE fines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    officer_id INT UNSIGNED NOT NULL,
    reason_type TINYINT NOT NULL DEFAULT 1, -- 1=очередь(-200) 2=фейк(-300) 3=оскорбление(-450)
    amount INT NOT NULL DEFAULT 200,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (officer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE cash_outs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    speaker_id INT UNSIGNED NOT NULL,
    amount INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (speaker_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE ball_tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT UNSIGNED NOT NULL,
    is_double TINYINT(1) NOT NULL DEFAULT 0,
    partner_name VARCHAR(150) DEFAULT NULL,
    total_paid INT NOT NULL,
    ticket_code VARCHAR(30) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    day_of_week TINYINT NOT NULL, -- 1=Пн ... 7=Вс
    meal_type ENUM('breakfast','lunch','dinner') NOT NULL,
    dish_name VARCHAR(200) NOT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_item_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    vote_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_vote (menu_item_id, user_id, vote_date),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE schedule (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(10) NOT NULL,       -- '10' или '11'
    day_of_week TINYINT NOT NULL,          -- 1=Пн ... 7=Вс
    lesson_number TINYINT NOT NULL,        -- номер урока
    subject VARCHAR(150) NOT NULL,
    teacher_name VARCHAR(150) DEFAULT NULL,
    room VARCHAR(30) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE settings (
    setting_key VARCHAR(60) PRIMARY KEY,
    setting_value VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES ('rate_physical', '300');
INSERT INTO settings (setting_key, setting_value) VALUES ('rate_mental', '350');
INSERT INTO settings (setting_key, setting_value) VALUES ('ball_price', '2000');

INSERT INTO users (full_name, login, password, role, branch, position_title, position_code) VALUES
('Шамаев Иван Иванович', 'director', '$2b$12$l6ylARnBDea37IPOwgFZ.O5glNctreplqYwHNXWmthckwy1aYUsom', 'teacher', 'Directorate', 'Директор', 'director');

INSERT INTO users (full_name, login, password, role) VALUES
('Петрова Мария Семёновна', 'teacher', '$2b$12$vD4erOuvoUWsw1YybDqRoOfyW1ui/9f.OYzVOVeaucK85ynsMpAnO', 'teacher');

INSERT INTO users (full_name, login, password, role, profile, school_class, residence_type, branch, position_title, position_code) VALUES ('Слепцов Айсен Егорович', 'student1', '$2b$12$47rWJr9glYaYryHEZaUqBuGKPnrcnwnPYFvCFifp7hl/t67dMs7qm', 'student', 'fizmat', '11', 'boarding', 'Government', 'Президент', 'president');
INSERT INTO users (full_name, login, password, role, profile, school_class, residence_type, branch, position_title, position_code) VALUES ('Егорова Сардана Владимировна', 'student2', '$2b$12$47rWJr9glYaYryHEZaUqBuGKPnrcnwnPYFvCFifp7hl/t67dMs7qm', 'student', 'humanitarian', '11', 'city', 'Parliament', 'Председатель Парламента', 'parliament_speaker');
INSERT INTO users (full_name, login, password, role, profile, school_class, residence_type, branch, position_title, position_code) VALUES ('Николаев Кыылан Дмитриевич', 'student3', '$2b$12$47rWJr9glYaYryHEZaUqBuGKPnrcnwnPYFvCFifp7hl/t67dMs7qm', 'student', 'polytech', '10', 'boarding', 'SBRL', 'Глава СБРЛ', 'sbrl_head');
INSERT INTO users (full_name, login, password, role, profile, school_class, residence_type, branch, position_title, position_code) VALUES ('Дмитриева Марианна Артуровна', 'student4', '$2b$12$47rWJr9glYaYryHEZaUqBuGKPnrcnwnPYFvCFifp7hl/t67dMs7qm', 'student', 'biohim', '10', 'city', 'none', '', 'student');

INSERT INTO news (title, content, author_id) VALUES ('Добро пожаловать на портал РЛИ!', 'Запущена внутришкольная цифровая система: личные кабинеты, лиры, меню столовой, расписание и билеты на бал. Регистрируйтесь и участвуйте в жизни лицея!', 2);
INSERT INTO news (title, content, author_id) VALUES ('Старт учебного года', 'Новый учебный год начался! Всем удачи в учёбе и активного участия в самоуправлении.', 2);

INSERT INTO laws (title, content, author_id) VALUES ('О ставках зарплат', 'Установлены ставки: физический труд - 300 лыр/час, умственный труд - 350 лыр/час.', 1);
INSERT INTO laws (title, content) VALUES ('О боях', 'Цена билета на осенний и весенний бал составляет 2000 лыр. Активисты органов самоуправления получают скидку в размере 50 процентов.');

INSERT INTO menu_items (day_of_week, meal_type, dish_name, created_by) VALUES (1, 'breakfast', 'Каша рисовая с маслом', 1);
INSERT INTO menu_items (day_of_week, meal_type, dish_name, created_by) VALUES (1, 'breakfast', 'Сырники со сметаной', 1);
INSERT INTO menu_items (day_of_week, meal_type, dish_name, created_by) VALUES (1, 'lunch', 'Борщ со сметаной', 1);
INSERT INTO menu_items (day_of_week, meal_type, dish_name, created_by) VALUES (1, 'lunch', 'Котлета с пюре', 1);
INSERT INTO menu_items (day_of_week, meal_type, dish_name, created_by) VALUES (1, 'dinner', 'Гречка с курицей', 1);
INSERT INTO menu_items (day_of_week, meal_type, dish_name, created_by) VALUES (2, 'lunch', 'Суп куриный с лапшой', 1);
INSERT INTO menu_items (day_of_week, meal_type, dish_name, created_by) VALUES (2, 'lunch', 'Плов с говядиной', 1);

INSERT INTO schedule (class_name, day_of_week, lesson_number, subject, teacher_name, room) VALUES ('10', 1, 1, 'Математика', 'Петрова М. С.', '305');
INSERT INTO schedule (class_name, day_of_week, lesson_number, subject, teacher_name, room) VALUES ('10', 1, 2, 'Физика', 'Петрова М. С.', '312');
INSERT INTO schedule (class_name, day_of_week, lesson_number, subject, teacher_name, room) VALUES ('10', 1, 3, 'История', 'Иванов А. А.', '201');
INSERT INTO schedule (class_name, day_of_week, lesson_number, subject, teacher_name, room) VALUES ('11', 1, 1, 'Литература', 'Сидорова Е. П.', '204');
INSERT INTO schedule (class_name, day_of_week, lesson_number, subject, teacher_name, room) VALUES ('11', 1, 2, 'Математика', 'Петрова М. С.', '305');
INSERT INTO schedule (class_name, day_of_week, lesson_number, subject, teacher_name, room) VALUES ('11', 1, 3, 'Информатика', 'Оголовеков И. И.', '410');

INSERT INTO special_announcements (title, content, is_ball, author_id, expires_at) VALUES
('Осенний бал!', 'Внимание! Сколько стоит билет на бал? 2000 лир, для активистов скидка 50 процентов! Покупайте билеты в личном кабинете.', 1, 3, DATE_ADD(NOW(), INTERVAL 72 HOUR));
