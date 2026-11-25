# Tocaan: Laravel Order & Payment API

[English](#english) | [العربية](#العربية)

## English

A Laravel 12 REST API for orders and payments. JWT authentication, order items, and payment gateways behind a Strategy pattern so new gateways are easy to add.

### Features

- JWT auth (register, login, refresh, logout, me) with `tymon/jwt-auth`
- Orders with line items; total is calculated on the server
- Order status flow: `pending` -> `confirmed` -> `cancelled` (cancelled is final; a paid order cannot be cancelled or deleted)
- Payments for confirmed orders only, one successful payment per order
- Pluggable gateways: `credit_card` and `paypal` (mock implementations)
- Every user sees only their own orders and payments
- JSON error responses on all `/api/*` routes
- PHPUnit tests (feature + unit) and a Postman collection

### Requirements

PHP 8.2+, Composer, SQLite (default) or MySQL/PostgreSQL.

### Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret      # writes JWT_SECRET to .env
php artisan migrate
php artisan serve           # http://localhost:8000/api
```

### Run the tests

```bash
php artisan test
```

Tests use an in-memory SQLite database and a built-in test JWT secret (see `phpunit.xml`), so no extra setup is needed.

### Endpoints

| Method | Path | Description |
| --- | --- | --- |
| POST | `/api/auth/register` | Register (`name`, `email`, `password`, `password_confirmation`) |
| POST | `/api/auth/login` | Login, returns a JWT |
| POST | `/api/auth/me` | Current user (auth) |
| POST | `/api/auth/refresh` | New token (auth) |
| POST | `/api/auth/logout` | Invalidate token (auth) |
| GET | `/api/orders` | List your orders, filter with `?status=` |
| POST | `/api/orders` | Create an order from `items[]` (`product_name`, `quantity`, `price`) |
| GET | `/api/orders/{id}` | Order with items and payments |
| PUT | `/api/orders/{id}` | Change status |
| DELETE | `/api/orders/{id}` | Delete (only if it has no payments) |
| POST | `/api/payments` | Pay a confirmed order (`order_id`, `payment_method`) |
| GET | `/api/payments` | List your payments, filter with `?order_id=` |
| GET | `/api/payments/{id}` | Payment details |

Send the token as `Authorization: Bearer <token>` and `Accept: application/json`. Import `postman_collection.json` into Postman for ready-made requests.

### Adding a payment gateway

1. Create a class in `app/Services/Payment/Gateways` that implements `PaymentGatewayInterface`:

```php
namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGatewayInterface;

class MyNewGateway implements PaymentGatewayInterface
{
    public function process(float $amount, array $details): array
    {
        return ['status' => 'successful', 'transaction_id' => '...'];
    }
}
```

2. Register it in the `GATEWAYS` map in `app/Services/Payment/PaymentGatewayFactory.php`:

```php
'my_new_method' => MyNewGateway::class,
```

The method is then accepted by `POST /api/payments` automatically.

### Notes

- The gateways are mocks. They always return a successful result and do not call a real provider.
- All payments use `USD`.
- Default database is SQLite. Change `DB_*` in `.env` for MySQL or PostgreSQL.

### License

MIT, see [LICENSE](LICENSE).

---

## العربية

واجهة API بلارافيل 12 لإدارة الطلبات والمدفوعات. فيها تسجيل دخول بـ JWT، وطلبات بعناصرها، وبوابات دفع بنمط Strategy عشان تضيف بوابة جديدة بسهولة.

### المميزات

- تسجيل دخول JWT (تسجيل، دخول، تجديد، خروج، بياناتي) بمكتبة `tymon/jwt-auth`
- طلبات بعناصر، والإجمالي بيتحسب في السيرفر
- مسار حالة الطلب: `pending` ثم `confirmed` ثم `cancelled` (الملغي نهائي، والطلب المدفوع مينفعش يتلغي أو يتمسح)
- الدفع للطلبات المؤكدة بس، ودفعة ناجحة واحدة لكل طلب
- بوابات دفع قابلة للتوسع: `credit_card` و `paypal` (تجريبية)
- كل مستخدم بيشوف طلباته ومدفوعاته بس
- ردود JSON لكل مسارات `/api/*`
- تيستات PHPUnit وملف Postman

### المتطلبات

PHP 8.2 أو أحدث، Composer، وSQLite (الافتراضي) أو MySQL/PostgreSQL.

### التشغيل

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret      # بيكتب JWT_SECRET في .env
php artisan migrate
php artisan serve           # http://localhost:8000/api
```

### تشغيل التيستات

```bash
php artisan test
```

التيستات بتستخدم SQLite في الذاكرة ومفتاح JWT للاختبار جاهز في `phpunit.xml`، فمفيش إعداد زيادة.

### المسارات

| الطريقة | المسار | الوصف |
| --- | --- | --- |
| POST | `/api/auth/register` | تسجيل مستخدم جديد |
| POST | `/api/auth/login` | دخول وياخد توكن JWT |
| POST | `/api/auth/me` | بيانات المستخدم الحالي |
| POST | `/api/auth/refresh` | توكن جديد |
| POST | `/api/auth/logout` | إلغاء التوكن |
| GET | `/api/orders` | طلباتك، وتفلتر بـ `?status=` |
| POST | `/api/orders` | إنشاء طلب من `items[]` |
| GET | `/api/orders/{id}` | تفاصيل الطلب بعناصره ومدفوعاته |
| PUT | `/api/orders/{id}` | تغيير الحالة |
| DELETE | `/api/orders/{id}` | حذف (لو مفيهوش مدفوعات) |
| POST | `/api/payments` | دفع طلب مؤكد |
| GET | `/api/payments` | مدفوعاتك |
| GET | `/api/payments/{id}` | تفاصيل دفعة |

ابعت التوكن في `Authorization: Bearer <token>` ومعاه `Accept: application/json`. استورد `postman_collection.json` في Postman عشان تجرب الطلبات.

### إضافة بوابة دفع جديدة

1. اعمل كلاس في `app/Services/Payment/Gateways` يطبق `PaymentGatewayInterface`.
2. سجله في مصفوفة `GATEWAYS` جوه `app/Services/Payment/PaymentGatewayFactory.php`.

وبكده طريقة الدفع الجديدة بتتقبل في `POST /api/payments` لوحدها.

### ملاحظات

- البوابات تجريبية: بترجع نجاح دايما ومش بتكلم مزود حقيقي.
- كل المدفوعات بالدولار `USD`.
- قاعدة البيانات الافتراضية SQLite، وغيّر `DB_*` في `.env` لو عايز MySQL أو PostgreSQL.

### الرخصة

MIT، شوف [LICENSE](LICENSE).
