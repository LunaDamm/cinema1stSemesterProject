USE movieDB;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(50) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL
);

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('company_description', 'A modern cinema with an old soul.'),
    ('opening_hours', 'Monday-Sunday, 10:00-23:00'),
    ('contact_information', 'The Marquee Cinema\n14 Picturehouse Lane\nhello@marquee.example\n+1 555 0100')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

CREATE TABLE IF NOT EXISTS news (
    newsID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_published TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS invoices (
    invoiceID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(40) NOT NULL UNIQUE,
    bookingID INT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('draft', 'sent', 'paid', 'void') NOT NULL DEFAULT 'draft',
    notes TEXT NOT NULL,
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bookingID) REFERENCES bookings(bookingID) ON DELETE SET NULL
);

ALTER TABLE showtimes ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) NOT NULL DEFAULT 0;
