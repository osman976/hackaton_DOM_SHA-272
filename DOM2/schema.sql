-- Создание и переход к базе, сразу правильная кодировка
CREATE DATABASE IF NOT EXISTS realestate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE realestate;

-- Таблица пользователей
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица жилых комплексов
DROP TABLE IF EXISTS complexes;
CREATE TABLE complexes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица квартир
DROP TABLE IF EXISTS apartments;
CREATE TABLE apartments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complex_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    description TEXT,
    price INT,
    rooms INT,
    area FLOAT,
    floor INT,
    FOREIGN KEY (complex_id) REFERENCES complexes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Примеры пользователей
INSERT INTO users (name, email, password, role) VALUES 
('Builder', 'builder@example.com', '$2y$12$WpB82PNEIXMvwLRL.FNv0.0xeyLAD7P9u6LAUZAXWBUe9kF8SSHAa', 'builder'),
('Test User', 'user@example.com', '$2y$12$F4/GB9VaS9kBDnENhz5JIeHJE1Z2SNE/nRMAh/k70D1.1uzsXoxAa', 'user');

-- Примеры комплексов
INSERT INTO complexes (name, description) VALUES
('Green Homes', 'Жилой комплекс Green Homes в центре города.'),
('Lakeside Apartments', 'Современный ЖК около озера.');

-- Примеры квартир (указывайте реальные адреса!)
INSERT INTO apartments (complex_id, name, address, description, price, rooms, area, floor) VALUES
(1, 'Квартира 2-комн №1', 'Москва, ул. Ленина, д. 1', 'Двухкомнатная квартира на 5 этаже', 120000, 2, 60.0, 5),
(1, 'Квартира 3-комн №2', 'Москва, ул. Ленина, д. 2', 'Трехкомнатная квартира на 2 этаже', 150000, 3, 80.0, 2),
(2, 'Студия №1', 'Москва, ул. Гагарина, д. 10', 'Однокомнатная квартира-студия', 80000, 1, 40.0, 3);
