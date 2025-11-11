# BizMi CRM
**Modern PHP-based Customer Relationship Management System**

Created by: **Amrullah Khan**  
Contact: amrulzlionheart@gmail.com  
Version: 1.0.0  
Date: November 11, 2025  

## Overview

BizMi CRM is a comprehensive, modern Customer Relationship Management system built with PHP and MySQL. Designed for easy deployment on any web hosting service with cPanel support, it provides businesses with powerful tools to manage their customer relationships, sales pipeline, and business operations.

## Key Features

### 🎯 Core CRM Features
- **Contact & Lead Management** - Centralized customer database
- **Sales Pipeline** - Deal tracking and opportunity management  
- **Task & Activity Management** - Stay organized and productive
- **Dashboard Analytics** - Real-time business insights
- **Document Management** - Store and share files securely

### 🚀 Sales Automation
- Lead scoring and qualification
- Pipeline stages and forecasting
- Quote and proposal generation
- Follow-up reminders and automation
- Sales performance tracking

### 📊 Business Intelligence
- Customizable dashboards
- Advanced reporting and analytics
- Sales forecasting
- Performance metrics
- Export capabilities

### 🔧 Technical Features
- **Easy Installation** - Web-based setup wizard
- **Responsive Design** - Works on all devices
- **Secure Architecture** - Role-based permissions
- **API Integration** - Extensible and customizable
- **Multi-user Support** - Team collaboration

## System Requirements

### Server Requirements
- **Web Server:** Apache 2.4+ or Nginx
- **PHP:** 8.0 or higher
- **Database:** MySQL 5.7+ or MariaDB 10.2+
- **Memory:** 256MB minimum (512MB recommended)
- **Storage:** 100MB minimum (excludes user data)

### PHP Extensions Required
- php-mysql
- php-json
- php-curl
- php-gd
- php-zip
- php-xml
- php-mbstring

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Installation

1. **Download** the latest release
2. **Extract** files to your web directory
3. **Create** a MySQL database
4. **Navigate** to your domain in a web browser
5. **Follow** the installation wizard

Detailed installation guide available in `/docs/installation.md`

## Directory Structure

```
bizmi-crm/
├── app/                    # Application core
│   ├── Controllers/        # MVC Controllers
│   ├── Models/            # Data models
│   ├── Views/             # Template files
│   └── Services/          # Business logic
├── config/                # Configuration files
├── public/                # Web accessible files
│   ├── assets/           # CSS, JS, images
│   └── index.php         # Application entry point
├── database/              # Database migrations & seeds
├── docs/                  # Documentation
├── install/               # Installation wizard
└── vendor/                # Third-party libraries
```

## Quick Start

After installation, log in with your admin credentials and:

1. **Setup Company Profile** - Configure your business information
2. **Add Users** - Invite your team members
3. **Import Contacts** - Upload your customer database
4. **Configure Pipeline** - Set up your sales stages
5. **Start Managing** - Begin tracking deals and activities

## Security

- Password encryption using modern hashing
- SQL injection prevention
- XSS protection
- CSRF token validation
- Role-based access control
- Session security

## License

Copyright (c) 2025 Amrullah Khan. All rights reserved.

This software is proprietary and confidential. Unauthorized copying, modification, distribution, or use is strictly prohibited without explicit written permission from the copyright holder.

## Support

For support, feature requests, or bug reports:
- Email: amrulzlionheart@gmail.com
- Documentation: `/docs/`

## Contributing

This is a proprietary project. For collaboration inquiries, please contact the owner.

---

**Built with ❤️ by Amrullah Khan**