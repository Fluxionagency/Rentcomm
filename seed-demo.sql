-- Optional demo data (matches the design prototype's sample rows).
-- Handy for previewing the Admin Portal before real leads exist.
-- Do NOT run on the live production database.

INSERT INTO investors (name, email, whatsapp, status, source, created_at) VALUES
  ('Adaeze Okafor', 'adaeze.okafor@gmail.com', '+234 803 221 4456', 'Meeting Scheduled', 'investor-journey', '2026-07-01 10:00:00'),
  ('Tunde Bakare', 'tunde.bakare@yahoo.com', '+234 705 902 1187', 'Contacted', 'investor-journey', '2026-07-03 11:30:00'),
  ('Chiamaka Nwosu', 'chiamaka.n@outlook.com', '+234 812 445 0093', 'New', 'portfolio-falcon-house', '2026-07-08 09:15:00'),
  ('Emeka Johnson', 'e.johnson@investcorp.ng', '+234 906 331 7720', 'Cold', 'investor-journey', '2026-07-10 16:45:00'),
  ('Folake Adeyemi', 'folake.a@gmail.com', '+234 802 118 5543', 'Contacted', 'portfolio-lwfhd', '2026-07-12 14:20:00');

INSERT INTO realtors (name, email, whatsapp, created_at) VALUES
  ('Ibrahim Musa', 'ibrahim.musa@remax.ng', '+234 807 662 4410', '2026-07-02 10:00:00'),
  ('Blessing Eze', 'blessing.eze@gmail.com', '+234 813 990 2261', '2026-07-05 12:00:00'),
  ('Kelechi Uba', 'kelechi.uba@homesng.com', '+234 701 456 8832', '2026-07-09 13:30:00'),
  ('Ngozi Chukwu', 'ngozi.c@propertyplus.ng', '+234 815 223 9901', '2026-07-12 15:00:00'),
  ('Yusuf Abdullahi', 'yusuf.a@gmail.com', '+234 908 774 1123', '2026-07-14 17:45:00');

INSERT INTO dispatches (realtor_id, pack_status, visit_status, visit_date)
SELECT id, 'Delivered', 'Scheduled', '2026-08-22' FROM realtors WHERE email = 'ibrahim.musa@remax.ng';
INSERT INTO dispatches (realtor_id, pack_status, visit_status, visit_date)
SELECT id, 'Dispatched', 'Not Scheduled', NULL FROM realtors WHERE email = 'blessing.eze@gmail.com';
INSERT INTO dispatches (realtor_id, pack_status, visit_status, visit_date)
SELECT id, 'Pending', 'Not Scheduled', NULL FROM realtors WHERE email = 'kelechi.uba@homesng.com';
INSERT INTO dispatches (realtor_id, pack_status, visit_status, visit_date)
SELECT id, 'Delivered', 'Completed', '2026-07-10' FROM realtors WHERE email = 'ngozi.c@propertyplus.ng';
INSERT INTO dispatches (realtor_id, pack_status, visit_status, visit_date)
SELECT id, 'Pending', 'Scheduled', '2026-08-28' FROM realtors WHERE email = 'yusuf.a@gmail.com';
