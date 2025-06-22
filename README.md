# GPS Tracking System

A full-featured GPS tracking system built with Laravel, replicating the Traccar platform with modern web interface and real-time tracking capabilities.

## 🚀 Features

### Core Features
- **Real-time GPS Tracking** - Live position updates with WebSocket support
- **Device Management** - Add, edit, and manage GPS devices
- **Geofencing** - Create geographic boundaries with polygon and circle support
- **Alerts & Notifications** - Speed limits, geofence entry/exit, device offline alerts
- **Trip Analysis** - Automatic trip detection and analysis
- **Reports & Analytics** - Comprehensive reporting with charts and statistics
- **User Management** - Role-based access control (Admin/User)
- **API Support** - RESTful API for device communication
- **Responsive UI** - Modern, mobile-friendly interface

### Advanced Features
- **Real-time Maps** - Interactive maps with device locations
- **Historical Data** - Track position history and playback
- **Speed Analysis** - Speed distribution and violation tracking
- **Distance Calculation** - Accurate distance and route tracking
- **Export Capabilities** - Export reports and data
- **Settings Management** - User preferences and system settings

## 📋 Requirements

- PHP 8.1 or higher
- Laravel 10.x
- MySQL 5.7+ or PostgreSQL
- Composer
- Node.js (for frontend assets)

## 🛠 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd my-tracking-backend
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   Edit `.env` file with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=gps_tracking
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Run migrations and seeders**
   ```bash
   php artisan migrate:fresh --seed
   ```

7. **Start the development server**
   ```bash
   php artisan serve
   ```

8. **Access the application**
   Visit `http://localhost:8000`

## 👤 Default Login

- **Email:** admin@example.com
- **Password:** password

## 📱 Device Integration

### Supported GPS Protocols
- GT06N
- TK103
- Custom protocols via API

### API Endpoints

#### Position Update
```http
POST /api/position
Content-Type: application/json

{
    "device_id": "DEVICE_UNIQUE_ID",
    "latitude": 40.7128,
    "longitude": -74.0060,
    "speed": 45.5,
    "course": 180,
    "timestamp": "2024-01-01 12:00:00"
}
```

#### Device Positions
```http
GET /api/devices/{device}/positions
Authorization: Bearer {api_key}
```

## 🗺 Geofencing

### Creating Geofences
1. Navigate to Geofences → Add Geofence
2. Choose type: Polygon or Circle
3. Draw on the map or enter coordinates
4. Assign devices and set alerts

### Geofence Types
- **Polygon** - Custom shape with multiple points
- **Circle** - Circular area with center and radius

## 📊 Reports & Analytics

### Available Reports
- **Device Performance** - Distance, time, speed analysis
- **Trip Reports** - Detailed trip information
- **Alert History** - All system alerts
- **Speed Analysis** - Speed distribution charts
- **Geofence Activity** - Entry/exit events

### Export Options
- CSV export for all reports
- PDF generation (planned)
- Real-time data streaming

## 🔧 Configuration

### User Settings
- Speed limit preferences
- Distance units (km/miles)
- Timezone settings
- Alert preferences

### System Settings
- Default geofence colors
- Alert thresholds
- API rate limits
- Map providers

## 🚨 Alerts & Notifications

### Alert Types
- **Geofence Enter** - Device enters defined area
- **Geofence Exit** - Device leaves defined area
- **Speed Limit** - Exceeds speed threshold
- **Device Offline** - No position updates
- **Low Battery** - Battery level warning
- **Maintenance** - Scheduled maintenance alerts

### Alert Configuration
- Email notifications
- SMS alerts (requires SMS provider)
- Webhook notifications
- In-app alerts

## 🗄 Database Schema

### Core Tables
- `users` - User accounts and preferences
- `devices` - GPS device information
- `positions` - GPS position data
- `geofences` - Geographic boundaries
- `alerts` - System alerts and notifications
- `trips` - Trip detection and analysis
- `settings` - System and user settings

## 🔒 Security

### Authentication
- Laravel Sanctum for API authentication
- Session-based web authentication
- Role-based access control

### API Security
- API key authentication for devices
- Rate limiting
- Input validation and sanitization

## 🚀 Deployment

### Production Setup
1. Set up production database
2. Configure environment variables
3. Set up web server (Apache/Nginx)
4. Configure SSL certificates
5. Set up cron jobs for maintenance

### Recommended Server
- **CPU:** 2+ cores
- **RAM:** 4GB+ 
- **Storage:** 50GB+ SSD
- **OS:** Ubuntu 20.04+ or CentOS 8+

## 📈 Performance

### Optimization Tips
- Enable database indexing
- Configure caching (Redis recommended)
- Use CDN for static assets
- Implement database partitioning for large datasets
- Enable compression

### Scaling
- Horizontal scaling with load balancers
- Database read replicas
- Redis clustering for caching
- Queue workers for background jobs

## 🐛 Troubleshooting

### Common Issues

**Login not working**
- Check database connection
- Verify user exists in database
- Clear application cache: `php artisan cache:clear`

**Devices not showing on map**
- Verify device API key
- Check position data exists
- Ensure WebSocket server is running

**Alerts not triggering**
- Verify geofence coordinates
- Check alert settings
- Review device status

### Debug Mode
Enable debug mode in `.env`:
```env
APP_DEBUG=true
APP_ENV=local
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Check the documentation
- Review the troubleshooting guide

## 🔄 Updates

### Version History
- **v1.0.0** - Initial release with core features
- **v1.1.0** - Added real-time WebSocket support
- **v1.2.0** - Enhanced reporting and analytics
- **v1.3.0** - Improved geofencing and alerts

### Upcoming Features
- Mobile app support
- Advanced analytics
- Machine learning insights
- Integration with third-party services

---

**Built with ❤️ using Laravel**
