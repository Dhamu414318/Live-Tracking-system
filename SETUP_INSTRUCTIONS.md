# GPS Tracking System - Setup Instructions

## 🚀 Quick Start Guide

### Prerequisites
- PHP 8.1+
- MySQL/MariaDB
- Composer
- Web server (Apache/Nginx) or PHP built-in server

### Step 1: Database Setup
1. Create a MySQL database:
```sql
CREATE DATABASE gps_tracking;
```

2. Update your `.env` file with database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gps_tracking
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Step 2: Install Dependencies
```bash
composer install
```

### Step 3: Run Migrations and Seed Data
```bash
php artisan migrate
php artisan db:seed
```

### Step 4: Start the Application
```bash
# Start web server
php artisan serve

# In another terminal, start WebSocket server
php artisan websocket:serve --port=8080
```

### Step 5: Access the Application
- Web Interface: http://localhost:8000
- Login: admin@example.com / password

## 📋 System Features

### ✅ Implemented Features
- **User Management**: Admin and regular user roles
- **Device Management**: CRUD operations for GPS devices
- **Real-time Tracking**: WebSocket-based live updates
- **Geofencing**: Circular and polygon geofences
- **Alerts System**: Speed, ignition, battery, geofence alerts
- **Trip Detection**: Automatic trip start/end detection
- **Reports**: Trip reports, alert summaries, usage analytics
- **Admin Panel**: User management, system monitoring
- **API Endpoints**: RESTful API for GPS position updates

### 🔧 Technical Implementation
- **Backend**: Laravel 10 with Eloquent ORM
- **Database**: MySQL with optimized schema
- **Real-time**: Ratchet WebSocket server
- **Authentication**: Laravel Sanctum
- **Maps**: Leaflet.js (easily switchable to Google Maps)
- **Frontend**: Blade templates with Bootstrap

## 📊 Database Schema

### Core Tables
- **users**: User accounts with role-based access
- **devices**: GPS tracker information
- **positions**: GPS tracking history
- **geofences**: Geographic boundaries
- **alerts**: System notifications
- **trips**: Journey tracking data

### Relationships
- Users have many devices
- Devices have many positions, trips, alerts
- Users have many geofences
- Devices can be assigned to multiple geofences

## 🔌 API Documentation

### GPS Position Updates
```bash
POST /api/positions
Content-Type: application/json

{
    "unique_id": "DEV_ABC123",
    "latitude": 40.7128,
    "longitude": -74.0060,
    "speed": 65.5,
    "altitude": 100,
    "course": 180,
    "ignition": true,
    "battery_level": 85.2,
    "timestamp": "2024-01-01T12:00:00Z",
    "additional_data": {
        "engine_rpm": 2500,
        "fuel_level": 75
    }
}
```

### Bulk Position Updates
```bash
POST /api/positions/bulk
Content-Type: application/json

{
    "positions": [
        {
            "unique_id": "DEV_ABC123",
            "latitude": 40.7128,
            "longitude": -74.0060,
            "speed": 65.5
        }
    ]
}
```

### WebSocket Connection
```javascript
const ws = new WebSocket('ws://localhost:8080');

// Authenticate
ws.send(JSON.stringify({
    type: 'auth',
    user_id: 1
}));

// Subscribe to device updates
ws.send(JSON.stringify({
    type: 'subscribe_device',
    device_id: 1
}));

// Listen for updates
ws.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log('Received:', data);
};
```

## 🗺️ Geofencing

### Circle Geofence
```json
{
    "area_type": "circle",
    "coordinates": {
        "center": {
            "lat": 40.7128,
            "lng": -74.0060
        },
        "radius": 500
    }
}
```

### Polygon Geofence
```json
{
    "area_type": "polygon",
    "coordinates": {
        "polygon": [
            {"lat": 40.7128, "lng": -74.0060},
            {"lat": 40.7228, "lng": -74.0060},
            {"lat": 40.7228, "lng": -73.9960},
            {"lat": 40.7128, "lng": -73.9960}
        ]
    }
}
```

## 🔔 Alert Types

- **speed_limit_exceeded**: When device exceeds speed limit
- **ignition_on**: When vehicle ignition is turned on
- **ignition_off**: When vehicle ignition is turned off
- **geofence_enter**: When device enters a geofence
- **geofence_exit**: When device exits a geofence
- **device_offline**: When device goes offline
- **battery_low**: When device battery is low
- **maintenance_due**: Maintenance reminder

## 📈 Reports

### Available Reports
- **Trip Reports**: Distance, duration, speed analysis
- **Alert Summaries**: Alert type breakdown and statistics
- **Speed Analysis**: Speed distribution and trends
- **Usage Reports**: Daily and device usage statistics
- **Export Functionality**: CSV export for all reports

## 🛠️ Configuration

### Environment Variables
```env
# GPS Settings
GPS_SPEED_LIMIT=80
GPS_BATTERY_THRESHOLD=20
GPS_OFFLINE_THRESHOLD=5
GPS_TRIP_DETECTION_SPEED=5
GPS_TRIP_END_DELAY=5

# WebSocket Settings
WEBSOCKET_PORT=8080
```

### Customization Options
- **Maps Provider**: Switch between Leaflet.js and Google Maps
- **Alert Thresholds**: Configure speed limits and battery thresholds
- **Trip Detection**: Adjust speed and delay parameters
- **Geofencing**: Custom geofence types and calculations

## 🚀 Deployment

### Production Setup
1. Set `APP_ENV=production` in `.env`
2. Configure production database
3. Set up SSL certificates
4. Configure web server (Apache/Nginx)
5. Set up WebSocket server as a service
6. Configure queue workers for background jobs

### WebSocket Server Service
```ini
[Unit]
Description=GPS Tracking WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/your/app
ExecStart=/usr/bin/php artisan websocket:serve --port=8080
Restart=always

[Install]
WantedBy=multi-user.target
```

## 🧪 Testing

### Run Setup Check
```bash
php setup.php
```

### Test API
```bash
php test_api.php
```

### Run Tests
```bash
php artisan test
```

## 📱 Mobile Integration

### GPS Device Configuration
Configure your GPS device to send data to:
```
POST http://your-domain.com/api/positions
```

### Mobile App Integration
Use the WebSocket API for real-time updates in mobile applications.

## 🔒 Security

### Authentication
- Laravel Sanctum for API authentication
- Session-based authentication for web interface
- Role-based access control (Admin/User)

### Data Protection
- Input validation and sanitization
- SQL injection prevention via Eloquent ORM
- XSS protection via Blade templating
- CSRF protection for web forms

## 📞 Support

### Troubleshooting
1. Check the setup script: `php setup.php`
2. Verify database connection
3. Check WebSocket server status
4. Review Laravel logs: `storage/logs/laravel.log`

### Common Issues
- **Database connection failed**: Check database credentials in `.env`
- **WebSocket not working**: Ensure port 8080 is available
- **Migrations failed**: Check database permissions
- **Seeding failed**: Verify database schema

## 🎯 Next Steps

### Immediate Actions
1. Set up your database
2. Run migrations and seeders
3. Start the application
4. Test with sample data
5. Configure your GPS devices

### Future Enhancements
- Mobile app development
- MQTT support for hardware devices
- Advanced analytics and machine learning
- Multi-language support
- Push notifications
- Fleet management features

---

**Happy Tracking! 🚗📱**

For more information, check the main README.md file. 