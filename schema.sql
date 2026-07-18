-- Rentcom — MySQL schema.
-- Run once against your cPanel-created database:
--   mysql -u USER -p DBNAME < schema.sql
-- (or paste into phpMyAdmin's SQL tab).

CREATE TABLE IF NOT EXISTS report_leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  whatsapp VARCHAR(32) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS investors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  whatsapp VARCHAR(32) NOT NULL,
  status ENUM('New','Contacted','Meeting Scheduled','Cold') NOT NULL DEFAULT 'New',
  source VARCHAR(80) NOT NULL DEFAULT 'investor-journey',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS realtors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  whatsapp VARCHAR(32) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dispatches (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  realtor_id INT UNSIGNED NOT NULL,
  pack_status ENUM('Pending','Dispatched','Delivered') NOT NULL DEFAULT 'Pending',
  visit_status ENUM('Not Scheduled','Scheduled','Completed') NOT NULL DEFAULT 'Not Scheduled',
  visit_date DATE NULL,
  CONSTRAINT fk_dispatch_realtor FOREIGN KEY (realtor_id) REFERENCES realtors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  type VARCHAR(8) NOT NULL,
  filename VARCHAR(190) NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The four marketing assets managed from the Admin Portal's Asset Manager.
-- Upload the real files via the portal (or drop them into public_html/downloads/).
INSERT INTO assets (name, type, filename) VALUES
  ('Fadayee Brochure', 'PDF', 'fadayee-brochure.pdf'),
  ('Social Media Pack', 'ZIP', 'social-media-pack.zip'),
  ('Site Walkthrough Video', 'MP4', 'site-walkthrough.mp4'),
  ('Unit Price List', 'PDF', 'unit-price-list.pdf');

-- Initial admin login. Email: team@rentcom.com  Password: RentcomAdmin2026!
-- CHANGE THIS PASSWORD immediately after first login:
--   php -r "echo password_hash('your-new-password', PASSWORD_DEFAULT);"
-- then UPDATE admin_users SET password_hash='<new hash>' WHERE email='team@rentcom.com';
INSERT INTO admin_users (email, password_hash) VALUES
  ('team@rentcom.com', '$2y$12$FXMxgEmlzBLkDuVS2l5bweAKH0M1gh.dHypcqMK7AwX2YN8OkIecW');
