-- Add Working Products to Database
-- These match the products in shopping-cart.html

INSERT INTO products (id, name, price, description, category, in_stock, created_at) 
VALUES 
(1, 'Gratitude Journal', 89990, 'Transform your life with gratitude', 'Journals', 1, NOW()),
(2, 'Fitness Journal', 71990, 'Track your health and fitness goals', 'Journals', 1, NOW()),
(3, 'Prayer Journal', 82490, 'Deepen your spiritual connection', 'Journals', 1, NOW())
ON DUPLICATE KEY UPDATE 
  name = VALUES(name),
  price = VALUES(price),
  description = VALUES(description),
  category = VALUES(category),
  in_stock = 1;
