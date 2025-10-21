-- Create a sample table
CREATE TABLE IF NOT EXISTS tx_tmexample_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some sample data
INSERT INTO tx_tmexample_table (title, description) VALUES
('First Item', 'This is the first item.'),
('Second Item', 'This is the second item.');

-- Another example table
CREATE TABLE IF NOT EXISTS tx_tmexample_users (
    uid INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample users
INSERT INTO tx_tmexample_users (username, email) VALUES
('user1', 'user1@example.com'),
('user2', 'user2@example.com');
