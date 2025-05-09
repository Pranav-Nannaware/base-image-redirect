# Bharat English School & Jr College - Integrated Website and Registration System

A comprehensive web-based system combining the school's website and student registration management system. The system handles both aided and unaided student registrations, fee payments, document management, and provides a modern website interface.

## Features

### Website Features
- Responsive design that works on all devices
- Dynamic content loaded from JSON files
- Modern and clean UI with Tailwind CSS
- School information and announcements
- Program and facility showcase

### Registration System Features
- Student registration for both aided and unaided categories
- Fee payment processing
- Document upload and management
- Receipt generation
- Admin panel for student approval and management
- Secure document storage in database

## Project Structure

```
├── site/                   # Main website directory
│   ├── admin/             # Admin panel
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   ├── images/            # Static images
│   ├── includes/          # PHP includes
│   ├── data/              # JSON data files
│   ├── dbmanage/          # Database management
│   ├── studlogin/         # Student login system
│   ├── studregister/      # Student registration
│   ├── index.php          # Main website page
│   ├── register.php       # Registration page
│   ├── setup.php          # Initial setup script
│   └── view_document.php  # Document viewer
├── reg/                   # Legacy registration system
│   ├── admin/            # Legacy admin panel
│   ├── DB/               # Database files
│   ├── uploads/          # Legacy uploads
│   └── ...               # Other registration files
└── index.php             # Landing page
```

## Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4+
- PDO PHP Extension
- PHP Extensions: json, mbstring, zip, gd, xml, curl

## Installation

1. Clone the repository to your web server directory
2. Create a MySQL database named `cmrit_db`
3. Create a MySQL user `cmrit_user` with password `test` and grant privileges:
   ```sql
   CREATE DATABASE cmrit_db;
   CREATE USER 'cmrit_user'@'localhost' IDENTIFIED BY 'test';
   GRANT ALL PRIVILEGES ON cmrit_db.* TO 'cmrit_user'@'localhost';
   FLUSH PRIVILEGES;
   ```
4. Import the database schema from `site/cmrit_db.sql`
5. Configure database connection in `site/includes/config.php`
6. Run the setup script by visiting `http://your-domain.com/site/setup.php`
7. Delete `setup.php` after successful setup
8. Set up proper permissions for upload directories

## Configuration

1. Database Configuration:
   - Update database credentials in `site/includes/config.php`
   - Default database name: `cmrit_db`
   - Default user: `cmrit_user`

2. Website Content:
   - Edit JSON files in `site/data/` to update website content
   - Modify `site/css/style.css` for styling changes
   - Update configuration in `site/includes/config.php`

3. File Upload Settings:
   - Maximum file size: 10MB
   - Allowed file types: Images and PDFs
   - Documents stored in database as BLOB

## Usage

### Website Navigation
1. Access the main page at `index.php`
2. Browse through school information, programs, and facilities
3. View announcements and updates

### Student Registration
1. Click on "ADMISSIONS OPEN" to start registration
2. Choose between aided or unaided registration
3. Fill in student details and upload required documents:
   - Aadhar Card
   - Passport-size Photo
   - 10th Marksheet
   - Leaving Certificate
4. Complete fee payment
5. Download receipt

### Admin Panel
1. Access admin panel at `site/admin/`
2. View and manage student registrations
3. Approve/reject applications
4. Generate reports
5. Manage fee payments
6. View uploaded documents

## Security Features

- Secure file upload handling
- Input validation and sanitization
- Protected admin area
- Secure database connections
- XSS protection
- Document access control
- Database-backed document storage

## Support

For technical support or queries, please contact:
- Email: office.nest@despune.org
- Phone: 020 67657014

## License

This project is proprietary software owned by Bharat English School & Jr College.

## Credits

- Developed for Bharat English School & Jr College
- Address: 19/2, TP scheme, Shivajinagar, Pune, Maharashtra 411005
- Font Awesome - Icons
- Google Fonts - Typography
- Tailwind CSS - Styling framework 