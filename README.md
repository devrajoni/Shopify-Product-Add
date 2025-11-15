<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

# Shopify Product API - Laravel

This project is a **Laravel RESTful API** to create Shopify products with variations and images.  
It uses the **Repository Pattern**, integrates with Shopify's **Admin GraphQL API (v2025-07)**, and handles product creation, inventory, and images automatically.

---

## 🔧 Requirements

- PHP ^8.2
- Laravel ^12
- Composer
- GuzzleHTTP ^7.10
- Shopify Partner Account & Admin API token

---

## ⚙️ Project Setup

### Clone the repository

```bash
git clone <https://github.com/devrajoni/Shopify-Products-Creation>
cd <shopify-product-api>
Install dependencies
bash
Copy code
composer install
Copy .env file and configure
bash
Copy code
cp .env.example .env
Set environment variables in .env
dotenv
Copy code
APP_NAME=ShopifyProductAPI
APP_ENV=local
APP_KEY=base64:GENERATE_KEY
APP_DEBUG=true
APP_URL=http://localhost

# Database (if needed)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shopify_api
DB_USERNAME=root
DB_PASSWORD=
Generate application key
bash
Copy code
php artisan key:generate
Run migrations (if applicable)
bash
Copy code
php artisan migrate
Start the development server
bash
Copy code
The API will run at: Your baseurl

🛠️ API Usage
Endpoint

bash
Copy code
POST /api/shopify/products
Headers

Accept: application/json

Content-Type: application/json

X-Shopify-Access-Token: YOUR_ADMIN_API_TOKEN

X-Shopify-Shop-Domain: YOUR_STORE.myshopify.com

JSON Body Example

json
Copy code
{
  "title": "Cotton T-Shirt Premium",
  "description": "<p>High quality premium cotton t-shirt</p>",
  "vendor": "My Brand",
  "product_type": "Apparel",
  "status": "active",
  "options": [
    { "name": "Size", "values": ["Small", "Medium", "Large"] },
    { "name": "Color", "values": ["Red", "Blue"] }
  ],
  "variants": [
    {
      "options": { "Size": "Small", "Color": "Red" },
      "price": 25.00,
      "inventory_quantity": 10,
      "sku": "TSHIRT-S-R"
    },
    {
      "options": { "Size": "Medium", "Color": "Blue" },
      "price": 27.00,
      "inventory_quantity": 15,
      "sku": "TSHIRT-M-B"
    }
  ],
  "images": [
    { "src": "https://example.com/image1.jpg", "alt": "T-Shirt Red Small" },
    { "src": "https://example.com/image2.jpg", "alt": "T-Shirt Blue Medium" }
  ]
}
Response Example

json
Copy code
{
  "success": true,
  "data": {
    "product": { "id": "...", "title": "Cotton T-Shirt Premium", ... },
    "variants": [...],
    "images": [...]
  }
}
🧪 Testing
A basic PHPUnit test is included for the endpoint:

bash
Copy code
php artisan test
📦 Code Structure
App\Repositories – Repository Pattern implementation for Shopify API requests

App\Http\Requests – Form Requests for validation

App\Http\Controllers\Api – API controller

routes/api.php – API routes

✅ Guidelines Followed
Repository Pattern used for Shopify API abstraction

Laravel Form Requests for input validation

Guzzle exceptions & Shopify API errors handled gracefully

PSR-12 coding standard and type hints applied

Single-responsibility methods and clean, readable code