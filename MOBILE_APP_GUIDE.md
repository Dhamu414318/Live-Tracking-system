# 📱 Mobile App Implementation Guide

## 🎯 **Simple Mobile App with ON/OFF Toggle**

This guide shows how to create a **simple mobile app** with just an **ON/OFF toggle button** that sends all tracking data to **one single API endpoint**.

---

## 🚀 **Single API Endpoint**

### **Endpoint:**
```
POST /api/mobile/track
```

### **Base URL:**
```
http://your-domain.com/api/mobile/track
```

---

## 📋 **API Request Format**

### **When Tracking is ON:**
```json
{
    "device_id": "MOBILE_123456",
    "api_key": "mobile_key_abc123",
    "is_tracking": true,
    "latitude": 40.7128,
    "longitude": -74.0060,
    "speed": 25.5,
    "altitude": 100,
    "course": 180,
    "ignition": true,
    "battery_level": 85,
    "timestamp": "2025-06-28 10:30:00",
    "user_email": "user@example.com"
}
```

### **When Tracking is OFF:**
```json
{
    "device_id": "MOBILE_123456",
    "api_key": "mobile_key_abc123",
    "is_tracking": false,
    "user_email": "user@example.com"
}
```

---

## 🔧 **Backend Automatic Processing**

When you send data to this single API, the backend automatically:

### ✅ **1. Device Management**
- Creates device if it doesn't exist
- Creates user if it doesn't exist
- Updates device status (online/offline)

### ✅ **2. Position Tracking**
- Stores GPS coordinates in `positions` table
- Calculates distance between points
- Updates device last position

### ✅ **3. Trip Detection**
- Automatically starts trips when device moves (>5 km/h)
- Automatically ends trips when device stops (>5 minutes)
- Calculates trip statistics (distance, speed, duration)

### ✅ **4. Alert Generation**
- Speed limit alerts (>80 km/h)
- Ignition on/off alerts
- Battery low alerts (<20%)
- Geofence enter/exit alerts

### ✅ **5. Geofencing**
- Checks if device enters/exits geofences
- Creates automatic alerts

---

## 📱 **Mobile App Implementation**

### **Flutter Example:**

```dart
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class TrackingApp extends StatefulWidget {
  @override
  _TrackingAppState createState() => _TrackingAppState();
}

class _TrackingAppState extends State<TrackingApp> {
  bool isTracking = false;
  String deviceId = 'MOBILE_${DateTime.now().millisecondsSinceEpoch}';
  String apiKey = 'mobile_key_${DateTime.now().millisecondsSinceEpoch}';
  String userEmail = 'mobileuser@example.com';
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Simple Tracker'),
        backgroundColor: isTracking ? Colors.green : Colors.grey,
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              isTracking ? Icons.location_on : Icons.location_off,
              size: 100,
              color: isTracking ? Colors.green : Colors.grey,
            ),
            SizedBox(height: 20),
            Text(
              isTracking ? 'TRACKING ON' : 'TRACKING OFF',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: isTracking ? Colors.green : Colors.grey,
              ),
            ),
            SizedBox(height: 40),
            Switch(
              value: isTracking,
              onChanged: (value) {
                setState(() {
                  isTracking = value;
                });
                if (value) {
                  startTracking();
                } else {
                  stopTracking();
                }
              },
              activeColor: Colors.green,
              activeTrackColor: Colors.green.withOpacity(0.3),
            ),
            SizedBox(height: 20),
            Text(
              'Tap to toggle tracking',
              style: TextStyle(color: Colors.grey),
            ),
          ],
        ),
      ),
    );
  }

  void startTracking() async {
    // Request location permissions
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    
    if (permission == LocationPermission.whileInUse || 
        permission == LocationPermission.always) {
      // Start periodic location updates
      Geolocator.getPositionStream(
        locationSettings: LocationSettings(
          accuracy: LocationAccuracy.high,
          distanceFilter: 10, // Update every 10 meters
        ),
      ).listen((Position position) {
        sendTrackingData(true, position);
      });
    }
  }

  void stopTracking() {
    sendTrackingData(false, null);
  }

  void sendTrackingData(bool tracking, Position? position) async {
    try {
      Map<String, dynamic> data = {
        'device_id': deviceId,
        'api_key': apiKey,
        'is_tracking': tracking,
        'user_email': userEmail,
      };

      if (tracking && position != null) {
        data.addAll({
          'latitude': position.latitude,
          'longitude': position.longitude,
          'speed': position.speed,
          'altitude': position.altitude,
          'course': position.heading,
          'ignition': true, // You can detect this from vehicle
          'battery_level': 85, // Get from device battery
          'timestamp': DateTime.now().toIso8601String(),
        });
      }

      final response = await http.post(
        Uri.parse('http://your-domain.com/api/mobile/track'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode(data),
      );

      if (response.statusCode == 200) {
        print('Tracking data sent successfully');
      } else {
        print('Error sending tracking data: ${response.body}');
      }
    } catch (e) {
      print('Error: $e');
    }
  }
}
```

### **React Native Example:**

```javascript
import React, { useState, useEffect } from 'react';
import { View, Text, Switch, StyleSheet, Alert } from 'react-native';
import Geolocation from '@react-native-community/geolocation';

const TrackingApp = () => {
  const [isTracking, setIsTracking] = useState(false);
  const [deviceId] = useState(`MOBILE_${Date.now()}`);
  const [apiKey] = useState(`mobile_key_${Date.now()}`);
  const [userEmail] = useState('mobileuser@example.com');

  const startTracking = () => {
    Geolocation.getCurrentPosition(
      (position) => {
        sendTrackingData(true, position);
      },
      (error) => Alert.alert('Error', error.message),
      { enableHighAccuracy: true, timeout: 20000, maximumAge: 1000 }
    );

    // Start watching position
    Geolocation.watchPosition(
      (position) => {
        sendTrackingData(true, position);
      },
      (error) => Alert.alert('Error', error.message),
      { enableHighAccuracy: true, distanceFilter: 10 }
    );
  };

  const stopTracking = () => {
    sendTrackingData(false, null);
  };

  const sendTrackingData = async (tracking, position) => {
    try {
      const data = {
        device_id: deviceId,
        api_key: apiKey,
        is_tracking: tracking,
        user_email: userEmail,
      };

      if (tracking && position) {
        data.latitude = position.coords.latitude;
        data.longitude = position.coords.longitude;
        data.speed = position.coords.speed;
        data.altitude = position.coords.altitude;
        data.course = position.coords.heading;
        data.ignition = true;
        data.battery_level = 85;
        data.timestamp = new Date().toISOString();
      }

      const response = await fetch('http://your-domain.com/api/mobile/track', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(data),
      });

      const result = await response.json();
      console.log('Tracking response:', result);
    } catch (error) {
      console.error('Error sending tracking data:', error);
    }
  };

  const toggleTracking = (value) => {
    setIsTracking(value);
    if (value) {
      startTracking();
    } else {
      stopTracking();
    }
  };

  return (
    <View style={styles.container}>
      <Text style={[styles.status, { color: isTracking ? '#4CAF50' : '#9E9E9E' }]}>
        {isTracking ? 'TRACKING ON' : 'TRACKING OFF'}
      </Text>
      <Switch
        value={isTracking}
        onValueChange={toggleTracking}
        trackColor={{ false: '#767577', true: '#4CAF50' }}
        thumbColor={isTracking ? '#fff' : '#f4f3f4'}
      />
      <Text style={styles.instruction}>Tap to toggle tracking</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#fff',
  },
  status: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 40,
  },
  instruction: {
    marginTop: 20,
    color: '#9E9E9E',
  },
});

export default TrackingApp;
```

---

## 🧪 **Testing the API**

Run the test script to see how it works:

```bash
php test_mobile_api.php
```

This will:
1. Start tracking (ON)
2. Send movement data
3. Test speed limit alerts
4. Stop tracking (OFF)

---

## 📊 **What Happens in Database**

When you send data to the mobile API, it automatically fills these tables:

### **1. `users` table**
- Creates user account if email provided

### **2. `devices` table**
- Creates device with unique ID and API key
- Updates device status and last position

### **3. `positions` table**
- Stores GPS coordinates
- Calculates distance between points

### **4. `trips` table**
- Automatically creates trips when device moves
- Calculates trip statistics

### **5. `alerts` table**
- Creates alerts for speed, ignition, battery, geofence

### **6. `geofences` table**
- Can be created via web dashboard
- Mobile app automatically checks geofence entry/exit

---

## 🎯 **Mobile App Features**

### **Simple Features:**
- ✅ ON/OFF toggle button
- ✅ GPS location tracking
- ✅ Background location updates
- ✅ Automatic data sending
- ✅ Network error handling

### **Advanced Features (Automatic):**
- ✅ Trip detection
- ✅ Speed limit alerts
- ✅ Battery monitoring
- ✅ Geofence checking
- ✅ Ignition status
- ✅ Distance calculation

---

## 🔧 **Configuration**

### **Required Permissions:**
```xml
<!-- Android -->
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
<uses-permission android:name="android.permission.INTERNET" />

<!-- iOS -->
<key>NSLocationWhenInUseUsageDescription</key>
<string>This app needs location access to track your position</string>
```

### **API Configuration:**
- Update base URL in mobile app
- Set device ID and API key
- Configure user email

---

## 🚀 **Deployment**

1. **Backend**: Deploy Laravel app to server
2. **Mobile App**: Build and publish to app stores
3. **Web Dashboard**: Access via browser
4. **Real-time Updates**: WebSocket for live tracking

---

## 📱 **Result**

With this simple implementation:
- **Mobile App**: Just one toggle button
- **Backend**: Handles everything automatically
- **Web Dashboard**: Shows all data in real-time
- **Database**: All tables filled automatically

The mobile app becomes extremely simple while the backend does all the complex work! 