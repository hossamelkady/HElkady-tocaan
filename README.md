# Laravel Order & Payment API

A Laravel-based API for managing orders and payments with JWT authentication and extensibility for payment gateways.

## Setup Instructions

1. **Clone the repository** (if applicable) or navigate to the project logic directory.
2. **Install Dependencies**:
   ```bash
   composer install
   ```
3. **Configure Environment**:
   - Copy `.env.example` to `.env`
   - Set database credentials (DB_DATABASE, etc.)
4. **Generate App Key**:
   ```bash
   php artisan key:generate
   ```
5. **Run Migrations**:
   ```bash
   php artisan migrate
   ```
6. **JWT Setup**:
   - Generate JWT Secret:
     ```bash
     php artisan jwt:secret
     ```
7. **Serve Application**:
   ```bash
   php artisan serve
   ```
   API URL: `http://localhost:8000/api`

## Extensibility: Adding a New Payment Gateway

The system uses the **Strategy Pattern** for payment gateways. To add a new gateway:

1. **Create the Gateway Class**:
   Create a new class in `app/Services/Payment/Gateways` that implements `App\Services\Payment\PaymentGatewayInterface`.
   
   ```php
   namespace App\Services\Payment\Gateways;

   use App\Services\Payment\PaymentGatewayInterface;

   class MyNewGateway implements PaymentGatewayInterface
   {
       public function process(float $amount, array $details): array
       {
           // Implement payment logic here
           return ['status' => 'successful', 'transaction_id' => '...'];
       }
   }
   ```

2. **Register the Gateway**:
   Update `app/Services/Payment/PaymentGatewayFactory.php` to include the new gateway in the `create` method.

   ```php
   public static function create(string $method): PaymentGatewayInterface
   {
       return match ($method) {
           'credit_card' => new CreditCardGateway(),
           'paypal' => new PayPalGateway(),
           'my_new_method' => new MyNewGateway(), // Add this line
           default => throw new InvalidArgumentException("Payment method {$method} is not supported."),
       };
   }
   ```

## API Documentation

A Postman collection is included in this repository: `postman_collection.json`. Import this file into Postman to test the endpoints.

### Endpoints Overview

#### Authentication
- `POST /api/auth/register` - Register a new user
- `POST /api/auth/login` - Login and get JWT token
- `POST /api/auth/me` - Get current user profile
- `POST /api/auth/logout` - Logout

#### Orders
- `GET /api/orders` - List all orders (filterable by status)
- `POST /api/orders` - Create a new order with items
- `GET /api/orders/{id}` - Get order details
- `PUT /api/orders/{id}` - Update order status
- `DELETE /api/orders/{id}` - Delete an order (if no payments)

#### Payments
- `POST /api/payments` - Process a payment for a confirmed order
- `GET /api/payments` - List executed payments
- `GET /api/payments/{id}` - Get payment details


## Assumptions & Notes

- **Payment Simulation**: The payment gateways (Credit Card, PayPal) are currently mock implementations. They simulate a successful response without connecting to a real provider.
- **Business Logic**: 
  - Payments can ONLY be processed for orders that are in the `confirmed` status.
  - An order cannot be deleted if it has associated payments.
- **Currency**: The system currently defaults to `USD` for all transactions.
- **Database**: The project is configured to use SQLite by default for ease of setup, but can be switched to MySQL/PostgreSQL via `.env`.
