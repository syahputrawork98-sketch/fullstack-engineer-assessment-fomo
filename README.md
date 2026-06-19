# Fullstack Engineer Assessment

This project is a Laravel-based solution for the assessment. It contains an Online Store API and a Hidden Item command-line program.

## Tech Stack
- PHP 8
- Laravel 9
- SQLite

## Setup

1. Install Composer dependencies:
   ```bash
   composer install
   ```

2. Copy the environment file:
   *   **Linux/Mac:**
       ```bash
       cp .env.example .env
       ```
   *   **Windows PowerShell:**
       ```powershell
       Copy-Item .env.example .env
       ```

3. Generate the application key:
   ```bash
   php artisan key:generate
   ```

4. Create the SQLite database file:
   *   **Linux/Mac:**
       ```bash
       touch database/database.sqlite
       ```
   *   **Windows PowerShell:**
       ```powershell
       New-Item -Path database/database.sqlite -ItemType File -Force
       ```

5. Set the database connection in `.env`:
   ```env
   DB_CONNECTION=sqlite
   ```

6. Run the database migrations and seeders:
   ```bash
   php artisan migrate:fresh --seed
   ```

## Run API

To run the local server:
```bash
php artisan serve
```
The base API URL will be: `http://127.0.0.1:8000/api`

## API Endpoints

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| GET | `/api/products` | Get list of products |
| GET | `/api/products/{id}` | Get product details |
| POST | `/api/orders` | Place a new order |
| GET | `/api/orders/{id}` | Get order details |

## Create Order Example

Send a `POST` request to `/api/orders` with the following body:

```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 1
    }
  ]
}
```

*   **Success Response:** Returns a HTTP `201 Created` status with the order details.
*   **Failed Response:** If the product stock is insufficient, the API returns a HTTP `409 Conflict` status with an error message.

## Stock Handling

Order creation is handled inside a database transaction. The application checks product stock before saving the order and updating stock. If stock is not enough, it returns 409 Conflict. This prevents stock from becoming negative during flash sale order attempts.

The implementation also uses Laravel's lockForUpdate() for databases that support row-level locking.

## Run Tests

Run all tests:
```bash
php artisan test
```

Run only the flash sale negative stock test:
```bash
php artisan test --filter=FlashSaleRaceConditionTest
```

The `FlashSaleRaceConditionTest` ensures that multiple order attempts do not make the product stock negative.

## Hidden Item CLI

To run the hidden item puzzle solver:

```bash
# Auto solve mode
php artisan hidden-item:solve

# Exact movement mode
php artisan hidden-item:solve --up=2 --right=3 --down=1
```

The command calculates and prints the probable item locations on the grid, and also prints the grid with those locations marked with a `$` symbol.

## Notes

This project uses SQLite to keep local setup simple. For a real flash sale system, MySQL or PostgreSQL would be better for production-level locking and concurrency testing.
