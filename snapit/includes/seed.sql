
INSERT INTO users (name, email, password, phone, address, role, status) VALUES
('Admin User', 'admin@snapit.ph', '$2y$12$NpXATJzDUz.C3YBjhHy9oOIUwNyWabZM4aI0F2Y92OdcIPfDNV9Fq', '09170000000', '123 Snap It HQ, Manila', 'admin', 'active'),
('Staff Member', 'staff@snapit.ph', '$2y$12$oF8ldqAS61ZTi5NPsJZSCufzZiowueIboPY63QRU8KwiHLocYYhFO', '09171111111', '456 Booth Ave, QC', 'staff', 'active'),
('Customer Demo', 'customer@snapit.ph', '$2y$12$Phl6741oilQU70juuuwACuRgvsQUj5lSpEVXEPvLMGsaRU1anHdZu', '09272222222', '789 Customer St, Makati', 'customer', 'active');

INSERT INTO packages (name, description, duration_hours, softcopy_count, hardcopy_count, base_price, has_softcopy_addon, softcopy_addon_price, is_active) VALUES
('Solo Event Package', 'Perfect for intimate gatherings and small events.', 6, 500, 200, 8000.00, 1, 1500.00, 1),
('Classic Party Package', 'Our most popular option for birthdays and weddings.', 8, 1000, 500, 14500.00, 1, 2500.00, 1),
('Premium Corporate Package', 'Full-day service with unlimited prints and express delivery.', 10, 2000, 1000, 25000.00, 1, 3500.00, 1),
('Grand Wedding Package', '12-hour coverage, albums included, on-site photographer.', 12, 3000, 1500, 42000.00, 1, 5000.00, 1),
('Quick Booth (Add-on)', 'Short 2-hour rental for mini-events.', 2, 100, 50, 3500.00, 1, 800.00, 1);

INSERT INTO camera_presets (name, description, brightness, contrast, saturation, warmness, is_active) VALUES
('Natural', 'Balanced, true-to-life colors.', 0, 0, 0, 0, 1),
('Vibrant', 'Boosted saturation and contrast for vivid shots.', 5, 15, 25, 0, 1),
('Soft Glow', 'Bright and dreamy with a gentle look.', 20, -5, 5, 10, 1),
('Studio Pro', 'Professional studio-style preset.', 10, 20, 10, -5, 1),
('Warm Sunset', 'Golden-hour inspired warmth.', 8, 8, 5, 30, 1),
('Cool Mono', 'Crisp, cool-toned black and white look.', 0, 15, -100, -20, 1);

INSERT INTO filters (name, css_filter, preview_color, is_active) VALUES
('None', 'none', '#ffffff', 1),
('Vintage', 'sepia(0.6) contrast(1.1)', '#d4a574', 1),
('Black & White', 'grayscale(1) contrast(1.2)', '#444444', 1),
('Pop', 'saturate(2) contrast(1.15)', '#e83e8c', 1),
('Cool', 'hue-rotate(20deg) saturate(1.3)', '#3b82f6', 1),
('Dreamy', 'brightness(1.1) contrast(0.9) saturate(1.2) blur(0.3px)', '#c4b5fd', 1),
('Film', 'sepia(0.2) contrast(1.1) brightness(0.95)', '#a16207', 1),
('Noir', 'grayscale(1) contrast(1.5) brightness(0.9)', '#111111', 1);

INSERT INTO layouts (name, photo_count, grid_cols, grid_rows, is_active) VALUES
('Solo Shot', 1, 1, 1, 1),
('4-Pic Strip', 4, 2, 2, 1),
('6-Pic Grid', 6, 3, 2, 1);

INSERT INTO frame_designs (name, image_path, border_color, border_width, is_active) VALUES
('Classic Purple', NULL, '#6f42c1', 6, 1),
('Princess Pink', NULL, '#e83e8c', 8, 1),
('Sunset Orange', NULL, '#fd7e14', 6, 1),
('Ocean Blue', NULL, '#0dcaf0', 8, 1),
('Forest Green', NULL, '#198754', 6, 1),
('Thin Gold', NULL, '#ffc107', 4, 1),
('Birthday Confetti', NULL, '#ff6b9d', 10, 1),
('Elegant White', NULL, '#ffffff', 8, 1);

INSERT INTO inventory_items (sku, name, description, category, quantity_on_hand, reorder_level, unit_measure, last_restocked_at) VALUES
('PAP-A4-GLOSS', 'Glossy Photo Paper 4R', 'Premium glossy 4R photo paper for prints.', 'paper', 1500, 300, 'sheets', NOW() - INTERVAL 2 DAY),
('PAP-A4-MATTE', 'Matte Photo Paper 4R', 'Anti-glare matte 4R paper.', 'paper', 800, 200, 'sheets', NOW() - INTERVAL 5 DAY),
('INK-CYAN-100', 'Cyan Ink Cartridge 100ml', 'Cyan dye ink for photo printers.', 'ink', 18, 5, 'bottle', NOW() - INTERVAL 1 DAY),
('INK-MAGENTA-100', 'Magenta Ink Cartridge 100ml', 'Magenta dye ink for photo printers.', 'ink', 15, 5, 'bottle', NOW() - INTERVAL 1 DAY),
('INK-YELLOW-100', 'Yellow Ink Cartridge 100ml', 'Yellow dye ink for photo printers.', 'ink', 12, 5, 'bottle', NOW() - INTERVAL 1 DAY),
('INK-BLACK-100', 'Black Ink Cartridge 100ml', 'Photo-black dye ink.', 'ink', 22, 5, 'bottle', NOW() - INTERVAL 1 DAY),
('PRN-CLEAN', 'Printer Cleaning Kit', 'Printer head cleaning wipes.', 'other', 40, 10, 'kit', NOW() - INTERVAL 7 DAY),
('CABLE-USB', 'USB Type-B Cable 3m', 'Printer USB cable.', 'other', 20, 5, 'pcs', NOW() - INTERVAL 10 DAY);
