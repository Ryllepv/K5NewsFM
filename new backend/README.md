# News Management Web Application

This project is a simple news management web application built using pure PHP and MySQL. It focuses on backend features and basic HTML forms, providing an admin panel for managing news articles, tags, live updates, and program schedules.

## Features

### Public Landing Page
- Displays the latest news articles with:
  - Title
  - Date Posted
  - Tags
  - Optional Preview of article content
- Live Updates Section for urgent headlines
- Weather Block (static or using an API)
- Daily or Weekly Program Schedule Table
- Clickable list of tags for filtering news
- Basic search bar for articles
- Link to Admin Login page

### Admin Panel
- **Admin Login Page**: Secure login for admin users.
- **Admin Dashboard**: Manage news articles, tags, live updates, and program schedules.
- **CRUD Operations**: Create, Read, Update, and Delete functionalities for articles, tags, live updates, and program schedules.

## Database Schema

The application uses the following database schema:

```sql
users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50),
  password VARCHAR(255)
)

articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255),
  content TEXT,
  created_at DATETIME,
  image_path VARCHAR(255)
)

tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100)
)

article_tags (
  article_id INT,
  tag_id INT,
  PRIMARY KEY (article_id, tag_id)
)

live_updates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  message TEXT,
  created_at DATETIME
)

program_schedule (
  id INT AUTO_INCREMENT PRIMARY KEY,
  program_name VARCHAR(100),
  day_of_week VARCHAR(10),
  time_slot VARCHAR(50)
)
```

## Security

- Passwords are hashed using `password_hash()` and verified with `password_verify()`.
- Prepared statements are used to prevent SQL injection.
- All inputs are sanitized to ensure security.

## Setup Instructions

1. Clone the repository to your local machine.
2. Import the `init.sql` file into your MySQL database to create the necessary tables.
3. Configure the database connection in `config/db.php`.
4. Access the application via your web server.

## Usage

- Navigate to `index.php` to view the public landing page.
- Access the admin panel by clicking the Admin Login link and entering your credentials.
- Use the admin dashboard to manage articles, tags, live updates, and program schedules.

## Contributing

Feel free to fork the repository and submit pull requests for any improvements or features you would like to add.