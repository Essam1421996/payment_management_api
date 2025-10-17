# Payment Management API

A comprehensive Laravel-based API for managing orders and payments with extensible payment gateway support using the Strategy Pattern.

## Features

- **Order Management**: Complete CRUD operations for orders with business logic validation
- **Payment Processing**: Multi-gateway payment processing with strategy pattern implementation
- **JWT Authentication**: Secure API authentication using JWT tokens
- **Extensible Payment Gateways**: Easy addition of new payment gateways with minimal code changes
- **Comprehensive Testing**: Full test coverage with unit and feature tests
- **RESTful API Design**: Clean, well-documented API endpoints following REST principles
- **Business Rules Enforcement**: Orders can only be deleted without payments, payments only for confirmed orders

## Payment Gateway Extensibility

The system uses the Strategy Pattern to allow easy addition of new payment gateways:

### Current Gateways
- **Credit Card Gateway**: Simulated credit card processing
- **PayPal Gateway**: PayPal payment processing
- **Stripe Gateway**: Stripe payment processing

### Adding a New Payment Gateway

1. Create a new gateway class implementing `PaymentGatewayInterface`:

```php
<?php

namespace App\Services\PaymentGateways;

class NewGateway implements PaymentGatewayInterface
{
    public function processPayment(array $paymentData): array
    {
        // Implementation
    }

    public function getGatewayName(): string
    {
        return 'new_gateway';
    }

    public function isConfigured(): bool
    {
        // Check configuration
    }
}
```

2. Register the gateway in `PaymentService`:

```php
$this->gateways = [
    'credit_card' => new CreditCardGateway(),
    'paypal' => new PayPalGateway(),
    'stripe' => new StripeGateway(),
    'new_gateway' => new NewGateway(), // Add here
];
```

3. Add environment variables for configuration:

```env
NEW_GATEWAY_API_KEY=your_api_key
NEW_GATEWAY_SECRET=your_secret
```

4. Update the payment method enum in the migration if needed.

## Installation

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL/PostgreSQL
- Node.js (for frontend assets)

### Setup Instructions

1. **Clone the repository**
```bash
git clone <repository-url>
cd payment_management_api
```

2. **Install dependencies**
```bash
composer install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

4. **Database configuration**
Update your `.env` file with database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=payment_management
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Run migrations**
```bash
php artisan migrate
```

6. **Configure payment gateways** (optional)
Add gateway credentials to `.env`:
```env
# Credit Card Gateway
CREDIT_CARD_API_KEY=your_api_key
CREDIT_CARD_SECRET=your_secret
CREDIT_CARD_ENDPOINT=https://api.creditcard.com/process

# PayPal Gateway
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_client_secret
PAYPAL_ENDPOINT=https://api.paypal.com/v1/payments

# Stripe Gateway
STRIPE_PUBLISHABLE_KEY=your_publishable_key
STRIPE_SECRET_KEY=your_secret_key
STRIPE_ENDPOINT=https://api.stripe.com/v1/charges
```

7. **Start the development server**
```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api`

## API Documentation

### Authentication Endpoints

#### Register User
```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Login User
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

#### Get User Profile
```http
GET /api/auth/me
Authorization: Bearer {jwt_token}
```

#### Logout
```http
POST /api/auth/logout
Authorization: Bearer {jwt_token}
```

### Order Endpoints

#### Create Order
```http
POST /api/orders
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "customer_name": "Jane Smith",
  "customer_email": "jane@example.com",
  "customer_address": "123 Main St, City, State 12345",
  "items": [
    {
      "product_name": "Laptop",
      "quantity": 1,
      "price": 999.99
    },
    {
      "product_name": "Mouse",
      "quantity": 2,
      "price": 25.50
    }
  ]
}
```

#### Get All Orders
```http
GET /api/orders?status=confirmed&per_page=10
Authorization: Bearer {jwt_token}
```

#### Get Order by ID
```http
GET /api/orders/{id}
Authorization: Bearer {jwt_token}
```

#### Update Order
```http
PUT /api/orders/{id}
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "customer_name": "Updated Name",
  "status": "confirmed"
}
```

#### Delete Order
```http
DELETE /api/orders/{id}
Authorization: Bearer {jwt_token}
```

### Payment Endpoints

#### Process Credit Card Payment
```http
POST /api/payments
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "order_id": 1,
  "payment_method": "credit_card",
  "card_number": "4111111111111111",
  "cvv": "123",
  "expiry_date": "12/25"
}
```

#### Process PayPal Payment
```http
POST /api/payments
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "order_id": 1,
  "payment_method": "paypal",
  "paypal_email": "buyer@example.com"
}
```

#### Process Stripe Payment
```http
POST /api/payments
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "order_id": 1,
  "payment_method": "stripe",
  "stripe_token": "tok_1234567890abcdef"
}
```

#### Get All Payments
```http
GET /api/payments?order_id=1&status=successful&per_page=10
Authorization: Bearer {jwt_token}
```

#### Get Payment by ID
```http
GET /api/payments/{id}
Authorization: Bearer {jwt_token}
```

#### Get Available Payment Gateways
```http
GET /api/payment-gateways
Authorization: Bearer {jwt_token}
```

## Testing

Run the test suite:

```bash
# Run all tests
php artisan test

# Run specific test files
php artisan test --filter=OrderTest
php artisan test --filter=PaymentTest
php artisan test --filter=AuthTest

# Run with coverage
php artisan test --coverage
```

## Postman Collection

Import the provided `postman_collection.json` file into Postman to test all API endpoints with pre-configured requests and examples.

## Business Rules

1. **Order Deletion**: Orders can only be deleted if they have no associated payments
2. **Payment Processing**: Payments can only be processed for orders in "confirmed" status
3. **User Isolation**: Users can only access their own orders and payments
4. **Gateway Configuration**: Payment gateways must be properly configured to be available

## Error Handling

The API returns consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["validation error message"]
  }
}
```

## Response Format

All successful responses follow this format:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data
  }
}
```

## Security

- JWT token-based authentication
- Input validation and sanitization
- SQL injection protection via Eloquent ORM
- CSRF protection for web routes
- Rate limiting (can be configured)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).