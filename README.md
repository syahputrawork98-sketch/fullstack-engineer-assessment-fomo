# Fullstack Engineer Assessment - PT Fomo Inovasi Teknologi

This repository contains the Fullstack Engineer Assessment solution, implemented using Laravel 9 and SQLite.

## Overview

The project comprises two main tasks:
1. **Task 1: Online Store API**: A RESTful JSON API handling product display and order placement, designed to prevent negative stock during concurrent flash sale conditions.
2. **Task 2: Hidden Item CLI**: A Laravel Artisan command-line tool that solves a grid-based pathfinding puzzle using movement constraints.

---

## Requirements Covered

### Task 1 - Online Store API
- [x] Order consists of at least one order item.
- [x] Product has inventory stock.
- [x] API uses JSON responses.
- [x] API uses proper HTTP status codes.
- [x] Stock cannot become negative.
- [x] Race condition is handled using database transaction and row-level locking.
- [x] Functional test is available from command line.

### Task 2 - Hidden Item CLI
- [x] Grid contains obstacle `#`, clear path `.`, and starting position `X`.
- [x] Player moves Up, Right, and Down.
- [x] Program outputs probable coordinate points.
- [x] **Bonus**: Grid is displayed with probable item locations marked with `$`.

---

## Installation

Run the following commands in your terminal:

```bash
# Clone the repository
git clone https://github.com/syahputrawork98-sketch/fullstack-engineer-assessment-fomo.git
cd fullstack-engineer-assessment-fomo

# Install dependencies
composer install

# Copy environment file
# For Linux / Mac / Git Bash:
cp .env.example .env

# For Windows PowerShell:
Copy-Item .env.example .env

# Generate application key
php artisan key:generate
```

---

## SQLite Setup

For local development and testing, this project is configured to use SQLite.

### 1. Create SQLite database file
*   **For Linux / Mac / Git Bash:**
    ```bash
    touch database/database.sqlite
    ```
*   **For Windows PowerShell:**
    ```powershell
    New-Item -Path database/database.sqlite -ItemType File -Force
    ```

### 2. Configure Environment (`.env`)
Update the database connection settings in `.env` to:
```env
DB_CONNECTION=sqlite
```
*Note: You can safely comment out or delete MySQL-specific configurations (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). Both `.env` and `database/database.sqlite` are ignored by Git and do not need to be committed.*

---

## Database Migration and Seeder

Run the migration and seed the database with initial products:
```bash
php artisan migrate --seed
```
This seeder will create the following flash sale product:
- **Name:** `Flash Sale Product`
- **Price:** `50000`
- **Stock:** `10`

---

## Run API Server

Start the local PHP development server:
```bash
php artisan serve
```
The API is available at:
`http://127.0.0.1:8000/api`

---

## API Endpoints

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/products` | Retrieve all products |
| `GET` | `/api/products/{id}` | Retrieve details of a specific product |
| `POST` | `/api/orders` | Place a new order |
| `GET` | `/api/orders/{id}` | Retrieve details of a specific order |

---

## API Examples

### 1. Retrieve Products
`GET /api/products`

**Response (200 OK):**
```json
{
    "message": "Products retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "Flash Sale Product",
            "price": 50000,
            "stock": 10
        }
    ]
}
```

### 2. Place Order
`POST /api/orders`

**Request Body:**
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

**Response Success (201 Created):**
```json
{
    "message": "Order created successfully",
    "data": {
        "order_id": 1,
        "order_number": "ORD-20260619-786933",
        "total_price": 50000,
        "items": [
            {
                "product_id": 1,
                "product_name": "Flash Sale Product",
                "quantity": 1,
                "price": 50000,
                "subtotal": 50000
            }
        ]
    }
}
```

**Response Failure - Insufficient Stock (409 Conflict):**
```json
{
    "message": "Insufficient stock for product Flash Sale Product"
}
```

---

## Race Condition Handling

To handle concurrent purchase requests during flash sales and prevent negative inventory:
1.  **Database Transactions (`DB::transaction()`):** Guarantees atomicity. If checking, subtracting stock, or writing order records fails, the entire transaction is rolled back.
2.  **Pessimistic Locking (`lockForUpdate()`):** Acquires a row-level lock on the products being checked. Concurrent requests trying to write to the same rows must wait until the current transaction commits or rolls back, ensuring stock reads are accurate.

---

## Run Race Condition Test

To verify the robust locking behavior, run the feature test:
```bash
php artisan test --filter=FlashSaleRaceConditionTest
```
Or run the entire test suite:
```bash
php artisan test
```

### Test Details & Expected Result:
- Simulates 30 order attempts for a product with initial stock 10.
- Exactly 10 orders must succeed (HTTP 201).
- Exactly 20 orders must be rejected due to insufficient stock (HTTP 409).
- Product final stock must equal 0 (never negative).

---

## Hidden Item CLI

Artisan command to solve the hidden item grid game:
```bash
php artisan hidden-item:solve [options]
```

### Execution Modes:
*   **Mode A - Auto Solve:**
    ```bash
    php artisan hidden-item:solve
    ```
    Tries all valid positive combinations of $A$ (Up), $B$ (Right), and $C$ (Down) steps, lists unique destinations, and draws the grid with probable locations marked with `$`.
*   **Mode B - Exact Movement:**
    ```bash
    php artisan hidden-item:solve --up=2 --right=3 --down=1
    ```
    Checks validation for exact step inputs and prints final location or invalid message.

*Note: One-based coordinate system is used for user outputs (`Row X, Col Y`).*

### Grid Symbol Reference:
```
########
#......#
#.###..#
#...#.##
#X#....#
########
```
- `#` = Obstacle
- `.` = Clear path
- `X` = Player starting position
- `$` = Probable item location

---

## Git Commit Notes

Meaningful commit messages were used in this repository to track progress:
- `Initial commit`
- `feat: initialize new Laravel project structure`
- `feat: add Order, OrderItem, and Product models with corresponding database migrations`
- `Add flash sale product seeder`
- `Implement product API endpoints`
- `Implement order API with stock locking`
- `Add flash sale race condition test`
- `Add hidden item CLI command`

---

## Final Verification Commands

Use these commands to verify the complete solution:
```bash
# Verify route definitions
php artisan route:list

# Reset and seed database
php artisan migrate:fresh --seed

# Run tests
php artisan test

# Run hidden item solver
php artisan hidden-item:solve
```
