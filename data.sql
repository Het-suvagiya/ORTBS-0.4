-- Initial Data for QuickTable
-- DATA SAFE: Removes potential existing admin to avoid unique constraint errors.

DELETE FROM `tbl_admin` WHERE `a_email` = 'admin@quicktable.com';

-- Admin User: admin@quicktable.com / admin@123
INSERT INTO `tbl_admin` (`a_email`, `a_password`, `a_firstname`, `a_lastname`) 
VALUES ('admin@quicktable.com', '$2y$10$pSjHdfIML2H.8b8NW5RU6OwczJDQer83V2hji0.qfQjXVKk4PIE6W', 'Main', 'Admin');
