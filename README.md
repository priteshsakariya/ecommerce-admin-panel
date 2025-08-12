# E-commerce Admin Panel

A comprehensive PHP-based admin panel for managing e-commerce operations with role-based authentication, product management, order tracking, and inventory control.

## Features

### Core Functionality
- **Role-based Authentication** - Admin and Editor roles with different access levels
- **Product Management** - Complete CRUD operations for products with variants and image galleries
- **Category Management** - Organize products into categories with descriptions and images
- **Order Management** - Track orders from pending to delivered with status updates
- **Inventory Dashboard** - Real-time stock monitoring with low-stock alerts
- **User Management** - Admin can manage user accounts and roles
- **Settings Panel** - Configure store settings, coupons, and system preferences

### Security Features
- CSRF token protection on all forms
- Password hashing with PHP's password_hash()
- SQL injection prevention with prepared statements
- Session management with timeout protection
- Role-based access control

### Technical Stack
- **Backend:** PHP 7.4+ with PDO
- **Database:** MySQL 5.7+
- **Frontend:** AdminLTE 3.2 + Tailwind CSS
- **JavaScript:** jQuery for dynamic interactions
- **Icons:** Font Awesome 6

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- mod_rewrite enabled (for Apache)

### Setup Instructions

1. **Clone/Download the project files**
   ```bash
   # Download and extract to your web server directory
   # e.g., /var/www/html/admin or C:\xampp\htdocs\admin
   ```

2. **Create Database**
   ```bash
   # Create a new MySQL database
   mysql -u root -p
   CREATE DATABASE ecommerce_admin;
   exit
   ```

3. **Import Database Schema**
   ```bash
   # Import the schema file
   mysql -u root -p ecommerce_admin < database/schema.sql
   ```

4. **Configure Database Connection**
   
   Edit `config/database.php` and update the connection settings:
   ```php
   private $host = 'localhost';        // Your database host
   private $dbname = 'ecommerce_admin'; // Your database name
   private $username = 'root';          // Your database username
   private $password = '';              // Your database password
   ```

5. **Set Directory Permissions**
   ```bash
   # Make uploads directory writable
   chmod 755 uploads/
   chmod 755 uploads/products/
   chmod 755 uploads/categories/
   ```

6. **Access the Application**
   
   Open your browser and navigate to: `http://localhost/your-project-folder/login.php`
   
   **Default Login Credentials:**
   - Username: `admin`
   - Password: `admin123`

## Project Structure

```
admin-panel/
├── config/
│   ├── app.php           # Application configuration & helper functions
│   └── database.php      # Database connection class
├── classes/
│   ├── Auth.php          # Authentication management
│   ├── Product.php       # Product CRUD operations
│   ├── Category.php      # Category management
│   ├── Order.php         # Order processing
│   └── User.php          # User management
├── includes/
│   ├── header.php        # Common header with navigation
│   └── footer.php        # Common footer with scripts
├── database/
│   └── schema.sql        # Database structure and sample data
├── uploads/              # File upload directory
│   ├── products/         # Product images
│   └── categories/       # Category images
├── ajax/                 # AJAX endpoints
│   ├── update_status.php # Status update handler
│   └── update_stock.php  # Stock update handler
├── login.php             # Login page
├── logout.php            # Logout handler
├── dashboard.php         # Main dashboard
├── products.php          # Product management
├── categories.php        # Category management
├── orders.php            # Order management
├── inventory.php         # Inventory dashboard
├── users.php             # User management (Admin only)
├── settings.php          # System settings (Admin only)
└── README.md             # This documentation
```

## Usage Guide

### User Roles

**Administrator**
- Full access to all features
- Can manage users and system settings
- Can perform all CRUD operations

**Editor**
- Access to products, categories, orders, and inventory
- Cannot manage users or system settings
- Can modify product information and stock levels

### Managing Products

1. **Add New Product**
   - Navigate to Products → Add Product
   - Fill in required fields (Name, Base Price)
   - Optionally assign to a category
   - Add source link for reference

2. **Product Variants**
   - Edit an existing product
   - Use the variants section to add size, color, or other variations
   - Each variant has its own SKU and stock quantity

3. **Inventory Management**
   - Use the Inventory dashboard to monitor stock levels
   - Get alerts when stock falls below threshold
   - Update stock quantities directly from product variants

### Order Processing

1. **Order Status Flow**
   ```
   Pending → Processing → Shipped → Delivered
                ↓
            Cancelled (any time)
   ```

2. **Managing Orders**
   - View all orders with filtering options
   - Update order status and add notes
   - Delete orders (will restore variant stock)

### Category Organization

- Create categories to organize products
- Add descriptions and optional images
- Categories can be activated/deactivated
- Cannot delete categories with existing products

## Customization

### Adding New Fields

1. **Database Schema**
   ```sql
   ALTER TABLE products ADD COLUMN new_field VARCHAR(255);
   ```

2. **Update Classes**
   ```php
   // In classes/Product.php
   // Add field to createProduct() and updateProduct() methods
   ```

3. **Update Forms**
   ```php
   // In products.php
   // Add form input for new field
   ```

### Custom Styling

The application uses a combination of AdminLTE and Tailwind CSS:

- **AdminLTE**: Layout, components, and base styling
- **Tailwind CSS**: Utility classes for custom styling
- **Custom CSS**: Located in `includes/header.php`

### Adding New User Roles

1. **Database Update**
   ```sql
   ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'editor', 'new_role');
   ```

2. **Update Permission Checks**
   ```php
   // In config/app.php
   function hasRole($roles) {
       return in_array($_SESSION['user_role'], (array)$roles);
   }
   ```

## Security Considerations

### Best Practices Implemented
- All user inputs are sanitized and validated
- SQL queries use prepared statements
- CSRF tokens protect against cross-site request forgery
- Passwords are hashed using PHP's password_hash()
- Session timeouts prevent unauthorized access
- File upload restrictions prevent malicious uploads

### Additional Security Recommendations
- Use HTTPS in production
- Keep PHP and MySQL updated
- Regular database backups
- Implement rate limiting for login attempts
- Use strong passwords for database connections

## Database Schema

### Core Tables

**users** - Admin user accounts
- Role-based authentication
- Password hashing
- Activity tracking

**products** - Product catalog
- Basic product information
- Category relationships
- SEO-friendly slugs

**product_variants** - Product variations
- SKU management
- Individual stock tracking
- Price adjustments

**categories** - Product organization
- Hierarchical structure ready
- SEO optimization

**orders** - Order management
- Customer information
- Status tracking
- Order history

**order_items** - Order details
- Product snapshots
- Quantity and pricing

### Settings & Configuration

**settings** - System configuration
- Store information
- Default values
- Feature toggles

**coupons** - Discount management
- Percentage or fixed discounts
- Usage limits
- Expiration dates

## Troubleshooting

### Common Issues

**Database Connection Failed**
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists and is accessible

**Permission Denied Errors**
- Check file permissions on uploads directory
- Ensure web server has write access
- Verify PHP file permissions

**Session Issues**
- Check PHP session configuration
- Ensure session directory is writable
- Clear browser cookies if needed

**Login Problems**
- Verify default credentials: admin/admin123
- Check if user account is active
- Reset password in database if needed

### Debug Mode

To enable debug mode, add this to `config/app.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## Contributing

### Code Standards
- Follow PSR-4 autoloading standards
- Use meaningful variable and function names
- Add comments for complex logic
- Validate all inputs
- Use prepared statements for database queries

### Testing
- Test all CRUD operations
- Verify role-based access controls
- Check form validation
- Test with different user roles

## License

This project is released under the MIT License. See LICENSE file for details.

## Support

For issues, questions, or contributions:
1. Check the troubleshooting section
2. Review the database schema
3. Examine the code comments
4. Test with default data

## Changelog

### Version 1.0.0
- Initial release
- Complete admin panel functionality
- Role-based authentication
- Product and order management
- Inventory tracking
- Responsive design