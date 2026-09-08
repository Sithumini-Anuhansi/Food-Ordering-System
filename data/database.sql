CREATE DATABASE IF NOT EXISTS `food-ordering-system`;
USE `food-ordering-system`;

CREATE TABLE IF NOT EXISTS users (
    User_ID                INT AUTO_INCREMENT PRIMARY KEY,
    Role                   VARCHAR(20)  NOT NULL,   -- 'admin', 'customer', 'kitchen', 'delivery'
    Name                   VARCHAR(100) NOT NULL,
    Email                  VARCHAR(150) NOT NULL UNIQUE,
    Password               VARCHAR(255) NOT NULL,   -- stored with password_hash()
    Phone_Number           VARCHAR(20),
    Address                VARCHAR(255),
    Reset_Token            VARCHAR(64)  NULL,       -- forgot-password flow
    Reset_Token_Expiry     DATETIME     NULL,
    Failed_Login_Attempts  INT NOT NULL DEFAULT 0,  -- login rate limiting
    Lockout_Until          DATETIME     NULL
);

CREATE TABLE IF NOT EXISTS food_items (
    Item_ID         INT AUTO_INCREMENT PRIMARY KEY,
    Name            VARCHAR(150) NOT NULL,
    Description     TEXT,
    Price           DECIMAL(10,2) NOT NULL,
    Image           VARCHAR(255),
    Category        VARCHAR(100),
    Rating          DECIMAL(2,1) DEFAULT 0.0,
    available        TINYINT(1) NOT NULL DEFAULT 1,  -- used by api/get_menu.php
    Stock_Quantity  INT NULL   -- NULL = unlimited/not tracked; otherwise decremented per order
);

CREATE TABLE IF NOT EXISTS orders (
    ID                     INT AUTO_INCREMENT PRIMARY KEY,
    User_ID                INT NOT NULL,
    Total                  DECIMAL(10,2) NOT NULL,          -- items + Delivery_Fee + Tax
    Status                 VARCHAR(30) NOT NULL DEFAULT 'Pending',
    Order_Time             DATETIME DEFAULT CURRENT_TIMESTAMP,
    Delivery_Fee           DECIMAL(10,2) NOT NULL DEFAULT 0,
    Tax                    DECIMAL(10,2) NOT NULL DEFAULT 0,
    Delivery_Address       VARCHAR(255) NULL,   -- snapshot at order time (may differ from profile)
    Delivery_Time          VARCHAR(50)  NULL,   -- 'ASAP' or a scheduled date/time string
    Special_Instructions   TEXT NULL,
    Payment_Method         VARCHAR(30) NOT NULL DEFAULT 'Cash on Delivery',
    Payment_Status         VARCHAR(20) NOT NULL DEFAULT 'Unpaid',
    FOREIGN KEY (User_ID) REFERENCES users(User_ID)
);

CREATE TABLE IF NOT EXISTS order_items (
    ID        INT AUTO_INCREMENT PRIMARY KEY,
    Order_ID  INT NOT NULL,
    Item_ID   INT NOT NULL,
    Quantity  INT NOT NULL,
    Price     DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (Order_ID) REFERENCES orders(ID),
    FOREIGN KEY (Item_ID) REFERENCES food_items(Item_ID)
);

CREATE TABLE IF NOT EXISTS reviews (
    Review_ID      INT AUTO_INCREMENT PRIMARY KEY,
    Name           VARCHAR(150) NOT NULL,
    Image          VARCHAR(255),
    Rating         DECIMAL(2,1) NOT NULL DEFAULT 5.0,
    Review_Text    TEXT NOT NULL,
    Display_Order  INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS team_members (
    Member_ID      INT AUTO_INCREMENT PRIMARY KEY,
    Name           VARCHAR(150) NOT NULL,
    Image          VARCHAR(255),
    Bio            TEXT,
    Display_Order  INT NOT NULL DEFAULT 0
);

-- Seed an admin account so you can log in immediately.
-- Password below is: admin123
-- (hash generated with PHP's password_hash(), PASSWORD_DEFAULT/bcrypt)
INSERT INTO users (Role, Name, Email, Password, Phone_Number, Address)
VALUES (
    'admin',
    'Admin',
    'admin@example.com',
    '$2y$10$kd52ywbT8BkOCrlqe8YchuKlXMCiMj49dathmG7CkQvjLbl9/OU0K',
    '0000000000',
    'HQ'
);

-- Seed the 70 menu items (moved out of the old hardcoded Menu.php)
INSERT INTO food_items (Name, Description, Price, Image, Category, Rating, available) VALUES
    ('Spicy Chicken Wings', 'Crispy wings tossed in a fiery buffalo sauce.', 7.99, 'cat-hot-spicy-chicken-wings.jpg', 'Hot Picks', 4.5, 1),
    ('Chilli Cheese Fries', 'Golden fries topped with spicy beef and cheddar.', 5.49, 'cat-hot-chilli-cheese-fries.jpg', 'Hot Picks', 4.5, 1),
    ('Peri-Peri Chicken Skewers', 'Grilled skewers marinated in tangy peri-peri sauce.', 8.99, 'cat-hot-peri-peri-chicken-skewers.jpg', 'Hot Picks', 4.5, 1),
    ('BBQ Pulled Pork Sliders', 'Mini buns filled with smoky pulled pork.', 6.99, 'cat-hot-bbq-pulled-pork-sliders.jpg', 'Hot Picks', 4.5, 1),
    ('Hot Garlic Prawns', 'Tiger prawns sautéed in chili garlic sauce.', 9.99, 'cat-hot-hot-garlic-prawns.jpg', 'Hot Picks', 4.5, 1),
    ('Loaded Nachos', 'Nachos with jalapeños, cheese, and beef.', 6.49, 'cat-hot-loaded-nachos.jpg', 'Hot Picks', 4.5, 1),
    ('Sriracha Chicken Wrap', 'Tortilla wrap with grilled chicken and sriracha mayo.', 7.49, 'cat-hot-sriracha-chicken-wrap.jpg', 'Hot Picks', 4.5, 1),
    ('Fiery Veggie Tacos', 'Spicy bean and vegetable tacos.', 5.99, 'cat-hot-fiery-veggie-tacos.jpg', 'Hot Picks', 4.5, 1),
    ('Cajun Fried Chicken', 'Southern-style chicken with Cajun spice.', 8.49, 'cat-hot-cajun-fried-chicken.jpg', 'Hot Picks', 4.5, 1),
    ('Spicy Mutton Chops', 'Tender mutton cooked in hot masala.', 10.99, 'cat-hot-spicy-mutton-chops.jpg', 'Hot Picks', 4.5, 1),
    ('Cheeseburger', 'Classic beef burger with melted cheese.', 4.99, 'cat-fast-cheeseburger.jpg', 'Fast Food', 4.5, 1),
    ('Chicken Sandwich', 'Crispy chicken fillet on a toasted bun.', 5.49, 'cat-fast-chicken-sandwich.jpg', 'Fast Food', 4.5, 1),
    ('Veggie Wrap', 'Fresh veggies wrapped with hummus.', 4.29, 'cat-fast-veggie-wrap.jpg', 'Fast Food', 4.5, 1),
    ('French Fries', 'Golden and crispy potato fries.', 2.49, 'cat-fast-french-fries.jpg', 'Fast Food', 4.5, 1),
    ('Onion Rings', 'Crunchy deep-fried onion rings.', 2.99, 'cat-fast-onion-rings.jpg', 'Fast Food', 4.5, 1),
    ('Chicken Nuggets', 'Juicy chicken bites, perfect for dipping.', 3.99, 'cat-fast-chicken-nuggets.jpg', 'Fast Food', 4.5, 1),
    ('Fish Fillet Burger', 'Breaded fish patty with tartar sauce.', 5.99, 'cat-fast-fish-fillet-burger.jpg', 'Fast Food', 4.5, 1),
    ('Chicken Popcorn', 'Bite-sized crispy chicken pieces.', 4.49, 'cat-fast-chicken-popcorn.jpg', 'Fast Food', 4.5, 1),
    ('Mozzarella Sticks', 'Melted mozzarella wrapped in a crispy shell.', 4.99, 'cat-fast-mozzarella-sticks.jpg', 'Fast Food', 4.5, 1),
    ('Hot Dog Classic', 'Grilled sausage in a soft bun with mustard.', 3.99, 'cat-fast-hotdog-classic.jpg', 'Fast Food', 4.5, 1),
    ('Margherita', 'Classic cheese and tomato pizza.', 7.99, 'cat-pizza-margherita.jpg', 'Pizza', 4.5, 1),
    ('Pepperoni', 'Loaded with spicy pepperoni slices.', 8.99, 'cat-pizza-pepperoni.jpg', 'Pizza', 4.5, 1),
    ('BBQ Chicken', 'Grilled chicken with BBQ sauce.', 9.49, 'cat-pizza-bbq-chicken.jpg', 'Pizza', 4.5, 1),
    ('Veggie Deluxe', 'Bell peppers, olives, onions & mushrooms.', 8.49, 'cat-pizza-veggie-deluxe.jpg', 'Pizza', 4.5, 1),
    ('Meat Lovers', 'A mix of beef, chicken, ham, and sausage.', 10.99, 'cat-pizza-meat-lovers.jpg', 'Pizza', 4.5, 1),
    ('Four Cheese', 'Mozzarella, cheddar, feta, and parmesan.', 9.99, 'cat-pizza-four-cheese.jpg', 'Pizza', 4.5, 1),
    ('Hawaiian', 'Pineapple and ham combo.', 8.99, 'cat-pizza-hawaiian.jpg', 'Pizza', 4.5, 1),
    ('Spicy Paneer', 'Indian twist with spiced paneer cubes.', 8.49, 'cat-pizza-spicy-paneer.jpg', 'Pizza', 4.5, 1),
    ('Chicken Alfredo', 'Creamy white sauce with grilled chicken.', 9.99, 'cat-pizza-chicken-alfredo.jpg', 'Pizza', 4.5, 1),
    ('Mushroom Feast', 'Sauteed mushrooms with herbs and cheese.', 8.29, 'cat-pizza-mushroom-feast.jpg', 'Pizza', 4.5, 1),
    ('Grilled Chicken Platter', 'Chicken breast with rice and veggies.', 12.99, 'cat-main-grilled-chicken-platter.jpg', 'Main Dishes', 4.5, 1),
    ('Beef Steak', 'Grilled steak with peppercorn sauce.', 15.99, 'cat-main-beef-steak.jpg', 'Main Dishes', 4.5, 1),
    ('Chicken Biriyani', 'Spiced rice with marinated chicken.', 10.49, 'cat-main-chicken-biriyani.jpg', 'Main Dishes', 4.5, 1),
    ('Vegetable Curry', 'Mixed vegetables in coconut curry.', 8.99, 'cat-main-vegetable-curry.jpg', 'Main Dishes', 4.5, 1),
    ('Butter Chicken', 'Creamy tomato-based chicken curry.', 11.99, 'cat-main-butter-chicken.jpg', 'Main Dishes', 4.5, 1),
    ('Grilled Salmon', 'Served with lemon herb sauce and salad.', 14.99, 'cat-main-grilled-salmon.jpg', 'Main Dishes', 4.5, 1),
    ('Roast Chicken Dinner', 'Half-roast chicken with potatoes and gravy.', 13.49, 'cat-main-roast-chicken-dinner.jpg', 'Main Dishes', 4.5, 1),
    ('Spaghetti Bolognese', 'Pasta in rich meat sauce.', 9.99, 'cat-main-spaghetti.jpg', 'Main Dishes', 4.5, 1),
    ('Tofu Stir Fry', 'Tofu and veggies tossed in soy sauce.', 9.49, 'cat-main-tofu.jpg', 'Main Dishes', 4.5, 1),
    ('Lamb Curry', 'Tender lamb cooked in aromatic spices.', 12.49, 'cat-main-lamb-curry.jpg', 'Main Dishes', 4.5, 1),
    ('Caesar Salad', 'Romaine lettuce, parmesan & croutons.', 6.99, 'cat-salad-caesar.jpg', 'Salads', 4.5, 1),
    ('Greek Salad', 'Feta, olives, cucumber, and tomatoes.', 7.49, 'cat-salad-greek.jpg', 'Salads', 4.5, 1),
    ('Chicken Garden Salad', 'Grilled chicken with fresh greens.', 8.49, 'cat-salad-chicken-garden.jpg', 'Salads', 4.5, 1),
    ('Tuna Salad', 'Tuna with beans, lettuce, and vinaigrette.', 7.99, 'cat-salad-tuna.jpg', 'Salads', 4.5, 1),
    ('Caprese Salad', 'Tomato, mozzarella, basil, and olive oil.', 6.99, 'cat-salad-caprese.jpg', 'Salads', 4.5, 1),
    ('Egg Salad', 'Creamy egg mix with herbs.', 5.49, 'cat-salad-egg.jpg', 'Salads', 4.5, 1),
    ('Avocado Salad', 'Avocado, cherry tomatoes & lime dressing.', 7.99, 'cat-salad-avocado.jpg', 'Salads', 4.5, 1),
    ('Quinoa Salad', 'Quinoa with vegetables and lemon.', 8.29, 'cat-salad-quinoa.jpg', 'Salads', 4.5, 1),
    ('Coleslaw Salad', 'Crunchy cabbage in creamy dressing.', 4.49, 'cat-salad-coleslaw.jpg', 'Salads', 4.5, 1),
    ('Shrimp Salad', 'Chilled shrimp with greens and citrus.', 9.49, 'cat-salad-shrimp.jpg', 'Salads', 4.5, 1),
    ('Chocolate Lava Cake', 'Warm cake with gooey chocolate center.', 4.99, 'cat-des-choc-lava.jpg', 'Desserts', 4.5, 1),
    ('Cheesecake', 'Creamy slice with a buttery crust.', 4.49, 'cat-des-cheesecake.jpg', 'Desserts', 4.5, 1),
    ('Brownie Sundae', 'Brownie topped with vanilla ice cream.', 5.49, 'cat-des-brownie-sundae.jpg', 'Desserts', 4.5, 1),
    ('Fruit Salad', 'Fresh seasonal fruits with honey.', 3.99, 'cat-des-fruitsalad.jpg', 'Desserts', 4.5, 1),
    ('Ice Cream Triple Scoop', 'Choose 3 flavors from our ice cream bar.', 4.99, 'cat-des-icecream.jpg', 'Desserts', 4.5, 1),
    ('Mango Mousse', 'Light and fluffy mango-flavored mousse.', 4.29, 'cat-des-mango-mousse.jpg', 'Desserts', 4.5, 1),
    ('Tiramisu', 'Italian layered dessert with coffee flavor.', 5.49, 'cat-des-tiramisu.jpg', 'Desserts', 4.5, 1),
    ('Gulab Jamun', 'Indian sweet dumplings soaked in syrup.', 3.49, 'cat-des-gulab.jpg', 'Desserts', 4.5, 1),
    ('Waffle with Syrup', 'Freshly baked waffle with maple syrup.', 4.99, 'cat-des-waffle.jpg', 'Desserts', 4.5, 1),
    ('Carrot Cake', 'Moist cake topped with cream cheese frosting.', 4.79, 'cat-des-carrot-cake.jpg', 'Desserts', 4.5, 1),
    ('Coca-Cola (Can)', 'Classic soft drink.', 1.99, 'cat-bev-cocacola.jpg', 'Beverages', 4.5, 1),
    ('Fresh Orange Juice', 'Freshly squeezed and vitamin-rich.', 3.49, 'cat-bev-orange-juice.jpg', 'Beverages', 4.5, 1),
    ('Iced Tea', 'Cold black tea with lemon.', 2.99, 'cat-bev-iced-tea.jpg', 'Beverages', 4.5, 1),
    ('Mango Smoothie', 'Creamy mango blend with yogurt.', 4.49, 'cat-bev-mango-smoothie.jpg', 'Beverages', 4.5, 1),
    ('Cold Coffee', 'Chilled coffee with milk and sugar.', 3.99, 'cat-bev-cold-coffee.jpg', 'Beverages', 4.5, 1),
    ('Lemon Mint Cooler', 'Zesty lemon and mint beverage.', 3.79, 'cat-bev-lemon-mint-cooler.jpg', 'Beverages', 4.5, 1),
    ('Milkshake (Strawberry)', 'Rich and thick strawberry shake.', 4.29, 'cat-bev-strawberry-milkshake.jpg', 'Beverages', 4.5, 1),
    ('Green Tea', 'Light and antioxidant-rich.', 2.49, 'cat-bev-green-tea.jpg', 'Beverages', 4.5, 1),
    ('Hot Chocolate', 'Creamy and warm cocoa drink.', 3.99, 'cat-bev-hot-chocolate.jpg', 'Beverages', 4.5, 1),
    ('Mineral Water (Bottle)', 'Pure and refreshing bottled water.', 1.49, 'cat-bev-mineral-water.jpg', 'Beverages', 4.5, 1);

-- Seed the 10 customer reviews (moved out of the old hardcoded Reviews.php)
INSERT INTO reviews (Name, Image, Rating, Review_Text, Display_Order) VALUES
    ('Sarah Fernando', 'review_1.png', 5.0, 'Absolutely love this service! The food is always fresh and arrives right on time. The platform is easy to use and has so many great options. Highly recommended!', 1),
    ('Lahiru S.', 'review_2.png', 4.5, 'Good vibes. Nice UI, fast checkout, and food doesn’t disappoint. Add more dessert options tho plz 😭', 2),
    ('Dinuli Wijesinghe', 'review_3.png', 5.0, 'This has become my go-to food ordering platform. Super reliable, and I love how I can track my order in real time. The kitchen staff does a fantastic job!', 3),
    ('Kavindu Perera', 'review_4.png', 4.5, 'Great variety and fast delivery. One star off because my drink was missing once, but customer support handled it quickly. Will definitely keep ordering!', 4),
    ('Janith M.', 'review_5.png', 5.0, 'Yo this app is a lifesaver! I was starving late at night and got hot kottu in like 25 mins. Bless up 🙌🔥', 5),
    ('Nadeesha Silva', 'review_6.png', 5.0, 'Perfect for busy days! I’ve been ordering lunch from here for weeks and it never disappoints. The portions are generous and the taste is amazing.', 6),
    ('Nuwani R.', 'review_7.png', 4.5, 'Food’s so good! Portions are decent and prices are chill. One time delivery guy was a bit late, but food still warm so all good 😅', 7),
    ('Tharindu Jayasena', 'review_8.png', 4.5, 'Tasty food, good pricing, and very helpful delivery staff. I just wish the app had better filters to sort by spice level or meal type. Other than that, excellent service!', 8),
    ('Harsha D.', 'review_9.png', 5.0, 'Tried it randomly and now I’m hooked. Fast, tasty, and no drama. Love how I can just tap and get food without calling anyone.', 9),
    ('Shenali T.', 'review_10.png', 5.0, 'Honestly? Way better than other sites I’ve tried. Ordered dinner with my friends and everyone was super happy. 10/10 would eat again 😂🍔', 10);

-- Seed the 5 team members (moved out of the old hardcoded About.php)
INSERT INTO team_members (Name, Image, Bio, Display_Order) VALUES
    ('Chef Anura Perera', 'chef1.png', 'Master of traditional Sri Lankan rice & curry with 20+ years of culinary experience.', 1),
    ('Chef Nadeesha Silva', 'chef2.png', 'Renowned for her innovative coconut-based desserts and fusion dishes.', 2),
    ('Chef Kamal Jayasinghe', 'chef3.jpg', 'Expert in seafood and coastal cuisine, especially Jaffna-style crab curry.', 3),
    ('Chef Ruwan Gunasekara', 'chef4.jpg', 'Pastry chef with international experience specializing in Sri Lankan sweets.', 4),
    ('Chef Dilani Fernando', 'chef5.png', 'Known for healthy Sri Lankan vegetarian dishes and ayurvedic food concepts.', 5);
