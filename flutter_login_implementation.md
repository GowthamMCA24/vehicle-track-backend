# Flutter Login Screen Integration

To connect your new Flutter app to the Laravel API we just built, you'll need to do three main things:
1. Make an HTTP POST request to the `/api/login` endpoint.
2. Handle the JSON response.
3. Store the `access_token` securely so you can use it for future requests (like fetching the user).

Here is a complete, step-by-step guide and the code you need to achieve this.

## Step 1: Add Dependencies

In your Flutter project, open your `pubspec.yaml` file and add the `http` package for making network requests and `flutter_secure_storage` for securely saving the API token.

```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.2.0
  flutter_secure_storage: ^9.0.0
```
Run `flutter pub get` in your terminal to install them.

## Step 2: Create an API Service

It's best practice to keep your API logic separate from your UI. Create a new file called `api_service.dart`.

> [!IMPORTANT]
> If you are testing this on an **Android Emulator**, use `10.0.2.2` instead of `127.0.0.1` or `localhost`, because the emulator has its own network. For an **iOS Simulator**, `127.0.0.1` works fine.

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiService {
  // Update this to your actual backend URL. 
  // Use 10.0.2.2 if testing on an Android emulator.
  static const String baseUrl = 'http://10.0.2.2:8000/api';
  final storage = const FlutterSecureStorage();

  Future<bool> login(String phone, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'phone': phone,
          'password': password,
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        // Save the token securely
        await storage.write(key: 'auth_token', value: data['access_token']);
        
        return true;
      } else {
        // You can handle specific errors here (e.g. 401 Unauthorized)
        print('Login failed: ${response.body}');
        return false;
      }
    } catch (e) {
      print('Error during login: $e');
      return false;
    }
  }

  // Example of how to use the saved token later to fetch the user
  Future<void> getCurrentUser() async {
    String? token = await storage.read(key: 'auth_token');
    
    if (token == null) return;

    final response = await http.get(
      Uri.parse('$baseUrl/users'),
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );
    
    print(response.body);
  }
}
```

## Step 3: Create the Login Screen UI

Now, create your `login_screen.dart` file. This screen will have text fields for the phone number and password, and will call the `ApiService` when the login button is pressed.

```dart
import 'package:flutter/material.dart';
import 'api_service.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _apiService = ApiService();
  
  bool _isLoading = false;
  String _errorMessage = '';

  void _handleLogin() async {
    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    final success = await _apiService.login(
      _phoneController.text.trim(),
      _passwordController.text,
    );

    setState(() {
      _isLoading = false;
    });

    if (success) {
      // Navigate to your home screen!
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Login Successful!')),
        );
        // Navigator.pushReplacementNamed(context, '/home');
      }
    } else {
      setState(() {
        _errorMessage = 'Invalid phone number or password';
      });
    }
  }

  @override
  void dispose() {
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Login')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            if (_errorMessage.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(bottom: 16.0),
                child: Text(
                  _errorMessage,
                  style: const TextStyle(color: Colors.red),
                ),
              ),
              
            TextField(
              controller: _phoneController,
              decoration: const InputDecoration(
                labelText: 'Phone Number',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.phone),
              ),
              keyboardType: TextInputType.phone,
            ),
            const SizedBox(height: 16),
            
            TextField(
              controller: _passwordController,
              decoration: const InputDecoration(
                labelText: 'Password',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.lock),
              ),
              obscureText: true,
            ),
            const SizedBox(height: 24),
            
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                onPressed: _isLoading ? null : _handleLogin,
                child: _isLoading
                    ? const CircularProgressIndicator()
                    : const Text('Login', style: TextStyle(fontSize: 18)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
```

## Step 4: Network Permissions (Optional, but important)
If you are testing on an Android device/emulator, ensure your app is allowed to make internet requests. Open `android/app/src/main/AndroidManifest.xml` and add this inside the `<manifest>` tag, above the `<application>` tag:

```xml
<uses-permission android:name="android.permission.INTERNET"/>
```
