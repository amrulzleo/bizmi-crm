# BizMi CRM - Technical Architecture

## System Overview

BizMi CRM follows a modern MVC (Model-View-Controller) architecture pattern built with PHP 8+, designed for scalability, maintainability, and ease of deployment on standard web hosting environments.

## Architecture Layers

### 1. Presentation Layer (Views)
- **Responsive Web Interface** - Bootstrap-based responsive design
- **Template Engine** - Custom PHP template system with inheritance
- **JavaScript Components** - Modern ES6+ frontend interactions
- **AJAX Communication** - Asynchronous data operations

### 2. Application Layer (Controllers)
- **Route Handler** - URL routing and request dispatch
- **Business Logic** - Core CRM functionality
- **API Endpoints** - RESTful API for integrations
- **Authentication** - User session and security management

### 3. Data Layer (Models)
- **ORM-like Pattern** - Object-relational mapping
- **Database Abstraction** - MySQL with prepared statements
- **Data Validation** - Input sanitization and validation
- **Migration System** - Database version control

### 4. Core Services
- **Security Service** - Authentication, authorization, encryption
- **Email Service** - SMTP integration for notifications
- **File Service** - Document upload and management
- **Cache Service** - Performance optimization
- **Log Service** - Error tracking and debugging

## Database Design

### Core Entity Relationships

```sql
Users (1:N) -> Activities
Users (1:N) -> Contacts (1:N) -> Deals
Contacts (1:N) -> Organizations
Deals (N:1) -> Pipeline_Stages
Activities (N:1) -> Activity_Types
```

### Key Tables
- **users** - System users and authentication
- **contacts** - Individual contacts/people
- **organizations** - Companies and businesses
- **deals** - Sales opportunities
- **activities** - Tasks, calls, meetings, emails
- **pipeline_stages** - Sales process stages
- **products** - Product/service catalog
- **quotes** - Proposals and quotations
- **documents** - File attachments

## Security Architecture

### Authentication
- **Session-based Auth** - PHP sessions with secure tokens
- **Password Security** - bcrypt hashing with salt
- **Two-Factor Option** - Email-based 2FA (future enhancement)

### Authorization
- **Role-Based Access Control (RBAC)**
  - Super Admin - Full system access
  - Admin - Organization management
  - Manager - Team and pipeline management
  - User - Basic CRM operations
  - View Only - Read-only access

### Data Protection
- **SQL Injection Prevention** - Prepared statements
- **XSS Protection** - Input sanitization and output encoding
- **CSRF Protection** - Token-based request validation
- **File Upload Security** - Type and size validation

## Module Architecture

### 1. Contact Management Module
```
ContactController -> ContactService -> ContactModel
├── Create/Edit contacts
├── Search and filtering
├── Import/Export functionality
├── Activity timeline
└── Relationship mapping
```

### 2. Sales Pipeline Module
```
DealController -> DealService -> DealModel
├── Deal creation and tracking
├── Stage progression
├── Forecasting calculations
├── Performance metrics
└── Quote generation
```

### 3. Activity Management Module
```
ActivityController -> ActivityService -> ActivityModel
├── Task scheduling
├── Calendar integration
├── Email tracking
├── Call logging
└── Meeting notes
```

## API Architecture

### RESTful Design
- **GET** `/api/contacts` - Retrieve contacts
- **POST** `/api/contacts` - Create new contact
- **PUT** `/api/contacts/{id}` - Update contact
- **DELETE** `/api/contacts/{id}` - Delete contact

### Response Format
```json
{
    "success": true,
    "data": {},
    "message": "Operation successful",
    "errors": [],
    "meta": {
        "total": 100,
        "page": 1,
        "per_page": 20
    }
}
```

## Performance Considerations

### Database Optimization
- **Indexing Strategy** - Primary keys, foreign keys, search fields
- **Query Optimization** - Efficient JOIN operations
- **Connection Pooling** - Reuse database connections
- **Lazy Loading** - Load related data only when needed

### Caching Strategy
- **File-based Cache** - Store frequently accessed data
- **Query Result Cache** - Cache expensive database queries
- **Template Cache** - Compiled template caching
- **Browser Cache** - Static asset caching headers

### Frontend Performance
- **Asset Minification** - Compressed CSS/JS files
- **Image Optimization** - Compressed and responsive images
- **Lazy Loading** - Progressive content loading
- **CDN Ready** - External asset delivery support

## Deployment Architecture

### File Structure for Hosting
```
public_html/
├── index.php          # Application entry point
├── assets/            # CSS, JS, images
├── uploads/           # User uploaded files
└── .htaccess          # Apache rewrite rules

private/               # Above web root (secure)
├── app/               # Application code
├── config/            # Configuration files
├── logs/              # Application logs
└── vendor/            # Dependencies
```

### Environment Configuration
- **Development** - Debug mode, detailed logging
- **Production** - Optimized performance, error handling
- **Staging** - Testing environment with production data

## Scalability Design

### Horizontal Scaling
- **Stateless Design** - No server-side state dependency
- **Database Separation** - Read/write split capability
- **File Storage** - External storage integration ready
- **Load Balancer Ready** - Session data externalization

### Vertical Scaling
- **Memory Optimization** - Efficient memory usage
- **Process Optimization** - Minimal server resources
- **Cache Utilization** - Reduce database load
- **Code Optimization** - Fast execution paths

## Maintenance & Monitoring

### Logging Strategy
- **Error Logs** - Application errors and exceptions
- **Access Logs** - User activity tracking
- **Performance Logs** - Slow query detection
- **Security Logs** - Authentication attempts

### Backup Strategy
- **Database Backups** - Automated daily backups
- **File Backups** - User uploads and documents
- **Configuration Backups** - System settings
- **Version Control** - Code repository integration

---

This architecture ensures BizMi CRM is robust, secure, scalable, and easy to deploy on standard web hosting services while providing enterprise-grade functionality.