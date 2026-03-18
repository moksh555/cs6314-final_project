# Travel Deals - CS6314 Web Technologies Final Project

A multi-page travel booking web application built for the CS6314 Web Technologies course at UT Dallas. The platform lets users search and book flights and hotel stays, manage a shopping cart, and view booking history, all backed by a PHP server and XML/JSON data storage.

## Key Features

- **Flight Search and Booking** - Search one-way or round-trip flights by origin, destination, date, and passenger count; results are fetched from an XML data source via AJAX
- **Hotel Stays Search** - Browse and book hotel accommodations, with data served from a JSON file
- **Shopping Cart** - Separate carts for flight and hotel bookings, persisted across pages
- **Booking History** - Dedicated pages to review past flight and hotel reservations
- **User Authentication** - Login and registration with server-side PHP validation and session management
- **Accessibility Controls** - Dynamic font size (Normal / Large / Extra Large) and background color (White / Gray / Black) toggleable on every page
- **Contact Form** - Client-side validation with AJAX submission storing enquiries to a server-side XML file

## Tech Stack

| Layer | Technologies |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (jQuery, AJAX) |
| Backend | PHP, Node.js (Express) |
| Data Storage | XML, JSON |
| Icons | Font Awesome 6 |

## Setup and Installation

Prerequisites: PHP 7+ and Node.js installed locally.

1. Clone the repository:
   ```bash
   git clone https://github.com/moksh555/cs6314-final_project.git
   cd cs6314-final_project
   ```
2. Install Node dependencies:
   ```bash
   npm install
   ```
3. Start a local PHP development server from the project root:
   ```bash
   php -S localhost:8080
   ```
4. Open http://localhost:8080/index.html in your browser.

Note: The PHP server handles login, registration, contact form submissions, and data reads from flights.xml and hotels.json. Make sure the server has write permissions on contacts.xml and bookings.xml.

## Usage

- Navigate to **Flights** to search for available flights and add them to your cart
- Navigate to **Stays** to browse hotel options and add them to your hotel cart
- Visit **My Flight Cart** or **My Cart Hotel** to review selections before booking
- View **Flight Booking** or **Hotel Booking** history pages to see confirmed reservations
- Use **My Account** to manage your profile after logging in
- Use the accessibility controls at the top of any page to adjust font size and background color

## Contributors

- Moksh Vaghasia (MXV220071)
- Kyu Shim (KJS170230)
- Aditya Swaroop (AXS230571)

CS6314 Web Technologies - Section 002, UT Dallas
