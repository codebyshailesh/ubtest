-- NeighbourShed database schema
-- A neighbourhood tool-sharing platform: tool_owner, renter, admin

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)        NOT NULL,
    email           VARCHAR(160)        NOT NULL UNIQUE,
    password_hash   VARCHAR(255)        NOT NULL,
    role            ENUM('tool_owner','renter','admin') NOT NULL DEFAULT 'renter',
    phone           VARCHAR(30)         NULL,
    -- Address is required at registration time because tools are shown
    -- to renters based on proximity to it.
    address_line    VARCHAR(255)        NOT NULL,
    city            VARCHAR(100)        NOT NULL,
    state           VARCHAR(100)        NOT NULL,
    postal_code     VARCHAR(20)         NOT NULL,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- tools  (listed by a tool_owner, must be verified by an admin)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tools (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    owner_id        INT                 NOT NULL,
    name            VARCHAR(150)        NOT NULL,
    description     TEXT                NULL,
    category        VARCHAR(80)         NOT NULL,
    photo_url       VARCHAR(255)        NULL,
    daily_rate      DECIMAL(10,2)       NOT NULL DEFAULT 0.00,
    deposit_amount  DECIMAL(10,2)       NOT NULL DEFAULT 0.00,
    -- Pickup location, defaults to the owner's registered address
    address_line    VARCHAR(255)        NOT NULL,
    city            VARCHAR(100)        NOT NULL,
    state           VARCHAR(100)        NOT NULL,
    postal_code     VARCHAR(20)         NOT NULL,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_notes     VARCHAR(255)        NULL,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tools_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- bookings  (a renter borrows a tool for a start/end date range)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tool_id         INT                 NOT NULL,
    renter_id       INT                 NOT NULL,
    start_date      DATE                NOT NULL,
    end_date        DATE                NOT NULL,
    total_days      INT                 NOT NULL,
    total_price     DECIMAL(10,2)       NOT NULL,
    -- This platform only supports cash paid on delivery/pickup of the tool.
    payment_method  VARCHAR(30)         NOT NULL DEFAULT 'cash_on_delivery',
    status          ENUM('pending','confirmed','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_tool   FOREIGN KEY (tool_id)   REFERENCES tools(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_renter FOREIGN KEY (renter_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- auth_tokens  (cookie-based session tokens)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth_tokens (
    token           VARCHAR(64)         NOT NULL PRIMARY KEY,
    user_id         INT                 NOT NULL,
    expiry          DATETIME            NOT NULL,
    CONSTRAINT fk_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Seed an admin account (email: admin@neighbourshed.test / password: Admin@123)
-- Change this password immediately after import.
-- ---------------------------------------------------------------------
INSERT INTO users (name, email, password_hash, role, phone, address_line, city, state, postal_code)
VALUES (
    'Platform Admin',
    'admin@neighbourshed.test',
    '$2b$10$i1IsEoaG5cQHmFP8VqhPI.FryQ30dWB17czmssgwhBBneAomoH3Ka',
    'admin',
    '0000000000',
    'Platform HQ',
    'Kathmandu',
    'Bagmati',
    '44600'
);
