# 🛒 Symfony E-commerce API

Welcome to the Symfony E-commerce API! This project provides endpoints for user management, product management, cart management, and order processing. It's built with Symfony and uses JWT for authentication.

## 📋 Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [API Endpoints](#api-endpoints)
- [Roles and Permissions](#roles-and-permissions)
- [Postman Collection](#postman-collection)

## 🌟 Project Description

This API allows you to manage users, products, carts, and orders for an e-commerce platform. It includes features like:

- User registration, login, and profile management
- CRUD operations for products
- Cart management (add, remove, validate, and pay for items)
- Order management and history tracking

## 🛠️ Installation

1. **Clone the repository**:

   ```bash
   git clone https://github.com/Yasser5711/Symfony-E-commerce-API.git
   cd Symfony-E-commerce-API
   ```

2. **Install dependencies**:

   ```bash
   composer install
   ```

3. **Set up environment variables**:
   Copy the `.env` file and set your environment variables.

   ```bash
   cp .env.example .env
   ```

4. **Generate the JWT keys**:

   ```bash
   mkdir -p config/jwt
   openssl genpkey -algorithm RSA -out config/jwt/private.pem -aes256
   openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
   ```

5. **Database setup**:
   Update your `.env` file with your database credentials and then run the migrations.
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

## ⚙️ Configuration

- **JWT_SECRET_KEY**: Set this value in your `.env` file.
- **Database**: Configure your database connection in the `.env` file.

## 🚀 Running the Application

1. **Start the Symfony server**:

   ```bash
   symfony server:start
   ```

2. **Access the application**:
   The application should now be running at `http://127.0.0.1:8000`.

## 🔌 API Endpoints

### User Endpoints

- **Register**: `POST /api/register`
- **Login**: `POST /api/login`
- **Logout**: `POST /api/logout`
- **Get User**: `GET /api/get-user` (Authenticated)
- **Update User**: `POST /api/get-user` (Authenticated)
- **Delete User**: `DELETE /api/get-user` (Authenticated)
- **Get All Users**: `GET /api/get-all-users` (Admin)
- **Update User Role**: `POST /api/update-user-role` (Admin)

### Product Endpoints

- **Get All Products**: `GET /api/products`
- **Get Product**: `GET /api/products/{id}`
- **Add Product**: `POST /api/products` (Admin)
- **Update Product**: `PUT /api/products/{id}` (Admin)
- **Delete Product**: `DELETE /api/products/{id}` (Admin)

### Cart Endpoints

- **Add to Cart**: `POST /api/carts` (Authenticated)
- **Get User Cart**: `GET /api/carts` (Authenticated)
- **Validate Cart**: `PATCH /api/carts/validate` (Authenticated)
- **Pay Cart**: `PATCH /api/carts/pay/{id}` (Authenticated)
- **Remove Product from Cart**: `DELETE /api/carts/{id}` (Authenticated)

### Order Endpoints

- **Get All Orders**: `GET /api/orders` (Authenticated)
- **Get Order**: `GET /api/orders/{id}` (Authenticated)
- **Cancel Order**: `DELETE /api/orders/{id}` (Authenticated)
- **Order History**: `GET /api/history` (Authenticated)

## 🛡️ Roles and Permissions

- **Admin**: Can manage all users, products, and has access to all routes.
- **User**: Can manage their own profile, cart, and orders. Can view products.

### Postman Collection

A Postman collection is available to test the API endpoints. Import the provided collection file into Postman.

## 📥 Postman Collection

To use the Postman collection:

1. Open Postman.
2. Click on the **Import** button.
3. Select the `postman_collection.json` file from the repository and import it.

You can find the Postman collection file here: [postman_collection.json](postman_collection.json)
