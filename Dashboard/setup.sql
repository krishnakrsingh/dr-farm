-- ═══════════════════════════════════════════════════
-- DrFarm — Complete Database Setup
-- Run:  mysql -u root -p < setup.sql
-- ═══════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS drfarm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE drfarm;

-- ── IoT Sensor Readings ──────────────────────
CREATE TABLE IF NOT EXISTS sensor_readings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    node_id     VARCHAR(50)  NOT NULL,
    temperature FLOAT        NOT NULL DEFAULT 0,
    humidity    FLOAT        NOT NULL DEFAULT 0,
    mq7         INT          NOT NULL DEFAULT 0,
    mq3         INT          NOT NULL DEFAULT 0,
    rain        INT          NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_node    (node_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ── IoT Node Registry ────────────────────────
CREATE TABLE IF NOT EXISTS nodes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    node_id    VARCHAR(50)  NOT NULL UNIQUE,
    name       VARCHAR(100) DEFAULT NULL,
    location   VARCHAR(200) DEFAULT NULL,
    last_seen  DATETIME     DEFAULT NULL,
    status     ENUM('online','offline') DEFAULT 'offline',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── AI Disease Detections ────────────────────
CREATE TABLE IF NOT EXISTS disease_detections (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    image_path   VARCHAR(500) DEFAULT NULL,
    disease_name VARCHAR(200) NOT NULL,
    confidence   FLOAT        NOT NULL DEFAULT 0,
    severity     VARCHAR(50)  NOT NULL DEFAULT 'Low',
    analysis     TEXT         DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ── Risk Logs ────────────────────────────────
CREATE TABLE IF NOT EXISTS risk_logs (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    risk_percentage   FLOAT      NOT NULL DEFAULT 0,
    farm_health_score FLOAT      NOT NULL DEFAULT 100,
    env_risk          FLOAT      NOT NULL DEFAULT 0,
    disease_risk      FLOAT      NOT NULL DEFAULT 0,
    factors_json      JSON       DEFAULT NULL,
    alert_triggered   TINYINT(1) NOT NULL DEFAULT 0,
    created_at        DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ── Alert Trigger State (single-row) ─────────
CREATE TABLE IF NOT EXISTS trigger_state (
    id         INT NOT NULL DEFAULT 1 PRIMARY KEY,
    state      TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO trigger_state (id, state) VALUES (1, 0);

-- ── Alert / Notification History ─────────────
CREATE TABLE IF NOT EXISTS alerts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    type       VARCHAR(50)  NOT NULL DEFAULT 'risk',
    channel    VARCHAR(50)  DEFAULT 'system',
    message    TEXT         DEFAULT NULL,
    phone      VARCHAR(20)  DEFAULT NULL,
    resolved   TINYINT(1)  NOT NULL DEFAULT 0,
    created_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── 7-Day Action Plans ───────────────────────
CREATE TABLE IF NOT EXISTS action_plans (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    plan_group_id VARCHAR(50)  NOT NULL,
    disease_name  VARCHAR(200) DEFAULT NULL,
    risk_level    VARCHAR(50)  DEFAULT 'Medium',
    day_number    INT          NOT NULL,
    action_title  VARCHAR(200) NOT NULL,
    action_desc   TEXT         DEFAULT NULL,
    icon          VARCHAR(50)  DEFAULT '📋',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_group (plan_group_id)
) ENGINE=InnoDB;

-- ── Chatbot Conversations ────────────────────
CREATE TABLE IF NOT EXISTS chat_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    role       ENUM('user','assistant') NOT NULL,
    message    TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Crop Health Certificates ─────────────────
CREATE TABLE IF NOT EXISTS certificates (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    cert_code       VARCHAR(50)  NOT NULL UNIQUE,
    farm_name       VARCHAR(200) DEFAULT 'My Farm',
    health_score    FLOAT        NOT NULL DEFAULT 0,
    risk_level      VARCHAR(50)  DEFAULT 'Low',
    disease_status  VARCHAR(200) DEFAULT 'Healthy',
    sensor_summary  TEXT         DEFAULT NULL,
    grade           VARCHAR(10)  DEFAULT 'A',
    issued_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    valid_until     DATETIME     DEFAULT NULL,
    INDEX idx_code (cert_code)
) ENGINE=InnoDB;

-- ── Marketplace Products (Dummy) ─────────────
CREATE TABLE IF NOT EXISTS marketplace_products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    category    VARCHAR(100) DEFAULT 'General',
    description TEXT         DEFAULT NULL,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit        VARCHAR(50)  DEFAULT 'per kg',
    image_url   VARCHAR(500) DEFAULT NULL,
    seller      VARCHAR(200) DEFAULT NULL,
    location    VARCHAR(200) DEFAULT NULL,
    rating      FLOAT        DEFAULT 4.0,
    in_stock    TINYINT(1)   DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Loan Lenders (Dummy) ─────────────────────
CREATE TABLE IF NOT EXISTS loan_lenders (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    lender_name    VARCHAR(200) NOT NULL,
    logo_emoji     VARCHAR(10)  DEFAULT '🏦',
    type           VARCHAR(100) DEFAULT 'Bank',
    interest_rate  FLOAT        NOT NULL DEFAULT 7.0,
    max_amount     DECIMAL(12,2) DEFAULT 500000,
    tenure_months  INT          DEFAULT 36,
    description    TEXT         DEFAULT NULL,
    requirements   TEXT         DEFAULT NULL,
    rating         FLOAT        DEFAULT 4.0,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Creditworthiness Index ───────────────────
CREATE TABLE IF NOT EXISTS credit_scores (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    farm_name       VARCHAR(200) DEFAULT 'My Farm',
    health_avg      FLOAT        DEFAULT 0,
    risk_avg        FLOAT        DEFAULT 0,
    consistency     FLOAT        DEFAULT 0,
    credit_score    INT          DEFAULT 0,
    grade           VARCHAR(10)  DEFAULT 'B',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ═══════════════════════════════════════════════════
-- Seed Dummy Data
-- ═══════════════════════════════════════════════════

-- Marketplace Products
INSERT INTO marketplace_products (name, category, description, price, unit, seller, location, rating) VALUES
('Organic Basmati Rice',      'Grains',      'Premium long-grain basmati rice, organically grown in Punjab. MSP-grade certified.',                    42.00,  'per kg',    'Singh Organic Farms',   'Amritsar, Punjab',       4.8),
('Farm Fresh Wheat',          'Grains',      'High-protein wheat grain, ideal for chapati and bread making. Chemical-free.',                          28.00,  'per kg',    'Kisan Cooperative',     'Karnal, Haryana',        4.5),
('Turmeric Powder (Organic)', 'Spices',      'Pure lakadong turmeric with 7%+ curcumin content. Lab-tested quality.',                                180.00, 'per kg',    'NE Spice Collective',   'Jaintia Hills, Meghalaya', 4.9),
('Cold-Pressed Mustard Oil',  'Oils',        'Traditional kachi ghani mustard oil. No chemicals, no preservatives.',                                 210.00, 'per litre', 'Rajasthan Oil Mills',   'Bharatpur, Rajasthan',   4.6),
('Alphonso Mango',            'Fruits',      'Premium Ratnagiri Alphonso mangoes. GI-tagged, naturally ripened.',                                    600.00, 'per dozen', 'Konkan Fresh',          'Ratnagiri, Maharashtra', 4.7),
('Neem-Based Pesticide',      'Agri Inputs', 'Organic neem oil pesticide concentrate. 10000 PPM Azadirachtin.',                                     350.00, 'per litre', 'GreenShield Agro',      'Indore, MP',             4.4),
('Vermicompost Premium',      'Fertilizers', 'High-quality vermicompost made from cow dung. Rich in NPK and microbes.',                              12.00,  'per kg',    'EarthWorm Organics',    'Nashik, Maharashtra',    4.3),
('Drip Irrigation Kit',       'Equipment',   '100-plant drip kit with timer. Saves 60% water vs flood irrigation.',                                  2800.00,'per set',   'AquaSmart Irrigation',  'Coimbatore, TN',         4.6),
('Solar Pest Trap',           'Equipment',   'Solar-powered insect light trap. Chemical-free pest control for 1 acre.',                              1500.00,'per unit',  'SunAgri Solutions',     'Pune, Maharashtra',      4.5),
('Heritage Tomato Seeds',     'Seeds',       'Desi variety tomato seeds. High yield, disease resistant. Pack of 100g.',                               85.00, 'per pack',  'Bija Swaraj Seedbank',  'Hyderabad, Telangana',   4.2),
('Organic Jaggery (Gur)',     'Processed',   'Chemical-free sugarcane jaggery from Kolhapur. Rich in iron and minerals.',                             65.00, 'per kg',    'Kolhapur Gur House',    'Kolhapur, Maharashtra',  4.8),
('Farm-Fresh Honey',          'Processed',   'Raw unprocessed multiflora honey. Sourced from Himalayan apiaries.',                                   450.00, 'per kg',    'Himalayan Bee Farm',    'Kullu, Himachal Pradesh',4.7);

-- Loan Lenders
INSERT INTO loan_lenders (lender_name, logo_emoji, type, interest_rate, max_amount, tenure_months, description, requirements, rating) VALUES
('State Bank of India',       '🏛️', 'Public Bank',    7.0,  1000000, 60, 'Kisan Credit Card & crop loan facility with lowest interest rates for farmers.', 'Land ownership proof, Aadhaar, PAN, 2 passport photos', 4.5),
('NABARD',                    '🌾', 'Govt Institution', 4.0, 2000000, 84, 'National agriculture development bank offering refinance & direct lending to farmers.', 'Farm registration, land records, bank statement', 4.7),
('HDFC Bank Agri Loan',       '🏦', 'Private Bank',   9.5,  750000, 48, 'Quick-disbursal agriculture loans with flexible repayment options for modern farmers.', 'KYC documents, land papers, crop plan, income proof', 4.2),
('Kisan Vikas Patra',         '📜', 'Govt Scheme',    6.9,  500000, 120,'Government savings-cum-loan instrument that doubles investment. Low-risk option.', 'Aadhaar card, PAN card, address proof', 4.4),
('Punjab National Bank',      '🏛️', 'Public Bank',    7.5,  800000, 60, 'PNB Kisan Tatkal Scheme — instant crop loans with subsidized rates for small farmers.', 'Land documents, Aadhaar, PAN, crop details', 4.3),
('Micro Units Dev (MUDRA)',   '🤝', 'Govt Scheme',    8.0,  1000000, 60, 'MUDRA loans under Shishu, Kishore & Tarun categories for agri-entrepreneurs.', 'Business plan, KYC, bank statement, photos', 4.6),
('Bajaj Finserv Agri',       '💼', 'NBFC',           10.5,  500000, 36, 'Digital-first agri loan with doorstep service and instant approval for verified farmers.', 'KYC, bank statements (6mo), land papers, crop proof', 3.9),
('Samunnati Financial',       '🌱', 'Agri-NBFC',      9.0,  300000, 24, 'Specialized agri-finance for FPOs and smallholder farmers. Collateral-free options.', 'FPO membership or land proof, KYC, farm photos', 4.1);
