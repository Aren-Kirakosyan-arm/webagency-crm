CREATE DATABASE IF NOT EXISTS webagency_crm
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE webagency_crm;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(40) NULL,
  company VARCHAR(150) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_clients_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NULL,
  name VARCHAR(180) NOT NULL,
  description TEXT NULL,
  budget DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('planning','in_progress','review','completed') NOT NULL DEFAULT 'planning',
  progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
  deadline DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_projects_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
  INDEX idx_projects_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NULL,
  title VARCHAR(180) NOT NULL,
  status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  due_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tasks_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB;
