# E-Commerce Web Application

An e-commerce website built using PHP and MySQL that provides online shopping functionality with user and administrator features.

## Features

- User registration and login
- Product catalog
- Shopping cart management
- Checkout and order placement
- Order tracking
- Admin dashboard
- Admin product management
- Admin order management
- Role-based access control

## Technologies Used

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- XAMPP
- Visual Studio Code
- phpMyAdmin

## Project Structure

```text
ecommerce-web-application-main/
├── admin_area/
│   ├── index.php
│   ├── orders.php
│   └── products.php
├── cart.php
├── checkout.php
├── dashboard.php
├── index.php
├── login.php
├── logout.php
├── products.php
├── register.php
├── track_order.php
├── README.md
└── mystore_db.sql
```

## Requirements

- XAMPP
- PHP
- MySQL
- A web browser
- Visual Studio Code (recommended)

## Installation and Setup

### 1. Install XAMPP

Download and install XAMPP on your computer.

### 2. Copy the Project

Extract the project folder into:

```text
C:\xampp\htdocs\
```

### 3. Start Apache and MySQL

Open the XAMPP Control Panel and start both **Apache** and **MySQL**.

### 4. Create the Database

1. Open `http://localhost/phpmyadmin/`.
2. Create a database named `mystore_db` if it does not already exist.
3. Select the `mystore_db` database.
4. Click the **Import** tab.
5. Choose the `mystore_db.sql` file.
6. Click **Import** or **Go** to import the database.

### 5. Configure the Database Connection

Check the database connection settings in your PHP files. Make sure the database name, username, password, and MySQL server settings match your local XAMPP configuration.

### 6. Run the Website

If your extracted folder is named `ecommerce-web-application-main`, open:

http://localhost/ecommerce-web-application-main/

If you renamed the folder, use its actual name in the URL.

## Database

The project uses MySQL to store application data, including users, products, categories, orders, and order items.

The `mystore_db.sql` file contains the database export required to set up the database locally.

## Purpose

This project demonstrates the development of an e-commerce web application using PHP and MySQL, including product browsing, shopping cart functionality, checkout, and administrative management.

## Author

Thanushree
