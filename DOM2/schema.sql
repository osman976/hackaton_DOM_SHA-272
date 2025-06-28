-- Создание таблиц
CREATE DATABASE IF NOT EXISTS realestate;
USE realestate;

-- Таблица пользователей
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Таблица жилых комплексов
CREATE TABLE complexes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    lat DOUBLE,  -- географическая широта комплекса
    lon DOUBLE   -- географическая долгота комплекса
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Таблица квартир
CREATE TABLE apartments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complex_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price INT,
    rooms INT,
    area FLOAT,
    floor INT,
    lat DOUBLE,
    lon DOUBLE,
    FOREIGN KEY (complex_id) REFERENCES complexes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Добавление учетной записи застройщика (специальные креденшелы)
INSERT INTO users (name, email, password, role) VALUES 
('Builder', 'builder@example.com', '$2y$12$WpB82PNEIXMvwLRL.FNv0.0xeyLAD7P9u6LAUZAXWBUe9kF8SSHAa', 'builder');

-- Добавление тестового обычного пользователя
INSERT INTO users (name, email, password, role) VALUES 
('Test User', 'user@example.com', '$2y$12$F4/GB9VaS9kBDnENhz5JIeHJE1Z2SNE/nRMAh/k70D1.1uzsXoxAa', 'user');

-- Добавление примеров жилых комплексов
INSERT INTO complexes (name, description, lat, lon) VALUES
('Green Homes', 'Жилой комплекс Green Homes в центре города.', 56.9500, 24.1000),
('Lakeside Apartments', 'Современный ЖК около озера.', 56.9600, 24.1200);

-- Добавление примеров квартир
INSERT INTO apartments (complex_id, name, description, price, rooms, area, floor, lat, lon) VALUES
(1, 'Квартира 2-комн №1', 'Двухкомнатная квартира на 5 этаже', 120000, 2, 60.0, 5, 56.9500, 24.1000),
(1, 'Квартира 3-комн №2', 'Трехкомнатная квартира на 2 этаже', 150000, 3, 80.0, 2, 56.9500, 24.1000),
(2, 'Студия №1', 'Однокомнатная квартира-студия', 80000, 1, 40.0, 3, 56.9600, 24.1200);
