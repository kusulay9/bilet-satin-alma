-- Otobüs Bileti Satın Alma Platformu Veritabanı Şeması

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    firm_id INTEGER,
    credit REAL DEFAULT 0.00,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Firms table
CREATE TABLE IF NOT EXISTS firms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Trips table
CREATE TABLE IF NOT EXISTS trips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    firm_id INTEGER NOT NULL,
    from_city TEXT NOT NULL,
    to_city TEXT NOT NULL,
    date TEXT NOT NULL,
    time TEXT NOT NULL,
    duration INTEGER NOT NULL,
    price REAL NOT NULL,
    seat_count INTEGER NOT NULL DEFAULT 40,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (firm_id) REFERENCES firms(id)
);

-- Tickets table
CREATE TABLE IF NOT EXISTS tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    trip_id INTEGER NOT NULL,
    seat_no INTEGER NOT NULL,
    price REAL NOT NULL,
    original_price REAL,
    discount_amount REAL DEFAULT 0,
    coupon_code TEXT,
    status TEXT DEFAULT 'active',
    purchase_time TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (trip_id) REFERENCES trips(id)
);

-- Coupons table
CREATE TABLE IF NOT EXISTS coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    discount_percent INTEGER NOT NULL,
    usage_limit INTEGER DEFAULT 1,
    expiry_date TEXT NOT NULL,
    firm_id INTEGER,
    is_global INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (firm_id) REFERENCES firms(id)
);

-- Logs table
CREATE TABLE IF NOT EXISTS logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    details TEXT,
    ip_address TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Firmalar
INSERT INTO firms (name) VALUES 
('Metro Turizm'),
('Ulusoy'),
('Kamil Koç'),
('Pamukkale Turizm'),
('Varan');

-- Yönetici hesabı
INSERT INTO users (name, email, password, role, credit) VALUES 
('Sistem Yöneticisi', 'admin@sbilet.com', '$2y$12$uSt./16iHcjYzgiC18q0/e3KblMjNORVnrTNCO2RJJ5SmXcZylwAO', 'admin', 1000.00);

-- Firma yöneticileri
INSERT INTO users (name, email, password, role, firm_id, credit) VALUES 
('Metro Müdürü', 'metro@metro.com.tr', '$2y$12$uSt./16iHcjYzgiC18q0/e3KblMjNORVnrTNCO2RJJ5SmXcZylwAO', 'firma_admin', 1, 500.00),
('Ulusoy Müdürü', 'yonetici@ulusoy.com.tr', '$2y$12$uSt./16iHcjYzgiC18q0/e3KblMjNORVnrTNCO2RJJ5SmXcZylwAO', 'firma_admin', 2, 500.00);

-- Normal kullanıcı
INSERT INTO users (name, email, password, role, credit) VALUES 
('Ahmet Yılmaz', 'ahmet@outlook.com', '$2y$12$n/33rXfZdSgajMqkMoPI.OmAfHIrJ6BQz3EmZKHwg8i1PIkkwzBiS', 'user', 100.00);

-- Seferler
INSERT INTO trips (firm_id, from_city, to_city, date, time, duration, price, seat_count) VALUES 
(1, 'İstanbul', 'Ankara', '2024-12-25', '08:00', 480, 150.00, 40),
(1, 'İstanbul', 'İzmir', '2024-12-25', '14:00', 600, 200.00, 40),
(2, 'Ankara', 'İstanbul', '2024-12-25', '10:00', 480, 140.00, 40),
(2, 'Ankara', 'Antalya', '2024-12-26', '16:00', 540, 180.00, 40),
(3, 'İzmir', 'İstanbul', '2024-12-26', '09:00', 600, 190.00, 40);

-- Kuponlar
INSERT INTO coupons (code, discount_percent, usage_limit, expiry_date, firm_id, is_global) VALUES 
('WELCOME10', 10, 100, '2024-12-31', NULL, 1),
('METRO20', 20, 50, '2024-12-31', 1, 0),
('ULUSOY15', 15, 30, '2024-12-31', 2, 0);
